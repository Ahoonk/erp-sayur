<script setup>
import { ref, watch, onMounted, computed } from "vue";
import DateInput from "../../components/DateInput.vue";
import CurrencyInput from "../../components/CurrencyInput.vue";
import api from "../../api";
import { useToast } from "../../composables/useToast";
import debounce from "lodash-es/debounce";
import { useAuthStore } from "../../stores/auth";
import ConfirmDialog from "../../components/ConfirmDialog.vue";
import { useRoute } from "vue-router";

const toast = useToast();
const authStore = useAuthStore();
const route = useRoute();
const isServiceMode = computed(() => route.name === "service-transaction-list");
const isKasir = computed(() => authStore.isKasir);
const kasirName = computed(() => authStore.user?.name || "Kasir");
const transactionType = computed(() =>
    isServiceMode.value ? "service" : "penjualan",
);

const sales = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const searchQuery = ref("");
const isLoading = ref(false);
const perPage = ref(10);
const stats = ref({ today: 0, month: 0, year: 0, total: 0 });

const detailModalOpen = ref(false);
const selectedSale = ref(null);

const filters = ref({
    start_date: "",
    end_date: "",
    user_id: "",
    sales_rep_id: "",
});

const users = ref([]);
const salesReps = ref([]);

onMounted(() => {
    if (isKasir.value && authStore.user?.id) {
        filters.value.user_id = authStore.user.id;
    }
    fetchSales();
    fetchStats();
    loadFilterOptions();
});

const loadFilterOptions = async () => {
    try {
        const requests = [];
        if (!isKasir.value) {
            requests.push(api.get("/user/all?role=kasir"));
        }
        if (!isServiceMode.value) {
            requests.push(api.get("/sales-reps/all"));
        }
        const responses = await Promise.all(requests);
        if (!isKasir.value) {
            users.value = responses[0]?.data?.data || [];
            salesReps.value = responses[1]?.data?.data || [];
        } else {
            users.value = [];
            salesReps.value = responses[0]?.data?.data || [];
        }
    } catch (e) {
        console.error("Gagal memuat opsi filter", e);
    }
};

const fetchStats = async () => {
    try {
        const res = await api.get("/sales/stats", {
            params: { tipe: transactionType.value },
        });
        stats.value = res.data.data;
    } catch (e) {
        console.error("Gagal memuat stats", e);
    }
};

const fetchSales = async (page = 1) => {
    isLoading.value = true;
    try {
        const res = await api.get("/sales", {
            params: {
                page,
                per_page: perPage.value,
                search: searchQuery.value,
                start_date: filters.value.start_date,
                end_date: filters.value.end_date,
                user_id: isKasir.value
                    ? authStore.user?.id
                    : filters.value.user_id,
                sales_rep_id: isServiceMode.value
                    ? undefined
                    : filters.value.sales_rep_id,
                tipe: transactionType.value,
            },
        });
        sales.value = res.data.data.data;
        pagination.value = {
            current_page: res.data.data.current_page,
            last_page: res.data.data.last_page,
            per_page: res.data.data.per_page,
            total: res.data.data.total,
        };
    } catch (e) {
        toast.error("Gagal memuat data penjualan");
    } finally {
        isLoading.value = false;
    }
};

const throttledSearch = debounce(() => {
    fetchSales(1);
}, 500);

watch(searchQuery, () => {
    throttledSearch();
});

watch(transactionType, () => {
    filters.value.sales_rep_id = "";
    fetchSales(1);
    fetchStats();
    loadFilterOptions();
});

watch(perPage, () => {
    fetchSales(1);
});

watch(
    () => filters.value,
    () => {
        // Validation: end date shouldn't be before start date
        if (
            filters.value.start_date &&
            filters.value.end_date &&
            filters.value.end_date < filters.value.start_date
        ) {
            toast.error(
                "Tanggal akhir tidak boleh lebih awal dari tanggal awal",
            );
            filters.value.end_date = "";
            return;
        }
        fetchSales(1);
    },
    { deep: true },
);

const showDeleteDialog = ref(false);
const deleteLoading = ref(false);
const saleToDelete = ref(null);

const confirmDelete = (id) => {
    saleToDelete.value = id;
    showDeleteDialog.value = true;
};

const deleteSale = async () => {
    if (!saleToDelete.value) return;
    deleteLoading.value = true;
    try {
        await api.delete(`/sales/${saleToDelete.value}`);
        toast.success(
            isServiceMode.value
                ? "Transaksi service dihapus"
                : "Transaksi dihapus dan stok dikembalikan",
        );
        showDeleteDialog.value = false;
        fetchSales(pagination.value.current_page);
        fetchStats(); // Update stats cards immediately
    } catch (e) {
        toast.error(e.response?.data?.message || "Gagal menghapus transaksi");
    } finally {
        deleteLoading.value = false;
    }
};

const detailLoading = ref(false);
const viewDetails = async (sale) => {
    selectedSale.value = sale;
    detailModalOpen.value = true;
    detailLoading.value = true;
    try {
        const res = await api.get(`/sales/${sale.id}`);
        selectedSale.value = res.data.data;
    } catch (e) {
        toast.error("Gagal memuat detail penjualan");
    } finally {
        detailLoading.value = false;
    }
};

const closeDetailModal = () => {
    detailModalOpen.value = false;
    selectedSale.value = null;
    detailLoading.value = false;
};

function formatCurrency(val) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
}

function formatDate(dateStr) {
    if (!dateStr) return "-";
    // Usually YYYY-MM-DD from API, change to DD-MM-YYYY
    const [y, m, d] = dateStr.split("-");
    const year = y && y.length > 4 ? y.substring(0, 4) : y;
    return `${d}-${m}-${year}`;
}

function getProductIdentifierLines(product) {
    if (!product) return [`IMEI 1: -`, `IMEI 2: -`];

    const imei1 = product.imei1 ? String(product.imei1).trim() : "-";
    const imei2 = product.imei2 ? String(product.imei2).trim() : "-";

    return [`IMEI 1: ${imei1}`, `IMEI 2: ${imei2}`];
}

// Edit HPP modal
const editModalData = ref({
    show: false,
    item: null,
    saleId: null,
    oldValueDisplay: "",
    newValue: 0,
});
const savingEditModal = ref(false);

function openEditModalItem(item) {
    editModalData.value.item = item;
    editModalData.value.saleId = selectedSale.value?.id;
    editModalData.value.oldValueDisplay = formatCurrency(item.hpp_total || 0);
    editModalData.value.newValue = Number(item.hpp_total || 0);
    editModalData.value.show = true;
}

async function saveEditModal() {
    if (!editModalData.value.item) return;
    const item = editModalData.value.item;
    savingEditModal.value = true;
    try {
        await api.put(`/sales/${editModalData.value.saleId}/items/${item.id}`, {
            hpp_total: editModalData.value.newValue,
        });
        item.hpp_total = editModalData.value.newValue;
        editModalData.value.show = false;
        toast.success("Harga modal berhasil diubah");
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal mengubah harga modal");
    } finally {
        savingEditModal.value = false;
    }
}

function servicePartsSummary(sale) {
    const parts = sale?.service_order?.parts || [];
    if (!parts.length) return "-";
    return parts
        .slice(0, 2)
        .map((p) => `${p.nama_part} (${p.qty})`)
        .join(", ");
}
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-2xl font-bold text-slate-800">
                {{
                    isServiceMode
                        ? "Transaksi Service"
                        : isKasir
                          ? `Data Penjualan ${kasirName}`
                          : "Data Penjualan"
                }}
            </h1>
            <router-link
                v-if="!isServiceMode"
                to="/dashboard/pos"
                class="px-4 py-2 font-medium text-white transition bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700"
            >
                + Input Penjualan (POS)
            </router-link>
        </div>

        <!-- Dashboard Stats Cards -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div
                class="relative p-6 overflow-hidden text-white shadow-sm bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl group"
            >
                <div class="relative z-10">
                    <div
                        class="mb-1 text-sm font-medium tracking-wider uppercase text-rose-100"
                    >
                        {{
                            isServiceMode
                                ? "Service Hari Ini"
                                : "Penjualan Hari Ini"
                        }}
                    </div>
                    <div class="text-2xl font-black">
                        {{ formatCurrency(stats.today) }}
                    </div>
                </div>
                <svg
                    class="absolute w-24 h-24 transition-transform -right-4 -bottom-4 text-white/10 group-hover:scale-110"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.75 16.44c-.46.32-1.01.56-1.65.71v.85h-1v-.81c-.6-.12-1.12-.35-1.57-.7l.38-.82c.43.3.9.52 1.41.66.44.11.87.16 1.31.14.49-.02.93-.15 1.33-.37.4-.23.6-.58.6-1.05 0-.4-.14-.72-.42-.96-.28-.24-.62-.43-1.04-.59-.42-.16-1-.37-1.74-.63-.74-.26-1.3-.59-1.69-1-.39-.41-.58-.93-.58-1.56 0-.61.18-1.14.53-1.59.35-.45.83-.81 1.44-1.08.4-.18.88-.3 1.43-.36V6h1v1.17c.56.09 1.05.29 1.48.59l-.36.83c-.43-.28-.88-.47-1.34-.58-.46-.11-.92-.15-1.36-.13-.44.02-.85.15-1.22.38-.37.23-.55.57-.55 1.02 0 .39.14.71.42.96.28.25.66.46 1.13.63.47.17 1.04.41 1.71.72.67.31 1.19.66 1.54 1.05s.53.94.53 1.63c0 .67-.2 1.25-.61 1.75z"
                    />
                </svg>
            </div>
            <div
                class="relative p-6 overflow-hidden text-white shadow-sm bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl group"
            >
                <div class="relative z-10">
                    <div
                        class="mb-1 text-sm font-medium tracking-wider text-blue-100 uppercase"
                    >
                        {{
                            isServiceMode
                                ? "Service Bulan Ini"
                                : "Penjualan Bulan Ini"
                        }}
                    </div>
                    <div class="text-2xl font-black">
                        {{ formatCurrency(stats.month) }}
                    </div>
                </div>
                <svg
                    class="absolute w-24 h-24 transition-transform -right-4 -bottom-4 text-white/10 group-hover:scale-110"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"
                    />
                </svg>
            </div>
            <div
                class="relative p-6 overflow-hidden text-white shadow-sm bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl group"
            >
                <div class="relative z-10">
                    <div
                        class="mb-1 text-sm font-medium tracking-wider uppercase text-amber-100"
                    >
                        {{
                            isServiceMode
                                ? "Service Tahun Ini"
                                : "Penjualan Tahun Ini"
                        }}
                    </div>
                    <div class="text-2xl font-black">
                        {{ formatCurrency(stats.year) }}
                    </div>
                </div>
                <svg
                    class="absolute w-24 h-24 transition-transform -right-4 -bottom-4 text-white/10 group-hover:scale-110"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm3.3 14.71L11 12.41V7h2v4.59l3.71 3.71-1.42 1.41z"
                    />
                </svg>
            </div>
            <div
                class="relative p-6 overflow-hidden text-white border shadow-sm bg-slate-800 rounded-2xl group border-slate-700"
            >
                <div class="relative z-10">
                    <div
                        class="mb-1 text-sm font-medium tracking-wider uppercase text-slate-400"
                    >
                        {{
                            isServiceMode
                                ? "Total Seluruh Service"
                                : "Total Seluruh Penjualan"
                        }}
                    </div>
                    <div class="text-2xl font-black">
                        {{ formatCurrency(stats.total) }}
                    </div>
                </div>
                <svg
                    class="absolute w-24 h-24 transition-transform -right-4 -bottom-4 text-white/5 group-hover:scale-110"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"
                    />
                </svg>
            </div>
        </div>

        <div
            class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200"
        >
            <!-- Header Actions -->
            <div
                class="flex flex-col items-start justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row md:items-center"
            >
                <!-- Left Side: Advanced Filters -->
                <div
                    :class="[
                        'grid w-full gap-3 lg:flex md:w-auto',
                        isServiceMode
                            ? 'grid-cols-2 md:grid-cols-4'
                            : 'grid-cols-2 md:grid-cols-5',
                    ]"
                >
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Mulai</label
                        >
                        <DateInput
                            
                            v-model="filters.start_date"
                            class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Sampai</label
                        >
                        <DateInput
                            
                            v-model="filters.end_date"
                            :min="filters.start_date"
                            class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white disabled:bg-slate-100 disabled:cursor-not-allowed"
                        />
                    </div>
                    <div
                        v-if="!isKasir"
                        class="flex flex-col gap-1 col-span-2 md:col-span-1"
                    >
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Kasir</label
                        >
                        <div class="relative">
                            <select
                                v-model="filters.user_id"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[140px] pr-8"
                            >
                                <option value="">Semua Kasir</option>
                                <option
                                    v-for="u in users"
                                    :key="u.id"
                                    :value="u.id"
                                >
                                    {{ u.name }}
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
                    <div class="flex flex-col gap-1 col-span-2 md:col-span-1" v-if="!isServiceMode">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Sales</label
                        >
                        <div class="relative">
                            <select
                                v-model="filters.sales_rep_id"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[140px] pr-8"
                            >
                                <option value="">Semua Sales</option>
                                <option
                                    v-for="s in salesReps"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    {{ s.nama }}
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
                    <div class="flex flex-col gap-1 col-span-2 md:col-span-1 lg:justify-end">
                        <label class="text-[10px] font-bold text-slate-400 uppercase invisible hidden md:block">Reset</label>
                        <button
                            @click="
                                filters = {
                                    start_date: '',
                                    end_date: '',
                                    user_id: isKasir ? authStore.user?.id : '',
                                    sales_rep_id: '',
                                }
                            "
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

                <!-- Right Side: Per Page & Search Combined -->
                <div class="flex flex-row items-end w-full gap-2 md:w-auto">
                    <!-- Per Page -->
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

                    <!-- Simple Search -->
                    <div class="flex flex-col gap-1 grow md:grow-0">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Search</label
                        >
                        <div class="relative">
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Cari invoice/pelanggan..."
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

            <!-- Table Section -->
            <div class="table-container">
                <table class="table-fixed-layout table-wide">
                    <thead class="table-header">
                        <tr>
                            <th class="w-16 text-center">No</th>
                            <th class="w-40">Nomor</th>
                            <th class="w-32">Tanggal</th>
                            <th class="w-48">Pelanggan</th>
                            <th class="w-32">Kasir</th>
                            <th v-if="isServiceMode" class="w-32">Teknisi</th>
                            <th v-else class="w-32">Sales</th>
                            <th v-if="isServiceMode" class="w-56">
                                Sparepart Digunakan
                            </th>
                            <th class="w-40 text-right">Sub Total</th>
                            <th class="w-20 text-center">Qty</th>
                            <th class="w-40 text-right">Uang Masuk</th>
                            <th class="table-col-action-h">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-if="isLoading">
                            <td
                                :colspan="isServiceMode ? 11 : 10"
                                class="px-6 py-12 text-center text-slate-500"
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
                        <tr v-else-if="sales.length === 0">
                            <td
                                :colspan="isServiceMode ? 11 : 10"
                                class="px-6 py-12 italic text-center text-slate-500"
                            >
                                {{
                                    isServiceMode
                                        ? "Tidak ada data transaksi service ditemukan."
                                        : "Tidak ada data penjualan ditemukan."
                                }}
                            </td>
                        </tr>
                        <tr
                            v-for="(s, index) in sales"
                            :key="s.id"
                            class="table-row group"
                        >
                            <td
                                class="table-cell font-medium text-center text-slate-500"
                            >
                                {{
                                    (pagination.current_page - 1) *
                                        pagination.per_page +
                                    index +
                                    1
                                }}
                            </td>
                            <td class="table-cell font-bold text-blue-600">
                                {{ s.no_invoice }}
                            </td>
                            <td class="table-cell font-semibold text-slate-500">
                                {{ formatDate(s.tanggal) }}
                            </td>
                            <td
                                class="table-cell font-bold uppercase text-slate-500"
                            >
                                {{ s.pelanggan || "Umum" }}
                            </td>
                            <td
                                class="table-cell text-slate-500 uppercase text-[10px] font-bold"
                            >
                                {{ s.user?.name || "-" }}
                            </td>
                            <td
                                v-if="isServiceMode"
                                class="table-cell text-[12px] font-bold text-slate-500 uppercase"
                            >
                                {{ s.service_order?.technician?.nama || "-" }}
                            </td>
                            <td
                                v-if="!isServiceMode"
                                class="table-cell text-slate-500 uppercase text-[10px] font-bold"
                            >
                                {{ s.sales_rep?.nama || "-" }}
                            </td>
                            <td
                                v-if="isServiceMode"
                                class="table-cell text-slate-600 text-[11px] font-semibold"
                            >
                                {{ servicePartsSummary(s) }}
                            </td>
                            <td
                                class="table-cell font-bold text-right text-slate-600"
                            >
                                {{ formatCurrency(s.subtotal) }}
                            </td>
                            <td
                                class="table-cell font-black text-center text-slate-700"
                            >
                                {{ s.items_sum_qty || 0 }}
                            </td>
                            <td
                                class="table-cell font-bold text-right"
                                :class="
                                    Number(s.grand_total || 0) < 0
                                        ? 'text-red-600'
                                        : 'text-green-600'
                                "
                            >
                                {{ formatCurrency(s.grand_total) }}
                            </td>
                            <td class="table-col-action">
                                <div class="table-actions">
                                    <button
                                        @click="viewDetails(s)"
                                        class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition"
                                        title="Detail Info"
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
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </button>
                                    <router-link
                                        v-if="!isServiceMode"
                                        :to="`/dashboard/pos?edit_id=${s.id}`"
                                        class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition"
                                        title="Edit Transaksi"
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
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </router-link>
                                    <router-link
                                        :to="`/dashboard/pos/${s.id}/invoice?from=/dashboard/sales`"
                                        class="p-1.5 text-purple-500 hover:bg-purple-50 rounded-lg transition"
                                        title="Print Invoice"
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
                                    </router-link>
                                    <button
                                        v-if="
                                            authStore.isSuperAdmin ||
                                            authStore.isAdmin
                                        "
                                        @click="confirmDelete(s.id)"
                                        class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                        title="Hapus"
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
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
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
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-6 py-3 border-t border-slate-200 bg-slate-50"
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
                            pagination.total,
                        )
                    }}</span>
                    dari
                    <span class="font-medium">{{ pagination.total }}</span>
                    hasil
                </div>
                <div class="flex gap-2">
                    <button
                        @click="fetchSales(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Sebelumnya
                    </button>
                    <button
                        @click="fetchSales(pagination.current_page + 1)"
                        :disabled="
                            pagination.current_page === pagination.last_page
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
            v-if="detailModalOpen && selectedSale"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="closeDetailModal"
            ></div>
            <div
                class="bg-white rounded-2xl shadow-xl w-full max-w-6xl max-h-[90vh] flex flex-col relative z-10 overflow-hidden"
            >
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50"
                >
                    <h3 class="text-lg font-bold text-slate-800">
                        {{
                            isServiceMode
                                ? "Detail Transaksi Service"
                                : "Detail Transaksi Penjualan"
                        }}
                    </h3>
                    <div class="flex items-center gap-3">
                        <router-link
                            :to="`/dashboard/pos/${selectedSale.id}/invoice?from=/dashboard/sales`"
                            class="px-4 py-1.5 bg-slate-800 text-white text-sm font-bold rounded-lg shadow hover:bg-slate-700 transition flex items-center gap-2"
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
                                ></path>
                            </svg>
                            Print
                        </router-link>
                        <button
                            @click="closeDetailModal"
                            class="text-slate-400 hover:text-slate-600"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
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
                </div>

                <div class="w-full p-6 overflow-y-auto">
                    <!-- Info Meta -->
                    <div
                        class="grid grid-cols-2 gap-6 mb-8 text-sm md:grid-cols-4"
                    >
                        <div
                            class="p-4 border bg-slate-50 rounded-xl border-slate-100"
                        >
                            <span
                                class="block mb-1 text-xs font-medium tracking-wider uppercase text-slate-500"
                                >No. Invoice</span
                            >
                            <span class="block font-bold text-blue-600">{{
                                selectedSale.no_invoice
                            }}</span>
                        </div>
                        <div
                            class="p-4 border bg-slate-50 rounded-xl border-slate-100"
                        >
                            <span
                                class="block mb-1 text-xs font-medium tracking-wider uppercase text-slate-500"
                                >Tanggal</span
                            >
                            <span class="block font-bold text-slate-800">{{
                                formatDate(selectedSale.tanggal)
                            }}</span>
                        </div>
                        <div
                            class="p-4 border bg-slate-50 rounded-xl border-slate-100"
                        >
                            <span
                                class="block mb-1 text-xs font-medium tracking-wider uppercase text-slate-500"
                                >{{
                                    isServiceMode ? "No. Service" : "Pelanggan"
                                }}</span
                            >
                            <span class="block font-bold text-slate-800">{{
                                isServiceMode
                                    ? selectedSale.service_order?.no_service ||
                                      "-"
                                    : selectedSale.pelanggan || "Umum"
                            }}</span>
                        </div>
                        <div
                            class="p-4 border bg-slate-50 rounded-xl border-slate-100"
                        >
                            <span
                                class="block mb-1 text-xs font-medium tracking-wider uppercase text-slate-500"
                                >{{
                                    isServiceMode ? "Kasir" : "Kasir / Sales"
                                }}</span
                            >
                            <span class="block font-bold text-slate-800"
                                >{{ selectedSale.user?.name }}
                                <span
                                    v-if="
                                        !isServiceMode && selectedSale.sales_rep
                                    "
                                    >/ {{ selectedSale.sales_rep.nama }}</span
                                ></span
                            >
                        </div>
                        <div
                            v-if="
                                isServiceMode &&
                                selectedSale.service_order?.technician
                            "
                            class="p-4 border border-indigo-100 bg-indigo-50 rounded-xl"
                        >
                            <span
                                class="block mb-1 text-xs font-medium tracking-wider text-indigo-500 uppercase"
                            >
                                Teknisi
                            </span>
                            <span class="block font-bold text-indigo-700"
                                >{{
                                    selectedSale.service_order.technician.nama
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Item Table -->
                    <h4 class="mb-3 ml-1 text-sm font-bold text-slate-800">
                        {{
                            isServiceMode ? "Daftar Sparepart" : "Daftar Produk"
                        }}
                    </h4>
                    <div
                        class="mb-8 overflow-hidden border border-slate-200 rounded-xl"
                    >
                        <table class="w-full text-sm text-left">
                            <thead
                                class="font-medium border-b bg-slate-50 text-slate-500 border-slate-200"
                            >
                                <tr>
                                    <th
                                        class="px-4 py-3 text-xs tracking-wider uppercase"
                                    >
                                        No
                                    </th>
                                    <th
                                        class="px-4 py-3 text-xs tracking-wider uppercase"
                                    >
                                        {{
                                            isServiceMode
                                                ? "Sparepart"
                                                : "Produk Info"
                                        }}
                                    </th>
                                    <th
                                        class="px-4 py-3 text-xs tracking-wider text-right uppercase"
                                    >
                                        Harga
                                    </th>
                                    <th
                                        class="px-4 py-3 text-xs tracking-wider text-center uppercase"
                                    >
                                        Qty
                                    </th>
                                    <th
                                        v-if="authStore.isSuperAdmin || authStore.isAdmin"
                                        class="px-4 py-3 text-xs tracking-wider text-right uppercase"
                                    >
                                        Modal
                                    </th>
                                    <th
                                        class="px-4 py-3 text-xs tracking-wider text-right uppercase"
                                    >
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="detailLoading">
                                    <td
                                        :colspan="(authStore.isSuperAdmin || authStore.isAdmin) ? 6 : 5"
                                        class="py-8 italic text-center text-slate-400"
                                    >
                                        <div
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <div
                                                class="w-4 h-4 border-2 border-blue-600 rounded-full border-t-transparent animate-spin"
                                            ></div>
                                            Memuat daftar produk...
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-else-if="
                                        !selectedSale.items ||
                                        selectedSale.items.length === 0
                                    "
                                >
                                    <td
                                        :colspan="(authStore.isSuperAdmin || authStore.isAdmin) ? 6 : 5"
                                        class="py-8 italic text-center text-slate-400"
                                    >
                                        {{
                                            isServiceMode
                                                ? "Tidak ada sparepart dalam transaksi service ini."
                                                : "Tidak ada produk dalam transaksi ini."
                                        }}
                                    </td>
                                </tr>
                                <tr
                                    v-else
                                    v-for="(item, idx) in selectedSale.items"
                                    :key="item.id"
                                    class="hover:bg-slate-50/50"
                                >
                                    <td class="px-4 py-3 align-top">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-bold text-slate-800">
                                            {{ item.product?.nama || "-" }}
                                        </div>
                                        <div
                                            v-if="
                                                !isServiceMode &&
                                                getProductIdentifierLines(
                                                    item.product,
                                                ).length > 0
                                            "
                                            class="mt-1 font-mono text-xs leading-relaxed text-slate-500"
                                        >
                                            <div
                                                v-for="(
                                                    line, lineIndex
                                                ) in getProductIdentifierLines(
                                                    item.product,
                                                )"
                                                :key="`identifier-${item.id}-${lineIndex}`"
                                            >
                                                {{ line }}
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right align-top whitespace-nowrap"
                                    >
                                        {{ formatCurrency(item.harga_satuan) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-bold text-center align-top"
                                    >
                                        {{ item.qty }}
                                    </td>
                                    <td
                                        v-if="authStore.isSuperAdmin || authStore.isAdmin"
                                        class="px-4 py-3 text-right align-top whitespace-nowrap"
                                    >
                                        <a
                                            href="javascript:void(0)"
                                            @click="openEditModalItem(item)"
                                            class="font-semibold text-amber-600 hover:text-amber-700 hover:underline underline-offset-2 cursor-pointer"
                                            title="Klik untuk ubah harga modal"
                                        >{{ formatCurrency(item.hpp_total) }}</a>
                                    </td>
                                    <td
                                        class="px-4 py-3 font-bold text-right text-blue-600 align-top whitespace-nowrap"
                                    >
                                        {{ formatCurrency(item.subtotal) }}
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        isServiceMode &&
                                        (selectedSale.service_order
                                            ?.biaya_jasa || 0) > 0
                                    "
                                    class="hover:bg-slate-50/50"
                                >
                                    <td class="px-4 py-3 align-top">
                                        {{
                                            (selectedSale.items?.length || 0) +
                                            1
                                        }}
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-bold text-slate-800">
                                            Biaya Jasa Service
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right align-top whitespace-nowrap"
                                    >
                                        {{
                                            formatCurrency(
                                                selectedSale.service_order
                                                    ?.biaya_jasa || 0,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-bold text-center align-top"
                                    >
                                        -
                                    </td>
                                    <td
                                        class="px-4 py-3 font-bold text-right text-blue-600 align-top whitespace-nowrap"
                                    >
                                        {{
                                            formatCurrency(
                                                selectedSale.service_order
                                                    ?.biaya_jasa || 0,
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Transaction Summary -->
                    <div class="flex justify-end pr-4">
                        <div class="w-full md:w-80">
                            <div
                                class="flex justify-between py-2 text-sm border-b border-slate-100"
                            >
                                <span class="font-semibold text-slate-600"
                                    >Sub Total</span
                                >
                                <span class="font-bold text-slate-800">{{
                                    formatCurrency(selectedSale.subtotal)
                                }}</span>
                            </div>
                            <div
                                v-if="isServiceMode"
                                class="flex justify-between py-2 text-sm border-b border-slate-100"
                            >
                                <span
                                    class="font-semibold opacity-75 text-slate-600"
                                    >Biaya Jasa</span
                                >
                                <span class="font-bold text-slate-700">{{
                                    formatCurrency(
                                        selectedSale.service_order
                                            ?.biaya_jasa || 0,
                                    )
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between py-2 text-sm border-b border-slate-100"
                            >
                                <span
                                    class="font-semibold opacity-75 text-slate-600"
                                    >Diskon</span
                                >
                                <span class="font-bold text-rose-500"
                                    >-
                                    {{
                                        formatCurrency(
                                            selectedSale.diskon_nominal,
                                        )
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between py-2 text-sm border-b border-slate-100"
                            >
                                <span class="font-semibold text-slate-600"
                                    >Pajak
                                    <span class="font-normal opacity-50"
                                        >({{
                                            selectedSale.tax_persen || 0
                                        }}%)</span
                                    ></span
                                >
                                <span class="font-bold text-slate-800"
                                    >+
                                    {{
                                        formatCurrency(selectedSale.tax_nominal)
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between py-3 mt-1 mb-3 border-b-2 border-slate-800"
                            >
                                <span
                                    class="text-base font-black tracking-wider uppercase text-slate-800"
                                    >Total Bayar</span
                                >
                                <span
                                    class="text-lg font-black text-blue-600"
                                    >{{
                                        formatCurrency(selectedSale.grand_total)
                                    }}</span
                                >
                            </div>

                            <div
                                class="p-3 mt-4 text-sm border border-blue-100 rounded-lg bg-blue-50/50"
                            >
                                <div class="flex justify-between mb-1">
                                    <span class="font-medium text-blue-800/70"
                                        >Metode (Tipe)</span
                                    >
                                    <span
                                        class="font-bold text-blue-900 uppercase"
                                        >{{
                                            selectedSale.metode_pembayaran ||
                                            "CASH"
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-if="
                                        selectedSale.metode_pembayaran ===
                                        'cash'
                                    "
                                    class="flex justify-between mb-1"
                                >
                                    <span class="font-medium text-blue-800/70"
                                        >Jumlah Bayar</span
                                    >
                                    <span class="font-bold text-emerald-600">{{
                                        formatCurrency(
                                            selectedSale.jumlah_bayar,
                                        )
                                    }}</span>
                                </div>
                                <div
                                    v-if="
                                        selectedSale.metode_pembayaran ===
                                        'cash'
                                    "
                                    class="flex justify-between"
                                >
                                    <span class="font-medium text-blue-800/70"
                                        >Kembalian</span
                                    >
                                    <span class="font-bold text-orange-500">{{
                                        formatCurrency(selectedSale.kembalian)
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Edit Harga Modal Popup -->
        <div
            v-if="editModalData.show"
            class="fixed inset-0 flex items-center justify-center bg-black/50 p-4"
            style="z-index: 9999"
            @click.self="editModalData.show = false"
        >
            <div class="w-full max-w-sm overflow-hidden bg-white rounded-xl shadow-2xl">
                <div class="flex items-center justify-between px-4 py-3 text-white bg-amber-600">
                    <h3 class="text-sm font-bold">Update Harga Modal</h3>
                    <button @click="editModalData.show = false" class="text-white hover:text-rose-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-5 py-5 flex flex-col gap-3">
                    <div class="text-sm text-slate-600">
                        <span class="font-semibold text-slate-800">{{ editModalData.item?.product?.nama }}</span>
                    </div>
                    <div class="text-xs text-slate-500">
                        Harga lama: <span class="font-bold text-amber-600">{{ editModalData.oldValueDisplay }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Harga Modal Baru</label>
                        <CurrencyInput v-model="editModalData.newValue" :allowThousands="true" />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100 bg-slate-50">
                    <button
                        @click="editModalData.show = false"
                        class="px-4 py-1.5 text-sm font-semibold text-slate-600 bg-slate-200 hover:bg-slate-300 rounded transition"
                    >Batal</button>
                    <button
                        @click="saveEditModal"
                        :disabled="savingEditModal"
                        class="px-4 py-1.5 text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 rounded transition disabled:opacity-50"
                    >{{ savingEditModal ? "Menyimpan..." : "Simpan" }}</button>
                </div>
            </div>
        </div>

        <!-- Deletion Confirmation Dialog -->
        <ConfirmDialog
            :show="showDeleteDialog"
            :loading="deleteLoading"
            title="Hapus Transaksi"
            message="Apakah Anda yakin ingin menghapus transaksi ini? Stok barang akan dikembalikan secara otomatis."
            @confirm="deleteSale"
            @cancel="showDeleteDialog = false"
        />
    </div>
</template>




