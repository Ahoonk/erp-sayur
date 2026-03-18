<script setup>
import { ref, computed, watch, onMounted } from "vue";
import debounce from "lodash-es/debounce";
import api from "../../api";
import { useToast } from "../../composables/useToast";

const toast = useToast();

const searchQuery = ref("");
const items = ref([]);
const isLoading = ref(false);
const selectedItem = ref(null);
const saving = ref(false);
const history = ref([]);
const historyLoading = ref(false);
const historySearch = ref("");
const historyReason = ref("");
const historyStartDate = ref("");
const historyEndDate = ref("");
const historyPagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10,
});

const reasons = [
    "REJECT SUPPLIER",
    "EXPIRED",
    "BARANG HILANG/SUSUT",
];

const todayStr = () => new Date().toISOString().slice(0, 10);

const form = ref({
    reason: "",
    qty: "",
    tanggal: todayStr(),
    keterangan: "",
});

const expiring = ref([]);
const expiringLoading = ref(false);
const expiringSearch = ref("");
const expiringPagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10,
});

const availableStock = computed(() => Number(selectedItem.value?.total_stok || 0));

function formatNumber(val, decimals = 3) {
    return Number(val || 0).toLocaleString("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: decimals,
    });
}

function formatDate(val) {
    if (!val) return "-";
    const d = new Date(val);
    return d.toLocaleDateString("id-ID", {
        year: "numeric",
        month: "short",
        day: "2-digit",
    });
}

async function fetchItems(page = 1) {
    isLoading.value = true;
    try {
        const params = {
            page,
            per_page: 15,
            search: searchQuery.value,
        };
        const { data } = await api.get("/stock/summary", { params });
        items.value = data.data.data || [];
    } catch (err) {
        toast.error("Gagal memuat daftar barang");
    } finally {
        isLoading.value = false;
    }
}

async function fetchHistory(page = 1) {
    historyLoading.value = true;
    try {
        const params = {
            page,
            per_page: historyPagination.value.per_page,
            search: historySearch.value,
            reason: historyReason.value,
            start_date: historyStartDate.value || undefined,
            end_date: historyEndDate.value || undefined,
        };
        const { data } = await api.get("/stock/adjustments", { params });
        history.value = data.data.data || [];
        historyPagination.value = {
            current_page: data.data.meta.current_page,
            last_page: data.data.meta.last_page,
            total: data.data.meta.total,
            per_page: data.data.meta.per_page,
        };
    } catch (err) {
        toast.error("Gagal memuat history penyusutan");
    } finally {
        historyLoading.value = false;
    }
}

async function fetchExpiring(page = 1) {
    expiringLoading.value = true;
    try {
        const params = {
            page,
            per_page: expiringPagination.value.per_page,
            search: expiringSearch.value,
        };
        const { data } = await api.get("/stock/expiring", { params });
        expiring.value = data.data.data || [];
        expiringPagination.value = {
            current_page: data.data.meta.current_page,
            last_page: data.data.meta.last_page,
            total: data.data.meta.total,
            per_page: data.data.meta.per_page,
        };
    } catch (err) {
        toast.error("Gagal memuat daftar barang akan expired");
    } finally {
        expiringLoading.value = false;
    }
}

const debouncedSearch = debounce(() => {
    fetchItems(1);
}, 400);

watch(searchQuery, () => debouncedSearch());

const debouncedHistorySearch = debounce(() => {
    fetchHistory(1);
}, 400);

watch(historySearch, () => debouncedHistorySearch());
watch(historyReason, () => fetchHistory(1));
watch(historyStartDate, () => fetchHistory(1));
watch(historyEndDate, () => fetchHistory(1));

const debouncedExpiringSearch = debounce(() => {
    fetchExpiring(1);
}, 400);

watch(expiringSearch, () => debouncedExpiringSearch());

onMounted(() => {
    fetchItems(1);
    fetchHistory(1);
    fetchExpiring(1);
});

function selectItem(item) {
    selectedItem.value = item;
    form.value.qty = "";
}

function clearSelection() {
    selectedItem.value = null;
    form.value.qty = "";
    form.value.reason = "";
    form.value.tanggal = todayStr();
    form.value.keterangan = "";
}

async function submitForm() {
    if (!selectedItem.value) {
        return toast.error("Pilih katalog barang terlebih dahulu");
    }
    if (availableStock.value <= 0) {
        return toast.error("Stok kosong, tidak bisa input penyusutan");
    }
    if (!form.value.reason) {
        return toast.error("Pilih alasan penyusutan");
    }
    if (!form.value.tanggal) {
        return toast.error("Pilih tanggal penyusutan");
    }
    const qty = Number(form.value.qty || 0);
    if (!qty || qty <= 0) {
        return toast.error("Qty harus lebih dari 0");
    }
    if (qty > availableStock.value) {
        return toast.error(`Maksimal stok tersedia: ${formatNumber(availableStock.value)}`);
    }

    saving.value = true;
    try {
        await api.post("/stock/adjustments", {
            katalog_barang_id: selectedItem.value.id,
            tanggal: form.value.tanggal,
            reason: form.value.reason,
            qty,
            keterangan: form.value.keterangan || null,
        });
        toast.success("Penyusutan stok berhasil disimpan");
        await fetchItems(1);
        await fetchHistory(1);
        // refresh selected item stock
        const refreshed = items.value.find((i) => i.id === selectedItem.value.id);
        if (refreshed) {
            selectedItem.value = refreshed;
        }
        form.value.qty = "";
        form.value.tanggal = todayStr();
        form.value.keterangan = "";
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal menyimpan penyusutan");
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Penyusutan Stok</h1>
                <p class="mt-1 text-sm text-slate-400">
                    Catat stok keluar karena reject supplier, expired, atau hilang/susut
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
            <section class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-sm font-bold uppercase text-slate-500">Pilih Katalog Barang</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari kode atau nama barang..."
                            class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        />
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <div v-if="isLoading" class="py-8 text-center text-slate-500">
                        Memuat daftar barang...
                    </div>

                    <div v-else class="max-h-[420px] overflow-auto border rounded-lg border-slate-200">
                        <div v-if="items.length === 0" class="py-10 text-center text-slate-500">
                            Tidak ada data barang.
                        </div>
                        <button
                            v-for="item in items"
                            :key="item.id"
                            type="button"
                            @click="selectItem(item)"
                            class="w-full px-4 py-3 text-left border-b last:border-b-0 border-slate-200 hover:bg-slate-50 transition flex items-center justify-between"
                            :class="selectedItem?.id === item.id ? 'bg-emerald-50' : ''"
                        >
                            <div>
                                <div class="font-mono text-xs font-semibold text-blue-600">
                                    {{ item.kode_barang }}
                                </div>
                                <div class="text-sm font-medium text-slate-700">
                                    {{ item.nama_barang }}
                                </div>
                                <div class="text-xs text-slate-400">
                                    {{ item.category?.nama || "-" }} • {{ item.unit?.nama || "-" }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs uppercase text-slate-400">Stok</div>
                                <div
                                    class="text-sm font-bold"
                                    :class="Number(item.total_stok) <= 0 ? 'text-rose-600' : 'text-emerald-600'"
                                >
                                    {{ formatNumber(item.total_stok, 3) }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase text-slate-500">Form Penyusutan</h2>
                    <button
                        v-if="selectedItem"
                        type="button"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-700"
                        @click="clearSelection"
                    >
                        Reset
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase text-slate-400">Barang Dipilih</div>
                        <div v-if="selectedItem" class="mt-1">
                            <div class="font-mono text-xs font-semibold text-blue-600">
                                {{ selectedItem.kode_barang }}
                            </div>
                            <div class="text-sm font-semibold text-slate-800">
                                {{ selectedItem.nama_barang }}
                            </div>
                            <div class="text-xs text-slate-500">
                                Stok tersedia: <span class="font-semibold">{{ formatNumber(availableStock, 3) }}</span>
                                {{ selectedItem.unit?.nama || "" }}
                            </div>
                        </div>
                        <div v-else class="mt-1 text-sm text-slate-500">Belum ada barang dipilih</div>
                    </div>

                    <form class="space-y-4" @submit.prevent="submitForm">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-slate-700">Alasan *</label>
                            <select
                                v-model="form.reason"
                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white"
                                required
                            >
                                <option value="" disabled>Pilih alasan</option>
                                <option v-for="reason in reasons" :key="reason" :value="reason">
                                    {{ reason }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-slate-700">Qty *</label>
                            <input
                                v-model="form.qty"
                                type="number"
                                min="0"
                                step="0.001"
                                :max="availableStock"
                                :disabled="!selectedItem || availableStock <= 0"
                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none transition disabled:bg-slate-100 disabled:cursor-not-allowed"
                                placeholder="Masukkan qty penyusutan"
                                required
                            />
                            <p v-if="selectedItem && availableStock <= 0" class="mt-1 text-xs text-rose-600">
                                Stok kosong, tidak bisa input qty.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-slate-700">Tanggal *</label>
                            <input
                                v-model="form.tanggal"
                                type="date"
                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                required
                            />
                            <p class="mt-1 text-xs text-slate-400">Tanggal saat input penyusutan</p>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-slate-700">Keterangan</label>
                            <textarea
                                v-model="form.keterangan"
                                rows="3"
                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                placeholder="Isi keterangan (opsional)"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="saving || !selectedItem || availableStock <= 0"
                            class="w-full px-4 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ saving ? "Menyimpan..." : "Simpan Penyusutan" }}
                        </button>
                    </form>
                </div>
            </section>
        </div>

        <section class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
            <div class="flex flex-col gap-3 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase text-slate-500">History Penyusutan</h2>
                    <p class="text-xs text-slate-400">Riwayat transaksi penyusutan stok</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Alasan</label>
                        <select
                            v-model="historyReason"
                            class="w-44 px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white"
                        >
                            <option value="">Semua</option>
                            <option v-for="reason in reasons" :key="reason" :value="reason">
                                {{ reason }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Search</label>
                        <input
                            v-model="historySearch"
                            type="text"
                            placeholder="Cari kode / nama..."
                            class="w-64 px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Tanggal Mulai</label>
                        <input
                            v-model="historyStartDate"
                            type="date"
                            class="w-40 px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Tanggal Akhir</label>
                        <input
                            v-model="historyEndDate"
                            type="date"
                            class="w-40 px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        />
                    </div>
                </div>
            </div>

            <div v-if="historyLoading" class="py-10 text-center text-slate-500">
                Memuat history...
            </div>
            <div v-else class="table-container">
                <table class="table-fixed-layout table-wide">
                    <thead class="table-header">
                        <tr>
                            <th class="w-12 text-center">No</th>
                            <th class="text-left">Kode</th>
                            <th class="text-left">Nama Barang</th>
                            <th class="text-left">Alasan</th>
                            <th class="text-right">Qty</th>
                            <th class="text-left">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-if="history.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500 italic">
                                Belum ada data penyusutan.
                            </td>
                        </tr>
                        <tr v-for="(row, index) in history" :key="row.id" class="table-row hover:bg-slate-50">
                            <td class="table-cell text-center text-slate-500 font-medium">
                                {{ (historyPagination.current_page - 1) * historyPagination.per_page + index + 1 }}
                            </td>
                            <td class="table-cell font-mono text-xs text-blue-600 font-semibold">
                                {{ row.katalog_barang?.kode_barang || "-" }}
                            </td>
                            <td class="table-cell font-medium text-slate-700">
                                {{ row.katalog_barang?.nama_barang || "-" }}
                            </td>
                            <td class="table-cell text-slate-600">
                                {{ row.reason }}
                            </td>
                            <td class="table-cell text-right font-bold text-rose-600">
                                {{ formatNumber(row.qty, 3) }}
                                <div class="mt-1 text-[10px] text-slate-400 font-normal">
                                    {{ formatDate(row.tanggal) }}
                                </div>
                            </td>
                            <td class="table-cell text-slate-600">
                                {{ row.keterangan || "-" }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="historyPagination.last_page > 1"
                class="flex flex-col items-start justify-between gap-3 px-6 py-3 border-t sm:flex-row sm:items-center border-slate-200 bg-slate-50"
            >
                <div class="text-sm text-slate-500">
                    Menampilkan
                    <span class="font-medium">{{ (historyPagination.current_page - 1) * historyPagination.per_page + 1 }}</span>
                    s/d
                    <span class="font-medium">{{ Math.min(historyPagination.current_page * historyPagination.per_page, historyPagination.total) }}</span>
                    dari
                    <span class="font-medium">{{ historyPagination.total }}</span>
                    hasil
                </div>
                <div class="flex gap-2">
                    <button
                        @click="fetchHistory(historyPagination.current_page - 1)"
                        :disabled="historyPagination.current_page === 1"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >Sebelumnya</button>
                    <button
                        @click="fetchHistory(historyPagination.current_page + 1)"
                        :disabled="historyPagination.current_page === historyPagination.last_page"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >Selanjutnya</button>
                </div>
            </div>
        </section>

        <section class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
            <div class="flex flex-col gap-3 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-sm font-bold uppercase text-slate-500">Barang Akan Expired</h2>
                    <p class="text-xs text-slate-400">Daftar batch dengan tanggal expired mendekat</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Search</label>
                        <input
                            v-model="expiringSearch"
                            type="text"
                            placeholder="Cari kode / nama..."
                            class="w-64 px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        />
                    </div>
                </div>
            </div>

            <div v-if="expiringLoading" class="py-10 text-center text-slate-500">
                Memuat daftar expired...
            </div>
            <div v-else class="table-container">
                <table class="table-fixed-layout table-wide">
                    <thead class="table-header">
                        <tr>
                            <th class="w-12 text-center">No</th>
                            <th class="text-left">Kode</th>
                            <th class="text-left">Nama Barang</th>
                            <th class="text-left">Expired</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Sisa Hari</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-if="expiring.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500 italic">
                                Belum ada barang yang akan expired.
                            </td>
                        </tr>
                        <tr v-for="(row, index) in expiring" :key="row.id" class="table-row hover:bg-slate-50">
                            <td class="table-cell text-center text-slate-500 font-medium">
                                {{ (expiringPagination.current_page - 1) * expiringPagination.per_page + index + 1 }}
                            </td>
                            <td class="table-cell font-mono text-xs text-blue-600 font-semibold">
                                {{ row.katalog_barang?.kode_barang || "-" }}
                            </td>
                            <td class="table-cell font-medium text-slate-700">
                                {{ row.katalog_barang?.nama_barang || "-" }}
                            </td>
                            <td class="table-cell text-slate-600">
                                {{ formatDate(row.expired_at) }}
                            </td>
                            <td class="table-cell text-right font-semibold text-amber-600">
                                {{ formatNumber(row.qty_sisa, 3) }}
                            </td>
                            <td class="table-cell text-right">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                    :class="row.days_left !== null && row.days_left <= 7 ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-700'"
                                >
                                    {{ row.days_left ?? "-" }} hari
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="expiringPagination.last_page > 1"
                class="flex flex-col items-start justify-between gap-3 px-6 py-3 border-t sm:flex-row sm:items-center border-slate-200 bg-slate-50"
            >
                <div class="text-sm text-slate-500">
                    Menampilkan
                    <span class="font-medium">{{ (expiringPagination.current_page - 1) * expiringPagination.per_page + 1 }}</span>
                    s/d
                    <span class="font-medium">{{ Math.min(expiringPagination.current_page * expiringPagination.per_page, expiringPagination.total) }}</span>
                    dari
                    <span class="font-medium">{{ expiringPagination.total }}</span>
                    hasil
                </div>
                <div class="flex gap-2">
                    <button
                        @click="fetchExpiring(expiringPagination.current_page - 1)"
                        :disabled="expiringPagination.current_page === 1"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >Sebelumnya</button>
                    <button
                        @click="fetchExpiring(expiringPagination.current_page + 1)"
                        :disabled="expiringPagination.current_page === expiringPagination.last_page"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >Selanjutnya</button>
                </div>
            </div>
        </section>
    </div>
</template>
