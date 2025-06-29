<?php

use Kirby\Http\Response;
use tobimori\Crumble\ConsentManager;

return [
	[
		'pattern' => 'crumble/consent',
		'method' => 'POST',
		'action' => function () {
			try {
				$cookie = ConsentManager::record();
				
				$expires = time() + (365 * 24 * 60 * 60);
				setcookie(
					'cc_cookie',
					$cookie,
					[
						'expires' => $expires,
						'path' => '/',
						'domain' => parse_url(kirby()->url(), PHP_URL_HOST),
						'secure' => kirby()->request()->ssl(),
						'samesite' => 'Lax'
					]
				);
				
				return 'ok';
			} catch (\Exception $e) {
				return Response::json(['error' => $e->getMessage()], 400);
			}
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
];