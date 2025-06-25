<?php

namespace tobimori\Crumble\Log;

use Kirby\Database\Database;
use tobimori\Crumble\Crumble;

class LogMysql extends Log
{
	/**
	 * Connect to MySQL database
	 */
	protected function connect(): void
	{
		$this->db = new Database([
			'type' => 'mysql',
			'host' => Crumble::option('database.mysql.host', 'localhost'),
			'port' => Crumble::option('database.mysql.port', 3306),
			'database' => Crumble::option('database.mysql.database', 'kirby'),
			'user' => Crumble::option('database.mysql.user', 'root'),
			'password' => Crumble::option('database.mysql.password', '')
		]);
	}
}
