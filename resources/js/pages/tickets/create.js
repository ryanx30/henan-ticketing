function createTicketPage() {
    return {
        submitting: false,
        similarLoading: false,
        optionsLoading: false,
        issueTypesLoading: false,
        clientSuggestLoading: false,
        clientSuggestOpen: false,
        clientHistoryLoading: false,
        attachmentName: '',
        similarTickets: [],
        clientSuggestions: [],
        clientHistory: [],
        selectedClient: null,
        issueTypes: [],
        master: {
            teams: [],
            categories: [],
            priorities: [],
        },
        sections: {
            client: true,
            summary: true,
            routing: true,
            details: true,
            notes: true,
        },

        form: {
            client_id: '',
            client_name: '',
            client_contact: '',
            client_email: '',
            title: '',
            description: '',
            priority_id: '',
            team_id: '',
            category_id: '',
            issue_type_id: '',
            platform_type: '',
            amount: '',
            flow_type: '',
            request_time: '',
            internal_notes: '',
            attachment: null,
        },

        async init() {
            await this.loadFormOptions();
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

            setTimeout(() => {
                el.classList.add('hidden');
            }, 3000);
        },

        clientSearchQuery() {
            return [
                this.form.client_name,
                this.form.client_email,
                this.form.client_contact,
            ].find(value => String(value || '').trim().length >= 2) || '';
        },

        async loadClientSuggestions() {
            const query = String(this.clientSearchQuery() || '').trim();

            if (query.length < 2) {
                this.clientSuggestions = [];
                this.clientSuggestOpen = false;
                return;
            }

            this.clientSuggestLoading = true;
            this.clientSuggestOpen = true;

            try {
                const params = new URLSearchParams({ q: query });
                const response = await fetch(`/api/clients/suggest?${params.toString()}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load client suggestions.');
                }

                this.clientSuggestions = result.data || [];
            } catch (error) {
                console.error(error);
                this.clientSuggestions = [];
            } finally {
                this.clientSuggestLoading = false;
            }
        },

        selectClientSuggestion(client) {
            this.selectedClient = client;
            this.form.client_id = client.id ? String(client.id) : '';
            this.form.client_name = client.name || '';
            this.form.client_contact = client.contact || '';
            this.form.client_email = client.email || '';
            this.clientSuggestOpen = false;
            this.loadClientHistory(client.id);
        },

        clearSelectedClient() {
            this.selectedClient = null;
            this.form.client_id = '';
            this.clientHistory = [];
        },

        async loadClientHistory(clientId) {
            if (!clientId) {
                this.clientHistory = [];
                return;
            }

            this.clientHistoryLoading = true;

            try {
                const response = await fetch(`/api/clients/${clientId}/history`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load client history.');
                }

                this.selectedClient = result.data?.client || this.selectedClient;
                this.clientHistory = result.data?.tickets || [];
            } catch (error) {
                console.error(error);
                this.clientHistory = [];
            } finally {
                this.clientHistoryLoading = false;
            }
        },

        ticketLabel(ticket) {
            return window.HenanApp?.ticketLabel(ticket) ?? '-';
        },

        formatDate(value) {
            if (!value) return '-';

            return new Date(value).toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
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
                    throw new Error(result.message || 'Failed to load ticket form options.');
                }

                this.master.teams = result.data?.teams || [];
                this.master.categories = result.data?.categories || [];
                this.master.priorities = result.data?.priorities || [];

                const defaultTeam = this.master.teams.find(item => (item.code || '').toLowerCase() === 'it') || this.master.teams[0];
                const defaultPriority = this.master.priorities.find(item => (item.code || '').toLowerCase() === 'medium') || this.master.priorities[0];

                this.form.team_id = defaultTeam ? String(defaultTeam.id) : '';
                this.form.priority_id = defaultPriority ? String(defaultPriority.id) : '';
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load ticket form options.', 'error');
            } finally {
                this.optionsLoading = false;
            }
        },

        async loadIssueTypes() {
            this.issueTypes = [];
            this.form.issue_type_id = '';

            if (!this.form.category_id) return;

            this.issueTypesLoading = true;

            try {
                const params = new URLSearchParams({ category_id: this.form.category_id });
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
                    throw new Error(result.message || 'Failed to load issue types.');
                }

                this.issueTypes = result.data || [];
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load issue types.', 'error');
            } finally {
                this.issueTypesLoading = false;
            }
        },

        async onCategoryChange() {
            await this.loadIssueTypes();
            this.loadSimilarTickets();
        },

        issueTypePlaceholder() {
            if (this.issueTypesLoading) return 'Loading issue types...';
            if (!this.form.category_id) return 'Select category first';
            if (this.issueTypes.length === 0) return 'No issue type available';
            return 'Select issue type';
        },

        findById(collection, id) {
            return collection.find(item => String(item.id) === String(id)) || null;
        },

        selectedTeam() {
            return this.findById(this.master.teams, this.form.team_id);
        },

        selectedCategory() {
            return this.findById(this.master.categories, this.form.category_id);
        },

        selectedIssueType() {
            return this.findById(this.issueTypes, this.form.issue_type_id);
        },

        selectedPriority() {
            return this.findById(this.master.priorities, this.form.priority_id);
        },

        masterLabel(item) {
            if (!item) return '-';

            // Code number tetap dipakai di belakang layar untuk generate ticket_code,
            // tapi tidak ditampilkan ke CS supaya form lebih mudah dipahami.
            return item.name || item.code || item.slug || '-';
        },

        slugify(value) {
            return String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        },

        selectedCategoryKey() {
            const category = this.selectedCategory();
            return this.slugify(category?.slug || category?.name || '');
        },

        showField(field) {
            const categoryKey = this.selectedCategoryKey();

            if (categoryKey.includes('finance') || categoryKey.includes('fund')) {
                return ['amount', 'flow_type', 'request_time'].includes(field);
            }

            if (categoryKey.includes('request')) {
                return ['flow_type', 'request_time'].includes(field);
            }

            return field === 'request_time';
        },

        routeToLabel() {
            const team = this.selectedTeam();
            return team ? (team.name || team.code || '-').toUpperCase() : '-';
        },

        previewCategoryLabel() {
            const category = this.selectedCategory();
            return category ? this.masterLabel(category) : '-';
        },

        previewIssueTypeLabel() {
            const issueType = this.selectedIssueType();
            return issueType ? this.masterLabel(issueType) : '-';
        },

        codePart(value, length) {
            if (value === undefined || value === null || value === '') return '?'.repeat(length);
            return String(value).padStart(length, '0');
        },

        ticketCodePreview() {
            const team = this.selectedTeam();
            const category = this.selectedCategory();
            const issueType = this.selectedIssueType();
            const priority = this.selectedPriority();

            return [
                this.codePart(team?.code_num, 1),
                this.codePart(category?.code_num, 2),
                this.codePart(issueType?.code_num, 3),
                this.codePart(priority?.code_num, 1),
                'xxxxx',
            ].join('');
        },

        slaPreview() {
            const priority = (this.selectedPriority()?.code || '').toLowerCase();

            const map = {
                critical: { response: '30m', resolve: '2hr' },
                high: { response: '1hr', resolve: '6hr' },
                medium: { response: '4hr', resolve: '12hr' },
                low: { response: '8hr', resolve: '24hr' },
            };

            return map[priority] || { response: '-', resolve: '-' };
        },

        requiredFieldMap() {
            return [
                { key: 'client_name', label: 'Client Name', selector: 'field-client_name', filled: !!this.form.client_name },
                { key: 'client_contact', label: 'Client Contact', selector: 'field-client_contact', filled: !!this.form.client_contact },
                { key: 'client_email', label: 'Client Email', selector: 'field-client_email', filled: !!this.form.client_email },
                { key: 'title', label: 'Title', selector: 'field-title', filled: !!this.form.title },
                { key: 'description', label: 'Description', selector: 'field-description', filled: !!this.form.description },
                { key: 'priority_id', label: 'Priority', selector: 'field-priority', filled: !!this.form.priority_id },
                { key: 'team_id', label: 'Owner Team', selector: 'field-team', filled: !!this.form.team_id },
                { key: 'category_id', label: 'Category', selector: 'field-category', filled: !!this.form.category_id },
                { key: 'issue_type_id', label: 'Issue Type', selector: 'field-issue_type', filled: !!this.form.issue_type_id },
                { key: 'platform_type', label: 'Platform', selector: 'field-platform', filled: !!this.form.platform_type },
            ];
        },

        missingFields() {
            return this.requiredFieldMap().filter(item => !item.filled);
        },

        isFormReady() {
            return this.missingFields().length === 0 && !this.optionsLoading && !this.issueTypesLoading;
        },

        scrollToField(key) {
            const item = this.requiredFieldMap().find(entry => entry.key === key);
            if (!item) return;

            const target = document.getElementById(item.selector);
            if (!target) return;

            target.scrollIntoView({ behavior: 'smooth', block: 'center' });

            target.classList.add('ring-2', 'ring-[#2f88d8]', 'rounded');
            setTimeout(() => {
                target.classList.remove('ring-2', 'ring-[#2f88d8]', 'rounded');
            }, 1500);
        },

        continueAnyway() {
            this.showAlert('Continuing ticket creation. Please make sure this is not a duplicate.', 'success');
        },

        onAttachmentChange(event) {
            const file = event.target.files?.[0] || null;
            this.form.attachment = file;
            this.attachmentName = file ? file.name : '';
        },

        submitDisabled() {
            return !this.isFormReady();
        },

        async loadSimilarTickets() {
            const team = this.selectedTeam();
            const category = this.selectedCategory();

            if (!this.form.title || !team || !category) {
                this.similarTickets = [];
                return;
            }

            this.similarLoading = true;

            try {
                const params = new URLSearchParams({
                    q: this.form.title,
                    team: team.code || team.name || '',
                    category: category.name || category.slug || '',
                });

                const response = await fetch(`/api/tickets-similar?${params.toString()}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load similar tickets.');
                }

                this.similarTickets = result.data || [];
            } catch (error) {
                console.error(error);
                this.similarTickets = [];
            } finally {
                this.similarLoading = false;
            }
        },

        async submitTicket() {
            if (this.submitDisabled() || this.submitting) return;

            this.submitting = true;

            try {
                const formData = new FormData();

                formData.append('title', this.form.title || '');
                formData.append('client_id', this.form.client_id || '');
                formData.append('description', this.form.description || '');
                formData.append('priority_id', this.form.priority_id || '');
                formData.append('team_id', this.form.team_id || '');
                formData.append('category_id', this.form.category_id || '');
                formData.append('issue_type_id', this.form.issue_type_id || '');

                formData.append('client_name', this.form.client_name || '');
                formData.append('client_contact', this.form.client_contact || '');
                formData.append('client_email', this.form.client_email || '');
                formData.append('platform_type', this.form.platform_type || '');
                formData.append('amount', this.form.amount || '');
                formData.append('flow_type', this.form.flow_type || '');
                formData.append('request_time', this.form.request_time || '');
                formData.append('internal_notes', this.form.internal_notes || '');

                if (this.form.attachment) {
                    formData.append('attachment', this.form.attachment);
                }

                const response = await fetch('/api/tickets', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: formData,
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to create ticket.');
                }

                this.showAlert('Ticket created successfully.', 'success');

                setTimeout(() => {
                    if (result.data?.id) {
                        window.location.href = `/tickets/${result.data.id}`;
                    } else {
                        window.location.href = '/tickets';
                    }
                }, 700);
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to create ticket.', 'error');
            } finally {
                this.submitting = false;
            }
        },

        saveDraft() {
            this.showAlert('Draft flow is not connected to backend yet.', 'error');
        },
    }
}

window.createTicketPage = createTicketPage;
export default createTicketPage;
