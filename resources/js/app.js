import './bootstrap';
import * as coreui from '@coreui/coreui';

window.coreui = coreui;

const initializeSidebarTogglers = () => {
    document.querySelectorAll('[data-coreui-toggle="sidebar"][data-coreui-target]').forEach((toggler) => {
        const selector = toggler.getAttribute('data-coreui-target');
        const sidebar = selector ? document.querySelector(selector) : null;

        if (!sidebar) {
            return;
        }

        toggler.addEventListener('click', (event) => {
            event.preventDefault();

            coreui.Sidebar.getOrCreateInstance(sidebar).toggle();
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSidebarTogglers);
} else {
    initializeSidebarTogglers();
}
