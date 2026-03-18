<script setup>
import { ref, onMounted, computed, watch } from "vue";
import DateInput from "../../components/DateInput.vue";
import api from "../../api";
import { useRouter, useRoute } from "vue-router";
import QuickAddModal from "../../components/QuickAddModal.vue";
import ConfirmDialog from "../../components/ConfirmDialog.vue";
import CurrencyInput from "../../components/CurrencyInput.vue";
import SearchableSelect from "../../components/SearchableSelect.vue";
import { useToast } from "../../composables/useToast";

const router = useRouter();
const route = useRoute();
const toast = useToast();

const isEditMode = computed(() => !!route.params.id);

// Purchase header
const today = new Date();
const localToday = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;

const form = ref({
    no_invoice: "",
    tanggal: localToday,
    supplier_id: "",
    keterangan: "",
});
const purchaseId = ref(null);
const headerSaved = ref(false);
const savingHeader = ref(false);
const headerError = ref("");
const editingHeader = ref(false);

// Item form
const itemForm = ref({
    katalog_barang_id: null,
    qty: "",
    harga_beli: 0,
    expired_at: "",
});
const addingItem = ref(false);
const itemError = ref("");

// Data lists
const suppliers = ref([]);
const katalogBarang = ref([]);
const items = ref([]);
const loadingItems = ref(false);

// Edit/delete item
const editingItemId = ref(null);
const editItemForm = ref({ qty: "", harga_beli: 0 });
const savingItemEdit = ref(false);

const showDeleteItem = ref(false);
const deleteItemId = ref(null);
const deletingItem = ref(false);

// Quick add
const showQuickAdd = ref(false);
const showQuickKatalog = ref(false);
const categories = ref([]);
const units = ref([]);

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

const selectedKatalog = computed(() =>
    katalogBarang.value.find(k => k.id === itemForm.value.katalog_barang_id)
);

const isDryGood = computed(() => {
    const kode = selectedKatalog.value?.kode_barang || "";
    return kode.toUpperCase().startsWith("D");
});

const itemGridClass = computed(() =>
    isDryGood.value
        ? "grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end"
        : "grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end"
);

watch(selectedKatalog, (val) => {
    if (!val) {
        itemForm.value.expired_at = "";
        return;
    }
    const kode = val.kode_barang || "";
    if (!kode.toUpperCase().startsWith("D")) {
        itemForm.value.expired_at = "";
    }
});

const totalBeli = computed(() =>
    items.value.reduce((sum, item) => sum + Number(item.subtotal || 0), 0)
);

// Katalog options for SearchableSelect (format: Kode - Nama (Satuan))
const katalogOptions = computed(() =>
    katalogBarang.value.map(k => ({
        id: k.id,
        nama: `${k.kode_barang} - ${k.nama_barang} (${k.unit?.nama || ""})`,
    }))
);

onMounted(async () => {
    await Promise.all([fetchSuppliers(), fetchKatalog(), fetchCategoriesUnits()]);
    if (isEditMode.value) {
        await loadExistingPurchase();
    }
});

async function fetchSuppliers() {
    try {
        const { data } = await api.get("/suppliers/all");
        suppliers.value = data.data;
    } catch (e) {
        console.error("Gagal memuat supplier", e);
    }
}

async function fetchKatalog() {
    try {
        const { data } = await api.get("/katalog-barang/all");
        katalogBarang.value = data.data;
    } catch (e) {
        console.error("Gagal memuat katalog barang", e);
    }
}

async function fetchCategoriesUnits() {
    try {
        const [catRes, unitRes] = await Promise.all([
            api.get("/categories/all"),
            api.get("/units/all"),
        ]);
        categories.value = catRes.data.data;
        units.value = unitRes.data.data;
    } catch (e) {
        console.error("Gagal memuat kategori/satuan", e);
    }
}

async function loadExistingPurchase() {
    try {
        const { data } = await api.get(`/purchases/${route.params.id}`);
        const p = data.data;
        purchaseId.value = p.id;
        form.value = {
            no_invoice: p.no_invoice,
            tanggal: p.tanggal,
            supplier_id: p.supplier_id || (p.supplier?.id ?? ""),
            keterangan: p.keterangan || "",
        };
        headerSaved.value = true;
        items.value = p.items || [];
    } catch (e) {
        toast.error("Gagal memuat data pembelian");
        router.push("/dashboard/purchases");
    }
}

async function fetchItems() {
    if (!purchaseId.value) return;
    loadingItems.value = true;
    try {
        const { data } = await api.get(`/purchases/${purchaseId.value}`);
        items.value = data.data.items || [];
    } catch (e) {
        console.error("Gagal memuat item", e);
    } finally {
        loadingItems.value = false;
    }
}

async function saveHeader() {
    savingHeader.value = true;
    headerError.value = "";
    try {
        if (purchaseId.value) {
            await api.put(`/purchases/${purchaseId.value}`, form.value);
            toast.success("Header invoice diperbarui");
            editingHeader.value = false;
        } else {
            const { data } = await api.post("/purchases", form.value);
            purchaseId.value = data.data.id;
            headerSaved.value = true;
            toast.success("Invoice dibuat, silakan tambah item");
        }
    } catch (err) {
        headerError.value = err.response?.data?.message || "Gagal menyimpan header";
    } finally {
        savingHeader.value = false;
    }
}

async function addItem() {
    if (!itemForm.value.katalog_barang_id) {
        itemError.value = "Pilih barang terlebih dahulu";
        return;
    }
    if (!itemForm.value.qty || Number(itemForm.value.qty) <= 0) {
        itemError.value = "Qty harus lebih dari 0";
        return;
    }
    if (isDryGood.value && !itemForm.value.expired_at) {
        itemError.value = "Tanggal expired wajib diisi untuk barang Dry Good (kode D)";
        return;
    }
    addingItem.value = true;
    itemError.value = "";
    try {
        await api.post(`/purchases/${purchaseId.value}/items`, {
            katalog_barang_id: itemForm.value.katalog_barang_id,
            qty: itemForm.value.qty,
            harga_beli: itemForm.value.harga_beli,
            expired_at: isDryGood.value ? itemForm.value.expired_at : null,
        });
        itemForm.value = { katalog_barang_id: null, qty: "", harga_beli: 0, expired_at: "" };
        await fetchItems();
        toast.success("Item berhasil ditambahkan");
    } catch (err) {
        itemError.value = err.response?.data?.message || "Gagal menambah item";
    } finally {
        addingItem.value = false;
    }
}

function startEditItem(item) {
    editingItemId.value = item.id;
    editItemForm.value = {
        qty: item.qty,
        harga_beli: item.harga_beli,
    };
}

function cancelEditItem() {
    editingItemId.value = null;
}

async function saveEditItem(itemId) {
    savingItemEdit.value = true;
    try {
        await api.put(`/purchases/${purchaseId.value}/items/${itemId}`, editItemForm.value);
        editingItemId.value = null;
        await fetchItems();
        toast.success("Item diperbarui");
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal memperbarui item");
    } finally {
        savingItemEdit.value = false;
    }
}

function confirmDeleteItem(itemId) {
    deleteItemId.value = itemId;
    showDeleteItem.value = true;
}

async function doDeleteItem() {
    deletingItem.value = true;
    try {
        await api.delete(`/purchases/${purchaseId.value}/items/${deleteItemId.value}`);
        showDeleteItem.value = false;
        await fetchItems();
        toast.success("Item dihapus");
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal menghapus item");
    } finally {
        deletingItem.value = false;
    }
}

async function quickAddKatalog(data) {
    const res = await api.post("/katalog-barang", data);
    const newItem = res.data.data;
    katalogBarang.value.push(newItem);
    itemForm.value.katalog_barang_id = newItem.id;
    return newItem;
}

async function quickAddSupplier(data) {
    const res = await api.post("/suppliers/quick", data);
    const newSupplier = res.data.data;
    suppliers.value.push(newSupplier);
    form.value.supplier_id = newSupplier.id;
    return newSupplier;
}

function done() {
    router.push("/dashboard/purchases");
}
</script>

<template>
    <div class="px-4 py-6 mx-auto space-y-6 md:px-8">
        <!-- Page Header -->
        <div class="flex items-center gap-3">
            <button
                @click="router.push('/dashboard/purchases')"
                class="p-2.5 hover:bg-slate-100 rounded-xl transition"
            >
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    {{ isEditMode ? "Edit Pembelian" : "Tambah Pembelian" }}
                </h1>
                <p class="text-sm text-slate-400 mt-0.5">
                    {{ isEditMode ? "Kelola item pada invoice ini" : "Buat invoice pembelian baru" }}
                </p>
            </div>
        </div>

        <!-- Header Form -->
        <div class="p-5 bg-white border shadow-sm rounded-xl border-slate-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700">Informasi Invoice</h3>
                <button
                    v-if="headerSaved && !editingHeader"
                    @click="editingHeader = true"
                    class="text-xs text-blue-600 hover:underline"
                >Edit</button>
            </div>

            <!-- View mode (header saved & not editing) -->
            <div v-if="headerSaved && !editingHeader" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <p class="text-xs text-slate-400 mb-0.5">No. Invoice</p>
                    <p class="font-mono font-semibold text-slate-800">{{ form.no_invoice }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-0.5">Tanggal</p>
                    <p class="font-semibold text-slate-800">
                        {{ form.tanggal ? form.tanggal.split("-").reverse().join("-") : "-" }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-0.5">Supplier</p>
                    <p class="font-semibold text-slate-800">
                        {{ suppliers.find(s => s.id === form.supplier_id)?.nama || "-" }}
                    </p>
                </div>
                <div v-if="form.keterangan">
                    <p class="text-xs text-slate-400 mb-0.5">Keterangan</p>
                    <p class="text-sm text-slate-600">{{ form.keterangan }}</p>
                </div>
            </div>

            <!-- Edit form -->
            <form v-else @submit.prevent="saveHeader">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block mb-1 text-xs font-medium text-slate-600">No. Invoice *</label>
                        <input
                            v-model="form.no_invoice"
                            type="text"
                            required
                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-400 bg-slate-50/50 outline-none"
                            placeholder="INV-001"
                        />
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-slate-600">Tanggal *</label>
                        <DateInput
                            v-model="form.tanggal"
                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-400 bg-slate-50/50 outline-none"
                        />
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-slate-600">Supplier *</label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <select
                                    v-model="form.supplier_id"
                                    required
                                    class="appearance-none w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-400 bg-slate-50/50 outline-none pr-8"
                                >
                                    <option value="" disabled>Pilih Supplier</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.nama }}</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="showQuickAdd = true"
                                class="px-2.5 py-2 text-sm text-blue-600 border border-blue-200 rounded-xl hover:bg-blue-50 transition"
                                title="Tambah supplier baru"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-slate-600">Keterangan</label>
                        <input
                            v-model="form.keterangan"
                            type="text"
                            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-400 bg-slate-50/50 outline-none"
                            placeholder="Opsional"
                        />
                    </div>
                </div>
                <p v-if="headerError" class="mt-2 text-sm text-red-500">{{ headerError }}</p>
                <div class="flex gap-2 mt-4">
                    <button
                        v-if="editingHeader"
                        type="button"
                        @click="editingHeader = false"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition"
                    >Batal</button>
                    <button
                        type="submit"
                        :disabled="savingHeader"
                        class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                    >
                        {{ savingHeader ? "Menyimpan..." : (purchaseId ? "Perbarui Header" : "Simpan & Lanjutkan") }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Item Entry Section (shown after header saved) -->
        <template v-if="headerSaved">
            <!-- Add Item Form -->
            <div class="p-5 bg-white border shadow-sm rounded-xl border-slate-200">
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Tambah Item</h3>
                <div :class="itemGridClass">
                    <div class="sm:col-span-2 lg:col-span-2">
                        <label class="block mb-1 text-xs font-medium text-slate-600">Barang *</label>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <SearchableSelect
                                    v-model="itemForm.katalog_barang_id"
                                    :options="katalogOptions"
                                    placeholder="Pilih barang..."
                                />
                            </div>
                            <button
                                type="button"
                                @click="showQuickKatalog = true"
                                class="px-2.5 py-2 text-sm text-blue-600 border border-blue-200 rounded-xl hover:bg-blue-50 transition shrink-0"
                                title="Tambah barang baru"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-slate-600">
                            Qty *
                            <span v-if="selectedKatalog" class="text-slate-400 font-normal ml-1">({{ selectedKatalog.unit?.nama || "" }})</span>
                        </label>
                        <input
                            v-model="itemForm.qty"
                            type="number"
                            step="0.001"
                            min="0.001"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-400 bg-slate-50/50 outline-none"
                            placeholder="0.000"
                        />
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-slate-600">Harga Beli *</label>
                        <CurrencyInput v-model="itemForm.harga_beli" />
                    </div>
                    <div v-if="isDryGood">
                        <label class="block mb-1 text-xs font-medium text-slate-600">Tanggal Expired *</label>
                        <input
                            v-model="itemForm.expired_at"
                            type="date"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-400 bg-slate-50/50 outline-none"
                        />
                        <p class="mt-1 text-[11px] text-slate-400">Wajib untuk barang Dry Good (kode D)</p>
                    </div>
                </div>
                <p v-if="itemError" class="mt-2 text-sm text-red-500">{{ itemError }}</p>
                <div class="mt-3">
                    <button
                        @click="addItem"
                        :disabled="addingItem"
                        class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ addingItem ? "Menambah..." : "Tambah Item" }}
                    </button>
                </div>
            </div>

            <!-- Items Table -->
            <div class="overflow-hidden bg-white border shadow-sm rounded-xl border-slate-200">
                <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">
                        Daftar Item
                        <span class="ml-1.5 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">
                            {{ items.length }}
                        </span>
                    </h3>
                </div>

                <div v-if="loadingItems" class="flex justify-center py-8">
                    <div class="w-6 h-6 border-2 border-slate-200 border-t-blue-500 rounded-full animate-spin"></div>
                </div>

                <div v-else class="table-container">
                    <table class="table-fixed-layout table-wide">
                        <thead class="table-header">
                            <tr>
                                <th class="w-12 text-center">No</th>
                                <th class="text-left">Nama Barang</th>
                                <th class="text-center">Satuan</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Harga Beli</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">Sisa Stok</th>
                                <th class="w-24 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <tr v-if="items.length === 0">
                                <td colspan="8" class="px-6 py-10 text-center text-slate-500 italic">
                                    Belum ada item. Tambahkan barang di atas.
                                </td>
                            </tr>
                            <tr
                                v-for="(item, idx) in items"
                                :key="item.id"
                                class="table-row hover:bg-slate-50 transition"
                            >
                                <td class="table-cell text-center text-slate-500">{{ idx + 1 }}</td>
                                <td class="table-cell">
                                    <p class="font-medium text-slate-700">{{ item.katalog_barang?.nama_barang || item.nama_barang || "-" }}</p>
                                    <p class="text-xs text-slate-400 font-mono">{{ item.katalog_barang?.kode_barang || "" }}</p>
                                </td>
                                <td class="table-cell text-center text-slate-500">
                                    {{ item.katalog_barang?.unit?.nama || item.satuan || "-" }}
                                </td>
                                <td class="table-cell text-right font-semibold text-slate-700">
                                    <input
                                        v-if="editingItemId === item.id"
                                        v-model="editItemForm.qty"
                                        type="number"
                                        step="0.001"
                                        min="0.001"
                                        class="w-24 px-2 py-1 text-sm text-right border border-blue-400 rounded outline-none focus:ring-2 focus:ring-blue-200"
                                    />
                                    <span v-else>{{ formatNumber(item.qty, 3) }}</span>
                                </td>
                                <td class="table-cell text-right text-slate-600">
                                    <CurrencyInput
                                        v-if="editingItemId === item.id"
                                        v-model="editItemForm.harga_beli"
                                        class="w-36"
                                    />
                                    <span v-else>{{ formatCurrency(item.harga_beli) }}</span>
                                </td>
                                <td class="table-cell text-right font-bold text-emerald-600">
                                    {{ formatCurrency(item.subtotal) }}
                                </td>
                                <td class="table-cell text-right" :class="Number(item.sisa_stok) <= 0 ? 'text-red-500' : 'text-slate-600'">
                                    {{ formatNumber(item.sisa_stok, 3) }}
                                </td>
                                <td class="table-cell text-center">
                                    <div v-if="editingItemId === item.id" class="flex justify-center gap-1">
                                        <button
                                            @click="saveEditItem(item.id)"
                                            :disabled="savingItemEdit"
                                            class="px-2 py-1 text-[11px] font-semibold text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50 transition"
                                        >{{ savingItemEdit ? "..." : "Simpan" }}</button>
                                        <button
                                            @click="cancelEditItem"
                                            class="px-2 py-1 text-[11px] font-semibold text-slate-600 bg-slate-100 rounded hover:bg-slate-200 transition"
                                        >Batal</button>
                                    </div>
                                    <div v-else class="flex justify-center gap-1">
                                        <button
                                            @click="startEditItem(item)"
                                            class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmDeleteItem(item.id)"
                                            class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                                            title="Hapus"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Total Footer -->
                <div class="flex items-center justify-end gap-6 px-5 py-3 border-t border-slate-100 bg-slate-50">
                    <span class="text-xs font-semibold tracking-wider uppercase text-slate-400">Total Pembelian</span>
                    <span class="text-base font-bold text-emerald-600">{{ formatCurrency(totalBeli) }}</span>
                </div>
            </div>

            <!-- Done Button -->
            <div class="flex justify-end">
                <button
                    @click="done"
                    class="px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl hover:shadow-lg hover:shadow-blue-500/25 transition-all"
                >
                    Selesai
                </button>
            </div>
        </template>

        <!-- Quick Add Katalog Barang Modal -->
        <QuickAddModal
            :show="showQuickKatalog"
            title="Tambah Barang Baru"
            :fields="[
                { key: 'nama_barang', label: 'Nama Barang', required: true },
                { key: 'category_id', label: 'Kategori', type: 'select', required: true, options: categories.map(c => ({ value: c.id, label: c.nama })) },
                { key: 'unit_id', label: 'Satuan', type: 'select', required: true, options: units.map(u => ({ value: u.id, label: u.nama })) },
            ]"
            :submit-function="quickAddKatalog"
            @close="showQuickKatalog = false"
            @created="showQuickKatalog = false"
        />

        <!-- Quick Add Supplier Modal -->
        <QuickAddModal
            :show="showQuickAdd"
            title="Tambah Supplier Baru"
            :fields="[{ key: 'nama', label: 'Nama Supplier', required: true }]"
            :submit-function="quickAddSupplier"
            @close="showQuickAdd = false"
            @created="showQuickAdd = false"
        />

        <ConfirmDialog
            :show="showDeleteItem"
            :loading="deletingItem"
            title="Hapus Item"
            message="Yakin ingin menghapus item ini? Stok akan disesuaikan."
            @confirm="doDeleteItem"
            @cancel="showDeleteItem = false"
        />
    </div>
</template>
