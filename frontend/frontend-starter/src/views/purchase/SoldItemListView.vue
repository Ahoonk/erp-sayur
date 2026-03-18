<script setup>
import { ref, onMounted, watch } from "vue";
import DateInput from "../../components/DateInput.vue";
import api from "../../api";
import { useRouter } from "vue-router";
import { useToast } from "../../composables/useToast";
import debounce from "lodash-es/debounce";

const router = useRouter();
const toast = useToast();

const items = ref([]);
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
    no_invoice: "",
});

onMounted(() => {
    fetchItems();
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
                no_invoice: filters.value.no_invoice,
                status: "sold", // Forced filter for sold items
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
        toast.error("Gagal memuat data barang terjual");
    } finally {
        isLoading.value = false;
    }
}

const throttledSearch = debounce(() => {
    fetchItems(1);
}, 500);

watch(searchQuery, throttledSearch);
watch(perPage, () => fetchItems(1));
watch(
    () => filters.value,
    () => fetchItems(1),
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
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Barang Terjual
                </h1>
                <p class="mt-1 text-sm text-slate-400">
                    Daftar semua unit yang sudah keluar/terjual
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <router-link
                    to="/dashboard/purchase-items"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-semibold transition-all bg-white border text-slate-600 rounded-xl border-slate-200 hover:bg-slate-50"
                >
                    Lihat Semua Barang
                </router-link>
            </div>
        </div>

        <div
            class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200"
        >
            <!-- Header Actions -->
            <div
                class="flex flex-col items-end justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row"
            >
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
                            class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white"
                        />
                    </div>
                    <div class="flex flex-col gap-1 col-span-2 md:col-span-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Supplier</label
                        >
                        <select
                            v-model="filters.supplier_id"
                            class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[140px]"
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
                    </div>
                    <div class="flex flex-col gap-1 col-span-2 md:col-span-1 lg:justify-end">
                        <label class="text-[10px] font-bold text-slate-400 uppercase invisible hidden md:block">Reset</label>
                        <button
                            @click="
                                filters = {
                                    start_date: '',
                                    end_date: '',
                                    supplier_id: '',
                                    no_invoice: '',
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

                <div class="flex flex-row items-end w-full gap-2 md:w-auto">
                    <div class="flex flex-col gap-1 grow md:grow-0">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Search</label
                        >
                        <div class="relative">
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Cari barang/IMEI..."
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
                            <th class="">Kode</th>
                            <th class="">Nama Barang</th>
                            <th class="">Kategori</th>
                            <th class="">Grade</th>
                            <th class="">IMEI 1</th>
                            <th class="text-right">Modal</th>
                            <th class="text-right">Jual</th>
                            <th class="">No Transaksi Masuk</th>
                            <th class="">Supplier</th>
                            <th class="">No Transaksi Keluar</th>
                            <th class="">Tanggal Terjual</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-if="isLoading">
                            <td
                                colspan="12"
                                class="px-6 py-12 text-center text-slate-500"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <div
                                        class="w-8 h-8 border-4 rounded-full border-slate-200 border-t-blue-500 animate-spin"
                                    ></div>
                                    <span class="text-sm font-medium"
                                        >Memuat data terjual...</span
                                    >
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="items.length === 0">
                            <td
                                colspan="12"
                                class="px-6 py-12 italic text-center text-slate-500"
                            >
                                Tidak ada data unit yang terjual.
                            </td>
                        </tr>
                        <tr
                            v-for="(item, index) in items"
                            :key="item.id"
                            class="table-row"
                        >
                            <td class="table-cell text-center text-slate-500">
                                {{
                                    (pagination.current_page - 1) *
                                        pagination.per_page +
                                    index +
                                    1
                                }}
                            </td>
                            <td
                                class="table-cell font-mono text-[10px] text-slate-600"
                            >
                                {{ item.product?.barcode }}
                            </td>
                            <td class="table-cell font-semibold text-slate-800">
                                {{ item.product?.nama }}
                            </td>
                            <td class="table-cell text-slate-500">
                                {{ item.product?.category || "-" }}
                            </td>
                            <td class="table-cell">
                                <span
                                    class="px-2 py-0.5 bg-rose-50 text-rose-600 rounded text-[10px] font-bold border border-rose-100 uppercase"
                                >
                                    {{ item.product?.grade || "-" }}
                                </span>
                            </td>
                            <td class="table-cell font-medium text-slate-600">
                                {{ item.product?.imei1 || "-" }}
                            </td>
                            <td
                                class="table-cell font-bold text-right text-slate-500"
                            >
                                {{ formatCurrency(item.harga_beli) }}
                            </td>
                            <td
                                class="table-cell font-bold text-right text-emerald-600"
                            >
                                {{ formatCurrency(item.product?.harga_jual) }}
                            </td>
                            <td class="table-cell font-medium text-blue-600">
                                {{ item.purchase?.no_invoice }}
                            </td>
                            <td class="table-cell text-slate-700">
                                {{ item.purchase?.supplier?.nama || "-" }}
                            </td>
                            <td class="table-cell font-medium text-emerald-600">
                                {{ item.product?.sale?.no_invoice || "-" }}
                            </td>
                            <td class="table-cell text-slate-500">
                                {{
                                    item.product?.sale?.tanggal
                                        ? formatDate(item.product.sale.tanggal)
                                        : item.updated_at
                                        ? formatDate(
                                              item.updated_at.split("T")[0]
                                          )
                                        : "-"
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination.last_page > 1"
                class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-3 border-t border-slate-200 bg-slate-50"
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
    </div>
</template>




