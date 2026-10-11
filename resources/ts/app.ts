import { api, type AppState, type LoginResponse } from './api';
import { $, $$, setText } from './dom';
import { loadDashboard } from './dashboard';
import { bindErp, loadErpModule } from './erp';

const state: AppState = {
    token: localStorage.getItem('frotacerta.token') || '',
    user: JSON.parse(localStorage.getItem('frotacerta.user') || 'null'),
    erpModule: 'vehicles',
    erpRecords: [],
    erpEditingId: null,
};

const setSession = (login: LoginResponse): void => {
    state.token = login.access_token;
    state.user = login.user;
    localStorage.setItem('frotacerta.token', state.token);
    localStorage.setItem('frotacerta.user', JSON.stringify(state.user));
};

const clearSession = (): void => {
    state.token = '';
    state.user = null;
    localStorage.removeItem('frotacerta.token');
    localStorage.removeItem('frotacerta.user');
};

const showApp = (authenticated: boolean): void => {
    $('[data-login-view]')?.classList.toggle('is-hidden', authenticated);
    $('[data-dashboard-view]')?.classList.toggle('is-hidden', !authenticated);
    setText('[data-user-name]', state.user?.name || '');
};

const bindEvents = (): void => {
    $('[data-login-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget as HTMLFormElement);
        setText('[data-login-message]', 'Entrando...');

        try {
            const login = await api<LoginResponse>(state, '/api/v1/auth/login', {
                method: 'POST',
                body: JSON.stringify({
                    email: form.get('email'),
                    password: form.get('password'),
                }),
            });
            setSession(login);
            showApp(true);
            await loadDashboard(state);
            setText('[data-login-message]', '');
        } catch (error) {
            setText('[data-login-message]', error instanceof Error ? error.message : 'Falha ao entrar.');
        }
    });

    $('[data-refresh]')?.addEventListener('click', () => loadDashboard(state));
    $('[data-logout]')?.addEventListener('click', () => {
        clearSession();
        showApp(false);
    });

    $$<HTMLButtonElement>('[data-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            $$('[data-tab]').forEach((item) => item.classList.toggle('is-active', item === button));
            $$<HTMLElement>('[data-panel]').forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.panel !== button.dataset.tab));
            if (button.dataset.tab === 'erp') {
                loadErpModule(state).catch((error) => {
                    setText('[data-erp-count]', error instanceof Error ? error.message : 'Falha ao carregar.');
                });
            }
        });
    });

    $('[data-audit-download]')?.addEventListener('click', async () => {
        const response = await fetch('/api/v1/audit-logs/export.csv', {
            headers: { Authorization: `Bearer ${state.token}` },
        });
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'audit-logs.csv';
        link.click();
        URL.revokeObjectURL(url);
    });

    document.addEventListener('click', async (event) => {
        const target = event.target as Element;
        const approveButton = target.closest<HTMLButtonElement>('[data-approve-execution]');
        const rejectButton = target.closest<HTMLButtonElement>('[data-reject-execution]');

        if (approveButton?.dataset.approveExecution) {
            approveButton.disabled = true;
            await api(state, `/api/v1/rule-executions/${approveButton.dataset.approveExecution}/approve`, { method: 'POST' });
            await loadDashboard(state);
        }

        if (rejectButton?.dataset.rejectExecution) {
            rejectButton.disabled = true;
            await api(state, `/api/v1/rule-executions/${rejectButton.dataset.rejectExecution}/reject`, {
                method: 'POST',
                body: JSON.stringify({ reason: 'Rejeitado pela central de acoes.' }),
            });
            await loadDashboard(state);
        }
    });

    bindErp(state);
};

bindEvents();
showApp(Boolean(state.token));
if (state.token) {
    loadDashboard(state).catch(() => {
        clearSession();
        showApp(false);
    });
}
