<?php

@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use tobimori\Crumble\Models\CrumblePage;
use Kirby\Data\Json;
use tobimori\Crumble\ConsentManager;
use tobimori\Crumble\Crumble;
use tobimori\Crumble\Migrations\Migrator;
use tobimori\Crumble\Models\CrumbleCategoryPage;
use Kirby\Http\Response;

if (
	version_compare(App::version() ?? '0.0.0', '5.0.0', '<') === true ||
	version_compare(App::version() ?? '0.0.0', '6.0.0', '>') === true
) {
	throw new Exception('Kirby Crumble requires Kirby 5');
}

App::plugin(
	'tobimori/crumble',
	extends: [
		'pageModels' => [
			'crumble' => CrumblePage::class,
			'crumble-category' => CrumbleCategoryPage::class,
		],
		'permissions' => [
			'access' => true,
			'update' => true,
			'delete' => true
		],
		'blueprints' => [
			'crumble/tabs/log' => __DIR__ . '/blueprints/tabs/log.yml',
			'crumble/tabs/texts' => __DIR__ . '/blueprints/tabs/texts.yml',
			'crumble/tabs/categories' => __DIR__ . '/blueprints/tabs/categories.yml',
			'crumble/tabs/style' => __DIR__ . '/blueprints/tabs/style.yml',
			'crumble/fields/writer' => require_once __DIR__ . '/blueprints/fields/writer.php',
			'crumble/fields/services' => __DIR__ . '/blueprints/fields/services.yml',
			'crumble/fields/cookies' => __DIR__ . '/blueprints/fields/cookies.yml',
			'pages/crumble' => __DIR__ . '/blueprints/page.yml',
			'pages/crumble-category' => __DIR__ . '/blueprints/category.yml',
		],
		'snippets' => [
			'crumble/script' => __DIR__ . '/snippets/script.php',
			'crumble/consent.js' => __DIR__ . '/snippets/consent.js.php',
		],
		'sections' => [
			'crumble-license' => [],
			'crumble-log' => [],
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
		],
		'translations' => [
			'en' => Json::read(__DIR__ . '/translations/en.json'),
			'de' => Json::read(__DIR__ . '/translations/de.json'),
		],
		'routes' => [
			[
				'pattern' => 'crumble/consent',
				'method' => 'POST',
				'action' => function () {
					return ConsentManager::record(kirby()->request()->data());
				}
			],
			[
				'pattern' => 'crumble/consent/validate',
				'method' => 'POST',
				'action' => function () {
					return ConsentManager::validate(
						kirby()->request()->data('consentId')
					);
				}
			],
			[
				'pattern' => 'crumble/consent/(:any)',
				'method' => 'GET',
				'action' => function ($consentId) {
					return ConsentManager::getStatus($consentId);
				}
			],
			[
				'pattern' => 'crumble/consent/(:any)',
				'method' => 'DELETE',
				'action' => function ($consentId) {
					return [
						'success' => ConsentManager::withdraw($consentId)
					];
				}
			],
			[
				'pattern' => 'crumble/consent/(:any)/export',
				'method' => 'GET',
				'action' => function ($consentId) {
					return ConsentManager::export($consentId);
				}
			],
			[
				'pattern' => 'crumble/config.json',
				'method' => 'GET',
				'action' => function () {
					$page = site()->find(option('tobimori.crumble.page'));
					if (!$page) {
						return Response::json(['error' => 'Configuration not found'], 404);
					}

					return Response::json($page->config());
				}
			],
			[
				'pattern' => 'crumble/consent.js',
				'method' => 'GET',
				'action' => function () {
					$page = site()->find(option('tobimori.crumble.page'));
					if (!$page) {
						return new Response('// Configuration not found', 'application/javascript', 404);
					}

					// Redirect to versioned URL
					return go('crumble/consent.' . $page->revision() . '.js');
				}
			],
			[
				'pattern' => 'crumble/consent.(:num).js',
				'method' => 'GET',
				'action' => function ($revision) {
					$page = site()->find(option('tobimori.crumble.page'));
					if (!$page) {
						return new Response('// Configuration not found', 'application/javascript', 404);
					}

					$content = snippet('crumble/consent.js', ['page' => $page, 'plugin' => kirby()->plugin('tobimori/crumble')], true);
					return new Response($content, 'application/javascript');
				}
			]
		],
		'hooks' => [
			'system.loadPlugins:after' => function () {
				// force page to exist
				Crumble::install();

				// run migrations
				Migrator::migrate();
			}
		],
		'options' => [
			'gtm' => null, // if specified, will this will force use GTM specific categories + services
			'categories' => null, // allows 'forcing' specific configuration in backend
			'marks' => ['bold', 'link'],

			// database configuration
			'database' => [
				'type' => 'sqlite',
				'sqlite' => [
					'path' => null // defaults to kirby()->root('logs') . '/crumble/consent.sqlite'
				],
				'mysql' => [
					'host' => 'localhost', // TODO: use global kirby DB config
					'port' => 3306,
					'database' => 'kirby',
					'user' => 'root',
					'password' => ''
				]
			],
			'page' => 'page://crumble',
			'ipHash' => null, // if specified, this will has IP addresses
			'expiresAfter' => 365, // days

			// geolocation settings
			'geo' => [
				'header' => null, // custom header name (e.g. 'X-Vercel-IP-Country')
				'resolver' => null, // callback function to resolve IP to country
				'geocoder' => null // callable that returns a Geocoder instance
			]
		]
	]
);
