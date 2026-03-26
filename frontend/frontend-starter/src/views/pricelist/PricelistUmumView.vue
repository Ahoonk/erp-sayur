<script setup>
import { ref, onMounted, computed } from "vue";
import api from "../../api";
import CurrencyInput from "../../components/CurrencyInput.vue";
import ConfirmDialog from "../../components/ConfirmDialog.vue";

const MONTHS = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
];

const mode = ref("list"); // "list" | "edit"
const categories = ref([]);

// LIST mode
const pricelists = ref([]);
const listLoading = ref(false);

// EDIT mode
const selectedYear = ref(new Date().getFullYear());
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedPeriode = ref(1);
const editLoading = ref(false);
const saving = ref(false);
const editError = ref("");
const currentPricelistId = ref(null);

const editItems = ref([]);
const filterCategoryId = ref("");
const editSearchQuery = ref("");
const editPerPage = ref(50);

const editPagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 50,
});

const showDeleteConfirm = ref(false);
const deleteId = ref(null);
const deleting = ref(false);

function formatCurrency(val) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
}

function formatNumber(val, decimals = 3) {
    const num = Number(val || 0);
    return new Intl.NumberFormat("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: decimals,
    }).format(num);
}

function timeAgo(dateStr) {
    if (!dateStr) return "-";
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return "-";
    let diffSec = Math.floor((Date.now() - date.getTime()) / 1000);
    if (diffSec < 5) return "baru saja";
    if (diffSec < 60) return `${diffSec} detik lalu`;
    const diffMin = Math.floor(diffSec / 60);
    if (diffMin < 60) return `${diffMin} menit lalu`;
    const diffHour = Math.floor(diffMin / 60);
    if (diffHour < 24) return `${diffHour} jam lalu`;
    const diffDay = Math.floor(diffHour / 24);
    if (diffDay < 30) return `${diffDay} hari lalu`;
    const diffMonth = Math.floor(diffDay / 30);
    if (diffMonth < 12) return `${diffMonth} bulan lalu`;
    const diffYear = Math.floor(diffMonth / 12);
    return `${diffYear} tahun lalu`;
}

function formatDate(dateStr) {
    if (!dateStr) return "-";
    const [y, m, d] = dateStr.split("-");
    return `${d}-${m}-${y}`;
}

const lastDayOfMonth = computed(() => new Date(selectedYear.value, selectedMonth.value, 0).getDate());

const periodeOptions = computed(() => [
    { value: 1, label: `Periode 1 (1-15 ${MONTHS[selectedMonth.value - 1]})` },
    { value: 2, label: `Periode 2 (16-${lastDayOfMonth.value} ${MONTHS[selectedMonth.value - 1]})` },
]);

function getPeriodeLabel(bulan, periode, tahun) {
    const lastDay = new Date(tahun, bulan, 0).getDate();
    const range = periode === 1 ? `1-15` : `16-${lastDay}`;
    return `Periode ${periode} (${range} ${MONTHS[bulan - 1]})`;
}

const periodLabel = computed(() => {
    const opt = periodeOptions.value.find(o => o.value === selectedPeriode.value);
    return `${MONTHS[selectedMonth.value - 1]} ${selectedYear.value} — ${opt?.label || ''}`;
});

const filteredEditItems = computed(() => {
    let list = editItems.value;
    if (filterCategoryId.value) {
        list = list.filter(i => i.category_id == filterCategoryId.value);
    }
    if (editSearchQuery.value) {
        const q = editSearchQuery.value.toLowerCase();
        list = list.filter(i =>
            i.nama_barang?.toLowerCase().includes(q) ||
            i.kode_barang?.toLowerCase().includes(q)
        );
    }
    return list;
});

onMounted(async () => {
    await fetchList();
    try {
        const { data } = await api.get("/categories/all");
        categories.value = data.data;
    } catch (e) {
        console.error("Gagal memuat kategori", e);
    }
});

async function fetchList() {
    listLoading.value = true;
    try {
        const { data } = await api.get("/pricelist/umum");
        pricelists.value = data.data.data || [];
    } catch (e) {
        console.error("Gagal memuat pricelist", e);
    } finally {
        listLoading.value = false;
    }
}

async function openPricelist() {
    editLoading.value = true;
    editError.value = "";
    try {
        const { data } = await api.post("/pricelist/umum/open", {
            tahun: selectedYear.value,
            bulan: selectedMonth.value,
            periode: selectedPeriode.value,
        });
        currentPricelistId.value = data.data.pricelist_id;
        // Flatten items with reactive persentase and harga_jual
        editItems.value = (data.data.items || []).map(item => ({
            ...item,
            persentase: item.persentase != null ? Number(item.persentase) : 0,
            harga_jual: item.harga_jual != null ? Number(item.harga_jual) : 0,
            stok: item.stok != null ? Number(item.stok) : 0,
            last_purchase_at: item.last_purchase_at || null,
        }));
        mode.value = "edit";
    } catch (e) {
        editError.value = e.response?.data?.message || "Gagal membuka price list";
    } finally {
        editLoading.value = false;
    }
}

function onPersentaseInput(item) {
    const modal = Number(item.modal_rata_rata || 0);
    if (modal > 0) {
        const raw = Math.round(modal * (1 + item.persentase / 100) / 100) * 100;
        item.harga_jual = raw;
    }
}

function onHargaJualInput(item) {
    const modal = Number(item.modal_rata_rata || 0);
    if (modal > 0) {
        item.persentase = Number((((item.harga_jual - modal) / modal) * 100).toFixed(2));
    }
}

async function savePricelist() {
    saving.value = true;
    editError.value = "";
    try {
        const items = editItems.value.map(item => ({
            katalog_barang_id: item.katalog_barang_id,
            modal_rata_rata: item.modal_rata_rata,
            persentase: item.persentase,
            harga_jual: item.harga_jual,
        }));
        await api.post(`/pricelist/umum/${currentPricelistId.value}/items`, { items });
        mode.value = "list";
        await fetchList();
    } catch (e) {
        editError.value = e.response?.data?.message || "Gagal menyimpan";
    } finally {
        saving.value = false;
    }
}

function confirmDelete(id) {
    deleteId.value = id;
    showDeleteConfirm.value = true;
}

async function doDelete() {
    deleting.value = true;
    try {
        await api.delete(`/pricelist/umum/${deleteId.value}`);
        showDeleteConfirm.value = false;
        await fetchList();
    } catch (e) {
        alert(e.response?.data?.message || "Gagal menghapus");
    } finally {
        deleting.value = false;
    }
}

function backToList() {
    mode.value = "list";
    editItems.value = [];
    currentPricelistId.value = null;
    filterCategoryId.value = "";
    editSearchQuery.value = "";
    fetchList();
}
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <!-- LIST MODE -->
        <template v-if="mode === 'list'">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Price List Umum</h1>
                <p class="mt-1 text-sm text-slate-400">Kelola harga jual umum per periode</p>
            </div>

            <!-- Selector Card -->
            <div class="p-5 bg-white border shadow-sm rounded-xl border-slate-200">
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Buka Price List Periode</h3>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-slate-500">1. Tahun</label>
                        <input
                            v-model.number="selectedYear"
                            type="number"
                            min="2020"
                            max="2099"
                            class="w-24 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-slate-500">2. Bulan</label>
                        <div class="relative">
                            <select
                                v-model.number="selectedMonth"
                                class="appearance-none w-36 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white pr-8"
                            >
                                <option v-for="(m, i) in MONTHS" :key="i" :value="i + 1">{{ m }}</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-slate-500">3. Periode</label>
                        <div class="relative">
                            <select
                                v-model.number="selectedPeriode"
                                class="appearance-none w-64 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white pr-8"
                            >
                                <option v-for="opt in periodeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <button
                        @click="openPricelist"
                        :disabled="editLoading"
                        class="px-5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg hover:shadow-lg hover:shadow-blue-500/25 transition-all disabled:opacity-60 flex items-center gap-2"
                    >
                        <svg v-if="editLoading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ editLoading ? "Memuat..." : "Buka Price List" }}
                    </button>
                </div>
                <p v-if="editError" class="mt-2 text-sm text-red-500">{{ editError }}</p>
            </div>

            <!-- Existing Pricelists Table -->
            <div class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
                <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-sm font-semibold text-slate-700">Riwayat Price List</h3>
                </div>
                <div v-if="listLoading" class="flex justify-center py-8">
                    <div class="w-6 h-6 border-2 border-slate-200 border-t-blue-500 rounded-full animate-spin"></div>
                </div>
                <div v-else class="table-container">
                    <table class="table-fixed-layout">
                        <thead class="table-header">
                            <tr>
                                <th class="w-12 text-center">No</th>
                                <th class="text-left">Tahun</th>
                                <th class="text-left">Bulan</th>
                                <th class="text-left">Periode</th>
                                <th class="text-center">Jumlah Item</th>
                                <th class="text-left">Dibuat</th>
                                <th class="w-28 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <tr v-if="pricelists.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-slate-500 italic">Belum ada price list.</td>
                            </tr>
                            <tr v-for="(pl, idx) in pricelists" :key="pl.id" class="table-row hover:bg-slate-50 transition">
                                <td class="table-cell text-center text-slate-500">{{ idx + 1 }}</td>
                                <td class="table-cell font-medium text-slate-700">{{ pl.tahun }}</td>
                                <td class="table-cell text-slate-600">{{ MONTHS[pl.bulan - 1] }}</td>
                                <td class="table-cell text-slate-600">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                        {{ getPeriodeLabel(pl.bulan, pl.periode, pl.tahun) }}
                                    </span>
                                </td>
                                <td class="table-cell text-center">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                        {{ pl.items_count || 0 }} item
                                    </span>
                                </td>
                                <td class="table-cell text-slate-500 text-sm">{{ formatDate(pl.created_at?.split("T")[0]) }}</td>
                                <td class="table-cell text-center">
                                    <div class="flex justify-center gap-1">
                                        <button
                                            @click="selectedYear = pl.tahun; selectedMonth = pl.bulan; selectedPeriode = pl.periode; openPricelist()"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="Buka / Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmDelete(pl.id)"
                                            class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                                            title="Hapus"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- EDIT MODE -->
        <template v-if="mode === 'edit'">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <button
                        @click="backToList"
                        class="p-2.5 hover:bg-slate-100 rounded-xl transition"
                    >
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Price List Umum - {{ periodLabel }}</h1>
                        <p class="text-sm text-slate-400 mt-0.5">Edit harga jual per produk untuk periode ini</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="backToList"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition"
                    >Kembali</button>
                    <button
                        @click="savePricelist"
                        :disabled="saving"
                        class="px-5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg hover:shadow-lg transition disabled:opacity-60"
                    >
                        {{ saving ? "Menyimpan..." : "Simpan" }}
                    </button>
                </div>
            </div>

            <p v-if="editError" class="text-sm text-red-500">{{ editError }}</p>

            <!-- Category Filter -->
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-slate-600">Filter Kategori:</label>
                <div class="relative">
                    <select
                        v-model="filterCategoryId"
                        class="appearance-none px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none bg-white min-w-[160px] pr-8"
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
                <span class="text-sm text-slate-400">{{ filteredEditItems.length }} produk ditampilkan</span>
            </div>

            <!-- Search Item -->
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-slate-600">Cari Barang:</label>
                <div class="relative w-full max-w-md">
                    <input
                        v-model="editSearchQuery"
                        type="text"
                        placeholder="Cari kode atau nama barang..."
                        class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                    />
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
                <div class="table-container">
                    <table class="table-fixed-layout table-wide">
                        <thead class="table-header">
                            <tr>
                                <th class="w-12 text-center">No</th>
                                <th class="text-left">Kode Barang</th>
                                <th class="text-left">Nama Barang</th>
                                <th class="text-center">Satuan</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Update Pembelian</th>
                                <th class="text-right">Modal Rata-Rata</th>
                                <th class="text-right w-32">Persentase (%)</th>
                                <th class="text-right w-44">Harga Jual (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <tr v-if="filteredEditItems.length === 0">
                                <td colspan="9" class="px-6 py-10 text-center text-slate-500 italic">Tidak ada produk.</td>
                            </tr>
                            <tr
                                v-for="(item, idx) in filteredEditItems"
                                :key="item.katalog_barang_id"
                                class="table-row hover:bg-slate-50 transition"
                            >
                                <td class="table-cell text-center text-slate-500">{{ idx + 1 }}</td>
                                <td class="table-cell font-mono text-xs text-blue-600 font-semibold">{{ item.kode_barang }}</td>
                                <td class="table-cell font-medium text-slate-700">{{ item.nama_barang }}</td>
                                <td class="table-cell text-center text-slate-500">{{ item.unit }}</td>
                                <td class="table-cell text-center">
                                    <span :class="Number(item.stok) <= 0 ? 'text-rose-600 font-semibold' : 'text-slate-600'">
                                        {{ formatNumber(item.stok, 3) }}
                                    </span>
                                </td>
                                <td class="table-cell text-center text-xs text-slate-500">
                                    {{ timeAgo(item.last_purchase_at) }}
                                </td>
                                <td class="table-cell text-right text-slate-600">{{ formatCurrency(item.modal_rata_rata) }}</td>
                                <td class="table-cell text-right">
                                    <input
                                        v-model.number="item.persentase"
                                        @input="onPersentaseInput(item)"
                                        type="number"
                                        step="0.01"
                                        class="w-full px-2 py-1.5 text-sm text-right border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-400 outline-none bg-slate-50/50"
                                        placeholder="0"
                                    />
                                </td>
                                <td class="table-cell text-right">
                                    <CurrencyInput
                                        v-model="item.harga_jual"
                                        @update:modelValue="onHargaJualInput(item)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottom Save -->
            <div class="flex justify-end gap-2">
                <button @click="backToList" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition">Kembali</button>
                <button
                    @click="savePricelist"
                    :disabled="saving"
                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg hover:shadow-lg transition disabled:opacity-60"
                >{{ saving ? "Menyimpan..." : "Simpan" }}</button>
            </div>
        </template>

        <ConfirmDialog
            :show="showDeleteConfirm"
            :loading="deleting"
            title="Hapus Price List"
            message="Yakin ingin menghapus price list ini? Semua item harga akan dihapus."
            @confirm="doDelete"
            @cancel="showDeleteConfirm = false"
        />
    </div>
</template>
