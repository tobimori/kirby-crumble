<?php

namespace tobimori\Crumble\Models;

use Kirby\Cms\Page;
use Kirby\Content\VersionId;

class CrumbleCategoryPage extends Page
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
	 * Build configuration for this category
	 */
	public function buildConfig(): array
	{
		$config = [
			'enabled' => $this->mandatory()->toBool(),
			'readOnly' => $this->mandatory()->toBool()
		];

		// add services if any exist
		$services = [];
		foreach ($this->services()->toStructure() as $service) {
			$serviceId = (string)$service->id();
			$services[$serviceId] = ['label' => (string)$service->name()];
		}
		
		if (!empty($services)) {
			$config['services'] = $services;
		}

		// add autoClear if cookies need to be cleared
		$autoClearCookies = [];
		foreach ($this->cookies()->toStructure() as $cookie) {
			if (!$cookie->autoDelete()->toBool()) {
				continue;
			}
			
			$cookieName = (string)$cookie->name();
			// only treat as regex if explicitly wrapped in /.../ 
			$autoClearCookies[] = ['name' => $cookieName];
		}
		
		if (!empty($autoClearCookies)) {
			$config['autoClear'] = ['cookies' => $autoClearCookies];
		}

		return $config;
	}

	/**
	 * Build translation section for preferences modal
	 */
	public function buildSection(string $locale): array
	{
		$section = [
			'title' => $this->title()->toString(),
			'description' => $this->description()->toString(),
			'linkedCategory' => $this->slug()
		];

		// add cookie table if cookies exist
		$cookiesStructure = $this->cookies()->toStructure();
		if (!$cookiesStructure->isEmpty()) {
			// get field definitions from blueprint
			$blueprint = $this->blueprint()->field('cookies');
			$fields = $blueprint['fields'] ?? [];
			
			$headers = [];
			$rows = [];

			foreach ($cookiesStructure as $cookie) {
				$row = [];
				
				foreach ($fields as $fieldName => $fieldConfig) {
					// skip autoDelete field in table
					if ($fieldName === 'autoDelete') {
						continue;
					}
					
					// build header on first pass
					if (empty($headers)) {
						$label = $fieldConfig['label'] ?? $fieldName;
						$headers[$fieldName] = t($label, $label, locale: $locale);
					}
					
					// get field value
					$value = $cookie->$fieldName();
					$row[$fieldName] = is_object($value) && method_exists($value, 'toString') 
						? $value->toString() 
						: (string)$value;
				}
				
				if (!empty($row)) {
					$rows[] = $row;
				}
			}

			if (!empty($rows)) {
				$section['cookieTable'] = ['headers' => $headers, 'body' => $rows];
			}
		}

		return $section;
	}
}
