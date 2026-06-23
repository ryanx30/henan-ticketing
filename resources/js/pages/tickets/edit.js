/**
 * Ticket edit/open page controller.
 * Loads editable ticket data, renders detail context, and sends update actions through the API client.
 */

import { apiGet, apiRequest } from '../../utils/apiClient';
import { ticketLabel as sharedTicketLabel } from '../../utils/badges';
import { showAlert as showSharedAlert } from '../../utils/toast';

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
            slaRules: [],
        },
        issueTypes: [],
        similarTickets: [],
        similarLoading: false,

        get sla() {
            const team = this.selectedTeam();
            const priority = this.selectedPriority();

            if (!team || !priority) {
                return { rule: 'Select team and priority', resolve: '-' };
            }

            if (!this.isSelectedTeamIt()) {
                return {
                    rule: `${this.masterLabel(team)} / ${this.masterLabel(priority)}`,
                    resolve: 'No SLA - direct closed',
                };
            }

            const rule = this.selectedSlaRule();
            const hours = rule?.hours ?? this.fallbackSlaHoursByPriority(priority.code);

            return {
                rule: rule ? 'Master Data SLA Rule' : 'Default fallback rule',
                resolve: this.formatHours(hours),
            };
        },

        normalizeCode(value) {
            return String(value || '').trim().toLowerCase();
        },

        isSelectedTeamIt() {
            const team = this.selectedTeam();

            return this.normalizeCode(team?.code || team?.name) === 'it';
        },

        formatHours(hours) {
            const numericHours = Number(hours);

            if (!Number.isFinite(numericHours) || numericHours <= 0) {
                return '-';
            }

            if (numericHours < 1) {
                return `${Math.round(numericHours * 60)}m`;
            }

            return `${numericHours}h`;
        },

        fallbackSlaHoursByPriority(priorityCode) {
            const map = {
                critical: 2,
                high: 6,
                medium: 12,
                low: 24,
            };

            return map[this.normalizeCode(priorityCode)] || null;
        },

        selectedSlaRule() {
            return this.master.slaRules.find(rule => (
                String(rule.team_id) === String(this.team_id)
                && String(rule.priority_id) === String(this.priority_id)
            )) || null;
        },


        showAlert(message, type = 'success') {
            showSharedAlert(message, type);
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
                const result = await apiGet('/api/ticket-form/options');

                this.master.teams = result.data?.teams || [];
                this.master.categories = result.data?.categories || [];
                this.master.priorities = result.data?.priorities || [];
                this.master.slaRules = result.data?.sla_rules || [];
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
                const result = await apiGet(`/api/ticket-form/issue-types?${params.toString()}`);

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


        normalizeTicketPayload(payload) {
            const code = payload.code || {};
            const classification = payload.classification || {};
            const assignment = payload.assignment || {};
            const timestamps = payload.timestamps || {};
            const client = payload.client || null;
            const status = payload.status || {};
            const priority = classification.priority || {};
            const team = classification.team || {};
            const category = classification.category || {};
            const issueType = classification.issue_type || {};

            return {
                ...payload,
                ticket_code: code.raw || payload.ticket_code || '',
                status: status.code || payload.status || 'new',
                priority: priority.code || priority.name || payload.priority || 'medium',
                team: team.code || team.name || payload.team || 'it',
                category: category.name || category.slug || payload.category || '',
                issue_type: issueType.name || issueType.slug || payload.issue_type || '',
                client_name: client?.name || payload.client_name || '',
                client_contact: client?.contact || payload.client_contact || '',
                client_email: client?.email || payload.client_email || '',
                platform_type: payload.platform_type || '',
                amount: payload.amount || '',
                flow_type: payload.flow_type || '',
                request_time: timestamps.request_time || payload.request_time || '',
                internal_notes: payload.internal_notes || '',
                creator: assignment.creator || payload.creator || null,
                holder: assignment.holder || payload.holder || null,
            };
        },
        ticketLabel(ticket) {
            return sharedTicketLabel(ticket);
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
                const result = await apiGet(this.loadUrl);

                const t = this.normalizeTicketPayload(result.data || {});

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
                const result = await apiRequest(this.submitUrl, {
                    method: this.submitMethod,
                    body: {
                        title: this.title,
                        description: this.description,
                        status: this.status,
                        priority_id: this.priority_id,
                        team_id: this.team_id,
                        category_id: this.category_id,
                        issue_type_id: this.issue_type_id,
                    },
                });

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

                const result = await apiGet(`/api/tickets-similar?${params.toString()}`);
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
