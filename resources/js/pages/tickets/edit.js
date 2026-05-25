function ticketEditForm(config) {
    return {
        ticketId: config.ticketId,
        loadUrl: config.loadUrl,
        submitUrl: config.submitUrl,
        submitMethod: config.submitMethod,

        loading: true,
        submitting: false,
        optionsLoading: false,
        issueTypesLoading: false,

        ticket_code: '',
        creator_name: '',
        holder_name: '',

        client_name: '',
        client_contact: '',
        client_email: '',

        title: '',
        description: '',
        priority_id: '',
        team_id: '',
        status: 'new',
        category_id: '',
        issue_type_id: '',
        platform_type: '',
        amount: '',
        flow_type: '',
        request_time: '',
        notes: '',

        master: {
            teams: [],
            categories: [],
            priorities: [],
        },
        issueTypes: [],
        similarTickets: [],
        similarLoading: false,

        get sla() {
            const priority = (this.selectedPriority()?.code || '').toLowerCase();

            const map = {
                critical: { response: '1hr', resolve: '2hr' },
                high: { response: '2hr', resolve: '6hr' },
                medium: { response: '4hr', resolve: '12hr' },
                low: { response: '8hr', resolve: '24hr' },
            };

            return map[priority] || { response: '-', resolve: '-' };
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
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

            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async init() {
            await this.loadFormOptions();
            await this.loadTicket();

            let t;
            this.$watch('title', () => {
                clearTimeout(t);
                t = setTimeout(() => this.fetchSimilar(), 300);
            });
        },

        async loadFormOptions() {
            this.optionsLoading = true;

            try {
                const response = await fetch('/api/ticket-form/options', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load master data options');
                }

                this.master.teams = result.data?.teams || [];
                this.master.categories = result.data?.categories || [];
                this.master.priorities = result.data?.priorities || [];
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load master data options', 'error');
            } finally {
                this.optionsLoading = false;
            }
        },

        async loadIssueTypes(categoryId = this.category_id, keepIssueSnapshot = '') {
            this.issueTypes = [];

            if (!categoryId) {
                this.issue_type_id = '';
                return;
            }

            this.issueTypesLoading = true;

            try {
                const params = new URLSearchParams({ category_id: categoryId });
                const response = await fetch(`/api/ticket-form/issue-types?${params.toString()}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load issue types');
                }

                this.issueTypes = result.data || [];

                if (keepIssueSnapshot) {
                    const matchedIssue = this.matchMaster(this.issueTypes, keepIssueSnapshot);
                    this.issue_type_id = matchedIssue ? String(matchedIssue.id) : '';
                }
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load issue types', 'error');
            } finally {
                this.issueTypesLoading = false;
            }
        },

        async onCategoryChange() {
            this.issue_type_id = '';
            await this.loadIssueTypes();
            this.fetchSimilar();
        },

        issueTypePlaceholder() {
            if (this.issueTypesLoading) return 'Loading issue types...';
            if (!this.category_id) return 'Select category first';
            if (this.issueTypes.length === 0) return 'No issue type available';
            return 'Select issue type';
        },

        slugify(value) {
            return String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        },

        normalize(value) {
            return this.slugify(value);
        },

        matchMaster(collection, snapshot) {
            const target = this.normalize(snapshot);
            if (!target) return null;

            return collection.find(item => {
                const candidates = [
                    item.id,
                    item.name,
                    item.code,
                    item.slug,
                    item.code_num,
                    this.slugify(item.name),
                ];

                return candidates.some(value => this.normalize(value) === target);
            }) || null;
        },

        findById(collection, id) {
            return collection.find(item => String(item.id) === String(id)) || null;
        },

        selectedTeam() {
            return this.findById(this.master.teams, this.team_id);
        },

        selectedCategory() {
            return this.findById(this.master.categories, this.category_id);
        },

        selectedIssueType() {
            return this.findById(this.issueTypes, this.issue_type_id);
        },

        selectedPriority() {
            return this.findById(this.master.priorities, this.priority_id);
        },
        ticketLabel(ticket) {
        return window.HenanApp?.ticketLabel(ticket) ?? '-';
    },

        ticketCodeLabel() {
            return this.ticketLabel({ ticket_code: this.ticket_code });
        },


        masterLabel(item) {
            if (!item) return '-';

            // Code number tetap dipakai di belakang layar untuk generate ticket_code,
            // tapi tidak ditampilkan ke CS supaya form lebih mudah dipahami.
            return item.name || item.code || item.slug || '-';
        },

        async loadTicket() {
            this.loading = true;

            try {
                const response = await fetch(this.loadUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load ticket');
                }

                const t = result.data || {};

                this.ticket_code = t.ticket_code || '';
                this.title = t.title || '';
                this.description = t.description || '';
                this.status = t.status || 'new';

                this.client_name = t.client_name || '';
                this.client_contact = t.client_contact || '';
                this.client_email = t.client_email || '';
                this.platform_type = t.platform_type || '';
                this.amount = t.amount || '';
                this.flow_type = t.flow_type || '';
                this.request_time = t.request_time || '';
                this.notes = t.internal_notes || '';

                const matchedPriority = this.matchMaster(this.master.priorities, t.priority || 'medium') || this.master.priorities[0];
                const matchedTeam = this.matchMaster(this.master.teams, t.team || 'it') || this.master.teams[0];
                const matchedCategory = this.matchMaster(this.master.categories, t.category || '');

                this.priority_id = matchedPriority ? String(matchedPriority.id) : '';
                this.team_id = matchedTeam ? String(matchedTeam.id) : '';
                this.category_id = matchedCategory ? String(matchedCategory.id) : '';

                if (this.category_id) {
                    await this.loadIssueTypes(this.category_id, t.issue_type || '');
                }

                this.creator_name = t.creator?.name || '-';
                this.holder_name = t.holder?.name || '-';
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load ticket', 'error');
            } finally {
                this.loading = false;
            }
        },

        async submitForm() {
            if (this.submitting) return;

            this.submitting = true;

            try {
                const response = await fetch(this.submitUrl, {
                    method: this.submitMethod,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        title: this.title,
                        description: this.description,
                        status: this.status,
                        priority_id: this.priority_id,
                        team_id: this.team_id,
                        category_id: this.category_id,
                        issue_type_id: this.issue_type_id,
                    }),
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to update ticket');
                }

                this.showAlert(result.message || 'Ticket updated successfully', 'success');

                setTimeout(() => {
                    window.location.href = '/tickets';
                }, 800);
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to update ticket', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async fetchSimilar() {
            const team = this.selectedTeam();
            const category = this.selectedCategory();

            if (!this.title || !team || !category) {
                this.similarTickets = [];
                return;
            }

            this.similarLoading = true;

            try {
                const params = new URLSearchParams({
                    q: this.title || '',
                    team: team.code || team.name || '',
                    category: category.name || category.slug || '',
                });

                const res = await fetch(`/api/tickets-similar?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const result = await res.json();
                this.similarTickets = result.data || [];
            } catch (e) {
                this.similarTickets = [];
            } finally {
                this.similarLoading = false;
            }
        },
    }
}

window.ticketEditForm = ticketEditForm;
export default ticketEditForm;
