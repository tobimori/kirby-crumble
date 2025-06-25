<?php

namespace tobimori\Crumble\Log;

use Kirby\Database\Database;
use tobimori\Crumble\Crumble;

class LogSqlite extends Log
{
	/**
	 * Connect to SQLite database
	 */
	protected function connect(): void
	{
		$path = Crumble::option('database.sqlite.path', kirby()->root('logs') . '/crumble/consent.sqlite');

		// ensure directory exists
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		$this->db = new Database([
			'type' => 'sqlite',
			'database' => $path
		]);
	}
}
