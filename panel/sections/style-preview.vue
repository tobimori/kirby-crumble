<script setup>
import { computed, onMounted, onUnmounted, watch, ref } from "kirbyuse"
import { useContent, useSection, usePanel } from "kirbyuse"
import { section } from "kirbyuse/props"
import "vanilla-cookieconsent/dist/cookieconsent.css"
import * as CookieConsent from "vanilla-cookieconsent"

const props = defineProps({
	...section
})

const { currentContent: content } = useContent()
const { load } = useSection()
const panel = usePanel()
const sectionData = ref({})

const loadSectionData = async () => {
	const response = await load({
		parent: props.parent,
		name: props.name
	})
	sectionData.value = response
}

loadSectionData()

watch(
	() => panel.language.code,
	async () => {
		await loadSectionData()
		if (ccInstance) {
			if (CookieConsent.hide) CookieConsent.hide()
			initCookieConsent()
		}
	}
)

const consentModal = computed(() => {
	if (!content.value) return { layout: "box", position: "bottom left" }

	const layout = content.value.consentlayout || "box"
	const variant = content.value.consentboxvariant || "default"

	let fullLayout = layout
	if (layout === "box" && variant !== "default") {
		fullLayout = `${layout} ${variant}`
	} else if (layout === "cloud" && content.value.consentcloudbuttons) {
		fullLayout = `${layout} inline`
	} else if (layout === "bar" && content.value.consentbarbuttons) {
		fullLayout = `${layout} inline`
	}

	let position = "bottom left"
	if (layout === "box")
		position = content.value.consentboxposition || "bottom left"
	else if (layout === "cloud")
		position = content.value.consentcloudposition || "bottom left"
	else if (layout === "bar")
		position = content.value.consentbarposition || "bottom"

	return { layout: fullLayout, position }
})

const preferencesModal = computed(() => {
	if (!content.value) return { layout: "bar", position: "left" }

	const layout = content.value.preferenceslayout || "bar"
	const size = content.value.preferencessize || "default"

	const fullLayout =
		layout === "bar" && size !== "default" ? `${layout} ${size}` : layout

	const position = content.value.preferencesposition || "left"

	return { layout: fullLayout, position }
})

const config = computed(() => ({
	cookie: {
		name: "cc_cookie_preview",
		expiresAfterDays: 0
	},
	guiOptions: {
		consentModal: {
			layout: consentModal.value.layout,
			position: consentModal.value.position,
			flipButtons: content.value?.flipbuttons || false,
			disablePageInteraction: false
		},
		preferencesModal: {
			layout: preferencesModal.value.layout,
			position: preferencesModal.value.position
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
		default: "x-default",
		translations: {
			"x-default": {
				consentModal: {
					title:
						content.value?.consenttitle ||
						sectionData.value?.translations?.consentModal?.title,
					description:
						content.value?.consentdescription ||
						sectionData.value?.translations?.consentModal?.description,
					acceptAllBtn:
						content.value?.consentacceptallbtn ||
						sectionData.value?.translations?.consentModal?.acceptAllBtn,
					acceptNecessaryBtn:
						content.value?.consentacceptnecessarybtn ||
						sectionData.value?.translations?.consentModal?.acceptNecessaryBtn,
					showPreferencesBtn:
						content.value?.consentshowpreferencesbtn ||
						sectionData.value?.translations?.consentModal?.showPreferencesBtn
				},
				preferencesModal: {
					title:
						content.value?.preferencestitle ||
						sectionData.value?.translations?.preferencesModal?.title,
					acceptAllBtn:
						content.value?.preferencesacceptallbtn ||
						sectionData.value?.translations?.preferencesModal?.acceptAllBtn,
					acceptNecessaryBtn:
						content.value?.preferencesacceptnecessarybtn ||
						sectionData.value?.translations?.preferencesModal
							?.acceptNecessaryBtn,
					savePreferencesBtn:
						content.value?.preferencessavepreferencesbtn ||
						sectionData.value?.translations?.preferencesModal
							?.savePreferencesBtn,
					closeIconLabel:
						sectionData.value?.translations?.preferencesModal?.closeIconLabel,
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
let isInitialized = false

const initCookieConsent = (showPreferences = false) => {
	if (CookieConsent.hide) CookieConsent.hide()
	if (CookieConsent.hidePreferences) CookieConsent.hidePreferences()

	if (CookieConsent.validConsent && CookieConsent.validConsent()) {
		CookieConsent.acceptCategory([])
		if (CookieConsent.eraseCookies) {
			CookieConsent.eraseCookies(config.value.cookie.name)
		}
	}

	if (ccInstance || window.CookieConsent) {
		CookieConsent.reset()
	}

	CookieConsent.run(config.value).then(() => {
		if (showPreferences) {
			if (CookieConsent.hide) {
				CookieConsent.hide()
			}
			if (CookieConsent.showPreferences) {
				CookieConsent.showPreferences()
			}
		} else {
			if (CookieConsent.show) {
				CookieConsent.show(true)
			}
		}

		isInitialized = true
	})

	ccInstance = true
}

onMounted(() => {
	const unwatch = watch(
		[() => content.value, () => sectionData.value?.translations],
		([contentVal, translationsVal]) => {
			if (contentVal && translationsVal) {
				initCookieConsent()
				unwatch()
			}
		},
		{ immediate: true }
	)
})

onUnmounted(() => {
	if (CookieConsent.hide) {
		CookieConsent.hide()
	}
	if (CookieConsent.hidePreferences) {
		CookieConsent.hidePreferences()
	}

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

let previousContent = {}

watch(
	() => content.value,
	(newValue) => {
		if (!isInitialized || !newValue) return

		if (Object.keys(previousContent).length === 0) {
			previousContent = { ...newValue }
			return
		}

		const changedFields = []
		for (const key in newValue) {
			if (newValue[key] !== previousContent[key]) {
				changedFields.push(key)
			}
		}
		let preferencesChanged = false

		for (const field of changedFields) {
			if (field.toLowerCase().includes("preferences")) {
				preferencesChanged = true
				break
			}
		}

		previousContent = { ...newValue }

		initCookieConsent(preferencesChanged)
	},
	{ deep: true }
)
</script>

<template><div /></template>

<style>
#cc-main {
	z-index: 9999;
}

#cc-main,
#cc-main .cm,
#cc-main .pm {
	--cc-modal-transition-duration: 0;
}
</style>
