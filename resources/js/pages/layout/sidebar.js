function staticSidebarNavigation() {
    return {
        collapsed: localStorage.getItem('sidebar-collapsed') === '1',
        openSections: {
            operations: true,
            it_monitoring: false,
            system_control: false,
        },

        init() {
            const savedSections = localStorage.getItem('sidebar-open-sections');

            if (!savedSections) {
                return;
            }

            try {
                const parsed = JSON.parse(savedSections);
                this.openSections = {
                    operations: parsed.operations ?? true,
                    it_monitoring: parsed.it_monitoring ?? false,
                    system_control: parsed.system_control ?? false,
                };
            } catch (error) {
                console.warn('Failed to parse sidebar state.');
            }
        },

        toggleSidebar() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0');
        },

        toggleSection(section) {
            this.openSections[section] = !this.openSections[section];
            localStorage.setItem('sidebar-open-sections', JSON.stringify(this.openSections));
        },
    };
}

window.staticSidebarNavigation = staticSidebarNavigation;
export default staticSidebarNavigation;
