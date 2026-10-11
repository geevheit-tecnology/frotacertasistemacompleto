import { api, asPageData, type ApiRecord, type AppState } from './api';
import { $, $$, escapeHtml, setText } from './dom';
import { loadDashboard } from './dashboard';

type ErpField = [name: string, label: string, type: string, required?: boolean, options?: string[]];
type ErpModule = {
    label: string;
    description: string;
    endpoint: string;
    titleField: string;
    columns: [key: string, label: string][];
    fields: ErpField[];
    canDelete?: boolean;
};

const modules: Record<string, ErpModule> = {
    companies: {
        label: 'Empresas',
        description: 'Dados fiscais e comerciais das empresas do grupo.',
        endpoint: '/api/v1/companies',
        titleField: 'legal_name',
        canDelete: false,
        columns: [['legal_name', 'Razão social'], ['trade_name', 'Nome fantasia'], ['tax_id', 'CNPJ/Documento']],
        fields: [['legal_name', 'Razão social', 'text', true], ['trade_name', 'Nome fantasia', 'text'], ['tax_id', 'CNPJ/Documento', 'text']],
    },
    branches: {
        label: 'Filiais',
        description: 'Unidades operacionais vinculadas à empresa atual.',
        endpoint: '/api/v1/branches',
        titleField: 'name',
        canDelete: true,
        columns: [['name', 'Nome'], ['created_at', 'Criada em']],
        fields: [['name', 'Nome da filial', 'text', true]],
    },
    users: {
        label: 'Usuários',
        description: 'Acessos ao SaaS, vínculo com filial e situação operacional.',
        endpoint: '/api/v1/users',
        titleField: 'name',
        canDelete: true,
        columns: [['name', 'Nome'], ['email', 'Email'], ['status', 'Status'], ['branch_id', 'Filial']],
        fields: [['name', 'Nome completo', 'text', true], ['email', 'Email', 'email', true], ['password', 'Senha', 'password'], ['branch_id', 'ID da filial', 'text'], ['status', 'Status', 'select', false, ['ACTIVE', 'INACTIVE']]],
    },
    vehicles: {
        label: 'Veículos',
        description: 'Cadastro operacional da frota, hodômetro, horímetro e status.',
        endpoint: '/api/v1/vehicles',
        titleField: 'plate',
        canDelete: true,
        columns: [['plate', 'Placa'], ['fleet_number', 'Frota'], ['manufacturer', 'Marca'], ['model', 'Modelo'], ['model_year', 'Ano'], ['current_odometer_km', 'Km'], ['status', 'Status']],
        fields: [['plate', 'Placa', 'text', true], ['fleet_number', 'Número da frota', 'text'], ['manufacturer', 'Fabricante', 'text'], ['model', 'Modelo', 'text'], ['model_year', 'Ano modelo', 'number'], ['current_odometer_km', 'Hodômetro atual', 'number'], ['current_hourmeter', 'Horímetro atual', 'number'], ['status', 'Status', 'select', false, ['ACTIVE', 'STOPPED', 'MAINTENANCE', 'INACTIVE']]],
    },
    drivers: {
        label: 'Motoristas',
        description: 'Cadastro de condutores, CNH, categoria, vencimento e situação.',
        endpoint: '/api/v1/drivers',
        titleField: 'name',
        canDelete: true,
        columns: [['name', 'Nome'], ['document_number', 'Documento'], ['license_number', 'CNH'], ['license_category', 'Categoria'], ['license_expires_at', 'Vencimento'], ['status', 'Status']],
        fields: [['name', 'Nome completo', 'text', true], ['document_number', 'Documento', 'text'], ['license_number', 'Número da CNH', 'text'], ['license_category', 'Categoria da CNH', 'text'], ['license_expires_at', 'Vencimento da CNH', 'date'], ['status', 'Status', 'select', false, ['ACTIVE', 'INACTIVE', 'SUSPENDED']]],
    },
    suppliers: {
        label: 'Fornecedores',
        description: 'Oficinas, autopeças, postos, seguradoras e parceiros operacionais.',
        endpoint: '/api/v1/suppliers',
        titleField: 'name',
        canDelete: true,
        columns: [['name', 'Nome'], ['document_number', 'Documento'], ['type', 'Tipo'], ['phone', 'Telefone'], ['email', 'Email'], ['status', 'Status']],
        fields: [['name', 'Nome', 'text', true], ['document_number', 'Documento', 'text'], ['type', 'Tipo', 'select', false, ['WORKSHOP', 'PARTS', 'FUEL', 'INSURANCE', 'OTHER']], ['phone', 'Telefone', 'text'], ['email', 'Email', 'email'], ['status', 'Status', 'select', false, ['ACTIVE', 'INACTIVE']]],
    },
    inventoryParts: {
        label: 'Peças / estoque',
        description: 'Itens de almoxarifado, saldo inicial, estoque mínimo e custo médio.',
        endpoint: '/api/v1/inventory-parts',
        titleField: 'name',
        canDelete: true,
        columns: [['sku', 'SKU'], ['name', 'Nome'], ['unit', 'Unidade'], ['quantity_on_hand', 'Saldo'], ['minimum_quantity', 'Mínimo'], ['average_cost', 'Custo médio'], ['status', 'Status']],
        fields: [['sku', 'SKU', 'text'], ['name', 'Nome da peça', 'text', true], ['unit', 'Unidade', 'text'], ['quantity_on_hand', 'Saldo inicial', 'number'], ['minimum_quantity', 'Estoque mínimo', 'number'], ['average_cost', 'Custo médio', 'number'], ['status', 'Status', 'select', false, ['ACTIVE', 'INACTIVE']]],
    },
    costCenters: {
        label: 'Centros de custo',
        description: 'Classificação financeira para custos de frota e operações.',
        endpoint: '/api/v1/cost-centers',
        titleField: 'name',
        canDelete: false,
        columns: [['name', 'Nome'], ['code', 'Código'], ['status', 'Status']],
        fields: [['name', 'Nome', 'text', true], ['code', 'Código', 'text'], ['status', 'Status', 'select', false, ['ACTIVE', 'INACTIVE']]],
    },
    fuelRecords: {
        label: 'Abastecimentos',
        description: 'Lançamentos de combustível com veículo, litros, valor e hodômetro.',
        endpoint: '/api/v1/fuel-records',
        titleField: 'fuel_type',
        canDelete: false,
        columns: [['fuel_type', 'Combustível'], ['quantity_liters', 'Litros'], ['unit_price', 'Preço'], ['total_amount', 'Total'], ['odometer_km', 'Km'], ['filled_at', 'Data']],
        fields: [['vehicle_id', 'ID do veículo', 'text', true], ['driver_id', 'ID do motorista', 'text'], ['supplier_id', 'ID do fornecedor', 'text'], ['cost_center_id', 'ID do centro de custo', 'text'], ['fuel_type', 'Tipo de combustível', 'select', true, ['DIESEL', 'GASOLINE', 'ETHANOL', 'ARLA']], ['quantity_liters', 'Litros', 'number', true], ['unit_price', 'Preço por litro', 'number', true], ['odometer_km', 'Hodômetro', 'number', true], ['invoice_number', 'Número da nota', 'text'], ['filled_at', 'Data do abastecimento', 'date']],
    },
    fines: {
        label: 'Multas',
        description: 'Registro de autos de infração, vencimento, valor e status.',
        endpoint: '/api/v1/fines',
        titleField: 'notice_number',
        canDelete: false,
        columns: [['notice_number', 'Auto'], ['vehicle_id', 'Veículo'], ['driver_id', 'Motorista'], ['occurred_at', 'Ocorrência'], ['due_at', 'Vencimento'], ['amount', 'Valor'], ['status', 'Status']],
        fields: [['vehicle_id', 'ID do veículo', 'text', true], ['driver_id', 'ID do motorista', 'text'], ['notice_number', 'Número do auto', 'text', true], ['occurred_at', 'Data da ocorrência', 'date', true], ['due_at', 'Vencimento', 'date'], ['amount', 'Valor', 'number'], ['status', 'Status', 'select', false, ['OPEN', 'PAID', 'CONTESTED', 'CANCELED']], ['description', 'Descrição', 'textarea'], ['file_path', 'Arquivo', 'text']],
    },
    vehicleDocuments: {
        label: 'Documentos de veículos',
        description: 'CRLV, seguros, licenças e vencimentos vinculados à frota.',
        endpoint: '/api/v1/vehicle-documents',
        titleField: 'document_type',
        canDelete: true,
        columns: [['vehicle_id', 'Veículo'], ['document_type', 'Tipo'], ['issued_at', 'Emissão'], ['expires_at', 'Vencimento'], ['status', 'Status'], ['file_path', 'Arquivo']],
        fields: [['vehicle_id', 'ID do veículo', 'text', true], ['document_type', 'Tipo do documento', 'text', true], ['issued_at', 'Emissão', 'date'], ['expires_at', 'Vencimento', 'date'], ['status', 'Status', 'select', false, ['VALID', 'EXPIRED', 'PENDING', 'CANCELED']], ['file_path', 'Arquivo', 'text']],
    },
    driverDocuments: {
        label: 'Documentos de motoristas',
        description: 'CNH, cursos, exames e documentos com controle de validade.',
        endpoint: '/api/v1/driver-documents',
        titleField: 'document_type',
        canDelete: true,
        columns: [['driver_id', 'Motorista'], ['document_type', 'Tipo'], ['issued_at', 'Emissão'], ['expires_at', 'Vencimento'], ['status', 'Status'], ['file_path', 'Arquivo']],
        fields: [['driver_id', 'ID do motorista', 'text', true], ['document_type', 'Tipo do documento', 'text', true], ['issued_at', 'Emissão', 'date'], ['expires_at', 'Vencimento', 'date'], ['status', 'Status', 'select', false, ['VALID', 'EXPIRED', 'PENDING', 'CANCELED']], ['file_path', 'Arquivo', 'text']],
    },
    maintenancePlans: {
        label: 'Planos de manutenção',
        description: 'Preventivas por componente, veículo, km, dias ou horas.',
        endpoint: '/api/v1/maintenance-plans',
        titleField: 'name',
        canDelete: true,
        columns: [['name', 'Plano'], ['component', 'Componente'], ['scope_type', 'Escopo'], ['vehicle_id', 'Veículo'], ['interval_km', 'Km'], ['interval_days', 'Dias'], ['interval_hours', 'Horas'], ['status', 'Status']],
        fields: [['name', 'Nome do plano', 'text', true], ['component', 'Componente', 'text', true], ['scope_type', 'Escopo', 'select', false, ['FLEET', 'VEHICLE', 'MODEL']], ['vehicle_id', 'ID do veículo', 'text'], ['manufacturer', 'Fabricante', 'text'], ['model', 'Modelo', 'text'], ['interval_km', 'Intervalo em km', 'number'], ['interval_days', 'Intervalo em dias', 'number'], ['interval_hours', 'Intervalo em horas', 'number'], ['status', 'Status', 'select', false, ['ACTIVE', 'INACTIVE']]],
    },
    oilServices: {
        label: 'Troca de óleo',
        description: 'Histórico de óleo com projeção de próxima troca e custos.',
        endpoint: '/api/v1/oil-services',
        titleField: 'engine',
        canDelete: true,
        columns: [['vehicle_id', 'Veículo'], ['engine', 'Motor'], ['oil_type', 'Óleo'], ['viscosity', 'Viscosidade'], ['service_odometer_km', 'Km troca'], ['interval_km', 'Intervalo'], ['service_date', 'Data'], ['cost_amount', 'Custo']],
        fields: [['vehicle_id', 'ID do veículo', 'text', true], ['engine', 'Motor', 'text'], ['oil_type', 'Tipo de óleo', 'text'], ['viscosity', 'Viscosidade', 'text'], ['manufacturer', 'Fabricante', 'text'], ['service_odometer_km', 'Km da troca', 'number', true], ['service_date', 'Data da troca', 'date', true], ['interval_km', 'Intervalo em km', 'number', true], ['supplier', 'Fornecedor', 'text'], ['invoice_number', 'Nota fiscal', 'text'], ['cost_amount', 'Custo', 'number'], ['work_order_number', 'OS', 'text'], ['notes', 'Observações', 'textarea']],
    },
    tachographs: {
        label: 'Tacógrafos',
        description: 'Equipamentos, certificados, calibração e vencimentos.',
        endpoint: '/api/v1/tachographs',
        titleField: 'number',
        canDelete: true,
        columns: [['vehicle_id', 'Veículo'], ['number', 'Número'], ['manufacturer', 'Fabricante'], ['model', 'Modelo'], ['certificate_number', 'Certificado'], ['expires_at', 'Vencimento'], ['status', 'Status']],
        fields: [['vehicle_id', 'ID do veículo', 'text', true], ['number', 'Número', 'text', true], ['manufacturer', 'Fabricante', 'text'], ['model', 'Modelo', 'text'], ['installed_at', 'Instalação', 'date'], ['calibrated_at', 'Calibração', 'date'], ['certificate_number', 'Certificado', 'text'], ['expires_at', 'Vencimento', 'date', true], ['last_maintenance_at', 'Última manutenção', 'date'], ['status', 'Status', 'select', false, ['VALID', 'EXPIRED', 'MAINTENANCE', 'INACTIVE']]],
    },
    workOrders: {
        label: 'Ordens de serviço',
        description: 'Serviços de manutenção com fornecedor, prioridade e custos.',
        endpoint: '/api/v1/work-orders',
        titleField: 'number',
        canDelete: false,
        columns: [['number', 'OS'], ['vehicle_id', 'Veículo'], ['supplier_id', 'Fornecedor'], ['type', 'Tipo'], ['status', 'Status'], ['priority', 'Prioridade'], ['opened_at', 'Abertura'], ['total_cost', 'Total']],
        fields: [['vehicle_id', 'ID do veículo', 'text', true], ['supplier_id', 'ID do fornecedor', 'text'], ['number', 'Número da OS', 'text', true], ['type', 'Tipo', 'select', false, ['PREVENTIVE', 'CORRECTIVE', 'INSPECTION', 'OTHER']], ['status', 'Status', 'select', false, ['OPEN', 'IN_PROGRESS', 'CLOSED', 'CANCELED']], ['priority', 'Prioridade', 'select', false, ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']], ['description', 'Descrição', 'textarea', true], ['opened_at', 'Abertura', 'date', true], ['scheduled_at', 'Agendamento', 'date'], ['closed_at', 'Fechamento', 'date'], ['odometer_km', 'Hodômetro', 'number'], ['labor_cost', 'Mão de obra', 'number']],
    },
    tires: {
        label: 'Pneus',
        description: 'Cadastro de pneus, DOT, posição, sulco, pressão e ciclo de vida.',
        endpoint: '/api/v1/tires',
        titleField: 'fire_number',
        canDelete: false,
        columns: [['fire_number', 'Fogo'], ['manufacturer', 'Fabricante'], ['model', 'Modelo'], ['size', 'Medida'], ['vehicle_id', 'Veículo'], ['position', 'Posição'], ['tread_depth_mm', 'Sulco'], ['status', 'Status']],
        fields: [['fire_number', 'Número de fogo', 'text', true], ['manufacturer', 'Fabricante', 'text'], ['model', 'Modelo', 'text'], ['size', 'Medida', 'text'], ['dot', 'DOT', 'text'], ['purchased_at', 'Compra', 'date'], ['purchase_cost', 'Custo', 'number'], ['initial_km', 'Km inicial', 'number'], ['vehicle_id', 'ID do veículo', 'text'], ['position', 'Posição', 'text'], ['tread_depth_mm', 'Sulco mm', 'number'], ['pressure_psi', 'Pressão PSI', 'number'], ['life_cycle', 'Ciclo', 'number'], ['retread_count', 'Recapagens', 'number'], ['status', 'Status', 'select', false, ['STOCK', 'MOUNTED', 'RETREAD', 'SCRAP']]],
    },
    tireInspections: {
        label: 'Inspeções de pneus',
        description: 'Medições de sulco, pressão, desgaste e evidências visuais.',
        endpoint: '/api/v1/tire-inspections',
        titleField: 'tire_id',
        canDelete: false,
        columns: [['tire_id', 'Pneu'], ['driver_id', 'Motorista'], ['position', 'Posição'], ['tread_depth_mm', 'Sulco'], ['pressure_psi', 'Pressão'], ['wear_pattern', 'Desgaste'], ['visual_status', 'Visual'], ['inspected_at', 'Data']],
        fields: [['tire_id', 'ID do pneu', 'text', true], ['driver_id', 'ID do motorista', 'text'], ['position', 'Posição', 'text'], ['tread_depth_mm', 'Sulco mm', 'number', true], ['pressure_psi', 'Pressão PSI', 'number'], ['wear_pattern', 'Padrão de desgaste', 'text'], ['notes', 'Observações', 'textarea'], ['visual_confidence', 'Confiança visual', 'number'], ['visual_status', 'Status visual', 'text'], ['inspected_at', 'Data da inspeção', 'date']],
    },
    financialTransactions: {
        label: 'Transações financeiras',
        description: 'Contas a pagar/receber ligadas a veículo, OS, fornecedor e centro de custo.',
        endpoint: '/api/v1/financial-transactions',
        titleField: 'description',
        canDelete: false,
        columns: [['type', 'Tipo'], ['category', 'Categoria'], ['description', 'Descrição'], ['amount', 'Valor'], ['due_at', 'Vencimento'], ['paid_at', 'Pagamento'], ['status', 'Status']],
        fields: [['vehicle_id', 'ID do veículo', 'text'], ['work_order_id', 'ID da OS', 'text'], ['supplier_id', 'ID do fornecedor', 'text'], ['cost_center_id', 'ID do centro de custo', 'text'], ['type', 'Tipo', 'select', true, ['PAYABLE', 'RECEIVABLE']], ['category', 'Categoria', 'text', true], ['description', 'Descrição', 'textarea'], ['amount', 'Valor', 'number', true], ['due_at', 'Vencimento', 'date'], ['paid_at', 'Pagamento', 'date'], ['status', 'Status', 'select', false, ['OPEN', 'PAID', 'CANCELED']], ['origin', 'Origem', 'text']],
    },
    invoices: {
        label: 'Notas fiscais',
        description: 'Notas com vínculo operacional e geração financeira automática.',
        endpoint: '/api/v1/invoices',
        titleField: 'number',
        canDelete: false,
        columns: [['number', 'Número'], ['series', 'Série'], ['supplier_id', 'Fornecedor'], ['vehicle_id', 'Veículo'], ['amount', 'Valor'], ['issued_at', 'Emissão'], ['category', 'Categoria'], ['status', 'Status']],
        fields: [['supplier_id', 'ID do fornecedor', 'text'], ['vehicle_id', 'ID do veículo', 'text'], ['work_order_id', 'ID da OS', 'text'], ['cost_center_id', 'ID do centro de custo', 'text'], ['number', 'Número', 'text', true], ['series', 'Série', 'text'], ['access_key', 'Chave de acesso', 'text'], ['issued_at', 'Emissão', 'date', true], ['amount', 'Valor', 'number', true], ['category', 'Categoria', 'text'], ['file_path', 'Arquivo', 'text'], ['status', 'Status', 'select', false, ['OPEN', 'PAID', 'CANCELED']]],
    },
    trips: {
        label: 'Viagens',
        description: 'Operações de rota com veículo, motorista, origem, destino e período.',
        endpoint: '/api/v1/trips',
        titleField: 'destination',
        canDelete: false,
        columns: [['vehicle_id', 'Veículo'], ['driver_id', 'Motorista'], ['origin', 'Origem'], ['destination', 'Destino'], ['starts_at', 'Início'], ['ends_at', 'Fim'], ['status', 'Status']],
        fields: [['vehicle_id', 'ID do veículo', 'text', true], ['driver_id', 'ID do motorista', 'text'], ['origin', 'Origem', 'text', true], ['destination', 'Destino', 'text', true], ['starts_at', 'Início', 'date', true], ['ends_at', 'Fim', 'date'], ['status', 'Status', 'select', false, ['SCHEDULED', 'IN_PROGRESS', 'DONE', 'CANCELED']]],
    },
    telematicsEvents: {
        label: 'Eventos telemáticos',
        description: 'Eventos de localização, hodômetro, horímetro, velocidade e origem da integração.',
        endpoint: '/api/v1/telematics-events',
        titleField: 'source',
        canDelete: false,
        columns: [['vehicle_id', 'Veículo'], ['recorded_at', 'Registro'], ['latitude', 'Latitude'], ['longitude', 'Longitude'], ['odometer_km', 'Km'], ['hourmeter', 'Horímetro'], ['speed_kmh', 'Velocidade'], ['source', 'Fonte']],
        fields: [['vehicle_id', 'ID do veículo', 'text', true], ['recorded_at', 'Data/hora', 'datetime-local', true], ['latitude', 'Latitude', 'number'], ['longitude', 'Longitude', 'number'], ['odometer_km', 'Hodômetro', 'number'], ['hourmeter', 'Horímetro', 'number'], ['speed_kmh', 'Velocidade km/h', 'number'], ['source', 'Fonte', 'text']],
    },
};

const formatValue = (value: unknown): string => {
    if (value === null || value === undefined || value === '') return '-';
    if (typeof value === 'string' && value.includes('T')) return value.slice(0, 10);
    return String(value);
};

const current = (state: AppState): ErpModule => modules[state.erpModule] || modules.vehicles;

const renderMenu = (state: AppState): void => {
    const target = $('[data-erp-menu]');
    if (!target) return;

    target.replaceChildren(...Object.entries(modules).map(([key, module]) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.erpModule = key;
        button.className = `w-full text-left px-4 py-3 rounded-xl transition-colors ${state.erpModule === key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'}`;
        button.innerHTML = `<span class="block font-bold">${escapeHtml(module.label)}</span><span class="block text-xs ${state.erpModule === key ? 'text-slate-300' : 'text-slate-400'}">${escapeHtml(module.description)}</span>`;
        return button;
    }));
};

export const renderErpTable = (state: AppState): void => {
    const module = current(state);
    const search = ($<HTMLInputElement>('[data-erp-search]')?.value || '').toLowerCase();
    const records = state.erpRecords.filter((record) => JSON.stringify(record).toLowerCase().includes(search));
    const head = $('[data-erp-table-head]');
    const body = $('[data-erp-table-body]');

    setText('[data-erp-kicker]', 'Cadastros ERP');
    setText('[data-erp-title]', module.label);
    setText('[data-erp-description]', module.description);
    setText('[data-erp-count]', `${records.length} registro(s)`);

    if (!head || !body) return;

    head.innerHTML = `<tr>${module.columns.map(([, label]) => `<th class="px-4 py-3 font-bold">${escapeHtml(label)}</th>`).join('')}<th class="px-4 py-3 font-bold text-right">Ações</th></tr>`;

    if (!records.length) {
        body.innerHTML = `<tr><td class="px-4 py-8 text-center text-slate-500" colspan="${module.columns.length + 1}">Nenhum registro encontrado.</td></tr>`;
        return;
    }

    body.replaceChildren(...records.map((record) => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-slate-50';
        row.innerHTML = `
            ${module.columns.map(([key]) => `<td class="px-4 py-3 text-slate-700 whitespace-nowrap">${escapeHtml(formatValue(record[key]))}</td>`).join('')}
            <td class="px-4 py-3">
                <div class="flex justify-end gap-2">
                    <button type="button" class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold" data-erp-edit="${escapeHtml(record.id)}">Editar</button>
                    ${module.canDelete ? `<button type="button" class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 font-semibold" data-erp-delete="${escapeHtml(record.id)}">Excluir</button>` : ''}
                </div>
            </td>
        `;
        return row;
    }));
};

export const loadErpModule = async (state: AppState): Promise<void> => {
    state.erpRecords = asPageData(await api<{ data?: ApiRecord[] }>(state, current(state).endpoint));
    renderMenu(state);
    renderErpTable(state);
};

const closeModal = (state: AppState): void => {
    $('[data-erp-modal]')?.classList.add('is-hidden');
    setText('[data-erp-message]', '');
    state.erpEditingId = null;
};

const openModal = (state: AppState, record: ApiRecord | null = null): void => {
    const module = current(state);
    const form = $<HTMLFormElement>('[data-erp-form]');
    if (!form) return;

    state.erpEditingId = record?.id || null;
    setText('[data-erp-form-kicker]', module.label);
    setText('[data-erp-form-title]', record ? `Editar ${formatValue(record[module.titleField])}` : `Novo ${module.label}`);
    setText('[data-erp-message]', '');

    form.id = 'erp-generated-form';
    form.replaceChildren(...module.fields.map(([name, label, type, required = false, options = []]) => {
        const wrapper = document.createElement('label');
        wrapper.className = 'block';
        const value = record?.[name] ?? '';
        const input = type === 'select'
            ? `<select name="${name}" ${required ? 'required' : ''} class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                <option value="">Selecione</option>
                ${options.map((option) => `<option value="${escapeHtml(option)}" ${value === option ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}
               </select>`
            : type === 'textarea'
                ? `<textarea name="${name}" ${required ? 'required' : ''} rows="4" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">${escapeHtml(formatValue(value) === '-' ? '' : formatValue(value))}</textarea>`
            : `<input name="${name}" type="${type}" value="${escapeHtml(formatValue(value) === '-' ? '' : formatValue(value))}" ${required ? 'required' : ''} class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">`;
        wrapper.innerHTML = `<span class="block text-sm font-semibold text-slate-700 mb-1">${escapeHtml(label)}${required ? ' *' : ''}</span>${input}`;
        return wrapper;
    }));

    $('[data-erp-modal]')?.classList.remove('is-hidden');
};

const submitForm = async (state: AppState, event: SubmitEvent): Promise<void> => {
    event.preventDefault();
    const module = current(state);
    const form = event.currentTarget as HTMLFormElement;
    const data = Object.fromEntries(new FormData(form).entries());

    Object.keys(data).forEach((key) => {
        if (data[key] === '') delete data[key];
    });

    try {
        setText('[data-erp-message]', 'Salvando...');
        await api(state, state.erpEditingId ? `${module.endpoint}/${state.erpEditingId}` : module.endpoint, {
            method: state.erpEditingId ? 'PUT' : 'POST',
            body: JSON.stringify(data),
        });
        closeModal(state);
        await loadErpModule(state);
        await loadDashboard(state);
    } catch (error) {
        setText('[data-erp-message]', error instanceof Error ? error.message : 'Falha ao salvar.');
    }
};

export const bindErp = (state: AppState): void => {
    $('[data-erp-new]')?.addEventListener('click', () => openModal(state));
    $('[data-erp-search]')?.addEventListener('input', () => renderErpTable(state));
    $('[data-erp-form]')?.addEventListener('submit', (event) => submitForm(state, event as SubmitEvent));
    $$('[data-erp-close]').forEach((button) => button.addEventListener('click', () => closeModal(state)));

    document.addEventListener('click', async (event) => {
        const target = event.target as Element;
        const moduleButton = target.closest<HTMLElement>('[data-erp-module]');
        const editButton = target.closest<HTMLElement>('[data-erp-edit]');
        const deleteButton = target.closest<HTMLButtonElement>('[data-erp-delete]');

        if (moduleButton?.dataset.erpModule) {
            state.erpModule = moduleButton.dataset.erpModule;
            await loadErpModule(state);
        }

        if (editButton?.dataset.erpEdit) {
            const record = state.erpRecords.find((item) => item.id === editButton.dataset.erpEdit);
            openModal(state, record || null);
        }

        if (deleteButton?.dataset.erpDelete) {
            deleteButton.disabled = true;
            await api(state, `${current(state).endpoint}/${deleteButton.dataset.erpDelete}`, { method: 'DELETE' });
            await loadErpModule(state);
            await loadDashboard(state);
        }
    });
};
