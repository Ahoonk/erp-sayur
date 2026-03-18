<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import DateInput from "../../components/DateInput.vue";
import { useAuthStore } from "../../stores/auth";
import { useRouter, useRoute } from "vue-router";
import api from "../../api";
import { useToast } from "../../composables/useToast";
import CurrencyInput from "../../components/CurrencyInput.vue";
import debounce from "lodash-es/debounce";

const toast = useToast();
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const editId = ref(route.query.edit_id || null);
const isEditing = computed(() => !!editId.value);

const cart = ref([]);
const taxOptions = ref([]);
const salesReps = ref([]);
const users = ref([]);

function getJakartaDateISO() {
    const parts = new Intl.DateTimeFormat("en-CA", {
        timeZone: "Asia/Jakarta",
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    }).formatToParts(new Date());

    const year = parts.find((p) => p.type === "year")?.value;
    const month = parts.find((p) => p.type === "month")?.value;
    const day = parts.find((p) => p.type === "day")?.value;

    return `${year}-${month}-${day}`;
}

function formatTanggalIndonesia(isoDate) {
    if (!isoDate) return "-";
    const [year, month, day] = String(isoDate).split("-");
    if (!year || !month || !day) return String(isoDate);
    return `${day}-${month}-${year}`;
}

const today = getJakartaDateISO();

const form = ref({
    tanggal_invoice: today,
    pelanggan: "",
    user_id: authStore.user?.id || "",
    sales_rep_id: "",
    tax_id: "",
    tax_persen: 0,
    diskon_type: "nominal", // 'persen' | 'nominal'
    diskon_persen: 0,
    diskon_nominal: 0,
    metode_pembayaran: "cash",
    jumlah_bayar: "",
});

const isProcessing = ref(false);

const inputForm = ref({
    product_id: "",
    kode: "",
    nama: "",
    satuan: "",
    imei1: "",
    imei2: "",
    harga_modal: 0,
    harga_jual: 0,
    maxStok: 0,
});

const showSearchModal = ref(false);
const modalSearchQuery = ref("");
const modalItems = ref([]);
const isSearchingModal = ref(false);
const modalPagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
});

// For currency inputs inside the checkout box
const displayJumlahBayar = ref("");
const displayDiskonNominal = ref("");

function formatInputCurrency(val) {
    if (val === 0) return "0";
    if (!val) return "";
    let str = val.toString().replace(/\D/g, "");
    return new Intl.NumberFormat("id-ID").format(str);
}

function parseInputCurrency(val) {
    if (!val) return 0;
    return parseInt(val.toString().replace(/\D/g, "")) || 0;
}

watch(
    () => displayJumlahBayar.value,
    (newVal) => {
        const rawValue = parseInputCurrency(newVal);
        if (form.value.jumlah_bayar !== rawValue) {
            form.value.jumlah_bayar = rawValue;
        }
        const formatted = formatInputCurrency(rawValue);
        if (displayJumlahBayar.value !== formatted) {
            displayJumlahBayar.value = formatted;
        }
    }
);

watch(
    () => displayDiskonNominal.value,
    (newVal) => {
        const rawValue = parseInputCurrency(newVal);
        if (form.value.diskon_nominal !== rawValue) {
            form.value.diskon_nominal = rawValue;
        }
        const formatted = formatInputCurrency(rawValue);
        if (displayDiskonNominal.value !== formatted) {
            displayDiskonNominal.value = formatted;
        }
    }
);

onMounted(async () => {
    await fetchOptions();
    if (isEditing.value) {
        await fetchSaleDetails();
    }
});

async function fetchOptions() {
    try {
        const [resTax, resUsers] = await Promise.all([
            api.get("/taxes"),
            api.get("/user/all"),
        ]);
        taxOptions.value = resTax.data.data.data;
        users.value = resUsers.data.data || [];
        salesReps.value = users.value.filter(
            (user) => !user.roles?.some((role) => role.name === "super-admin")
        );

        if (!isEditing.value) {
            if (authStore.user) {
                form.value.user_id = authStore.user.id;
            }
        }
    } catch (e) {
        console.error("Failed to fetch options", e);
    }
}

async function fetchSaleDetails() {
    try {
        const res = await api.get(`/sales/${editId.value}`);
        const sale = res.data.data;

        form.value.tanggal_invoice = sale.tanggal
            ? sale.tanggal.split("T")[0]
            : today;
        form.value.pelanggan = sale.pelanggan;
        form.value.user_id = sale.user?.id || "";
        form.value.sales_rep_id = sale.sales_rep_id || "";
        form.value.tax_id = sale.tax_id || "";
        form.value.tax_persen = parseFloat(sale.tax_persen) || 0;

        if (parseFloat(sale.diskon_persen) > 0) {
            form.value.diskon_type = "persen";
            form.value.diskon_persen = parseFloat(sale.diskon_persen);
        } else {
            form.value.diskon_type = "nominal";
            form.value.diskon_nominal = parseFloat(sale.diskon_nominal);
        }

        form.value.metode_pembayaran = sale.metode_pembayaran;
        form.value.jumlah_bayar = sale.jumlah_bayar;
        displayJumlahBayar.value = formatInputCurrency(sale.jumlah_bayar);

        if (sale.diskon_nominal > 0) {
            displayDiskonNominal.value = formatInputCurrency(
                sale.diskon_nominal
            );
        }

        cart.value = sale.items.map((item) => ({
            id: item.product_id,
            kode: item.product?.barcode,
            nama: item.product?.nama,
            imei: [item.product?.imei1, item.product?.imei2]
                .filter(Boolean)
                .join(" / "),
            satuan: item.product?.masterProduct?.unit?.nama || "-",
            harga_jual: parseFloat(item.harga_satuan),
            qty: item.qty,
            stok: (item.product?.stok || 0) + item.qty,
            maxStok: (item.product?.stok || 0) + item.qty,
        }));
    } catch (e) {
        toast.error("Gagal memuat detail transaksi");
        editId.value = null;
    }
}

function formatCurrency(val) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
}

// --------------------- LEFT INPUT FORM METHODS ------------------------
async function searchByCode() {
    if (!inputForm.value.kode) return;
    try {
        const res = await api.get("/products/scan", {
            params: { code: inputForm.value.kode },
        });
        const p = res.data.data;
        populateInputForm(p);
        toast.success("Produk ditemukan!");
    } catch (e) {
        toast.error("Kode tidak ditemukan atau stok kosong");
    }
}

function populateInputForm(p) {
    if (!p) return;
    inputForm.value = {
        product_id: p.id,
        kode: p.barcode,
        nama: p.nama || p.product?.nama,
        satuan:
            p.unit?.nama ||
            p.unit ||
            p.masterProduct?.unit?.nama ||
            p.product?.unit ||
            "-",
        imei1: p.imei1 || p.product?.imei1 || "-",
        imei2: p.imei2 || p.product?.imei2 || "-",
        harga_modal: parseFloat(p.harga_modal || p.harga_beli || 0),
        harga_jual: parseFloat(p.harga_jual || p.product?.harga_jual || 0),
        maxStok: p.stok || p.qty, // 'qty' is max limit for specific purchase item
    };
}

function addToCart() {
    if (!inputForm.value.product_id)
        return toast.error("Pilih produk terlebih dahulu");
    if (inputForm.value.harga_jual <= 0)
        return toast.error("Harga jual tidak valid");

    const product = inputForm.value;
    const existing = cart.value.find((i) => i.id === product.product_id);

    if (existing) {
        if (existing.qty < existing.maxStok) {
            existing.qty++;
            toast.success("Kuantitas ditambah");
        } else {
            toast.error("Stok maksimal tercapai");
        }
    } else {
        cart.value.push({
            id: product.product_id,
            kode: product.kode,
            nama: product.nama,
            satuan: product.satuan,
            imei1: product.imei1,
            imei2: product.imei2,
            harga_jual: product.harga_jual,
            qty: 1,
            maxStok: product.maxStok,
        });
    }
    inputForm.value = {
        product_id: "",
        kode: "",
        nama: "",
        satuan: "",
        imei1: "-",
        imei2: "-",
        harga_modal: 0,
        harga_jual: 0,
        maxStok: 0,
    };
}

// --------------------- MODAL & SEARCH ------------------------
const debouncedModalSearch = debounce(() => {
    fetchModalItems(1);
}, 300);

watch(modalSearchQuery, () => {
    debouncedModalSearch();
});

async function fetchModalItems(page = 1) {
    isSearchingModal.value = true;
    try {
        const { data } = await api.get("/purchases/items", {
            params: {
                search: modalSearchQuery.value,
                status: "available",
                page,
                per_page: 10,
            },
        });
        modalItems.value = data.data.data;
        modalPagination.value = {
            current_page: data.data.current_page,
            last_page: data.data.last_page,
            total: data.data.total,
        };
    } catch (e) {
        toast.error("Gagal memuat daftar barang");
    } finally {
        isSearchingModal.value = false;
    }
}

function openSearchModal() {
    showSearchModal.value = true;
    modalSearchQuery.value = "";
    fetchModalItems(1);
}

function selectFromModal(item) {
    // This receives a purchase item wrapping a product
    populateInputForm({
        ...item.product,
        harga_beli: item.harga_beli,
        qty: item.qty, // Available qty for this purchase item
    });
    showSearchModal.value = false;
    toast.success("Barang dipilih!");
}

// --------------------- CART METHODS ------------------------
function updateQty(item, amount) {
    const newQty = item.qty + amount;
    if (newQty >= 1 && newQty <= item.maxStok) {
        item.qty = newQty;
    } else if (newQty > item.maxStok) {
        toast.error(`Maksimal stok tersisa: ${item.maxStok}`);
    } else if (newQty < 1) {
        item.qty = 1;
    }
}
function removeFromCart(idx) {
    cart.value.splice(idx, 1);
}

// --------------------- COMPUTED TOTALS ------------------------
const activePelanggan = computed(() => form.value.pelanggan || "NN");

const subtotal = computed(() => {
    return cart.value.reduce(
        (acc, item) => acc + item.harga_jual * item.qty,
        0
    );
});

const diskonNominal = computed(() => {
    if (form.value.diskon_type === "persen") {
        return Math.floor(
            subtotal.value * ((form.value.diskon_persen || 0) / 100)
        );
    }
    return form.value.diskon_nominal || 0;
});

const afterDiskon = computed(() => {
    return subtotal.value - diskonNominal.value;
});

function onTaxChange() {
    const tax = taxOptions.value.find((t) => t.id === form.value.tax_id);
    if (tax) form.value.tax_persen = tax.persentase;
    else form.value.tax_persen = 0;
}

const taxNominal = computed(() => {
    return Math.floor(afterDiskon.value * ((form.value.tax_persen || 0) / 100));
});

const grandTotal = computed(() => {
    return afterDiskon.value + taxNominal.value;
});

const kembalian = computed(() => {
    if (form.value.metode_pembayaran !== "cash") return 0;
    const jb = parseFloat(form.value.jumlah_bayar) || 0;
    return jb > grandTotal.value ? jb - grandTotal.value : 0;
});

async function processTransaction() {
    if (cart.value.length === 0) return toast.error("Keranjang belanja kosong");
    if (!form.value.sales_rep_id) return toast.error("Sales wajib dipilih");
    if (!form.value.pelanggan?.trim())
        return toast.error("Nama pelanggan wajib diisi");

    isProcessing.value = true;
    try {
        const payload = {
            tanggal: form.value.tanggal_invoice,
            pelanggan: form.value.pelanggan.trim(),
            user_id: form.value.user_id || authStore.user?.id,
            sales_rep_id: form.value.sales_rep_id,
            tax_id: form.value.tax_id || null,
            tax_persen: form.value.tax_persen || 0,
            diskon_persen:
                form.value.diskon_type === "persen"
                    ? form.value.diskon_persen || 0
                    : 0,
            diskon_nominal:
                form.value.diskon_type === "nominal"
                    ? form.value.diskon_nominal || 0
                    : diskonNominal.value,
            metode_pembayaran: form.value.metode_pembayaran,
            jumlah_bayar:
                form.value.metode_pembayaran === "cash"
                    ? parseFloat(form.value.jumlah_bayar) || 0
                    : grandTotal.value,
            items: cart.value.map((c) => ({
                product_id: c.id,
                qty: c.qty,
                harga_satuan: c.harga_jual,
            })),
        };

        let res;
        if (isEditing.value) {
            res = await api.put(`/sales/${editId.value}`, payload);
            toast.success("Transaksi berhasil diperbarui!");
        } else {
            res = await api.post("/sales", payload);
            toast.success("Transaksi berhasil!");
        }

        router.push(
            `/dashboard/pos/${res.data.data.id}/invoice?from=/dashboard/pos`
        );
    } catch (error) {
        toast.error(error.response?.data?.message || "Terjadi kesalahan");
    } finally {
        isProcessing.value = false;
    }
}

function cancelTransaction() {
    Object.assign(form.value, {
        tanggal_invoice: getJakartaDateISO(),
        pelanggan: "",
        sales_rep_id: "",
        tax_id: "",
        tax_persen: 0,
        diskon_type: "nominal",
        diskon_persen: 0,
        diskon_nominal: 0,
        metode_pembayaran: "cash",
        jumlah_bayar: "",
    });
    cart.value = [];
    displayJumlahBayar.value = "";
    displayDiskonNominal.value = "";
    inputForm.value = {
        product_id: "",
        kode: "",
        nama: "",
        satuan: "",
        imei1: "",
        imei2: "",
        harga_modal: 0,
        harga_jual: 0,
        maxStok: 0,
    };
}
</script>

<template>
    <div
        class="flex flex-col w-full min-h-screen gap-6 pb-20 mx-auto bg-slate-50"
    >
        <!-- Title Header -->
        <div
            class="flex flex-col gap-3 pb-3 mx-3 mt-4 border-b-2 sm:flex-row sm:items-center sm:justify-between sm:mx-6 border-slate-200"
        >
            <h1
                class="flex items-center gap-2 text-xl font-bold text-slate-700"
            >
                Penjualan
                <span class="text-sm font-normal text-slate-400"
                    >Input Penjualan Baru</span
                >
            </h1>
            <div class="text-[11px] font-bold text-slate-400">
                <router-link to="/dashboard" class="hover:text-blue-500"
                    >Home</router-link
                >
                - Penjualan
            </div>
        </div>

        <!-- Top Information Blocks -->
        <div
            class="relative z-10 grid grid-cols-1 gap-4 px-3 sm:px-6 lg:grid-cols-4"
        >
            <!-- Blue Top Line Decoration -->
            <div
                class="absolute -top-[1.2rem] left-3 right-3 sm:left-6 sm:right-6 h-[3px] bg-cyan-400 rounded-t-sm shadow-sm"
            ></div>

            <div
                class="flex flex-col gap-1 p-3 bg-white border rounded shadow-sm border-slate-100"
            >
                <label class="text-[10px] uppercase font-bold text-slate-400"
                    >Kasir</label
                >
                <input
                    type="text"
                    :value="
                        authStore.isKasir
                            ? authStore.user?.name
                            : users.find((u) => u.id === form.user_id)?.name ||
                              authStore.user?.name
                    "
                    readonly
                    class="px-3 py-2 text-sm font-bold border rounded outline-none bg-slate-100 text-slate-500 border-slate-200"
                />
            </div>

            <!-- Removed No. Invoice as requested by User (Replaced by Sales Dropdown which was in Col 3) -->
            <div
                class="flex flex-col gap-1 p-3 bg-white border rounded shadow-sm border-slate-100"
            >
                <label class="text-[10px] uppercase font-bold text-slate-400"
                    >Sales</label
                >
                <select
                    v-model="form.sales_rep_id"
                    class="px-3 py-2 text-sm font-medium transition bg-white border rounded outline-none text-slate-700 border-slate-200 focus:ring-1 focus:ring-blue-400"
                    required=""
                >
                    <option value="">PIlih Sales</option>
                    <option
                        v-for="rep in salesReps"
                        :key="rep.id"
                        :value="rep.id"
                    >
                        {{ rep.name || rep.nama }}
                    </option>
                </select>
            </div>

            <div
                class="flex flex-col gap-1 p-3 bg-white border rounded shadow-sm border-slate-100"
            >
                <label class="text-[10px] uppercase font-bold text-slate-400"
                    >Tanggal Invoice</label
                >
                <DateInput
                    v-model="form.tanggal_invoice"
                    class="px-3 py-2 text-sm font-medium transition bg-white border rounded outline-none text-slate-700 border-slate-200 focus:ring-1 focus:ring-blue-400"
                />
            </div>

            <div
                class="flex flex-col gap-1 p-3 bg-white border rounded shadow-sm border-slate-100"
            >
                <label class="text-[10px] uppercase font-bold text-slate-400"
                    >Pelanggan</label
                >
                <input
                    type="text"
                    v-model="form.pelanggan"
                    placeholder="Masukkan Nama Pelanggan"
                    class="px-3 py-2 text-sm font-medium capitalize transition bg-white border rounded outline-none text-slate-800 border-slate-200 focus:ring-1 focus:border-blue-500 focus:ring-blue-400"
                    required=""
                />
            </div>
        </div>

        <!-- Main Body (Form + Table) -->
        <div class="flex flex-col gap-6 px-3 sm:px-6 lg:flex-row">
            <!-- Left Side: Tambah Pembelian (300px width) -->
            <div
                class="w-full lg:w-[400px] bg-white rounded shadow-sm border border-slate-100 shrink-0 self-start"
            >
                <div
                    class="p-4 border-b border-l-4 border-slate-100 border-l-blue-500"
                >
                    <h2 class="text-lg font-normal text-slate-700">
                        Tambah Pembelian
                    </h2>
                </div>
                <div class="flex flex-col gap-4 p-4">
                    <!-- Kode Produk -->
                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[11px] font-extrabold uppercase text-slate-600"
                            >Kode Produk</label
                        >
                        <div class="flex gap-2">
                            <input
                                type="text"
                                v-model="inputForm.kode"
                                @keydown.enter.prevent="searchByCode"
                                @blur="searchByCode"
                                placeholder="Kode..."
                                class="w-2/3 px-3 py-2 text-sm uppercase transition bg-white border rounded-sm outline-none border-slate-200 focus:border-blue-500"
                            />
                            <button
                                @click="openSearchModal"
                                class="w-1/3 bg-blue-700 hover:bg-blue-800 text-white px-2 py-2 rounded-sm text-[11px] font-bold flex items-center justify-center gap-1 transition shadow-sm"
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
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    ></path>
                                </svg>
                                Cari Produk
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[11px] font-extrabold uppercase text-slate-600"
                            >Produk</label
                        >
                        <input
                            type="text"
                            :value="inputForm.nama"
                            readonly
                            class="px-3 py-2 text-sm font-medium border rounded-sm outline-none cursor-not-allowed bg-slate-100 border-slate-200 text-slate-700"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[11px] font-extrabold uppercase text-slate-600"
                            >Satuan</label
                        >
                        <input
                            type="text"
                            :value="inputForm.satuan"
                            readonly
                            class="px-3 py-2 text-sm font-medium border rounded-sm outline-none cursor-not-allowed bg-slate-100 border-slate-200 text-slate-700"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[11px] font-extrabold uppercase text-slate-600"
                            >IMEI 1</label
                        >
                        <input
                            type="text"
                            :value="inputForm.imei1"
                            readonly
                            class="px-3 py-2 text-sm font-medium border rounded-sm outline-none cursor-not-allowed bg-slate-100 border-slate-200 text-slate-700"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[11px] font-extrabold uppercase text-slate-600"
                            >IMEI 2</label
                        >
                        <input
                            type="text"
                            :value="inputForm.imei2"
                            readonly
                            class="px-3 py-2 text-sm font-medium border rounded-sm outline-none cursor-not-allowed bg-slate-100 border-slate-200 text-slate-700"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[11px] font-extrabold uppercase text-slate-600"
                            >Modal</label
                        >
                        <input
                            type="text"
                            :value="formatCurrency(inputForm.harga_modal)"
                            readonly
                            class="px-3 py-2 text-sm font-bold border rounded-sm outline-none cursor-not-allowed bg-slate-100 border-slate-200 text-slate-700"
                        />
                    </div>

                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[11px] font-extrabold uppercase text-slate-600"
                            >Harga Jual</label
                        >
                        <CurrencyInput
                            v-model="inputForm.harga_jual"
                            :allowThousands="true"
                        />
                    </div>

                    <button
                        @click="addToCart"
                        class="w-full bg-[#3b82f6] hover:bg-blue-600 text-white font-bold py-2.5 rounded-sm uppercase text-xs tracking-wider shadow-sm transition mt-1"
                    >
                        Tambahkan
                    </button>
                </div>
            </div>

            <!-- Right Side: Daftar Pembelian (Table) -->
            <div class="flex flex-col flex-1 gap-4">
                <!-- Table Card -->
                <div
                    class="overflow-hidden bg-white border rounded shadow-sm border-slate-100"
                >
                    <div
                        class="p-4 border-b border-l-4 border-slate-100 border-l-blue-500"
                    >
                        <h2 class="text-lg font-normal text-slate-700">
                            Daftar Pembelian
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[12px]">
                            <thead
                                class="font-bold uppercase bg-white border-b border-slate-200 text-slate-700"
                            >
                                <tr>
                                    <th class="px-4 py-3">Kode Produk</th>
                                    <th class="px-4 py-3">Nama Produk</th>
                                    <th class="px-4 py-3">Satuan</th>
                                    <th class="px-4 py-3 text-right">Harga</th>
                                    <th class="px-4 py-3 text-center">
                                        Jumlah
                                    </th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-center">Opsi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="cart.length === 0">
                                    <td
                                        colspan="7"
                                        class="py-8 italic text-center text-slate-400"
                                    >
                                        Belum ada barang di keranjang.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(item, idx) in cart"
                                    :key="item.id"
                                    class="transition hover:bg-slate-50"
                                >
                                    <td
                                        class="px-4 py-3 font-mono text-slate-500"
                                    >
                                        {{ item.kode }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        <div class="font-bold">
                                            {{ item.nama }}
                                        </div>
                                        <div
                                            class="text-[10px] text-slate-500 mt-1 flex flex-col"
                                        >
                                            <span
                                                >IMEI 1:
                                                {{ item.imei1 || "-" }}</span
                                            >
                                            <span
                                                >IMEI 2:
                                                {{ item.imei2 || "-" }}</span
                                            >
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ item.satuan }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-bold text-right text-slate-700"
                                    >
                                        {{ formatCurrency(item.harga_jual) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <button
                                                @click="updateQty(item, -1)"
                                                class="flex items-center justify-center w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-600"
                                            >
                                                -
                                            </button>
                                            <span
                                                class="w-6 font-bold text-center text-slate-800"
                                                >{{ item.qty }}</span
                                            >
                                            <button
                                                @click="updateQty(item, 1)"
                                                class="flex items-center justify-center w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-600"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 font-bold text-right text-blue-700"
                                    >
                                        {{
                                            formatCurrency(
                                                item.harga_jual * item.qty
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            @click="removeFromCart(idx)"
                                            class="bg-[#e74c3c] hover:bg-red-600 text-white px-2 py-1 rounded text-[10px] font-bold shadow-sm flex items-center justify-center mx-auto gap-1 transition"
                                        >
                                            <svg
                                                class="w-3 h-3"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"
                                                ></path>
                                            </svg>
                                            Batal
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot
                                class="bg-[#d2eaf4]/60 border-t border-slate-200 text-slate-800"
                            >
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-4 py-3 font-black text-right"
                                    >
                                        Total
                                    </td>
                                    <td
                                        class="px-4 py-3 font-bold text-center text-blue-700"
                                    >
                                        {{
                                            cart.reduce(
                                                (acc, c) => acc + c.qty,
                                                0
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-bold text-right text-blue-700"
                                    >
                                        {{ formatCurrency(subtotal) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Footer Summary (Subtotal, Diskon, dsb) -->
                <div class="grid grid-cols-1 gap-6 mt-2 md:grid-cols-2">
                    <!-- Left Summary Card -->
                    <div
                        class="bg-white rounded shadow-sm border border-slate-100 p-0 text-[12px] h-fit"
                    >
                        <div
                            class="flex items-center px-4 py-3 border-b border-slate-100"
                        >
                            <span class="w-1/3 font-bold text-slate-700"
                                >Sub Total Pembelian</span
                            >
                            <span
                                class="flex-1 font-bold text-right text-slate-600"
                                >{{ formatCurrency(subtotal) }}</span
                            >
                        </div>
                        <div
                            class="flex items-center px-4 py-3 border-b border-slate-100"
                        >
                            <span class="w-1/3 font-bold text-slate-700"
                                >Diskon</span
                            >
                            <div class="flex items-center flex-1">
                                <template v-if="form.diskon_type === 'persen'">
                                    <input
                                        type="number"
                                        v-model.number="form.diskon_persen"
                                        class="flex-1 min-w-0 border border-slate-300 rounded-l px-3 py-1.5 focus:border-blue-500 outline-none text-right font-bold text-sky-600"
                                    />
                                    <span
                                        class="bg-slate-50 border border-l-0 border-slate-300 rounded-r px-3 py-1.5 text-slate-500 cursor-pointer hover:bg-slate-100 transition"
                                        @click="form.diskon_type = 'nominal'"
                                        >%</span
                                    >
                                </template>
                                <template v-else>
                                    <span
                                        class="bg-slate-50 border border-r-0 border-slate-300 rounded-l px-3 py-1.5 text-slate-500 cursor-pointer hover:bg-slate-100 transition"
                                        @click="form.diskon_type = 'persen'"
                                        >Rp.</span
                                    >
                                    <input
                                        type="text"
                                        v-model="displayDiskonNominal"
                                        class="flex-1 min-w-0 border border-slate-300 rounded-r px-3 py-1.5 focus:border-blue-500 outline-none text-right font-bold text-rose-600"
                                    />
                                </template>
                            </div>
                        </div>
                        <div
                            class="flex items-center px-4 py-3 border-b border-slate-100 bg-slate-50/50"
                        >
                            <span class="w-1/3 font-bold text-slate-700"
                                >Total Pembelian</span
                            >
                            <span
                                class="flex-1 font-bold text-right text-slate-700"
                                >{{ formatCurrency(grandTotal) }}</span
                            >
                        </div>
                    </div>

                    <!-- Right Finalize Payment Card -->
                    <div
                        class="bg-white rounded shadow-sm border border-slate-100 p-4 text-[12px] h-fit flex flex-col gap-3"
                    >
                        <div class="flex items-center">
                            <span class="w-1/3 font-bold text-slate-700"
                                >Pajak</span
                            >
                            <div class="flex-1">
                                <select
                                    v-model="form.tax_id"
                                    @change="onTaxChange"
                                    class="w-full bg-white border border-slate-300 px-3 py-1.5 rounded focus:border-blue-500 outline-none"
                                >
                                    <option value="">Tanpa Pajak</option>
                                    <option
                                        v-for="tax in taxOptions"
                                        :key="tax.id"
                                        :value="tax.id"
                                    >
                                        {{ tax.nama }} ({{ tax.persentase }}%)
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <span class="w-1/3 font-bold text-slate-700"
                                >Metode Bayar</span
                            >
                            <div class="flex-1">
                                <select
                                    v-model="form.metode_pembayaran"
                                    class="w-full bg-white border border-slate-300 px-3 py-1.5 rounded focus:border-blue-500 outline-none"
                                >
                                    <option value="cash">Tunai / Cash</option>
                                    <option value="transfer">
                                        Transfer Bank
                                    </option>
                                    <option value="qris">QRIS</option>
                                </select>
                            </div>
                        </div>
                        <div
                            class="flex items-center"
                            v-if="form.metode_pembayaran === 'cash'"
                        >
                            <span class="w-1/3 font-bold text-slate-700"
                                >Jumlah Bayar</span
                            >
                            <div
                                class="flex flex-1 overflow-hidden text-xl rounded shadow-inner"
                            >
                                <span
                                    class="flex items-center px-3 py-2 text-sm font-black text-blue-600 border border-r-0 border-blue-200 bg-blue-50"
                                    >Rp.</span
                                >
                                <input
                                    type="text"
                                    v-model="displayJumlahBayar"
                                    class="w-full !px-3 !py-2 border border-blue-200 focus:border-blue-500 font-black text-blue-800 outline-none text-right"
                                    placeholder="0"
                                />
                            </div>
                        </div>
                        <div
                            class="flex items-center"
                            v-if="form.metode_pembayaran === 'cash'"
                        >
                            <span class="w-1/3 font-bold text-slate-700"
                                >Kembalian</span
                            >
                            <span
                                class="flex-1 text-lg font-black text-right"
                                :class="
                                    kembalian < 0
                                        ? 'text-rose-600'
                                        : 'text-emerald-600'
                                "
                                >{{ formatCurrency(kembalian) }}</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="flex items-center justify-between px-2 mt-6">
                    <button
                        @click="cancelTransaction"
                        class="bg-[#e74c3c] hover:bg-red-600 text-white px-5 py-2.5 rounded-sm text-sm font-bold shadow-sm flex items-center gap-2 transition"
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
                                d="M6 18L18 6M6 6l12 12"
                            ></path>
                        </svg>
                        Batalkan Transaksi
                    </button>
                    <button
                        @click="processTransaction"
                        :disabled="cart.length === 0 || isProcessing"
                        class="bg-[#2ecc71] hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-sm text-sm font-bold shadow-sm flex items-center gap-2 transition"
                    >
                        <template v-if="!isProcessing">
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
                                    d="M5 13l4 4L19 7"
                                ></path>
                            </svg>
                            Buat Transaksi
                        </template>
                        <template v-else>Memproses...</template>
                    </button>
                </div>
            </div>
        </div>

        <!-- SEARCH MODAL -->
        <div
            v-if="showSearchModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4 transition-all"
            @click.self="showSearchModal = false"
        >
            <div
                class="bg-white rounded-lg shadow-xl w-full max-w-6xl overflow-hidden flex flex-col h-[90vh] lg:h-[80vh]"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between px-5 py-4 bg-white border-b text-slate-700"
                >
                    <h3 class="text-base font-bold">Pilih Pembelian produk</h3>
                    <button
                        @click="showSearchModal = false"
                        class="transition text-slate-400 hover:text-rose-500"
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
                            ></path>
                        </svg>
                    </button>
                </div>
                <!-- Search Bar -->
                <div
                    class="flex items-center justify-end gap-2 p-4 border-b bg-slate-50"
                >
                    <span class="text-sm font-medium text-slate-600"
                        >Search:</span
                    >
                    <input
                        type="text"
                        v-model="modalSearchQuery"
                        class="border border-slate-300 rounded px-3 py-1.5 focus:outline-none focus:border-blue-500 w-64 text-sm bg-white"
                    />
                </div>
                <!-- Table -->
                <div class="flex-1 p-4 overflow-auto bg-white">
                    <table class="w-full text-left text-[11px] min-w-[800px]">
                        <thead
                            class="font-bold uppercase bg-white border-b-2 text-slate-600 border-slate-200"
                        >
                            <tr>
                                <th class="px-2 pb-3">NO</th>
                                <th class="px-2 pb-3">KODE</th>
                                <th class="pb-3 px-2 w-[180px]">PRODUK</th>
                                <th class="px-2 pb-3">SATUAN</th>
                                <th class="px-2 pb-3">IMEI</th>
                                <th class="px-2 pb-3">HARGA BELI</th>
                                <th class="px-2 pb-3">HARGA JUAL</th>
                                <th class="px-2 pb-3">SUPLIER</th>
                                <th class="px-2 pb-3">KETERANGAN</th>
                                <th class="px-2 pb-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="isSearchingModal">
                                <td
                                    colspan="10"
                                    class="py-12 text-center text-slate-400"
                                >
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="modalItems.length === 0">
                                <td
                                    colspan="10"
                                    class="py-12 italic text-center text-slate-400"
                                >
                                    Data tidak ditemukan.
                                </td>
                            </tr>
                            <tr
                                v-for="(item, idx) in modalItems"
                                :key="item.id"
                                class="hover:bg-slate-50"
                            >
                                <td class="px-2 py-3 text-slate-500">
                                    {{
                                        (modalPagination.current_page - 1) *
                                            10 +
                                        idx +
                                        1
                                    }}
                                </td>
                                <td class="px-2 py-3 font-mono text-slate-600">
                                    {{ item.product?.barcode }}
                                </td>
                                <td
                                    class="px-2 py-3 font-bold break-words text-slate-800"
                                >
                                    {{ item.product?.nama }}
                                </td>
                                <td class="px-2 py-3 text-slate-600">
                                    {{
                                        item.product?.unit ||
                                        item.product?.masterProduct?.unit
                                            ?.nama ||
                                        "-"
                                    }}
                                </td>
                                <td class="px-2 py-3 font-mono text-slate-600">
                                    {{
                                        [
                                            item.product?.imei1,
                                            item.product?.imei2,
                                        ]
                                            .filter(Boolean)
                                            .join("\n") || "-"
                                    }}
                                </td>
                                <td class="px-2 py-3 font-bold text-slate-700">
                                    {{ formatCurrency(item.harga_beli) }}
                                </td>
                                <td class="px-2 py-3 font-bold text-slate-700">
                                    {{
                                        formatCurrency(item.product?.harga_jual)
                                    }}
                                </td>
                                <td class="px-2 py-3 text-slate-600">
                                    {{ item.purchase?.supplier?.nama || "-" }}
                                </td>
                                <td class="px-2 py-3 uppercase text-slate-500">
                                    {{ item.product?.keterangan || "-" }}
                                </td>
                                <td class="px-2 py-3 text-right">
                                    <button
                                        @click="selectFromModal(item)"
                                        class="bg-[#00a65a] hover:bg-green-700 text-white shadow font-bold py-1.5 px-3 rounded text-[11px] transition"
                                    >
                                        Pilih
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div
                    class="flex items-center justify-between px-5 py-3 text-sm bg-white border-t rounded-b-lg text-slate-600"
                >
                    <span
                        >Showing
                        {{
                            modalItems.length > 0
                                ? (modalPagination.current_page - 1) * 10 + 1
                                : 0
                        }}
                        to
                        {{
                            (modalPagination.current_page - 1) * 10 +
                            modalItems.length
                        }}
                        of {{ modalPagination.total }} entries</span
                    >
                    <div
                        class="flex overflow-hidden border border-slate-300 rounded text-[12px]"
                    >
                        <button
                            :disabled="modalPagination.current_page === 1"
                            @click="
                                fetchModalItems(
                                    modalPagination.current_page - 1
                                )
                            "
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:bg-slate-100 transition border-r font-medium border-slate-300"
                        >
                            Previous
                        </button>
                        <span
                            class="px-3 py-1.5 bg-blue-600 text-white font-bold"
                            >{{ modalPagination.current_page }}</span
                        >
                        <button
                            :disabled="
                                modalPagination.current_page ===
                                modalPagination.last_page
                            "
                            @click="
                                fetchModalItems(
                                    modalPagination.current_page + 1
                                )
                            "
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:bg-slate-100 transition border-l font-medium border-slate-300"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scoped minimal styles to align tightly with screenshot */
input[readonly] {
    cursor: not-allowed;
    opacity: 0.9;
}
</style>
