<script setup>
import { computed, ref, onMounted, watch } from "vue";
import DateInput from "../../components/DateInput.vue";
import api from "../../api";
import ConfirmDialog from "../../components/ConfirmDialog.vue";
import CurrencyInput from "../../components/CurrencyInput.vue";
import { useRouter } from "vue-router";
import { useToast } from "../../composables/useToast";
import { useAuthStore } from "../../stores/auth";
import debounce from "lodash-es/debounce";

const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();
const canDelete = computed(() => !authStore.isKasir);

const items = ref([]);
const suppliers = ref([]);
const brands = ref([]);
const units = ref([]);
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
    brand_id: "",
    no_invoice: "",
});

const showDelete = ref(false);
const deleteId = ref(null);
const deleting = ref(false);

onMounted(() => {
    fetchItems();
    fetchSuppliers();
    fetchBrands();
    fetchUnits();
});

async function fetchUnits() {
    try {
        const { data } = await api.get("/units/all");
        units.value = data.data;
    } catch (e) {
        console.error("Gagal memuat satuan", e);
    }
}

async function fetchBrands() {
    try {
        const { data } = await api.get("/brands/all");
        brands.value = data.data;
    } catch (e) {
        console.error("Gagal memuat merk", e);
    }
}

async function fetchSuppliers() {
    try {
        const { data } = await api.get("/suppliers/all");
        suppliers.value = data.data;
    } catch (e) {
        console.error("Gagal memuat supplier", e);
    }
}

async function fetchItems(page = 1) {
    isLoading.value = true;
    try {
        const { data } = await api.get("/purchases/items", {
            params: {
                page,
                per_page: perPage.value,
                search: searchQuery.value,
                start_date: filters.value.start_date,
                end_date: filters.value.end_date,
                supplier_id: filters.value.supplier_id,
                brand_id: filters.value.brand_id,
                no_invoice: filters.value.no_invoice,
                status: "available",
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
        toast.error("Gagal memuat detail stok");
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
        if (
            filters.value.start_date &&
            filters.value.end_date &&
            filters.value.end_date < filters.value.start_date
        ) {
            toast.error(
                "Tanggal akhir tidak boleh lebih awal dari tanggal awal"
            );
            filters.value.end_date = "";
            return;
        }
        fetchItems(1);
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

function formatNumber(value) {
    return new Intl.NumberFormat("id-ID").format(Number(value || 0));
}

function getEffectiveSalePrice(item) {
    const basePrice = Number(item?.product?.harga_jual || 0);
    const transactionPrice = Number(item?.product?.harga_jual_transaksi || 0);

    if (item?.product?.is_sold && transactionPrice > 0) {
        return transactionPrice;
    }

    return basePrice;
}

async function printBarcode(purchaseId, itemId = null) {
    if (!purchaseId) return toast.error("ID Pembelian tidak tersedia");
    router.push({
        name: "purchase-barcode",
        params: { id: purchaseId },
        query: itemId ? { item_id: itemId } : {},
    });
}

function confirmDelete(id, purchaseId) {
    deleteId.value = { id, purchaseId };
    showDelete.value = true;
}

async function doDelete() {
    deleting.value = true;
    try {
        const { id, purchaseId } = deleteId.value;
        await api.delete(`/purchases/${purchaseId}/items/${id}`);
        toast.success("Item berhasil dihapus dari transaksi");
        showDelete.value = false;
        fetchItems(pagination.value.current_page);
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal menghapus item");
    } finally {
        deleting.value = false;
    }
}

// Edit Modal Logic
const editModal = ref({
    show: false,
    title: "",
    type: "", // 'unit', 'imei1', 'imei2', 'harga_beli', 'harga_jual'
    item: null,
    oldValueDisplay: "",
    newValue: "",
});
const savingEdit = ref(false);

function openEditModal(type, item) {
    editModal.value.type = type;
    editModal.value.item = item;
    editModal.value.show = true;

    if (type === "unit") {
        editModal.value.title = "Update Satuan";
        editModal.value.newValue = item.product?.unit_id || "";
    } else if (type === "imei1") {
        editModal.value.title = "Update IMEI 1";
        editModal.value.newValue = item.product?.imei1 || "";
    } else if (type === "imei2") {
        editModal.value.title = "Update IMEI 2";
        editModal.value.newValue = item.product?.imei2 || "";
    } else if (type === "harga_beli") {
        editModal.value.title = "Update Harga Modal";
        editModal.value.oldValueDisplay = formatCurrency(item.harga_beli);
        editModal.value.newValue = item.harga_beli || 0;
    } else if (type === "harga_jual") {
        editModal.value.title = "Update Harga Jual";
        editModal.value.oldValueDisplay = formatCurrency(
            item.product?.harga_jual
        );
        editModal.value.newValue = item.product?.harga_jual || 0;
    }
}

async function saveEdit() {
    if (!editModal.value.item) return;
    const item = editModal.value.item;
    const type = editModal.value.type;

    savingEdit.value = true;
    try {
        const payload = {};
        if (type === "unit") payload.unit_id = editModal.value.newValue;
        if (type === "imei1") payload.imei1 = editModal.value.newValue;
        if (type === "imei2") payload.imei2 = editModal.value.newValue;
        if (type === "harga_beli")
            payload.harga_beli = editModal.value.newValue;
        if (type === "harga_jual")
            payload.harga_jual = editModal.value.newValue;

        await api.put(
            `/purchases/${item.purchase_id}/items/${item.id}`,
            payload
        );
        toast.success("Berhasil diupdate!");
        editModal.value.show = false;
        fetchItems(pagination.value.current_page);
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal mengupdate");
    } finally {
        savingEdit.value = false;
    }
}

const copyAmountStok = computed(() => {
    return items.value.reduce(
        (acc, curr) => acc + (curr.qty || curr.product?.stok || 0),
        0
    );
});
const copyTotalModal = computed(() => {
    return items.value.reduce(
        (acc, curr) => acc + (curr.harga_beli || 0) * (curr.qty || 1),
        0
    );
});
const copyTotalJual = computed(() => {
    return items.value.reduce(
        (acc, curr) => acc + getEffectiveSalePrice(curr) * (curr.qty || 1),
        0
    );
});
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <div class="flex items-start justify-between">
            <div class="flex flex-col w-full">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">
                            List Semua Barang
                        </h1>
                        <p class="mt-1 text-sm text-slate-400">
                            Daftar breakdown semua unit unit/imei yang masuk
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <router-link
                            to="/dashboard/purchases"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold transition-all bg-white border text-slate-600 rounded-xl border-slate-200 hover:bg-slate-50"
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
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                />
                            </svg>
                            Stok per Supplier
                        </router-link>
                    </div>
                </div>
                <div
                    class="grid w-full max-w-3xl grid-cols-1 gap-3 mt-4 sm:grid-cols-3"
                >
                    <div
                        class="relative overflow-hidden rounded-2xl shadow-lg p-4 min-h-[94px] bg-gradient-to-br from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-blue-500/20"
                    >
                        <div class="relative z-10">
                            <div class="text-xl font-black tracking-wide">
                                {{ formatNumber(copyAmountStok) }}
                            </div>
                            <div
                                class="mt-1 text-sm font-semibold/5 opacity-95"
                            >
                                Total Item
                            </div>
                        </div>
                        <div
                            class="absolute inset-y-0 right-0 flex items-end gap-1 pb-3 pr-3 opacity-60"
                        >
                            <span
                                class="w-2 h-3 rounded-sm bg-blue-900/25"
                            ></span>
                            <span
                                class="w-2 h-6 rounded-sm bg-blue-900/25"
                            ></span>
                            <span
                                class="w-2 h-10 rounded-sm bg-blue-900/25"
                            ></span>
                            <span
                                class="w-2 h-5 rounded-sm bg-blue-900/25"
                            ></span>
                            <span
                                class="w-2 h-8 rounded-sm bg-blue-900/25"
                            ></span>
                        </div>
                    </div>
                    <div
                        class="relative overflow-hidden rounded-2xl shadow-lg p-4 min-h-[94px] bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 text-white shadow-amber-500/20"
                    >
                        <div class="relative z-10">
                            <div class="text-xl font-black tracking-wide">
                                {{ formatCurrency(copyTotalModal) }}
                            </div>
                            <div
                                class="mt-1 text-sm font-semibold/5 opacity-95"
                            >
                                Total Modal
                            </div>
                        </div>
                        <div
                            class="absolute inset-y-0 right-0 flex items-end gap-1 pb-3 pr-3 opacity-60"
                        >
                            <span
                                class="w-2 h-3 rounded-sm bg-orange-900/20"
                            ></span>
                            <span
                                class="w-2 h-6 rounded-sm bg-orange-900/20"
                            ></span>
                            <span
                                class="w-2 h-10 rounded-sm bg-orange-900/20"
                            ></span>
                            <span
                                class="w-2 h-5 rounded-sm bg-orange-900/20"
                            ></span>
                            <span
                                class="w-2 h-8 rounded-sm bg-orange-900/20"
                            ></span>
                        </div>
                    </div>
                    <router-link
                        :to="{ name: 'stock-wa' }"
                        class="relative overflow-hidden rounded-2xl shadow-lg p-4 min-h-[94px] bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600 text-white shadow-purple-500/20 flex flex-col justify-between hover:shadow-xl hover:scale-[1.02] transition-all duration-200"
                    >
                        <div class="relative z-10">
                            <div class="text-sm font-black tracking-wide">
                                List Stok WA
                            </div>
                            <div class="mt-1 text-xs opacity-90">
                                Lihat &amp; copy daftar stok
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs font-semibold opacity-80 mt-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Buka &rarr;
                        </div>
                        <div class="absolute inset-y-0 right-0 flex items-end gap-1 pb-3 pr-3 opacity-40">
                            <span class="w-2 h-3 rounded-sm bg-purple-900/30"></span>
                            <span class="w-2 h-6 rounded-sm bg-purple-900/30"></span>
                            <span class="w-2 h-10 rounded-sm bg-purple-900/30"></span>
                            <span class="w-2 h-5 rounded-sm bg-purple-900/30"></span>
                            <span class="w-2 h-8 rounded-sm bg-purple-900/30"></span>
                        </div>
                    </router-link>
                </div>
            </div>
        </div>

        <div
            class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200"
        >
            <!-- Header Actions -->
            <div
                class="flex flex-col items-end justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row"
            >
                <!-- Left Side: Advanced Filters -->
                <div
                    class="grid items-end w-full grid-cols-2 gap-3 md:grid-cols-4 lg:flex md:w-auto"
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
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Merk</label
                        >
                        <div class="relative">
                            <select
                                v-model="filters.brand_id"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[140px] pr-8 shadow-sm"
                            >
                                <option value="">Semua Merk</option>
                                <option
                                    v-for="c in brands"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.nama }}
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
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Supplier</label
                        >
                        <div class="relative">
                            <select
                                v-model="filters.supplier_id"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[140px] pr-8"
                            >
                                <option value="">Semua Supplier</option>
                                <option
                                    v-for="s in suppliers"
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

                    <div
                        class="flex flex-col col-span-2 gap-1 md:col-span-1 lg:justify-end"
                    >
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase invisible hidden md:block"
                            >Reset</label
                        >
                        <button
                            @click="
                                filters = {
                                    start_date: '',
                                    end_date: '',
                                    supplier_id: '',
                                    brand_id: '',
                                    no_invoice: '',
                                };
                                searchQuery = '';
                            "
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

                <!-- Right Side -->
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
                                placeholder="Cari barang/IMEI/No.Invoice..."
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
            <div
                class="overflow-x-auto border-0 rounded-none shadow-none text-[11px]"
            >
                <table class="min-w-full leading-normal">
                    <thead
                        class="font-bold uppercase border-b bg-slate-100/70 border-slate-200 text-slate-500 whitespace-nowrap"
                    >
                        <tr>
                            <th class="px-2 py-2 text-center">No</th>
                            <th class="px-2 py-2 text-left">Kode</th>
                            <th class="px-2 py-2 text-left">Nama Barang</th>
                            <th class="px-2 py-2 text-left">Satuan</th>
                            <th class="px-2 py-2 text-left">Merk</th>
                            <th class="px-2 py-2 text-left">Grade</th>
                            <th class="px-2 py-2 text-left">IMEI 1</th>
                            <th class="px-2 py-2 text-left">IMEI 2</th>
                            <th class="px-2 py-2 text-center">Stok</th>
                            <th class="px-2 py-2 text-right">Modal</th>
                            <th class="px-2 py-2 text-right">Jual</th>
                            <th class="px-2 py-2 text-left">No Invoice</th>
                            <th class="px-2 py-2 text-left">Supplier</th>
                            <th class="px-2 py-2 text-left">Tanggal</th>
                            <th class="px-2 py-2 text-left">Keterangan</th>
                            <th class="px-2 py-2 text-left">Status</th>
                            <th
                                class="px-2 py-2 text-center sticky right-0 bg-slate-100/90 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)]"
                            >
                                AKSI
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        <tr v-if="isLoading">
                            <td
                                colspan="17"
                                class="px-6 py-12 text-center text-slate-500"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <div
                                        class="w-8 h-8 border-4 rounded-full border-slate-200 border-t-blue-500 animate-spin"
                                    ></div>
                                    <span class="text-sm font-medium"
                                        >Memuat data detail...</span
                                    >
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="items.length === 0">
                            <td
                                colspan="17"
                                class="px-6 py-12 italic text-center text-slate-500 text-[14px]"
                            >
                                Tidak ada data ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="(item, index) in items"
                            :key="item.id"
                            class="table-row group"
                        >
                            <td
                                class="px-2 py-2 text-center text-slate-500 whitespace-nowrap"
                            >
                                {{
                                    (pagination.current_page - 1) *
                                        pagination.per_page +
                                    index +
                                    1
                                }}
                            </td>
                            <td
                                class="px-2 py-2 font-mono text-[12px] text-slate-600 whitespace-nowrap"
                            >
                                {{ item.product?.barcode }}
                            </td>
                            <td
                                class="px-2 py-2 text-slate-800 break-words text-[12px] line-clamp-2 max-w-[200px]"
                                style="display: table-cell"
                            >
                                {{ item.product?.nama }}
                            </td>
                            <td
                                class="px-2 py-2 text-blue-600 whitespace-nowrap text-[12px] uppercase font-medium"
                            >
                                <a
                                    href="javascript:void(0)"
                                    @click="openEditModal('unit', item)"
                                    class="hover:underline"
                                >
                                    {{ item.product?.unit || "INPUT" }}
                                </a>
                            </td>
                            <td
                                class="px-2 py-2 text-slate-800 text-[12px] whitespace-nowrap"
                            >
                                {{ item.product?.brand || "-" }}
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-[12px]">
                                {{ item.product?.grade || "-" }}
                            </td>
                            <td
                                class="px-2 py-2 font-medium text-blue-600 whitespace-nowrap text-[12px]"
                            >
                                <a
                                    href="javascript:void(0)"
                                    @click="openEditModal('imei1', item)"
                                    class="hover:underline"
                                >
                                    {{ item.product?.imei1 || "INPUT" }}
                                </a>
                            </td>
                            <td
                                class="px-2 py-2 font-medium text-blue-600 whitespace-nowrap text-[12px]"
                            >
                                <a
                                    href="javascript:void(0)"
                                    @click="openEditModal('imei2', item)"
                                    class="hover:underline"
                                >
                                    {{ item.product?.imei2 || "INPUT" }}
                                </a>
                            </td>
                            <td
                                class="px-2 py-2 font-bold text-center text-slate-700 whitespace-nowrap text-[12px]"
                            >
                                {{ item.qty || item.product?.stok || 0 }}
                            </td>
                            <td
                                class="px-2 py-2 font-bold text-right text-blue-600 whitespace-nowrap text-[12px]"
                            >
                                <a
                                    href="javascript:void(0)"
                                    @click="openEditModal('harga_beli', item)"
                                    class="hover:underline"
                                >
                                    {{ formatCurrency(item.harga_beli) }}
                                </a>
                            </td>
                            <td
                                class="px-2 py-2 font-bold text-right text-emerald-600 whitespace-nowrap text-[12px]"
                            >
                                <a
                                    href="javascript:void(0)"
                                    @click="openEditModal('harga_jual', item)"
                                    class="hover:underline"
                                >
                                    {{
                                        formatCurrency(
                                            getEffectiveSalePrice(item)
                                        )
                                    }}
                                </a>
                            </td>
                            <td
                                class="px-2 py-2 font-medium text-slate-600 whitespace-nowrap text-[12px]"
                            >
                                {{ item.purchase?.no_invoice }}
                            </td>
                            <td
                                class="px-2 py-2 text-slate-800 whitespace-nowrap text-[12px]"
                            >
                                {{ item.purchase?.supplier?.nama || "-" }}
                            </td>
                            <td
                                class="px-2 py-2 text-slate-800 whitespace-nowrap text-[12px]"
                            >
                                {{ formatDate(item.purchase?.tanggal) }}
                            </td>
                            <td
                                class="px-2 py-2 text-slate-400 italic break-words max-w-[120px] text-[12px]"
                            >
                                {{ item.product?.keterangan || "-" }}
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-[12px]">
                                <span
                                    v-if="item.product?.is_sold"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100"
                                >
                                    Terjual
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100"
                                >
                                    Tersedia
                                </span>
                            </td>
                            <!-- Sticky Ops Column for better UX -->
                            <td
                                class="px-2 py-2 text-center sticky right-0 bg-white/95 group-hover:bg-slate-50/95 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)] whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <button
                                        @click="
                                            printBarcode(
                                                item.purchase_id,
                                                item.id
                                            )
                                        "
                                        class="p-1 text-purple-500 transition rounded hover:bg-purple-100"
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
                                    <router-link
                                        :to="{
                                            name: 'purchase-edit',
                                            params: { id: item.purchase_id },
                                        }"
                                        class="p-1 transition rounded text-amber-500 hover:bg-amber-100"
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
                                    <button
                                        v-if="canDelete"
                                        @click="
                                            confirmDelete(
                                                item.id,
                                                item.purchase_id
                                            )
                                        "
                                        class="p-1 transition rounded text-rose-500 hover:bg-rose-100"
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
                    <tfoot
                        v-if="items.length > 0"
                        class="font-bold border-t bg-blue-50/50 border-slate-200"
                    >
                        <tr>
                            <td
                                colspan="8"
                                class="px-2 py-3 tracking-wider text-center uppercase text-slate-800"
                            >
                                TOTAL
                            </td>
                            <td class="px-2 py-3 text-center text-slate-800">
                                {{ copyAmountStok }}
                            </td>
                            <td class="px-2 py-3 text-right text-blue-700">
                                {{ formatCurrency(copyTotalModal) }}
                            </td>
                            <td class="px-2 py-3 text-right text-emerald-700">
                                {{ formatCurrency(copyTotalJual) }}
                            </td>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination.last_page > 1"
                class="flex flex-col items-start justify-between gap-3 px-6 py-3 border-t sm:flex-row sm:items-center border-slate-200 bg-slate-50"
            >
                <div class="text-[11px] text-slate-500">
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
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Sebelumnya
                    </button>
                    <button
                        @click="fetchItems(pagination.current_page + 1)"
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

        <ConfirmDialog
            :show="showDelete"
            :loading="deleting"
            @confirm="doDelete"
            @cancel="showDelete = false"
        />

        <!-- Inline Edit Modal -->
        <div
            v-if="editModal.show"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
        >
            <div
                class="w-full max-w-md overflow-hidden bg-white rounded-lg shadow-2xl"
            >
                <div
                    class="flex items-center justify-between px-4 py-3 text-white bg-blue-600"
                >
                    <h3 class="text-sm font-bold">{{ editModal.title }}</h3>
                    <button
                        @click="editModal.show = false"
                        class="text-white hover:text-rose-300"
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
                            ></path>
                        </svg>
                    </button>
                </div>

                <div class="px-5 py-6">
                    <!-- For Unit -->
                    <div
                        v-if="editModal.type === 'unit'"
                        class="flex items-center gap-3"
                    >
                        <label class="w-24 text-sm font-bold text-slate-700"
                            >Satuan :</label
                        >
                        <select
                            v-model="editModal.newValue"
                            class="flex-1 px-3 py-2 text-sm border rounded border-slate-300 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Pilih Satuan</option>
                            <option
                                v-for="u in units"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.nama }}
                            </option>
                        </select>
                    </div>

                    <!-- For IMEI -->
                    <div
                        v-if="
                            editModal.type === 'imei1' ||
                            editModal.type === 'imei2'
                        "
                        class="flex flex-col gap-2"
                    >
                        <label
                            class="text-sm font-bold uppercase text-slate-700"
                            >{{
                                editModal.type === "imei1" ? "IMEI 1" : "IMEI 2"
                            }}</label
                        >
                        <input
                            type="text"
                            v-model="editModal.newValue"
                            class="w-full px-3 py-2 border rounded border-slate-300 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <!-- For Harga -->
                    <div
                        v-if="
                            editModal.type === 'harga_beli' ||
                            editModal.type === 'harga_jual'
                        "
                        class="flex flex-col gap-2"
                    >
                        <div class="mb-2 text-sm font-bold text-slate-700">
                            {{
                                editModal.type === "harga_beli"
                                    ? "Harga Modal Lama"
                                    : "Harga Jual Lama"
                            }}
                            : {{ editModal.oldValueDisplay }}
                        </div>
                        <label class="text-sm font-bold text-slate-700">{{
                            editModal.type === "harga_beli"
                                ? "Harga Modal"
                                : "Harga Jual"
                        }}</label>
                        <CurrencyInput
                            v-model="editModal.newValue"
                            :allowThousands="true"
                        />
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100 bg-slate-50"
                >
                    <button
                        @click="editModal.show = false"
                        class="px-4 py-1.5 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded transition shadow"
                    >
                        Tutup
                    </button>
                    <button
                        @click="saveEdit"
                        :disabled="savingEdit"
                        class="px-4 py-1.5 text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 rounded transition shadow disabled:opacity-50"
                    >
                        {{ savingEdit ? "Menyimpan..." : "Simpan" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
