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

const form = ref({
    nama: "",
    telepon: "",
    alamat: "",
    keterangan: "",
    is_active: true,
});

const columns = [
    { key: "nama", label: "Nama" },
    { key: "telepon", label: "Telepon" },
    { key: "alamat", label: "Alamat" },
];

async function fetchData(params) {
    const { data } = await api.get("/mitra", { params });
    return data.data;
}

function openCreate() {
    editId.value = null;
    form.value = { nama: "", telepon: "", alamat: "", keterangan: "", is_active: true };
    error.value = "";
    showForm.value = true;
}

function openEdit(row) {
    editId.value = row.id;
    form.value = {
        nama: row.nama,
        telepon: row.telepon || "",
        alamat: row.alamat || "",
        keterangan: row.keterangan || "",
        is_active: row.is_active,
    };
    error.value = "";
    showForm.value = true;
}

async function saveForm() {
    saving.value = true;
    error.value = "";
    try {
        if (editId.value) {
            await api.put(`/mitra/${editId.value}`, form.value);
        } else {
            await api.post("/mitra", form.value);
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
        await api.delete(`/mitra/${deleteId.value}`);
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
                <h1 class="text-2xl font-bold text-gray-900">Mitra</h1>
                <p class="mt-1 text-sm text-gray-500">Kelola data mitra / pelanggan</p>
            </div>
            <button
                @click="openCreate"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Mitra
            </button>
        </div>

        <DataTable
            ref="dataTable"
            :columns="columns"
            :fetch-function="fetchData"
            search-placeholder="Cari mitra..."
        >
            <template #actions></template>
            <template #cell-is_active="{ row }">
                <span
                    :class="row.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                    class="px-2.5 py-1 rounded-full text-xs font-semibold"
                >
                    {{ row.is_active ? "Aktif" : "Nonaktif" }}
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
                <div class="relative z-10 w-full max-w-md p-6 bg-white shadow-2xl rounded-xl">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">
                        {{ editId ? "Edit" : "Tambah" }} Mitra
                    </h3>
                    <form @submit.prevent="saveForm">
                        <div class="space-y-3">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Nama *</label>
                                <input
                                    v-model="form.nama"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Nama mitra"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Telepon</label>
                                <input
                                    v-model="form.telepon"
                                    type="text"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="08xxx"
                                />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Alamat</label>
                                <textarea
                                    v-model="form.alamat"
                                    rows="2"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Alamat lengkap"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Keterangan</label>
                                <textarea
                                    v-model="form.keterangan"
                                    rows="2"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Catatan tambahan"
                                ></textarea>
                            </div>
                            <div class="flex items-center gap-3 pt-1">
                                <label class="text-sm font-medium text-gray-700">Status Aktif</label>
                                <button
                                    type="button"
                                    @click="form.is_active = !form.is_active"
                                    :class="form.is_active ? 'bg-blue-600' : 'bg-gray-200'"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                                >
                                    <span
                                        :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                                    ></span>
                                </button>
                                <span class="text-sm" :class="form.is_active ? 'text-emerald-600 font-medium' : 'text-gray-400'">
                                    {{ form.is_active ? "Aktif" : "Nonaktif" }}
                                </span>
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
