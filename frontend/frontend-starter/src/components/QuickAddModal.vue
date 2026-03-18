<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @click.self="$emit('close')"
        >
            <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
            <div
                class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 z-10 transform transition-all"
            >
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ title }}
                </h3>

                <form @submit.prevent="handleSubmit">
                    <div class="space-y-3">
                        <div v-for="field in fields" :key="field.key">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ field.label }}</label
                            >
                            <div v-if="field.type === 'select'" class="relative">
                                <select
                                    v-model="formData[field.key]"
                                    :required="field.required !== false"
                                    class="appearance-none w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm pr-8"
                                >
                                    <option value="" disabled>{{ field.placeholder || 'Pilih ' + field.label }}</option>
                                    <option v-for="opt in field.options" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            <input
                                v-else-if="field.type !== 'textarea'"
                                :type="field.type || 'text'"
                                v-model="formData[field.key]"
                                :placeholder="field.placeholder || field.label"
                                :required="field.required !== false"
                                :step="field.step"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                @input="field.uppercase ? (formData[field.key] = $event.target.value.toUpperCase()) : null"
                            />
                            <textarea
                                v-else
                                v-model="formData[field.key]"
                                :placeholder="field.placeholder || field.label"
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            ></textarea>
                        </div>
                    </div>

                    <p v-if="error" class="text-red-500 text-sm mt-2">
                        {{ error }}
                    </p>

                    <div class="flex gap-2 mt-5">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                        >
                            {{ submitting ? "Menyimpan..." : "Simpan" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch, reactive } from "vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: "Tambah Cepat" },
    fields: {
        type: Array,
        default: () => [{ key: "nama", label: "Nama", required: true }],
    },
    submitFunction: { type: Function, required: true },
});

const emit = defineEmits(["close", "created"]);

const formData = reactive({});
const submitting = ref(false);
const error = ref("");

watch(
    () => props.show,
    (val) => {
        if (val) {
            // Reset form
            props.fields.forEach((f) => {
                formData[f.key] = f.default || "";
            });
            error.value = "";
        }
    },
);

async function handleSubmit() {
    submitting.value = true;
    error.value = "";
    try {
        const result = await props.submitFunction({ ...formData });
        emit("created", result);
        emit("close");
    } catch (err) {
        error.value = err.response?.data?.message || "Gagal menyimpan data";
    } finally {
        submitting.value = false;
    }
}
</script>
