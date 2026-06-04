/**
 * Admin user edit page controller.
 * Loads user edit state and submits updates through the internal API.
 */

import { apiGet, apiPatch } from '../../../utils/apiClient';
import { showAlert as showSharedAlert } from '../../../utils/toast';

function adminUserEditPage({ userId }) {
    return {
        userId,
        loading: false,
        submitting: false,
        errors: {},
        showPassword: false,
        showPasswordConfirm: false,
        form: {
            name: '',
            email: '',
            role: 'cs',
            password: '',
            password_confirmation: '',
            is_active: true,
        },

        init() {
            this.loadUser();
        },


        async loadUser() {
            this.loading = true;

            try {
                const result = await apiGet(`/api/admin/users/${this.userId}`);

                const data = result.data || {};
                this.form.name = data.name || '';
                this.form.email = data.email || '';
                this.form.role = data.role || 'cs';
                this.form.is_active = !!data.is_active;
            } catch (error) {
                this.showAlert(error.message || 'Failed to load user', 'error');
            } finally {
                this.loading = false;
            }
        },

        async submit() {
            this.submitting = true;
            this.errors = {};

            try {
                const result = await apiPatch(`/api/admin/users/${this.userId}`, this.form);

                this.showAlert(result.message || 'User updated successfully.', 'success');

                setTimeout(() => {
                    window.location.href = (window.HenanApp?.routes?.adminUsers || '/admin/users');
                }, 700);
            } catch (error) {
                if (error.status === 422) {
                    this.errors = this.mapErrors(error.payload?.errors || {});
                }

                if (!Object.keys(this.errors).length) {
                    this.showAlert(error.message || 'Failed to update user', 'error');
                }
            } finally {
                this.submitting = false;
            }
        },

        mapErrors(errors) {
            const mapped = {};
            Object.keys(errors).forEach(key => {
                mapped[key] = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            });
            return mapped;
        },

        showAlert(message, type = 'success') {
            showSharedAlert(message, type);
        },
    }
}

window.adminUserEditPage = adminUserEditPage;
export default adminUserEditPage;
