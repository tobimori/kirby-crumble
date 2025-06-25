<?php

namespace tobimori\Crumble\Migrations;

use Kirby\Database\Database;
use tobimori\Crumble\Crumble;
use tobimori\Crumble\Log\Log;

class Migrator
{
	/**
	 * Run all pending migrations
	 */
	public static function migrate(): void
	{
		$migrator = new static();
		$migrator->ensureMigrationsTable();
		
		// get all migration classes
		$migrations = $migrator->getMigrations();
		
		foreach ($migrations as $migration) {
			if (!$migrator->hasRun($migration->version())) {
				$migration->up();
				$migrator->markAsRun($migration->version());
			}
		}
	}

	/**
	 * Get all migration instances
	 */
	protected function getMigrations(): array
	{
		$migrations = [];
		$path = __DIR__ . '/migrations';
		
		if (!is_dir($path)) {
			mkdir($path, 0755, true);
			return $migrations;
		}
		
		// scan for migration files
		$files = glob($path . '/Migration*.php');
		
		foreach ($files as $file) {
			$className = 'tobimori\\Crumble\\Migrations\\migrations\\' . basename($file, '.php');
			
			if (class_exists($className)) {
				$migrations[] = new $className();
			}
		}
		
		// sort by version number
		usort($migrations, fn($a, $b) => strcmp($a->version(), $b->version()));
		
		return $migrations;
	}

	/**
	 * Check if migration has been run
	 */
	protected function hasRun(string $version): bool
	{
		$db = $this->db();
		
		$result = $db->table('crumble_migrations')
			->select('*')
			->where('version', '=', $version)
			->first();
			
		return $result !== null;
	}

	/**
	 * Mark migration as complete
	 */
	protected function markAsRun(string $version): void
	{
		$db = $this->db();
		
		$db->insert('crumble_migrations', [
			'version' => $version,
			'run_at' => date('Y-m-d H:i:s')
		]);
	}

	/**
	 * Create migrations tracking table
	 */
	protected function ensureMigrationsTable(): void
	{
		$db = $this->db();
		
		// Try to select from the table - if it fails, table doesn't exist
		try {
			$db->table('crumble_migrations')->select('*')->limit(1)->first();
			return; // Table exists
		} catch (\Exception $e) {
			// Table doesn't exist, continue to create it
		}
		
		$type = Crumble::option('database.type', 'sqlite');
		
		if ($type === 'sqlite') {
			$sql = "CREATE TABLE crumble_migrations (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				version TEXT NOT NULL UNIQUE,
				run_at TEXT NOT NULL
			)";
		} else {
			$sql = "CREATE TABLE crumble_migrations (
				id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				version VARCHAR(255) NOT NULL UNIQUE,
				run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
		}
		
		$db->execute($sql);
	}

	/**
	 * Get database instance
	 */
	protected function db(): Database
	{
		return Log::instance()->database();
	}
}