<?php

return [
	'crumble-license' => [],
	'crumble-log' => [],
	'crumble-style-preview' => [
		'computed' => [
			'translations' => function () {
				$locale = kirby()->language()?->code();

				return [
					'consentModal' => [
						'title' => t('crumble.strings.consentModal.title', locale: $locale),
						'description' => t('crumble.strings.consentModal.description', locale: $locale),
						'acceptAllBtn' => t('crumble.strings.consentModal.acceptAllBtn', locale: $locale),
						'acceptNecessaryBtn' => t('crumble.strings.consentModal.acceptNecessaryBtn', locale: $locale),
						'showPreferencesBtn' => t('crumble.strings.consentModal.showPreferencesBtn', locale: $locale)
					],
					'preferencesModal' => [
						'title' => t('crumble.strings.preferencesModal.title', locale: $locale),
						'acceptAllBtn' => t('crumble.strings.preferencesModal.acceptAllBtn', locale: $locale),
						'acceptNecessaryBtn' => t('crumble.strings.preferencesModal.acceptNecessaryBtn', locale: $locale),
						'savePreferencesBtn' => t('crumble.strings.preferencesModal.savePreferencesBtn', locale: $locale),
						'closeIconLabel' => t('crumble.strings.preferencesModal.closeIconLabel', locale: $locale)
					]
				];
			}
		]
	]
];