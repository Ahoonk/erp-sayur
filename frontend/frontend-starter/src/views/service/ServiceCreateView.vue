<script setup>
import { ref, watch, computed, onMounted, onBeforeUnmount } from "vue";
import DateInput from "../../components/DateInput.vue";
import { useRouter } from "vue-router";
import api from "../../api";
import { useToast } from "../../composables/useToast";
import QuickAddModal from "../../components/QuickAddModal.vue";

function formatCurrency(val) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
}

const router = useRouter();
const toast = useToast();

const isLoading = ref(false);
function getLocalDateString() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

const today = getLocalDateString();

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
    tanggal_masuk: today,
    parts: [],
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

function onTechnicianCreated(technician) {
    toast.success("Teknisi berhasil ditambahkan");
}

// Sparepart state
const isSearchingPart = ref(false);
const partSearch = ref("");
const partResults = ref([]);
const selectedPart = ref(null);
const tempQty = ref(1);
const showPartDropdown = ref(false);
const partSearchWrapper = ref(null);

async function searchParts(forceOpen = false) {
    if (forceOpen) {
        showPartDropdown.value = true;
    }
    isSearchingPart.value = true;
    try {
        const { data } = await api.get("/products/search", {
            params: {
                keyword: partSearch.value || "",
                category: "Sparepart",
            },
        });
        partResults.value = (data.data || [])
            .filter((item) =>
                String(item?.category || "")
                    .toLowerCase()
                    .includes("sparepart"),
            )
            .slice(0, 6);
    } catch (e) {
        console.error(e);
        partResults.value = [];
    } finally {
        isSearchingPart.value = false;
    }
}

function selectPart(product) {
    selectedPart.value = product;
    tempQty.value = 1;
    partResults.value = [];
    partSearch.value = "";
    showPartDropdown.value = false;
}

function confirmAddPart() {
    if (!selectedPart.value) return;
    form.value.parts.push({
        product_id: selectedPart.value.id,
        nama: selectedPart.value.nama,
        qty: tempQty.value,
        harga_satuan: selectedPart.value.harga_jual,
    });
    selectedPart.value = null;
    toast.success("Sparepart ditambahkan");
}

function removePart(index) {
    form.value.parts.splice(index, 1);
}

function handlePartFocus() {
    searchParts(true);
}

function handleDocumentClick(event) {
    if (!partSearchWrapper.value) return;
    if (!partSearchWrapper.value.contains(event.target)) {
        showPartDropdown.value = false;
    }
}

onMounted(() => {
    document.addEventListener("click", handleDocumentClick);
    fetchTechnicians();
    fetchServiceBrands();
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleDocumentClick);
});

const totalParts = computed(() => {
    return form.value.parts.reduce((sum, p) => sum + p.qty * p.harga_satuan, 0);
});

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

watch(partSearch, () => {
    if (!showPartDropdown.value) showPartDropdown.value = true;
    searchParts();
});

async function handleSubmit() {
    isLoading.value = true;
    try {
        await api.post("/services", form.value);
        toast.success("Service berhasil didaftarkan");
        router.push("/dashboard/services");
    } catch (err) {
        toast.error(
            err.response?.data?.message || "Gagal mendaftarkan service",
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
                    Service Masuk Baru
                </h1>
                <p class="text-sm text-slate-500">
                    Input detail barang dan keluhan pelanggan
                </p>
            </div>
        </div>

        <form
            @submit.prevent="handleSubmit"
            class="grid grid-cols-1 gap-6 lg:grid-cols-3"
        >
            <!-- Left Side: Basic Info -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Data Pelanggan Card -->
                <div
                    class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200"
                >
                    <div
                        class="px-6 py-4 border-b border-slate-100 bg-slate-50"
                    >
                        <h3
                            class="flex items-center gap-2 text-sm font-bold tracking-wider uppercase text-slate-800"
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
                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                        <div>
                            <label
                                class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
                                >Nama Pelanggan *</label
                            >
                            <DateInput
                                v-model="form.nama_pelanggan"
                                type="text"
                                required
                                placeholder="Contoh: Budi Sudarsono"
                                class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                            />
                        </div>
                        <div>
                            <label
                                class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
                                >No. HP / WhatsApp</label
                            >
                            <input
                                v-model="form.no_hp_pelanggan"
                                type="text"
                                placeholder="0812xxxx"
                                class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                            />
                        </div>
                        <div>
                            <label
                                class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
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
                                    class="px-3 py-2 text-indigo-600 transition-colors bg-indigo-50 rounded-xl hover:bg-indigo-100"
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
                    class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200"
                >
                    <div
                        class="px-6 py-4 border-b border-slate-100 bg-slate-50"
                    >
                        <h3
                            class="flex items-center gap-2 text-sm font-bold tracking-wider uppercase text-slate-800"
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
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label
                                    class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
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
                                        class="px-3 py-2 text-indigo-600 transition-colors bg-indigo-50 rounded-xl hover:bg-indigo-100"
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
                                    class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
                                    >Tipe HP *</label
                                >
                                <input
                                    v-model="form.tipe_hp"
                                    type="text"
                                    required
                                    placeholder="Contoh: Note 20 Ultra"
                                    class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                                />
                            </div>
                            <div>
                                <label
                                    class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
                                    >IMEI / SN</label
                                >
                                <input
                                    v-model="form.imei_hp"
                                    type="text"
                                    placeholder="Masukkan IMEI"
                                    class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium font-mono transition-all"
                                />
                            </div>
                            <div>
                                <label
                                    class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
                                    >Kelengkapan</label
                                >
                                <input
                                    v-model="form.kelengkapan"
                                    type="text"
                                    placeholder="Contoh: Dus, Charger"
                                    class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
                                >Kerusakan / Keluhan *</label
                            >
                            <textarea
                                v-model="form.kerusakan"
                                rows="3"
                                required
                                placeholder="Jelaskan secara detail masalahnya..."
                                class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium transition-all italic"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Action Panel -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Biaya Card -->
                <div
                    class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200"
                >
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50"
                    >
                        <h3
                            class="flex items-center gap-2 text-sm font-bold tracking-wider uppercase text-slate-800"
                        >
                            Spareparts & Biaya
                        </h3>
                        <span
                            class="px-2 py-1 text-xs font-bold rounded-md text-emerald-600 bg-emerald-100"
                            >{{ form.parts.length }} Part</span
                        >
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Sparepart Search -->
                        <div ref="partSearchWrapper" class="relative">
                            <label
                                class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
                                >Tambah Sparepart (Opsional)</label
                            >
                            <div class="relative group">
                                <input
                                    v-model="partSearch"
                                    @focus="handlePartFocus"
                                    type="text"
                                    placeholder="Cari sprepart disini..."
                                    class="w-full py-3 pl-10 pr-4 text-sm font-medium transition-all border rounded-xl border-slate-300 focus:border-blue-500"
                                />
                                <svg
                                    class="absolute w-4 h-4 transition-colors -translate-y-1/2 left-4 top-1/2 text-slate-400 group-focus-within:text-blue-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>

                            <!-- Search Results Popup -->
                            <div
                                v-if="showPartDropdown"
                                class="absolute left-0 top-full z-[60] mt-2 w-full bg-white rounded-xl shadow-xl border border-slate-100 max-h-60 overflow-y-auto"
                            >
                                <div
                                    v-if="isSearchingPart"
                                    class="p-3 text-xs font-semibold text-slate-500"
                                >
                                    Mencari sparepart...
                                </div>
                                <div
                                    v-for="res in partResults"
                                    :key="res.id"
                                    @click="selectPart(res)"
                                    class="p-3 border-b cursor-pointer hover:bg-slate-50 border-slate-50 last:border-0"
                                >
                                    <div
                                        class="text-sm font-bold text-slate-800"
                                    >
                                        {{ res.nama }}
                                    </div>
                                    <div
                                        class="flex justify-between mt-1 text-xs text-blue-600"
                                    >
                                        <span>{{
                                            formatCurrency(res.harga_jual)
                                        }}</span>
                                        <span
                                            :class="
                                                res.stok > 0
                                                    ? 'text-emerald-500'
                                                    : 'text-rose-500'
                                            "
                                            >Stok: {{ res.stok }}</span
                                        >
                                    </div>
                                </div>
                                <div
                                    v-if="
                                        !isSearchingPart &&
                                        partResults.length === 0
                                    "
                                    class="p-3 text-xs text-slate-500"
                                >
                                    Sparepart tidak ditemukan
                                </div>
                            </div>

                            <!-- Selected Part Config -->
                            <div
                                v-if="selectedPart"
                                class="p-4 mt-3 border border-blue-100 bg-blue-50 rounded-xl"
                            >
                                <p
                                    class="mb-2 text-sm font-bold text-slate-800"
                                >
                                    {{ selectedPart.nama }}
                                </p>
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model.number="tempQty"
                                        type="number"
                                        min="1"
                                        class="w-20 px-3 py-2 text-sm bg-white border rounded-lg border-slate-300"
                                        placeholder="Qty"
                                    />
                                    <button
                                        type="button"
                                        @click="confirmAddPart"
                                        class="px-4 py-2 text-xs font-bold text-white transition bg-blue-600 rounded-lg hover:bg-blue-700"
                                    >
                                        Tambahkan
                                    </button>
                                </div>
                            </div>

                            <!-- Selected List -->
                            <div
                                v-if="form.parts.length > 0"
                                class="mt-4 space-y-2"
                            >
                                <div
                                    v-for="(p, idx) in form.parts"
                                    :key="idx"
                                    class="flex items-center justify-between p-3 border border-slate-200 rounded-xl bg-slate-50/50"
                                >
                                    <div class="text-xs">
                                        <p class="font-bold text-slate-800">
                                            {{ p.nama }}
                                        </p>
                                        <p class="mt-1 text-slate-500">
                                            {{ p.qty }}x
                                            {{ formatCurrency(p.harga_satuan) }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removePart(idx)"
                                        class="p-1 text-rose-500 hover:text-rose-700"
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
                                                d="M6 18L18 6M6 6l12 12"
                                            ></path>
                                        </svg>
                                    </button>
                                </div>
                                <div
                                    class="flex items-center justify-between pt-2 text-sm font-bold text-slate-700"
                                >
                                    <span>Subtotal Part</span>
                                    <span>{{
                                        formatCurrency(totalParts)
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100">
                            <label
                                class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
                                >Estimasi Biaya Jasa</label
                            >
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center pl-4 font-bold text-slate-400"
                                    >Rp</span
                                >
                                <input
                                    v-model="displayBiayaJasa"
                                    type="text"
                                    class="block w-full py-3 pl-12 pr-4 text-xl font-black transition-all border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-800"
                                />
                            </div>
                        </div>
                        <div>
                            <label
                                class="block mb-2 text-xs font-semibold tracking-widest uppercase text-slate-500"
                                >Tanggal Masuk</label
                            >
                            <input
                                v-model="form.tanggal_masuk"
                                
                                readonly
                                class="block w-full px-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 text-slate-500 cursor-not-allowed font-medium text-sm"
                            />
                        </div>

                        <div class="pt-4">
                            <button
                                type="submit"
                                :disabled="isLoading"
                                class="flex items-center justify-center w-full gap-2 py-4 text-xs font-black tracking-wider text-white uppercase transition-all bg-blue-600 shadow-xl rounded-2xl shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50"
                            >
                                <svg
                                    v-if="isLoading"
                                    class="w-4 h-4 text-white animate-spin"
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
                                {{
                                    isLoading
                                        ? "Sedang Menyimpan..."
                                        : "Simpan Service"
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

                <div
                    class="p-4 border shadow-sm bg-amber-50 rounded-2xl border-amber-100"
                >
                    <div class="flex gap-3">
                        <svg
                            class="w-5 h-5 text-amber-500 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <p
                            class="text-[11px] text-amber-800 leading-relaxed font-semibold"
                        >
                            Pastikan data pelanggan dan unit sudah benar. Tanda
                            terima akan dapat dicetak setelah data disimpan.
                        </p>
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
                { key: 'no_hp', label: 'No. HP', required: false },
                { key: 'specialist', label: 'Spesialis', required: false },
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




