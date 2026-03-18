<template>
    <div class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-red-100 rounded-full mb-6">
                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h1 class="text-4xl font-black text-slate-800 mb-2">403</h1>
            <h2 class="text-xl font-semibold text-slate-600 mb-4">Akses Ditolak</h2>
            <p class="text-slate-500 mb-8 max-w-md mx-auto">
                Anda tidak memiliki izin untuk mengakses halaman ini. Silakan hubungi administrator jika Anda yakin seharusnya dapat mengakses halaman ini.
            </p>
            <div class="flex flex-col gap-3 justify-center sm:flex-row">
                <router-link to="/dashboard" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                    Kembali ke Dashboard
                </router-link>
                <button @click="$router.back()" class="px-6 py-2.5 bg-white text-slate-700 font-medium rounded-lg border border-slate-300 hover:bg-slate-50 transition">
                    Halaman Sebelumnya
                </button>
            </div>
        </div>
    </div>
</template>
