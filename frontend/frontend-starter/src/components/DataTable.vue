<template>
    <div>
        <!-- Header Section with Filters + Search -->
        <div
            class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200 mb-4"
        >
            <!-- Filter Bar -->
            <div
                class="flex flex-col items-start justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row md:items-center"
            >
                <!-- Left: Custom Filters (slot) -->
                <div class="w-full md:w-auto flex items-end gap-3">
                    <slot name="filters"></slot>
                    <slot name="actions"></slot>
                </div>

                <!-- Right: Per Page & Search -->
                <div class="flex flex-row items-end gap-2 w-full md:w-auto">
                    <!-- Per Page -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Tampilkan</label>
                        <div class="relative">
                            <select
                                v-model="perPage"
                                @change="onPerPageChange"
                                class="appearance-none block w-20 px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm bg-white pr-8"
                            >
                                <option :value="10">10</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="flex flex-col gap-1 grow md:grow-0">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Search</label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="searchQuery"
                                @input="onSearchDebounced"
                                :placeholder="searchPlaceholder"
                                class="block w-full md:w-64 pl-10 pr-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm"
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-12">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-8 h-8 border-4 rounded-full border-slate-200 border-t-blue-500 animate-spin"></div>
                    <span class="text-sm font-medium text-slate-500">Memuat data...</span>
                </div>
            </div>

            <!-- Table Section -->
            <div v-else class="table-container">
                <table class="table-fixed-layout" :class="{ 'table-wide': wideTable }">
                    <thead class="table-header">
                        <tr>
                            <th class="w-16 text-center">No</th>
                            <th
                                v-for="col in columns"
                                :key="col.key"
                                class="table-cell"
                                :class="col.class || ''"
                            >
                                {{ col.label }}
                            </th>
                            <th
                                v-if="$slots.rowActions"
                                class="table-col-action-h"
                            >
                                AKSI
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-if="data.length === 0">
                            <td
                                :colspan="columns.length + ($slots.rowActions ? 2 : 1)"
                                class="px-6 py-12 text-center text-slate-500 italic"
                            >
                                Tidak ada data ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="(row, index) in data"
                            :key="row.id"
                            class="table-row group"
                        >
                            <td class="table-cell text-slate-500 text-center font-medium">
                                {{ startNumber + index }}
                            </td>
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="table-cell text-slate-700"
                                :class="col.cellClass || ''"
                            >
                                <slot
                                    :name="'cell-' + col.key"
                                    :row="row"
                                    :value="getCellValue(row, col.key)"
                                >
                                    {{ getCellValue(row, col.key) || "-" }}
                                </slot>
                            </td>
                            <td v-if="$slots.rowActions" class="table-col-action">
                                <div class="table-actions">
                                    <slot name="rowActions" :row="row"></slot>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination && pagination.last_page > 1"
                class="flex flex-col gap-3 sm:flex-row sm:items-center justify-between px-4 sm:px-6 py-3 border-t border-slate-200 bg-slate-50"
            >
                <div class="text-sm text-slate-500">
                    Menampilkan
                    <span class="font-medium">{{ startNumber }}</span>
                    s/d
                    <span class="font-medium">{{ endNumber }}</span>
                    dari
                    <span class="font-medium">{{ pagination.total }}</span>
                    hasil
                </div>
                <div class="flex gap-2">
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage <= 1"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Sebelumnya
                    </button>
                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage >= lastPage"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Selanjutnya
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    columns: { type: Array, required: true },
    fetchFunction: { type: Function, required: true },
    searchPlaceholder: { type: String, default: "Cari..." },
    initialPerPage: { type: Number, default: 10 },
    wideTable: { type: Boolean, default: false },
    externalFilters: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["loaded", "filter-change"]);

const data = ref([]);
const loading = ref(false);
const searchQuery = ref("");
const perPage = ref(props.initialPerPage);
const currentPage = ref(1);
const lastPage = ref(1);
const pagination = ref(null);

let debounceTimer = null;

const startNumber = computed(() => {
    return (currentPage.value - 1) * perPage.value + 1;
});

const endNumber = computed(() => {
    return Math.min(currentPage.value * perPage.value, pagination.value?.total || 0);
});

const visiblePages = computed(() => {
    const pages = [];
    const total = lastPage.value;
    const current = currentPage.value;

    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 3) pages.push("...");
        for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
            pages.push(i);
        }
        if (current < total - 2) pages.push("...");
        pages.push(total);
    }
    return pages;
});

async function fetchData() {
    loading.value = true;
    try {
        const params = {
            page: currentPage.value,
            per_page: perPage.value,
            search: searchQuery.value,
            ...props.externalFilters,
        };

        const result = await props.fetchFunction(params);

        if (result?.data) {
            data.value = result.data;
            currentPage.value = result.current_page;
            lastPage.value = result.last_page;
            pagination.value = result;
        }
        emit("loaded", result);
    } catch (error) {
        console.error("DataTable fetch error:", error);
    } finally {
        loading.value = false;
    }
}

function onSearchDebounced() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        currentPage.value = 1;
        fetchData();
    }, 300);
}

function onPerPageChange() {
    currentPage.value = 1;
    fetchData();
}

function goToPage(page) {
    if (page < 1 || page > lastPage.value) return;
    currentPage.value = page;
    fetchData();
}

function refresh() {
    fetchData();
}

function resetFilters() {
    searchQuery.value = "";
    currentPage.value = 1;
    fetchData();
}

function getCellValue(row, key) {
    if (!row || !key) return null;
    if (!key.includes(".")) return row[key];

    return key.split(".").reduce((acc, part) => {
        if (acc === null || acc === undefined) return null;
        return acc[part];
    }, row);
}

watch(() => props.externalFilters, () => {
    currentPage.value = 1;
    fetchData();
}, { deep: true });

fetchData();

defineExpose({ refresh, fetchData, resetFilters, goToPage });
</script>
