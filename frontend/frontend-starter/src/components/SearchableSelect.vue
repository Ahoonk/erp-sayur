<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from "vue";

const props = defineProps({
    modelValue: { type: [String, Number], default: "" },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: "Pilih produk..." },
    required: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue", "change"]);

const isOpen = ref(false);
const searchQuery = ref("");
const dropdownRef = ref(null);
const inputRef = ref(null);

const selectedOption = computed(() => {
    return props.options.find((o) => o.id === props.modelValue);
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const lower = searchQuery.value.toLowerCase();
    return props.options.filter((o) => o.nama?.toLowerCase().includes(lower));
});

function selectItem(id) {
    emit("update:modelValue", id);
    emit("change", id);
    isOpen.value = false;
    searchQuery.value = "";
}

function toggleDropdown() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = "";
        nextTick(() => {
            if (inputRef.value) inputRef.value.focus();
        });
    }
}

function handleClickOutside(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

onMounted(() => document.addEventListener("click", handleClickOutside));
onUnmounted(() => document.removeEventListener("click", handleClickOutside));
</script>

<template>
    <div class="relative w-full" ref="dropdownRef">
        <!-- Main trigger button -->
        <div
            class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50/50 transition cursor-pointer flex justify-between items-center group hover:bg-white"
            :class="[
                isOpen
                    ? 'ring-2 ring-blue-500 border-blue-400 bg-white shadow-sm'
                    : 'hover:border-slate-300',
            ]"
            @click="toggleDropdown"
        >
            <span
                class="w-full truncate"
                :class="
                    selectedOption
                        ? 'text-slate-700 font-medium'
                        : 'text-slate-500'
                "
            >
                {{ selectedOption ? selectedOption.nama : placeholder }}
            </span>

            <svg
                class="w-4 h-4 text-slate-400 flex-shrink-0 ml-2 transition-transform duration-200"
                :class="{
                    'rotate-180 text-blue-500': isOpen,
                    'group-hover:text-slate-600': !isOpen,
                }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </div>

        <!-- Hidden input for required validation -->
        <input
            type="text"
            :value="modelValue"
            class="hidden"
            :required="required"
        />

        <!-- Dropdown menu -->
        <div
            v-if="isOpen"
            class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden"
            style="
                transform-origin: top;
                animation: dropdown-fade-in 0.15s ease-out;
            "
        >
            <!-- Search bar sticky header -->
            <div class="p-2 border-b border-slate-100 bg-slate-50/50">
                <div class="relative">
                    <svg
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
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
                    <input
                        ref="inputRef"
                        v-model="searchQuery"
                        type="text"
                        class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-400 transition outline-none"
                        placeholder="Cari..."
                        @keydown.stop
                        @click.stop
                    />
                </div>
            </div>

            <!-- Options List -->
            <ul class="max-h-60 overflow-y-auto p-1.5 custom-scrollbar">
                <li
                    v-if="filteredOptions.length === 0"
                    class="px-4 py-8 text-sm text-slate-500 text-center bg-slate-50/30 rounded-lg"
                >
                    <div class="flex flex-col items-center justify-center">
                        <svg
                            class="w-8 h-8 text-slate-300 mb-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>Produk tidak ditemukan</span>
                    </div>
                </li>
                <li
                    v-for="opt in filteredOptions"
                    :key="opt.id"
                    @click="selectItem(opt.id)"
                    class="px-3 py-2.5 text-sm text-slate-700 rounded-lg hover:bg-blue-50 cursor-pointer transition-colors flex items-center justify-between group"
                    :class="{
                        'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-500/20':
                            modelValue === opt.id,
                    }"
                >
                    <span class="truncate pr-4">{{ opt.nama }}</span>
                    <svg
                        v-if="modelValue === opt.id"
                        class="w-4 h-4 text-blue-600 flex-shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
@keyframes dropdown-fade-in {
    from {
        opacity: 0;
        transform: translateY(-4px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
}
</style>
