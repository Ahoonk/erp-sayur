import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

import LoginView from "../views/LoginView.vue";
import DashboardView from "../views/DashboardView.vue";
import DashboardHome from "../views/DashboardHome.vue";
import ProfileView from "../views/ProfileView.vue";
import HomeView from "@/views/HomeView.vue";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: "/login",
            name: "login",
            component: LoginView,
            meta: { guest: true },
        },
        {
            path: "/forbidden",
            name: "forbidden",
            component: () => import("../views/errors/ForbiddenView.vue"),
        },
        {
            path: "/dashboard",
            component: DashboardView,
            meta: { requiresAuth: true },
            children: [
                { path: "/", name: "home", component: HomeView },
                { path: "/dashboard", name: "dashboard", component: DashboardHome },
                { path: "profile", name: "profile", component: ProfileView },

                // User Management
                {
                    path: "management/users",
                    name: "user-list",
                    component: () => import("../views/UserListView.vue"),
                    meta: { requiresAdmin: true },
                },
                {
                    path: "management/user/create",
                    name: "create-user",
                    component: () => import("../views/AdminUserCreateView.vue"),
                    meta: { requiresAdmin: true },
                },
                {
                    path: "management/roles",
                    name: "role-list",
                    component: () => import("../views/RoleManagerView.vue"),
                    meta: { requiresSuperAdmin: true },
                },

                // Settings
                {
                    path: "settings",
                    name: "store-settings",
                    component: () => import("../views/settings/StoreSettingView.vue"),
                    meta: { requiresAdmin: true },
                },

                // Stok Barang - Pembelian
                {
                    path: "purchases",
                    name: "purchase-list",
                    component: () => import("../views/purchase/PurchaseListView.vue"),
                    meta: { requiresStockView: true },
                },
                {
                    path: "purchases/create",
                    name: "purchase-create",
                    component: () => import("../views/purchase/PurchaseCreateView.vue"),
                    meta: { requiresStockView: true },
                },
                {
                    path: "purchases/:id/edit",
                    name: "purchase-edit",
                    component: () => import("../views/purchase/PurchaseCreateView.vue"),
                    meta: { requiresStockView: true },
                },
                {
                    path: "purchases/:id",
                    name: "purchase-detail",
                    component: () => import("../views/purchase/PurchaseDetailView.vue"),
                    meta: { requiresStockView: true },
                },

                // Stok Barang - Stock Summary
                {
                    path: "stock",
                    name: "stock-summary",
                    component: () => import("../views/stock/StockSummaryView.vue"),
                    meta: { requiresStockView: true },
                },

                // Stok Barang - Mutasi
                {
                    path: "mutasi",
                    name: "mutasi",
                    component: () => import("../views/stock/MutasiView.vue"),
                    meta: { requiresStockView: true },
                },
                // Stok Barang - Penyusutan
                {
                    path: "penyusutan",
                    name: "stock-adjustment",
                    component: () => import("../views/stock/StockAdjustmentView.vue"),
                    meta: { requiresStockView: true },
                },

                // Price List
                {
                    path: "pricelist/umum",
                    name: "pricelist-umum",
                    component: () => import("../views/pricelist/PricelistUmumView.vue"),
                    meta: { requiresAdmin: true },
                },
                {
                    path: "pricelist/mitra",
                    name: "pricelist-mitra",
                    component: () => import("../views/pricelist/PricelistMitraView.vue"),
                    meta: { requiresAdmin: true },
                },

                // Laporan
                {
                    path: "report/purchases",
                    name: "report-purchases",
                    component: () => import("../views/report/PurchaseReportView.vue"),
                    meta: { requiresAdmin: true },
                },
                {
                    path: "report/sales",
                    name: "report-sales",
                    component: () => import("../views/report/SalesReportView.vue"),
                    meta: { requiresAdmin: true },
                },

                // Master Data
                {
                    path: "master/katalog-barang",
                    name: "katalog-barang-list",
                    component: () => import("../views/masterdata/KatalogBarangListView.vue"),
                    meta: { requiresMasterDataView: true },
                },
                {
                    path: "master/categories",
                    name: "category-list",
                    component: () => import("../views/masterdata/CategoryListView.vue"),
                    meta: { requiresMasterDataView: true },
                },
                {
                    path: "master/units",
                    name: "unit-list",
                    component: () => import("../views/masterdata/UnitListView.vue"),
                    meta: { requiresSuperAdmin: true },
                },
                {
                    path: "master/suppliers",
                    name: "supplier-list",
                    component: () => import("../views/masterdata/SupplierListView.vue"),
                    meta: { requiresMasterDataView: true },
                },
                {
                    path: "master/mitra",
                    name: "mitra-list",
                    component: () => import("../views/masterdata/MitraListView.vue"),
                    meta: { requiresMasterDataView: true },
                },
            ],
        },
    ],
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    if (authStore.token && !authStore.user) {
        await authStore.fetchUser();
    }
    const isAuthenticated = authStore.isAuthenticated;
    if (to.meta.requiresAuth && !isAuthenticated) return next("/login");
    if (to.meta.guest && isAuthenticated) return next("/");
    if (to.meta.requiresAdmin && !authStore.isAdmin) return next("/forbidden");
    if (to.meta.requiresSuperAdmin && !authStore.isSuperAdmin) return next("/forbidden");
    if (to.meta.requiresStockView && !authStore.canViewPurchases) return next("/forbidden");
    if (to.meta.requiresMasterDataView && !authStore.canViewMasterData) return next("/forbidden");
    next();
});

router.addRoute({
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: () => import("../views/errors/NotFoundView.vue"),
});

export default router;
