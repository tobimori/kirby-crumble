<?php

namespace tobimori\Crumble\Migrations\migrations;

use tobimori\Crumble\Migrations\Migration;

class Migration001CreateConsentLogsTable extends Migration
{
	/**
	 * Get migration version
	 */
	public function version(): string
	{
		return '001';
	}

	/**
	 * Run the migration
	 */
	public function up(): void
	{
		$db = $this->db();

		if ($this->isSqlite()) {
			$sql = "CREATE TABLE IF NOT EXISTS crumble_consent_logs (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				consent_id TEXT NOT NULL,
				action TEXT NOT NULL,
				timestamp TEXT NOT NULL,
				ip_address TEXT NOT NULL,
				user_agent TEXT,
				consent_version TEXT,
				accept_type TEXT,
				accepted_categories TEXT,
				rejected_categories TEXT,
				accepted_services TEXT,
				rejected_services TEXT,
				language TEXT,
				country_code TEXT,
				page_url TEXT,
				expires_at TEXT NOT NULL,
				created_at TEXT DEFAULT CURRENT_TIMESTAMP
			)";

			$db->execute($sql);

			// create indexes
			$db->execute("CREATE INDEX IF NOT EXISTS idx_consent_id ON crumble_consent_logs (consent_id)");
			$db->execute("CREATE INDEX IF NOT EXISTS idx_ip_address ON crumble_consent_logs (ip_address)");
			$db->execute("CREATE INDEX IF NOT EXISTS idx_timestamp ON crumble_consent_logs (timestamp)");
			$db->execute("CREATE INDEX IF NOT EXISTS idx_expires_at ON crumble_consent_logs (expires_at)");
		} else {
			// mysql
			$sql = "CREATE TABLE IF NOT EXISTS crumble_consent_logs (
				id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				consent_id VARCHAR(36) NOT NULL,
				action VARCHAR(20) NOT NULL,
				timestamp DATETIME NOT NULL,
				ip_address VARCHAR(64) NOT NULL,
				user_agent TEXT,
				consent_version VARCHAR(20),
				accept_type VARCHAR(20),
				accepted_categories JSON,
				rejected_categories JSON,
				accepted_services JSON,
				rejected_services JSON,
				language VARCHAR(10),
				country_code VARCHAR(2),
				page_url TEXT,
				expires_at DATETIME NOT NULL,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_consent_id (consent_id),
				INDEX idx_ip_address (ip_address),
				INDEX idx_timestamp (timestamp),
				INDEX idx_expires_at (expires_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

			$db->execute($sql);
		}
	}

	/**
	 * Rollback the migration
	 */
	public function down(): void
	{
		$this->db()->execute("DROP TABLE IF EXISTS crumble_consent_logs");
	}
}