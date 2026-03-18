<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../../api";
import { useToast } from "../../composables/useToast";
import debounce from "lodash-es/debounce";

const router = useRouter();
const toast = useToast();

const items = ref([]);

async function printBarcode(purchaseId, productId) {
    if (!purchaseId) return toast.error("ID Pembelian tidak tersedia");
    router.push({
        name: "purchase-barcode",
        params: { id: purchaseId },
        query: { product_id: productId },
    });
}
const brands = ref([]);
const isLoading = ref(false);
const searchQuery = ref("");
const perPage = ref(10);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10,
});

const filters = ref({
    brand_id: "",
});

onMounted(() => {
    fetchItems();
    fetchBrands();
});

async function fetchBrands() {
    try {
        const { data } = await api.get("/brands/all");
        brands.value = data.data;
    } catch (e) {
        console.error("Gagal memuat merk", e);
    }
}

async function fetchItems(page = 1) {
    isLoading.value = true;
    try {
        const { data } = await api.get("/products/summary", {
            params: {
                page,
                per_page: perPage.value,
                search: searchQuery.value,
                brand_id: filters.value.brand_id,
            },
        });

        items.value = data.data.data;
        pagination.value = {
            current_page: data.data.current_page,
            last_page: data.data.last_page,
            total: data.data.total,
            per_page: data.data.per_page,
        };
    } catch (err) {
        toast.error("Gagal memuat stok barang");
    } finally {
        isLoading.value = false;
    }
}

const throttledSearch = debounce(() => {
    fetchItems(1);
}, 500);

watch(searchQuery, () => {
    throttledSearch();
});

watch(perPage, () => {
    fetchItems(1);
});

watch(
    () => filters.value,
    () => {
        fetchItems(1);
    },
    { deep: true }
);

function resetFilters() {
    filters.value = { brand_id: "" };
    searchQuery.value = "";
    fetchItems(1);
}

const showDetailModal = ref(false);
const detailLoading = ref(false);
const detailItems = ref([]);
const detailHeader = ref({ nama: "", grade: "-" });
const detailSearch = ref("");
const detailPerPage = ref(10);
const detailPage = ref(1);

const detailFiltered = computed(() => {
    let rows = [...detailItems.value];
    // Sort by newest first
    rows.sort((a, b) => {
        const da = a.tanggal_pembelian || "";
        const db = b.tanggal_pembelian || "";
        if (db > da) return 1;
        if (db < da) return -1;
        return (b.id || 0) - (a.id || 0);
    });
    const q = detailSearch.value.toLowerCase().trim();
    if (q) {
        rows = rows.filter((row) => {
            return (
                (row.invoice_pembelian || "").toLowerCase().includes(q) ||
                (row.barcode || "").toLowerCase().includes(q)
            );
        });
    }
    return rows;
});

const detailTotalPages = computed(() =>
    Math.max(1, Math.ceil(detailFiltered.value.length / detailPerPage.value))
);

const detailPaginated = computed(() => {
    const start = (detailPage.value - 1) * detailPerPage.value;
    return detailFiltered.value.slice(start, start + detailPerPage.value);
});

watch(detailSearch, () => {
    detailPage.value = 1;
});
watch(detailPerPage, () => {
    detailPage.value = 1;
});

async function openDetail(item) {
    showDetailModal.value = true;
    detailLoading.value = true;
    detailItems.value = [];
    detailSearch.value = "";
    detailPerPage.value = 10;
    detailPage.value = 1;
    detailHeader.value = { nama: item.nama };
    try {
        const { data } = await api.get("/products/stock-details", {
            params: { master_product_id: item.master_product_id },
        });
        detailItems.value = data.data || [];
    } catch (error) {
        toast.error("Gagal memuat detail SKU");
    } finally {
        detailLoading.value = false;
    }
}

function closeDetail() {
    showDetailModal.value = false;
    detailItems.value = [];
}

function formatCurrency(value) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(Number(value || 0));
}

function formatDate(dateStr) {
    if (!dateStr) return "-";
    const [y, m, d] = String(dateStr).split("-");
    if (!y || !m || !d) return dateStr;
    return `${d}-${m}-${y}`;
}

function productIdentifier(item) {
    if (item?.imei1 && item?.imei2) {
        return `IMEI1: ${item.imei1} / IMEI2: ${item.imei2}`;
    }
    if (item?.imei1) {
        return `IMEI: ${item.imei1}`;
    }
    return item?.barcode || "-";
}
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Stok Barang (Ready)
                </h1>
                <p class="mt-1 text-sm text-slate-400">
                    Ringkasan stok barang yang tersedia untuk dijual
                </p>
            </div>
        </div>

        <!-- Filter Section -->
        <div
            class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200"
        >
            <div
                class="flex flex-col items-start justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row md:items-center"
            >
                <!-- Left: Filters -->
                <div
                    class="grid w-full grid-cols-2 gap-3 md:grid-cols-2 lg:flex md:w-auto"
                >
                    <div class="flex flex-col col-span-2 gap-1 md:col-span-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Merk</label
                        >
                        <div class="relative">
                            <select
                                v-model="filters.brand_id"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white pr-8"
                            >
                                <option value="">Semua Merk</option>
                                <option
                                    v-for="b in brands"
                                    :key="b.id"
                                    :value="b.id"
                                >
                                    {{ b.nama }}
                                </option>
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-slate-400"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex flex-col col-span-2 gap-1 md:col-span-1 lg:justify-end"
                    >
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase invisible hidden md:block"
                            >Reset</label
                        >
                        <button
                            @click="resetFilters"
                            class="flex items-center justify-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-rose-500 hover:bg-rose-50 border border-slate-200 bg-white rounded-lg transition md:p-2 md:border-0 md:bg-transparent md:rounded-none md:justify-start"
                            title="Reset Filter"
                        >
                            <svg
                                class="w-4 h-4 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                            <span class="md:hidden">Reset Filter</span>
                        </button>
                    </div>
                </div>

                <!-- Right: Per Page & Search -->
                <div class="flex flex-row items-end w-full gap-2 md:w-auto">
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Tampilkan</label
                        >
                        <div class="relative">
                            <select
                                v-model="perPage"
                                class="appearance-none block w-20 px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm bg-white pr-8"
                            >
                                <option :value="10">10</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 grow md:grow-0">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Search</label
                        >
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari produk..."
                                class="block w-full md:w-64 pl-10 pr-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm"
                            />
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                            >
                                <svg
                                    class="w-4 h-4 text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="border-0 rounded-none shadow-none table-container">
                <table class="table-fixed-layout table-wide">
                    <thead class="table-header">
                        <tr class="">
                            <th class="">Nama Produk</th>
                            <th class="text-center">Merk</th>
                            <th class="text-center">Total Stok</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="isLoading">
                            <td
                                colspan="5"
                                class="px-4 py-12 text-center text-slate-500"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <div
                                        class="w-8 h-8 border-4 rounded-full border-slate-200 border-t-blue-500 animate-spin"
                                    ></div>
                                    <span class="text-sm font-medium"
                                        >Memuat data...</span
                                    >
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="items.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-12 italic text-center text-slate-500"
                            >
                                Tidak ada produk ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="item in items"
                            :key="item.id"
                            class="table-row group"
                        >
                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    <div>
                                        <button
                                            @click="openDetail(item)"
                                            class="font-bold text-left text-blue-600 uppercase transition-colors cursor-pointer hover:text-blue-800 hover:underline"
                                        >
                                            {{ item.nama }}
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="table-cell text-center">
                                <span
                                    class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase"
                                    >{{ item.brand?.nama || "Generic" }}</span
                                >
                            </td>
                            <td class="table-cell text-center">
                                <span
                                    class="text-base font-black text-slate-800"
                                    >{{ item.total_stok }}</span
                                >
                                <span
                                    class="text-[10px] font-bold text-slate-400 ml-1 uppercase block"
                                    >Ready</span
                                >
                            </td>
                            <td class="table-cell text-center">
                                <button
                                    @click="openDetail(item)"
                                    class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg inline-flex"
                                    title="Detail"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                </button>
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
                    <span class="font-medium">{{
                        (pagination.current_page - 1) * pagination.per_page + 1
                    }}</span>
                    s/d
                    <span class="font-medium">{{
                        Math.min(
                            pagination.current_page * pagination.per_page,
                            pagination.total
                        )
                    }}</span>
                    dari
                    <span class="font-medium">{{ pagination.total }}</span>
                    hasil
                </div>
                <div class="flex gap-2">
                    <button
                        @click="fetchItems(pagination.current_page - 1)"
                        :disabled="pagination.current_page <= 1"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Sebelumnya
                    </button>
                    <button
                        @click="fetchItems(pagination.current_page + 1)"
                        :disabled="
                            pagination.current_page >= pagination.last_page
                        "
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Selanjutnya
                    </button>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div
            v-if="showDetailModal"
            class="fixed inset-0 z-[80] bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
            @click.self="showDetailModal = false"
        >
            <div
                class="flex flex-col w-full max-w-6xl bg-white border shadow-xl rounded-2xl border-slate-100"
                style="max-height: 90vh"
            >
                <!-- Modal Header -->
                <div
                    class="flex flex-col items-start justify-between gap-3 px-6 py-4 border-b sm:flex-row sm:items-center border-slate-100 bg-slate-50 rounded-t-2xl shrink-0"
                >
                    <div>
                        <h3
                            class="text-sm font-black tracking-wider uppercase text-slate-800"
                        >
                            Detail SKU
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ detailHeader.nama }}
                        </p>
                    </div>
                    <button
                        @click="showDetailModal = false"
                        class="p-2 transition rounded-lg text-slate-500 hover:bg-slate-200"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Toolbar: search + per-page -->
                <div
                    class="flex flex-wrap items-end gap-3 px-4 pt-4 pb-2 shrink-0"
                >
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Tampilkan</label
                        >
                        <div class="relative">
                            <select
                                v-model="detailPerPage"
                                class="appearance-none w-20 px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none bg-white pr-7"
                            >
                                <option :value="10">10</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 grow">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Cari Invoice / Barcode</label
                        >
                        <div class="relative">
                            <input
                                v-model="detailSearch"
                                type="text"
                                placeholder="Cari invoice atau barcode..."
                                class="w-full sm:w-72 pl-9 pr-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none"
                            />
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 pb-1.5">
                        {{ detailFiltered.length }} data
                    </p>
                </div>

                <!-- Table (scrollable) -->
                <div class="flex-1 px-4 overflow-auto">
                    <table class="w-full" style="font-size: 13px">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-xs font-bold text-left uppercase text-slate-500 whitespace-nowrap"
                                >
                                    Produk
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-bold text-left uppercase text-slate-500 whitespace-nowrap"
                                >
                                    Invoice
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-bold text-left uppercase text-slate-500 whitespace-nowrap"
                                >
                                    Supplier
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-bold text-left uppercase text-slate-500 whitespace-nowrap"
                                >
                                    Satuan
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-bold text-left uppercase text-slate-500 whitespace-nowrap"
                                >
                                    Grade
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-bold text-right uppercase text-slate-500 whitespace-nowrap"
                                >
                                    Harga Beli
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-bold text-center uppercase text-slate-500 whitespace-nowrap"
                                >
                                    Stok
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-bold text-center uppercase text-slate-500 whitespace-nowrap"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="detailLoading">
                                <td
                                    colspan="9"
                                    class="px-4 py-8 text-center text-slate-500"
                                >
                                    Memuat detail...
                                </td>
                            </tr>
                            <tr v-else-if="detailFiltered.length === 0">
                                <td
                                    colspan="9"
                                    class="px-4 py-8 text-center text-slate-400"
                                >
                                    Tidak ada data ditemukan.
                                </td>
                            </tr>
                            <tr
                                v-for="row in detailPaginated"
                                :key="row.id"
                                class="transition-colors hover:bg-slate-50"
                            >
                                <td class="px-4 py-2.5">
                                    <div class="font-semibold text-slate-800">
                                        {{ row.nama }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ row.barcode || "-" }}
                                        <span v-if="row.imei1 || row.imei2">
                                            | IMEI: {{ row.imei1 || "-" }} /
                                            {{ row.imei2 || "-" }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-slate-700">
                                    {{ row.invoice_pembelian || "-" }}
                                    <div class="text-xs text-slate-500">
                                        {{ formatDate(row.tanggal_pembelian) }}
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-slate-700">
                                    {{ row.supplier || "-" }}
                                </td>
                                <td
                                    class="px-4 py-2.5 text-slate-700 uppercase"
                                >
                                    {{ row.satuan || "-" }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-700">
                                    {{ row.grade || "-" }}
                                </td>
                                <td
                                    class="px-4 py-2.5 font-semibold text-right text-slate-700"
                                >
                                    {{ formatCurrency(row.harga_modal) }}
                                </td>
                                <td
                                    class="px-4 py-2.5 font-bold text-center text-slate-800"
                                >
                                    {{ row.stok }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <button
                                        v-if="row.purchase_id"
                                        @click="
                                            printBarcode(
                                                row.purchase_id,
                                                row.id
                                            )
                                        "
                                        class="p-1.5 text-purple-500 hover:bg-purple-50 rounded-lg transition"
                                        title="Cetak Barcode"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                            />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Modal Pagination -->
                <div
                    v-if="detailTotalPages > 1 || detailFiltered.length > 0"
                    class="flex flex-col items-start justify-between gap-3 px-6 py-3 border-t sm:flex-row sm:items-center border-slate-100 bg-slate-50 rounded-b-2xl shrink-0"
                >
                    <p class="text-xs text-slate-500">
                        Menampilkan
                        <span class="font-medium">{{
                            detailFiltered.length === 0
                                ? 0
                                : (detailPage - 1) * detailPerPage + 1
                        }}</span>
                        s/d
                        <span class="font-medium">{{
                            Math.min(
                                detailPage * detailPerPage,
                                detailFiltered.length
                            )
                        }}</span>
                        dari
                        <span class="font-medium">{{
                            detailFiltered.length
                        }}</span>
                        data
                    </p>
                    <div class="flex gap-2">
                        <button
                            @click="detailPage--"
                            :disabled="detailPage <= 1"
                            class="px-3 py-1 text-xs font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Sebelumnya
                        </button>
                        <span
                            class="px-3 py-1 text-xs font-medium text-slate-600"
                            >{{ detailPage }} / {{ detailTotalPages }}</span
                        >
                        <button
                            @click="detailPage++"
                            :disabled="detailPage >= detailTotalPages"
                            class="px-3 py-1 text-xs font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Selanjutnya
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
