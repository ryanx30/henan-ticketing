/**
 * Admin user edit page controller.
 * Loads user edit state, handles confirmation flow, and submits updates through the internal API.
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
        confirmation: {
            open: false,
            changes: [],
        },
        form: {
            name: '',
            email: '',
            role: 'cs',
            password: '',
            password_confirmation: '',
            is_active: true,
        },
        originalForm: {},

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
                this.form.password = '';
                this.form.password_confirmation = '';
                this.originalForm = { ...this.form };
            } catch (error) {
                this.showAlert(error.message || 'Failed to load user', 'error');
            } finally {
                this.loading = false;
            }
        },

        roleLabel(role) {
            switch (role) {
                case 'admin':
                    return 'Admin';
                case 'head_cs':
                    return 'Head CS';
                case 'cs':
                    return 'CS';
                case 'it':
                    return 'IT';
                case 'supervisor':
                    return 'Supervisor';
                default:
                    return role || '-';
            }
        },

        normalizeForCompare(value) {
            if (value === null || value === undefined) {
                return '';
            }

            if (typeof value === 'boolean') {
                return value ? '1' : '0';
            }

            return String(value).trim();
        },

        confirmationChanges() {
            const fields = {
                name: 'Full Name',
                email: 'Email',
                role: 'Role',
                is_active: 'Account Status',
            };

            const changes = Object.entries(fields)
                .filter(([key]) => this.normalizeForCompare(this.form[key]) !== this.normalizeForCompare(this.originalForm[key]))
                .map(([key, label]) => ({
                    key,
                    label,
                    before: key === 'role'
                        ? this.roleLabel(this.originalForm[key])
                        : (key === 'is_active' ? (this.originalForm[key] ? 'Active' : 'Inactive') : (this.originalForm[key] || '-')),
                    after: key === 'role'
                        ? this.roleLabel(this.form[key])
                        : (key === 'is_active' ? (this.form[key] ? 'Active' : 'Inactive') : (this.form[key] || '-')),
                }));

            if (String(this.form.password || '').trim()) {
                changes.push({
                    key: 'password',
                    label: 'Password',
                    before: '[hidden]',
                    after: '[updated]',
                });
            }

            return changes;
        },

        openConfirmation() {
            this.confirmation = {
                open: true,
                changes: this.confirmationChanges(),
            };
        },

        closeConfirmation() {
            this.confirmation = {
                open: false,
                changes: [],
            };
        },

        submit() {
            this.errors = {};
            this.openConfirmation();
        },

        async confirmSubmit() {
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
                    this.closeConfirmation();
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
