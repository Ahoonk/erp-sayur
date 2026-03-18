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
const showQuickBrand = ref(false);

const form = ref({
    nama: "",
    brand_id: null,
});

const brands = ref([]);

const columns = [
    { key: "nama", label: "Nama Produk" },
    { key: "brand.nama", label: "Merk" },
];

async function fetchMasterProducts(params) {
    const { data } = await api.get("/master-products", { params });
    return data.data;
}

async function fetchOptions() {
    try {
        const b = await api.get("/brands/all");
        brands.value = b.data.data;
    } catch (err) {
        console.error("Gagal mengambil opsi:", err);
    }
}

onMounted(() => {
    fetchOptions();
});

function openCreate() {
    editId.value = null;
    form.value = {
        nama: "",
        brand_id: null,
    };
    error.value = "";
    showForm.value = true;
}

async function quickBrandCreated(result) {
    await fetchOptions();
    form.value.brand_id = result.data.id;
}

function openEdit(row) {
    editId.value = row.id;
    form.value = {
        nama: row.nama,
        brand_id: row.brand_id,
    };
    error.value = "";
    showForm.value = true;
}

async function saveForm() {
    saving.value = true;
    error.value = "";
    try {
        if (editId.value) {
            await api.put(`/master-products/${editId.value}`, form.value);
        } else {
            await api.post("/master-products", form.value);
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
        await api.delete(`/master-products/${deleteId.value}`);
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
        <div
            class="flex flex-col gap-4 mb-6 sm:flex-row sm:justify-between sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Katalog Produk</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Kelola daftar katalog produk (Master Data)
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
                Tambah Produk
            </button>
        </div>

        <DataTable
            ref="dataTable"
            :columns="columns"
            :fetch-function="fetchMasterProducts"
            search-placeholder="Cari produk..."
        >
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

        <!-- Form Modal -->
        <Teleport to="body">
            <div
                v-if="showForm"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="showForm = false"
            >
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div
                    class="relative z-10 w-full max-w-lg p-6 bg-white shadow-2xl rounded-xl"
                >
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-xl font-bold text-gray-900">
                            {{ editId ? "Edit" : "Tambah" }} Katalog Produk
                        </h3>
                        <button
                            @click="showForm = false"
                            class="text-gray-400 transition hover:text-gray-600"
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
                                />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveForm" class="space-y-4">
                        <div>
                            <label
                                class="block mb-1 text-sm font-semibold text-gray-700"
                                >Nama Produk</label
                            >
                            <input
                                v-model="form.nama"
                                type="text"
                                required
                                class="w-full px-4 py-2 text-sm transition border border-gray-200 shadow-sm bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white"
                                placeholder="Contoh: iPhone 13 Pro Max"
                                @input="
                                    form.nama =
                                        $event.target.value.toUpperCase()
                                "
                            />
                        </div>

                        <div>
                            <label
                                class="block mb-1 text-sm font-semibold text-gray-700"
                                >Merk</label
                            >
                            <div class="flex gap-2">
                                <select
                                    v-model="form.brand_id"
                                    required
                                    class="flex-1 px-4 py-2 text-sm transition border border-gray-200 shadow-sm bg-slate-50 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white"
                                >
                                    <option :value="null">Pilih Merk</option>
                                    <option
                                        v-for="b in brands"
                                        :key="b.id"
                                        :value="b.id"
                                    >
                                        {{ b.nama }}
                                    </option>
                                </select>
                                <button
                                    type="button"
                                    @click="showQuickBrand = true"
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-600"
                                    title="Tambah merk cepat"
                                >
                                    +
                                </button>
                            </div>
                        </div>

                        <p v-if="error" class="text-sm italic text-red-500">
                            {{ error }}
                        </p>

                        <div class="flex gap-3 pt-4 mt-6">
                            <button
                                type="button"
                                @click="showForm = false"
                                class="flex-1 px-4 py-2.5 text-sm font-bold text-slate-500 bg-slate-100 rounded-xl hover:bg-slate-200 transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="saving"
                                class="flex-1 px-4 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition disabled:opacity-50"
                            >
                                {{ saving ? "Menyimpan..." : "Simpan Produk" }}
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
            :show="showQuickBrand"
            title="Tambah Merk Cepat"
            :submit-function="
                (d) => api.post('/brands/quick', d).then((r) => r.data)
            "
            @created="quickBrandCreated"
            @close="showQuickBrand = false"
        />
    </div>
</template>
