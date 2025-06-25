<?php

namespace tobimori\Crumble\Migrations;

use Kirby\Database\Database;
use tobimori\Crumble\Crumble;
use tobimori\Crumble\Log\Log;

abstract class Migration
{
	/**
	 * Get migration version
	 */
	abstract public function version(): string;

	/**
	 * Run the migration
	 */
	abstract public function up(): void;

	/**
	 * Rollback the migration
	 */
	abstract public function down(): void;

	/**
	 * Get database instance
	 */
	protected function db(): Database
	{
		return Log::instance()->database();
	}

	/**
	 * Get database type
	 */
	protected function dbType(): string
	{
		return Crumble::option('database.type', 'sqlite');
	}

	/**
	 * Check if using SQLite
	 */
	protected function isSqlite(): bool
	{
		return $this->dbType() === 'sqlite';
	}

	/**
	 * Check if using MySQL
	 */
	protected function isMysql(): bool
	{
		return $this->dbType() === 'mysql';
	}
}