<?php

use tobimori\Crumble\ConsentManager;

if (!function_exists('consent')) {
	/**
	 * Check if consent was given
	 *
	 * @param string $category The category slug to check, or 'any'/'all' for special checks
	 * @param string|null $service Optional service slug to check within the category
	 * @return bool
	 */
	function consent(string $category, ?string $service = null): bool
	{
		if ($category === 'any') {
			return ConsentManager::hasAnyConsent();
		}

		if ($category === 'all') {
			return ConsentManager::hasFullConsent();
		}

		return ConsentManager::hasConsent($category, $service);
	}
}
