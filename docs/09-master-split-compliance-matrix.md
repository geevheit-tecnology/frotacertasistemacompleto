# Matriz de conformidade do 01_MASTER_SPLIT

Fonte: `src/01_MASTER_SPLIT.md`

Status usados:

- `Implementado`: existe backend funcional, persistencia, rotas ou teste cobrindo o escopo principal.
- `Parcial`: existe parte relevante, mas falta cobertura completa do escopo descrito.
- `Preparado`: existe arquitetura, contrato, campo ou base tecnica, mas ainda sem fluxo operacional completo.
- `Pendente`: nao ha implementacao suficiente no codigo atual.

## Resultado executivo

O roadmap tecnico da secao 57 foi executado ate a Fase 9 como fundacao backend/API, com Neon, Vercel preview e testes automatizados. O prompt mestre completo ainda nao esta 100% encerrado como produto enterprise, pois varias secoes exigem telas operacionais, automacoes continuas, notificacoes externas, integracoes reais, telemetria, relatorios avancados e modulos complementares.

## Matriz por secao

| Secao | Tema | Status | Evidencia atual | Pendencia objetiva |
| --- | --- | --- | --- | --- |
| 1 | Stack obrigatoria | Parcial | Laravel, PHP, Postgres, Redis, Queue table, REST, OpenAPI, Docker e Compose existem | Frontend integrado ainda e minimo; Scheduler/Queue ainda nao operam como ciclo continuo |
| 2 | Arquitetura modular monolith | Parcial | Dominios em `app/*`, services em Application, docs de arquitetura | Nem todos os dominios listados possuem modulo real: Contracts, Insurance, Fines, Trips, Tracking, Telematics |
| 3 | Preventive Intelligence Engine | Parcial | Calculadoras/servicos para oleo, tacografo, pneus, checklist e custos | Falta motor continuo analisando todos os sinais com jobs/scheduler |
| 4 | Conceito de alerta | Implementado | `alerts`, model `Alert`, severidades/status, rotas `/api/v1/alerts` | Pode evoluir responsavel/evidencias e historico de status |
| 5 | Alertas de oleo | Implementado | `OilServiceProjectionCalculator`, `OilServiceAlertService`, `/api/v1/oil-services`, testes | Fluxo visual/agenda operacional ainda falta |
| 6 | Preventiva por KM | Parcial | `maintenance_plans.interval_km` e API de planos | Falta catalogo completo por modelo/fabricante/componente com execucao automatica ampla |
| 7 | Preventiva por tempo | Parcial | Planos por dias e tacografo/documentos com vencimento | Falta calendario visual e tarefas automaticas para todos os documentos/licencas/seguros |
| 8 | Preventiva por horimetro | Parcial | `maintenance_plans.interval_hours`, checklist registra horimetro | Falta previsao por historico de uso/hora em servico dedicado |
| 9 | Tacografo | Implementado inicial | `tachograph_records`, alertas/insights e `/api/v1/tachographs` | Falta cruzar com viagens futuras porque Trips ainda nao existe |
| 10 | Licencas e documentacao | Parcial | `vehicle_documents`, `driver_documents` | Falta calendario de vencimentos, seguros/ANTT/TAC/contratos em dominio completo |
| 11 | Documentos dos motoristas | Parcial | `driver_documents` e API documental | Falta regra preventiva completa para exames, certificados e treinamentos |
| 12 | Pneus | Parcial | `tires`, `tire_inspections`, `tire_movements`, rotas e testes | Movimento/posicoes/recapagem ainda precisam fluxo operacional completo |
| 13 | Preventiva de pneus | Parcial | Alertas/insights por sulco, pressao e desgaste | Falta idade, recapagem, tendencia historica mais robusta |
| 14 | Foto do pneu | Parcial | `photo_paths`, evidencias em checklist/inspecao | Falta upload/armazenamento real e comparacao visual historica |
| 15 | Visao computacional | Preparado | Campos `visual_confidence` e `visual_status` | Falta servico de analise visual e pipeline assinado como assistivo |
| 16 | Checklist | Parcial | Templates, execucoes, respostas, nao conformidades, eventos `completed` e `nonconformity` | Falta eventos `vehicle.blocked` e `vehicle.released` completos |
| 17 | Bloqueio automatico | Implementado inicial | `RuleEngine` suporta `block_vehicle` e `release_vehicle`, com eventos `vehicle.blocked.v1` e `vehicle.released.v1` | Falta tela/fluxo formal de aprovacao para executar acao pendente |
| 18 | Ordem de servico automatica | Implementado inicial | `RuleEngine` suporta `create_work_order`, cria OS idempotente e publica `maintenance.order.created.v1` | Falta aprovacao operacional para transformar execucoes `WAITING_APPROVAL` em acoes efetivas |
| 19 | Motor de insights | Implementado inicial | `insights`, rotas e servicos de oleo/tacografo/pneus | Falta ciclo amplo para todos os tipos e priorizacao avancada |
| 20 | Insights preditivos | Implementado inicial | Oleo gera previsao e recomendacao | Falta componente visual e explicabilidade padronizada em todos os dominios |
| 21 | Insights de manutencao | Preparado | Dados de OS e custos existem | Falta detector de recorrencia/frequencia/custo acima da media |
| 22 | Insights de custo | Preparado | Financeiro, combustivel, NF e relatorio por veiculo existem | Falta comparativos por modelo/categoria/filial/fornecedor e anomalias |
| 23 | Insights de combustivel | Parcial | `fuel_records` calcula media e valida KM | Falta detector de duplicidade, consumo anormal e incompatibilidade operacional |
| 24 | Central de controle / Mission Control | Implementado inicial | `/api/v1/mission-control` agrega indicadores e a home renderiza Mission Control apos login | Falta aprofundar indicadores por risco/veiculo e fluxo operacional completo |
| 25 | Prioridade automatica | Preparado | Severidade/prioridade em Alert, Insight e Task | Falta algoritmo central considerando impacto, prazo, probabilidade e criticidade |
| 26 | Central de acoes | Parcial | Aba `Acoes` lista tarefas abertas e aprovacoes pendentes com aprovar/rejeitar | Falta resolver/criar OS/adiar/ignorar/ver detalhes como comandos transacionais |
| 27 | Relatorios de preventiva | Parcial | Relatorio executivo e custo por veiculo existem | Falta relatorios especificos de preventivas, falhas recorrentes e indisponibilidade |
| 28 | Calendario de preventivas | Parcial | Aba `Calendario` mostra planos ativos e vencimentos de tacografo | Falta incluir oleo, pneus, documentos, filtros e visualizacao mensal/semanal |
| 29 | Financeiro | Parcial | `financial_transactions`, `cost_centers`, fornecedores, combustivel e NF | Falta contas a receber, contratos e financiamentos |
| 30 | Notas fiscais | Parcial | `invoices`, vinculos, flags de pendencia/duplicidade | Falta upload real, OS sem nota e divergencia de valor |
| 31 | Custo por veiculo | Parcial | `/api/v1/reports/vehicles/{vehicle}/costs` | Falta seguros, impostos, financiamento, mao de obra detalhada e custo/km real quando sem KM historico |
| 32 | TCO | Parcial | Relatorio inicial soma custos operacionais | Falta aquisicao, depreciacao, financiamento, rankings e disponibilidade |
| 33 | Motor de regras | Parcial | `rules`, `rule_executions`, `RuleEngine` | Falta biblioteca de condicoes/açoes completa e execucao automatica por evento |
| 34 | Workflow engine | Implementado inicial | `rule_executions`, tasks, notifications, evidences, outbox, aprovar/rejeitar execucoes e tela de pendencias | Falta SLA por estado e historico detalhado de transicoes |
| 35 | Automacao | Parcial | Rule engine/outbox executam tarefa, notificacao interna, bloqueio/liberacao de veiculo e OS automatica | Falta preventiva automatica, relatorio automatico e notificacao externa real |
| 36 | Event bus + outbox | Implementado inicial | `outbox`, `OutboxPublisher`, `OutboxProcessor`, comando `outbox:process`, retry e dead-letter | Falta adaptadores reais de entrega externa para webhook/push/e-mail |
| 37 | Auditoria | Parcial | `audit_logs`, `Auditor`, relatorio/export CSV | Falta append-only reforcado em nivel de banco para eventos criticos |
| 38 | Notificacoes | Parcial | `notifications` internas e acao `notify_internal` | Falta e-mail, push, webhook e preparacao WhatsApp operacional |
| 39 | Tarefas | Implementado inicial | `tasks`, rotas, criacao a partir de alerta | Falta workflow completo de SLA, responsavel e acoes padronizadas |
| 40 | Fotos e evidencias | Parcial | `evidences`, `evidence_paths`, `photo_paths` | Falta upload seguro, videos/documentos reais e storage externo |
| 41 | App mobile | Preparado | API de checklist, tarefas, OS, veiculos e notificacoes | Falta app mobile real e endpoints otimizados para jornada do motorista |
| 42 | API | Parcial | Rotas v1 para principais modulos e OpenAPI | Faltam aliases/recursos dedicados para `/maintenance`, `/preventive`, `/intelligence`, `/audit` conforme nomenclatura do prompt |
| 43 | Seguranca | Parcial | Auth token, RBAC, middleware, tenant scope, validacoes e handler Vercel preservando status HTTP | Falta rate limiting refinado, policies por modelo e protecao real de uploads |
| 44 | Multiempresa | Implementado inicial | group/company/branch em tabelas e scopes | Reforcar testes negativos em todos os endpoints e constraints de isolamento |
| 45 | Dashboard executivo | Implementado inicial | Aba `Relatorios` consome `/api/v1/reports/executive` e mostra indicadores principais | Falta indicadores completos de disponibilidade, custo/km, documentos, OS e riscos por periodo |
| 46 | Relatorio de auditoria | Parcial | `/api/v1/audit-logs` e CSV | Falta PDF e Excel |
| 47 | Escalabilidade | Parcial | Indices, paginacao, filas/tabelas, tenant scopes, outbox com lock transacional e scheduler | Falta cache aplicado e workers reais no ambiente de execucao continuo |
| 48 | Observabilidade | Parcial | `/api/health` e `/api/ready` com banco, fila, outbox pendente e dead-letter | Falta monitorar Redis, jobs, integracoes, erros e performance |
| 49 | Testes | Parcial | Testes cobrem API, auth, workflow, aprovacao/rejeicao, events, audit, outbox retry/dead-letter, bloqueio, OS automatica e erros Vercel | Falta isolamento negativo em massa |
| 50 | Banco | Parcial | Migrations organizadas para core, inteligencia, workflow, pneus, checklist, financeiro | Faltam tabelas especificas de contratos, seguros, multas, viagens, tracking, telematics, risks/recommendations |
| 51 | Principio de IA | Implementado inicial | Inteligencia deterministica antes de IA generica | Falta camada futura com LLM para resumo/explicacao sem inventar dados |
| 52 | Exemplo de insight final | Parcial | Insight de oleo contem previsao/recomendacao | Falta apresentacao completa com historico/acoes em UI |
| 53 | Exemplo de insight de pneu | Parcial | Insight de pneu por inspecao critica | Falta tendencia historica e acoes UI `criar tarefa/criar OS` integradas |
| 54 | Exemplo de documento | Parcial | Documentos e vencimentos existem | Falta correlacao com programacao/viagens futuras |
| 55 | Exemplo de tacografo | Parcial | Tacografo com validade/alerta/insight | Falta utilizacao alta e viagem programada apos vencimento |
| 56 | Mission Control perguntas-chave | Parcial | Tela inicial responde parte de frota, criticos, preventivas, tacografos, acoes e pneus | Falta custo excessivo, risco por veiculo, manutencao por causa raiz e acoes do dia completas |
| 57 | Roadmap Fases 1-9 | Parcial | Fases 1-9 implementadas como backend/API inicial e deployadas | Algumas fases foram entregues como base inicial, nao como produto operacional completo |
| 58 | Regra para o Codex | Parcial | Analise/docs, fases, migrations, testes e deploys realizados; sem apagar funcionalidades | A apresentacao formal de plano/migrations antes de cada microfase pode ser reforcada em proximas entregas |
| 59 | Objetivo final | Parcial | Plataforma ja centraliza varios dados e gera alertas/insights deterministas | Ainda falta frontend, integracoes e automacoes para virar copiloto operacional completo |

## Pendencias prioritarias para fechar o prompt mestre

1. Completar documentos/licencas/seguros/contratos/multas/viagens/telemetria.
2. Implementar upload seguro e storage de evidencias.
3. Ampliar relatorios: preventiva, calendario completo, auditoria PDF/Excel, rankings TCO.
4. Fortalecer observabilidade: Redis, jobs, erros, performance e integracoes.
5. Expandir testes negativos de tenant/RBAC e testes de automacoes criticas.
