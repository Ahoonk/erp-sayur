<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from "vue";
import flatpickr from "flatpickr";
import { Indonesian } from "flatpickr/dist/l10n/id.js";
import "flatpickr/dist/flatpickr.min.css";

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: String, default: "" },
    min: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue", "blur"]);
const inputRef = ref(null);
let fp = null;

// Parse YYYY-MM-DD → Date object (local timezone, no UTC shift)
function isoToDate(iso) {
    if (!iso) return null;
    const [y, m, d] = iso.split("-").map(Number);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
}

// Date object → YYYY-MM-DD
function toISO(date) {
    if (!date) return "";
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
}

onMounted(() => {
    fp = flatpickr(inputRef.value, {
        locale: Indonesian,
        dateFormat: "d/m/Y",
        allowInput: true,
        defaultDate: isoToDate(props.modelValue),
        minDate: isoToDate(props.min),
        onChange: (dates) => emit("update:modelValue", dates[0] ? toISO(dates[0]) : ""),
        onClose: () => emit("blur"),
    });
});

watch(() => props.modelValue, (val) => {
    if (!fp) return;
    const cur = fp.selectedDates[0] ? toISO(fp.selectedDates[0]) : "";
    if (val !== cur) {
        const d = isoToDate(val);
        d ? fp.setDate(d, false) : fp.clear(false);
    }
});

watch(() => props.min, (val) => {
    fp?.set("minDate", isoToDate(val));
});

onBeforeUnmount(() => { fp?.destroy(); fp = null; });
</script>

<template>
    <div class="relative w-full">
        <input
            ref="inputRef"
            v-bind="$attrs"
            type="text"
            placeholder="dd/mm/yyyy"
            style="padding-right: 2.25rem;"
            class="w-full cursor-pointer"
        />
        <button
            type="button"
            tabindex="-1"
            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-blue-500 transition-colors"
            @click.prevent="fp?.open()"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </button>
    </div>
</template>

<style>
.flatpickr-calendar {
    border-radius: 14px !important;
    box-shadow: 0 10px 40px rgba(0,0,0,0.13), 0 2px 8px rgba(0,0,0,0.07) !important;
    border: 1px solid #e2e8f0 !important;
    font-family: inherit !important;
    font-size: 13px !important;
    width: 268px !important;
    padding: 10px !important;
}
.flatpickr-calendar.arrowTop::before,
.flatpickr-calendar.arrowTop::after,
.flatpickr-calendar.arrowBottom::before,
.flatpickr-calendar.arrowBottom::after {
    display: none !important;
}
.flatpickr-months {
    padding-bottom: 6px !important;
    align-items: center !important;
}
.flatpickr-month {
    height: 32px !important;
}
.flatpickr-current-month {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: #1e293b !important;
    padding-top: 2px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 3px !important;
    width: 100% !important;
    left: 0 !important;
}
.flatpickr-current-month .cur-month {
    font-weight: 700 !important;
    color: #1e293b !important;
}
.flatpickr-current-month input.cur-year {
    font-weight: 700 !important;
    color: #1e293b !important;
}
.flatpickr-current-month .numInputWrapper span {
    display: none !important;
}
.flatpickr-months .flatpickr-prev-month,
.flatpickr-months .flatpickr-next-month {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 5px !important;
    border-radius: 8px !important;
    color: #64748b !important;
    transition: background 0.15s, color 0.15s !important;
    top: 10px !important;
}
.flatpickr-months .flatpickr-prev-month:hover,
.flatpickr-months .flatpickr-next-month:hover {
    background: #f1f5f9 !important;
    color: #1e293b !important;
}
.flatpickr-months .flatpickr-prev-month svg,
.flatpickr-months .flatpickr-next-month svg {
    fill: currentColor !important;
    width: 11px !important;
    height: 11px !important;
}
.flatpickr-weekdays {
    margin-bottom: 2px !important;
}
span.flatpickr-weekday {
    font-size: 11px !important;
    font-weight: 600 !important;
    color: #94a3b8 !important;
    text-transform: uppercase !important;
}
.flatpickr-days .dayContainer {
    gap: 2px !important;
    padding: 0 !important;
    min-width: 248px !important;
    max-width: 248px !important;
    width: 248px !important;
}
.flatpickr-day {
    border-radius: 8px !important;
    font-size: 12.5px !important;
    height: 32px !important;
    line-height: 32px !important;
    max-width: 32px !important;
    color: #334155 !important;
    border: none !important;
    transition: background 0.12s !important;
    flex-basis: 32px !important;
}
.flatpickr-day:hover,
.flatpickr-day.prevMonthDay:hover,
.flatpickr-day.nextMonthDay:hover {
    background: #f1f5f9 !important;
    color: #1e293b !important;
    border: none !important;
}
.flatpickr-day.today {
    background: #dbeafe !important;
    color: #1d4ed8 !important;
    font-weight: 700 !important;
    border: none !important;
}
.flatpickr-day.today:hover {
    background: #bfdbfe !important;
    color: #1d4ed8 !important;
}
.flatpickr-day.selected,
.flatpickr-day.selected:hover {
    background: #2563eb !important;
    color: #fff !important;
    font-weight: 700 !important;
    border: none !important;
    box-shadow: 0 2px 6px rgba(37,99,235,0.35) !important;
}
.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {
    color: #cbd5e1 !important;
}
.flatpickr-day.flatpickr-disabled,
.flatpickr-day.flatpickr-disabled:hover {
    color: #e2e8f0 !important;
    background: transparent !important;
    cursor: not-allowed !important;
}
</style>
