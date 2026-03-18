<script setup>
import { computed, ref } from "vue";
import api from "../../api";
import DataTable from "../../components/DataTable.vue";
import ConfirmDialog from "../../components/ConfirmDialog.vue";
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

const form = ref({ nama: "", kode_prefix: "" });

const prefixOptions = [
    { kode: "V", label: "V - Sayur" },
    { kode: "F", label: "F - Buah" },
    { kode: "R", label: "R - Beras" },
    { kode: "A", label: "A - Ayam Daging" },
    { kode: "D", label: "D - Dry Good" },
    { kode: "FF", label: "FF - Frozen Food" },
];

const columns = [
    { key: "kode_prefix", label: "Kode Prefix" },
    { key: "nama", label: "Nama Kategori" },
];

async function fetchData(params) {
    const { data } = await api.get("/categories", { params });
    return data.data;
}

function openCreate() {
    editId.value = null;
    form.value = { nama: "", kode_prefix: "" };
    error.value = "";
    showForm.value = true;
}

function openEdit(row) {
    editId.value = row.id;
    form.value = {
        nama: row.nama,
        kode_prefix: row.kode_prefix || "",
    };
    error.value = "";
    showForm.value = true;
}

async function saveForm() {
    saving.value = true;
    error.value = "";
    try {
        if (editId.value) {
            await api.put(`/categories/${editId.value}`, form.value);
        } else {
            await api.post("/categories", form.value);
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
        await api.delete(`/categories/${deleteId.value}`);
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
                <h1 class="text-2xl font-bold text-gray-900">Kategori</h1>
                <p class="mt-1 text-sm text-gray-500">Kelola data kategori produk</p>
            </div>
            <button
                @click="openCreate"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Kategori
            </button>
        </div>

        <DataTable
            ref="dataTable"
            :columns="columns"
            :fetch-function="fetchData"
            search-placeholder="Cari kategori..."
        >
            <template #actions></template>
            <template #cell-kode_prefix="{ row }">
                <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 font-mono">
                    {{ row.kode_prefix || "-" }}
                </span>
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
                <div class="relative z-10 w-full max-w-sm p-6 bg-white shadow-2xl rounded-xl">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">
                        {{ editId ? "Edit" : "Tambah" }} Kategori
                    </h3>
                    <form @submit.prevent="saveForm">
                        <div class="space-y-3">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Nama Kategori *</label>
                                <input
                                    v-model="form.nama"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Contoh: Sayuran"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Kode Prefix *</label>
                                <div class="relative">
                                    <select
                                        v-model="form.kode_prefix"
                                        required
                                        class="appearance-none w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-8"
                                    >
                                        <option value="" disabled>Pilih Kode Prefix</option>
                                        <option v-for="opt in prefixOptions" :key="opt.kode" :value="opt.kode">
                                            {{ opt.label }}
                                        </option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Prefix digunakan untuk generate kode barang otomatis</p>
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
    </div>
</template>
