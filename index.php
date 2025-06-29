<?php

@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use tobimori\Crumble\Models\CrumblePage;
use Kirby\Data\Json;
use tobimori\Crumble\Crumble;
use tobimori\Crumble\Migrations\Migrator;
use tobimori\Crumble\Models\CrumbleCategoryPage;

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
		'sections' => require __DIR__ . '/extensions/sections.php',
		'translations' => [
			'en' => Json::read(__DIR__ . '/translations/en.json'),
			'de' => Json::read(__DIR__ . '/translations/de.json'),
		],
		'routes' => require __DIR__ . '/extensions/routes.php',
		'hooks' => [
			'system.loadPlugins:after' => function () {
				// force page to exist
				Crumble::install();

				// run migrations
				Migrator::migrate();
			}
		],
		'options' => require __DIR__ . '/extensions/options.php'
	]
);
