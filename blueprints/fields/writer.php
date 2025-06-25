<?php

use tobimori\Crumble\Crumble;

return function () {
	return [
		'type' => 'writer',
		'marks' => Crumble::option('marks'),
		'inline' => true,
		'toolbar' => [
			'inline' => false
		]
	];
};
