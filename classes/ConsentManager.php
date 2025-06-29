<?php

namespace tobimori\Crumble;

use Kirby\Cms\App;
use tobimori\Crumble\Log\Log;

class ConsentManager
{
	private static ?array $validationCache = null;
	private static ?array $categoriesCache = null;

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
		if (!$data || !is_array($data)) {
			throw new \Exception('Invalid cookie data');
		}

		if (!isset($data['consentId']) || !is_string($data['consentId'])) {
			throw new \Exception('Invalid consent ID in cookie');
		}

		if (isset($data['categories']) && !is_array($data['categories'])) {
			$data['categories'] = [];
		}
		if (isset($data['services']) && !is_array($data['services'])) {
			$data['services'] = [];
		}

		$kirby = App::instance();

		$acceptType = 'necessary';
		$acceptedCategories = $data['categories'] ?? [];

		if (!empty($acceptedCategories)) {
			$allCategories = static::getOptionalCategories();

			// compare arrays in an order-independent way
			$isAll = empty(array_diff($allCategories, $acceptedCategories)) && empty(array_diff($acceptedCategories, $allCategories));
			$acceptType = $isAll ? 'all' : 'custom';
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
	 * Withdraw consent
	 */
	public static function withdraw(string|null $consentId = null): bool
	{
		if (!$consentId) {
			$cookieData = static::getCookieData();
			$consentId = $cookieData['consentId'] ?? null;
		}

		if (!$consentId) {
			return false;
		}

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

		return true;
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
	 * Validate consent status
	 */
	public static function validate(string|null $consentId = null): array
	{
		if (static::$validationCache !== null && $consentId === null) {
			return static::$validationCache;
		}

		$cookieData = static::getCookieData();

		if (!$consentId) {
			$consentId = $cookieData['consentId'] ?? null;
		}

		if (!$consentId) {
			$result = ['valid' => false, 'reason' => 'no_consent'];
			static::$validationCache = $result;
			return $result;
		}

		if ($cookieData && isset($cookieData['expirationTime'])) {
			// js timestamp is in milliseconds
			if ($cookieData['expirationTime'] < time() * 1000) {
				$result = ['valid' => false, 'reason' => 'expired'];
				static::$validationCache = $result;
				return $result;
			}
		}

		$log = Log::instance();
		$consent = $log->findLatestByConsentId($consentId);

		if ($consent && $consent['action'] === 'withdrawn') {
			$result = ['valid' => false, 'reason' => 'withdrawn'];
			static::$validationCache = $result;
			return $result;
		}

		if ($cookieData) {
			$result = [
				'valid' => true,
				'consent' => [
					'acceptedCategories' => $cookieData['categories'] ?? [],
					'acceptedServices' => $cookieData['services'] ?? [],
					'timestamp' => $cookieData['consentTimestamp'] ?? null,
					'expiresAt' => isset($cookieData['expirationTime']) ? date('Y-m-d H:i:s', $cookieData['expirationTime'] / 1000) : null
				]
			];
			static::$validationCache = $result;
			return $result;
		}

		if (!$consent) {
			$result = ['valid' => false, 'reason' => 'not_found'];
			static::$validationCache = $result;
			return $result;
		}

		$result = [
			'valid' => true,
			'consent' => [
				'acceptType' => $consent['accept_type'],
				'acceptedCategories' => json_decode($consent['accepted_categories'], true),
				'acceptedServices' => json_decode($consent['accepted_services'], true),
				'timestamp' => $consent['timestamp'],
				'expiresAt' => $consent['expires_at']
			]
		];
		static::$validationCache = $result;
		return $result;
	}

	/**
	 * Get consent status
	 */
	public static function getStatus(string|null $consentId = null): ?array
	{
		$cookieData = static::getCookieData();

		if (!$consentId) {
			$consentId = $cookieData['consentId'] ?? null;
		}

		if (!$consentId) {
			return null;
		}

		$log = Log::instance();
		$dbRecord = $log->findLatestByConsentId($consentId);

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
	 * Check if consent was given for a category and optionally a specific service
	 */
	public static function hasConsent(string $category, ?string $service = null): bool
	{
		if (!static::hasConsentForCategory($category)) {
			return false;
		}

		if ($service !== null) {
			return static::hasConsentForService($service);
		}

		return true;
	}

	/**
	 * Check if consent was given for a specific category
	 */
	public static function hasConsentForCategory(string $category): bool
	{
		$cookieData = static::getCookieData();
		if (!$cookieData) {
			return false;
		}

		$validation = static::validate();
		if (!$validation['valid']) {
			return false;
		}

		$acceptedCategories = $cookieData['categories'] ?? [];
		return in_array($category, $acceptedCategories);
	}

	/**
	 * Check if consent was given for a specific service
	 */
	public static function hasConsentForService(string $service): bool
	{
		$cookieData = static::getCookieData();
		if (!$cookieData) {
			return false;
		}

		$validation = static::validate();
		if (!$validation['valid']) {
			return false;
		}

		$acceptedServices = $cookieData['services'] ?? [];
		return in_array($service, $acceptedServices);
	}

	/**
	 * Check if any consent beyond necessary was given
	 */
	public static function hasAnyConsent(): bool
	{
		$cookieData = static::getCookieData();
		if (!$cookieData) {
			return false;
		}

		$validation = static::validate();
		if (!$validation['valid']) {
			return false;
		}

		$acceptedCategories = $cookieData['categories'] ?? [];
		return !empty($acceptedCategories);
	}

	/**
	 * Check if all optional categories were accepted
	 */
	public static function hasFullConsent(): bool
	{
		$cookieData = static::getCookieData();
		if (!$cookieData) {
			return false;
		}

		$validation = static::validate();
		if (!$validation['valid']) {
			return false;
		}

		$optionalCategories = static::getOptionalCategories();
		$acceptedCategories = $cookieData['categories'] ?? [];

		foreach ($optionalCategories as $category) {
			if (!in_array($category, $acceptedCategories)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Tells the CMS responder that the response relies on a cookie and
	 * its value (even if the cookie isn't set in the current request);
	 * this ensures that the response is only cached for visitors who don't
	 * have this cookie set;
	 * https://github.com/getkirby/kirby/issues/4423#issuecomment-1166300526
	 */
	protected static function trackUsage(): void
	{
		$kirby = App::instance(null, true);
		$kirby?->response()->usesCookie('cc_cookie');
	}

	/**
	 * Get all optional categories (cached)
	 */
	protected static function getOptionalCategories(): array
	{
		if (static::$categoriesCache !== null) {
			return static::$categoriesCache;
		}

		$crumblePage = Crumble::page();
		if (!$crumblePage) {
			static::$categoriesCache = [];
			return [];
		}

		$categories = $crumblePage->children()
			->filterBy('intendedTemplate', 'crumble-category')
			->filterBy('mandatory', false)
			->pluck('slug');

		static::$categoriesCache = $categories;
		return $categories;
	}

	/**
	 * Get consent data from cc_cookie
	 */
	protected static function getCookieData(): ?array
	{
		static::trackUsage();

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

		$customHeader = Crumble::option('geo.header');
		if ($customHeader) {
			$country = $kirby->request()->header($customHeader);
			if ($country) {
				return strtolower($country);
			}
		}

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
			}
		}

		$callback = Crumble::option('geo.resolver');
		if (is_callable($callback)) {
			$country = $callback($kirby->visitor()->ip());
			if ($country) {
				return strtolower($country);
			}
		}

		return null;
	}
}
