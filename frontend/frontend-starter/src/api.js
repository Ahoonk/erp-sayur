import axios from "axios";

const apiBaseUrl = import.meta.env.VITE_API_URL || "http://app-kasir.test/api";

const api = axios.create({
    baseURL: apiBaseUrl,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    withCredentials: true,
});

// Request interceptor
api.interceptors.request.use((config) => {
    // Add auth token
    const token = sessionStorage.getItem("token");
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    // PENTING: Jika data adalah FormData, hapus Content-Type agar
    // browser otomatis generate header multipart/form-data + boundary yang benar.
    // Jika Content-Type diset manual, server tidak bisa parse file upload.
    if (config.data instanceof FormData) {
        delete config.headers["Content-Type"];
    }

    return config;
});

export default api;
