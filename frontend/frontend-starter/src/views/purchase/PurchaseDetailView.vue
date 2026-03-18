<script setup>
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../../api";

const route = useRoute();
const router = useRouter();
const purchase = ref(null);
const loading = ref(true);

function formatCurrency(val) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
}

function formatNumber(val, decimals = 3) {
    return Number(val || 0).toLocaleString("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: decimals,
    });
}

function formatDate(dateStr) {
    if (!dateStr) return "-";
    const [y, m, d] = dateStr.split("-");
    return `${d}-${m}-${y}`;
}

const totalPembelian = computed(() =>
    (purchase.value?.items || []).reduce(
        (sum, item) => sum + Number(item.subtotal || 0),
        0
    )
);

async function loadPurchase() {
    loading.value = true;
    try {
        const { data } = await api.get(`/purchases/${route.params.id}`);
        purchase.value = data.data;
    } catch (err) {
        alert("Gagal memuat data");
        router.push("/dashboard/purchases");
    } finally {
        loading.value = false;
    }
}

onMounted(loadPurchase);
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <!-- Page Header -->
        <div class="flex items-center gap-3">
            <button
                @click="router.push('/dashboard/purchases')"
                class="p-2.5 hover:bg-slate-100 rounded-xl transition"
            >
                <svg
                    class="w-5 h-5 text-slate-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
            </button>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Detail Pembelian</h1>
                <p class="text-sm text-slate-400 mt-0.5" v-if="purchase">
                    {{ purchase.no_invoice }}
                </p>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="flex flex-col items-center gap-2">
                <div class="w-8 h-8 border-4 rounded-full border-slate-200 border-t-blue-500 animate-spin"></div>
                <span class="text-sm font-medium text-slate-500">Memuat data...</span>
            </div>
        </div>

        <template v-else-if="purchase">
            <!-- Invoice Info Card -->
            <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-100 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                    <div>
                        <p class="mb-1 text-xs font-medium text-slate-400">No. Invoice</p>
                        <p class="font-mono font-semibold text-slate-800">
                            {{ purchase.no_invoice }}
                        </p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-medium text-slate-400">Tanggal</p>
                        <p class="font-semibold text-slate-800">
                            {{ formatDate(purchase.tanggal) }}
                        </p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-medium text-slate-400">Supplier</p>
                        <p class="font-semibold text-slate-800">
                            {{ purchase.supplier?.nama || "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-medium text-slate-400">Total Pembelian</p>
                        <p class="font-bold text-emerald-600">
                            {{ formatCurrency(purchase.total ?? totalPembelian) }}
                        </p>
                    </div>
                </div>
                <div
                    v-if="purchase.keterangan"
                    class="pt-4 mt-4 border-t border-slate-100"
                >
                    <p class="mb-1 text-xs font-medium text-slate-400">Keterangan</p>
                    <p class="text-sm text-slate-600">{{ purchase.keterangan }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-100">
                <!-- Header -->
                <div class="px-4 sm:px-5 py-3.5 bg-gradient-to-r from-slate-50 to-white flex flex-col sm:flex-row gap-2 sm:gap-0 justify-between items-start sm:items-center border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-800">
                        Daftar Barang
                        <span class="ml-1.5 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">
                            {{ purchase.items?.length || 0 }}
                        </span>
                    </h3>
                    <router-link
                        :to="`/dashboard/purchases/${purchase.id}/edit`"
                        class="px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-lg border border-emerald-200 hover:bg-emerald-100 transition flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah / Edit Item
                    </router-link>
                </div>

                <!-- Table -->
                <div class="table-container">
                    <table class="table-fixed-layout table-wide">
                        <thead class="table-header">
                            <tr>
                                <th class="w-12 text-center">No</th>
                                <th class="text-left">Kode Barang</th>
                                <th class="text-left">Nama Barang</th>
                                <th class="text-left">Kategori</th>
                                <th class="text-center">Satuan</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Harga Beli</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">Sisa Stok</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <tr v-if="!purchase.items || purchase.items.length === 0">
                                <td
                                    colspan="9"
                                    class="px-6 py-10 text-center text-slate-500 italic"
                                >
                                    Tidak ada item pada invoice ini.
                                </td>
                            </tr>
                            <tr
                                v-for="(item, idx) in purchase.items"
                                :key="item.id"
                                class="table-row hover:bg-slate-50 transition"
                            >
                                <td class="table-cell text-center text-slate-400 font-medium">
                                    {{ idx + 1 }}
                                </td>
                                <td class="table-cell font-mono text-xs text-blue-600 font-semibold">
                                    {{ item.katalog_barang?.kode_barang || "-" }}
                                </td>
                                <td class="table-cell font-medium text-slate-700">
                                    {{ item.katalog_barang?.nama_barang || item.nama_barang || "-" }}
                                </td>
                                <td class="table-cell text-slate-500">
                                    {{ item.katalog_barang?.category?.nama || "-" }}
                                </td>
                                <td class="table-cell text-center text-slate-500">
                                    {{ item.katalog_barang?.unit?.nama || item.satuan || "-" }}
                                </td>
                                <td class="table-cell text-right font-semibold text-slate-700">
                                    {{ formatNumber(item.qty, 3) }}
                                </td>
                                <td class="table-cell text-right text-slate-600">
                                    {{ formatCurrency(item.harga_beli) }}
                                </td>
                                <td class="table-cell text-right font-bold text-emerald-600">
                                    {{ formatCurrency(item.subtotal) }}
                                </td>
                                <td
                                    class="table-cell text-right font-semibold"
                                    :class="Number(item.sisa_stok) <= 0 ? 'text-red-500' : 'text-slate-600'"
                                >
                                    {{ formatNumber(item.sisa_stok, 3) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Total Footer -->
                <div class="flex items-center justify-end gap-6 px-4 sm:px-5 py-3 border-t border-slate-100 bg-slate-50">
                    <span class="text-xs font-semibold tracking-wider uppercase text-slate-400">
                        Total Pembelian
                    </span>
                    <span class="text-base font-bold text-emerald-600">
                        {{ formatCurrency(purchase.total ?? totalPembelian) }}
                    </span>
                </div>
            </div>

            <!-- Back Button -->
            <div class="flex justify-start">
                <button
                    @click="router.push('/dashboard/purchases')"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Daftar
                </button>
            </div>
        </template>
    </div>
</template>
