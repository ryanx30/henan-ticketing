import './bootstrap';
import './henan-helpers';
import './henan-export-queue';
import './sla-countdown';
import './pages/layout/sidebar';
import './pages/layout/notifications';

import './pages/tickets/index';
import './pages/tickets/detail';
import './pages/reports/index';
import './pages/it/history';
import './pages/it/dashboard-it';
import './pages/dashboard/cs';
import './pages/case-analytics/index';
import './pages/resolver-inbox/index';
import './pages/admin/master-data';
import './pages/admin/users/edit';
import './pages/admin/users/create';
import './pages/admin/users/index';
import './pages/admin/audit-logs';
import './pages/resolver-inbox/show';
import './pages/it/team-queue';
import './pages/it/my-queue';
import './pages/tickets/edit';
import './pages/tickets/create';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
