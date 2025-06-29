<?php

namespace tobimori\Crumble;

use Kirby\Cms\App;
use tobimori\Crumble\Log\Log;

class ConsentManager
{
	/**
	 * Record consent from frontend
	 */
	public static function record(): string
	{
		$cookie = $_COOKIE['cc_cookie'] ?? null;
		if (!$cookie) {
			throw new \Exception('No consent cookie found');
		}

		$data = json_decode(urldecode($cookie), true);
		if (!$data) {
			throw new \Exception('Invalid cookie data');
		}

		$kirby = App::instance();

		// Determine accept type
		$acceptType = 'necessary';
		$acceptedCategories = $data['categories'] ?? [];

		if (!empty($acceptedCategories)) {
			$page = Crumble::page();
			if ($page) {
				$allCategories = $page->children()
					->filterBy('intendedTemplate', 'crumble-category')
					->filterBy('mandatory', false)
					->pluck('slug');

				$acceptType = $allCategories == $acceptedCategories ? 'all' : 'custom';
			} else {
				$acceptType = 'custom';
			}
		}

		Log::instance()->insert([
			'consent_id' => $data['consentId'],
			'action' => 'consent',
			'timestamp' => date('Y-m-d H:i:s'),
			'ip_address' => static::processIp($kirby->visitor()->ip()),
			'user_agent' => $kirby->request()->header('User-Agent'),
			'consent_version' => (string)($data['revision'] ?? '1'),
			'accept_type' => $acceptType,
			'accepted_categories' => json_encode($data['categories'] ?? []),
			'accepted_services' => json_encode($data['services'] ?? []),
			'language' => $data['languageCode'] ?? 'en',
			'country_code' => static::detectCountryCode(),
			'page_url' => $kirby->request()->header('Referer') ?? '',
			'expires_at' => date('Y-m-d H:i:s', strtotime('+' . Crumble::option('expiresAfter', 365) . ' days'))
		]);

		return $cookie;
	}

	/**
	 * Validate consent status
	 */
	public static function validate(string|null $consentId = null): array
	{
		// get consent data from cc_cookie first
		$cookieData = static::getCookieData();

		if (!$consentId) {
			$consentId = $cookieData['consentId'] ?? null;
		}

		if (!$consentId) {
			return ['valid' => false, 'reason' => 'no_consent'];
		}

		// check if cookie is expired
		if ($cookieData && isset($cookieData['expirationTime'])) {
			if ($cookieData['expirationTime'] < time() * 1000) { // js timestamp is in milliseconds
				return ['valid' => false, 'reason' => 'expired'];
			}
		}

		// optionally check database for withdrawn status
		$log = Log::instance();
		$consent = $log->findLatestByConsentId($consentId);

		if ($consent && $consent['action'] === 'withdrawn') {
			return ['valid' => false, 'reason' => 'withdrawn'];
		}

		// if we have cookie data, use that as primary source
		if ($cookieData) {
			return [
				'valid' => true,
				'consent' => [
					'acceptedCategories' => $cookieData['categories'] ?? [],
					'acceptedServices' => $cookieData['services'] ?? [],
					'timestamp' => $cookieData['consentTimestamp'] ?? null,
					'expiresAt' => isset($cookieData['expirationTime']) ? date('Y-m-d H:i:s', $cookieData['expirationTime'] / 1000) : null
				]
			];
		}

		// fallback to database if no cookie
		if (!$consent) {
			return ['valid' => false, 'reason' => 'not_found'];
		}

		return [
			'valid' => true,
			'consent' => [
				'acceptType' => $consent['accept_type'],
				'acceptedCategories' => json_decode($consent['accepted_categories'], true),
				'acceptedServices' => json_decode($consent['accepted_services'], true),
				'timestamp' => $consent['timestamp'],
				'expiresAt' => $consent['expires_at']
			]
		];
	}

	/**
	 * Withdraw consent
	 */
	public static function withdraw(string|null $consentId = null): bool
	{
		// get consent id from cc_cookie if not provided
		if (!$consentId) {
			$cookieData = static::getCookieData();
			$consentId = $cookieData['consentId'] ?? null;
		}

		if (!$consentId) {
			return false;
		}

		// record withdrawal in database for audit trail
		$kirby = App::instance();
		$log = Log::instance();
		$log->insert([
			'consent_id' => $consentId,
			'action' => 'withdrawn',
			'ip_address' => static::processIp($kirby->visitor()->ip()),
			'user_agent' => $kirby->request()->header('User-Agent'),
			'consent_version' => '1',
			'accept_type' => 'none',
			'accepted_categories' => json_encode([]),
			'accepted_services' => json_encode([]),
			'language' => $kirby->language()?->code() ?? 'en',
			'page_url' => $kirby->request()->header('Referer') ?? '',
			'timestamp' => date('Y-m-d H:i:s'),
			'expires_at' => date('Y-m-d H:i:s')
		]);

		// note: cc_cookie should be cleared by the frontend library
		return true;
	}

	/**
	 * Get consent status
	 */
	public static function getStatus(string|null $consentId = null): ?array
	{
		// get consent data from cc_cookie first
		$cookieData = static::getCookieData();

		if (!$consentId) {
			$consentId = $cookieData['consentId'] ?? null;
		}

		if (!$consentId) {
			return null;
		}

		// check database for additional info
		$log = Log::instance();
		$dbRecord = $log->findLatestByConsentId($consentId);

		// merge cookie and database data, preferring cookie data
		if ($cookieData && $dbRecord) {
			return array_merge($dbRecord, [
				'categories' => $cookieData['categories'] ?? [],
				'services' => $cookieData['services'] ?? [],
				'revision' => $cookieData['revision'] ?? null
			]);
		}

		return $dbRecord;
	}

	/**
	 * Export consent data for GDPR
	 */
	public static function export(string $consentId): array
	{
		$log = Log::instance();
		$history = $log->getHistory($consentId);

		return [
			'consentId' => $consentId,
			'history' => array_map(function ($record) {
				// remove sensitive data from export
				unset($record['ip_address']);
				return $record;
			}, $history),
			'exportedAt' => date('Y-m-d H:i:s')
		];
	}

	/**
	 * Get consent data from cc_cookie
	 */
	protected static function getCookieData(): ?array
	{
		$cookie = $_COOKIE['cc_cookie'] ?? null;
		if (!$cookie) {
			return null;
		}

		$decoded = urldecode($cookie);
		$data = json_decode($decoded, true);

		if (!$data || !is_array($data)) {
			return null;
		}

		return $data;
	}

	/**
	 * Process IP address based on privacy settings
	 */
	protected static function processIp(string $ip): string
	{
		if (Crumble::option('database.hashIp', false)) {
			$salt = Crumble::option('database.hashSalt', '');
			return hash('sha256', $ip . $salt);
		}

		return $ip;
	}

	/**
	 * Detect country code from various sources
	 */
	protected static function detectCountryCode(): ?string
	{
		$kirby = App::instance();

		// 1. check header (for services like CF)
		$customHeader = Crumble::option('geo.header');
		if ($customHeader) {
			$country = $kirby->request()->header($customHeader);
			if ($country) {
				return strtolower($country);
			}
		}

		// 2. try geocoder if configured
		$geocoder = Crumble::option('geo.geocoder');
		if ($geocoder) {
			try {
				$result = $geocoder->geocode($kirby->visitor()->ip());
				if (!$result->isEmpty()) {
					$country = $result->first()->getCountry();
					if ($country) {
						return strtolower($country->getCode());
					}
				}
			} catch (\Exception $e) {
				// geocoding failed, continue to next method
			}
		}

		// 3. use callback if provided
		$callback = Crumble::option('geo.resolver');
		if (is_callable($callback)) {
			$country = $callback($kirby->visitor()->ip());
			if ($country) {
				return strtolower($country);
			}
		}

		// 4. default to null (unknown)
		return null;
	}
}
