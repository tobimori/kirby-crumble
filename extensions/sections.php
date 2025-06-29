<?php

use tobimori\Crumble\Log\Log;
use tobimori\Crumble\Crumble;

return [
	'crumble-license' => [],
	'crumble-log' => [
		'computed' => [
			'logs' => function () {
				if (!Crumble::can('access')) {
					return [];
				}

				try {
					$log = Log::instance();
					
					// get query parameters from the request
					$request = kirby()->request();
					$query = $request->query();
					
					$params = [
						'page' => $query->get('page', 1),
						'limit' => $query->get('limit', 50),
						'timeRange' => $query->get('timeRange', '24h'),
						'category' => $query->get('category')
					];
					
					$result = $log->getPaginatedLogs($params);
					
					// Pre-fetch all category names for better performance
					$categoryMap = [];
					$crumblePage = Crumble::page();
					if ($crumblePage) {
						foreach ($crumblePage->children()->filterBy('intendedTemplate', 'crumble-category') as $categoryPage) {
							$categoryMap[$categoryPage->slug()] = $categoryPage->title()->value();
						}
					}
					
					// process logs for display
					$logs = [];
					foreach ($result['logs'] as $log) {
						// Extract only the fields we need for display
						$acceptedCategories = json_decode($log->accepted_categories ?? '[]', true) ?: [];
						
						// Map category slugs to names using pre-fetched data
						$categoryNames = [];
						if (!empty($acceptedCategories)) {
							foreach ($acceptedCategories as $categorySlug) {
								$categoryNames[] = $categoryMap[$categorySlug] ?? $categorySlug;
							}
						}
						
						// Mask IP address for privacy
						$ipAddress = $log->ip_address ?? '';
						if ($ipAddress) {
							if (str_contains($ipAddress, ':')) {
								// IPv6 - show first 2 segments for better privacy
								$parts = explode(':', $ipAddress);
								$ipAddress = $parts[0] . ':' . ($parts[1] ?? '') . '::';
							} elseif (str_contains($ipAddress, '.')) {
								// IPv4
								$parts = explode('.', $ipAddress);
								if (count($parts) === 4) {
									$ipAddress = $parts[0] . '.' . $parts[1] . '.xxx.xxx';
								}
							}
						}
						
						// Only include fields actually displayed in the table
						$logData = [
							'action' => $log->action ?? null,
							'timestamp' => $log->timestamp ?? null,
							'ip_address' => $ipAddress,
							'country_code' => $log->country_code ?? null,
							'accept_type' => $log->accept_type ?? 'necessary',
							'category_names' => $categoryNames
						];
						
						$logs[] = $logData;
					}
					
					return [
						'items' => $logs,
						'pagination' => $result['pagination']
					];
				} catch (\Exception $e) {
					return [
						'items' => [],
						'error' => $e->getMessage()
					];
				}
			},
			'stats' => function () {
				if (!Crumble::can('access')) {
					return [];
				}

				try {
					$log = Log::instance();
					$db = $log->database();
					
					// get query parameters from the request to filter stats
					$request = kirby()->request();
					$query = $request->query();
					
					$params = [
						'timeRange' => $query->get('timeRange', '24h'),
						'category' => $query->get('category')
					];
					
					// helper to build filtered query
					$buildFilteredQuery = function() use ($db, $params) {
						$query = $db->crumble_consent_logs();
						
						// apply time range filter
						if (!empty($params['timeRange']) && $params['timeRange'] !== 'all') {
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
						
						// apply category filter
						if (!empty($params['category'])) {
							$query->where('accepted_categories', 'like', '%"' . $params['category'] . '"%');
						}
						
						return $query;
					};
					
					// get total logs (filtered)
					$total = $buildFilteredQuery()->count();
					
					// get consent vs withdrawn counts (filtered)
					$consents = $buildFilteredQuery()->where('action', '=', 'consent')->count();
					$withdrawals = $buildFilteredQuery()->where('action', '=', 'withdrawn')->count();
					
					// get category statistics (filtered)
					$categoryStats = [];
					$allLogs = $buildFilteredQuery()
						->where('action', '=', 'consent')
						->select(['accepted_categories'])
						->all();
					
					$categoryCount = [];
					foreach ($allLogs as $log) {
						$categories = json_decode($log->accepted_categories ?? '[]', true);
						if (is_array($categories)) {
							foreach ($categories as $category) {
								$categoryCount[$category] = ($categoryCount[$category] ?? 0) + 1;
							}
						}
					}
					
					// calculate acceptance rate
					$acceptanceRate = $total > 0 ? round(($consents / $total) * 100, 1) : 0;
					
					return [
						'total' => $total,
						'consents' => $consents,
						'withdrawals' => $withdrawals,
						'acceptanceRate' => $acceptanceRate,
						'categoryStats' => $categoryCount
					];
				} catch (\Exception $e) {
					return [
						'error' => $e->getMessage()
					];
				}
			},
			'categories' => function () {
				// get all available categories for filtering
				$page = Crumble::page();
				if (!$page) {
					return [];
				}
				
				$categories = [];
				foreach ($page->children()->filterBy('intendedTemplate', 'crumble-category') as $category) {
					$categories[] = [
						'id' => $category->slug(),
						'name' => $category->title()->value()
					];
				}
				
				return $categories;
			}
		]
	],
	'crumble-style-preview' => [
		'computed' => [
			'translations' => function () {
				$locale = kirby()->language()?->code();

				return [
					'consentModal' => [
						'title' => t('crumble.strings.consentModal.title', locale: $locale),
						'description' => t('crumble.strings.consentModal.description', locale: $locale),
						'acceptAllBtn' => t('crumble.strings.consentModal.acceptAllBtn', locale: $locale),
						'acceptNecessaryBtn' => t('crumble.strings.consentModal.acceptNecessaryBtn', locale: $locale),
						'showPreferencesBtn' => t('crumble.strings.consentModal.showPreferencesBtn', locale: $locale)
					],
					'preferencesModal' => [
						'title' => t('crumble.strings.preferencesModal.title', locale: $locale),
						'acceptAllBtn' => t('crumble.strings.preferencesModal.acceptAllBtn', locale: $locale),
						'acceptNecessaryBtn' => t('crumble.strings.preferencesModal.acceptNecessaryBtn', locale: $locale),
						'savePreferencesBtn' => t('crumble.strings.preferencesModal.savePreferencesBtn', locale: $locale),
						'closeIconLabel' => t('crumble.strings.preferencesModal.closeIconLabel', locale: $locale)
					]
				];
			}
		]
	]
];