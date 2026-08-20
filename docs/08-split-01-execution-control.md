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
| PostgreSQL gerenciado remoto | Pendente | Vercel preview nao possui `DB_*` configurado |
| Rodar migrations no banco remoto | Pendente | Depende do PostgreSQL gerenciado |
| Bootstrap admin remoto | Pendente | Depende do PostgreSQL gerenciado |

## Proxima fase autorizada pelo roteiro

Somente depois que o banco remoto for configurado, a Fase 1 podera ser considerada operacional fora do ambiente local.

Depois disso, a proxima etapa do split e:

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
