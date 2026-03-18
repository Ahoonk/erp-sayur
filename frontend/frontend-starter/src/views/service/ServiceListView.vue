<script setup>
import { ref, onMounted, watch } from "vue";
import DateInput from "../../components/DateInput.vue";
import api from "../../api";
import { useRouter } from "vue-router";
import { useToast } from "../../composables/useToast";
import { useAuthStore } from "../../stores/auth";
import debounce from "lodash-es/debounce";
import ConfirmDialog from "../../components/ConfirmDialog.vue";

const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();

const services = ref([]);
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
    status: "",
    start_date: "",
    end_date: "",
});

onMounted(() => {
    fetchServices();
});

async function fetchServices(page = 1) {
    isLoading.value = true;
    try {
        const { data } = await api.get("/services", {
            params: {
                page,
                per_page: perPage.value,
                search: searchQuery.value,
                status: filters.value.status,
                start_date: filters.value.start_date,
                end_date: filters.value.end_date,
            },
        });

        services.value = data.data.data;
        pagination.value = {
            current_page: data.data.current_page,
            last_page: data.data.last_page,
            total: data.data.total,
            per_page: data.data.per_page,
        };
    } catch (err) {
        toast.error("Gagal memuat data servis");
    } finally {
        isLoading.value = false;
    }
}

const throttledSearch = debounce(() => {
    fetchServices(1);
}, 500);

watch(searchQuery, throttledSearch);
watch(perPage, () => fetchServices(1));
watch(
    () => filters.value,
    () => {
        if (
            filters.value.start_date &&
            filters.value.end_date &&
            filters.value.end_date < filters.value.start_date
        ) {
            toast.error(
                "Tanggal sampai tidak boleh lebih kecil dari tanggal mulai"
            );
            filters.value.end_date = "";
            return;
        }
        fetchServices(1);
    },
    { deep: true }
);

function formatCurrency(val) {
    return new Intl.NumberFormat("id-ID", {
        minimumFractionDigits: 0,
    }).format(val || 0);
}

function formatDate(dateStr) {
    if (!dateStr) return "-";
    const [y, m, d] = dateStr.split("-");
    return `${d}-${m}-${y}`;
}

const showDelete = ref(false);
const deleteId = ref(null);
const deleting = ref(false);

function confirmDelete(id) {
    deleteId.value = id;
    showDelete.value = true;
}

async function doDelete() {
    if (!deleteId.value) return;
    deleting.value = true;
    try {
        await api.delete(`/services/${deleteId.value}`);
        toast.success("Data servis berhasil dihapus");
        fetchServices(pagination.value.current_page);
    } catch (err) {
        toast.error(
            err.response?.data?.message || "Gagal menghapus data servis"
        );
    } finally {
        deleting.value = false;
        showDelete.value = false;
        deleteId.value = null;
    }
}

const statusBadges = {
    pending: "bg-slate-100 text-slate-500 border-slate-200",
    dikerjakan: "bg-blue-50 text-blue-600 border-blue-100",
    selesai: "bg-emerald-50 text-emerald-600 border-emerald-100",
    diambil: "bg-purple-50 text-purple-600 border-purple-100",
    batal: "bg-rose-50 text-rose-600 border-rose-100",
};

const statusLabels = {
    pending: "Pending",
    dikerjakan: "Proses",
    selesai: "Selesai",
    batal: "Batal",
};
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <!-- Header Section -->
        <div
            class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-800">
                    Data Servis HP
                </h1>
                <p class="text-sm font-medium text-slate-500">
                    Kelola antrian dan progres perbaikan unit
                </p>
            </div>
            <router-link
                to="/dashboard/services/create"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-xl shadow-blue-500/20 active:scale-95"
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
                        stroke-width="2.5"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Service Masuk Baru
            </router-link>
        </div>

        <div
            class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200"
        >
            <!-- Filter Bar -->
            <div
                class="flex flex-col items-start justify-between gap-6 px-6 py-5 border-b border-slate-100 bg-slate-50/50 md:flex-row md:items-center"
            >
                <!-- Left: Advanced Filters -->
                <div
                    class="grid w-full grid-cols-2 gap-3 md:grid-cols-4 lg:flex md:w-auto"
                >
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Status</label
                        >
                        <div class="relative">
                            <select
                                v-model="filters.status"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[140px] pr-8"
                            >
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="dikerjakan">Proses</option>
                                <option value="selesai">Selesai</option>
                                <option value="batal">Batal</option>
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
                    <div class="flex flex-col gap-1 col-span-2 md:col-span-1 lg:justify-end">
                        <label class="text-[10px] font-bold text-slate-400 uppercase invisible hidden md:block">Reset</label>
                        <button
                            @click="
                                filters = {
                                    status: '',
                                    start_date: '',
                                    end_date: '',
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

                <!-- Right: Pagination & Search -->
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
                                type="text"
                                v-model="searchQuery"
                                placeholder="Cari no service / pelanggan..."
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
                            <th class="w-32">No Service</th>
                            <th class="w-44">Pelanggan</th>
                            <th class="w-32">Teknisi</th>
                            <th class="w-40">Unit / IMEI</th>
                            <th class="w-52">Kerusakan</th>
                            <th class="text-center w-28">Perbaikan</th>
                            <th class="w-32 text-center">Pengambilan</th>

                            <th class="w-32 text-center">Tgl Masuk</th>
                            <th class="w-40 text-right">Estimasi</th>
                            <th class="table-col-action-h">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="isLoading">
                            <td
                                colspan="11"
                                class="px-6 py-20 text-center text-slate-500"
                            >
                                <div class="flex flex-col items-center gap-3">
                                    <div
                                        class="w-10 h-10 border-4 rounded-full border-blue-50 border-t-blue-500 animate-spin"
                                    ></div>
                                    <span
                                        class="text-xs font-black tracking-widest uppercase text-slate-400"
                                        >Memuat Data...</span
                                    >
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="services.length === 0">
                            <td
                                colspan="11"
                                class="px-6 py-20 italic font-medium text-center text-slate-400"
                            >
                                Tidak ada data servis ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="(item, index) in services"
                            :key="item.id"
                            class="table-row group"
                        >
                            <td
                                class="table-cell text-xs font-black text-center text-slate-400 group-hover:text-blue-500"
                            >
                                {{
                                    (pagination.current_page - 1) *
                                        pagination.per_page +
                                    index +
                                    1
                                }}
                            </td>
                            <td class="table-cell">
                                <div class="text-xs font-bold text-slate-700">
                                    {{ item.no_service || "-" }}
                                </div>
                            </td>
                            <td class="table-cell">
                                <div class="text-sm font-bold text-slate-800">
                                    {{ item.nama_pelanggan }}
                                </div>
                                <div
                                    class="text-[10px] font-bold text-slate-400 mt-0.5"
                                >
                                    {{ item.no_hp_pelanggan || "-" }}
                                </div>
                            </td>
                            <td class="table-cell">
                                <span
                                    v-if="item.technician"
                                    class="text-sm font-bold text-slate-800"
                                >
                                    {{ item.technician.nama }}
                                </span>
                                <span
                                    v-else
                                    class="text-xs font-medium text-slate-400"
                                >
                                    -
                                </span>
                            </td>
                            <td class="table-cell">
                                <div class="text-sm font-bold text-slate-700">
                                    {{ item.merk_hp }} {{ item.tipe_hp }}
                                </div>
                                <div
                                    class="text-[10px] font-mono text-slate-400 mt-0.5 uppercase tracking-tighter"
                                >
                                    {{ item.imei_hp || "-" }}
                                </div>
                            </td>
                            <td class="table-cell">
                                <p
                                    class="text-xs italic font-medium text-slate-500 line-clamp-1"
                                >
                                    "{{ item.kerusakan }}"
                                </p>
                            </td>
                            <td class="table-cell text-center">
                                <span
                                    :class="statusBadges[item.status]"
                                    class="inline-block px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border shadow-sm"
                                >
                                    {{ statusLabels[item.status] }}
                                </span>
                            </td>
                            <td class="table-cell text-center">
                                <span
                                    v-if="
                                        item.status_pengambilan ===
                                        'sudah_diambil'
                                    "
                                    class="bg-purple-50 text-purple-600 border-purple-100 inline-block px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border shadow-sm"
                                >
                                    SUDAH DIAMBIL
                                </span>
                                <span
                                    v-else
                                    class="bg-slate-50 text-slate-400 border-slate-100 inline-block px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border shadow-sm"
                                >
                                    BELUM DIAMBIL
                                </span>
                            </td>

                            <td
                                class="table-cell text-center text-slate-500 text-[11px] font-bold"
                            >
                                {{ formatDate(item.tanggal_masuk) }}
                            </td>
                            <td class="table-cell text-right">
                                <span
                                    class="text-[10px] font-bold text-slate-400 uppercase mr-1"
                                    >Rp</span
                                >
                                <span
                                    class="text-sm font-black tracking-tight text-slate-800"
                                >
                                    {{ formatCurrency(item.grand_total) }}
                                </span>
                            </td>
                            <td class="table-col-action">
                                <div class="table-actions">
                                    <button
                                        @click="
                                            router.push(
                                                `/dashboard/services/${item.id}`
                                            )
                                        "
                                        class="p-2 text-blue-500 transition-all rounded-lg hover:bg-blue-50"
                                        title="Detail Service"
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
                                    <button
                                        @click="
                                            router.push(
                                                `/dashboard/services/${item.id}/edit`
                                            )
                                        "
                                        v-if="
                                            item.status !== 'selesai' ||
                                            authStore.isSuperAdmin
                                        "
                                        class="p-2 transition-all rounded-lg text-amber-500 hover:bg-amber-50"
                                        title="Edit Data"
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
                                    </button>
                                    <button
                                        @click="confirmDelete(item.id)"
                                        v-if="
                                            item.status !== 'selesai' ||
                                            authStore.isSuperAdmin
                                        "
                                        class="p-2 transition-all rounded-lg text-rose-500 hover:bg-rose-50"
                                        title="Hapus Data"
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

            <!-- Pagination Section -->
            <div
                v-if="pagination.last_page > 1"
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/30"
            >
                <div
                    class="text-xs font-bold tracking-widest uppercase text-slate-400"
                >
                    Halaman
                    <span class="text-slate-800">{{
                        pagination.current_page
                    }}</span>
                    dari
                    <span class="text-slate-800">{{
                        pagination.last_page
                    }}</span>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="fetchServices(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
                    >
                        Prev
                    </button>
                    <button
                        @click="fetchServices(pagination.current_page + 1)"
                        :disabled="
                            pagination.current_page === pagination.last_page
                        "
                        class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-600 hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
        <ConfirmDialog
            :show="showDelete"
            :loading="deleting"
            title="Konfirmasi Hapus"
            message="Hapus data servis ini secara permanen?"
            confirmText="Hapus"
            loadingText="Menghapus..."
            @confirm="doDelete"
            @cancel="showDelete = false"
        />
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>




