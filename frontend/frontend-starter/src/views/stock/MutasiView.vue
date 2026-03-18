<script setup>
import { ref, onMounted, watch } from "vue";
import api from "../../api";
import DateInput from "../../components/DateInput.vue";
import debounce from "lodash-es/debounce";
import { useRouter } from "vue-router";

const router = useRouter();

const items = ref([]);
const categories = ref([]);
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
    start_date: "",
    end_date: "",
    category_id: "",
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

function formatDate(dateStr) {
    if (!dateStr) return "-";
    const [y, m, d] = dateStr.split("-");
    return `${d}-${m}-${y}`;
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
        const { data } = await api.get("/stock/mutasi", {
            params: {
                page,
                per_page: perPage.value,
                search: searchQuery.value,
                start_date: filters.value.start_date,
                end_date: filters.value.end_date,
                category_id: filters.value.category_id,
            },
        });
        items.value = data.data.data;
        pagination.value = {
            current_page: data.data.meta.current_page,
            last_page: data.data.meta.last_page,
            total: data.data.meta.total,
            per_page: data.data.meta.per_page,
        };
    } catch (err) {
        console.error("Gagal memuat mutasi", err);
    } finally {
        isLoading.value = false;
    }
}

const throttledSearch = debounce(() => fetchData(1), 400);
watch(searchQuery, () => throttledSearch());
watch(perPage, () => fetchData(1));
watch(() => filters.value, () => fetchData(1), { deep: true });
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Mutasi Barang</h1>
            <p class="mt-1 text-sm text-slate-400">Riwayat pembelian & pergerakan stok</p>
        </div>

        <div class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
            <!-- Filter Bar -->
            <div class="flex flex-col items-start justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row md:items-center">
                <div class="grid w-full grid-cols-2 gap-3 md:grid-cols-4 lg:flex md:w-auto">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Mulai</label>
                        <DateInput
                            v-model="filters.start_date"
                            class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Sampai</label>
                        <DateInput
                            v-model="filters.end_date"
                            :min="filters.start_date"
                            class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Kategori</label>
                        <div class="relative">
                            <select
                                v-model="filters.category_id"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[140px] pr-8"
                            >
                                <option value="">Semua Kategori</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nama }}</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 lg:justify-end">
                        <label class="text-[10px] font-bold text-slate-400 uppercase invisible hidden md:block">Reset</label>
                        <button
                            @click="filters = { start_date: '', end_date: '', category_id: '' }"
                            class="flex items-center justify-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-rose-500 hover:bg-rose-50 border border-slate-200 bg-white rounded-lg transition"
                            title="Reset Filter"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span class="md:hidden">Reset Filter</span>
                        </button>
                    </div>
                </div>

                <div class="flex flex-row items-end w-full gap-2 md:w-auto">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Tampilkan</label>
                        <div class="relative">
                            <select
                                v-model="perPage"
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
                    <div class="flex flex-col gap-1 grow md:grow-0">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Search</label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Cari nama barang / invoice..."
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
                            <th class="text-left">Tanggal</th>
                            <th class="text-left">No Invoice</th>
                            <th class="text-left">Supplier</th>
                            <th class="text-left">Kode Barang</th>
                            <th class="text-left">Nama Barang</th>
                            <th class="text-center">Satuan</th>
                            <th class="text-right">Qty Beli</th>
                            <th class="text-right">Harga Beli</th>
                            <th class="text-right">Subtotal</th>
                            <th class="text-right">Sisa Stok</th>
                            <th class="w-16 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-if="items.length === 0">
                            <td colspan="12" class="px-6 py-12 text-center text-slate-500 italic">Tidak ada data ditemukan.</td>
                        </tr>
                        <tr
                            v-for="(item, index) in items"
                            :key="item.id"
                            class="table-row hover:bg-slate-50 transition"
                        >
                            <td class="table-cell text-center text-slate-500 font-medium">
                                {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                            </td>
                            <td class="table-cell text-slate-600">{{ formatDate(item.tanggal) }}</td>
                            <td class="table-cell font-mono text-xs text-blue-600 font-semibold">{{ item.no_invoice }}</td>
                            <td class="table-cell text-slate-600">{{ item.supplier || "-" }}</td>
                            <td class="table-cell font-mono text-xs text-blue-600 font-semibold">{{ item.kode_barang }}</td>
                            <td class="table-cell font-medium text-slate-700">{{ item.nama_barang }}</td>
                            <td class="table-cell text-center text-slate-500">{{ item.unit }}</td>
                            <td class="table-cell text-right font-semibold text-slate-700">{{ formatNumber(item.qty, 3) }}</td>
                            <td class="table-cell text-right text-slate-600">{{ formatCurrency(item.harga_beli) }}</td>
                            <td class="table-cell text-right font-bold text-emerald-600">{{ formatCurrency(item.subtotal) }}</td>
                            <td class="table-cell text-right" :class="Number(item.qty_sisa) <= 0 ? 'text-red-500 font-bold' : 'text-slate-600 font-semibold'">
                                {{ formatNumber(item.qty_sisa, 3) }}
                            </td>
                            <td class="table-cell text-center">
                                <button
                                    v-if="item.purchase_id"
                                    @click="router.push(`/dashboard/purchases/${item.purchase_id}/edit`)"
                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                    title="Edit Pembelian"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
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
