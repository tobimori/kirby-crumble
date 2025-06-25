<?php

namespace tobimori\Crumble;

use Kirby\Cms\App;
use Kirby\Http\Cookie;
use Kirby\Toolkit\Str;
use tobimori\Crumble\Log\Log;

class ConsentManager
{
	/**
	 * Record consent from frontend
	 */
	public static function record(array $data): array
	{
		// get or create consent id
		$consentId = static::getConsentIdFromCookie() ?? Str::uuid();

		// prepare data for logging
		$kirby = App::instance();
		$logData = [
			'consent_id' => $consentId,
			'action' => $data['action'] ?? 'given',
			'ip_address' => static::processIp($kirby->visitor()->ip()),
			'user_agent' => $kirby->request()->header('User-Agent'),
			'consent_version' => Crumble::option('consent.version', '1.0'),
			'accept_type' => $data['acceptType'] ?? 'custom',
			'accepted_categories' => json_encode($data['categories']['accepted'] ?? []),
			'rejected_categories' => json_encode($data['categories']['rejected'] ?? []),
			'accepted_services' => json_encode($data['services']['accepted'] ?? []),
			'rejected_services' => json_encode($data['services']['rejected'] ?? []),
			'language' => $kirby->language()?->code() ?? 'en', // TODO: do we need to have an option or single lang here?
			'country_code' => $data['country_code'] ?? static::detectCountryCode(),
			'page_url' => $data['page_url'] ?? $kirby->request()->url()->toString(),
			'timestamp' => date('Y-m-d H:i:s'),
			'expires_at' => date('Y-m-d H:i:s', strtotime('+' . Crumble::option('consent.expiresAfter', 365) . ' days'))
		];

		// store in database
		$log = Log::instance();
		$log->insert($logData);

		// set cookies
		static::setCookies($consentId, [
			'accepted' => $data['categories']['accepted'] ?? [],
			'rejected' => $data['categories']['rejected'] ?? []
		]);

		return [
			'success' => true,
			'consentId' => $consentId
		];
	}

	/**
	 * Validate consent status
	 */
	public static function validate(string|null $consentId = null): array
	{
		$consentId = $consentId ?? static::getConsentIdFromCookie();

		if (!$consentId) {
			return ['valid' => false, 'reason' => 'no_consent'];
		}

		$log = Log::instance();
		$consent = $log->findLatestByConsentId($consentId);

		if (!$consent) {
			return ['valid' => false, 'reason' => 'not_found'];
		}

		// check if expired
		if (strtotime($consent['expires_at']) < time()) {
			return ['valid' => false, 'reason' => 'expired'];
		}

		// check if withdrawn
		if ($consent['action'] === 'withdrawn') {
			return ['valid' => false, 'reason' => 'withdrawn'];
		}

		return [
			'valid' => true,
			'consent' => [
				'acceptType' => $consent['accept_type'],
				'acceptedCategories' => json_decode($consent['accepted_categories'], true),
				'rejectedCategories' => json_decode($consent['rejected_categories'], true),
				'acceptedServices' => json_decode($consent['accepted_services'], true),
				'rejectedServices' => json_decode($consent['rejected_services'], true),
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
		$consentId = $consentId ?? static::getConsentIdFromCookie();

		if (!$consentId) {
			return false;
		}

		// record withdrawal
		$log = Log::instance();
		$log->insert([
			'consent_id' => $consentId,
			'action' => 'withdrawn',
			'ip_address' => static::processIp(kirby()->visitor()->ip()),
			'user_agent' => kirby()->request()->header('User-Agent'),
			'consent_version' => Crumble::option('consent.version', '1.0'),
			'accept_type' => 'none',
			'accepted_categories' => json_encode([]),
			'rejected_categories' => json_encode([]),
			'accepted_services' => json_encode([]),
			'rejected_services' => json_encode([]),
			'language' => kirby()->language()?->code() ?? 'en',
			'page_url' => kirby()->request()->url()->toString(),
			'timestamp' => date('Y-m-d H:i:s'),
			'expires_at' => date('Y-m-d H:i:s')
		]);

		// clear cookies
		static::clearCookies();

		return true;
	}

	/**
	 * Get consent status
	 */
	public static function getStatus(string|null $consentId = null): ?array
	{
		$consentId = $consentId ?? static::getConsentIdFromCookie();

		if (!$consentId) {
			return null;
		}

		$log = Log::instance();
		return $log->findLatestByConsentId($consentId);
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
	 * Set consent cookies
	 */
	protected static function setCookies(string $consentId, array $categories): void
	{
		// secure consent id cookie (httponly)
		Cookie::set('crumble_consent_id', $consentId, [
			'lifetime' => Crumble::option('consent.expiresAfter', 365) * 24 * 60,
			'httpOnly' => true,
			'secure' => kirby()->request()->ssl(),
			'sameSite' => 'Lax'
		]);

		// categories cookie (readable by js)
		Cookie::set('crumble_consent_status', json_encode($categories), [
			'lifetime' => Crumble::option('consent.expiresAfter', 365) * 24 * 60,
			'secure' => kirby()->request()->ssl(),
			'sameSite' => 'Lax'
		]);
	}

	/**
	 * Clear consent cookies
	 */
	protected static function clearCookies(): void
	{
		Cookie::remove('crumble_consent_id');
		Cookie::remove('crumble_consent_status');
	}

	/**
	 * Get consent id from cookie
	 */
	protected static function getConsentIdFromCookie(): ?string
	{
		return Cookie::get('crumble_consent_id');
	}

	/**
	 * Process IP address based on privacy settings
	 */
	protected static function processIp(string $ip): string
	{
		if (Crumble::option('privacy.hashIp', true)) {
			$salt = Crumble::option('privacy.salt');
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
