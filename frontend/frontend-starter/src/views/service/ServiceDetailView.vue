<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../../api";
import { useToast } from "../../composables/useToast";
import ConfirmDialog from "../../components/ConfirmDialog.vue";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const serviceId = route.params.id;
const service = ref(null);
const isLoading = ref(true);
const isUpdatingStatus = ref(false);
const cancelModalOpen = ref(false);
const savingPayment = ref(false);

// Spareparts management
const showAddPart = ref(false);
const partSearch = ref("");
const partResults = ref([]);
const isSearchingPart = ref(false);
const selectedPart = ref(null);
const showPartDropdown = ref(false);
const partSearchWrapper = ref(null);
const partForm = ref({
    qty: 1,
    harga_satuan: 0,
});
const paymentForm = ref({
    discount_type: "percent",
    discount_value: 0,
    metode_pembayaran: "cash",
    jumlah_bayar: 0,
});
const discountNominalDisplay = ref("");
const amountPaidDisplay = ref("");

const confirmState = ref({
    show: false,
    action: null,
    payload: null,
    title: "",
    message: "",
    confirmText: "",
    loadingText: "",
});

function closeConfirm() {
    confirmState.value.show = false;
}

async function fetchService(quiet = false) {
    if (!quiet) isLoading.value = true;
    try {
        const { data } = await api.get(`/services/${serviceId}`);
        service.value = data.data;
        const diskonPersen = Number(
            service.value.transaction?.diskon_persen || 0,
        );
        const diskonNominal = Number(
            service.value.transaction?.diskon_nominal || 0,
        );
        paymentForm.value.discount_type =
            diskonPersen > 0 ? "percent" : "nominal";
        paymentForm.value.discount_value =
            paymentForm.value.discount_type === "percent"
                ? diskonPersen
                : diskonNominal;
        paymentForm.value.metode_pembayaran =
            service.value.transaction?.metode_pembayaran || "cash";
        paymentForm.value.jumlah_bayar = Number(
            service.value.transaction?.jumlah_bayar ||
                service.value.grand_total ||
                0,
        );
        discountNominalDisplay.value = formatNumberInput(diskonNominal);
        amountPaidDisplay.value = formatNumberInput(
            paymentForm.value.jumlah_bayar,
        );
    } catch (err) {
        toast.error("Gagal memuat detail service");
    } finally {
        if (!quiet) isLoading.value = false;
    }
}

async function updateStatus(status) {
    if (status === "batal") {
        cancelModalOpen.value = true;
        return;
    }
    const payload = { status };

    isUpdatingStatus.value = true;
    try {
        await api.patch(`/services/${serviceId}/status`, payload);
        toast.success(`Status perbaikan diubah ke ${statusLabels[status]}`);
        await fetchService(true);
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal mengubah status");
    } finally {
        isUpdatingStatus.value = false;
    }
}

function confirmHandover() {
    confirmState.value = {
        show: true,
        action: "handover",
        payload: null,
        title: "Serahkan Unit",
        message: "Konfirmasi penyerahan unit ke pelanggan?",
        confirmText: "Serahkan",
        loadingText: "Memproses...",
    };
}

async function doHandover() {
    isUpdatingStatus.value = true;
    try {
        await api.patch(`/services/${serviceId}/status`, {
            status_pengambilan: "sudah_diambil",
        });
        toast.success("Unit telah diserahkan ke pelanggan");
        await fetchService(true);
    } catch (err) {
        toast.error("Gagal memproses penyerahan");
    } finally {
        isUpdatingStatus.value = false;
        closeConfirm();
    }
}

async function searchParts() {
    isSearchingPart.value = true;
    try {
        const { data } = await api.get("/products/search", {
            params: {
                keyword: partSearch.value || "",
                category: "Sparepart",
            },
        });
        partResults.value = (data.data || [])
            .filter((item) =>
                String(item?.category || "")
                    .toLowerCase()
                    .includes("sparepart"),
            )
            .slice(0, 6);
    } catch (e) {
        console.error(e);
        partResults.value = [];
    } finally {
        isSearchingPart.value = false;
    }
}

function handlePartFocus() {
    showPartDropdown.value = true;
    searchParts();
}

function closeAddPartModal() {
    showAddPart.value = false;
    showPartDropdown.value = false;
    partSearch.value = "";
    partResults.value = [];
    selectedPart.value = null;
}

function handleDocumentClick(event) {
    if (!showAddPart.value) return;
    if (!partSearchWrapper.value) return;
    if (!partSearchWrapper.value.contains(event.target)) {
        showPartDropdown.value = false;
    }
}

function selectPart(product) {
    selectedPart.value = product;
    partForm.value.harga_satuan = product.harga_jual;
    partForm.value.qty = 1;
    partResults.value = [];
    partSearch.value = "";
    showPartDropdown.value = false;
}

async function handleAddPart() {
    try {
        await api.post(`/services/${serviceId}/parts`, {
            product_id: selectedPart.value.id,
            qty: partForm.value.qty,
            harga_satuan: partForm.value.harga_satuan,
        });
        toast.success("Sparepart berhasil ditambahkan");
        closeAddPartModal();
        fetchService(true);
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal menambah sparepart");
    }
}

function confirmRemovePart(partId) {
    confirmState.value = {
        show: true,
        action: "removePart",
        payload: partId,
        title: "Hapus Sparepart",
        message: "Hapus sparepart ini dari list?",
        confirmText: "Hapus",
        loadingText: "Menghapus...",
    };
}

async function doRemovePart(partId) {
    isUpdatingStatus.value = true;
    try {
        await api.delete(`/services/${serviceId}/parts/${partId}`);
        toast.success("Sparepart dihapus");
        fetchService(true);
    } catch (err) {
        toast.error("Gagal menghapus sparepart");
    } finally {
        isUpdatingStatus.value = false;
        closeConfirm();
    }
}

async function handleConfirm() {
    if (confirmState.value.action === "handover") {
        await doHandover();
    } else if (confirmState.value.action === "removePart") {
        await doRemovePart(confirmState.value.payload);
    }
}

async function cancelService(restoreParts) {
    isUpdatingStatus.value = true;
    try {
        await api.patch(`/services/${serviceId}/status`, {
            status: "batal",
            restore_parts: restoreParts,
        });
        toast.success(
            restoreParts
                ? "Service dibatalkan dan sparepart dikembalikan ke stok"
                : "Service dibatalkan tanpa pengembalian sparepart ke stok",
        );
        cancelModalOpen.value = false;
        await fetchService(true);
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal membatalkan service");
    } finally {
        isUpdatingStatus.value = false;
    }
}

const paymentSummary = computed(() => {
    const subtotal = Number(service.value?.grand_total || 0);
    const diskonNominal =
        paymentForm.value.discount_type === "percent"
            ? subtotal * (Number(paymentForm.value.discount_value || 0) / 100)
            : Number(paymentForm.value.discount_value || 0);
    const grandTotal = Math.max(subtotal - diskonNominal, 0);
    const jumlahBayar =
        paymentForm.value.metode_pembayaran === "cash"
            ? Number(paymentForm.value.jumlah_bayar || 0)
            : grandTotal;
    const kembalian = Math.max(jumlahBayar - grandTotal, 0);

    return {
        subtotal,
        diskonNominal,
        grandTotal,
        jumlahBayar,
        kembalian,
    };
});

async function savePayment() {
    if (paymentLocked.value) return;
    savingPayment.value = true;
    try {
        const diskonPersen =
            paymentForm.value.discount_type === "percent"
                ? Number(paymentForm.value.discount_value || 0)
                : 0;
        const diskonNominal =
            paymentForm.value.discount_type === "nominal"
                ? Number(paymentForm.value.discount_value || 0)
                : 0;

        await api.patch(`/services/${serviceId}/status`, {
            diskon_persen: diskonPersen,
            diskon_nominal: diskonNominal,
            metode_pembayaran: paymentForm.value.metode_pembayaran,
            jumlah_bayar:
                paymentForm.value.metode_pembayaran === "cash"
                    ? Number(paymentForm.value.jumlah_bayar || 0)
                    : paymentSummary.value.grandTotal,
        });
        toast.success("Pembayaran service berhasil disimpan");
        await fetchService(true);
        if (service.value?.transaction?.id) {
            window.open(
                `/dashboard/pos/${service.value.transaction.id}/invoice`,
                "_blank",
            );
        }
    } catch (err) {
        toast.error(
            err.response?.data?.message || "Gagal menyimpan pembayaran",
        );
    } finally {
        savingPayment.value = false;
    }
}

const paymentLocked = computed(() => {
    return service.value?.status_pengambilan === "sudah_diambil";
});

function formatNumberInput(value) {
    const num = Number(value || 0);
    return new Intl.NumberFormat("id-ID").format(num);
}

function parseNumberInput(value) {
    const clean = String(value || "").replace(/[^\d]/g, "");
    return Number(clean || 0);
}

function onDiscountNominalInput(event) {
    const num = parseNumberInput(event.target.value);
    paymentForm.value.discount_value = num;
    discountNominalDisplay.value = formatNumberInput(num);
}

function onAmountPaidInput(event) {
    const num = parseNumberInput(event.target.value);
    paymentForm.value.jumlah_bayar = num;
    amountPaidDisplay.value = formatNumberInput(num);
}

function onDiscountTypeChange() {
    paymentForm.value.discount_value = 0;
    discountNominalDisplay.value = "0";
}

const printService = () => {
    window.open(`/dashboard/services/${serviceId}/print`, "_blank");
};

onMounted(() => {
    fetchService();
    document.addEventListener("click", handleDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleDocumentClick);
});

watch(partSearch, () => {
    if (!showAddPart.value) return;
    if (!showPartDropdown.value) showPartDropdown.value = true;
    searchParts();
});

// Helpers
const isFinalInvoice = computed(() => {
    return service.value && service.value.status === "selesai";
});

const statusBadges = {
    pending: "bg-slate-100 text-slate-700",
    dikerjakan: "bg-blue-100 text-blue-700",
    selesai: "bg-emerald-100 text-emerald-700",
    batal: "bg-red-100 text-red-700",
};

const statusLabels = {
    dikerjakan: "Proses",
    selesai: "Selesai",
    batal: "Batal",
};

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
</script>

<template>
    <div class="px-4 md:px-8 w-full mx-auto space-y-6 pb-20">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
            <div class="flex items-center gap-4">
                <button
                    @click="router.back()"
                    class="p-2.5 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm"
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
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                </button>
                <div v-if="service" class="flex-1">
                    <h1
                        class="text-2xl font-bold tracking-tight text-slate-800"
                    >
                        Detail Service #{{
                            service.no_service || service.id.substring(0, 8)
                        }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 mt-1.5">
                        <span
                            :class="statusBadges[service.status]"
                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border border-current/10"
                        >
                            {{ statusLabels[service.status] }}
                        </span>
                        <!-- Handover Status Indicator -->
                        <div
                            v-if="
                                service.status_pengambilan === 'sudah_diambil'
                            "
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="3"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            SUDAH DIAMBIL
                        </div>
                        <div
                            v-else
                            class="px-3 py-1.5 bg-slate-100 text-slate-500 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-200"
                        >
                            BELUM DIAMBIL
                        </div>

                        <!-- Technician Header Badge -->
                        <div
                            v-if="service.technician"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-indigo-500/20"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            TEKNISI: {{ service.technician.nama }}
                        </div>

                        <span
                            class="text-slate-400 text-[11px] font-bold uppercase tracking-wider ml-1"
                        >
                            - Tgl Masuk: {{ formatDate(service.tanggal_masuk) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons based on status -->
            <div v-if="service" class="flex flex-wrap items-center gap-3">
                <!-- Status Toggle: Proses, Selesai, Batal -->
                <div
                    v-if="service.status_pengambilan !== 'sudah_diambil'"
                    class="flex items-center p-1 border bg-slate-100 rounded-2xl border-slate-200"
                >
                    <button
                        v-for="key in ['dikerjakan', 'selesai', 'batal']"
                        :key="key"
                        @click="updateStatus(key)"
                        :disabled="
                            isUpdatingStatus ||
                            service.status === key ||
                            (service.status === 'selesai' &&
                                key === 'dikerjakan')
                        "
                        :class="[
                            service.status === key
                                ? 'bg-white text-blue-600 shadow-sm border-slate-200'
                                : 'text-slate-500 hover:text-slate-700 hover:bg-white/50 border-transparent',
                            'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all flex items-center gap-2',
                        ]"
                    >
                        <div
                            v-if="service.status === key"
                            class="w-1.5 h-1.5 rounded-full bg-blue-500"
                        ></div>
                        {{ statusLabels[key] }}
                    </button>
                </div>

                <!-- Penyerahan Button -->
                <button
                    v-if="
                        ['selesai', 'batal'].includes(service.status) &&
                        service.status_pengambilan === 'belum_diambil'
                    "
                    @click="confirmHandover"
                    :disabled="isUpdatingStatus"
                    class="bg-purple-600 text-white px-6 py-2.5 rounded-xl text-sm font-black uppercase tracking-widest shadow-xl shadow-purple-500/20 hover:bg-purple-700 disabled:opacity-50 transition-all active:scale-95 flex items-center gap-2"
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
                            stroke-width="2.5"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    Serahkan Ke Pelanggan
                </button>

                <div
                    v-if="service.status_pengambilan !== 'sudah_diambil'"
                    class="w-px h-8 mx-1 bg-slate-200"
                ></div>

                <button
                    v-if="service.status !== 'selesai'"
                    @click="printService"
                    class="bg-white border-2 border-slate-100 text-slate-700 px-6 py-2.5 rounded-xl text-sm font-black uppercase tracking-widest shadow-sm hover:bg-slate-50 transition-all inline-flex items-center gap-2 active:scale-95"
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
                            stroke-width="2.5"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                        />
                    </svg>
                    Cetak
                    {{ isFinalInvoice ? "Nota / Invoice" : "Tanda Terima" }}
                </button>
            </div>
        </div>

        <div
            v-if="isLoading"
            class="p-24 text-center bg-white border shadow-sm rounded-3xl border-slate-200"
        >
            <div class="flex justify-center mb-6">
                <div
                    class="w-12 h-12 border-4 rounded-full border-blue-50 border-t-blue-500 animate-spin"
                ></div>
            </div>
            <p
                class="text-xs font-black tracking-widest uppercase text-slate-500"
            >
                Memuat detail perbaikan...
            </p>
        </div>

        <div v-else-if="service" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Side: Device & Info -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Customer Card -->
                <div
                    class="overflow-hidden transition-all bg-white border shadow-sm rounded-2xl border-slate-200 hover:border-blue-200"
                >
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b bg-slate-50 border-slate-100"
                    >
                        <h3
                            class="font-black text-slate-800 flex items-center gap-2 text-[10px] uppercase tracking-[0.2em]"
                        >
                            <svg
                                class="w-4 h-4 text-blue-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            Data Pelanggan
                        </h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex items-center justify-center w-12 h-12 text-lg font-black text-blue-600 rounded-2xl bg-blue-50"
                            >
                                {{ service.nama_pelanggan.charAt(0) }}
                            </div>
                            <div>
                                <p
                                    class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5"
                                >
                                    Nama Lengkap
                                </p>
                                <p
                                    class="text-base font-black leading-tight text-slate-800"
                                >
                                    {{ service.nama_pelanggan }}
                                </p>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-slate-50">
                            <p
                                class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5"
                            >
                                No. WhatsApp
                            </p>
                            <a
                                :href="`https://wa.me/${service.no_hp_pelanggan}`"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-black tracking-widest uppercase transition-all border bg-emerald-50 text-emerald-700 rounded-xl hover:bg-emerald-100 border-emerald-100"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.246 2.248 3.484 5.232 3.483 8.413-.003 6.557-5.338 11.892-11.893 11.892-1.997-.001-3.951-.5-5.688-1.448l-6.308 1.654zm6.233-3.762c1.608.955 3.197 1.441 4.861 1.442 5.419 0 9.829-4.411 9.831-9.831 0-2.628-1.023-5.099-2.88-6.956-1.857-1.858-4.325-2.88-6.953-2.881-5.42 0-9.831 4.412-9.833 9.832 0 1.896.536 3.733 1.554 5.305l-1.018 3.715 3.839-1.006zm9.805-6.52c-.266-.134-1.576-.778-1.821-.867-.244-.089-.422-.134-.599.134-.177.268-.687.867-.843 1.045-.155.178-.311.2-.577.066-.266-.134-1.124-.415-2.141-1.322-.792-.705-1.327-1.577-1.482-1.844-.155-.267-.017-.411.116-.544.121-.119.266-.311.399-.466.133-.155.177-.267.266-.445.089-.178.044-.334-.022-.468-.066-.134-.599-1.444-.821-1.977-.215-.521-.431-.45-.599-.459-.155-.008-.333-.009-.511-.009-.177 0-.466.067-.71.311-.243.245-.932.912-.932 2.224s.954 2.581 1.087 2.759c.133.178 1.878 2.871 4.548 4.022.635.274 1.132.438 1.519.561.637.203 1.217.174 1.674.105.51-.077 1.576-.644 1.798-1.267.222-.622.222-1.156.155-1.267s-.244-.2-.511-.334z"
                                    />
                                </svg>
                                {{ service.no_hp_pelanggan || "-" }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Device Card -->
                <div
                    class="overflow-hidden transition-all bg-white border shadow-sm rounded-2xl border-slate-200 hover:border-indigo-200"
                >
                    <div
                        class="px-6 py-4 border-b bg-slate-50 border-slate-100"
                    >
                        <h3
                            class="font-black text-slate-800 flex items-center gap-2 text-[10px] uppercase tracking-[0.2em]"
                        >
                            <svg
                                class="w-4 h-4 text-indigo-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"
                                />
                            </svg>
                            Informasi Unit
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div>
                            <p
                                class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5"
                            >
                                Model / Tipe Unit
                            </p>
                            <p
                                class="text-base font-black leading-tight text-slate-800"
                            >
                                {{ service.merk_hp }}
                                <span class="text-blue-600">{{
                                    service.tipe_hp
                                }}</span>
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p
                                    class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1"
                                >
                                    IMEI / SN
                                </p>
                                <p
                                    class="text-xs font-mono font-bold text-slate-600 bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-100"
                                >
                                    {{ service.imei_hp || "-" }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1"
                                >
                                    Kelengkapan
                                </p>
                                <p class="text-xs font-bold text-slate-600">
                                    {{ service.kelengkapan || "-" }}
                                </p>
                            </div>
                        </div>
                        <div class="pt-5 border-t border-slate-50">
                            <p
                                class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2"
                            >
                                Masalah / Keluhan
                            </p>
                            <div
                                class="p-4 text-xs italic font-bold leading-relaxed border bg-rose-50 rounded-2xl text-rose-700 border-rose-100"
                            >
                                "{{ service.kerusakan }}"
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Spareparts & Cost -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Spareparts Management -->
                <div
                    class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200"
                >
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b bg-slate-50 border-slate-100"
                    >
                        <h3
                            class="font-black text-slate-800 flex items-center gap-2 text-[10px] uppercase tracking-[0.2em]"
                        >
                            <svg
                                class="w-4 h-4 text-emerald-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            Sparepart Digunakan
                        </h3>

                        <!-- Tampilkan tombol jika status 'dikerjakan' (Proses) atau 'selesai' (untuk koreksi) -->
                        <button
                            v-if="
                                ['dikerjakan', 'selesai'].includes(
                                    service.status,
                                ) &&
                                service.status_pengambilan !== 'sudah_diambil'
                            "
                            @click="showAddPart = true"
                            class="text-[10px] font-black uppercase tracking-widest bg-blue-600 text-white px-4 py-2 rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition-all active:scale-95 flex items-center gap-2"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="3"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            Tambah Part
                        </button>
                    </div>

                    <div
                        v-if="
                            service.status_pengambilan === 'sudah_diambil' ||
                            service.status === 'batal'
                        "
                        class="p-12 text-center bg-slate-50/30"
                    >
                        <div class="max-w-xs mx-auto space-y-4">
                            <div
                                class="flex items-center justify-center w-16 h-16 mx-auto border-2 border-white shadow-sm bg-slate-100 text-slate-400 rounded-2xl"
                            >
                                <svg
                                    class="w-8 h-8"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h4
                                    class="text-sm font-black tracking-widest uppercase text-slate-800"
                                >
                                    Akses Dikunci
                                </h4>
                                <p
                                    class="mt-1 text-xs font-medium leading-relaxed text-slate-500"
                                >
                                    Sparepart tidak dapat diubah karena unit
                                    <b>{{
                                        service.status_pengambilan ===
                                        "sudah_diambil"
                                            ? "sudah diambil"
                                            : "dibatalkan"
                                    }}</b
                                    >.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-0">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50 border-b border-slate-100"
                                >
                                    <th class="px-6 py-4">Nama Part</th>
                                    <th class="px-6 py-4 text-center">Qty</th>
                                    <th class="px-6 py-4 text-right">Harga</th>
                                    <th class="px-6 py-4 text-right">
                                        Subtotal
                                    </th>
                                    <th class="w-10 px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr
                                    v-for="part in service.parts"
                                    :key="part.id"
                                    class="text-sm transition-colors hover:bg-slate-50/50"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-black text-slate-800">
                                            {{ part.nama_part }}
                                        </div>
                                        <div
                                            class="text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-widest"
                                        >
                                            GUDANG SPAREPART
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-block px-2.5 py-1 bg-slate-100 rounded-lg font-black text-slate-700"
                                            >{{ part.qty }}</span
                                        >
                                    </td>
                                    <td
                                        class="px-6 py-4 font-bold text-right text-slate-500"
                                    >
                                        {{ formatCurrency(part.harga_satuan) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 font-black text-right text-slate-800"
                                    >
                                        {{ formatCurrency(part.subtotal) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button
                                            v-if="
                                                service.status === 'dikerjakan'
                                            "
                                            @click="confirmRemovePart(part.id)"
                                            class="p-2 transition-all text-slate-300 hover:text-rose-600 active:scale-90"
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
                                    </td>
                                </tr>
                                <tr v-if="service.parts.length === 0">
                                    <td
                                        colspan="5"
                                        class="px-6 py-12 italic font-medium text-center text-slate-400"
                                    >
                                        Belum ada sparepart yang digunakan untuk
                                        unit ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cost Breakdown & Summary -->
                <div
                    class="overflow-hidden bg-white border-2 shadow-xl rounded-3xl border-slate-100 shadow-slate-100 group"
                >
                    <div class="p-8 space-y-5">
                        <div
                            class="flex items-center justify-between text-slate-500"
                        >
                            <span
                                class="text-xs font-black tracking-widest uppercase"
                                >Biaya Jasa Perbaikan</span
                            >
                            <span class="font-bold text-slate-800">{{
                                formatCurrency(service.biaya_jasa)
                            }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between pb-5 border-b-2 text-slate-500 border-slate-50"
                        >
                            <span
                                class="text-xs font-black tracking-widest uppercase"
                                >Total Harga Sparepart</span
                            >
                            <span class="font-bold text-slate-800">{{
                                formatCurrency(service.total_biaya_parts)
                            }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between pt-3 border-t border-slate-100"
                        >
                            <span
                                class="text-base font-black tracking-widest uppercase text-slate-800"
                                >Subtotal Tagihan</span
                            >
                            <div class="text-right">
                                <span
                                    class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1"
                                    >Sudah Termasuk Jasa</span
                                >
                                <span
                                    class="text-3xl font-black tracking-tight text-blue-600"
                                    >{{
                                        formatCurrency(service.grand_total)
                                    }}</span
                                >
                            </div>
                        </div>

                        <template v-if="service.status === 'selesai'">
                            <div
                                class="pt-4 space-y-4 border-t border-slate-100"
                            >
                                <h4
                                    class="text-xs font-black uppercase tracking-widest text-slate-600"
                                >
                                    Pembayaran Service
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1"
                                        >
                                            Jenis Diskon
                                        </label>
                                        <select
                                            v-model="paymentForm.discount_type"
                                            @change="onDiscountTypeChange"
                                            :disabled="paymentLocked"
                                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm font-bold focus:ring-0 focus:border-blue-500"
                                        >
                                            <option value="percent">
                                                Persentase (%)
                                            </option>
                                            <option value="nominal">
                                                Nominal (Rp)
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1"
                                        >
                                            {{
                                                paymentForm.discount_type ===
                                                "percent"
                                                    ? "Nilai Diskon (%)"
                                                    : "Nilai Diskon (Rp)"
                                            }}
                                        </label>
                                        <input
                                            v-if="
                                                paymentForm.discount_type ===
                                                'percent'
                                            "
                                            v-model.number="
                                                paymentForm.discount_value
                                            "
                                            :disabled="paymentLocked"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm font-bold focus:ring-0 focus:border-blue-500"
                                        />
                                        <div v-else class="relative">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-500"
                                                >Rp</span
                                            >
                                            <input
                                                :value="discountNominalDisplay"
                                                @input="onDiscountNominalInput"
                                                :disabled="paymentLocked"
                                                type="text"
                                                inputmode="numeric"
                                                class="w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl text-sm font-bold focus:ring-0 focus:border-blue-500"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1"
                                        >
                                            Metode Pembayaran
                                        </label>
                                        <select
                                            v-model="
                                                paymentForm.metode_pembayaran
                                            "
                                            :disabled="paymentLocked"
                                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm font-bold focus:ring-0 focus:border-blue-500"
                                        >
                                            <option value="cash">Cash</option>
                                            <option value="transfer">
                                                Transfer
                                            </option>
                                            <option value="qris">QRIS</option>
                                        </select>
                                    </div>
                                    <div
                                        v-if="
                                            paymentForm.metode_pembayaran ===
                                            'cash'
                                        "
                                    >
                                        <label
                                            class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1"
                                        >
                                            Uang Dibayar
                                        </label>
                                        <div class="relative">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-500"
                                                >Rp</span
                                            >
                                            <input
                                                :value="amountPaidDisplay"
                                                @input="onAmountPaidInput"
                                                :disabled="paymentLocked"
                                                type="text"
                                                inputmode="numeric"
                                                class="w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl text-sm font-bold focus:ring-0 focus:border-blue-500"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-2"
                                >
                                    <div
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span class="text-slate-500"
                                            >Subtotal</span
                                        >
                                        <span
                                            class="font-black text-slate-800"
                                            >{{
                                                formatCurrency(
                                                    paymentSummary.subtotal,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span class="text-slate-500"
                                            >Diskon</span
                                        >
                                        <span class="font-black text-rose-600"
                                            >-
                                            {{
                                                formatCurrency(
                                                    paymentSummary.diskonNominal,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-sm border-t border-slate-200 pt-2"
                                    >
                                        <span class="text-slate-500"
                                            >Total Tagihan</span
                                        >
                                        <span
                                            class="font-black text-slate-800"
                                            >{{
                                                formatCurrency(
                                                    paymentSummary.grandTotal,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-sm"
                                        v-if="
                                            paymentForm.metode_pembayaran ===
                                            'cash'
                                        "
                                    >
                                        <span class="text-slate-500"
                                            >Uang Dibayar</span
                                        >
                                        <span
                                            class="font-black text-blue-600"
                                            >{{
                                                formatCurrency(
                                                    paymentSummary.jumlahBayar,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-sm"
                                        v-if="
                                            paymentForm.metode_pembayaran ===
                                            'cash'
                                        "
                                    >
                                        <span class="text-slate-500"
                                            >Kembalian</span
                                        >
                                        <span
                                            class="font-black text-emerald-600"
                                            >{{
                                                formatCurrency(
                                                    paymentSummary.kembalian,
                                                )
                                            }}</span
                                        >
                                    </div>
                                </div>
                                <button
                                    @click="savePayment"
                                    :disabled="savingPayment || paymentLocked"
                                    class="w-full py-3 rounded-xl bg-blue-600 text-white text-xs font-black uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50 transition"
                                >
                                    {{
                                        paymentLocked
                                            ? "Pembayaran Sudah Final"
                                            : savingPayment
                                              ? "Menyimpan..."
                                              : "Selesaikan Pembayaran"
                                    }}
                                </button>
                                <p
                                    v-if="paymentLocked"
                                    class="text-[11px] text-emerald-700 font-semibold text-center"
                                >
                                    Transaksi service sudah selesai, unit sudah
                                    diserahkan, dan data terkunci.
                                </p>
                            </div>
                        </template>
                    </div>
                    <div
                        class="p-5 text-center border-t bg-blue-600/5 border-blue-600/10"
                    >
                        <div
                            class="flex items-center justify-center gap-2 text-blue-700 font-black uppercase tracking-[0.2em] text-[10px]"
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
                                    stroke-width="2.5"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04c-.243.398-.382.866-.382 1.366 0 5.42 2.732 10.209 6.883 13.065.415.286.83.567 1.25.842a15.751 15.751 0 001.25-.842c4.151-2.856 6.883-7.645 6.883-13.065 0-.5-.139-.968-.382-1.366z"
                                />
                            </svg>
                            Garansi Service: 7 Hari
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Part Modal -->
        <div
            v-if="showAddPart"
            class="fixed inset-0 bg-slate-900/80 z-[60] backdrop-blur-md flex items-center justify-center p-4"
        >
            <div
                class="w-full max-w-2xl overflow-hidden bg-white border-4 shadow-2xl rounded-3xl animate-scale-in border-slate-50"
            >
                <div
                    class="flex items-center justify-between px-8 py-6 bg-white border-b border-slate-100"
                >
                    <div>
                        <h3
                            class="font-black text-slate-800 uppercase tracking-[0.2em] text-sm"
                        >
                            Tambah Sparepart
                        </h3>
                        <p
                            class="text-[10px] font-bold text-slate-400 uppercase mt-0.5 tracking-wider"
                        >
                            Pilih dari inventaris gudang
                        </p>
                    </div>
                    <button
                        @click="closeAddPartModal"
                        class="p-2 transition-all bg-slate-100 hover:bg-slate-200 text-slate-400 rounded-xl"
                    >
                        <svg
                            class="w-6 h-6"
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
                <div class="p-8 space-y-6">
                    <div ref="partSearchWrapper" class="relative">
                        <label
                            class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3"
                            >Cari Sesuai Nama / Barcode</label
                        >
                        <div class="relative group">
                            <input
                                v-model="partSearch"
                                @focus="handlePartFocus"
                                type="text"
                                placeholder="Cari sparepart disini..."
                                class="w-full py-4 pr-6 font-bold transition-all border-2 rounded-2xl border-slate-100 focus:border-blue-500 focus:ring-0 pl-14 text-slate-800 placeholder:text-slate-300 bg-slate-50/50 focus:bg-white"
                            />
                            <svg
                                class="absolute w-6 h-6 transition-colors -translate-y-1/2 left-5 top-1/2 text-slate-300 group-focus-within:text-blue-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>

                        <!-- Results Popup -->
                        <div
                            v-if="showPartDropdown"
                            class="absolute z-10 w-full mt-3 overflow-hidden overflow-y-auto bg-white border divide-y shadow-2xl rounded-2xl border-slate-100 max-h-64 divide-slate-50"
                        >
                            <div
                                v-if="isSearchingPart"
                                class="p-3 text-xs font-semibold text-slate-500"
                            >
                                Mencari sparepart...
                            </div>
                            <div
                                v-for="res in partResults"
                                :key="res.id"
                                @click="selectPart(res)"
                                class="flex items-center gap-4 p-4 transition-all cursor-pointer hover:bg-blue-50 group"
                            >
                                <div
                                    class="flex items-center justify-center w-12 h-12 transition-colors rounded-xl bg-slate-100 text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-600"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                        />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div
                                        class="font-black truncate text-slate-800"
                                    >
                                        {{ res.nama }}
                                    </div>
                                    <div
                                        class="text-[10px] flex items-center gap-3 mt-1 font-bold"
                                    >
                                        <span
                                            :class="
                                                res.stok > 0
                                                    ? 'text-emerald-600'
                                                    : 'text-rose-600'
                                            "
                                            >Stok: {{ res.stok }}</span
                                        >
                                        <span class="text-blue-600">{{
                                            formatCurrency(res.harga_jual)
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="
                                    !isSearchingPart && partResults.length === 0
                                "
                                class="p-3 text-xs text-slate-500"
                            >
                                Sparepart tidak ditemukan
                            </div>
                        </div>
                    </div>

                    <!-- Selected Part Form -->
                    <div
                        v-if="selectedPart"
                        class="p-6 space-y-5 border-2 border-blue-100 bg-blue-50/50 rounded-3xl animate-slide-up"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h4
                                    class="font-black leading-tight text-blue-900"
                                >
                                    {{ selectedPart.nama }}
                                </h4>
                                <p
                                    class="text-[10px] font-bold text-blue-400 uppercase mt-1 tracking-widest"
                                >
                                    Part Terpilih
                                </p>
                            </div>
                            <button
                                @click="selectedPart = null"
                                class="text-[10px] font-black text-blue-600 uppercase hover:underline"
                            >
                                Ganti
                            </button>
                        </div>
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-blue-100/50"
                        >
                            <div>
                                <label
                                    class="block text-[9px] font-black text-blue-400 uppercase tracking-widest mb-2"
                                    >Jumlah (Qty)</label
                                >
                                <input
                                    v-model="partForm.qty"
                                    type="number"
                                    min="1"
                                    class="w-full px-4 py-3 font-black border-blue-100 rounded-xl focus:border-blue-500 focus:ring-0 text-slate-800"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-[9px] font-black text-blue-400 uppercase tracking-widest mb-2"
                                    >Harga Jual (Rp)</label
                                >
                                <input
                                    v-model="partForm.harga_satuan"
                                    type="number"
                                    class="w-full px-4 py-3 font-black border-blue-100 rounded-xl focus:border-blue-500 focus:ring-0 text-slate-800"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-4">
                        <button
                            @click="closeAddPartModal"
                            class="flex-1 py-4 rounded-2xl border-2 border-slate-100 text-slate-400 font-black uppercase tracking-widest text-[10px] hover:bg-slate-50 transition-all"
                        >
                            Batal
                        </button>
                        <button
                            @click="handleAddPart"
                            :disabled="!selectedPart"
                            class="flex-[2] py-4 rounded-2xl bg-blue-600 text-white font-black uppercase tracking-widest text-[10px] shadow-xl shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition-all active:scale-95"
                        >
                            Konfirmasi & Tambah
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmDialog
            :show="confirmState.show"
            :loading="isUpdatingStatus"
            :title="confirmState.title"
            :message="confirmState.message"
            :confirm-text="confirmState.confirmText"
            :loading-text="confirmState.loadingText"
            @confirm="handleConfirm"
            @cancel="closeConfirm"
        />
        <div
            v-if="cancelModalOpen"
            class="fixed inset-0 z-[90] bg-black/45 backdrop-blur-sm flex items-center justify-center p-4"
        >
            <div
                class="w-full max-w-md bg-white rounded-2xl border border-slate-100 shadow-xl p-6"
            >
                <h3 class="text-lg font-black text-slate-800">
                    Batalkan Service
                </h3>
                <p class="mt-2 text-sm text-slate-500">
                    Pilih perlakuan sparepart saat service dibatalkan.
                </p>
                <div class="mt-5 grid gap-3">
                    <button
                        @click="cancelService(true)"
                        :disabled="isUpdatingStatus"
                        class="w-full py-2.5 rounded-xl bg-amber-500 text-white text-xs font-black uppercase tracking-wider hover:bg-amber-600 disabled:opacity-50 transition"
                    >
                        Batalkan + Kembalikan Sparepart ke Stok
                    </button>
                    <button
                        @click="cancelService(false)"
                        :disabled="isUpdatingStatus"
                        class="w-full py-2.5 rounded-xl bg-rose-600 text-white text-xs font-black uppercase tracking-wider hover:bg-rose-700 disabled:opacity-50 transition"
                    >
                        Batalkan Tanpa Kembalikan Sparepart
                    </button>
                    <button
                        @click="cancelModalOpen = false"
                        class="w-full py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-black uppercase tracking-wider hover:bg-slate-50 transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes scale-in {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.animate-scale-in {
    animation: scale-in 0.2s ease-out;
}
@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-slide-up {
    animation: slide-up 0.2s ease-out;
}
</style>
