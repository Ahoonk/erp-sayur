<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useAuthStore } from "../stores/auth";
import { useRouter } from "vue-router";
import { useToast } from "../composables/useToast";
import api from "../api";

const authStore = useAuthStore();
const router = useRouter();
const toast = useToast();

// Collapsible sidebar group state
const stokBarangOpen = ref(false);
const priceListOpen = ref(false);
const laporanOpen = ref(false);
const masterDataOpen = ref(false);
const manajemenOpen = ref(false);

const profileDropdownOpen = ref(false);
const mobileMenuOpen = ref(false);

const storeProfile = ref({
    name: "ERP Sayur",
    logo_url: null,
});

const handleLogout = async () => {
    await authStore.logout();
    toast.success("Berhasil logout");
    router.push("/login");
};

const userInitial = computed(() => {
    return authStore.user?.name?.charAt(0)?.toUpperCase() || "U";
});

const photoUrl = computed(() => {
    return authStore.user?.profile?.avatar_url;
});

const roleBadge = computed(() => {
    if (authStore.isSuperAdmin)
        return {
            label: "Super Admin",
            class: "bg-purple-100 text-purple-700 border-purple-200",
        };
    if (authStore.isAdmin)
        return {
            label: "Admin",
            class: "bg-blue-100 text-blue-700 border-blue-200",
        };
    return {
        label: "Kasir",
        class: "bg-green-100 text-green-700 border-green-200",
    };
});

async function fetchStoreProfile() {
    try {
        const { data } = await api.get("/store-settings");
        if (data?.data) {
            storeProfile.value = {
                name: data.data.name || "ERP Sayur",
                logo_url: data.data.logo_url || null,
            };
        }
    } catch (error) {
        // Keep fallback
    }
}

// Close profile dropdown on outside click
const closeDropdowns = (e) => {
    if (
        !e.target.closest(".dropdown-trigger") &&
        !e.target.closest(".nav-dropdown")
    ) {
        profileDropdownOpen.value = false;
    }
};

onMounted(() => {
    fetchStoreProfile();
    window.addEventListener("click", closeDropdowns);
});

onUnmounted(() => {
    window.removeEventListener("click", closeDropdowns);
});
</script>

<template>
    <div class="flex flex-col min-h-screen bg-slate-50">
        <!-- Top Navigation Bar -->
        <header
            class="sticky top-0 z-50 bg-white border-b shadow-sm border-slate-200 backdrop-blur-md bg-white/90"
        >
            <div
                class="flex items-center justify-between w-full h-16 gap-4 px-4 mx-auto sm:px-6 lg:px-8 xl:px-10 2xl:px-12"
            >
                <!-- Left: Logo -->
                <router-link to="/dashboard" class="flex items-center gap-3 shrink-0">
                    <div
                        :class="[
                            'flex items-center justify-center w-9 h-9 rounded-xl overflow-hidden',
                            storeProfile.logo_url
                                ? 'bg-transparent ring-1 ring-slate-200'
                                : 'bg-gradient-to-br from-emerald-500 to-green-700 shadow-lg shadow-emerald-500/20',
                        ]"
                    >
                        <img
                            v-if="storeProfile.logo_url"
                            :src="storeProfile.logo_url"
                            alt="Logo"
                            class="object-contain w-full h-full p-0.5"
                        />
                        <svg
                            v-else
                            class="w-5 h-5 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 3h14a2 2 0 012 2v1a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2zM3 10h18M3 14h18M5 18h14a2 2 0 002-2v-1a2 2 0 00-2-2H5a2 2 0 00-2 2v1a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                    <span
                        class="hidden text-xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-700 sm:block xl:hidden 2xl:block"
                    >
                        {{ storeProfile.name }}
                    </span>
                </router-link>

                <!-- Center: Desktop Menu -->
                <nav
                    class="items-center justify-center flex-1 hidden gap-0.5 xl:gap-1 xl:flex"
                >
                    <!-- Dashboard -->
                    <router-link
                        to="/dashboard"
                        class="nav-link"
                        :class="{
                            'nav-link-active': $route.name === 'dashboard',
                        }"
                    >
                        Dashboard
                    </router-link>

                    <!-- Stok Barang Dropdown -->
                    <div
                        v-if="authStore.canViewPurchases"
                        class="relative group"
                    >
                        <button
                            class="nav-link flex items-center gap-1.5"
                            :class="{
                                'nav-link-active':
                                    $route.path.includes('/dashboard/purchases') ||
                                    $route.path.includes('/dashboard/stock') ||
                                    $route.path.includes('/dashboard/mutasi') ||
                                    $route.path.includes('/dashboard/penyusutan'),
                            }"
                        >
                            Stok Barang
                            <svg
                                class="w-3.5 h-3.5 opacity-60"
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
                        </button>
                        <div class="dropdown-menu">
                            <router-link
                                to="/dashboard/purchases"
                                class="dropdown-item"
                                >Pembelian (Invoice)</router-link
                            >
                            <router-link
                                to="/dashboard/stock"
                                class="dropdown-item"
                                >Stok Barang</router-link
                            >
                            <router-link
                                to="/dashboard/mutasi"
                                class="dropdown-item"
                                >Mutasi</router-link
                            >
                            <router-link
                                to="/dashboard/penyusutan"
                                class="dropdown-item"
                                >Penyusutan</router-link
                            >
                        </div>
                    </div>

                    <!-- Price List Dropdown -->
                    <div
                        v-if="authStore.isAdmin"
                        class="relative group"
                    >
                        <button
                            class="nav-link flex items-center gap-1.5"
                            :class="{
                                'nav-link-active':
                                    $route.path.includes('/dashboard/pricelist'),
                            }"
                        >
                            Price List
                            <svg
                                class="w-3.5 h-3.5 opacity-60"
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
                        </button>
                        <div class="dropdown-menu">
                            <router-link
                                to="/dashboard/pricelist/umum"
                                class="dropdown-item"
                                >Price List Umum</router-link
                            >
                            <router-link
                                to="/dashboard/pricelist/mitra"
                                class="dropdown-item"
                                >Price List Mitra</router-link
                            >
                        </div>
                    </div>

                    <!-- Laporan Dropdown -->
                    <div
                        v-if="authStore.isAdmin"
                        class="relative group"
                    >
                        <button
                            class="nav-link flex items-center gap-1.5"
                            :class="{
                                'nav-link-active':
                                    $route.path.includes('/dashboard/report'),
                            }"
                        >
                            Laporan
                            <svg
                                class="w-3.5 h-3.5 opacity-60"
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
                        </button>
                        <div class="dropdown-menu">
                            <router-link
                                to="/dashboard/report/purchases"
                                class="dropdown-item"
                                >Laporan Pembelian</router-link
                            >
                            <router-link
                                to="/dashboard/report/sales"
                                class="dropdown-item"
                                >Laporan Penjualan</router-link
                            >
                        </div>
                    </div>

                    <!-- Master Data Dropdown -->
                    <div
                        v-if="authStore.canViewMasterData"
                        class="relative group"
                    >
                        <button
                            class="nav-link flex items-center gap-1.5"
                            :class="{
                                'nav-link-active':
                                    $route.path.includes('/dashboard/master'),
                            }"
                        >
                            Master Data
                            <svg
                                class="w-3.5 h-3.5 opacity-60"
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
                        </button>
                        <div class="w-56 dropdown-menu">
                            <router-link
                                to="/dashboard/master/katalog-barang"
                                class="dropdown-item"
                                >Katalog Barang</router-link
                            >
                            <router-link
                                to="/dashboard/master/categories"
                                class="dropdown-item"
                                >Kategori</router-link
                            >
                            <router-link
                                v-if="authStore.isSuperAdmin"
                                to="/dashboard/master/units"
                                class="dropdown-item"
                                >Satuan</router-link
                            >
                            <router-link
                                to="/dashboard/master/suppliers"
                                class="dropdown-item"
                                >Supplier</router-link
                            >
                            <router-link
                                to="/dashboard/master/mitra"
                                class="dropdown-item"
                                >Mitra</router-link
                            >
                        </div>
                    </div>

                    <!-- Manajemen Dropdown -->
                    <div
                        v-if="authStore.isAdmin || authStore.isSuperAdmin"
                        class="relative group"
                    >
                        <button
                            class="nav-link flex items-center gap-1.5"
                            :class="{
                                'nav-link-active':
                                    $route.path.includes('/dashboard/management'),
                            }"
                        >
                            Manajemen
                            <svg
                                class="w-3.5 h-3.5 opacity-60"
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
                        </button>
                        <div class="w-56 dropdown-menu">
                            <router-link
                                to="/dashboard/management/users"
                                class="dropdown-item"
                                >User</router-link
                            >
                            <router-link
                                v-if="authStore.isSuperAdmin"
                                to="/dashboard/management/roles"
                                class="dropdown-item"
                                >Roles</router-link
                            >
                        </div>
                    </div>

                    <!-- Pengaturan -->
                    <router-link
                        v-if="authStore.isAdmin"
                        to="/dashboard/settings"
                        class="nav-link"
                        :class="{
                            'nav-link-active': $route.name === 'store-settings',
                        }"
                        >Pengaturan</router-link
                    >
                </nav>

                <!-- Right: User Profile & Dropdown -->
                <div class="flex items-center gap-2">
                    <!-- Mobile Menu Toggle -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 xl:hidden text-slate-500 hover:bg-slate-100 rounded-xl"
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
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <div class="relative dropdown-trigger">
                        <button
                            @click="profileDropdownOpen = !profileDropdownOpen"
                            class="flex items-center gap-2 p-1.5 hover:bg-slate-100 rounded-2xl transition-all duration-300"
                        >
                            <div
                                class="overflow-hidden border-2 border-white shadow-inner w-9 h-9 rounded-xl ring-1 ring-slate-200"
                            >
                                <img
                                    v-if="photoUrl"
                                    :src="photoUrl"
                                    class="object-cover w-full h-full"
                                />
                                <div
                                    v-else
                                    class="flex items-center justify-center w-full h-full bg-gradient-to-br from-emerald-500 to-green-600"
                                >
                                    <span class="font-bold text-white">{{
                                        userInitial
                                    }}</span>
                                </div>
                            </div>
                            <div class="hidden mr-1 text-left 2xl:block">
                                <p
                                    class="text-xs font-bold leading-none text-slate-800"
                                >
                                    {{ authStore.user?.name }}
                                </p>
                                <span
                                    class="text-[10px] text-slate-500 font-medium"
                                    >{{ roleBadge.label }}</span
                                >
                            </div>
                            <svg
                                class="hidden w-4 h-4 text-slate-400 2xl:block"
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
                        </button>

                        <!-- Profile Dropdown -->
                        <div
                            v-show="profileDropdownOpen"
                            class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-2xl shadow-xl py-2 z-[60] animate-in-fade-slide overflow-hidden font-medium"
                        >
                            <div
                                class="px-4 py-3 mb-1 border-b border-slate-100"
                            >
                                <p class="text-sm font-bold text-slate-800">
                                    {{ authStore.user?.name }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ roleBadge.label }}
                                </p>
                            </div>
                            <router-link
                                to="/dashboard/profile"
                                class="dropdown-item flex items-center gap-3 py-2.5"
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
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                                Edit Profile
                            </router-link>
                            <router-link
                                v-if="authStore.isAdmin || authStore.isSuperAdmin"
                                to="/dashboard/settings"
                                class="dropdown-item flex items-center gap-3 py-2.5"
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
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    />
                                </svg>
                                Pengaturan
                            </router-link>
                            <div class="h-px mx-2 my-1 bg-slate-100"></div>
                            <button
                                @click="handleLogout"
                                class="w-full text-left dropdown-item flex items-center gap-3 py-2.5 text-rose-600 hover:bg-rose-50"
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
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                    />
                                </svg>
                                Keluar Aplikasi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Navigation Drawer -->
        <transition name="mobile-menu">
            <div
                v-show="mobileMenuOpen"
                class="fixed inset-0 z-[100] xl:hidden"
            >
                <div
                    @click="mobileMenuOpen = false"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
                ></div>
                <div
                    class="fixed top-0 bottom-0 left-0 w-[80%] max-w-sm bg-white shadow-2xl flex flex-col p-6 overflow-y-auto"
                >
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-800">Menu Utama</span>
                        </div>
                        <button
                            @click="mobileMenuOpen = false"
                            class="p-2 rounded-lg bg-slate-100"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-1">
                        <!-- Dashboard -->
                        <router-link
                            @click="mobileMenuOpen = false"
                            to="/dashboard"
                            class="mobile-link"
                            :class="{
                                'mobile-link-active': $route.name === 'dashboard',
                            }"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </router-link>

                        <!-- Stok Barang Group -->
                        <div v-if="authStore.canViewPurchases">
                            <button
                                @click="stokBarangOpen = !stokBarangOpen"
                                class="mobile-group-btn"
                                :class="{ 'mobile-group-btn-active': $route.path.includes('/dashboard/purchases') || $route.path.includes('/dashboard/stock') || $route.path.includes('/dashboard/mutasi') || $route.path.includes('/dashboard/penyusutan') }"
                            >
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    Stok Barang
                                </span>
                                <svg
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{ 'rotate-180': stokBarangOpen }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="stokBarangOpen" class="mt-1 ml-6 space-y-1">
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/purchases"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path.includes('/dashboard/purchases') }"
                                    >Pembelian (Invoice)</router-link
                                >
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/stock"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path === '/dashboard/stock' }"
                                    >Stok Barang</router-link
                                >
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/mutasi"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path === '/dashboard/mutasi' }"
                                    >Mutasi</router-link
                                >
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/penyusutan"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path === '/dashboard/penyusutan' }"
                                    >Penyusutan</router-link
                                >
                            </div>
                        </div>

                        <!-- Price List Group -->
                        <div v-if="authStore.isAdmin">
                            <button
                                @click="priceListOpen = !priceListOpen"
                                class="mobile-group-btn"
                                :class="{ 'mobile-group-btn-active': $route.path.includes('/dashboard/pricelist') }"
                            >
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    Price List
                                </span>
                                <svg
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{ 'rotate-180': priceListOpen }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="priceListOpen" class="mt-1 ml-6 space-y-1">
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/pricelist/umum"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path === '/dashboard/pricelist/umum' }"
                                    >Price List Umum</router-link
                                >
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/pricelist/mitra"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path === '/dashboard/pricelist/mitra' }"
                                    >Price List Mitra</router-link
                                >
                            </div>
                        </div>

                        <!-- Laporan Group -->
                        <div v-if="authStore.isAdmin">
                            <button
                                @click="laporanOpen = !laporanOpen"
                                class="mobile-group-btn"
                                :class="{ 'mobile-group-btn-active': $route.path.includes('/dashboard/report') }"
                            >
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Laporan
                                </span>
                                <svg
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{ 'rotate-180': laporanOpen }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="laporanOpen" class="mt-1 ml-6 space-y-1">
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/report/purchases"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path === '/dashboard/report/purchases' }"
                                    >Laporan Pembelian</router-link
                                >
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/report/sales"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path === '/dashboard/report/sales' }"
                                    >Laporan Penjualan</router-link
                                >
                            </div>
                        </div>

                        <!-- Master Data Group -->
                        <div v-if="authStore.canViewMasterData">
                            <button
                                @click="masterDataOpen = !masterDataOpen"
                                class="mobile-group-btn"
                                :class="{ 'mobile-group-btn-active': $route.path.includes('/dashboard/master') }"
                            >
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                    </svg>
                                    Master Data
                                </span>
                                <svg
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{ 'rotate-180': masterDataOpen }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="masterDataOpen" class="mt-1 ml-6 space-y-1">
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/master/katalog-barang"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path.includes('/dashboard/master/katalog-barang') }"
                                    >Katalog Barang</router-link
                                >
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/master/categories"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path.includes('/dashboard/master/categories') }"
                                    >Kategori</router-link
                                >
                                <router-link
                                    v-if="authStore.isSuperAdmin"
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/master/units"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path.includes('/dashboard/master/units') }"
                                    >Satuan</router-link
                                >
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/master/suppliers"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path.includes('/dashboard/master/suppliers') }"
                                    >Supplier</router-link
                                >
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/master/mitra"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path.includes('/dashboard/master/mitra') }"
                                    >Mitra</router-link
                                >
                            </div>
                        </div>

                        <!-- Manajemen Group -->
                        <div v-if="authStore.isAdmin || authStore.isSuperAdmin">
                            <button
                                @click="manajemenOpen = !manajemenOpen"
                                class="mobile-group-btn"
                                :class="{ 'mobile-group-btn-active': $route.path.includes('/dashboard/management') }"
                            >
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Manajemen
                                </span>
                                <svg
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{ 'rotate-180': manajemenOpen }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-show="manajemenOpen" class="mt-1 ml-6 space-y-1">
                                <router-link
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/management/users"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path.includes('/dashboard/management/users') }"
                                    >User</router-link
                                >
                                <router-link
                                    v-if="authStore.isSuperAdmin"
                                    @click="mobileMenuOpen = false"
                                    to="/dashboard/management/roles"
                                    class="mobile-sublink"
                                    :class="{ 'mobile-sublink-active': $route.path.includes('/dashboard/management/roles') }"
                                    >Roles</router-link
                                >
                            </div>
                        </div>

                        <!-- Pengaturan -->
                        <router-link
                            v-if="authStore.isAdmin"
                            @click="mobileMenuOpen = false"
                            to="/dashboard/settings"
                            class="mobile-link"
                            :class="{ 'mobile-link-active': $route.name === 'store-settings' }"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            </svg>
                            Pengaturan
                        </router-link>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Main Page Content -->
        <main
            class="flex-1 w-full px-4 py-4 mx-auto sm:px-6 lg:px-8 xl:px-10 2xl:px-12 md:py-6 lg:py-8 animate-in-fade"
        >
            <router-view v-slot="{ Component }">
                <transition name="page" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </main>

        <!-- Footer — hidden on print pages -->
        <footer
            v-if="!$route.path.includes('/invoice') && !$route.path.includes('/print')"
            class="px-6 py-4 mt-auto text-center border-t border-slate-200"
        >
            <p
                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"
            >
                &copy; 2024 - {{ new Date().getFullYear() }}
                {{ storeProfile.name }}.
            </p>
        </footer>
    </div>
</template>

<style scoped>
.nav-link {
    @apply px-2 py-1.5 xl:px-2.5 xl:py-2 text-[11px] 2xl:text-[13px] font-semibold text-slate-600 rounded-lg transition-all duration-300 hover:text-emerald-600 hover:bg-emerald-50/50 relative whitespace-nowrap uppercase;
}
.nav-link-active {
    @apply text-emerald-700 bg-emerald-50 shadow-sm shadow-emerald-500/5 ring-1 ring-emerald-100/50;
}
.dropdown-menu {
    @apply absolute top-full left-0 mt-0 hidden group-hover:block bg-white border border-slate-200 rounded-2xl shadow-xl py-2 z-[60] animate-in-fade-slide min-w-[220px] uppercase;
}
.group:hover .dropdown-menu,
.dropdown-menu:hover {
    @apply block;
}
.dropdown-item {
    @apply px-4 py-2.5 text-xs 2xl:text-[13px] font-medium text-slate-600 hover:text-emerald-700 hover:bg-emerald-50/70 transition-all duration-150 flex items-center;
}
.mobile-link {
    @apply flex items-center gap-2 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all duration-150;
}
.mobile-link-active {
    @apply text-emerald-600 bg-emerald-50;
}
.mobile-group-btn {
    @apply flex items-center justify-between w-full px-3 py-2.5 text-sm font-semibold text-slate-700 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-all duration-150;
}
.mobile-group-btn-active {
    @apply text-emerald-700 bg-emerald-50/80;
}
.mobile-sublink {
    @apply block py-2 px-3 text-sm font-medium text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all duration-150;
}
.mobile-sublink-active {
    @apply text-emerald-600 font-semibold bg-emerald-50;
}

/* Animations */
@keyframes fadeInSlide {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-in-fade-slide {
    animation: fadeInSlide 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
.animate-in-fade {
    animation: fadeIn 0.4s ease-out forwards;
}

.page-enter-active,
.page-leave-active {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.page-enter-from {
    opacity: 0;
    transform: translateY(5px);
}
.page-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}

.mobile-menu-enter-active,
.mobile-menu-leave-active {
    transition: all 0.3s ease;
}
.mobile-menu-enter-from,
.mobile-menu-leave-to {
    opacity: 0;
}
.mobile-menu-enter-from > div:last-child {
    transform: translateX(-100%);
}
.mobile-menu-enter-to > div:last-child {
    transform: translateX(0);
}
.mobile-menu-leave-to > div:last-child {
    transform: translateX(-100%);
}
</style>
