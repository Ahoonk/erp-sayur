<script setup>
import { computed } from "vue";
import { useAuthStore } from "../stores/auth";
import { useRouter } from "vue-router";

const authStore = useAuthStore();
const router = useRouter();

const userName = computed(() => authStore.user?.name || "Pengguna");

// Menggunakan path SVG dari Heroicons yang lebih modern dan sesuai konteks
const menuTransaksi = [
    {
        label: "Jual Barang",
        desc: "Buat transaksi penjualan / kasir baru",
        icon: "M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z", // Shopping Cart
        to: "/dashboard/pos",
        color: "from-blue-500 to-indigo-600",
        borderHover: "hover:border-blue-300",
        shadowHover: "hover:shadow-blue-200",
        textHover: "group-hover:text-blue-600",
    },
    {
        label: "Riwayat Transaksi",
        desc: "Lihat daftar dan detail transaksi penjualan",
        icon: "M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z", // Document Text
        to: "/dashboard/sales",
        color: "from-indigo-500 to-purple-600",
        borderHover: "hover:border-indigo-300",
        shadowHover: "hover:shadow-indigo-200",
        textHover: "group-hover:text-indigo-600",
    },
];

const menuPembelian = computed(() => [
    {
        label: "Tambah Pembelian",
        desc: "Input faktur / stok barang baru dari supplier",
        icon: "M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375M5.25 8.25h14.5m-14.5 0v5.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125v-5.25m-12 0h12", // Truck
        to: "/dashboard/purchases/create",
        color: "from-emerald-400 to-emerald-600",
        borderHover: "hover:border-emerald-300",
        shadowHover: "hover:shadow-emerald-200",
        textHover: "group-hover:text-emerald-600",
        show: authStore.canViewPurchases,
    },
    {
        label: "Stok Barang",
        desc: "Pantau ketersediaan barang siap jual",
        icon: "M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9", // Cube
        to: "/dashboard/stock-summary",
        color: "from-teal-400 to-teal-600",
        borderHover: "hover:border-teal-300",
        shadowHover: "hover:shadow-teal-200",
        textHover: "group-hover:text-teal-600",
        show: authStore.canViewPurchases,
    },
    {
        label: "Keseluruhan Barang",
        desc: "Rekapitulasi semua barang dari seluruh invoice",
        icon: "M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z", // Clipboard
        to: "/dashboard/purchase-items",
        color: "from-cyan-400 to-cyan-600",
        borderHover: "hover:border-cyan-300",
        shadowHover: "hover:shadow-cyan-200",
        textHover: "group-hover:text-cyan-600",
        show: authStore.canViewPurchases,
    },
]);

const menuMasterData = computed(() => [
    {
        label: "Katalog Produk",
        desc: "Kelola master produk & SKU",
        icon: "M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z", // Tag
        to: "/dashboard/master/products",
        color: "from-violet-400 to-violet-600",
        textHover: "group-hover:text-violet-600",
        show: authStore.canViewMasterData,
    },
    {
        label: "Merk",
        desc: "Kelola data merk produk",
        icon: "M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z", // Star
        to: "/dashboard/master/brands",
        color: "from-pink-400 to-pink-600",
        textHover: "group-hover:text-pink-600",
        show: authStore.canViewMasterData,
    },
    {
        label: "Grade",
        desc: "Kelola grade / kondisi produk",
        icon: "M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z", // Badge Check
        to: "/dashboard/master/grades",
        color: "from-amber-400 to-amber-600",
        textHover: "group-hover:text-amber-600",
        show: authStore.canViewMasterData,
    },
    {
        label: "Supplier",
        desc: "Kelola data pemasok / supplier",
        icon: "M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z", // Building Office
        to: "/dashboard/master/suppliers",
        color: "from-orange-400 to-orange-600",
        textHover: "group-hover:text-orange-600",
        show: authStore.canViewMasterData,
    },
]);
</script>

<template>
    <div class="min-h-screen py-8 font-sans bg-slate-50">
        <div class="max-w-6xl px-4 mx-auto space-y-12 sm:px-6">
            <!-- <header class="flex flex-col gap-1 mb-8">
                <h1
                    class="text-3xl font-extrabold tracking-tight text-slate-800"
                >
                    Halo, {{ userName }} 👋
                </h1>
                <p class="text-slate-500">
                    Pilih menu di bawah ini untuk memulai aktivitas Anda hari
                    ini.
                </p>
            </header> -->

            <section>
                <div class="flex items-center gap-3 mb-5">
                    <div
                        class="w-1.5 h-6 bg-blue-600 rounded-full shadow-sm"
                    ></div>
                    <h2
                        class="text-sm font-bold tracking-widest uppercase text-slate-600"
                    >
                        Transaksi Utama
                    </h2>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <button
                        v-for="item in menuTransaksi"
                        :key="item.label"
                        @click="router.push(item.to)"
                        class="flex items-center w-full gap-5 p-5 text-left transition-all duration-300 bg-white border shadow-sm group rounded-2xl border-slate-100 hover:-translate-y-1 hover:shadow-lg"
                        :class="[item.borderHover, item.shadowHover]"
                    >
                        <div
                            class="flex items-center justify-center shadow-inner w-14 h-14 shrink-0 rounded-2xl bg-gradient-to-br"
                            :class="item.color"
                        >
                            <svg
                                class="text-white w-7 h-7 drop-shadow-sm"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="item.icon"
                                />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-lg font-bold transition-colors text-slate-800"
                                :class="item.textHover"
                            >
                                {{ item.label }}
                            </p>
                            <p class="mt-1 text-sm text-slate-400 line-clamp-2">
                                {{ item.desc }}
                            </p>
                        </div>
                        <div
                            class="flex items-center justify-center w-8 h-8 transition-colors rounded-full bg-slate-50 group-hover:bg-blue-50 shrink-0"
                        >
                            <svg
                                class="w-4 h-4 transition-colors text-slate-400 group-hover:text-blue-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </div>
                    </button>
                </div>
            </section>

            <section v-if="authStore.canViewPurchases">
                <div class="flex items-center gap-3 mb-5">
                    <div
                        class="w-1.5 h-6 rounded-full bg-emerald-500 shadow-sm"
                    ></div>
                    <h2
                        class="text-sm font-bold tracking-widest uppercase text-slate-600"
                    >
                        Manajemen Stok
                    </h2>
                </div>
                <div
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <button
                        v-for="item in menuPembelian.filter((m) => m.show)"
                        :key="item.label"
                        @click="router.push(item.to)"
                        class="flex items-center w-full gap-4 p-5 text-left transition-all duration-300 bg-white border shadow-sm group rounded-2xl border-slate-100 hover:-translate-y-1 hover:shadow-lg"
                        :class="[item.borderHover, item.shadowHover]"
                    >
                        <div
                            class="flex items-center justify-center w-12 h-12 shadow-inner shrink-0 rounded-xl bg-gradient-to-br"
                            :class="item.color"
                        >
                            <svg
                                class="w-6 h-6 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="item.icon"
                                />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="font-bold transition-colors text-slate-800"
                                :class="item.textHover"
                            >
                                {{ item.label }}
                            </p>
                            <p
                                class="text-xs text-slate-400 mt-0.5 line-clamp-2"
                            >
                                {{ item.desc }}
                            </p>
                        </div>
                    </button>
                </div>
            </section>

            <section v-if="authStore.canViewMasterData">
                <div class="flex items-center gap-3 mb-5">
                    <div
                        class="w-1.5 h-6 rounded-full bg-violet-500 shadow-sm"
                    ></div>
                    <h2
                        class="text-sm font-bold tracking-widest uppercase text-slate-600"
                    >
                        Data Master
                    </h2>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <button
                        v-for="item in menuMasterData.filter((m) => m.show)"
                        :key="item.label"
                        @click="router.push(item.to)"
                        class="flex flex-col items-center w-full gap-4 p-6 text-center transition-all duration-300 bg-white border shadow-sm group rounded-2xl border-slate-100 hover:shadow-md hover:border-violet-200 hover:-translate-y-1"
                    >
                        <div
                            class="flex items-center justify-center transition-transform duration-300 rounded-full shadow-inner w-14 h-14 bg-gradient-to-br group-hover:scale-110"
                            :class="item.color"
                        >
                            <svg
                                class="text-white w-7 h-7"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="item.icon"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-sm font-bold transition-colors text-slate-800"
                                :class="item.textHover"
                            >
                                {{ item.label }}
                            </p>
                            <p
                                class="text-xs text-slate-400 mt-1.5 leading-relaxed"
                            >
                                {{ item.desc }}
                            </p>
                        </div>
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>
