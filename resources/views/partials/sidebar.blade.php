<aside
    x-data="sidebarNavigation()"
    x-init="init()"
    :class="collapsed ? 'w-[84px]' : 'w-[260px]'"
    class="min-h-screen bg-[#051823] text-white flex flex-col transition-all duration-300 ease-in-out overflow-hidden"
>
    {{-- Header / Logo --}}
    <div class="pl-4 pr-4 pt-3 pb-6 border-b border-slate-800">
        <div class="flex items-center gap-3">
            {{-- Toggle button on the left side of logo --}}
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

            {{-- Logo --}}
            <div class="flex items-center justify-center overflow-hidden transition-all duration-300"
                 :class="collapsed ? 'w-[34px]' : 'w-auto'">
                <img
                    src="{{ asset('images/logo-henan.png') }}"
                    alt="Henan Logo"
                    :class="collapsed ? 'h-[34px] max-w-[34px]' : 'h-[55px] max-w-[170px]'"
                    class="w-auto object-contain transition-all duration-300"
                />
            </div>
        </div>
    </div>

    {{-- TOP MENU --}}
    <nav class="p-3 space-y-1 border-b border-slate-800">
        <template x-for="menu in topMenus" :key="menu.key">
            <a
                :href="menu.href"
                class="flex items-center rounded hover:bg-slate-800 transition"
                :class="collapsed ? 'justify-center px-2 py-3' : 'gap-3 px-3 py-2'"
                :title="collapsed ? menu.label : ''"
            >
                <img
                    :src="iconUrl(menu.icon)"
                    alt=""
                    class="h-5 w-5 shrink-0 object-contain brightness-0 invert"
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

    {{-- SECOND MENU --}}
    <nav class="p-3 space-y-1">
        <template x-for="menu in bottomMenus" :key="menu.key">
            <a
                :href="menu.href"
                class="flex items-center rounded hover:bg-slate-800 transition"
                :class="[
                    collapsed ? 'justify-center px-2 py-3' : 'gap-3 px-3 py-2',
                    menu.active ? 'bg-slate-800' : ''
                ]"
                :title="collapsed ? menu.label : ''"
            >
                <img
                    :src="iconUrl(menu.icon)"
                    alt=""
                    class="h-5 w-5 shrink-0 object-contain brightness-0 invert"
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

    {{-- Footer --}}
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

                get topMenus() {
                    return this.menus.filter(menu => menu.section === 'top');
                },

                get bottomMenus() {
                    return this.menus.filter(menu => menu.section === 'bottom');
                },

                init() {
                    const savedState = localStorage.getItem('sidebar-collapsed');
                    this.collapsed = savedState === '1';
                    this.loadNavigation();
                },

                toggleSidebar() {
                    this.collapsed = !this.collapsed;
                    localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0');
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

                iconUrl(iconName) {
                    return `/images/icons/${iconName}.png`;
                }
            }
        }
    </script>
</aside>