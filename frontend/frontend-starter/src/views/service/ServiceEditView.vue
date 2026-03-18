<script setup>
import { ref, onMounted, watch } from "vue";
import DateInput from "../../components/DateInput.vue";
import { useRouter, useRoute } from "vue-router";
import api from "../../api";
import { useToast } from "../../composables/useToast";
import QuickAddModal from "../../components/QuickAddModal.vue";

const router = useRouter();
const route = useRoute();
const toast = useToast();

const isLoading = ref(false);
const isFetching = ref(true);
const technicians = ref([]);
const serviceBrands = ref([]);

const form = ref({
    nama_pelanggan: "",
    no_hp_pelanggan: "",
    service_brand_id: "",
    merk_hp: "",
    tipe_hp: "",
    kerusakan: "",
    imei_hp: "",
    kelengkapan: "",
    biaya_jasa: 0,
    technician_id: "",
    tanggal_masuk: "",
});

const displayBiayaJasa = ref("0");
const showQuickAddTechnician = ref(false);
const showQuickAddBrand = ref(false);

async function fetchTechnicians() {
    try {
        const { data } = await api.get("/technicians/all");
        technicians.value = data.data || [];
    } catch (e) {
        console.error("Failed to fetch technicians", e);
    }
}

async function fetchServiceBrands() {
    try {
        const { data } = await api.get("/service-brands/all");
        serviceBrands.value = data.data || [];
    } catch (e) {
        console.error("Failed to fetch service brands", e);
    }
}

async function handleQuickAddBrand({ nama }) {
    try {
        const { data } = await api.post("/service-brands/quick", { nama });
        serviceBrands.value.push(data.data);
        form.value.service_brand_id = data.data.id;
        showQuickAddBrand.value = false;
        toast.success("Merk HP berhasil ditambahkan");
        return data.data;
    } catch (e) {
        throw e;
    }
}

async function handleQuickAddTechnician({ nama, no_hp, specialist }) {
    try {
        const { data } = await api.post("/technicians/quick", {
            nama,
            no_hp,
            specialist,
        });
        technicians.value.push(data.data);
        form.value.technician_id = data.data.id;
        showQuickAddTechnician.value = false;
        return data.data;
    } catch (e) {
        throw e;
    }
}

function onTechnicianCreated() {
    toast.success("Teknisi berhasil ditambahkan");
}

function formatInputCurrency(val) {
    if (!val && val !== 0) return "";
    let str = val.toString().replace(/\D/g, "");
    if (str === "") return "";
    return new Intl.NumberFormat("id-ID").format(parseInt(str));
}

function parseInputCurrency(val) {
    if (!val) return 0;
    return parseInt(val.toString().replace(/\D/g, "")) || 0;
}

watch(
    () => displayBiayaJasa.value,
    (newVal) => {
        const rawValue = parseInputCurrency(newVal);
        if (form.value.biaya_jasa !== rawValue) {
            form.value.biaya_jasa = rawValue;
        }
        const formatted = formatInputCurrency(rawValue);
        if (displayBiayaJasa.value !== formatted) {
            displayBiayaJasa.value = formatted;
        }
    },
);

async function fetchService() {
    try {
        const { data } = await api.get(`/services/${route.params.id}`);
        const service = data.data;
        form.value = {
            nama_pelanggan: service.nama_pelanggan,
            no_hp_pelanggan: service.no_hp_pelanggan || "",
            service_brand_id: service.service_brand_id || "",
            merk_hp: service.merk_hp,
            tipe_hp: service.tipe_hp,
            kerusakan: service.kerusakan,
            imei_hp: service.imei_hp || "",
            kelengkapan: service.kelengkapan || "",
            biaya_jasa: service.biaya_jasa,
            technician_id: service.technician_id || "",
            tanggal_masuk: service.tanggal_masuk,
        };
        displayBiayaJasa.value = formatInputCurrency(service.biaya_jasa);
    } catch (err) {
        toast.error("Gagal memuat data servis");
        router.push("/dashboard/services");
    } finally {
        isFetching.value = false;
    }
}

onMounted(() => {
    fetchService();
    fetchTechnicians();
    fetchServiceBrands();
});

async function handleSubmit() {
    isLoading.value = true;
    try {
        await api.put(`/services/${route.params.id}`, form.value);
        toast.success("Data servis berhasil diperbarui");
        router.push("/dashboard/services");
    } catch (err) {
        toast.error(
            err.response?.data?.message || "Gagal memperbarui data servis",
        );
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <div class="px-4 md:px-8 mx-auto py-6 space-y-6 w-full">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <button
                @click="router.back()"
                class="p-2.5 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm"
            >
                <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>
            </button>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Edit Data Service
                </h1>
                <p class="text-slate-500 text-sm">
                    Perbarui informasi unit atau detail pelanggan
                </p>
            </div>
        </div>

        <div v-if="isFetching" class="flex justify-center items-center py-20">
            <div
                class="w-10 h-10 border-4 border-blue-50 border-t-blue-500 rounded-full animate-spin"
            ></div>
        </div>

        <form
            v-else
            @submit.prevent="handleSubmit"
            class="grid grid-cols-1 lg:grid-cols-3 gap-6"
        >
            <!-- Left Side: Basic Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Data Pelanggan Card -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden"
                >
                    <div
                        class="px-6 py-4 border-b border-slate-100 bg-slate-50"
                    >
                        <h3
                            class="font-bold text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2"
                        >
                            <svg
                                class="w-4 h-4 text-blue-500"
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
                            Informasi Pelanggan
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                >Nama Pelanggan *</label
                            >
                            <DateInput
                                v-model="form.nama_pelanggan"
                                type="text"
                                required
                                class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                >No. HP / WhatsApp</label
                            >
                            <input
                                v-model="form.no_hp_pelanggan"
                                type="text"
                                class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                >Teknisi</label
                            >
                            <div class="flex gap-2">
                                <select
                                    v-model="form.technician_id"
                                    class="block flex-1 px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                                >
                                    <option value="">
                                        -- Pilih Teknisi --
                                    </option>
                                    <option
                                        v-for="tech in technicians"
                                        :key="tech.id"
                                        :value="tech.id"
                                    >
                                        {{ tech.nama }}
                                    </option>
                                </select>
                                <button
                                    type="button"
                                    @click="showQuickAddTechnician = true"
                                    class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition-colors"
                                    title="Tambah Teknisi"
                                >
                                    <svg
                                        class="w-5 h-5"
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
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Unit Card -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden"
                >
                    <div
                        class="px-6 py-4 border-b border-slate-100 bg-slate-50"
                    >
                        <h3
                            class="font-bold text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2"
                        >
                            <svg
                                class="w-4 h-4 text-indigo-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"
                                />
                            </svg>
                            Detail Unit & Keluhan
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                    >Merk HP *</label
                                >
                                <div class="flex gap-2">
                                    <select
                                        v-model="form.service_brand_id"
                                        required
                                        class="block flex-1 px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                                    >
                                        <option value="">
                                            -- Pilih Merk --
                                        </option>
                                        <option
                                            v-for="brand in serviceBrands"
                                            :key="brand.id"
                                            :value="brand.id"
                                        >
                                            {{ brand.nama }}
                                        </option>
                                    </select>
                                    <button
                                        type="button"
                                        @click="showQuickAddBrand = true"
                                        class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition-colors"
                                        title="Tambah Merk"
                                    >
                                        <svg
                                            class="w-5 h-5"
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
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                    >Tipe HP *</label
                                >
                                <input
                                    v-model="form.tipe_hp"
                                    type="text"
                                    required
                                    class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                    >IMEI / SN</label
                                >
                                <input
                                    v-model="form.imei_hp"
                                    type="text"
                                    class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium font-mono transition-all"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                    >Kelengkapan</label
                                >
                                <input
                                    v-model="form.kelengkapan"
                                    type="text"
                                    class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                >Kerusakan / Keluhan *</label
                            >
                            <textarea
                                v-model="form.kerusakan"
                                rows="3"
                                required
                                class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all italic"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Action Panel -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Biaya Card -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden"
                >
                    <div
                        class="px-6 py-4 border-b border-slate-100 bg-slate-50"
                    >
                        <h3
                            class="font-bold text-slate-800 text-sm uppercase tracking-wider"
                        >
                            Konfirmasi Biaya
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                >Estimasi Biaya Jasa</label
                            >
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold"
                                    >Rp</span
                                >
                                <input
                                    v-model="displayBiayaJasa"
                                    type="text"
                                    class="block w-full pl-12 pr-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xl font-black text-slate-800 transition-all"
                                />
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2"
                                >Tanggal Masuk</label
                            >
                            <input
                                v-model="form.tanggal_masuk"
                                
                                class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium"
                            />
                        </div>

                        <div class="pt-4">
                            <button
                                type="submit"
                                :disabled="isLoading"
                                class="w-full py-4 rounded-2xl bg-blue-600 text-white font-black uppercase tracking-wider text-xs shadow-xl shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50 transition-all flex items-center justify-center gap-2"
                            >
                                <svg
                                    v-if="isLoading"
                                    class="animate-spin h-4 w-4 text-white"
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
                                    />
                                </svg>
                                {{
                                    isLoading
                                        ? "Sedang Menyimpan..."
                                        : "Update Data Servis"
                                }}
                            </button>
                            <button
                                type="button"
                                @click="router.back()"
                                class="w-full mt-3 py-3 rounded-2xl border-2 border-slate-100 text-slate-400 font-black uppercase tracking-wider text-[10px] hover:bg-slate-50 transition-all"
                            >
                                Batalkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <QuickAddModal
            v-if="showQuickAddTechnician"
            title="Tambah Teknisi"
            :show="showQuickAddTechnician"
            :fields="[
                { key: 'nama', label: 'Nama Teknisi *', required: true },
                { key: 'no_hp', label: 'No. HP' },
                { key: 'specialist', label: 'Spesialis' },
            ]"
            :submitFunction="handleQuickAddTechnician"
            @close="showQuickAddTechnician = false"
            @created="onTechnicianCreated"
        />

        <QuickAddModal
            v-if="showQuickAddBrand"
            title="Tambah Merk HP"
            :show="showQuickAddBrand"
            :fields="[{ key: 'nama', label: 'Nama Merk *', required: true }]"
            :submitFunction="handleQuickAddBrand"
            @close="showQuickAddBrand = false"
            @created="() => toast.success('Merk HP berhasil ditambahkan')"
        />
    </div>
</template>




