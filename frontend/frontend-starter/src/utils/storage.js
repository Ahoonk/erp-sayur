/**
 * Get the backend storage URL for uploaded files.
 * Derives the base URL from the API baseURL (strips /api suffix).
 */
import api from "../api.js";

const base =
    import.meta.env.VITE_STORAGE_URL ||
    api.defaults.baseURL?.replace(/\/api$/, "") ||
    "http://app-kasir.test";

export function storageUrl(path) {
    if (!path) return null;
    // If it already starts with http, return as-is
    if (path.startsWith("http")) return path;
    return `${base}/storage/${path}`;
}
