<?php

namespace tobimori\Crumble\Log;

use Kirby\Database\Database;
use Kirby\Database\Query;
use tobimori\Crumble\Crumble;

abstract class Log
{
	protected Database $db;
	protected string $table = 'crumble_consent_logs';

	public function __construct()
	{
		$this->connect();
	}

	/**
	 * Factory method to get correct instance
	 */
	public static function instance(): static
	{
		$type = Crumble::option('database.type', 'sqlite');

		return match ($type) {
			'mysql' => new LogMysql(),
			default => new LogSqlite()
		};
	}

	/**
	 * Connect to database
	 */
	abstract protected function connect(): void;

	/**
	 * Insert consent record
	 */
	public function insert(array $data): int
	{
		return $this->db->insert($this->table, $data);
	}

	/**
	 * Find latest consent by ID
	 */
	public function findLatestByConsentId(string $consentId): ?array
	{
		return $this->query()
			->where('consent_id', '=', $consentId)
			->order('timestamp DESC')
			->first();
	}

	/**
	 * Find consents by IP address
	 */
	public function findByIp(string $ip): array
	{
		return $this->query()
			->where('ip_address', '=', $ip)
			->order('timestamp DESC')
			->all();
	}

	/**
	 * Get consent history
	 */
	public function getHistory(string $consentId): array
	{
		return $this->query()
			->where('consent_id', '=', $consentId)
			->order('timestamp DESC')
			->all();
	}

	/**
	 * Delete expired consents
	 */
	public function deleteExpired(): int
	{
		return $this->db->delete($this->table, [
			'expires_at <' => date('Y-m-d H:i:s')
		]);
	}


	/**
	 * Get query builder
	 */
	protected function query(): Query
	{
		return $this->db->query($this->table);
	}

	/**
	 * Get database instance
	 */
	public function database(): Database
	{
		return $this->db;
	}
}
