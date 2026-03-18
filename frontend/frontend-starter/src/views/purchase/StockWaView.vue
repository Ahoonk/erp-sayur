<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../../api";
import { useToast } from "../../composables/useToast";

const toast = useToast();
const isLoading = ref(false);
const groups = ref([]);
const copied = ref(false);

onMounted(() => fetchStockWa());

async function fetchStockWa() {
    isLoading.value = true;
    try {
        const { data } = await api.get("/products/stock-wa");
        groups.value = data.data || [];
    } catch {
        toast.error("Gagal memuat data stok WA");
    } finally {
        isLoading.value = false;
    }
}

const totalItems = computed(() =>
    groups.value.reduce((acc, g) => acc + g.products.length, 0)
);

const totalStok = computed(() =>
    groups.value.reduce(
        (acc, g) => acc + g.products.reduce((a, p) => a + p.stok, 0),
        0
    )
);

const waText = computed(() => {
    if (!groups.value.length) return "";
    return groups.value
        .map((g) => {
            const header = `==${g.brand}==`;
            const lines = g.products.map((p) => `${p.nama} x${p.stok}`).join("\n");
            return `${header}\n${lines}`;
        })
        .join("\n\n");
});

async function copyToClipboard() {
    if (!waText.value) return;
    try {
        await navigator.clipboard.writeText(waText.value);
        copied.value = true;
        toast.success("Teks berhasil disalin!");
        setTimeout(() => (copied.value = false), 2500);
    } catch {
        toast.error("Gagal menyalin teks");
    }
}
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold text-slate-800">List Stok WA</h1>
                <p class="mt-1 text-sm text-slate-400">
                    Daftar barang tersedia untuk dibagikan via WhatsApp
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    @click="copyToClipboard"
                    :disabled="isLoading || !groups.length"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white transition-all shadow-sm rounded-xl disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="
                        copied
                            ? 'bg-emerald-500 hover:bg-emerald-600'
                            : 'bg-blue-600 hover:bg-blue-700'
                    "
                >
                    <svg
                        v-if="!copied"
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    {{ copied ? "Tersalin!" : "Copy to Clipboard" }}
                </button>
                <router-link
                    to="/dashboard/purchase-items"
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
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                    Kembali
                </router-link>
            </div>
        </div>

        <!-- Stat Cards -->

        <!-- Content -->
        <div
            class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200"
        >
            <!-- Loading -->
            <div
                v-if="isLoading"
                class="flex flex-col items-center justify-center gap-3 py-20"
            >
                <div
                    class="border-4 rounded-full w-9 h-9 border-slate-200 border-t-blue-500 animate-spin"
                ></div>
                <span class="text-sm text-slate-400">Memuat stok...</span>
            </div>

            <!-- Empty -->
            <div
                v-else-if="!groups.length"
                class="flex flex-col items-center justify-center gap-3 py-20"
            >
                <svg
                    class="w-12 h-12 text-slate-200"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                    />
                </svg>
                <p class="text-sm text-slate-400">Tidak ada stok tersedia</p>
            </div>

            <!-- List -->

            <!-- Footer preview -->
            <div
                v-if="groups.length"
                class="px-5 py-4 border-t border-slate-200 bg-slate-50"
            >
                <pre
                    class="p-3 overflow-y-auto font-mono text-xs leading-relaxed whitespace-pre-wrap bg-white border rounded-lg text-slate-600 max-h-48 border-slate-200"
                    >{{ waText }}</pre
                >
            </div>
        </div>
    </div>
</template>
