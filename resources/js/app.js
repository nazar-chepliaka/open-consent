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

const initializeAiConnectionTests = () => {
    document.querySelectorAll('[data-ai-test-form]').forEach((form) => {
        const button = form.querySelector('[data-ai-test-button]');
        const label = button?.querySelector('[data-ai-test-label]');
        const inlineStatus = form.querySelector('[data-ai-test-status]');
        const statusCell = form.closest('tr')?.querySelector('[data-ai-test-status-cell]');
        const url = form.getAttribute('data-ai-test-url');

        if (!button || !label || (!inlineStatus && !statusCell) || !url) {
            return;
        }

        const defaultLabel = label.textContent;
        const renderConnectionStatus = (state, testedAt) => {
            if (!statusCell) {
                return;
            }

            const status = document.createElement('span');
            status.className = state === 'success' ? 'text-success' : 'text-danger';
            status.textContent = state === 'success' ? 'Підключення перевірено' : 'Перевірка не вдалася';

            if (typeof testedAt !== 'string' || testedAt === '') {
                statusCell.replaceChildren(status);
                return;
            }

            const testedAtElement = document.createElement('div');
            testedAtElement.className = 'small text-body-secondary';
            testedAtElement.textContent = testedAt;

            statusCell.replaceChildren(status, testedAtElement);
        };

        const clearSuccessfulState = () => {
            if (!inlineStatus || inlineStatus.dataset.aiTestState !== 'success') {
                return;
            }

            inlineStatus.textContent = '';
            inlineStatus.className = 'small';
            delete inlineStatus.dataset.aiTestState;
        };

        form.querySelectorAll('[data-ai-test-watch]').forEach((field) => {
            field.addEventListener('input', clearSuccessfulState);
            field.addEventListener('change', clearSuccessfulState);
        });

        button.addEventListener('click', async () => {
            if (form.dataset.aiTestPending === 'true') {
                return;
            }

            form.dataset.aiTestPending = 'true';
            button.disabled = true;
            label.textContent = 'Перевіряємо…';

            if (inlineStatus) {
                inlineStatus.textContent = 'Перевіряємо...';
                inlineStatus.className = 'small text-body-secondary';
                delete inlineStatus.dataset.aiTestState;
            }

            const formData = new FormData(form);
            formData.delete('_method');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });
                const data = await response.json().catch(() => ({}));
                const message = typeof data.message === 'string' && data.message !== ''
                    ? data.message
                    : 'Не вдалося підключитися до провайдера.';

                if (response.ok && data.successful === true) {
                    if (statusCell) {
                        renderConnectionStatus('success', data.last_tested_at);
                    }

                    if (inlineStatus) {
                        inlineStatus.textContent = `✓ ${message}`;
                        inlineStatus.className = 'small text-success';
                        inlineStatus.dataset.aiTestState = 'success';
                    }
                } else {
                    if (statusCell) {
                        renderConnectionStatus('failed', data.last_tested_at);
                    }

                    if (inlineStatus) {
                        inlineStatus.textContent = message;
                        inlineStatus.className = 'small text-danger';
                        inlineStatus.dataset.aiTestState = 'failure';
                    }
                }
            } catch (error) {
                if (inlineStatus) {
                    inlineStatus.textContent = 'Не вдалося підключитися до провайдера.';
                    inlineStatus.className = 'small text-danger';
                    inlineStatus.dataset.aiTestState = 'failure';
                }
            } finally {
                delete form.dataset.aiTestPending;
                button.disabled = false;
                label.textContent = defaultLabel;
            }
        });
    });
};

const initializeAiProviderHelp = () => {
    document.querySelectorAll('[data-ai-provider-help]').forEach((help) => {
        const form = help.closest('form');
        const select = form?.querySelector('[data-ai-provider-select]');

        if (!form || !select) {
            return;
        }

        let metadata = {};

        try {
            metadata = JSON.parse(help.getAttribute('data-ai-provider-help-options') || '{}');
        } catch (error) {
            metadata = {};
        }

        const render = () => {
            const provider = metadata[select.value] || {};
            const displayName = typeof provider.displayName === 'string' ? provider.displayName : '';
            const helpUrl = typeof provider.credentialsHelpUrl === 'string' ? provider.credentialsHelpUrl : '';
            const helpLabel = typeof provider.credentialsHelpLabel === 'string' ? provider.credentialsHelpLabel : '';

            if (displayName === '' || helpUrl === '' || helpLabel === '') {
                help.hidden = true;
                help.replaceChildren();
                return;
            }

            const providerLine = document.createElement('div');
            providerLine.textContent = `Для ${displayName}:`;

            const link = document.createElement('a');
            link.href = helpUrl;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.className = 'd-inline-flex align-items-center gap-1';

            const icon = document.createElement('i');
            icon.className = 'cil-external-link';
            icon.setAttribute('aria-hidden', 'true');

            const label = document.createElement('span');
            label.textContent = helpLabel;

            link.append(icon, label);
            help.replaceChildren(providerLine, link);
            help.hidden = false;
        };

        select.addEventListener('change', render);
        render();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeSidebarTogglers();
        initializeAiConnectionTests();
        initializeAiProviderHelp();
    });
} else {
    initializeSidebarTogglers();
    initializeAiConnectionTests();
    initializeAiProviderHelp();
}
