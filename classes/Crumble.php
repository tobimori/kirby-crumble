<?php

namespace tobimori\Crumble;

use Kirby\Cms\App;
use Kirby\Toolkit\Str;

final class Crumble
{
	/**
	 * Returns a plugin option
	 */
	public static function option(string $key, mixed $default = null): mixed
	{
		$option = App::instance()->option("tobimori.crumble.{$key}", $default);
		if (is_callable($option)) {
			$option = $option();
		}

		return $option;
	}

	/**
	 * Create the crumble page if it doesn't exist yet
	 */
	public static function install(): void
	{
		$kirby = App::instance();
		$page = static::option('page');
		if ($kirby->page($page)?->exists()) {
			return;
		}

		$isUuid = Str::startsWith($page, "page://");

		// create the page
		$kirby->impersonate(
			'kirby',
			fn() => $kirby->site()->createChild([
				'slug' => $isUuid ? "crumble" : $page,
				'template' => 'crumble',
				'content' => [
					'uuid' => $isUuid ? Str::after($page, "page://") : 'crumble',
				]
			])->changeStatus('unlisted')
		);
	}
}
