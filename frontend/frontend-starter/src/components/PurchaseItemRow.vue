<script setup>
import { storageUrl } from "../utils/storage";

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    showActions: {
        type: Boolean,
        default: false,
    },
    canRemove: {
        type: Boolean,
        default: true,
    },
});

defineEmits(["edit", "remove", "image-click"]);

function formatCurrency(val) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
}
</script>

<template>
    <div
        class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50/60 transition"
    >
        <!-- Row Number -->
        <span
            class="text-xs text-slate-300 font-bold w-5 text-center shrink-0"
            >{{ index }}</span
        >

        <!-- Photo (clickable) -->
        <div
            class="w-11 h-11 rounded-xl bg-slate-100 shrink-0 overflow-hidden border border-slate-200 flex items-center justify-center"
            :class="
                item.product?.foto
                    ? 'cursor-pointer hover:opacity-75 hover:border-blue-300 transition'
                    : ''
            "
            @click="
                item.product?.foto
                    ? $emit('image-click', storageUrl(item.product.foto))
                    : null
            "
            :title="item.product?.foto ? 'Klik untuk lihat foto' : ''"
        >
            <img
                v-if="storageUrl(item.product?.foto)"
                :src="storageUrl(item.product?.foto)"
                class="w-full h-full object-cover"
                @error="$event.target.style.display = 'none'"
            />
            <svg
                v-if="!item.product?.foto"
                class="w-5 h-5 text-slate-300"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
            </svg>
        </div>

        <!-- Main Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span
                    class="font-mono text-[11px] text-blue-600 font-semibold"
                    >{{ item.product?.barcode }}</span
                >
                <span
                    v-if="item.product?.unit"
                    class="text-[10px] px-1.5 py-0.5 bg-amber-50 text-amber-600 rounded border border-amber-200 font-medium"
                    >{{ item.product.unit }}</span
                >
                <span
                    v-if="item.product?.grade"
                    class="text-[10px] px-1.5 py-0.5 bg-purple-50 text-purple-600 rounded border border-purple-200 font-medium"
                    >{{ item.product.grade }}</span
                >
            </div>
            <p class="text-sm font-medium text-slate-700 truncate mt-0.5">
                {{ item.product?.nama }}
            </p>
            <div
                class="flex items-center gap-3 mt-0.5 text-[11px] text-slate-400"
            >
                <span v-if="item.product?.brand">{{ item.product.brand }}</span>
                <span v-if="item.product?.category" class="text-slate-300"
                    >·</span
                >
                <span v-if="item.product?.category">{{
                    item.product.category
                }}</span>
            </div>
        </div>

        <!-- Fixed-width columns for alignment -->
        <div class="flex items-center gap-3 shrink-0">
            <!-- IMEI 1 -->
            <div class="text-right w-32">
                <p class="text-[10px] text-slate-400 mb-0.5">IMEI 1</p>
                <p
                    class="text-xs font-mono font-medium text-slate-600 whitespace-nowrap"
                >
                    {{ item.product?.imei1 || "-" }}
                </p>
            </div>
            <!-- IMEI 2 -->
            <div class="text-right w-32">
                <p class="text-[10px] text-slate-400 mb-0.5">IMEI 2</p>
                <p
                    class="text-xs font-mono font-medium text-slate-600 whitespace-nowrap"
                >
                    {{ item.product?.imei2 || "-" }}
                </p>
            </div>
            <div class="w-px h-8 bg-slate-100"></div>
            <!-- Qty -->
            <div class="text-center w-12">
                <p class="text-[10px] text-slate-400 mb-0.5">Qty</p>
                <p class="text-sm font-bold text-slate-700">{{ item.qty }}</p>
            </div>
            <div class="w-px h-8 bg-slate-100"></div>
            <!-- Harga Beli -->
            <div class="text-right w-28">
                <p class="text-[10px] text-slate-400 mb-0.5">Harga Beli</p>
                <p class="text-xs font-medium text-slate-600 whitespace-nowrap">
                    {{ formatCurrency(item.harga_beli) }}
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div
            v-if="showActions"
            class="flex items-center gap-1.5 shrink-0 ml-4 border-l border-slate-100 pl-4"
        >
            <button
                @click="$emit('edit', item)"
                class="p-1.5 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                title="Edit"
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
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                </svg>
            </button>
            <button
                v-if="canRemove"
                @click="$emit('remove', item.id)"
                class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                title="Hapus"
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
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>
