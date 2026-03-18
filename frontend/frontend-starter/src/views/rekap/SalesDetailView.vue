<script setup>
import { ref, watch, onMounted } from "vue";
import debounce from "lodash-es/debounce";
import api from "../../api";
import { useToast } from "../../composables/useToast";
import { useAuthStore } from "../../stores/auth";
import CurrencyInput from "../../components/CurrencyInput.vue";
import DateInput from "../../components/DateInput.vue";

const toast = useToast();
const authStore = useAuthStore();
const isLoading = ref(false);
const exportingExcel = ref(false);
const exportingPdf = ref(false);
const rows = ref([]);

const filters = ref({ start_date: "", end_date: "", search: "" });
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 25,
    total: 0,
});
const perPage = ref(25);
const summary = ref({ total_modal: 0, total_harga_jual: 0, total_laba: 0 });

function formatCurrency(v) {
    return "Rp." + Number(v || 0).toLocaleString("id-ID") + ",-";
}
function formatDate(d) {
    if (!d) return "-";
    const [y, m, day] = d.split("-");
    return `${day}-${m}-${y}`;
}

async function fetchData(page = 1) {
    isLoading.value = true;
    try {
        const params = {
            page,
            per_page: perPage.value,
            ...(filters.value.start_date && {
                start_date: filters.value.start_date,
            }),
            ...(filters.value.end_date && { end_date: filters.value.end_date }),
            ...(filters.value.search && { search: filters.value.search }),
        };
        const { data } = await api.get("/reports/sales-detail", { params });
        const d = data.data;
        rows.value = d.data || [];
        summary.value = d.summary || {
            total_modal: 0,
            total_harga_jual: 0,
            total_laba: 0,
        };
        pagination.value = {
            current_page: d.current_page,
            last_page: d.last_page,
            per_page: d.per_page,
            total: d.total,
        };
    } catch {
        toast.error("Gagal memuat data");
    } finally {
        isLoading.value = false;
    }
}

const debouncedSearch = debounce(() => fetchData(1), 400);

watch(
    () => filters.value.start_date,
    () => fetchData(1)
);
watch(
    () => filters.value.end_date,
    () => fetchData(1)
);
watch(() => filters.value.search, debouncedSearch);
watch(perPage, () => fetchData(1));

function resetFilter() {
    filters.value = { start_date: "", end_date: "", search: "" };
}

function buildDateSuffix(start, end) {
    if (!start && !end) return "all";
    const fmt = (d) => { const [y, m, day] = d.split("-"); return `${day}-${m}-${y}`; };
    if (start && end) return `${fmt(start)}_sd_${fmt(end)}`;
    return fmt(start || end);
}

async function exportExcel() {
    exportingExcel.value = true;
    try {
        const params = {
            export: "excel",
            ...(filters.value.start_date && {
                start_date: filters.value.start_date,
            }),
            ...(filters.value.end_date && { end_date: filters.value.end_date }),
            ...(filters.value.search && { search: filters.value.search }),
        };
        const res = await api.get("/reports/sales-detail", {
            params,
            responseType: "blob",
        });
        const url = URL.createObjectURL(res.data);
        const a = document.createElement("a");
        a.href = url;
        a.download = `rekap-penjualan-detail_${buildDateSuffix(filters.value.start_date, filters.value.end_date)}.xlsx`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    } catch {
        toast.error("Gagal export Excel");
    } finally {
        exportingExcel.value = false;
    }
}

function buildPdfHtml(dataRows, summ) {
    const title = "Rekap Penjualan Detail";
    const s = filters.value.start_date ? formatDate(filters.value.start_date) : null;
    const e = filters.value.end_date ? formatDate(filters.value.end_date) : null;
    const periode = (s || e) ? `${s || "-"} s/d ${e || "-"}` : "Semua";

    const bodyRows = dataRows.map((row, idx) => `
        <tr>
            <td>${idx + 1}</td>
            <td>${row.no_invoice || "-"}</td>
            <td>${row.kode || "-"}</td>
            <td>${formatDate(row.tanggal)}</td>
            <td>${row.nama_produk || "-"}</td>
            <td>${row.merk || "-"}</td>
            <td>${row.grade || "-"}</td>
            <td>${row.satuan || "-"}</td>
            <td>${row.imei1 || "-"}</td>
            <td>${row.imei2 || "-"}</td>
            <td>${row.pelanggan || "-"}</td>
            <td>${row.kasir || "-"}</td>
            <td>${row.sales || "-"}</td>
            <td style="text-align:right;">${formatCurrency(row.modal)}</td>
            <td style="text-align:right;">${formatCurrency(row.harga_jual)}</td>
            <td style="text-align:right; color:${Number(row.laba || 0) < 0 ? "#dc2626" : "#16a34a"};">${formatCurrency(row.laba)}</td>
        </tr>`).join("");

    return `<html><head><title>${title}</title><style>
        body { font-family: Arial, sans-serif; padding: 16px; color: #0f172a; }
        h1 { margin: 0 0 6px; font-size: 18px; }
        p { margin: 0 0 12px; font-size: 11px; color: #475569; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; }
        th { background: #f8fafc; text-align: left; }
        tfoot td { background: #e2eef6; font-weight: 700; }
    </style></head><body>
        <h1>${title}</h1>
        <p>Periode: ${periode}</p>
        <table>
            <thead><tr>
                <th>No</th><th>No Invoice</th><th>Kode</th><th>Tanggal</th>
                <th>Nama Produk</th><th>Merk</th><th>Grade</th><th>Satuan</th>
                <th>IMEI 1</th><th>IMEI 2</th><th>Pelanggan</th><th>Kasir</th><th>Sales</th>
                <th style="text-align:right;">Modal</th>
                <th style="text-align:right;">Harga Jual</th>
                <th style="text-align:right;">Laba</th>
            </tr></thead>
            <tbody>${bodyRows}</tbody>
            <tfoot><tr>
                <td colspan="13" style="text-align:right;">TOTAL</td>
                <td style="text-align:right;">${formatCurrency(summ.total_modal || 0)}</td>
                <td style="text-align:right;">${formatCurrency(summ.total_harga_jual || 0)}</td>
                <td style="text-align:right; color:${Number(summ.total_laba || 0) < 0 ? "#dc2626" : "#16a34a"};">${formatCurrency(summ.total_laba || 0)}</td>
            </tr></tfoot>
        </table>
    </body></html>`;
}

async function exportPdf() {
    exportingPdf.value = true;
    try {
        const { data } = await api.get("/reports/sales-detail", {
            params: {
                per_page: -1,
                ...(filters.value.start_date && { start_date: filters.value.start_date }),
                ...(filters.value.end_date && { end_date: filters.value.end_date }),
                ...(filters.value.search && { search: filters.value.search }),
            },
        });
        const allRows = data.data.data || [];
        const summ = data.data.summary || summary.value;
        const printWindow = window.open("", "_blank");
        if (!printWindow) { toast.error("Popup diblokir browser"); return; }
        printWindow.document.write(buildPdfHtml(allRows, summ));
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    } catch {
        toast.error("Gagal export PDF");
    } finally {
        exportingPdf.value = false;
    }
}

onMounted(() => fetchData(1));

// Edit HPP modal
const editModal = ref({ show: false, row: null, oldValueDisplay: "", newValue: 0 });
const savingEdit = ref(false);

function openEditModal(row) {
    editModal.value.row = row;
    editModal.value.oldValueDisplay = formatCurrency(row.modal);
    editModal.value.newValue = Number(row.modal || 0);
    editModal.value.show = true;
}

async function saveEdit() {
    if (!editModal.value.row) return;
    const row = editModal.value.row;
    savingEdit.value = true;
    try {
        await api.put(`/sales/${row.sales_transaction_id}/items/${row.id}`, {
            hpp_total: editModal.value.newValue,
        });
        const diff = editModal.value.newValue - row.modal;
        row.modal = editModal.value.newValue;
        row.laba = row.harga_jual - row.modal;
        summary.value.total_modal += diff;
        summary.value.total_laba -= diff;
        editModal.value.show = false;
        toast.success("Harga modal berhasil diubah");
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal mengubah harga modal");
    } finally {
        savingEdit.value = false;
    }
}
</script>

<template>
    <div class="space-y-5">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-800">
                    Rekap Penjualan Detail
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Semua item terjual dipecah per barang
                </p>
            </div>
            <div class="flex gap-2">
                <button
                    @click="exportPdf"
                    :disabled="exportingPdf"
                    class="flex items-center gap-1.5 px-3 py-2 bg-slate-700 text-white text-sm font-semibold rounded-xl hover:bg-slate-600 transition disabled:opacity-50"
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
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                        />
                    </svg>
                    {{ exportingPdf ? "..." : "PDF" }}
                </button>
                <button
                    @click="exportExcel"
                    :disabled="exportingExcel"
                    class="flex items-center gap-1.5 px-3 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-500 transition disabled:opacity-50"
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
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                        />
                    </svg>
                    {{ exportingExcel ? "..." : "Excel" }}
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div
                class="relative p-4 overflow-hidden text-white shadow-lg rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-700 shadow-blue-500/20"
            >
                <div
                    class="text-xs font-semibold tracking-wider uppercase opacity-80"
                >
                    Total Modal
                </div>
                <div class="mt-1 text-xl font-black">
                    {{ formatCurrency(summary.total_modal) }}
                </div>
            </div>
            <div
                class="relative p-4 overflow-hidden text-white shadow-lg rounded-2xl bg-gradient-to-br from-emerald-500 via-green-600 to-teal-600 shadow-green-500/20"
            >
                <div
                    class="text-xs font-semibold tracking-wider uppercase opacity-80"
                >
                    Total Harga Jual
                </div>
                <div class="mt-1 text-xl font-black">
                    {{ formatCurrency(summary.total_harga_jual) }}
                </div>
            </div>
            <div
                class="relative p-4 overflow-hidden text-white shadow-lg rounded-2xl"
                :class="
                    summary.total_laba >= 0
                        ? 'bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600 shadow-purple-500/20'
                        : 'bg-gradient-to-br from-rose-500 via-red-600 to-pink-600 shadow-red-500/20'
                "
            >
                <div
                    class="text-xs font-semibold tracking-wider uppercase opacity-80"
                >
                    Total Laba
                </div>
                <div class="mt-1 text-xl font-black">
                    {{ formatCurrency(summary.total_laba) }}
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-4 bg-white border shadow-sm border-slate-200 rounded-2xl">
            <div
                class="flex flex-col items-center gap-3 sm:flex-row sm:items-end sm:justify-between"
            >
                <!-- Kiri: filter tanggal + reset -->
                <div class="flex flex-wrap items-end gap-2">
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-xs font-semibold tracking-wide uppercase text-slate-500"
                            >Dari Tanggal</label
                        >
                        <DateInput
                            v-model="filters.start_date"
                            class="w-40 px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-xs font-semibold tracking-wide uppercase text-slate-500"
                            >Sampai Tanggal</label
                        >
                        <DateInput
                            v-model="filters.end_date"
                            :min="filters.start_date"
                            class="w-40 px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400"
                        />
                    </div>
                    <!-- Reset icon -->
                    <button
                        @click="resetFilter"
                        title="Reset filter"
                        class="p-2 mb-1 transition border rounded-xl border-slate-300 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
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
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </button>
                </div>
                <!-- Kanan: cari + per halaman -->
                <div class="flex flex-wrap items-end gap-2">
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-xs font-semibold tracking-wide uppercase text-slate-500"
                            >Cari</label
                        >
                        <input
                            type="text"
                            v-model="filters.search"
                            placeholder="No invoice / pelanggan / IMEI..."
                            class="px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 w-52"
                        />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-xs font-semibold tracking-wide uppercase text-slate-500"
                            >Per Halaman</label
                        >
                        <select
                            v-model.number="perPage"
                            class="w-24 px-3 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                        >
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div
            class="overflow-hidden bg-white border shadow-sm border-slate-200 rounded-2xl"
        >
            <div class="px-5 py-3 border-b border-slate-100">
                <span class="text-sm font-bold text-slate-700">
                    Total {{ pagination.total.toLocaleString("id-ID") }} barang
                    terjual
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead class="table-header">
                        <tr>
                            <th class="px-2 py-2.5 text-center w-10">No</th>
                            <th class="px-2 py-2.5 text-left w-[9%]">
                                No Invoice
                            </th>
                            <th class="px-2 py-2.5 text-left w-[8%]">Kode</th>
                            <th class="px-2 py-2.5 text-left w-[7%]">
                                Tanggal
                            </th>
                            <th class="px-2 py-2.5 text-left w-[13%]">
                                Nama Produk
                            </th>
                            <th class="px-2 py-2.5 text-left w-[7%]">Merk</th>
                            <th class="px-2 py-2.5 text-left w-[5%]">Grade</th>
                            <th class="px-2 py-2.5 text-left w-[5%]">Satuan</th>
                            <th class="px-2 py-2.5 text-left w-[9%]">IMEI 1</th>
                            <th class="px-2 py-2.5 text-left w-[9%]">IMEI 2</th>
                            <th class="px-2 py-2.5 text-left w-[7%]">
                                Pelanggan
                            </th>
                            <th class="px-2 py-2.5 text-left w-[6%]">Kasir</th>
                            <th class="px-2 py-2.5 text-left w-[5%]">Sales</th>
                            <th class="px-2 py-2.5 text-right w-[7%]">Modal</th>
                            <th class="px-2 py-2.5 text-right w-[7%]">
                                Harga Jual
                            </th>
                            <th class="px-2 py-2.5 text-right w-[6%]">Laba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="isLoading">
                            <td
                                colspan="16"
                                class="py-16 text-center text-slate-400"
                            >
                                Memuat data...
                            </td>
                        </tr>
                        <tr v-else-if="rows.length === 0">
                            <td
                                colspan="16"
                                class="py-16 text-center text-slate-400"
                            >
                                Tidak ada data
                            </td>
                        </tr>
                        <tr
                            v-else
                            v-for="(row, idx) in rows"
                            :key="row.id"
                            class="transition-colors hover:bg-slate-50"
                        >
                            <td class="px-2 py-2 text-center text-slate-400">
                                {{
                                    (pagination.current_page - 1) *
                                        pagination.per_page +
                                    idx +
                                    1
                                }}
                            </td>
                            <td
                                class="px-2 py-2 font-bold leading-tight text-blue-600"
                            >
                                {{ row.no_invoice || "-" }}
                            </td>
                            <td
                                class="px-2 py-2 font-mono leading-tight text-slate-600"
                            >
                                {{ row.kode || "-" }}
                            </td>
                            <td
                                class="px-2 py-2 text-slate-600 whitespace-nowrap"
                            >
                                {{ formatDate(row.tanggal) }}
                            </td>
                            <td
                                class="px-2 py-2 font-semibold leading-tight text-slate-800"
                            >
                                {{ row.nama_produk || "-" }}
                            </td>
                            <td class="px-2 py-2 leading-tight text-slate-600">
                                {{ row.merk || "-" }}
                            </td>
                            <td class="px-2 py-2">
                                <span
                                    class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-bold text-[10px] whitespace-nowrap"
                                >
                                    {{ row.grade || "-" }}
                                </span>
                            </td>
                            <td class="px-2 py-2 text-slate-500">
                                {{ row.satuan || "-" }}
                            </td>
                            <td
                                class="px-2 py-2 font-mono leading-tight text-slate-500"
                            >
                                {{ row.imei1 || "-" }}
                            </td>
                            <td
                                class="px-2 py-2 font-mono leading-tight text-slate-500"
                            >
                                {{ row.imei2 || "-" }}
                            </td>
                            <td class="px-2 py-2 leading-tight text-slate-700">
                                {{ row.pelanggan || "-" }}
                            </td>
                            <td class="px-2 py-2 leading-tight text-slate-700">
                                {{ row.kasir || "-" }}
                            </td>
                            <td class="px-2 py-2 leading-tight text-slate-600">
                                {{ row.sales || "-" }}
                            </td>
                            <td class="px-2 py-2 text-right whitespace-nowrap">
                                <a
                                    v-if="authStore.isSuperAdmin || authStore.isAdmin"
                                    href="javascript:void(0)"
                                    @click="openEditModal(row)"
                                    class="font-semibold text-amber-600 hover:text-amber-700 hover:underline underline-offset-2 cursor-pointer"
                                    title="Klik untuk ubah harga modal"
                                >{{ formatCurrency(row.modal) }}</a>
                                <span v-else class="font-semibold text-blue-700">{{ formatCurrency(row.modal) }}</span>
                            </td>
                            <td
                                class="px-2 py-2 font-semibold text-right text-slate-700 whitespace-nowrap"
                            >
                                {{ formatCurrency(row.harga_jual) }}
                            </td>
                            <td
                                class="px-2 py-2 font-black text-right whitespace-nowrap"
                                :class="
                                    row.laba >= 0
                                        ? 'text-emerald-600'
                                        : 'text-rose-600'
                                "
                            >
                                {{ formatCurrency(row.laba) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot
                        v-if="rows.length > 0"
                        class="border-t-2 bg-slate-50 border-slate-300"
                    >
                        <tr>
                            <td
                                colspan="13"
                                class="px-3 py-2 font-black text-right text-slate-700"
                            >
                                TOTAL
                            </td>
                            <td
                                class="px-2 py-2 font-black text-right text-blue-700 whitespace-nowrap"
                            >
                                {{ formatCurrency(summary.total_modal) }}
                            </td>
                            <td
                                class="px-2 py-2 font-black text-right text-slate-700 whitespace-nowrap"
                            >
                                {{ formatCurrency(summary.total_harga_jual) }}
                            </td>
                            <td
                                class="px-2 py-2 font-black text-right whitespace-nowrap"
                                :class="
                                    summary.total_laba >= 0
                                        ? 'text-emerald-600'
                                        : 'text-rose-600'
                                "
                            >
                                {{ formatCurrency(summary.total_laba) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination -->
            <div
                class="flex flex-col items-center justify-between gap-3 px-5 py-3 border-t sm:flex-row border-slate-100"
            >
                <p class="text-xs text-slate-500">
                    Menampilkan
                    <span class="font-semibold">{{
                        (pagination.current_page - 1) * pagination.per_page + 1
                    }}</span>
                    –
                    <span class="font-semibold">{{
                        Math.min(
                            pagination.current_page * pagination.per_page,
                            pagination.total
                        )
                    }}</span>
                    dari
                    <span class="font-semibold">{{ pagination.total }}</span>
                    data
                </p>
                <div class="flex gap-2">
                    <button
                        @click="fetchData(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1.5 text-sm font-medium bg-white border border-slate-300 rounded-xl text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    >
                        &larr; Sebelumnya
                    </button>
                    <span class="px-3 py-1.5 text-sm font-bold text-slate-600">
                        {{ pagination.current_page }} /
                        {{ pagination.last_page }}
                    </span>
                    <button
                        @click="fetchData(pagination.current_page + 1)"
                        :disabled="
                            pagination.current_page === pagination.last_page
                        "
                        class="px-3 py-1.5 text-sm font-medium bg-white border border-slate-300 rounded-xl text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    >
                        Selanjutnya &rarr;
                    </button>
                </div>
            </div>
        </div>
    <!-- Edit Harga Modal Popup -->
    <div
        v-if="editModal.show"
        class="fixed inset-0 flex items-center justify-center bg-black/50 p-4"
        style="z-index: 9999"
        @click.self="editModal.show = false"
    >
        <div class="w-full max-w-sm overflow-hidden bg-white rounded-xl shadow-2xl">
            <div class="flex items-center justify-between px-4 py-3 text-white bg-amber-600">
                <h3 class="text-sm font-bold">Update Harga Modal</h3>
                <button @click="editModal.show = false" class="text-white hover:text-rose-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-5 py-5 flex flex-col gap-3">
                <div class="text-sm text-slate-600">
                    <span class="font-semibold text-slate-800">{{ editModal.row?.nama_produk }}</span>
                    <span class="ml-2 text-xs text-slate-400">{{ editModal.row?.no_invoice }}</span>
                </div>
                <div class="text-xs text-slate-500">
                    Harga lama: <span class="font-bold text-amber-600">{{ editModal.oldValueDisplay }}</span>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Harga Modal Baru</label>
                    <CurrencyInput v-model="editModal.newValue" :allowThousands="true" />
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100 bg-slate-50">
                <button
                    @click="editModal.show = false"
                    class="px-4 py-1.5 text-sm font-semibold text-slate-600 bg-slate-200 hover:bg-slate-300 rounded transition"
                >Batal</button>
                <button
                    @click="saveEdit"
                    :disabled="savingEdit"
                    class="px-4 py-1.5 text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 rounded transition disabled:opacity-50"
                >{{ savingEdit ? "Menyimpan..." : "Simpan" }}</button>
            </div>
        </div>
    </div>
    </div>
</template>
