<script setup>
import { ref, watch } from "vue";
import debounce from "lodash-es/debounce";
import api from "../../api";
import { storageUrl } from "../../utils/storage";

const query = ref("");
const isLoading = ref(false);
const notFound = ref(false);
const item = ref(null);

function formatCurrency(value) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(Number(value || 0));
}

async function lookupItem(rawCode) {
    const code = String(rawCode || "").trim();
    if (!code) {
        item.value = null;
        notFound.value = false;
        return;
    }

    isLoading.value = true;
    notFound.value = false;

    try {
        const { data } = await api.get("/products/search", {
            params: {
                q: code,
                include_out_stock: true,
            },
        });

        const rows = data.data || [];
        const codeLower = code.toLowerCase();
        const exact = rows.find((row) => {
            const barcode = String(row.barcode || "").toLowerCase();
            const imei1 = String(row.imei1 || "").toLowerCase();
            const imei2 = String(row.imei2 || "").toLowerCase();
            return (
                barcode === codeLower ||
                imei1 === codeLower ||
                imei2 === codeLower
            );
        });

        item.value = exact || null;
        notFound.value = !exact;
    } catch (error) {
        item.value = null;
        notFound.value = true;
    } finally {
        isLoading.value = false;
    }
}

const debouncedLookup = debounce((value) => lookupItem(value), 250);

watch(query, (value) => {
    debouncedLookup(value);
});

function clearField() {
    query.value = "";
    item.value = null;
    notFound.value = false;
}
</script>

<template>
    <div class="max-w-6xl py-2 mx-auto space-y-6 px-4 sm:px-0">
        <section
            class="relative overflow-hidden border shadow-sm rounded-2xl border-slate-200 bg-gradient-to-br from-white via-slate-50 to-blue-50"
        >
            <div
                class="absolute w-40 h-40 rounded-full -top-16 -right-12 bg-blue-100/60"
            ></div>
            <div
                class="absolute w-32 h-32 rounded-full -bottom-12 -left-10 bg-emerald-100/60"
            ></div>
            <div class="relative p-6 md:p-8">
                <h1 class="text-3xl font-black tracking-tight text-slate-800">
                    Cari Barang
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Scan atau ketik Barcode / IMEI, detail barang akan muncul
                    otomatis.
                </p>

                <div
                    class="grid grid-cols-1 gap-2 mt-5 md:grid-cols-[1fr_auto]"
                >
                    <input
                        v-model.trim="query"
                        type="text"
                        placeholder="Masukkan barcode, IMEI 1, atau IMEI 2..."
                        class="w-full px-4 py-3 text-lg font-semibold transition bg-white border outline-none rounded-xl border-slate-300 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                        autofocus
                    />
                    <button
                        type="button"
                        class="px-4 py-3 font-semibold transition bg-white border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                        @click="clearField"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200"
        >
            <div class="p-6 md:p-8">
                <div v-if="isLoading" class="py-16 text-center text-slate-500">
                    Mencari data barang...
                </div>

                <div v-else-if="notFound && query" class="py-16 text-center">
                    <p class="text-lg font-bold text-rose-600">
                        Barang tidak ditemukan
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Pastikan barcode/IMEI yang dimasukkan benar.
                    </p>
                </div>

                <div
                    v-else-if="item"
                    class="grid grid-cols-1 gap-6 md:grid-cols-[240px_1fr]"
                >
                    <div
                        class="flex items-center justify-center p-4 border rounded-xl border-slate-200 bg-slate-50 min-h-[220px]"
                    >
                        <img
                            v-if="item.foto"
                            :src="storageUrl(item.foto)"
                            alt="Foto Barang"
                            class="object-contain w-full h-full max-h-56"
                        />
                        <div v-else class="text-sm font-medium text-slate-400">
                            Tidak ada foto
                        </div>
                    </div>

                    <div
                        class="overflow-hidden border rounded-xl border-slate-200"
                    >
                        <table class="w-full">
                            <tbody class="divide-y divide-slate-200">
                                <tr>
                                    <td
                                        class="px-4 py-3 text-sm font-bold uppercase text-slate-500 w-44"
                                    >
                                        Kode Barcode
                                    </td>
                                    <td
                                        class="px-4 py-3 text-xl font-black text-slate-800"
                                    >
                                        {{ item.barcode || "-" }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="px-4 py-3 text-sm font-bold uppercase text-slate-500"
                                    >
                                        Nama Barang
                                    </td>
                                    <td
                                        class="px-4 py-3 text-lg font-bold text-slate-800"
                                    >
                                        {{ item.nama || "-" }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="px-4 py-3 text-sm font-bold uppercase text-slate-500"
                                    >
                                        Modal
                                    </td>
                                    <td
                                        class="px-4 py-3 text-lg font-bold text-blue-700"
                                    >
                                        {{ formatCurrency(item.harga_modal) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="px-4 py-3 text-sm font-bold uppercase text-slate-500"
                                    >
                                        Harga Jual
                                    </td>
                                    <td
                                        class="px-4 py-3 text-lg font-bold text-emerald-700"
                                    >
                                        {{ formatCurrency(item.harga_jual) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="px-4 py-3 text-sm font-bold uppercase text-slate-500"
                                    >
                                        IMEI 1
                                    </td>
                                    <td
                                        class="px-4 py-3 text-base font-semibold text-slate-800"
                                    >
                                        {{ item.imei1 || "-" }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="px-4 py-3 text-sm font-bold uppercase text-slate-500"
                                    >
                                        IMEI 2
                                    </td>
                                    <td
                                        class="px-4 py-3 text-base font-semibold text-slate-800"
                                    >
                                        {{ item.imei2 || "-" }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="py-16 text-center text-slate-500">
                    Masukkan barcode atau IMEI untuk mulai mencari.
                </div>
            </div>
        </section>
    </div>
</template>
