<script setup>
defineProps({
    src: { type: String, default: null },
    show: { type: Boolean, default: false },
    alt: { type: String, default: "Preview Gambar" },
});
const emit = defineEmits(["close"]);
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show && src"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click.self="emit('close')"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/75 backdrop-blur-sm"
                    @click="emit('close')"
                ></div>

                <!-- Image Container -->
                <div class="relative z-10 max-w-4xl max-h-full">
                    <!-- Close Button -->
                    <button
                        @click="emit('close')"
                        class="absolute -top-3 -right-3 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-red-50 hover:text-red-500 transition z-20"
                        title="Tutup"
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
                            />
                        </svg>
                    </button>

                    <!-- Image -->
                    <img
                        :src="src"
                        :alt="alt"
                        class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain"
                    />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
.modal-fade-enter-active .relative,
.modal-fade-leave-active .relative {
    transition: transform 0.2s ease;
}
.modal-fade-enter-from .relative {
    transform: scale(0.92);
}
.modal-fade-leave-to .relative {
    transform: scale(0.92);
}
</style>
