<script setup>
import { ref } from "vue";
import api from "../api";
import { useRouter } from "vue-router";
import { useToast } from "../composables/useToast";

const router = useRouter();
const toast = useToast();

const initialForm = () => ({
    name: "",
    username: "",
    password: "",
    role: "kasir",
});
const form = ref(initialForm());
const loading = ref(false);
const showPassword = ref(false);

const createUser = async () => {
    loading.value = true;
    try {
        await api.post("/user", form.value);
        toast.success("Pengguna berhasil dibuat");
        form.value = initialForm();
    } catch (err) {
        toast.error(err.response?.data?.message || "Gagal membuat pengguna");
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="max-w-lg px-4 py-8 mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Tambah Pengguna</h2>
            <p class="mt-1 text-sm text-gray-500">
                Isi formulir di bawah untuk membuat akun pengguna baru.
            </p>
        </div>

        <!-- Card -->
        <div
            class="p-6 space-y-5 bg-white border border-gray-100 shadow-sm rounded-2xl"
        >
            <!-- Nama -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                    >Nama Lengkap</label
                >
                <input
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="Masukkan nama lengkap"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    @input="form.name = ($event.target.value).toUpperCase()"
                />
            </div>

            <!-- Username -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                    >Username</label
                >
                <input
                    v-model="form.username"
                    type="text"
                    required
                    placeholder="Contoh: johndoe123"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                />
                <p class="mt-1 text-xs text-gray-400">
                    Hanya huruf dan angka, tanpa spasi
                </p>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                    >Password</label
                >
                <div class="relative">
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        placeholder="Minimal 3 karakter"
                        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 pr-10 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition"
                    >
                        <svg
                            v-if="!showPassword"
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Role -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                    >Role</label
                >
                <select
                    v-model="form.role"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white"
                >
                    <option value="owner">Owner</option>
                    <option value="admin">Admin</option>
                    <option value="kasir">Kasir</option>
                </select>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-100"></div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button
                    type="button"
                    @click="router.back()"
                    class="flex-1 py-2.5 px-4 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition"
                >
                    Batal
                </button>
                <button
                    @click="createUser"
                    :disabled="loading"
                    class="flex-1 py-2.5 px-4 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition flex items-center justify-center gap-2"
                >
                    <svg
                        v-if="loading"
                        class="w-4 h-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        ></path>
                    </svg>
                    {{ loading ? "Menyimpan..." : "Buat Pengguna" }}
                </button>
            </div>
        </div>
    </div>
</template>
