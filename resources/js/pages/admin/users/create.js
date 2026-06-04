/**
 * Admin user create page controller.
 * Handles validation feedback and user creation through the internal API.
 */

import { apiPost } from '../../../utils/apiClient';
import { showAlert as showSharedAlert } from '../../../utils/toast';

function adminUserCreatePage() {
    return {
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

        init() {},


        async submit() {
            this.submitting = true;
            this.errors = {};

            try {
                const result = await apiPost('/api/admin/users', this.form);

                this.showAlert(result.message || 'User created successfully.', 'success');

                setTimeout(() => {
                    window.location.href = (window.HenanApp?.routes?.adminUsers || '/admin/users');
                }, 700);
            } catch (error) {
                if (error.status === 422) {
                    this.errors = this.mapErrors(error.payload?.errors || {});
                }

                if (!Object.keys(this.errors).length) {
                    this.showAlert(error.message || 'Failed to create user', 'error');
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

window.adminUserCreatePage = adminUserCreatePage;
export default adminUserCreatePage;
