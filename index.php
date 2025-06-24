<?php

@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;

if (
	version_compare(App::version() ?? '0.0.0', '5.0.0', '<') === true ||
	version_compare(App::version() ?? '0.0.0', '6.0.0', '>') === true
) {
	throw new Exception('Kirby Crumble requires Kirby 5');
}

App::plugin(
	'tobimori/crumble',
	extends: []
);
