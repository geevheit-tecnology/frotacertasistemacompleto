const state = {
    token: localStorage.getItem('frotacerta.token') || '',
    user: JSON.parse(localStorage.getItem('frotacerta.user') || 'null'),
};

const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => [...document.querySelectorAll(selector)];

const api = async (path, options = {}) => {
    const headers = {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(state.token ? { Authorization: `Bearer ${state.token}` } : {}),
        ...(options.headers || {}),
    };

    const response = await fetch(path, { ...options, headers });
    const text = await response.text();
    const payload = text ? JSON.parse(text) : null;

    if (!response.ok) {
        throw new Error(payload?.message || 'Falha na requisicao.');
    }

    return payload;
};

const setSession = (login) => {
    state.token = login.access_token;
    state.user = login.user;
    localStorage.setItem('frotacerta.token', state.token);
    localStorage.setItem('frotacerta.user', JSON.stringify(state.user));
};

const clearSession = () => {
    state.token = '';
    state.user = null;
    localStorage.removeItem('frotacerta.token');
    localStorage.removeItem('frotacerta.user');
};

const showApp = (authenticated) => {
    $('[data-login-view]')?.classList.toggle('is-hidden', authenticated);
    $('[data-dashboard-view]')?.classList.toggle('is-hidden', !authenticated);
    $('[data-user-name]').textContent = state.user?.name || '';
};

const empty = () => $('[data-empty-template]').content.firstElementChild.cloneNode(true);

const badgeClass = (value) => {
    if (['CRITICO', 'ALTO', 'OVERDUE'].includes(value)) return 'bg-red-100 text-red-700 ring-1 ring-red-600/10';
    if (['MEDIO', 'ATENCAO', 'OPEN'].includes(value)) return 'bg-orange-100 text-orange-700 ring-1 ring-orange-600/10';
    if (['PLANO', 'TACOGRAFO'].includes(value)) return 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10';
    return 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-600/10';
};

const renderMetric = (label, value) => {
    const item = document.createElement('article');
    item.className = 'bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex flex-col justify-between hover:shadow-md transition-shadow group';
    item.innerHTML = `<span class="text-xs font-bold text-slate-500 uppercase tracking-wider group-hover:text-indigo-500 transition-colors">${label}</span><strong class="text-3xl font-extrabold text-slate-800 mt-3 block">${value ?? 0}</strong>`;
    return item;
};

const renderListItem = ({ title, description, meta = [], badges = [] }) => {
    const item = document.createElement('article');
    item.className = 'bg-white rounded-xl border border-slate-100 p-4 shadow-sm hover:shadow-md transition-shadow group';
    item.innerHTML = `
        <h4 class="font-bold text-slate-800 text-base mb-1 group-hover:text-indigo-600 transition-colors">${title || 'Sem titulo'}</h4>
        ${description ? `<p class="text-sm text-slate-500 mb-3 line-clamp-2">${description}</p>` : ''}
        <div class="flex flex-wrap gap-2 mt-2">
            ${meta.filter(Boolean).map((value) => `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600">${value}</span>`).join('')}
            ${badges.filter(Boolean).map((value) => `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${badgeClass(value)}">${value}</span>`).join('')}
        </div>
    `;
    return item;
};

const renderCollection = (selector, records, mapper) => {
    const target = $(selector);
    target.replaceChildren();

    if (!records.length) {
        target.appendChild(empty());
        return;
    }

    records.forEach((record) => target.appendChild(mapper(record)));
};

const asPageData = (payload) => payload?.data || [];

const loadDashboard = async () => {
    const [mission, alerts, insights, tasks, approvals, plans, tachographs, report, audit] = await Promise.all([
        api('/api/v1/mission-control'),
        api('/api/v1/alerts?status=ABERTO'),
        api('/api/v1/insights'),
        api('/api/v1/tasks?status=OPEN'),
        api('/api/v1/rule-executions?status=WAITING_APPROVAL'),
        api('/api/v1/maintenance-plans?status=ACTIVE'),
        api('/api/v1/tachographs'),
        api('/api/v1/reports/executive'),
        api('/api/v1/audit-logs'),
    ]);

    $('[data-mission-grid]').replaceChildren(
        renderMetric('Frota', mission.fleet?.total),
        renderMetric('Ativos', mission.fleet?.active),
        renderMetric('Parados', mission.fleet?.stopped),
        renderMetric('Criticos', mission.alerts?.critical),
        renderMetric('Preventivas', mission.preventive?.active_plans),
        renderMetric('Tacografos 90 dias', mission.tachographs?.next_90_days),
        renderMetric('Acoes abertas', mission.tasks?.open),
        renderMetric('Pneus atencao', mission.tires?.attention_tread),
    );

    renderCollection('[data-alert-list]', asPageData(alerts), (alert) => renderListItem({
        title: alert.message || alert.type,
        description: alert.recommendation,
        meta: [alert.category, alert.entity],
        badges: [alert.severity, alert.status],
    }));

    renderCollection('[data-insight-list]', asPageData(insights), (insight) => renderListItem({
        title: insight.title,
        description: insight.description || insight.recommendation,
        meta: [`Prioridade ${insight.priority ?? 0}`],
        badges: [insight.type, insight.status],
    }));

    const taskRecords = asPageData(tasks);
    const approvalRecords = asPageData(approvals);
    $('[data-action-count]').textContent = `${taskRecords.length} abertas / ${approvalRecords.length} aprovacoes`;
    renderCollection('[data-approval-list]', approvalRecords, (execution) => {
        const item = renderListItem({
            title: execution.trigger_event,
            description: `${execution.entity} ${execution.entity_id}`,
            meta: [`Executado ${execution.executed_at || '-'}`],
            badges: [execution.status],
        });
        const actions = document.createElement('div');
        actions.className = 'flex gap-2 mt-4 pt-3 border-t border-slate-100';
        actions.innerHTML = `
            <button type="button" class="flex-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold py-2 px-3 rounded-lg text-sm transition-colors" data-approve-execution="${execution.id}">Aprovar</button>
            <button type="button" class="flex-1 bg-red-50 hover:bg-red-100 text-red-700 font-semibold py-2 px-3 rounded-lg text-sm transition-colors" data-reject-execution="${execution.id}">Rejeitar</button>
        `;
        item.appendChild(actions);
        return item;
    });
    renderCollection('[data-task-list]', taskRecords, (task) => renderListItem({
        title: task.title,
        description: task.description,
        meta: [task.due_at ? `Prazo ${task.due_at}` : 'Sem prazo'],
        badges: [task.priority, task.status],
    }));

    const calendarItems = [
        ...asPageData(plans).map((plan) => ({
            title: plan.name,
            description: `${plan.component} - ${plan.interval_km || '-'} km / ${plan.interval_days || '-'} dias / ${plan.interval_hours || '-'} h`,
            badges: ['PLANO', plan.status],
        })),
        ...asPageData(tachographs).map((record) => ({
            title: `Tacografo ${record.vehicle?.plate || record.vehicle_id}`,
            description: `Validade ${record.expires_at}`,
            badges: ['TACOGRAFO', record.status],
        })),
    ];
    renderCollection('[data-calendar-list]', calendarItems, (item) => {
        const card = document.createElement('article');
        card.className = 'calendar-item';
        card.appendChild(renderListItem(item));
        return card;
    });

    $('[data-report-grid]').replaceChildren(
        renderMetric('Veiculos', report.fleet?.total),
        renderMetric('Disponiveis', report.fleet?.available),
        renderMetric('Alertas criticos', report.risks?.critical_alerts),
        renderMetric('Insights abertos', report.risks?.open_insights),
        renderMetric('Checklists', report.operations?.completed_checklists),
        renderMetric('Combustivel R$', report.costs?.fuel_amount),
        renderMetric('Litros', report.costs?.fuel_liters),
        renderMetric('Contas abertas R$', report.costs?.open_payables),
    );

    renderCollection('[data-audit-list]', asPageData(audit), (log) => renderListItem({
        title: `${log.action} em ${log.entity}`,
        description: log.origin,
        meta: [log.occurred_at || log.created_at || 'Sem data'],
        badges: ['AUDIT'],
    }));
};

const bindEvents = () => {
    $('[data-login-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const message = $('[data-login-message]');
        message.textContent = 'Entrando...';

        try {
            const login = await api('/api/v1/auth/login', {
                method: 'POST',
                body: JSON.stringify({
                    email: form.get('email'),
                    password: form.get('password'),
                }),
            });
            setSession(login);
            showApp(true);
            await loadDashboard();
            message.textContent = '';
        } catch (error) {
            message.textContent = error.message;
        }
    });

    $('[data-refresh]')?.addEventListener('click', () => loadDashboard());
    $('[data-logout]')?.addEventListener('click', () => {
        clearSession();
        showApp(false);
    });

    $$('[data-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            $$('[data-tab]').forEach((item) => item.classList.toggle('is-active', item === button));
            $$('[data-panel]').forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.panel !== button.dataset.tab));
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
        const approveButton = event.target.closest('[data-approve-execution]');
        const rejectButton = event.target.closest('[data-reject-execution]');

        if (approveButton) {
            approveButton.disabled = true;
            await api(`/api/v1/rule-executions/${approveButton.dataset.approveExecution}/approve`, { method: 'POST' });
            await loadDashboard();
        }

        if (rejectButton) {
            rejectButton.disabled = true;
            await api(`/api/v1/rule-executions/${rejectButton.dataset.rejectExecution}/reject`, {
                method: 'POST',
                body: JSON.stringify({ reason: 'Rejeitado pela central de acoes.' }),
            });
            await loadDashboard();
        }
    });
};

bindEvents();
showApp(Boolean(state.token));
if (state.token) {
    loadDashboard().catch(() => {
        clearSession();
        showApp(false);
    });
}
