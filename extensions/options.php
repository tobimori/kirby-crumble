<?php

return [
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
];