import "./index.css"
import AcceptTypePreview from "./components/accept-type-preview.vue"
import CountryPreview from "./components/country-preview.vue"
import License from "./sections/license.vue"
import Log from "./sections/log.vue"
import StylePreview from "./sections/style-preview.vue"

panel.plugin("tobimori/crumble", {
	icons: {
		cookie:
			'<path d="M10.867 2.065C10.787 3.787 11.051 5.902 12 7c.865 1 2.5 1.5 4.5 1 0 2-.464 2.8 1.136 4 .762.571 2.51.936 4.294 1.164C21.353 18.138 17.129 22 12 22 6.477 22 2 17.523 2 12c0-5.14 3.878-9.372 8.867-9.935ZM8.99 4.588A8 8 0 0 0 12 20a7.999 7.999 0 0 0 7.497-5.215c-.2-.043-.398-.087-.589-.136-.797-.201-1.755-.511-2.472-1.048V13.6c-1.103-.828-1.772-1.76-1.949-3.023a5.01 5.01 0 0 1-.039-.406c-1.57-.114-2.982-.732-3.96-1.863h-.002c-.8-.926-1.195-2.12-1.4-3.153a11.204 11.204 0 0 1-.096-.567ZM10.25 15a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm5 0a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm-9-3a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm6-1a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm-4-3a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Z"/>'
	},
	components: {
		"k-accept-type-field-preview": AcceptTypePreview,
		"k-country-field-preview": CountryPreview
	},
	sections: {
		"crumble-license": License,
		"crumble-log": Log,
		"crumble-style-preview": StylePreview
	}
})
