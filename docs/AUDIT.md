# AUDIT

## Estado atual

* **Stack**: PHP 8.4, Laravel 12, PostgreSQL, Redis, Vercel Serverless, TailwindCSS 4, Vite.
* **Arquitetura encontrada**: Modular Monolith com Bounded Contexts.
* **Estrutura do projeto**: Diretório `app` dividido por domínios: Administration, Checklist, Compliance, Console, Financial, Fleet, Http, Intelligence, Models, Providers, Reports, Shared, Support, Workflow.
* **Dependências**: Laravel Framework, TailwindCSS, Vite.
* **Banco**: PostgreSQL (gerenciado via Neon). Migrations em `database/migrations`.
* **APIs**: REST API implementada em `/api/v1`. Autenticação baseada em tokens (Bearer). OpenAPI em `openapi/openapi.yaml`.
* **Frontend**: API-First, com blade templates básicos para entrypoints (Mission Control web local).
* **Integrações**: Preparado para Vercel via `vercel.json` e `api/index.php`.

## Funcionalidades encontradas

* **Administration**: RBAC, usuários, empresas, filiais (existente).
* **Fleet**: Veículos (existente).
* **Drivers**: Motoristas (existente).
* **Documents**: Documentos de veículos e motoristas (existente).
* **Maintenance**: Ordens de serviço, oficinas, peças, estoque (parcialmente implementado/inicial).
* **PreventiveMaintenance**: Regras por KM, tempo, horímetro (existente).
* **Tires**: Cadastro, inspeções, alertas (existente).
* **Checklists**: Templates, execuções, respostas (existente).
* **Intelligence**: Alertas preventivos, insights, contadores do Mission Control (existente).
* **Workflow**: Regras de negócio configuráveis, tarefas, aprovações, automações (existente).
* **Audit**: Auditoria append-only, exportação (existente).
* **Financial**: Centros de custo, transações, abastecimentos, notas fiscais, custo por veículo (existente/inicial).

## Problemas encontrados

### P1 — Alto
* **Testabilidade Local**: O ambiente local atual não possui PHP ou Docker daemon configurado adequadamente, impossibilitando rodar testes localmente de forma autônoma. 

### P3 — Baixo
* **Frontend Design**: O frontend parece básico (entrypoint `Home`), focado mais na API. Necessita revisão visual se for ser o front definitivo.
