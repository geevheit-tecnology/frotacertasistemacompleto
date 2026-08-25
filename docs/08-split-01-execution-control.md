# Controle de execucao do Split 01

Este documento rastreia o cumprimento do roteiro definido em `src/01_MASTER_SPLIT.md`.

## Regra de execucao

Nao avancar para Fase 2 enquanto a Fase 1 nao estiver estavel e validada.

## Primeira Entrega

| Item | Status | Evidencia |
| --- | --- | --- |
| Analise do projeto existente | Concluido | `docs/01-project-analysis.md` |
| Arquitetura | Concluido | `docs/02-architecture.md` |
| Mapa dos modulos | Concluido | `docs/02-architecture.md` |
| Modelo de dados | Concluido | `docs/03-data-model.md` |
| Bounded contexts | Concluido | `docs/02-architecture.md` |
| Estrategia do Preventive Intelligence Engine | Concluido | `docs/04-preventive-intelligence-engine.md` |
| Estrategia de eventos | Concluido | `docs/05-events-audit.md` |
| Estrategia de auditoria | Concluido | `docs/05-events-audit.md` |
| Estrategia de integracao com checklist | Concluido | `docs/06-checklist-integration.md` |
| Roadmap | Concluido | `docs/07-roadmap-risks-tests.md` |
| Riscos tecnicos | Concluido | `docs/07-roadmap-risks-tests.md` |
| Plano de testes | Concluido | `docs/07-roadmap-risks-tests.md` |

## Fase 1

Escopo exigido pelo split:

| Item | Status | Evidencia |
| --- | --- | --- |
| Empresas | Implementado | API `/api/v1/companies`, model `Company`, migrations |
| Filiais | Implementado | API `/api/v1/branches`, model `Branch`, migrations |
| Usuarios | Implementado | API `/api/v1/users`, model `User`, migrations |
| RBAC | Implementado | `Role`, `Permission`, middleware `permission` |
| Veiculos | Implementado | API `/api/v1/vehicles`, model `Vehicle` |
| Motoristas | Implementado | API `/api/v1/drivers`, model `Driver` |
| Documentos | Implementado | APIs `vehicle-documents` e `driver-documents` |
| Auditoria | Implementado | `AuditLog`, `Auditor`, create/update/delete |
| Autenticacao API | Implementado | `/api/v1/auth/login`, `/api/v1/auth/me`, `api_tokens` |
| Bootstrap inicial | Implementado | `php artisan fleet:bootstrap` |

## Verificacao da Fase 1

| Verificacao | Status | Evidencia |
| --- | --- | --- |
| Executar testes | Concluido | `php artisan test`: 11 passed, 42 assertions |
| Corrigir falhas | Concluido | Trait UUID e compatibilidade Laravel/Symfony corrigidos |
| Verificar migrations | Concluido local | `migrate:status`: 6 migrations aplicadas |
| Verificar API | Concluido | `route:list --path=api`: 39 rotas |
| Verificar autorizacao | Concluido | Testes de token e permissao |
| Verificar auditoria | Concluido | Testes de create/update/delete auditados |
| Documentar | Concluido | README, OpenAPI, docs do split |

## Pendencias para ambiente remoto

Estas pendencias nao bloqueiam a Fase 1 local, mas bloqueiam uso operacional do preview Vercel:

| Item | Status | Motivo |
| --- | --- | --- |
| PostgreSQL gerenciado remoto | Concluido | Neon `frotacerta`, branch `production` |
| Rodar migrations no banco remoto | Concluido | Migrations aplicadas no Neon |
| Bootstrap admin remoto | Concluido | Admin `getuliomoreira.engsoftware@gmail.com` criado |
| Deploy preview Vercel | Concluido | `https://frotacerta-gqbostqxv-geevheits-projects.vercel.app` |
| Validar API remota | Concluido | `/api/health`, `/api/ready` e login retornaram sucesso |

## Proxima fase autorizada pelo roteiro

Somente depois que o banco remoto for configurado, a Fase 1 podera ser considerada operacional fora do ambiente local.

Fase 1 operacional fora do ambiente local.

## Fase 2

Escopo exigido pelo split:

| Item | Status | Evidencia |
| --- | --- | --- |
| Manutencao preventiva | Implementado inicial | API de trocas de oleo, projecao, alerta e insight |
| Controle de oleo | Implementado inicial | `oil_service_records`, `/api/v1/oil-services`, projecao preditiva |
| Alertas preventivos | Implementado inicial | `alerts`, `/api/v1/alerts`, alerta de oleo por regra |
| Insights preventivos | Implementado inicial | `insights`, `/api/v1/insights`, previsao de troca de oleo |
| Mission Control de alertas | Implementado inicial | Contadores de alertas e insights abertos |
| OS | Implementado inicial | `work_orders`, `/api/v1/work-orders` |
| Oficinas | Implementado inicial | `suppliers`, `/api/v1/suppliers` |
| Pecas | Implementado inicial | `inventory_parts`, `/api/v1/inventory-parts` |
| Estoque | Implementado inicial | `inventory_movements`, baixa por consumo em OS |
| OpenAPI Fase 2 | Implementado inicial | `openapi/openapi.yaml` documenta oleo, OS, fornecedores, estoque, alertas e insights |

Proxima etapa do split:

```text
FASE 2
Manutencao:
- preventiva;
- corretiva;
- OS;
- oficinas;
- pecas;
- estoque.
```

## Fase 3

Escopo inicial implementado a partir dos itens 6, 7, 8, 9, 19, 20 e 24 do split:

| Item | Status | Evidencia |
| --- | --- | --- |
| Preventiva por KM | Implementado inicial | `maintenance_plans.interval_km`, `/api/v1/maintenance-plans` |
| Preventiva por tempo | Implementado inicial | `maintenance_plans.interval_days`, `/api/v1/maintenance-plans` |
| Preventiva por horimetro | Implementado inicial | `maintenance_plans.interval_hours`, `/api/v1/maintenance-plans` |
| Tacografo | Implementado inicial | `tachograph_records`, `/api/v1/tachographs` |
| Alertas de tacografo | Implementado inicial | regra `tachograph-expiration`, janela 90/30/7 dias/vencido |
| Insights documentais | Implementado inicial | insight `DOCUMENTAL` para vencimento de tacografo |
| Mission Control Fase 3 | Implementado inicial | contadores `preventive.active_plans` e `tachographs.*` |
| OpenAPI Fase 3 | Implementado inicial | rotas de planos preventivos e tacografos documentadas |

## Fase 4

Escopo inicial implementado a partir dos itens 33, 34, 35, 36, 38, 39, 40 e 42 do split:

| Item | Status | Evidencia |
| --- | --- | --- |
| Motor de regras | Implementado inicial | `rules`, `/api/v1/rules`, execucao manual auditavel |
| Workflow engine | Implementado inicial | `rule_executions`, `/api/v1/rule-executions` |
| Automacao auditavel | Implementado inicial | regras possuem `requires_approval`, `conditions`, `actions` e execucao idempotente |
| Event bus/outbox | Implementado inicial | evento `rule.executed.v1` gravado em `outbox` |
| Tarefas | Implementado inicial | `tasks`, `/api/v1/tasks`, `POST /api/v1/alerts/{alert}/tasks` |
| Notificacoes internas | Implementado inicial | `notifications`, acao `notify_internal` |
| Evidencias | Implementado inicial | `evidences`, `/api/v1/evidences` para anexos referenciados |
| Mission Control Fase 4 | Implementado inicial | contadores `tasks.open` e `tasks.overdue` |

## Fase 5

Escopo inicial implementado a partir dos itens 16, 40, 41, 42 e 49 do split:

| Item | Status | Evidencia |
| --- | --- | --- |
| Templates de checklist | Implementado inicial | `checklist_templates`, `/api/v1/checklist-templates` |
| Execucao mobile de checklist | Implementado inicial | `checklist_executions`, `/api/v1/checklists` |
| Respostas de checklist | Implementado inicial | `checklist_responses` com severidade e evidencias |
| Nao conformidades | Implementado inicial | `non_conformities`, `/api/v1/non-conformities` |
| Eventos de checklist | Implementado inicial | `checklist.completed.v1` e `checklist.nonconformity.created.v1` no outbox |
| Alerta/tarefa por nao conformidade | Implementado inicial | severidade acionavel cria alerta e tarefa automaticamente |
| API preparada para app mobile | Implementado inicial | checklist registra KM, horimetro, localizacao e evidencias referenciadas |
| Mission Control Fase 5 | Implementado inicial | contadores `checklists.*` |

## Fase 6

Escopo inicial implementado a partir dos itens 12, 13, 14, 15 e 24 do split:

| Item | Status | Evidencia |
| --- | --- | --- |
| Cadastro de pneus | Implementado inicial | `tires`, `/api/v1/tires` |
| Inspecoes de pneus | Implementado inicial | `tire_inspections`, `/api/v1/tire-inspections` |
| Historico/movimento de pneus | Preparado inicial | `tire_movements` |
| Foto/evidencia de pneu | Implementado inicial | `photo_paths` por inspecao |
| Arquitetura para visao computacional | Preparado inicial | `visual_confidence` e `visual_status` sem diagnostico automatico definitivo |
| Alertas preventivos de pneus | Implementado inicial | regra `tire-preventive-inspection` por sulco, pressao e desgaste |
| Insights de pneus | Implementado inicial | insight `PNEU` vinculado ao pneu |
| Mission Control Fase 6 | Implementado inicial | contadores `tires.*` |

## Fase 7

Escopo inicial implementado a partir dos itens 23, 29, 31 e 45 do split:

| Item | Status | Evidencia |
| --- | --- | --- |
| Centros de custo | Implementado inicial | `cost_centers`, `/api/v1/cost-centers` |
| Abastecimentos | Implementado inicial | `fuel_records`, `/api/v1/fuel-records` |
| Financeiro operacional | Implementado inicial | `financial_transactions`, `/api/v1/financial-transactions` |
| Custo por veiculo | Implementado inicial | abastecimento gera transacao financeira vinculada ao veiculo |
| Indicadores executivos | Implementado inicial | Mission Control inclui `fuel.*` e `financial.*` |

## Fase 8

Escopo inicial implementado a partir dos itens 30, 31, 32, 45 e 46 do split:

| Item | Status | Evidencia |
| --- | --- | --- |
| Notas fiscais | Implementado inicial | `invoices`, `/api/v1/invoices` |
| Classificacao/vinculos | Implementado inicial | nota vincula fornecedor, OS, veiculo e centro de custo |
| Deteccao de pendencias | Implementado inicial | flags `MISSING_OPERATIONAL_LINK`, `MISSING_COST_CENTER`, `POSSIBLE_DUPLICATE` |
| Custo por veiculo | Implementado inicial | `/api/v1/reports/vehicles/{vehicle}/costs` |
| TCO inicial | Implementado inicial | soma custos financeiros, notas e combustivel por veiculo |
| Mission Control Fase 8 | Implementado inicial | contadores de notas pendentes e sem vinculo |

## Fase 9

Escopo inicial implementado a partir dos itens 46, 48 e 49 do split:

| Item | Status | Evidencia |
| --- | --- | --- |
| Relatorio de auditoria | Implementado inicial | `/api/v1/audit-logs` |
| Exportacao CSV de auditoria | Implementado inicial | `/api/v1/audit-logs/export.csv` |
| Relatorio executivo | Implementado inicial | `/api/v1/reports/executive` |
| Observabilidade ampliada | Implementado inicial | `/api/ready` retorna banco, fila e pendencias de outbox |
| Permissoes de relatorio/auditoria | Implementado inicial | `reports.view`, `audit.view` |
