/**
 * Admin user create page controller.
 * Handles validation feedback, confirmation flow, and user creation through the internal API.
 */

import { apiPost } from '../../../utils/apiClient';
import { showAlert as showSharedAlert } from '../../../utils/toast';

function adminUserCreatePage() {
    return {
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

        init() {},

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

        confirmationChanges() {
            return [{
                    key: 'name',
                    label: 'Full Name',
                    before: '-',
                    after: this.form.name || '-',
                },
                {
                    key: 'email',
                    label: 'Email',
                    before: '-',
                    after: this.form.email || '-',
                },
                {
                    key: 'role',
                    label: 'Role',
                    before: '-',
                    after: this.roleLabel(this.form.role),
                },
                {
                    key: 'is_active',
                    label: 'Account Status',
                    before: '-',
                    after: this.form.is_active ? 'Active' : 'Inactive',
                },
                {
                    key: 'password',
                    label: 'Password',
                    before: '-',
                    after: this.form.password ? '[hidden]' : '-',
                },
            ].filter(change => change.after !== '-');
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
                const result = await apiPost('/api/admin/users', this.form);

                this.showAlert(result.message || 'User created successfully.', 'success');

                setTimeout(() => {
                    window.location.href = (window.HenanApp?.routes?.adminUsers || '/admin/users');
                }, 700);
            } catch (error) {
                if (error.status === 422) {
                    this.errors = this.mapErrors(error.payload?.errors || {});
                    this.closeConfirmation();
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
