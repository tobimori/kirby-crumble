<?php

namespace tobimori\Crumble\Models;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Content\Field;
use Kirby\Content\VersionId;
use tobimori\Crumble\Crumble;

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

	/**
	 * Render a 404 page to lock pages
	 */
	public function render(
		array $data = [],
		$contentType = 'html',
		VersionId|string|null $versionId = null
	): string {
		kirby()->response()->code(404);
		return $this->site()->errorPage()->render();
	}

	/**
	 * Basic permissions for all pages
	 */
	public function isAccessible(): bool
	{
		if (!App::instance()->user()->role()->permissions()->for('tobimori.crumble', 'access')) {
			return false;
		}

		return parent::isAccessible();
	}

	/**
	 * Calculate revision based on the modified date of this page and all category subpages
	 */
	public function revision(): int
	{
		$timestamps = [$this->modified() ?: time()];
		foreach ($this->children()->filterBy('intendedTemplate', 'crumble-category') as $category) {
			$timestamps[] = $category->modified() ?: time();
		}

		// max -> most recent timestamp
		return max($timestamps);
	}

	/**
	 * Get the complete configuration for the cookie consent
	 */
	public function config(): array
	{
		$kirby = App::instance();
		$locale = $kirby->language()?->code() ?? 'en';

		// get all category pages once and build data
		$categoryPages = $this->children()->filterBy('intendedTemplate', 'crumble-category');
		$categories = [];
		$sections = [];

		foreach ($categoryPages as $category) {
			$categories[$category->slug()] = $category->buildConfig();
			$sections[] = $category->buildSection($locale);
		}

		// get layout settings
		$consentLayout = $this->consentLayout()->value() ?? 'box';
		$preferencesLayout = $this->preferencesLayout()->value() ?? 'bar';

		// build gui options
		$guiOptions = [
			'consentModal' => [
				'layout' => $consentLayout,
				'position' => match ($consentLayout) {
					'box' => $this->consentBoxPosition()->value() ?? 'bottom center',
					'cloud' => $this->consentCloudPosition()->value() ?? 'bottom center',
					'bar' => $this->consentBarPosition()->value() ?? 'bottom',
					default => 'bottom center'
				},
				'flipButtons' => $this->flipButtons()->toBool(),
				'equalWeightButtons' => true
			],
			'preferencesModal' => [
				'layout' => $preferencesLayout,
				'position' => $preferencesLayout === 'bar' ? ($this->preferencesPosition()->value() ?? 'left') : null,
				'flipButtons' => $this->flipButtons()->toBool(),
				'equalWeightButtons' => false
			]
		];

		// add optional gui settings
		if ($consentLayout === 'box' && ($variant = $this->consentBoxVariant()->value()) !== 'default') {
			$guiOptions['consentModal']['variant'] = $variant;
		}

		if ($preferencesLayout === 'bar' && ($size = $this->preferencesSize()->value()) !== 'default') {
			$guiOptions['preferencesModal']['size'] = $size;
		}

		if ($this->disablePageInteraction()->toBool()) {
			$guiOptions['disablePageInteraction'] = true;
		}

		// helper for translation field with fallback
		$t = fn($field, $key) => $this->$field()->or(t($key, locale: $locale))->toString();

		return [
			'revision' => $this->revision(),
			'categories' => $categories,
			'language' => [
				'default' => $locale,
				'autoDetect' => true,
				'translations' => [
					$locale => [
						'consentModal' => [
							'title' => $t('consentTitle', 'crumble.strings.consentModal.title'),
							'description' => $t('consentDescription', 'crumble.strings.consentModal.description'),
							'acceptAllBtn' => $t('consentAcceptAllBtn', 'crumble.strings.consentModal.acceptAllBtn'),
							'acceptNecessaryBtn' => $t('consentAcceptNecessaryBtn', 'crumble.strings.consentModal.acceptNecessaryBtn'),
							'showPreferencesBtn' => $t('consentShowPreferencesBtn', 'crumble.strings.consentModal.showPreferencesBtn')
						],
						'preferencesModal' => [
							'title' => $t('preferencesTitle', 'crumble.strings.preferencesModal.title'),
							'acceptAllBtn' => $t('preferencesAcceptAllBtn', 'crumble.strings.preferencesModal.acceptAllBtn'),
							'acceptNecessaryBtn' => $t('preferencesAcceptNecessaryBtn', 'crumble.strings.preferencesModal.acceptNecessaryBtn'),
							'savePreferencesBtn' => $t('preferencesSavePreferencesBtn', 'crumble.strings.preferencesModal.savePreferencesBtn'),
							'closeIconLabel' => t('crumble.strings.preferencesModal.closeIconLabel', locale: $locale),
							'sections' => $sections
						]
					]
				]
			],
			'guiOptions' => $guiOptions,
			'cookie' => [
				'name' => 'cc_cookie',
				'domain' => parse_url($kirby->url(), PHP_URL_HOST),
				'path' => '/',
				'sameSite' => 'Lax',
				'expiresAfterDays' => 0 // session cookie until server extends it
			]
		];
	}
}
