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

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        async loadUser() {
            this.loading = true;

            try {
                const response = await fetch(`/api/admin/users/${this.userId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load user');
                }

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
                const response = await fetch(`/api/admin/users/${this.userId}`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(this.form),
                });

                const result = await response.json();

                if (response.status === 422) {
                    this.errors = this.mapErrors(result.errors || {});
                    throw new Error(result.message || 'Validation failed');
                }

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to update user');
                }

                this.showAlert(result.message || 'User updated successfully.', 'success');

                setTimeout(() => {
                    window.location.href = (window.HenanApp?.routes?.adminUsers || '/admin/users');
                }, 700);
            } catch (error) {
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
            const el = document.getElementById('page-alert');
            if (!el) return;

            el.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
            el.textContent = message;

            if (type === 'success') {
                el.classList.add('bg-green-100', 'text-green-800');
            } else {
                el.classList.add('bg-red-100', 'text-red-800');
            }

            setTimeout(() => el.classList.add('hidden'), 3000);
        },
    }
}

window.adminUserEditPage = adminUserEditPage;
export default adminUserEditPage;
