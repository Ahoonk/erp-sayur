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
const form = ref({ nama: "", no_hp: "", alamat: "", specialist: "" });

const columns = [
    { key: "nama", label: "Nama Teknisi" },
    { key: "no_hp", label: "No. HP" },
    { key: "specialist", label: "Spesialis" },
];

async function fetchData(params) {
    const { data } = await api.get("/technicians", { params });
    return data.data;
}
function openCreate() {
    editId.value = null;
    form.value = { nama: "", no_hp: "", alamat: "", specialist: "" };
    error.value = "";
    showForm.value = true;
}
function openEdit(row) {
    editId.value = row.id;
    form.value = {
        nama: row.nama,
        no_hp: row.no_hp || "",
        alamat: row.alamat || "",
        specialist: row.specialist || "",
    };
    error.value = "";
    showForm.value = true;
}
async function saveForm() {
    saving.value = true;
    error.value = "";
    try {
        if (editId.value)
            await api.put(`/technicians/${editId.value}`, form.value);
        else await api.post("/technicians", form.value);
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
        await api.delete(`/technicians/${deleteId.value}`);
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
                <h1 class="text-2xl font-bold text-gray-900">Teknisi</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Kelola data teknisi / service engineer
                </p>
            </div>
            <button
                @click="openCreate"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700"
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
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Tambah Teknisi
            </button>
        </div>

        <DataTable
            ref="dataTable"
            :columns="columns"
            :fetch-function="fetchData"
            search-placeholder="Cari teknisi..."
        >
            <template #cell="{ row }">
                <td class="table-cell font-medium text-slate-800">
                    {{ row.nama }}
                </td>
                <td class="table-cell text-slate-500">
                    {{ row.no_hp || "-" }}
                </td>
                <td class="table-cell text-slate-500">
                    {{ row.specialist || "-" }}
                </td>
            </template>
            <template #rowActions="{ row }">
                <div class="flex justify-center gap-1">
                    <button
                        @click="openEdit(row)"
                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                        title="Edit"
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
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                    </button>
                    <button
                        v-if="canDelete"
                        @click="confirmDelete(row)"
                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                        title="Hapus"
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
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Create/Edit Modal -->
        <div
            v-if="showForm"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        >
            <div class="relative z-10 w-full max-w-md p-6 bg-white shadow-2xl rounded-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ editId ? "Edit Teknisi" : "Tambah Teknisi" }}
                </h3>
                <form @submit.prevent="saveForm" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Nama *</label
                        >
                        <input
                            v-model="form.nama"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"
                            placeholder="Nama teknisi"
                            @input="form.nama = ($event.target.value).toUpperCase()"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >No. HP</label
                        >
                        <input
                            v-model="form.no_hp"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"
                            placeholder="0812xxxx"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Spesialis</label
                        >
                        <input
                            v-model="form.specialist"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"
                            placeholder="Contoh: iPhone, Samsung, Xiaomi"
                            @input="form.specialist = ($event.target.value).toUpperCase()"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Alamat</label
                        >
                        <textarea
                            v-model="form.alamat"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border"
                            placeholder="Alamat"
                        ></textarea>
                    </div>
                    <p v-if="error" class="text-sm text-red-500 mt-2">
                        {{ error }}
                    </p>
                    <div class="flex gap-2 mt-4">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ saving ? "Menyimpan..." : "Simpan" }}
                        </button>
                        <button
                            type="button"
                            @click="showForm = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                        >
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmDialog
            v-if="showDelete"
            title="Hapus Teknisi"
            message="Apakah Anda yakin ingin menghapus teknisi ini?"
            @confirm="doDelete"
            @cancel="showDelete = false"
            :loading="deleting"
        />
    </div>
</template>
