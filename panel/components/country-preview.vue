<script setup>
defineProps({
	value: String
})

// map country codes to flag emojis
const FLAGS = {
	// europe
	'de': '🇩🇪',
	'fr': '🇫🇷',
	'gb': '🇬🇧',
	'uk': '🇬🇧',
	'es': '🇪🇸',
	'it': '🇮🇹',
	'nl': '🇳🇱',
	'be': '🇧🇪',
	'ch': '🇨🇭',
	'at': '🇦🇹',
	'pl': '🇵🇱',
	'cz': '🇨🇿',
	'dk': '🇩🇰',
	'se': '🇸🇪',
	'no': '🇳🇴',
	'fi': '🇫🇮',
	'pt': '🇵🇹',
	'gr': '🇬🇷',
	'ie': '🇮🇪',
	'hu': '🇭🇺',
	'ro': '🇷🇴',
	'bg': '🇧🇬',
	'hr': '🇭🇷',
	'sk': '🇸🇰',
	'si': '🇸🇮',
	'lt': '🇱🇹',
	'lv': '🇱🇻',
	'ee': '🇪🇪',
	'lu': '🇱🇺',
	'mt': '🇲🇹',
	'cy': '🇨🇾',
	
	// americas
	'us': '🇺🇸',
	'ca': '🇨🇦',
	'mx': '🇲🇽',
	'br': '🇧🇷',
	'ar': '🇦🇷',
	'cl': '🇨🇱',
	'co': '🇨🇴',
	'pe': '🇵🇪',
	've': '🇻🇪',
	'uy': '🇺🇾',
	'py': '🇵🇾',
	'bo': '🇧🇴',
	'ec': '🇪🇨',
	'cr': '🇨🇷',
	'pa': '🇵🇦',
	'cu': '🇨🇺',
	'do': '🇩🇴',
	'gt': '🇬🇹',
	'hn': '🇭🇳',
	'ni': '🇳🇮',
	'sv': '🇸🇻',
	
	// asia pacific
	'cn': '🇨🇳',
	'jp': '🇯🇵',
	'kr': '🇰🇷',
	'in': '🇮🇳',
	'au': '🇦🇺',
	'nz': '🇳🇿',
	'sg': '🇸🇬',
	'my': '🇲🇾',
	'id': '🇮🇩',
	'th': '🇹🇭',
	'ph': '🇵🇭',
	'vn': '🇻🇳',
	'hk': '🇭🇰',
	'tw': '🇹🇼',
	'pk': '🇵🇰',
	'bd': '🇧🇩',
	'lk': '🇱🇰',
	
	// middle east & africa
	'ae': '🇦🇪',
	'sa': '🇸🇦',
	'eg': '🇪🇬',
	'za': '🇿🇦',
	'ng': '🇳🇬',
	'ke': '🇰🇪',
	'ma': '🇲🇦',
	'il': '🇮🇱',
	'tr': '🇹🇷',
	'qa': '🇶🇦',
	'kw': '🇰🇼',
	'bh': '🇧🇭',
	'om': '🇴🇲',
	'jo': '🇯🇴',
	'lb': '🇱🇧',
	'tn': '🇹🇳',
	'dz': '🇩🇿',
	'et': '🇪🇹',
	'gh': '🇬🇭',
	'ug': '🇺🇬',
	'tz': '🇹🇿',
	
	// other
	'ru': '🇷🇺',
	'ua': '🇺🇦',
	'by': '🇧🇾',
	'kz': '🇰🇿',
	'uz': '🇺🇿',
	'ge': '🇬🇪',
	'am': '🇦🇲',
	'az': '🇦🇿',
	'is': '🇮🇸',
	'al': '🇦🇱',
	'ba': '🇧🇦',
	'rs': '🇷🇸',
	'me': '🇲🇪',
	'mk': '🇲🇰',
	'md': '🇲🇩'
}

// get flag emoji or fallback to globe
const getFlag = (code) => {
	if (!code) return '🌍'
	const lowerCode = code.toLowerCase()
	return FLAGS[lowerCode] || '🌍'
}
</script>

<template>
	<span
		class="k-crumble-country-preview k-text-field-preview"
		:data-country="value"
		:title="value ? value.toUpperCase() : ''"
	>
		<span class="k-crumble-country-preview-flag">
			{{ getFlag(value) }}
		</span>
		<span class="k-crumble-country-preview-code">
			{{ value ? value.toUpperCase() : '—' }}
		</span>
	</span>
</template>

<style>
.k-crumble-country-preview {
	display: inline-flex;
	align-items: center;
	gap: 0.375rem;
}

.k-crumble-country-preview-flag {
	font-size: 1.125rem;
	line-height: 1;
	display: inline-flex;
}

.k-crumble-country-preview-code {
	font-size: var(--text-sm);
	color: var(--color-text-dimmed);
	font-family: var(--font-mono);
}
</style>