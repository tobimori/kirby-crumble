<?php

namespace tobimori\Crumble\Log;

use Kirby\Cms\App;
use Kirby\Database\Database;
use tobimori\Crumble\Crumble;

class LogSqlite extends Log
{
	/**
	 * Connect to SQLite database
	 */
	protected function connect(): void
	{
		$kirby = App::instance();
		$path = Crumble::option('database.sqlite.path', $kirby->root('logs') . '/crumble/consent.sqlite');

		// ensure directory exists
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		$this->db = new Database([
			'type' => 'sqlite',
			'database' => $path
		]);
		
		// Set SQLite to use WAL mode to prevent locking issues
		try {
			$this->db->execute('PRAGMA journal_mode=WAL');
			$this->db->execute('PRAGMA busy_timeout=5000'); // 5 second timeout
		} catch (\Exception $e) {
			// Ignore pragma errors
		}
	}
}
