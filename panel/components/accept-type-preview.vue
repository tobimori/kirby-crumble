<script setup>
defineProps({
	value: String
})

const ICONS = {
	necessary: "cancel",
	custom: "remove",
	all: "check"
}
</script>

<template>
	<span
		class="k-crumble-accept-type-preview k-text-field-preview"
		:data-type="value"
	>
		<k-icon :type="ICONS[value] || 'circle'" />
		<span class="k-crumble-accept-type-preview-text">
			{{ $t(`crumble.log.acceptType.${value}`) || value }}
		</span>
	</span>
</template>

<style>
.k-crumble-accept-type-preview {
	display: flex;
	align-items: center;
	gap: 0.375rem;
}

.k-crumble-accept-type-preview[data-type="necessary"] {
	color: var(--color-gray-600);
}

.k-crumble-accept-type-preview[data-type="custom"] {
	color: var(--color-yellow-600);
}

.k-crumble-accept-type-preview[data-type="all"] {
	color: var(--color-green-600);
}
</style>
