<script setup>
import { ref, computed, onMounted, watch } from "kirbyuse"
import { useSection, usePanel } from "kirbyuse"
import { section } from "kirbyuse/props"

const props = defineProps(section)

const { load } = useSection()
const panel = usePanel()

const sectionData = ref({})
const logs = ref([])
const stats = ref({})
const categories = ref([])
const isLoading = ref(false)
const error = ref(null)

const timeRange = ref("24h")
const categoryFilter = ref("")

const currentPage = ref(1)
const itemsPerPage = ref(50)
const totalItems = ref(0)
const totalPages = ref(0)

const categoryDropdown = ref(null)
const timerangeDropdown = ref(null)

const timeRanges = [
	{ value: "1h", label: panel.t("crumble.log.timeRange.lastHour") },
	{ value: "24h", label: panel.t("crumble.log.timeRange.last24Hours") },
	{ value: "7d", label: panel.t("crumble.log.timeRange.last7Days") },
	{ value: "30d", label: panel.t("crumble.log.timeRange.last30Days") },
	{ value: "all", label: panel.t("crumble.log.timeRange.allTime") }
]

const hasFilters = computed(() => {
	return timeRange.value !== "24h" || categoryFilter.value
})

const selectedCategoryLabel = computed(() => {
	if (!categoryFilter.value) return panel.t("crumble.log.categories.all")
	const category = categories.value.find((c) => c.id === categoryFilter.value)
	return category?.name || panel.t("crumble.log.categories.all")
})

const selectedTimeRangeLabel = computed(() => {
	const range = timeRanges.find((r) => r.value === timeRange.value)
	return range?.label || panel.t("crumble.log.timeRange.last24Hours")
})

const tableColumns = computed(() => ({
	action: {
		label: panel.t("crumble.log.action"),
		type: "text",
		width: "1/12",
		mobile: true
	},
	ip_address: {
		label: panel.t("crumble.log.ipAddress"),
		type: "text",
		width: "2/12",
		mobile: true
	},
	country_code: {
		label: panel.t("crumble.log.country"),
		type: "country",
		width: "1/12",
		mobile: false
	},
	timestamp: {
		label: panel.t("crumble.log.timestamp"),
		type: "date",
		display: "DD.MM.YYYY HH:mm",
		width: "2/12",
		mobile: true
	},
	accept_type: {
		label: panel.t("crumble.log.acceptType"),
		type: "accept-type",
		width: "2/12",
		mobile: true
	},
	categories: {
		label: panel.t("crumble.log.categories"),
		type: "tags",
		width: "4/12",
		mobile: true
	}
}))

const tableRows = computed(() => {
	return logs.value.map((log) => ({
		...log,
		timestamp: log.timestamp ? new Date(log.timestamp).toISOString() : null,
		categories: log.category_names || []
	}))
})

const statsReports = computed(() => {
	if (!stats.value || stats.value.error) return []

	return [
		{
			info: panel.t("crumble.log.stats.total"),
			value: String(stats.value.total || 0)
		},
		{
			info: panel.t("crumble.log.stats.consents"),
			value: String(stats.value.consents || 0),
			theme: "positive"
		},
		{
			info: panel.t("crumble.log.stats.withdrawals"),
			value: String(stats.value.withdrawals || 0),
			theme: "negative"
		},
		{
			info: panel.t("crumble.log.stats.acceptanceRate"),
			value: (stats.value.acceptanceRate || 0) + "%"
		}
	]
})

// methods
const loadSectionData = async () => {
	isLoading.value = true
	error.value = null

	try {
		const response = await load({
			parent: props.parent,
			name: props.name
		})

		sectionData.value = response

		if (response.logs) {
			logs.value = response.logs.items || []
			totalItems.value = response.logs.pagination?.total || 0
			totalPages.value = response.logs.pagination?.pages || 0
		}

		stats.value = response.stats || {}
		categories.value = response.categories || []

		error.value = response.logs?.error || response.stats?.error
	} catch (e) {
		error.value = e.message || panel.t("error.fetch")
	} finally {
		isLoading.value = false
	}
}

const fetchFilteredLogs = async () => {
	isLoading.value = true
	error.value = null

	try {
		const queryParams = new URLSearchParams()
		queryParams.set("page", currentPage.value.toString())
		queryParams.set("limit", itemsPerPage.value.toString())

		if (timeRange.value) queryParams.set("timeRange", timeRange.value)
		if (categoryFilter.value) queryParams.set("category", categoryFilter.value)

		const response = await panel.api.get(
			`${props.parent}/sections/${props.name}?${queryParams.toString()}`
		)

		if (response.logs) {
			logs.value = response.logs.items || []
			totalItems.value = response.logs.pagination?.total || 0
			totalPages.value = response.logs.pagination?.pages || 0
		}

		if (response.stats) {
			stats.value = response.stats
		}

		error.value = response.logs?.error
	} catch (e) {
		error.value = e.message || panel.t("error.fetch")
		logs.value = []
	} finally {
		isLoading.value = false
	}
}

const resetFilters = () => {
	timeRange.value = "24h"
	categoryFilter.value = ""
	currentPage.value = 1
	loadSectionData()
}

const goToPage = (page) => {
	currentPage.value = page
	fetchFilteredLogs()
}

watch([timeRange, categoryFilter], () => {
	currentPage.value = 1
	fetchFilteredLogs()
})

onMounted(() => {
	loadSectionData()
})
</script>

<template>
	<k-section :label="$t('crumble.log')" class="k-crumble-log-section">
		<k-button-group slot="options">
			<!-- Category filter dropdown -->
			<k-dropdown v-if="categories && categories.length > 0">
				<k-button
					icon="filter"
					variant="filled"
					size="sm"
					@click="categoryDropdown.toggle()"
				>
					{{ selectedCategoryLabel }}
				</k-button>
				<k-dropdown-content ref="categoryDropdown" align-x="end">
					<k-dropdown-item
						:current="!categoryFilter"
						@click="categoryFilter = ''"
					>
						{{ $t("crumble.log.categories.all") }}
					</k-dropdown-item>
					<hr />
					<k-dropdown-item
						v-for="cat in categories"
						:key="cat.id"
						:current="categoryFilter === cat.id"
						@click="categoryFilter = cat.id"
					>
						{{ cat.name }}
					</k-dropdown-item>
				</k-dropdown-content>
			</k-dropdown>

			<!-- Time range -->
			<k-dropdown>
				<k-button
					icon="calendar"
					variant="filled"
					size="sm"
					@click="timerangeDropdown.toggle()"
				>
					{{ selectedTimeRangeLabel }}
				</k-button>
				<k-dropdown-content ref="timerangeDropdown" align-x="end">
					<k-dropdown-item
						v-for="range in timeRanges"
						:key="range.value"
						:current="timeRange === range.value"
						@click="timeRange = range.value"
					>
						{{ range.label }}
					</k-dropdown-item>
				</k-dropdown-content>
			</k-dropdown>

			<!-- Reset filters -->
			<k-button
				v-if="hasFilters"
				icon="cancel"
				variant="filled"
				size="sm"
				@click="resetFilters"
			>
				{{ $t("crumble.log.resetFilters") }}
			</k-button>

			<!-- Refresh -->
			<k-button
				icon="refresh"
				variant="filled"
				size="sm"
				@click="loadSectionData"
			/>
		</k-button-group>

		<!-- Statistics -->
		<k-stats
			v-if="statsReports.length > 0"
			:reports="statsReports"
			size="large"
		/>

		<!-- Error state -->
		<k-empty v-if="error" icon="alert" class="crumble-log-error">
			{{ error }}
		</k-empty>

		<!-- Loading state -->
		<div v-else-if="isLoading" class="crumble-log-loading">
			<k-loader />
		</div>

		<!-- Empty state -->
		<k-empty v-else-if="!tableRows.length" icon="box">
			{{ hasFilters ? $t("crumble.log.noResults") : $t("crumble.log.empty") }}
		</k-empty>

		<!-- Table -->
		<k-table v-else :columns="tableColumns" :rows="tableRows">
			<template #action="{ row }">
				<k-tag :theme="row.action === 'consent' ? 'positive' : 'negative'">
					{{ $t(`crumble.log.action.${row.action}`) }}
				</k-tag>
			</template>
			<template #categories="{ row }">
				<div
					v-if="row.category_names && row.category_names.length"
					class="crumble-log-categories"
				>
					<k-tag
						v-for="(cat, index) in row.category_names"
						:key="index"
						size="small"
					>
						{{ cat }}
					</k-tag>
				</div>
			</template>
		</k-table>

		<!-- Pagination -->
		<k-pagination
			v-if="totalPages > 1"
			:page="currentPage"
			:total="totalPages"
			@paginate="goToPage"
			align="center"
			class="crumble-log-pagination"
		/>
	</k-section>
</template>

<style>
.k-crumble-log-section {
	.k-stats {
		margin-bottom: var(--spacing-6);
	}

	.crumble-log-loading {
		display: flex;
		justify-content: center;
		padding: var(--spacing-12);
	}

	.crumble-log-error {
		margin: var(--spacing-6) 0;
	}

	.crumble-log-categories {
		display: flex;
		gap: var(--spacing-1);
		flex-wrap: wrap;
	}

	.crumble-log-pagination {
		margin-top: var(--spacing-6);
	}

	.k-table {
		margin-bottom: var(--spacing-6);
	}
}
</style>
