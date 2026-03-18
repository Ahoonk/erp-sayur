<script setup>
import { computed, ref, onMounted, watch } from "vue";
import DateInput from "../../components/DateInput.vue";
import api from "../../api";
import ConfirmDialog from "../../components/ConfirmDialog.vue";
import { useRouter } from "vue-router";
import { useToast } from "../../composables/useToast";
import { useAuthStore } from "../../stores/auth";
import debounce from "lodash-es/debounce";

const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();
const canDelete = computed(() => !authStore.isKasir);

const purchases = ref([]);
const suppliers = ref([]);
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
    supplier_id: "",
});

const showDelete = ref(false);
const deleteId = ref(null);
const deleting = ref(false);

const editingPurchaseId = ref(null);
const editForm = ref({
    no_invoice: "",
    tanggal: "",
    supplier_id: "",
});
const savingEdit = ref(false);

function startEditPurchase(p) {
    editingPurchaseId.value = p.id;
    editForm.value = {
        no_invoice: p.no_invoice,
        tanggal: p.tanggal,
        supplier_id: p.supplier_id || (p.supplier ? p.supplier.id : ""),
    };
}

function cancelEditPurchase() {
    editingPurchaseId.value = null;
}

async function saveEditPurchase(id) {
    savingEdit.value = true;
    try {
        await api.put(`/purchases/${id}`, editForm.value);
        toast.success("Informasi invoice berhasil diupdate");
        editingPurchaseId.value = null;
        fetchPurchases(pagination.value.current_page);
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal mengupdate invoice");
    } finally {
        savingEdit.value = false;
    }
}

onMounted(() => {
    fetchPurchases();
    fetchSuppliers();
});

async function fetchSuppliers() {
    try {
        const { data } = await api.get("/suppliers/all");
        suppliers.value = data.data;
    } catch (e) {
        console.error("Gagal memuat supplier", e);
    }
}

async function fetchPurchases(page = 1) {
    isLoading.value = true;
    try {
        const { data } = await api.get("/purchases", {
            params: {
                page,
                per_page: perPage.value,
                search: searchQuery.value,
                start_date: filters.value.start_date,
                end_date: filters.value.end_date,
                supplier_id: filters.value.supplier_id,
            },
        });
        purchases.value = data.data.data;
        pagination.value = {
            current_page: data.data.current_page,
            last_page: data.data.last_page,
            total: data.data.total,
            per_page: data.data.per_page,
        };
    } catch (err) {
        toast.error("Gagal memuat data pembelian");
    } finally {
        isLoading.value = false;
    }
}

const throttledSearch = debounce(() => {
    fetchPurchases(1);
}, 500);

watch(searchQuery, () => throttledSearch());
watch(perPage, () => fetchPurchases(1));
watch(
    () => filters.value,
    () => {
        if (
            filters.value.start_date &&
            filters.value.end_date &&
            filters.value.end_date < filters.value.start_date
        ) {
            toast.error("Tanggal akhir tidak boleh lebih awal dari tanggal awal");
            filters.value.end_date = "";
            return;
        }
        fetchPurchases(1);
    },
    { deep: true }
);

function formatCurrency(val) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
}

function formatDate(dateStr) {
    if (!dateStr) return "-";
    const [y, m, d] = dateStr.split("-");
    return `${d}-${m}-${y}`;
}

function confirmDelete(id) {
    deleteId.value = id;
    showDelete.value = true;
}

async function doDelete() {
    deleting.value = true;
    try {
        await api.delete(`/purchases/${deleteId.value}`);
        toast.success("Transaksi berhasil dihapus dan stok diperbarui");
        showDelete.value = false;
        fetchPurchases(pagination.value.current_page);
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal menghapus");
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Pembelian (Invoice)</h1>
                <p class="mt-1 text-sm text-slate-400">Kelola data pembelian barang dari supplier</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <router-link
                    to="/dashboard/purchases/create"
                    class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:shadow-blue-500/25 transition-all flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Pembelian
                </router-link>
            </div>
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
                    <div class="flex flex-col col-span-2 gap-1 md:col-span-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Supplier</label>
                        <div class="relative">
                            <select
                                v-model="filters.supplier_id"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[160px] pr-8"
                            >
                                <option value="">Semua Supplier</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.nama }}</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col col-span-2 gap-1 md:col-span-1 lg:justify-end">
                        <label class="text-[10px] font-bold text-slate-400 uppercase invisible hidden md:block">Reset</label>
                        <button
                            @click="filters = { start_date: '', end_date: '', supplier_id: '' }"
                            class="flex items-center justify-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-500 hover:text-rose-500 hover:bg-rose-50 border border-slate-200 bg-white rounded-lg transition md:p-2 md:border-0 md:bg-transparent md:rounded-none md:justify-start"
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
                                placeholder="Cari invoice/supplier..."
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

            <!-- Table -->
            <div class="border-0 rounded-none shadow-none table-container">
                <table class="table-fixed-layout table-wide">
                    <thead class="table-header">
                        <tr>
                            <th class="w-16 text-center">No</th>
                            <th class="text-left">No. Invoice</th>
                            <th class="text-left">Tanggal</th>
                            <th class="text-left">Supplier</th>
                            <th class="text-center">Jumlah Item</th>
                            <th class="text-right">Total</th>
                            <th class="w-36 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-if="isLoading">
                            <td colspan="7" class="px-6 py-4 text-center text-slate-500">Memuat data...</td>
                        </tr>
                        <tr v-else-if="purchases.length === 0">
                            <td colspan="7" class="px-6 py-4 text-center text-slate-500">Tidak ada data pembelian ditemukan.</td>
                        </tr>
                        <tr
                            v-for="(p, index) in purchases"
                            :key="p.id"
                            class="table-row transition hover:bg-slate-50"
                        >
                            <td class="table-cell text-center text-slate-500">
                                {{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}
                            </td>
                            <td class="table-cell">
                                <input
                                    v-if="editingPurchaseId === p.id"
                                    v-model="editForm.no_invoice"
                                    type="text"
                                    class="w-full px-2 py-1 text-sm bg-white border border-blue-400 rounded outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <span
                                    v-else
                                    @click="startEditPurchase(p)"
                                    class="cursor-pointer border-b border-dashed border-blue-300 pb-0.5 hover:text-blue-700 transition inline-block text-blue-600"
                                    title="Klik untuk mengedit"
                                >{{ p.no_invoice }}</span>
                            </td>
                            <td class="table-cell">
                                <DateInput
                                    v-if="editingPurchaseId === p.id"
                                    v-model="editForm.tanggal"
                                    class="w-full px-2 py-1 text-sm bg-white border border-blue-400 rounded outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <span
                                    v-else
                                    @click="startEditPurchase(p)"
                                    class="cursor-pointer border-b border-dashed border-slate-300 pb-0.5 hover:text-slate-700 transition inline-block text-blue-600"
                                    title="Klik untuk mengedit"
                                >{{ formatDate(p.tanggal) }}</span>
                            </td>
                            <td class="table-cell font-medium">
                                <select
                                    v-if="editingPurchaseId === p.id"
                                    v-model="editForm.supplier_id"
                                    class="w-full px-2 py-1 text-sm bg-white border border-blue-400 rounded outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    <option value="" disabled>Pilih Supplier</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.nama }}</option>
                                </select>
                                <span
                                    v-else
                                    @click="startEditPurchase(p)"
                                    class="cursor-pointer border-b border-dashed border-slate-300 pb-0.5 hover:text-blue-600 transition inline-block text-blue-600"
                                    title="Klik untuk mengedit"
                                >{{ p.supplier?.nama || "-" }}</span>
                            </td>
                            <td class="table-cell text-center">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-600">
                                    {{ p.items_count || 0 }} item
                                </span>
                            </td>
                            <td class="table-cell font-bold text-right text-emerald-600">
                                {{ formatCurrency(p.total) }}
                            </td>
                            <td class="px-4 py-4 text-sm font-medium text-center">
                                <div v-if="editingPurchaseId === p.id" class="flex justify-center gap-1.5 pt-1">
                                    <button
                                        @click="saveEditPurchase(p.id)"
                                        :disabled="savingEdit"
                                        class="px-2.5 py-1 text-[11px] font-semibold text-white bg-blue-600 rounded shadow hover:bg-blue-700 disabled:opacity-50 transition"
                                    >{{ savingEdit ? "..." : "Simpan" }}</button>
                                    <button
                                        @click="cancelEditPurchase"
                                        class="px-2.5 py-1 text-[11px] font-semibold text-slate-600 bg-slate-100 rounded hover:bg-slate-200 border border-slate-200 transition"
                                    >Batal</button>
                                </div>
                                <div v-else class="flex justify-center">
                                    <router-link
                                        :to="`/dashboard/purchases/${p.id}`"
                                        class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg inline-flex"
                                        title="Detail"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </router-link>
                                    <router-link
                                        :to="`/dashboard/purchases/${p.id}/edit`"
                                        class="p-1.5 text-emerald-500 hover:bg-emerald-50 rounded-lg inline-flex"
                                        title="Edit Item"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </router-link>
                                    <button
                                        v-if="canDelete"
                                        @click="confirmDelete(p.id)"
                                        class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg inline-flex"
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
                        @click="fetchPurchases(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >Sebelumnya</button>
                    <button
                        @click="fetchPurchases(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >Selanjutnya</button>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="showDelete"
            :loading="deleting"
            @confirm="doDelete"
            @cancel="showDelete = false"
        />
    </div>
</template>
