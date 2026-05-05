<aside
    x-data="sidebarNavigation()"
    x-init="init()"
    :class="collapsed ? 'w-[84px]' : 'w-[260px]'"
    class="min-h-screen bg-[#051823] text-white flex flex-col transition-all duration-300 ease-in-out overflow-hidden"
>
    
    <div class="pl-4 pr-4 pt-3 pb-6 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="toggleSidebar()"
                class="shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-md hover:bg-slate-800 transition"
                :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <svg
                    x-show="!collapsed"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    style="display: none;"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>

                <svg
                    x-show="collapsed"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    style="display: none;"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div
                class="flex items-center justify-center overflow-hidden transition-all duration-300"
                :class="collapsed ? 'w-[34px]' : 'w-auto'"
            >
                <img
                    src="<?php echo e(asset('images/logo-henan.png')); ?>"
                    alt="Henan Logo"
                    :class="collapsed ? 'h-[40px] max-w-[40px]' : 'h-[60px] max-w-[180px]'"
                    class="w-auto object-contain transition-all duration-300"
                />
            </div>
        </div>
    </div>

    
    <div class="flex-1 overflow-y-auto">
        
        <template x-if="operationsMenus.length > 0">
            <div class="border-b border-slate-800 p-3">
                <button
                    type="button"
                    x-show="!collapsed"
                    @click="toggleSection('operations')"
                    class="mb-2 flex w-full items-center justify-between rounded px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400 hover:bg-slate-800 transition"
                    style="display: none;"
                >
                    <span>Operations</span>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 transition-transform duration-200"
                        :class="openSections.operations ? 'rotate-180' : ''"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <nav
                    x-show="collapsed || openSections.operations"
                    x-transition
                    class="space-y-1"
                >
                    <template x-for="menu in operationsMenus" :key="menu.key">
                        <a
                            :href="menu.href"
                            class="relative flex items-center rounded transition duration-200"
                            :class="menuClasses(menu)"
                            :title="collapsed ? menu.label : ''"
                        >
                            <span
                                x-show="isActive(menu) && !collapsed"
                                class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-white"
                                style="display: none;"
                            ></span>

                            <img
                                :src="iconUrl(menu.icon)"
                                alt=""
                                class="h-5 w-5 shrink-0 object-contain brightness-0 invert"
                                :class="isActive(menu) ? 'opacity-100' : 'opacity-90'"
                            />

                            <span
                                x-show="!collapsed"
                                x-text="menu.label"
                                class="whitespace-nowrap"
                                style="display: none;"
                            ></span>
                        </a>
                    </template>
                </nav>
            </div>
        </template>

        
        <template x-if="itMonitoringMenus.length > 0">
            <div class="border-b border-slate-800 p-3">
                <button
                    type="button"
                    x-show="!collapsed"
                    @click="toggleSection('it_monitoring')"
                    class="mb-2 flex w-full items-center justify-between rounded px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400 hover:bg-slate-800 transition"
                    style="display: none;"
                >
                    <span>IT Monitoring</span>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 transition-transform duration-200"
                        :class="openSections.it_monitoring ? 'rotate-180' : ''"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <nav
                    x-show="collapsed || openSections.it_monitoring"
                    x-transition
                    class="space-y-1"
                >
                    <template x-for="menu in itMonitoringMenus" :key="menu.key">
                        <a
                            :href="menu.href"
                            class="relative flex items-center rounded transition duration-200"
                            :class="menuClasses(menu)"
                            :title="collapsed ? menu.label : ''"
                        >
                            <span
                                x-show="isActive(menu) && !collapsed"
                                class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-white"
                                style="display: none;"
                            ></span>

                            <img
                                :src="iconUrl(menu.icon)"
                                alt=""
                                class="h-5 w-5 shrink-0 object-contain brightness-0 invert"
                                :class="isActive(menu) ? 'opacity-100' : 'opacity-90'"
                            />

                            <span
                                x-show="!collapsed"
                                x-text="menu.label"
                                class="whitespace-nowrap"
                                style="display: none;"
                            ></span>
                        </a>
                    </template>
                </nav>
            </div>
        </template>

        
        <template x-if="systemControlMenus.length > 0">
            <div class="p-3">
                <button
                    type="button"
                    x-show="!collapsed"
                    @click="toggleSection('system_control')"
                    class="mb-2 flex w-full items-center justify-between rounded px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400 hover:bg-slate-800 transition"
                    style="display: none;"
                >
                    <span>System Control</span>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 transition-transform duration-200"
                        :class="openSections.system_control ? 'rotate-180' : ''"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <nav
                    x-show="collapsed || openSections.system_control"
                    x-transition
                    class="space-y-1"
                >
                    <template x-for="menu in systemControlMenus" :key="menu.key">
                        <a
                            :href="menu.href"
                            class="relative flex items-center rounded transition duration-200"
                            :class="menuClasses(menu)"
                            :title="collapsed ? menu.label : ''"
                        >
                            <span
                                x-show="isActive(menu) && !collapsed"
                                class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-white"
                                style="display: none;"
                            ></span>

                            <img
                                :src="iconUrl(menu.icon)"
                                alt=""
                                class="h-5 w-5 shrink-0 object-contain brightness-0 invert"
                                :class="isActive(menu) ? 'opacity-100' : 'opacity-90'"
                            />

                            <span
                                x-show="!collapsed"
                                x-text="menu.label"
                                class="whitespace-nowrap"
                                style="display: none;"
                            ></span>
                        </a>
                    </template>
                </nav>
            </div>
        </template>
    </div>

    
    <div class="mt-auto border-t border-slate-800 p-4 text-xs text-slate-400">
        <template x-if="!collapsed">
            <div>
                Logged in as:
                <span x-text="user.name || '-'"></span>
                (<span x-text="(user.role || '-').toUpperCase()"></span>)
            </div>
        </template>

        <template x-if="collapsed">
            <div class="text-center" :title="user.name ? `${user.name} (${(user.role || '-').toUpperCase()})` : ''">
                <span x-text="(user.role || '-').toUpperCase()"></span>
            </div>
        </template>
    </div>

    <script>
        function sidebarNavigation() {
            return {
                collapsed: false,
                user: {
                    name: '',
                    role: '',
                },
                menus: [],
                openSections: {
                    operations: true,
                    it_monitoring: false,
                    system_control: false,
                },

                get operationsMenus() {
                    return this.menus.filter(menu => menu.group === 'operations');
                },

                get itMonitoringMenus() {
                    return this.menus.filter(menu => menu.group === 'it_monitoring');
                },

                get systemControlMenus() {
                    return this.menus.filter(menu => menu.group === 'system_control');
                },

                init() {
                    const savedState = localStorage.getItem('sidebar-collapsed');
                    this.collapsed = savedState === '1';

                    const savedSections = localStorage.getItem('sidebar-open-sections');
                    if (savedSections) {
                        try {
                            const parsed = JSON.parse(savedSections);
                            this.openSections = {
                                operations: parsed.operations ?? true,
                                it_monitoring: parsed.it_monitoring ?? false,
                                system_control: parsed.system_control ?? false,
                            };
                        } catch (e) {
                            console.warn('Failed to parse sidebar sections state');
                        }
                    }

                    this.loadNavigation();
                },

                toggleSidebar() {
                    this.collapsed = !this.collapsed;
                    localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0');
                },

                toggleSection(section) {
                    this.openSections[section] = !this.openSections[section];
                    localStorage.setItem('sidebar-open-sections', JSON.stringify(this.openSections));
                },

                async loadNavigation() {
                    try {
                        const response = await fetch('/api/navigation', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load sidebar');
                        }

                        const data = result.data || {};
                        this.user = data.user || this.user;
                        this.menus = data.menus || [];
                    } catch (error) {
                        console.error('Sidebar load failed:', error);
                    }
                },

                normalizePath(path) {
                    if (!path) return '/';

                    let normalized = path.replace(/\/+$/, '');
                    if (normalized === '') normalized = '/';

                    return normalized;
                },

                isActive(menu) {
                    const currentPath = this.normalizePath(window.location.pathname);
                    const menuPath = this.normalizePath(new URL(menu.href, window.location.origin).pathname);

                    switch (menu.key) {
                        case 'dashboard':
                            return currentPath === '/dashboard';

                        case 'new-ticket':
                            return currentPath === '/tickets/create';

                        case 'tickets':
                            return currentPath.startsWith('/tickets') && currentPath !== '/tickets/create';

                        case 'resolver-inbox':
                            return currentPath.startsWith('/resolver-inbox');

                        case 'reports':
                            return currentPath.startsWith('/reports');

                        case 'case-analytics':
                            return currentPath.startsWith('/case-analytics');

                        case 'my-queue':
                            return currentPath.startsWith('/it/my-queue');

                        case 'team-queue':
                            return currentPath.startsWith('/it/team-queue');

                        case 'history':
                            return currentPath.startsWith('/it/history');

                        case 'users':
                            return currentPath.startsWith('/admin/users');

                        case 'master-data':
                            return currentPath.startsWith('/admin/master-data');

                        case 'audit-logs':
                            return currentPath.startsWith('/admin/audit-logs');

                        default:
                            return currentPath === menuPath || currentPath.startsWith(menuPath + '/');
                    }
                },

                menuClasses(menu) {
                    const spacing = this.collapsed
                        ? 'justify-center px-2 py-3'
                        : 'gap-3 px-3 py-2';

                    const state = this.isActive(menu)
                        ? 'bg-slate-800 text-white shadow-sm ring-1 ring-slate-700'
                        : 'text-white/90 hover:bg-slate-800 hover:text-white';

                    return `${spacing} ${state}`;
                },

                iconUrl(iconName) {
                    return `/images/icons/${iconName}.png`;
                }
            }
        }
    </script>
</aside><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/partials/sidebar.blade.php ENDPATH**/ ?>