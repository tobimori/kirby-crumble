<?php

@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use tobimori\Crumble\Models\CrumblePage;
use Kirby\Data\Json;
use tobimori\Crumble\ConsentManager;
use tobimori\Crumble\Crumble;
use tobimori\Crumble\Migrations\Migrator;

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
		],
		'blueprints' => [
			'crumble/tabs/log' => __DIR__ . '/blueprints/tabs/log.yml',
			'crumble/tabs/texts' => __DIR__ . '/blueprints/tabs/texts.yml',
			'crumble/tabs/categories' => __DIR__ . '/blueprints/tabs/categories.yml',
			'crumble/tabs/style' => __DIR__ . '/blueprints/tabs/style.yml',
			'pages/crumble' => __DIR__ . '/blueprints/page.yml',
		],
		'sections' => [
			'crumble-license' => [],
			'crumble-log' => [],
			'crumble-style-preview' => [
				'props' => []
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
