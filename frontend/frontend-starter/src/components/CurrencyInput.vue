<script setup>
import { ref, watch, nextTick } from "vue";

const props = defineProps({
    modelValue: { type: [Number, String], default: "" },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    allowThousands: { type: Boolean, default: false }, // Option to enable the .000 feature
});
const emit = defineEmits(["update:modelValue"]);

const displayValue = ref("");
const appendThousands = ref(true);

function formatNumber(num) {
    if (!num && num !== 0) return "";
    return Number(num).toLocaleString("id-ID");
}

function parseRaw(str) {
    if (!str) return "";
    return str.replace(/[^0-9]/g, "");
}

let isEmitting = false;

// Sync external changes
watch(
    () => props.modelValue,
    (val) => {
        if (isEmitting) {
            isEmitting = false;
            return;
        }

        let raw = parseRaw(String(val));
        if (raw === "0") raw = ""; // Treat 0 as empty for init logic

        if (props.allowThousands) {
            // Un-multiply for display if it ends with 000
            if (raw.length > 3 && raw.endsWith("000")) {
                raw = raw.slice(0, -3);
                appendThousands.value = true;
            } else if (raw !== "") {
                // If the external value does NOT end with 000,
                // we'll un-check the appendThousands button
                // so it displays the real exact number
                appendThousands.value = false;
            } else {
                appendThousands.value = true;
            }
        }

        displayValue.value = raw ? formatNumber(raw) : "";
    },
    { immediate: true },
);

function emitValue(rawDisplay) {
    let finalValue = rawDisplay;
    if (props.allowThousands && appendThousands.value && finalValue) {
        finalValue += "000";
    }
    isEmitting = true;
    emit("update:modelValue", finalValue ? Number(finalValue) : 0);
}

function onInput(e) {
    const el = e.target;
    // Get cursor pos
    let cursorPos = el.selectionStart;
    const oldLen = el.value.length;

    let raw = parseRaw(el.value);
    const formatted = raw ? formatNumber(raw) : "";
    displayValue.value = formatted;

    emitValue(raw);

    // restore cursor
    const diff = formatted.length - oldLen;
    nextTick(() => {
        let newPos = cursorPos + diff;
        el.setSelectionRange(newPos, newPos);
    });
}

function toggleThousands() {
    appendThousands.value = !appendThousands.value;
    const raw = parseRaw(displayValue.value);
    emitValue(raw);
}
</script>

<template>
    <div class="relative flex items-center">
        <span
            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-medium pointer-events-none select-none z-10"
            >Rp</span
        >
        <input
            type="text"
            inputmode="numeric"
            :value="displayValue"
            @input="onInput"
            :placeholder="'0'"
            :required="required"
            :disabled="disabled"
            class="w-full pl-9 py-2.5 border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-400 bg-slate-50/50 transition-all duration-200 hover:border-slate-300 rounded-xl"
            :class="[allowThousands ? 'pr-14' : 'pr-3']"
        />

        <!-- Toggle button for appending .000 -->
        <button
            v-if="allowThousands"
            type="button"
            @click="toggleThousands"
            class="absolute right-1.5 top-1/2 -translate-y-1/2 px-2 py-1.5 rounded-lg text-xs font-bold transition-all"
            :class="
                appendThousands
                    ? 'bg-blue-100 text-blue-700 hover:bg-blue-200 ring-1 ring-blue-300 shadow-sm'
                    : 'bg-slate-100/80 text-slate-400 hover:bg-slate-200 hover:text-slate-600 border border-slate-200'
            "
            title="Otomatis tambah 3 digit 0"
        >
            .000
        </button>
    </div>
</template>
