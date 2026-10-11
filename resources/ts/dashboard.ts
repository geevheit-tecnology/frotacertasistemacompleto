import { api, asPageData, type ApiRecord, type AppState } from './api';
import { $, setText } from './dom';

const empty = (): Node => {
    const template = $<HTMLTemplateElement>('[data-empty-template]');
    return template?.content.firstElementChild?.cloneNode(true) ?? document.createTextNode('Nenhum registro encontrado.');
};

const badgeClass = (value: unknown): string => {
    if (['CRITICO', 'ALTO', 'OVERDUE'].includes(String(value))) return 'bg-red-100 text-red-700 ring-1 ring-red-600/10';
    if (['MEDIO', 'ATENCAO', 'OPEN'].includes(String(value))) return 'bg-orange-100 text-orange-700 ring-1 ring-orange-600/10';
    if (['PLANO', 'TACOGRAFO'].includes(String(value))) return 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10';
    return 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-600/10';
};

export const renderMetric = (label: string, value: unknown): HTMLElement => {
    const item = document.createElement('article');
    item.className = 'bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex flex-col justify-between hover:shadow-md transition-shadow group';
    item.innerHTML = `<span class="text-xs font-bold text-slate-500 uppercase tracking-wider group-hover:text-indigo-500 transition-colors">${label}</span><strong class="text-3xl font-extrabold text-slate-800 mt-3 block">${value ?? 0}</strong>`;
    return item;
};

type ListItemView = {
    title?: unknown;
    description?: unknown;
    meta?: unknown[];
    badges?: unknown[];
};

export const renderListItem = ({ title, description, meta = [], badges = [] }: ListItemView): HTMLElement => {
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

const renderCollection = <T>(selector: string, records: T[], mapper: (record: T) => HTMLElement): void => {
    const target = $(selector);
    if (!target) return;

    target.replaceChildren();

    if (!records.length) {
        target.appendChild(empty());
        return;
    }

    records.forEach((record) => target.appendChild(mapper(record)));
};

export const loadDashboard = async (state: AppState): Promise<void> => {
    const [mission, alerts, insights, tasks, approvals, plans, tachographs, report, audit] = await Promise.all([
        api<ApiRecord>(state, '/api/v1/mission-control'),
        api<{ data?: ApiRecord[] }>(state, '/api/v1/alerts?status=ABERTO'),
        api<{ data?: ApiRecord[] }>(state, '/api/v1/insights'),
        api<{ data?: ApiRecord[] }>(state, '/api/v1/tasks?status=OPEN'),
        api<{ data?: ApiRecord[] }>(state, '/api/v1/rule-executions?status=WAITING_APPROVAL'),
        api<{ data?: ApiRecord[] }>(state, '/api/v1/maintenance-plans?status=ACTIVE'),
        api<{ data?: ApiRecord[] }>(state, '/api/v1/tachographs'),
        api<ApiRecord>(state, '/api/v1/reports/executive'),
        api<{ data?: ApiRecord[] }>(state, '/api/v1/audit-logs'),
    ]);

    const missionGrid = $('[data-mission-grid]');
    missionGrid?.replaceChildren(
        renderMetric('Frota', (mission.fleet as ApiRecord)?.total),
        renderMetric('Ativos', (mission.fleet as ApiRecord)?.active),
        renderMetric('Parados', (mission.fleet as ApiRecord)?.stopped),
        renderMetric('Criticos', (mission.alerts as ApiRecord)?.critical),
        renderMetric('Preventivas', (mission.preventive as ApiRecord)?.active_plans),
        renderMetric('Tacografos 90 dias', (mission.tachographs as ApiRecord)?.next_90_days),
        renderMetric('Acoes abertas', (mission.tasks as ApiRecord)?.open),
        renderMetric('Pneus atencao', (mission.tires as ApiRecord)?.attention_tread),
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
    setText('[data-action-count]', `${taskRecords.length} abertas / ${approvalRecords.length} aprovacoes`);
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

    const calendarItems: ListItemView[] = [
        ...asPageData(plans).map((plan) => ({
            title: plan.name,
            description: `${plan.component} - ${plan.interval_km || '-'} km / ${plan.interval_days || '-'} dias / ${plan.interval_hours || '-'} h`,
            badges: ['PLANO', plan.status],
        })),
        ...asPageData(tachographs).map((record) => ({
            title: `Tacografo ${(record.vehicle as ApiRecord)?.plate || record.vehicle_id}`,
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

    const reportGrid = $('[data-report-grid]');
    reportGrid?.replaceChildren(
        renderMetric('Veiculos', (report.fleet as ApiRecord)?.total),
        renderMetric('Disponiveis', (report.fleet as ApiRecord)?.available),
        renderMetric('Alertas criticos', (report.risks as ApiRecord)?.critical_alerts),
        renderMetric('Insights abertos', (report.risks as ApiRecord)?.open_insights),
        renderMetric('Checklists', (report.operations as ApiRecord)?.completed_checklists),
        renderMetric('Combustivel R$', (report.costs as ApiRecord)?.fuel_amount),
        renderMetric('Litros', (report.costs as ApiRecord)?.fuel_liters),
        renderMetric('Contas abertas R$', (report.costs as ApiRecord)?.open_payables),
    );

    renderCollection('[data-audit-list]', asPageData(audit), (log) => renderListItem({
        title: `${log.action} em ${log.entity}`,
        description: log.origin,
        meta: [log.occurred_at || log.created_at || 'Sem data'],
        badges: ['AUDIT'],
    }));
};
