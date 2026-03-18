<script setup>
import { computed, ref, onMounted } from "vue";
import api from "../../api";
import DataTable from "../../components/DataTable.vue";
import ConfirmDialog from "../../components/ConfirmDialog.vue";
import QuickAddModal from "../../components/QuickAddModal.vue";
import { useAuthStore } from "../../stores/auth";

const dataTable = ref(null);
const authStore = useAuthStore();
const canDelete = computed(() => !authStore.isKasir);

const showForm = ref(false);
const showDelete = ref(false);
const editId = ref(null);
const deleteId = ref(null);
const deleting = ref(false);
const saving = ref(false);
const error = ref("");

const categories = ref([]);
const units = ref([]);
const filterCategoryId = ref("");

const form = ref({
    nama_barang: "",
    category_id: "",
    unit_id: "",
});

const editKodeBarang = ref("");

// Quick-add state
const showQuickCategory = ref(false);
const showQuickUnit = ref(false);

async function quickAddCategory(data) {
    const res = await api.post("/categories/quick", data);
    const newCat = res.data.data;
    categories.value.push(newCat);
    form.value.category_id = newCat.id;
    return newCat;
}

async function quickAddUnit(data) {
    const res = await api.post("/units/quick", data);
    const newUnit = res.data.data;
    units.value.push(newUnit);
    form.value.unit_id = newUnit.id;
    return newUnit;
}

const columns = [
    { key: "kode_barang", label: "Kode Barang" },
    { key: "nama_barang", label: "Nama Barang" },
    { key: "category.nama", label: "Kategori" },
    { key: "unit.nama", label: "Satuan" },
];

const externalFilters = computed(() => ({
    category_id: filterCategoryId.value,
}));

onMounted(async () => {
    try {
        const [catRes, unitRes] = await Promise.all([
            api.get("/categories/all"),
            api.get("/units/all"),
        ]);
        categories.value = catRes.data.data;
        units.value = unitRes.data.data;
    } catch (e) {
        console.error("Gagal memuat referensi", e);
    }
});

async function fetchData(params) {
    const { data } = await api.get("/katalog-barang", { params });
    return data.data;
}

function openCreate() {
    editId.value = null;
    editKodeBarang.value = "";
    form.value = { nama_barang: "", category_id: "", unit_id: "" };
    error.value = "";
    showForm.value = true;
}

function openEdit(row) {
    editId.value = row.id;
    editKodeBarang.value = row.kode_barang || "";
    form.value = {
        nama_barang: row.nama_barang,
        category_id: row.category_id,
        unit_id: row.unit_id,
    };
    error.value = "";
    showForm.value = true;
}

async function saveForm() {
    saving.value = true;
    error.value = "";
    try {
        if (editId.value) {
            await api.put(`/katalog-barang/${editId.value}`, form.value);
        } else {
            await api.post("/katalog-barang", form.value);
        }
        showForm.value = false;
        dataTable.value?.refresh();
    } catch (err) {
        error.value = err.response?.data?.message || "Gagal menyimpan";
    } finally {
        saving.value = false;
    }
}

function confirmDelete(row) {
    deleteId.value = row.id;
    showDelete.value = true;
}

async function doDelete() {
    deleting.value = true;
    try {
        await api.delete(`/katalog-barang/${deleteId.value}`);
        showDelete.value = false;
        dataTable.value?.refresh();
    } catch (err) {
        alert(err.response?.data?.message || "Gagal menghapus");
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <div>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Katalog Barang</h1>
                <p class="mt-1 text-sm text-gray-500">Kelola data katalog produk sayur & frozen food</p>
            </div>
            <button
                @click="openCreate"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Barang
            </button>
        </div>

        <DataTable
            ref="dataTable"
            :columns="columns"
            :fetch-function="fetchData"
            :external-filters="externalFilters"
            search-placeholder="Cari kode atau nama barang..."
        >
            <template #filters>
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Kategori</label>
                    <div class="relative">
                        <select
                            v-model="filterCategoryId"
                            class="appearance-none w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white min-w-[160px] pr-8"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.nama }}
                            </option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </template>

            <template #rowActions="{ row }">
                <div class="flex justify-center gap-1">
                    <button
                        @click="openEdit(row)"
                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                        title="Edit"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    <button
                        v-if="canDelete"
                        @click="confirmDelete(row)"
                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                        title="Hapus"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <Teleport to="body">
            <div
                v-if="showForm"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="showForm = false"
            >
                <div class="fixed inset-0 bg-black/50"></div>
                <div class="relative z-10 w-full max-w-md p-6 bg-white shadow-2xl rounded-xl">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">
                        {{ editId ? "Edit" : "Tambah" }} Katalog Barang
                    </h3>
                    <form @submit.prevent="saveForm">
                        <div class="space-y-3">
                            <div v-if="editId">
                                <label class="block mb-1 text-sm font-medium text-gray-700">Kode Barang</label>
                                <input
                                    :value="editKodeBarang"
                                    type="text"
                                    readonly
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-500 font-mono"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Nama Barang *</label>
                                <input
                                    v-model="form.nama_barang"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Nama produk"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Kategori *</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <select
                                            v-model="form.category_id"
                                            required
                                            class="appearance-none w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-8"
                                        >
                                            <option value="" disabled>Pilih Kategori</option>
                                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                                {{ cat.nama }}
                                            </option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="showQuickCategory = true"
                                        class="px-2.5 py-2 text-sm font-bold text-blue-600 border border-blue-300 rounded-lg hover:bg-blue-50 transition shrink-0"
                                        title="Tambah kategori baru"
                                    >+</button>
                                </div>
                                <p v-if="!editId" class="mt-1 text-xs text-gray-400">Kode barang akan dibuat otomatis berdasarkan kategori</p>
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Satuan *</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <select
                                            v-model="form.unit_id"
                                            required
                                            class="appearance-none w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-8"
                                        >
                                            <option value="" disabled>Pilih Satuan</option>
                                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                                {{ unit.nama }}
                                            </option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="showQuickUnit = true"
                                        class="px-2.5 py-2 text-sm font-bold text-blue-600 border border-blue-300 rounded-lg hover:bg-blue-50 transition shrink-0"
                                        title="Tambah satuan baru"
                                    >+</button>
                                </div>
                            </div>
                        </div>
                        <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
                        <div class="flex gap-2 mt-4">
                            <button
                                type="button"
                                @click="showForm = false"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 transition bg-gray-100 rounded-lg hover:bg-gray-200"
                            >Batal</button>
                            <button
                                type="submit"
                                :disabled="saving"
                                class="flex-1 px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ saving ? "Menyimpan..." : "Simpan" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <ConfirmDialog
            :show="showDelete"
            :loading="deleting"
            @confirm="doDelete"
            @cancel="showDelete = false"
        />

        <QuickAddModal
            :show="showQuickCategory"
            title="Tambah Kategori Baru"
            :fields="[{ key: 'nama', label: 'Nama Kategori', required: true }]"
            :submit-function="quickAddCategory"
            @close="showQuickCategory = false"
            @created="showQuickCategory = false"
        />

        <QuickAddModal
            :show="showQuickUnit"
            title="Tambah Satuan Baru"
            :fields="[{ key: 'nama', label: 'Nama Satuan', required: true }]"
            :submit-function="quickAddUnit"
            @close="showQuickUnit = false"
            @created="showQuickUnit = false"
        />
    </div>
</template>
