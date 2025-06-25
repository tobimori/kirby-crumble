<script setup>
import { computed, onMounted, onUnmounted, watch } from "kirbyuse"
import { useContent } from "kirbyuse"
import "vanilla-cookieconsent/dist/cookieconsent.css"
import * as CookieConsent from "vanilla-cookieconsent"

const { currentContent: content } = useContent()

// compute the position based on layout type
const consentPosition = computed(() => {
	if (!content.value) return "bottom left"
	const layout = content.value.consentlayout

	if (layout === "box") return content.value.consentpositionbox
	if (layout === "box inline") return content.value.consentpositionboxinline
	if (layout === "box wide") return content.value.consentpositionboxwide

	// for bar layouts, position is handled differently
	return "bottom left"
})

// compute preferences position
const preferencesPosition = computed(() => {
	if (!content.value) return "left"
	const layout = content.value.preferenceslayout

	if (layout === "bar") return content.value.preferencespositionbar
	if (layout === "bar wide") return content.value.preferencespositionbarwide

	return "left"
})

// build the config object
const config = computed(() => ({
	cookie: {
		name: "cc_cookie_preview",
		expiresAfterDays: 0
	},
	guiOptions: {
		consentModal: {
			layout: content.value?.consentlayout || "box",
			position: consentPosition.value || "bottom left",
			flipButtons: content.value?.flipbuttons || false
		},
		preferencesModal: {
			layout: content.value?.preferenceslayout || "bar",
			position: preferencesPosition.value || "left"
		}
	},
	categories: {
		necessary: {
			readOnly: true
		},
		analytics: {},
		marketing: {}
	},
	language: {
		default: "en",
		translations: {
			en: {
				consentModal: {
					title: "Cookie Consent Preview",
					description:
						"This is a preview of how your cookie consent banner will look.",
					acceptAllBtn: "Accept all",
					acceptNecessaryBtn: "Reject all",
					showPreferencesBtn: "Manage preferences"
				},
				preferencesModal: {
					title: "Cookie Preferences",
					acceptAllBtn: "Accept all",
					acceptNecessaryBtn: "Reject all",
					savePreferencesBtn: "Save preferences",
					closeIconLabel: "Close",
					sections: [
						{
							title: "Cookie Usage",
							description:
								"We use cookies to ensure the basic functionalities of the website and to enhance your online experience."
						},
						{
							title: "Necessary cookies",
							description:
								"These cookies are essential for the proper functioning of the website.",
							linkedCategory: "necessary"
						},
						{
							title: "Analytics cookies",
							description:
								"These cookies help us understand how visitors interact with our website.",
							linkedCategory: "analytics"
						},
						{
							title: "Marketing cookies",
							description:
								"These cookies are used to deliver personalized advertisements.",
							linkedCategory: "marketing"
						}
					]
				}
			}
		}
	}
}))

let ccInstance = null

const initCookieConsent = () => {
	// reset cookies and consent state
	if (CookieConsent.validConsent && CookieConsent.validConsent()) {
		// clear all accepted categories
		CookieConsent.acceptCategory([])
		// erase the cookie
		if (CookieConsent.eraseCookies) {
			CookieConsent.eraseCookies(config.value.cookie.name)
		}
	}

	// reset the plugin
	if (ccInstance || window.CookieConsent) {
		CookieConsent.reset()
	}

	// run with new config and immediately show consent modal
	CookieConsent.run(config.value).then(() => {
		// show consent modal after initialization
		if (CookieConsent.show) {
			CookieConsent.show(true) // pass true to create modal if needed
		}
	})

	ccInstance = true
}

onMounted(() => {
	initCookieConsent()
})

onUnmounted(() => {
	// hide modals before cleanup
	if (CookieConsent.hide) {
		CookieConsent.hide()
	}
	if (CookieConsent.hidePreferences) {
		CookieConsent.hidePreferences()
	}

	// clean up on unmount
	if (CookieConsent.validConsent && CookieConsent.validConsent()) {
		CookieConsent.acceptCategory([])
		if (CookieConsent.eraseCookies) {
			CookieConsent.eraseCookies(config.value.cookie.name)
		}
	}
	if (window.CookieConsent) {
		CookieConsent.reset()
	}
})

// watch for config changes and reinitialize
watch(
	config,
	() => {
		initCookieConsent()
	},
	{ deep: true }
)
</script>

<template>
	<k-section>
		<k-text>
			{{
				$t(
					"crumble.stylePreview.info",
					"The cookie consent banner is displayed on this page. Modify the settings to see changes in real-time."
				)
			}}
		</k-text>
	</k-section>
</template>

<style>
#cc-main {
	z-index: 9999;
}
</style>
