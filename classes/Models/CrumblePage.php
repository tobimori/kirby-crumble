<?php

namespace tobimori\Crumble\Models;

use Kirby\Cms\Page;
use Kirby\Content\Field;

class CrumblePage extends Page
{
	/**
	 * Disable sitemap for page
	 * Integration into tobimori/kirby-seo
	 */
	public function metaDefaults()
	{
		return ['robotsIndex' => false];
	}

	/**
	 * Override the page title to be static to the template name
	 */
	public function title(): Field
	{
		return new Field($this, 'title', t("crumble.consent"));
	}
}
