<template>
    <div class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-slate-200 rounded-full mb-6">
                <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-4xl font-black text-slate-800 mb-2">404</h1>
            <h2 class="text-xl font-semibold text-slate-600 mb-4">Halaman Tidak Ditemukan</h2>
            <p class="text-slate-500 mb-8 max-w-md mx-auto">
                Maaf, halaman yang Anda cari tidak ada atau telah dipindahkan. Silakan kembali ke halaman utama.
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
