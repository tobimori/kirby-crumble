<?php

namespace tobimori\Crumble;

use Kirby\Cms\App;

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
}
