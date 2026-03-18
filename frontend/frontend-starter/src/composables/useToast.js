import { ref } from "vue";

const toasts = ref([]);
let nextId = 0;

export function useToast() {
    function addToast(message, type = "success", duration = 3000) {
        const id = nextId++;
        toasts.value.push({ id, message, type, visible: true });
        setTimeout(() => {
            const idx = toasts.value.findIndex((t) => t.id === id);
            if (idx !== -1) toasts.value.splice(idx, 1);
        }, duration);
    }

    return {
        toasts,
        success: (msg) => addToast(msg, "success"),
        error: (msg) => addToast(msg, "error", 5000),
        warning: (msg) => addToast(msg, "warning", 4000),
        info: (msg) => addToast(msg, "info"),
    };
}
