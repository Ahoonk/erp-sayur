import { defineStore } from "pinia";
import api from "../api";

export const useAuthStore = defineStore("auth", {
    state: () => ({
        user: null,
        token: sessionStorage.getItem("token") || null,
        loading: false,
        error: null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => {
            if (!state.user?.roles) return false;
            return state.user.roles.some(
                (role) =>
                    role.name === "admin" ||
                    role.name === "super-admin" ||
                    role.name === "owner",
            );
        },
        isSuperAdmin: (state) => {
            if (!state.user?.roles) return false;
            return state.user.roles.some((role) => role.name === "super-admin");
        },
        isOwner: (state) => {
            if (!state.user?.roles) return false;
            return state.user.roles.some((role) => role.name === "owner");
        },
        isKasir: (state) => {
            if (!state.user?.roles) return false;
            return state.user.roles.some((role) => role.name === "kasir");
        },
        // Permission checks - simplified for now, show menu for kasir role
        canViewSales: (state) => {
            // If user has isKasir role, return true (show menu)
            if (state.user?.roles?.some(role => role.name === 'kasir')) {
                return true;
            }
            // Otherwise check for admin roles
            if (state.user?.roles?.some(role => 
                role.name === 'admin' || role.name === 'super-admin' || role.name === 'owner'
            )) {
                return true;
            }
            return false;
        },
        canViewServices: (state) => {
            // If user has isKasir role, return true (show menu)
            if (state.user?.roles?.some(role => role.name === 'kasir')) {
                return true;
            }
            // Otherwise check for admin roles
            if (state.user?.roles?.some(role => 
                role.name === 'admin' || role.name === 'super-admin' || role.name === 'owner'
            )) {
                return true;
            }
            return false;
        },
        canViewReports: (state) => {
            // If user has admin roles, return true
            if (state.user?.roles?.some(role => 
                role.name === 'admin' || role.name === 'super-admin' || role.name === 'owner'
            )) {
                return true;
            }
            return false;
        },
        canViewPurchases: (state) => {
            // If user has kasir role, allow stock pages
            if (state.user?.roles?.some(role => role.name === 'kasir')) {
                return true;
            }
            // If user has admin roles, return true
            if (state.user?.roles?.some(role => 
                role.name === 'admin' || role.name === 'super-admin' || role.name === 'owner'
            )) {
                return true;
            }
            return false;
        },
        canViewMasterData: (state) => {
            if (state.user?.roles?.some(role => role.name === 'kasir')) {
                return true;
            }
            if (state.user?.roles?.some(role =>
                role.name === 'admin' || role.name === 'super-admin' || role.name === 'owner'
            )) {
                return true;
            }
            return false;
        },
    },
    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.post("/login", credentials);
                this.token = response.data.data.access_token;
                this.user = response.data.data.user;

                sessionStorage.setItem("token", this.token);

                // Fetch full user profile
                await this.fetchUser();

                return true;
            } catch (err) {
                this.error = err.response?.data?.message || "Login failed";
                return false;
            } finally {
                this.loading = false;
            }
        },

        async register(userData) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.post("/register", userData);
                this.token = response.data.data.access_token;
                this.user = response.data.data.user;

                sessionStorage.setItem("token", this.token);
                await this.fetchUser();

                return true;
            } catch (err) {
                this.error =
                    err.response?.data?.message || "Registration failed";
                return false;
            } finally {
                this.loading = false;
            }
        },

        async fetchUser() {
            if (!this.token) return;
            try {
                const response = await api.get("/me");
                this.user = response.data.data;
            } catch (err) {
                console.error("Fetch user failed", err);
                this.logout();
            }
        },

        async logout() {
            try {
                // Try API logout but clear state regardless
                if (this.token) {
                    await api.post("/logout");
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.user = null;
                this.token = null;
                this.error = null;
                sessionStorage.removeItem("token");
            }
        },

        async updateProfile(formData) {
            this.loading = true;
            try {
                const response = await api.post("/profile", formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                });

                // Update local user state
                this.user = {
                    ...this.user,
                    profile: response.data.data,
                };

                // Refresh user data to be safe
                await this.fetchUser();

                return { success: true, message: response.data.message };
            } catch (err) {
                return {
                    success: false,
                    message: err.response?.data?.message || "Update failed",
                    errors: err.response?.data?.errors,
                };
            } finally {
                this.loading = false;
            }
        },

        async changePassword(data) {
            this.loading = true;
            try {
                const response = await api.post("/change-password", data);
                return { success: true, message: response.data.message };
            } catch (err) {
                return {
                    success: false,
                    message:
                        err.response?.data?.message || "Password change failed",
                    errors: err.response?.data?.errors,
                };
            } finally {
                this.loading = false;
            }
        },
    },
});
