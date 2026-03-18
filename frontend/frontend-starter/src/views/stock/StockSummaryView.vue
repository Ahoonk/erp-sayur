<script setup>
import { ref, onMounted, watch, computed } from "vue";
import api from "../../api";
import debounce from "lodash-es/debounce";

const items = ref([]);
const categories = ref([]);
const isLoading = ref(false);
const searchQuery = ref("");
const perPage = ref(10);
const filterCategoryId = ref("");
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10,
});

function formatCurrency(val) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
}

function formatNumber(val, decimals = 3) {
    return Number(val || 0).toLocaleString("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: decimals,
    });
}

onMounted(async () => {
    await fetchData();
    try {
        const { data } = await api.get("/categories/all");
        categories.value = data.data;
    } catch (e) {
        console.error("Gagal memuat kategori", e);
    }
});

async function fetchData(page = 1) {
    isLoading.value = true;
    try {
        const params = {
            page,
            search: searchQuery.value,
            category_id: filterCategoryId.value,
        };
        if (perPage.value !== "all") {
            params.per_page = perPage.value;
        } else {
            params.per_page = 9999;
        }
        const { data } = await api.get("/stock/summary", { params });
        items.value = data.data.data;
        pagination.value = {
            current_page: data.data.meta.current_page,
            last_page: data.data.meta.last_page,
            total: data.data.meta.total,
            per_page: data.data.meta.per_page,
        };
    } catch (err) {
        console.error("Gagal memuat data stok", err);
    } finally {
        isLoading.value = false;
    }
}

const throttledSearch = debounce(() => {
    fetchData(1);
}, 400);

watch(searchQuery, () => throttledSearch());
watch(perPage, () => fetchData(1));
watch(filterCategoryId, () => fetchData(1));
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Stok Barang</h1>
                <p class="mt-1 text-sm text-slate-400">Ringkasan stok & modal rata-rata semua produk</p>
            </div>
        </div>

        <div class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
            <!-- Filter Bar -->
            <div class="flex flex-col items-start justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row md:items-center">
                <div class="flex flex-wrap items-end gap-3 w-full md:w-auto">
                    <!-- Category Filter -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Kategori</label>
                        <div class="relative">
                            <select
                                v-model="filterCategoryId"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[160px] pr-8"
                            >
                                <option value="">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.nama }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-row items-end w-full gap-2 md:w-auto">
                    <!-- Per Page -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Tampilkan</label>
                        <div class="relative">
                            <select
                                v-model="perPage"
                                class="appearance-none block w-24 px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm bg-white pr-8"
                            >
                                <option :value="10">10</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                                <option value="all">Semua</option>
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
                                placeholder="Cari kode atau nama barang..."
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
            <div v-if="isLoading" class="flex justify-center py-12">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-8 h-8 border-4 rounded-full border-slate-200 border-t-blue-500 animate-spin"></div>
                    <span class="text-sm font-medium text-slate-500">Memuat data...</span>
                </div>
            </div>

            <!-- Table -->
            <div v-else class="table-container">
                <table class="table-fixed-layout table-wide">
                    <thead class="table-header">
                        <tr>
                            <th class="w-12 text-center">No</th>
                            <th class="text-left">Kode Barang</th>
                            <th class="text-left">Nama Barang</th>
                            <th class="text-left">Kategori</th>
                            <th class="text-center">Satuan</th>
                            <th class="text-right">Total Stok</th>
                            <th class="text-right">Modal Rata-Rata</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-if="items.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 italic">Tidak ada data ditemukan.</td>
                        </tr>
                        <tr
                            v-for="(item, index) in items"
                            :key="item.id"
                            class="table-row transition"
                            :class="Number(item.total_stok) === 0 ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-slate-50'"
                        >
                            <td class="table-cell text-center text-slate-500 font-medium">
                                {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                            </td>
                            <td class="table-cell font-mono text-xs text-blue-600 font-semibold">
                                {{ item.kode_barang }}
                            </td>
                            <td class="table-cell font-medium text-slate-700">
                                {{ item.nama_barang }}
                            </td>
                            <td class="table-cell text-slate-500">
                                {{ item.category?.nama || "-" }}
                            </td>
                            <td class="table-cell text-center text-slate-500">
                                {{ item.unit?.nama || "-" }}
                            </td>
                            <td class="table-cell text-right font-bold" :class="Number(item.total_stok) === 0 ? 'text-red-600' : 'text-slate-700'">
                                {{ formatNumber(item.total_stok, 3) }}
                            </td>
                            <td class="table-cell text-right text-emerald-600 font-semibold">
                                {{ formatCurrency(item.modal_rata_rata) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination.last_page > 1"
                class="flex flex-col items-start justify-between gap-3 px-6 py-3 border-t sm:flex-row sm:items-center border-slate-200 bg-slate-50"
            >
                <div class="text-sm text-slate-500">
                    Menampilkan
                    <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span>
                    s/d
                    <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
                    dari
                    <span class="font-medium">{{ pagination.total }}</span>
                    hasil
                </div>
                <div class="flex gap-2">
                    <button
                        @click="fetchData(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >Sebelumnya</button>
                    <button
                        @click="fetchData(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >Selanjutnya</button>
                </div>
            </div>
        </div>
    </div>
</template>
