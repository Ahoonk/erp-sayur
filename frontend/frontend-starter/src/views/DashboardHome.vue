<script setup>
import { computed, onMounted, ref } from "vue";
import api from "../api";
import { useToast } from "../composables/useToast";

const toast = useToast();
const loading = ref(true);
const summary = ref(null);
const isKasir = computed(() => Boolean(summary.value?.is_kasir));
const kasirName = computed(() => summary.value?.current_user_name || "Kasir");

const colors = {
    sales: "from-sky-500 to-blue-600 shadow-sky-500/30",
    profit: "from-amber-400 to-orange-500 shadow-orange-500/30",
    purchases: "from-rose-400 to-red-500 shadow-rose-500/30",
};

const compact = new Intl.NumberFormat("id-ID", {
    notation: "compact",
    compactDisplay: "short",
    maximumFractionDigits: 1,
});

const currency = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
});

const salesCards = computed(() => {
    const c = summary.value?.cards?.sales || {};
    return [
        { label: "Penjualan Hari Ini", value: c.today || 0 },
        { label: "Penjualan Bulan Ini", value: c.month || 0 },
        { label: "Penjualan Tahun Ini", value: c.year || 0 },
        { label: "Total Seluruh Penjualan", value: c.total || 0 },
    ];
});

const profitCards = computed(() => {
    const c = summary.value?.cards?.profit || {};
    return [
        { label: "Laba Hari Ini", value: c.today || 0 },
        { label: "Laba Bulan Ini", value: c.month || 0 },
        { label: "Laba Tahun Ini", value: c.year || 0 },
        { label: "Total Seluruh Laba", value: c.total || 0 },
    ];
});

const purchaseCards = computed(() => {
    const c = summary.value?.cards?.purchases || {};
    return [
        { label: "Pembelian Hari Ini", value: c.today || 0 },
        { label: "Pembelian Bulan Ini", value: c.month || 0 },
        { label: "Pembelian Tahun Ini", value: c.year || 0 },
        { label: "Total Seluruh Pembelian", value: c.total || 0 },
    ];
});

const miniCards = computed(() => {
    const c = summary.value?.cards?.summary || {};
    return [
        { label: "Jumlah Invoice Pembelian", value: c.purchase_invoices || 0 },
        { label: "Jumlah Produk Tersedia", value: c.available_products || 0 },
        { label: "Jumlah Invoice Penjualan", value: c.sales_invoices || 0 },
        { label: "Jumlah Sales", value: c.kasir_users || 0 },
        { label: "Jumlah Mitra", value: c.suppliers || 0 },
    ];
});

const dailyChart = computed(() => summary.value?.charts?.daily || null);
const monthlyChart = computed(() => summary.value?.charts?.monthly || null);

function chartRows(source) {
    if (!source?.labels?.length) return [];
    return source.labels.map((label, index) => ({
        label,
        sales: Number(source.sales?.[index] || 0),
        purchases: Number(source.purchases?.[index] || 0),
        profit: Number(source.profit?.[index] || 0),
    }));
}

// Memotong data menjadi 7 hari terakhir agar pas di layar
const dailyRows = computed(() => {
    const rows = chartRows(dailyChart.value);
    return rows.slice(-7);
});

const monthlyRows = computed(() => chartRows(monthlyChart.value));

function maxValue(rows, includeProfit = true, includePurchases = true) {
    if (!rows || rows.length === 0) return 1;
    return Math.max(
        1,
        ...rows.map((r) =>
            Math.max(
                r.sales || 0,
                includePurchases ? r.purchases || 0 : 0,
                includeProfit ? r.profit || 0 : 0
            )
        )
    );
}

// Konfigurasi dinamis untuk kedua grafik
const chartsData = computed(() => [
    {
        title: "Grafik Penjualan Perhari",
        rows: dailyRows.value,
        max: maxValue(dailyRows.value, !isKasir.value, !isKasir.value),
    },
    {
        title: `Grafik Bulanan (${new Date().getFullYear()})`,
        rows: monthlyRows.value,
        max: maxValue(monthlyRows.value, !isKasir.value, !isKasir.value),
    },
]);

// Logika tinggi batang (0% jika nilainya kosong/nol)
function barHeight(value, max) {
    if (!value || value <= 0) return "0%";
    if (!max) return "0%";
    return `${Math.max(1.5, (value / max) * 100)}%`;
}

// Logika pembagian sumbu Y
function getYAxisTicks(max) {
    return [1, 0.75, 0.5, 0.25, 0].map((ratio) => compact.format(max * ratio));
}

async function fetchSummary() {
    loading.value = true;
    try {
        const { data } = await api.get("/dashboard/summary");
        summary.value = data.data;
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal memuat dashboard");
    } finally {
        loading.value = false;
    }
}

onMounted(fetchSummary);
</script>

<template>
    <section
        class="min-h-screen p-4 space-y-8 font-sans bg-slate-50 sm:p-6 lg:p-8"
    >
        <!-- <div
            class="relative p-5 overflow-hidden text-white shadow-2xl sm:p-8 rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-black shadow-slate-900/20"
        >
            <div class="relative z-10">
                <p
                    class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-400"
                >
                    Ringkasan Bisnis
                </p>
                <h1
                    class="mt-3 text-2xl font-extrabold tracking-tight text-transparent sm:text-3xl lg:text-4xl bg-clip-text bg-gradient-to-r from-white to-slate-400"
                >
                    Dashboard Operasional
                </h1>
                <p class="max-w-xl mt-2 text-sm font-medium text-slate-400">
                    Pantau performa real-time penjualan, pembelian, laba, dan
                    matriks utama toko Anda dalam satu tampilan cerdas.
                </p>
            </div>
            <div
                class="absolute rounded-full pointer-events-none -right-10 -top-24 h-96 w-96 bg-gradient-to-br from-blue-500/20 to-purple-500/20 blur-3xl"
            ></div>
        </div> -->

        <div
            v-if="loading"
            class="flex flex-col items-center justify-center py-24 bg-white shadow-sm rounded-3xl ring-1 ring-slate-100"
        >
            <div
                class="w-10 h-10 border-4 rounded-full animate-spin border-slate-100 border-t-sky-500"
            ></div>
            <p class="mt-4 text-sm font-medium animate-pulse text-slate-400">
                Menyiapkan data dashboard...
            </p>
        </div>

        <template v-else>
            <div class="space-y-10">
                <div
                    v-for="(section, idx) in [
                        {
                            title: isKasir
                                ? `Penjualan ${kasirName}`
                                : 'Penjualan',
                            cards: salesCards,
                            color: colors.sales,
                        },
                    ].concat(
                        isKasir
                            ? []
                            : [
                                  {
                                      //   title: 'Laba',
                                      cards: profitCards,
                                      color: colors.profit,
                                  },
                                  {
                                      title: 'Pembelian',
                                      cards: purchaseCards,
                                      color: colors.purchases,
                                  },
                              ]
                    )"
                    :key="idx"
                    class="space-y-4"
                >
                    <h2
                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-slate-400"
                    >
                        <!-- <span
                            class="h-1.5 w-1.5 rounded-full bg-slate-400"
                        ></span> -->
                        {{ section.title }}
                    </h2>
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <article
                            v-for="card in section.cards"
                            :key="card.label"
                            class="p-5 transition-all duration-300 bg-white shadow-sm group rounded-2xl ring-1 ring-slate-200/60 hover:-translate-y-1 hover:shadow-lg"
                        >
                            <div
                                class="inline-flex items-center px-4 py-2 mb-4 text-sm font-bold text-white transition-transform shadow-md rounded-xl bg-gradient-to-r group-hover:scale-105"
                                :class="section.color"
                            >
                                {{ currency.format(card.value) }}
                            </div>
                            <p
                                class="text-xs font-semibold tracking-wider uppercase text-slate-400"
                            >
                                {{ card.label }}
                            </p>
                        </article>
                    </div>
                </div>

                <div v-if="!isKasir" class="space-y-4">
                    <h2
                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-slate-400"
                    >
                        <!-- <span
                            class="h-1.5 w-1.5 rounded-full bg-slate-400"
                        ></span>
                        Metrik Pendukung -->
                    </h2>
                    <div
                        class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-5"
                    >
                        <article
                            v-for="card in miniCards"
                            :key="card.label"
                            class="p-5 transition-all duration-300 bg-white shadow-sm rounded-2xl ring-1 ring-slate-200/60 hover:shadow-md"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
                            >
                                {{ card.label }}
                            </p>
                            <p
                                class="mt-2 text-3xl font-black tracking-tight text-slate-700"
                            >
                                {{ compact.format(card.value) }}
                            </p>
                        </article>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <section
                        v-for="(chart, idx) in chartsData"
                        :key="idx"
                        class="flex flex-col p-6 bg-white shadow-sm rounded-3xl ring-1 ring-slate-200/60"
                    >
                        <header
                            class="flex flex-wrap items-center justify-between gap-4 mb-6"
                        >
                            <h3 class="text-base font-extrabold text-slate-800">
                                {{ chart.title }}
                            </h3>
                            <div
                                class="flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-wide"
                            >
                                <span
                                    class="flex items-center gap-1.5 rounded-full bg-slate-50 border border-slate-100 px-3 py-1.5 text-sky-600"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-sky-500"
                                    ></span>
                                    Penjualan
                                </span>
                                <span
                                    v-if="!isKasir"
                                    class="flex items-center gap-1.5 rounded-full bg-slate-50 border border-slate-100 px-3 py-1.5 text-amber-600"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-amber-500"
                                    ></span>
                                    Laba
                                </span>
                                <span
                                    v-if="!isKasir"
                                    class="flex items-center gap-1.5 rounded-full bg-slate-50 border border-slate-100 px-3 py-1.5 text-rose-600"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-rose-500"
                                    ></span>
                                    Pembelian
                                </span>
                            </div>
                        </header>

                        <div
                            class="flex w-full gap-2 mt-auto h-45 sm:h-60 md:h-75"
                        >
                            <div
                                class="flex flex-col justify-between pb-8 text-right text-[10px] font-semibold text-slate-400 w-10"
                            >
                                <span
                                    v-for="tick in getYAxisTicks(chart.max)"
                                    :key="tick"
                                    >{{ tick }}</span
                                >
                            </div>

                            <div
                                class="relative flex-1 border-b border-l border-slate-200"
                            >
                                <div
                                    class="absolute inset-0 z-0 flex flex-col justify-between pb-8"
                                >
                                    <div
                                        v-for="i in 5"
                                        :key="i"
                                        class="w-full border-t border-dashed border-slate-200"
                                    ></div>
                                </div>

                                <div
                                    class="absolute inset-0 z-10 w-full px-1 sm:px-2"
                                >
                                    <div
                                        class="flex justify-between w-full h-full gap-1 sm:gap-2"
                                    >
                                        <div
                                            v-for="item in chart.rows"
                                            :key="item.label"
                                            class="flex flex-col items-center flex-1 h-full group"
                                        >
                                            <div
                                                class="relative flex-1 w-full transition-transform"
                                            >
                                                <div
                                                    class="absolute inset-x-0 bottom-0 flex h-full items-end justify-center gap-[1px] sm:gap-[2px]"
                                                >
                                                    <div
                                                        v-if="item.sales > 0"
                                                        class="w-full max-w-[14px] rounded-t-sm bg-gradient-to-t from-sky-600 to-sky-400 transition-opacity hover:opacity-80 cursor-pointer"
                                                        :style="{
                                                            height: barHeight(
                                                                item.sales,
                                                                chart.max
                                                            ),
                                                        }"
                                                        :title="`Penjualan: ${currency.format(
                                                            item.sales
                                                        )}`"
                                                    ></div>
                                                    <div
                                                        v-if="
                                                            !isKasir &&
                                                            item.profit > 0
                                                        "
                                                        class="w-full max-w-[14px] rounded-t-sm bg-gradient-to-t from-amber-500 to-amber-300 transition-opacity hover:opacity-80 cursor-pointer"
                                                        :style="{
                                                            height: barHeight(
                                                                item.profit,
                                                                chart.max
                                                            ),
                                                        }"
                                                        :title="`Laba: ${currency.format(
                                                            item.profit
                                                        )}`"
                                                    ></div>
                                                    <div
                                                        v-if="
                                                            !isKasir &&
                                                            item.purchases > 0
                                                        "
                                                        class="w-full max-w-[14px] rounded-t-sm bg-gradient-to-t from-rose-600 to-rose-400 transition-opacity hover:opacity-80 cursor-pointer"
                                                        :style="{
                                                            height: barHeight(
                                                                item.purchases,
                                                                chart.max
                                                            ),
                                                        }"
                                                        :title="`Pembelian: ${currency.format(
                                                            item.purchases
                                                        )}`"
                                                    ></div>
                                                </div>
                                            </div>
                                            <div
                                                class="h-8 flex w-full items-center justify-center text-center text-[9px] sm:text-[10px] font-medium text-slate-500"
                                            >
                                                <span class="truncate">{{
                                                    item.label
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </template>
    </section>
</template>
