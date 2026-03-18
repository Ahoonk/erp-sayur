<script setup>
import { computed, onMounted, ref, watch } from "vue";
import DateInput from "../../components/DateInput.vue";
import CurrencyInput from "../../components/CurrencyInput.vue";
import debounce from "lodash-es/debounce";
import api from "../../api";
import { useToast } from "../../composables/useToast";

const toast = useToast();

const isLoading = ref(false);
const exportingExcel = ref(false);
const rows = ref([]);
const suppliers = ref([]);
const search = ref("");
const perPage = ref(10);
const filters = ref({
    start_date: "",
    end_date: "",
    supplier_id: "",
});

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

const summary = ref({
    items_count: 0,
    total: 0,
});
const detailModal = ref({
    show: false,
    loading: false,
    data: null,
});
const detailPerPage = ref(10);
const detailPage = ref(1);

const detailItems = computed(() => detailModal.value.data?.items || []);

const detailPageCount = computed(() =>
    Math.max(1, Math.ceil(detailItems.value.length / detailPerPage.value))
);

const detailItemsPaged = computed(() => {
    const start = (detailPage.value - 1) * detailPerPage.value;
    return detailItems.value.slice(start, start + detailPerPage.value);
});

const detailTotals = computed(() =>
    detailItems.value.reduce(
        (acc, item) => {
            const qty = Number(item.qty || 0);
            const modal = qty * Number(item.harga_beli || 0);
            const hj = item.product?.is_sold
                ? Number(
                      item.product?.harga_jual_transaksi ||
                          item.product?.harga_jual ||
                          0
                  )
                : Number(item.product?.harga_jual || 0);
            const hargaJual = qty * hj;

            acc.qty += qty;
            acc.modal += modal;
            acc.harga_jual += hargaJual;
            return acc;
        },
        { qty: 0, modal: 0, harga_jual: 0 }
    )
);

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
    return `${d}-${m}-${y}`;
}

const statCards = computed(() => [
    {
        key: "items_count",
        label: "Total Barang",
        value: Number(summary.value.items_count || 0),
        isCurrency: false,
        wrapClass:
            "from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-blue-500/20",
        accentClass: "bg-blue-900/25",
    },
    {
        key: "total_modal",
        label: "Total Modal",
        value: Number(summary.value.total || 0),
        isCurrency: true,
        wrapClass:
            "from-amber-500 via-orange-500 to-amber-600 text-white shadow-amber-500/20",
        accentClass: "bg-orange-900/20",
    },
]);

function fileSafeDate(value) {
    return value ? value.replaceAll("-", "") : "all";
}

function buildDateSuffix(start, end) {
    if (!start && !end) return "all";
    const fmt = (d) => { const [y, m, day] = d.split("-"); return `${day}-${m}-${y}`; };
    if (start && end) return `${fmt(start)}_sd_${fmt(end)}`;
    return fmt(start || end);
}

function saveBlob(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
}

async function fetchSuppliers() {
    try {
        const { data } = await api.get("/suppliers/all");
        suppliers.value = data.data || [];
    } catch (error) {
        suppliers.value = [];
    }
}

async function fetchReport(page = 1) {
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

    isLoading.value = true;
    try {
        const { data } = await api.get("/reports/purchases", {
            params: {
                page,
                per_page: perPage.value,
                search: search.value,
                start_date: filters.value.start_date || undefined,
                end_date: filters.value.end_date || undefined,
                supplier_id: filters.value.supplier_id || undefined,
            },
        });

        rows.value = data.data.data || [];
        pagination.value = {
            current_page: data.data.current_page,
            last_page: data.data.last_page,
            per_page: data.data.per_page,
            total: data.data.total,
        };
        summary.value = {
            items_count: Number(data.data.summary?.items_count || 0),
            total: Number(data.data.summary?.total || 0),
        };
    } catch (error) {
        toast.error("Gagal memuat laporan pembelian");
    } finally {
        isLoading.value = false;
    }
}

async function openInvoiceDetail(row) {
    detailModal.value.show = true;
    detailModal.value.loading = true;
    detailModal.value.data = null;
    detailPerPage.value = 10;
    detailPage.value = 1;
    try {
        const { data } = await api.get(`/purchases/${row.id}`);
        detailModal.value.data = data.data;
    } catch (error) {
        toast.error("Gagal memuat detail invoice");
        detailModal.value.show = false;
    } finally {
        detailModal.value.loading = false;
    }
}

function closeInvoiceDetail() {
    detailModal.value.show = false;
}

// Edit harga modal
const editModal = ref({
    show: false,
    item: null,
    purchaseId: null,
    oldValueDisplay: "",
    newValue: 0,
});
const savingEdit = ref(false);

function openEditModal(item) {
    editModal.value.item = item;
    editModal.value.purchaseId = detailModal.value.data?.id;
    editModal.value.oldValueDisplay = formatCurrency(item.harga_beli);
    editModal.value.newValue = item.harga_beli || 0;
    editModal.value.show = true;
}

async function saveEdit() {
    if (!editModal.value.item) return;
    const item = editModal.value.item;
    savingEdit.value = true;
    try {
        await api.put(
            `/purchases/${editModal.value.purchaseId}/items/${item.id}`,
            {
                harga_beli: editModal.value.newValue,
            }
        );
        item.harga_beli = editModal.value.newValue;
        editModal.value.show = false;
        toast.success("Harga modal berhasil diubah");
        // Update main table row immediately
        const row = rows.value.find((r) => r.id === editModal.value.purchaseId);
        if (row) {
            const newTotal = detailItems.value.reduce(
                (sum, i) =>
                    sum + Number(i.qty || 0) * Number(i.harga_beli || 0),
                0
            );
            row.total = newTotal;
        }
    } catch (err) {
        toast.error(
            err.response?.data?.message || "Gagal mengubah harga modal"
        );
    } finally {
        savingEdit.value = false;
    }
}

function changeDetailPage(page) {
    if (page < 1 || page > detailPageCount.value) return;
    detailPage.value = page;
}

watch(detailPerPage, () => {
    detailPage.value = 1;
});

const debouncedSearch = debounce(() => fetchReport(1), 400);
watch(search, debouncedSearch);
watch(perPage, () => fetchReport(1));
watch(
    () => filters.value,
    () => fetchReport(1),
    { deep: true }
);

function resetFilters() {
    filters.value = { start_date: "", end_date: "", supplier_id: "" };
    search.value = "";
    fetchReport(1);
}

async function exportExcel() {
    if (exportingExcel.value) return;
    exportingExcel.value = true;
    try {
        const response = await api.get("/reports/purchases", {
            params: {
                search: search.value,
                start_date: filters.value.start_date || undefined,
                end_date: filters.value.end_date || undefined,
                supplier_id: filters.value.supplier_id || undefined,
                export: "excel",
            },
            responseType: "blob",
        });

        saveBlob(
            response.data,
            `laporan-pembelian-${buildDateSuffix(filters.value.start_date, filters.value.end_date)}.xlsx`
        );
    } catch (error) {
        toast.error("Gagal export Excel");
    } finally {
        exportingExcel.value = false;
    }
}

function buildPdfHtml(dataRows, footerSummary) {
    const bodyRows = dataRows
        .map(
            (row, idx) => `
                <tr>
                    <td>${idx + 1}</td>
                    <td>${row.no_invoice || "-"}</td>
                    <td>${formatDate(row.tanggal)}</td>
                    <td>${row.supplier || "-"}</td>
                    <td style="text-align:center;">${row.items_count || 0}</td>
                    <td>${row.keterangan || "-"}</td>
                    <td style="text-align:left;">${formatCurrency(
                        row.total
                    )}</td>
                </tr>`
        )
        .join("");

    return `
        <html>
        <head>
            <title>Laporan Pembelian</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; color: #0f172a; }
                h1 { margin: 0 0 8px; font-size: 22px; }
                p { margin: 0 0 16px; font-size: 12px; color: #475569; }
                table { width: 100%; border-collapse: collapse; font-size: 12px; }
                th, td { border: 1px solid #cbd5e1; padding: 8px; }
                th { background: #f8fafc; text-align: left; }
                tfoot td { background: #e2eef6; font-weight: 700; }
            </style>
        </head>
        <body>
            <h1>Laporan Pembelian</h1>
            <p>Periode: ${formatDate(filters.value.start_date) || "-"} s/d ${
        formatDate(filters.value.end_date) || "-"
    } | Supplier: ${
        suppliers.value.find((s) => s.id == filters.value.supplier_id)?.nama ||
        "Semua"
    }</p>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No Invoice</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>Item</th>
                        <th>Keterangan</th>
                        <th>Modal</th>
                    </tr>
                </thead>
                <tbody>${bodyRows}</tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:center;">TOTAL</td>
                        <td style="text-align:center;">${
                            footerSummary.items_count || 0
                        }</td>
                        <td></td>
                        <td style="text-align:left;">${formatCurrency(
                            footerSummary.total || 0
                        )}</td>
                    </tr>
                </tfoot>
            </table>
        </body>
        </html>
    `;
}

async function exportPdf() {
    try {
        const { data } = await api.get("/reports/purchases", {
            params: {
                page: 1,
                per_page: -1, // Get all data from server
                search: search.value,
                start_date: filters.value.start_date || undefined,
                end_date: filters.value.end_date || undefined,
                supplier_id: filters.value.supplier_id || undefined,
            },
        });

        const allRows = data.data.data || [];
        const printWindow = window.open("", "_blank");
        if (!printWindow) {
            toast.error("Popup diblokir browser");
            return;
        }
        printWindow.document.write(
            buildPdfHtml(allRows, data.data.summary || summary.value)
        );
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    } catch (error) {
        toast.error("Gagal export PDF");
    }
}

onMounted(() => {
    fetchSuppliers();
    fetchReport(1);
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4">
            <div
                class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200"
            >
                <h1 class="text-2xl font-black text-slate-800">
                    Laporan Pembelian
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan invoice supplier per periode.
                </p>
            </div>
            <div class="grid w-full max-w-2xl grid-cols-1 gap-3 md:grid-cols-2">
                <div
                    v-for="card in statCards"
                    :key="card.key"
                    class="relative overflow-hidden rounded-2xl shadow-lg p-4 min-h-[94px] bg-gradient-to-br"
                    :class="card.wrapClass"
                >
                    <div class="relative z-10">
                        <div class="text-xl font-black tracking-wide">
                            {{
                                card.isCurrency
                                    ? formatCurrency(card.value)
                                    : new Intl.NumberFormat("id-ID").format(
                                          card.value
                                      )
                            }}
                        </div>
                        <div class="mt-1 text-sm font-semibold/5 opacity-95">
                            {{ card.label }}
                        </div>
                    </div>
                    <div
                        class="absolute inset-y-0 right-0 flex items-end gap-1 pb-3 pr-3 opacity-60"
                    >
                        <span
                            class="w-2 h-3 rounded-sm"
                            :class="card.accentClass"
                        ></span>
                        <span
                            class="w-2 h-6 rounded-sm"
                            :class="card.accentClass"
                        ></span>
                        <span
                            class="w-2 h-10 rounded-sm"
                            :class="card.accentClass"
                        ></span>
                        <span
                            class="w-2 h-5 rounded-sm"
                            :class="card.accentClass"
                        ></span>
                        <span
                            class="w-2 h-8 rounded-sm"
                            :class="card.accentClass"
                        ></span>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200"
        >
            <div
                class="flex flex-col items-start justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 md:flex-row md:items-center"
            >
                <!-- Left: Filters -->
                <div
                    class="grid w-full grid-cols-2 gap-3 md:grid-cols-4 lg:flex md:w-auto"
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
                    <div class="flex flex-col col-span-2 gap-1 md:col-span-1">
                        <label
                            class="text-[10px] font-bold text-slate-400 uppercase"
                            >Supplier</label
                        >
                        <div class="relative">
                            <select
                                v-model="filters.supplier_id"
                                class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white pr-8 min-w-[140px]"
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

                <!-- Right: Per Page, Search, Export -->
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
                                v-model="search"
                                type="text"
                                placeholder="Cari invoice/supplier..."
                                class="block w-full sm:w-56 pl-10 pr-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm"
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
                    <div class="flex items-end gap-2 pb-0.5">
                        <button
                            @click="exportPdf"
                            class="px-3 py-1.5 bg-slate-700 text-white text-sm font-medium rounded-lg hover:bg-slate-600 transition"
                        >
                            PDF
                        </button>
                        <button
                            @click="exportExcel"
                            :disabled="exportingExcel"
                            class="px-3 py-1.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-500 transition disabled:opacity-50"
                        >
                            {{ exportingExcel ? "..." : "Excel" }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-0 rounded-none shadow-none table-container">
                <table class="table-fixed-layout table-wide">
                    <thead class="table-header">
                        <tr class="">
                            <th class="w-16 text-center">No</th>
                            <th class="">No Invoice</th>
                            <th class="">Tanggal</th>
                            <th class="">Supplier</th>
                            <th class="text-center">Item</th>
                            <th class="">Keterangan</th>
                            <th class="text-right">Modal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="isLoading">
                            <td
                                colspan="7"
                                class="px-4 py-16 text-center text-slate-400"
                            >
                                Memuat data...
                            </td>
                        </tr>
                        <tr v-else-if="rows.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-16 text-center text-slate-400"
                            >
                                Tidak ada data
                            </td>
                        </tr>
                        <tr
                            v-else
                            v-for="(row, idx) in rows"
                            :key="row.id"
                            class="table-row cursor-pointer hover:bg-blue-50"
                            @click="openInvoiceDetail(row)"
                        >
                            <td class="table-cell text-center text-slate-500">
                                {{
                                    (pagination.current_page - 1) *
                                        pagination.per_page +
                                    idx +
                                    1
                                }}
                            </td>
                            <td class="table-cell font-bold text-blue-600">
                                {{ row.no_invoice }}
                            </td>
                            <td class="table-cell">
                                {{ formatDate(row.tanggal) }}
                            </td>
                            <td class="table-cell">
                                {{ row.supplier || "-" }}
                            </td>
                            <td
                                class="table-cell font-bold text-center text-slate-700"
                            >
                                {{ row.items_count }}
                            </td>
                            <td class="table-cell text-slate-500">
                                {{ row.keterangan || "-" }}
                            </td>
                            <td
                                class="table-cell font-black text-right text-amber-600"
                            >
                                {{ formatCurrency(row.total) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length > 0">
                        <tr class="border-t bg-sky-50 border-slate-200">
                            <td
                                colspan="4"
                                class="table-cell font-black tracking-wider text-center uppercase"
                            >
                                Total
                            </td>
                            <td class="table-cell font-black text-center">
                                {{ summary.items_count }}
                            </td>
                            <td class="table-cell"></td>
                            <td
                                class="table-cell font-black text-right text-amber-700"
                            >
                                {{ formatCurrency(summary.total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div
                v-if="pagination.last_page > 1"
                class="flex flex-col gap-3 px-6 py-3 border-t border-slate-200 bg-slate-50 sm:flex-row sm:items-center sm:justify-between"
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
                        @click="fetchReport(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1 text-sm font-medium bg-white border rounded border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Sebelumnya
                    </button>
                    <button
                        @click="fetchReport(pagination.current_page + 1)"
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

        <Teleport to="body">
            <div
                v-if="detailModal.show"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
                @click.self="closeInvoiceDetail"
            >
                <div
                    class="w-full max-w-[95vw] xl:max-w-[1400px] overflow-hidden bg-white shadow-2xl rounded-2xl"
                >
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b bg-slate-50 border-slate-200"
                    >
                        <div>
                            <h3 class="text-base font-bold text-slate-800">
                                Detail Invoice Pembelian
                            </h3>
                            <p
                                v-if="detailModal.data"
                                class="text-xs text-slate-500"
                            >
                                {{ detailModal.data.no_invoice }} -
                                {{ formatDate(detailModal.data.tanggal) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <label
                                    class="text-[11px] font-bold tracking-wider uppercase text-slate-500"
                                    >Tampilkan</label
                                >
                                <select
                                    v-model.number="detailPerPage"
                                    class="px-2 py-1 text-xs bg-white border rounded border-slate-300 text-slate-700"
                                >
                                    <option :value="10">10</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                            </div>
                            <button
                                type="button"
                                class="text-slate-400 hover:text-rose-500"
                                @click="closeInvoiceDetail"
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
                    </div>

                    <div class="p-5 max-h-[88vh] overflow-hidden">
                        <div
                            v-if="detailModal.loading"
                            class="py-12 text-center text-slate-500"
                        >
                            Memuat detail...
                        </div>
                        <div
                            v-else-if="!detailModal.data"
                            class="py-12 text-center text-slate-500"
                        >
                            Detail tidak tersedia.
                        </div>
                        <div v-else>
                            <div
                                class="grid grid-cols-1 gap-3 mb-4 text-xs md:grid-cols-3"
                            >
                                <div class="p-3 rounded-lg bg-slate-50">
                                    <div class="text-slate-500">Supplier</div>
                                    <div class="font-bold text-slate-800">
                                        {{
                                            detailModal.data.supplier?.nama ||
                                            "-"
                                        }}
                                    </div>
                                </div>

                                <div class="p-3 rounded-lg bg-slate-50">
                                    <div class="text-slate-500">Keterangan</div>
                                    <div class="font-bold text-slate-800">
                                        {{ detailModal.data.keterangan || "-" }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="overflow-auto border rounded-lg max-h-[52vh] custom-scrollbar border-slate-200 lg:overflow-x-hidden"
                            >
                                <table class="w-full table-fixed text-[11px]">
                                    <thead
                                        class="sticky top-0 bg-slate-50 text-slate-600"
                                    >
                                        <tr>
                                            <th
                                                class="w-[20%] px-2 py-1.5 text-left"
                                            >
                                                Produk
                                            </th>
                                            <th
                                                class="w-[11%] px-2 py-1.5 text-left"
                                            >
                                                Kode
                                            </th>
                                            <th
                                                class="w-[10%] px-2 py-1.5 text-left"
                                            >
                                                Merk
                                            </th>
                                            <th
                                                class="w-[9%] px-2 py-1.5 text-left"
                                            >
                                                IMEI 1
                                            </th>
                                            <th
                                                class="w-[9%] px-2 py-1.5 text-left"
                                            >
                                                IMEI 2
                                            </th>
                                            <th
                                                class="w-[9%] px-2 py-1.5 text-left"
                                            >
                                                Satuan
                                            </th>
                                            <th
                                                class="w-[8%] px-2 py-1.5 text-left"
                                            >
                                                Grade
                                            </th>

                                            <th
                                                class="w-[5%] px-2 py-1.5 text-center"
                                            >
                                                Qty
                                            </th>
                                            <th
                                                class="w-[10%] px-2 py-1.5 text-right"
                                            >
                                                Modal
                                            </th>
                                            <th
                                                class="w-[9%] px-2 py-1.5 text-right"
                                            >
                                                Harga Jual
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="item in detailItemsPaged"
                                            :key="item.id"
                                            class="border-t border-slate-100"
                                        >
                                            <td
                                                class="px-2 py-1.5 font-medium text-slate-800 truncate"
                                                :title="
                                                    item.product?.nama || '-'
                                                "
                                            >
                                                {{ item.product?.nama || "-" }}
                                            </td>
                                            <td
                                                class="px-2 py-1.5 font-mono text-slate-600 truncate"
                                                :title="
                                                    item.product?.barcode || '-'
                                                "
                                            >
                                                {{
                                                    item.product?.barcode || "-"
                                                }}
                                            </td>
                                            <td
                                                class="px-2 py-1.5 text-slate-700 truncate"
                                                :title="
                                                    item.product?.brand || '-'
                                                "
                                            >
                                                {{ item.product?.brand || "-" }}
                                            </td>
                                            <td
                                                class="px-2 py-1.5 font-mono text-slate-700 truncate"
                                                :title="
                                                    item.product?.imei1 || '-'
                                                "
                                            >
                                                {{ item.product?.imei1 || "-" }}
                                            </td>
                                            <td
                                                class="px-2 py-1.5 font-mono text-slate-700 truncate"
                                                :title="
                                                    item.product?.imei2 || '-'
                                                "
                                            >
                                                {{ item.product?.imei2 || "-" }}
                                            </td>
                                            <td
                                                class="px-2 py-1.5 text-slate-700 truncate"
                                                :title="
                                                    item.product?.unit || '-'
                                                "
                                            >
                                                {{ item.product?.unit || "-" }}
                                            </td>
                                            <td
                                                class="px-2 py-1.5 text-slate-700 truncate"
                                                :title="
                                                    item.product?.grade || '-'
                                                "
                                            >
                                                {{ item.product?.grade || "-" }}
                                            </td>

                                            <td
                                                class="px-2 py-1.5 font-bold text-center text-slate-700"
                                            >
                                                {{ item.qty || 0 }}
                                            </td>
                                            <td
                                                class="px-2 py-1.5 text-right text-blue-700"
                                            >
                                                <a
                                                    href="javascript:void(0)"
                                                    @click="openEditModal(item)"
                                                    class="cursor-pointer hover:underline underline-offset-2"
                                                    title="Klik untuk ubah harga modal"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            item.harga_beli || 0
                                                        )
                                                    }}
                                                </a>
                                            </td>
                                            <td
                                                class="px-2 py-1.5 text-right"
                                                :class="
                                                    item.product?.is_sold
                                                        ? 'text-emerald-700'
                                                        : 'text-slate-500'
                                                "
                                            >
                                                {{
                                                    formatCurrency(
                                                        item.product?.is_sold
                                                            ? item.product
                                                                  ?.harga_jual_transaksi ||
                                                                  item.product
                                                                      ?.harga_jual ||
                                                                  0
                                                            : item.product
                                                                  ?.harga_jual ||
                                                                  0
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot
                                        class="border-t bg-slate-50 border-slate-200"
                                    >
                                        <tr>
                                            <td
                                                colspan="7"
                                                class="px-2 py-1.5 font-bold text-right text-slate-700"
                                            >
                                                Total
                                            </td>
                                            <td
                                                class="px-2 py-1.5 font-black text-center text-slate-800"
                                            >
                                                {{ detailTotals.qty }}
                                            </td>
                                            <td
                                                class="px-2 py-1.5 font-black text-right text-blue-700"
                                            >
                                                {{
                                                    formatCurrency(
                                                        detailTotals.modal
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="px-2 py-1.5 font-black text-right text-emerald-700"
                                            >
                                                {{
                                                    formatCurrency(
                                                        detailTotals.harga_jual
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div
                                class="flex items-center justify-between mt-3 text-xs text-slate-500"
                            >
                                <span>
                                    Menampilkan
                                    {{
                                        detailItems.length
                                            ? (detailPage - 1) * detailPerPage +
                                              1
                                            : 0
                                    }}
                                    s/d
                                    {{
                                        Math.min(
                                            detailPage * detailPerPage,
                                            detailItems.length
                                        )
                                    }}
                                    dari {{ detailItems.length }} item
                                </span>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="px-2 py-1 bg-white border rounded border-slate-300 disabled:opacity-50"
                                        :disabled="detailPage <= 1"
                                        @click="
                                            changeDetailPage(detailPage - 1)
                                        "
                                    >
                                        Sebelumnya
                                    </button>
                                    <span class="font-bold text-slate-700">
                                        {{ detailPage }} / {{ detailPageCount }}
                                    </span>
                                    <button
                                        type="button"
                                        class="px-2 py-1 bg-white border rounded border-slate-300 disabled:opacity-50"
                                        :disabled="
                                            detailPage >= detailPageCount
                                        "
                                        @click="
                                            changeDetailPage(detailPage + 1)
                                        "
                                    >
                                        Selanjutnya
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Edit Harga Modal (di dalam Teleport yang sama agar z-index bekerja) -->
            <div
                v-if="editModal.show"
                class="fixed inset-0 flex items-center justify-center p-4 bg-black/50"
                style="z-index: 9999"
                @click.self="editModal.show = false"
            >
                <div
                    class="w-full max-w-sm overflow-hidden bg-white shadow-2xl rounded-xl"
                >
                    <div
                        class="flex items-center justify-between px-4 py-3 text-white bg-blue-600"
                    >
                        <h3 class="text-sm font-bold">Update Harga Modal</h3>
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
                                />
                            </svg>
                        </button>
                    </div>
                    <div class="flex flex-col gap-3 px-5 py-5">
                        <div class="text-sm text-slate-600">
                            <span class="font-semibold text-slate-800">{{
                                editModal.item?.product?.nama
                            }}</span>
                        </div>
                        <div class="text-xs text-slate-500">
                            Harga lama:
                            <span class="font-bold text-blue-600">{{
                                editModal.oldValueDisplay
                            }}</span>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-bold text-slate-700 mb-1.5"
                                >Harga Modal Baru</label
                            >
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
                            class="px-4 py-1.5 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded transition"
                        >
                            Batal
                        </button>
                        <button
                            @click="saveEdit"
                            :disabled="savingEdit"
                            class="px-4 py-1.5 text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 rounded transition disabled:opacity-50"
                        >
                            {{ savingEdit ? "Menyimpan..." : "Simpan" }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
