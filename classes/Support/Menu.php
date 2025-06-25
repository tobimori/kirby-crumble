<?php

namespace tobimori\Crumble\Support;

use Kirby\Cms\App;
use tobimori\Crumble\Crumble;

/**
 * Helper class for customizing the panel menu
 */
final class Menu
{
	private function __construct()
	{
		throw new \Error('This class cannot be instantiated');
	}

	/**
	 * Returns the current path
	 */
	private static function path()
	{
		return App::instance()->request()->path()->toString();
	}

	/**
	 * Returns the path to the forms page
	 */
	public static function consentPath()
	{
		$crumblePage = App::instance()->site()->findPageOrDraft(Crumble::option('page'));
		return $crumblePage?->panel()->path() ?? "/pages/__consent";
	}

	/**
	 * Returns the menu item for the forms page
	 */
	public static function forms()
	{
		if (App::instance()->user()?->role()->permissions()->for('tobimori.crumble', 'accessConsent') === false) {
			return null;
		}

		return [
			'label' => t('crumble.consent'),
			'link' => static::consentPath(),
			'icon' => 'cookie',
			'current' => fn() =>
			str_contains(static::path(), static::consentPath())
		];
	}

	/**
	 * Returns the menu item for the crumble page
	 */
	public static function site()
	{
		return [
			'current' => fn(string|null $id) => $id === 'site' && !str_contains(static::path(), static::consentPath())
		];
	}
}
