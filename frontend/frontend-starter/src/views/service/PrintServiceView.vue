<script setup>
import { ref, onMounted, computed, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../../api";
import { useToast } from "../../composables/useToast";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const service = ref(null);
const store = ref(null);
const isLoading = ref(true);

const isFinalInvoice = computed(() => {
    return (
        service.value?.status === "selesai" ||
        service.value?.status_pengambilan === "sudah_diambil"
    );
});

const totalQty = computed(() => {
    if (!service.value) return 0;
    return (service.value.parts || []).reduce(
        (acc, part) => acc + (part.qty || 0),
        0
    );
});

const subtotalParts = computed(() => {
    return service.value?.total_biaya_parts || 0;
});

async function fetchData() {
    try {
        const [serviceRes, storeRes] = await Promise.all([
            api.get(`/services/${route.params.id}`),
            api.get("/store-settings"),
        ]);
        service.value = serviceRes.data.data;
        store.value = storeRes.data.data;
    } catch (e) {
        toast.error("Gagal memuat detail service");
    } finally {
        isLoading.value = false;
        nextTick(() => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    }
}

onMounted(() => {
    fetchData();
});

if (typeof window !== "undefined") {
    window.onafterprint = () => {
        router.back();
    };
}

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

function printNow() {
    window.print();
}
</script>

<template>
    <div
        v-if="isLoading"
        class="flex items-center justify-center h-screen bg-white"
    >
        <p class="font-mono text-xs uppercase text-slate-500">
            MEMUAT DOKUMEN SERVICE...
        </p>
    </div>

    <div
        v-else-if="service"
        class="min-h-screen bg-white print:min-h-0 print:bg-transparent"
    >
        <div class="max-w-[800px] mx-auto pt-6 px-4 print:hidden">
            <div
                class="flex items-center justify-between p-4 mb-6 border bg-slate-50 rounded-xl border-slate-200"
            >
                <button
                    @click="router.back()"
                    class="flex items-center gap-2 text-sm font-medium text-slate-600"
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
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                    KEMBALI
                </button>
                <button
                    @click="printNow"
                    class="flex items-center gap-2 px-6 py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow-lg hover:bg-blue-700"
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
                    CETAK SEKARANG
                </button>
            </div>
        </div>

        <div
            id="invoice-printable"
            class="bg-white w-full max-w-[800px] mx-auto p-8 mb-20 print:mb-0 print:p-0 font-mono text-[11px] text-black"
        >
            <div class="flex items-center justify-between mb-4">
                <h1
                    class="font-serif font-black tracking-tighter text-black"
                    :class="{
                        'text-5xl': isFinalInvoice,
                        'text-4xl': !isFinalInvoice,
                    }"
                >
                    {{ isFinalInvoice ? "INVOICE" : "TANDA TERIMA SERVIS" }}
                </h1>
                <div class="text-right">
                    <img
                        v-if="store?.logo_url"
                        :src="store.logo_url"
                        class="object-contain ml-auto h-14"
                    />
                    <div
                        v-else
                        class="text-2xl font-black leading-none text-black uppercase"
                    >
                        {{ store?.name }}
                    </div>
                </div>
            </div>

            <div class="mb-8 border-b-2 border-slate-800"></div>

            <table class="w-full mb-10 border-collapse table-fixed">
                <tr>
                    <td class="w-1/2 pr-10 align-top">
                        <div class="leading-relaxed uppercase">
                            <p class="font-black text-[12px] mb-2">
                                {{ store?.name }}
                            </p>
                            <p class="text-slate-600">{{ store?.address }}</p>
                            <p class="mt-4 font-bold">
                                No Telp :
                                <span class="text-black">{{
                                    store?.phone
                                }}</span>
                            </p>
                            <p v-if="store?.email">
                                Email :
                                <span class="text-black">{{
                                    store?.email
                                }}</span>
                            </p>
                        </div>
                    </td>
                    <td class="w-1/2 align-top">
                        <table class="w-full border-collapse">
                            <tr>
                                <td class="w-32 py-1 font-bold uppercase">
                                    No. Servis
                                </td>
                                <td class="w-4 py-1 text-center">:</td>
                                <td class="py-1 font-bold text-right">
                                    {{ service.no_service || "-" }}
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 font-bold uppercase">
                                    Tanggal Masuk
                                </td>
                                <td class="py-1 text-center">:</td>
                                <td class="py-1 text-right">
                                    {{ formatDate(service.tanggal_masuk) }}
                                </td>
                            </tr>
                            <tr v-if="service.tanggal_selesai">
                                <td class="py-1 font-bold uppercase">
                                    Tanggal Selesai
                                </td>
                                <td class="py-1 text-center">:</td>
                                <td class="py-1 text-right">
                                    {{ formatDate(service.tanggal_selesai) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="py-1 font-bold uppercase">
                                    Pelanggan
                                </td>
                                <td class="py-1 text-center">:</td>
                                <td class="py-1 font-bold text-right uppercase">
                                    {{ service.nama_pelanggan }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div
                class="text-[10px] font-black mb-4 uppercase tracking-widest border-l-4 border-black pl-3 py-0.5"
            >
                Rincian Service
            </div>

            <table class="w-full mb-6 border border-collapse border-slate-300">
                <thead>
                    <tr class="border-b bg-slate-50 border-slate-300">
                        <th
                            class="border-r border-slate-300 p-2 text-center w-[40px] font-bold"
                        >
                            No
                        </th>
                        <th
                            class="border-r border-slate-300 p-2 text-left w-[280px] font-bold"
                        >
                            Nama Produk/Jasa
                        </th>
                        <th
                            class="border-r border-slate-300 p-2 text-center w-[80px] font-bold"
                        >
                            Satuan
                        </th>
                        <th
                            class="border-r border-slate-300 p-2 text-right w-[110px] font-bold"
                        >
                            Harga
                        </th>
                        <th
                            class="border-r border-slate-300 p-2 text-center w-[60px] font-bold"
                        >
                            Qty
                        </th>
                        <th class="p-2 text-right w-[130px] font-bold">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(part, idx) in service.parts"
                        :key="part.id"
                        class="align-top border-b border-slate-200"
                    >
                        <td
                            class="p-3 text-center border-r border-slate-200 text-slate-500"
                        >
                            {{ idx + 1 }}
                        </td>
                        <td class="p-3 border-r border-slate-200">
                            <div
                                class="mb-1 font-bold leading-tight text-black uppercase"
                            >
                                {{ part.nama_part }}
                            </div>
                            <div class="text-[9px] text-slate-500 uppercase">
                                {{ part.product?.brand || "-" }}
                            </div>
                        </td>
                        <td
                            class="p-3 italic text-center uppercase border-r border-slate-200 text-slate-500"
                        >
                            {{ part.product?.unit || "Unit" }}
                        </td>
                        <td class="p-3 text-right border-r border-slate-200">
                            Rp.{{ formatCurrency(part.harga_satuan) }},-
                        </td>
                        <td
                            class="p-3 font-bold text-center border-r border-slate-200"
                        >
                            {{ part.qty }}
                        </td>
                        <td class="p-3 font-bold text-right text-black">
                            Rp.{{ formatCurrency(part.subtotal) }},-
                        </td>
                    </tr>

                    <tr
                        v-if="service.biaya_jasa > 0"
                        class="align-top border-b border-slate-200"
                    >
                        <td
                            class="p-3 text-center border-r border-slate-200 text-slate-500"
                        >
                            {{ service.parts.length + 1 }}
                        </td>
                        <td class="p-3 border-r border-slate-200">
                            <div
                                class="mb-1 font-bold leading-tight text-black uppercase"
                            >
                                Biaya Jasa Perbaikan
                            </div>
                            <div class="text-[9px] text-slate-500 uppercase">
                                {{ service.merk_hp }} {{ service.tipe_hp }}
                            </div>
                        </td>
                        <td
                            class="p-3 italic text-center uppercase border-r border-slate-200 text-slate-500"
                        >
                            Jasa
                        </td>
                        <td class="p-3 text-right border-r border-slate-200">
                            Rp.{{ formatCurrency(service.biaya_jasa) }},-
                        </td>
                        <td
                            class="p-3 font-bold text-center border-r border-slate-200"
                        >
                            -
                        </td>
                        <td class="p-3 font-bold text-right text-black">
                            Rp.{{ formatCurrency(service.biaya_jasa) }},-
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="font-black bg-slate-50">
                        <td
                            colspan="4"
                            class="p-3 text-right uppercase tracking-widest text-[9px]"
                        >
                            Total Kuantitas
                        </td>
                        <td class="p-3 text-center border-x border-slate-200">
                            {{ totalQty }}
                        </td>
                        <td class="p-3 font-black text-right">
                            Rp.{{ formatCurrency(service.grand_total) }},-
                        </td>
                    </tr>
                </tfoot>
            </table>

            <table class="w-full table-fixed">
                <tr>
                    <td class="w-1/2 pr-8 align-top">
                        <div
                            class="p-4 uppercase border rounded border-slate-200 bg-slate-50"
                        >
                            <p
                                class="font-bold text-slate-400 mb-2 text-[9px] tracking-widest"
                            >
                                INFO SERVICE
                            </p>

                            <p
                                class="mt-2 text-[12px] normal-case text-slate-500"
                            >
                                Keluhan: {{ service.kerusakan }}
                            </p>
                            <p
                                v-if="service.catatan_teknisi"
                                class="text-[9px] text-slate-500 mt-1 normal-case"
                            >
                                Catatan Teknisi: {{ service.catatan_teknisi }}
                            </p>
                        </div>
                    </td>

                    <td class="w-1/2 align-top">
                        <table class="w-full border-collapse">
                            <tr class="border-b border-slate-100">
                                <td
                                    class="py-2 font-bold uppercase text-slate-500"
                                >
                                    Sub Total Parts
                                </td>
                                <td class="py-2 font-bold text-right">
                                    Rp.{{ formatCurrency(subtotalParts) }},-
                                </td>
                            </tr>
                            <tr class="border-b border-slate-100">
                                <td
                                    class="py-2 font-bold uppercase text-slate-500"
                                >
                                    Biaya Jasa
                                </td>
                                <td class="py-2 font-bold text-right">
                                    Rp.{{
                                        formatCurrency(service.biaya_jasa)
                                    }},-
                                </td>
                            </tr>
                            <tr class="border-t-2 border-black">
                                <td
                                    class="py-4 font-black uppercase text-[12px]"
                                >
                                    {{
                                        isFinalInvoice
                                            ? "GRAND TOTAL"
                                            : "ESTIMASI BIAYA"
                                    }}
                                </td>
                                <td
                                    class="py-4 text-right font-black text-[16px]"
                                >
                                    Rp.{{
                                        formatCurrency(service.grand_total)
                                    }},-
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td class="pt-10 align-bottom">
                        <div>
                            <p
                                class="font-bold text-slate-400 text-[9px] tracking-widest mb-1"
                            >
                                TERIMA KASIH ATAS
                            </p>
                            <p class="font-black text-[11px] uppercase">
                                KEPERCAYAAN SERVICE ANDA
                            </p>
                        </div>
                    </td>
                    <td class="pt-10 text-right align-bottom">
                        <div class="mb-0">
                            <p
                                class="font-signature text-[18pt] text-slate-800 leading-none whitespace-nowrap"
                            >
                                {{
                                    store?.signature_name ||
                                    "Febryana Nurudin Araniri"
                                }}
                            </p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=Courgette&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap");

.font-signature {
    font-family: "Courgette", cursive !important;
}

@media print {
    @page {
        size: auto;
        margin: 0mm;
    }

    html,
    body {
        height: auto !important;
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    body * {
        visibility: hidden !important;
    }

    #invoice-printable,
    #invoice-printable * {
        visibility: visible !important;
    }

    #invoice-printable {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 10mm !important;
        background: white !important;
        box-shadow: none !important;
        border: none !important;
    }

    table,
    tr,
    td,
    th {
        break-inside: avoid !important;
    }

    #invoice-printable,
    #invoice-printable p,
    #invoice-printable td,
    #invoice-printable th,
    #invoice-printable h1 {
        font-family: "Space Mono", monospace !important;
        color: black !important;
    }

    img {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
