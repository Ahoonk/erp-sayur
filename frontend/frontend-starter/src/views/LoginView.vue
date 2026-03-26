<script setup>
import { ref, onMounted } from "vue";
import { useAuthStore } from "../stores/auth";
import { useRouter } from "vue-router";
import api from "../api";

const authStore = useAuthStore();
const router = useRouter();

const form = ref({
    login: "",
    password: "",
});

const loginError = ref("");
const storeProfile = ref({
    name: "App Kasir",
    logo_url: null,
});

const showPassword = ref(false);

const handleLogin = async () => {
    loginError.value = "";
    const success = await authStore.login(form.value);

    // Kosongkan password setelah percobaan login
    form.value.password = "";

    if (success) {
        router.push("/");
    } else {
        loginError.value = authStore.error || "Username atau password salah";
    }
};

async function fetchStoreProfile() {
    try {
        const { data } = await api.get("/logo");
        if (data?.data) {
            storeProfile.value = {
                name: data.data.name || "App Kasir",
                logo_url: data.data.logo_url || null,
            };
        }
    } catch (error) {
        // Fallback jika tidak ada data logo
    }
}

onMounted(fetchStoreProfile);
</script>

<template>
    <div
        class="flex flex-col justify-center min-h-screen px-4 py-12 font-sans bg-gradient-to-br from-emerald-50 via-gray-50 to-white sm:px-6 lg:px-8"
    >
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div
                class="relative px-4 py-8 bg-white border border-gray-100 shadow-2xl shadow-emerald-900/5 rounded-3xl sm:py-12 sm:px-12"
            >
                <div
                    class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-green-500 to-emerald-600 rounded-t-3xl"
                ></div>

                <div class="mb-8 sm:mx-auto sm:w-full sm:max-w-md">
                    <div class="flex justify-center">
                        <img
                            v-if="storeProfile.logo_url"
                            :src="storeProfile.logo_url"
                            alt="Logo Toko"
                            class="object-contain w-auto h-20 drop-shadow-sm"
                        />
                        <div
                            v-else
                            class="flex items-center justify-center w-20 h-20 text-white shadow-lg bg-gradient-to-tr from-green-600 to-emerald-500 rounded-2xl shadow-emerald-500/30"
                        >
                            <svg
                                class="w-10 h-10"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                />
                            </svg>
                        </div>
                    </div>

                    <h2
                        class="mt-6 text-2xl font-extrabold tracking-tight text-center text-gray-900 sm:text-3xl"
                    >
                        CV DHAROES PUTRA
                    </h2>
                    <p class="mt-2 text-sm text-center text-gray-500">
                        Silakan masuk ke akun Anda untuk melanjutkan
                    </p>
                </div>

                <div
                    v-if="loginError"
                    class="flex items-center p-4 mb-6 space-x-3 text-red-700 border-l-4 border-red-500 rounded-r-lg bg-red-50"
                >
                    <svg
                        class="w-5 h-5 text-red-500"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <p class="text-sm font-medium">{{ loginError }}</p>
                </div>

                <form class="space-y-6" @submit.prevent="handleLogin">
                    <div>
                        <label
                            for="login"
                            class="block text-sm font-semibold text-gray-700"
                            >Username</label
                        >
                        <div class="relative mt-2">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                            >
                                <svg
                                    class="w-5 h-5 text-gray-400"
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
                            </div>
                            <input
                                v-model="form.login"
                                id="login"
                                name="login"
                                type="text"
                                autocomplete="username"
                                required
                                class="block w-full py-3 pl-10 pr-3 text-sm text-gray-900 transition duration-200 border border-gray-300 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder:text-gray-400"
                                placeholder="username"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="block text-sm font-semibold text-gray-700"
                            >Password</label
                        >
                        <div class="relative mt-2">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                            >
                                <svg
                                    class="w-5 h-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>

                            <input
                                v-model="form.password"
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                class="block w-full py-3 pl-10 pr-10 text-sm text-gray-900 transition duration-200 border border-gray-300 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder:text-gray-400"
                                placeholder="••••••••"
                            />

                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none"
                            >
                                <svg
                                    v-if="!showPassword"
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
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
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                id="remember-me"
                                name="remember-me"
                                type="checkbox"
                                class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                            />
                            <label
                                for="remember-me"
                                class="block ml-2 text-sm text-gray-600"
                            >
                                Ingat saya
                            </label>
                        </div>

                        <div class="text-sm">
                            <a
                                href="#"
                                class="font-semibold text-emerald-600 transition-colors hover:text-emerald-500"
                            >
                                Lupa password?
                            </a>
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="authStore.loading"
                            class="relative flex justify-center w-full px-4 py-3.5 text-sm font-bold text-white transition-all bg-gradient-to-r from-green-600 to-emerald-600 border border-transparent rounded-xl shadow-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-70 disabled:cursor-not-allowed transform active:scale-[0.98]"
                        >
                            <span
                                v-if="authStore.loading"
                                class="absolute inset-y-0 left-0 flex items-center pl-4"
                            >
                                <svg
                                    class="w-5 h-5 text-white animate-spin"
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
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                            </span>
                            {{
                                authStore.loading
                                    ? "Memproses..."
                                    : "Masuk Sekarang"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
