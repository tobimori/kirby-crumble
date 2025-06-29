<?php

namespace tobimori\Crumble\Log;

use Kirby\Database\Database;
use Kirby\Database\Query;
use tobimori\Crumble\Crumble;

abstract class Log
{
	protected Database $db;
	protected string $table = 'crumble_consent_logs';
	protected static ?self $instance = null;

	public function __construct()
	{
		$this->connect();
	}

	/**
	 * Factory method to get correct instance
	 */
	public static function instance(): static
	{
		if (static::$instance === null) {
			$type = Crumble::option('database.type', 'sqlite');

			static::$instance = match ($type) {
				'mysql' => new LogMysql(),
				default => new LogSqlite()
			};
		}

		return static::$instance;
	}

	/**
	 * Connect to database
	 */
	abstract protected function connect(): void;

	/**
	 * Insert consent record
	 */
	public function insert(array $data): int|false
	{
		return $this->table()->insert($data);
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
		$results = $this->query()
			->where('consent_id', '=', $consentId)
			->order('timestamp DESC')
			->all();
		
		// Convert Collection to array
		$history = [];
		foreach ($results as $result) {
			$history[] = $result;
		}
		
		return $history;
	}

	/**
	 * Delete expired consents
	 */
	public function deleteExpired(): bool
	{
		return $this->table()->delete([
			'expires_at <' => date('Y-m-d H:i:s')
		]);
	}


	/**
	 * Get query builder
	 */
	protected function query(): Query
	{
		return $this->table();
	}

	/**
	 * Returns the database table object
	 */
	public function table(): Query
	{
		return $this->db->crumble_consent_logs();
	}

	/**
	 * Get database instance
	 */
	public function database(): Database
	{
		return $this->db;
	}

	/**
	 * Get paginated logs with filters
	 */
	public function getPaginatedLogs(array $params = []): array
	{
		$page = $params['page'] ?? 1;
		$limit = $params['limit'] ?? 50;
		$offset = ($page - 1) * $limit;
		
		$query = $this->query();
		$countQuery = clone $query;
		
		// apply filters
		$this->applyFilters($query, $params);
		$this->applyFilters($countQuery, $params);
		
		// get total count
		$total = $countQuery->count();
		
		// get logs - explicitly select all columns to ensure country_code is included
		$logs = $query
			->select('*')
			->order('timestamp DESC')
			->limit($limit)
			->offset($offset)
			->all();
		
		return [
			'logs' => $logs,
			'pagination' => [
				'page' => $page,
				'limit' => $limit,
				'total' => $total,
				'pages' => ceil($total / $limit)
			]
		];
	}

	/**
	 * Get logs for export
	 */
	public function getExportLogs(array $params = []): array
	{
		$query = $this->query();
		
		// apply filters
		$this->applyFilters($query, $params);
		
		// get all logs matching filters
		return $query
			->order('timestamp DESC')
			->all();
	}

	/**
	 * Apply filters to query
	 */
	protected function applyFilters(Query $query, array $params): void
	{
		// search filter (consent_id or ip_address)
		if (!empty($params['search'])) {
			$search = $params['search'];
			$query->where(function($q) use ($search) {
				$q->where('consent_id', 'like', '%' . $search . '%')
				  ->orWhere('ip_address', 'like', '%' . $search . '%');
			});
		}
		
		// time range filter
		if (!empty($params['timeRange']) && $params['timeRange'] !== 'all') {
			$now = date('Y-m-d H:i:s');
			$dateFrom = match($params['timeRange']) {
				'1h' => date('Y-m-d H:i:s', strtotime('-1 hour')),
				'24h' => date('Y-m-d H:i:s', strtotime('-24 hours')),
				'7d' => date('Y-m-d H:i:s', strtotime('-7 days')),
				'30d' => date('Y-m-d H:i:s', strtotime('-30 days')),
				default => null
			};
			
			if ($dateFrom) {
				$query->where('timestamp', '>=', $dateFrom);
			}
		}
		
		// category filter
		if (!empty($params['category'])) {
			$query->where('accepted_categories', 'like', '%"' . $params['category'] . '"%');
		}
		
		// action filter
		if (!empty($params['action'])) {
			$query->where('action', '=', $params['action']);
		}
	}
}
