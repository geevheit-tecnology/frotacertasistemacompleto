# PRD - Product Requirements Document

## 4.1 Visão do produto
FrotaCerta Fleet Intelligence: um sistema completo de gestão inteligente de frotas, atuando de ponta a ponta desde o cadastro até análises financeiras e predições (manutenção preventiva).

## 4.2 Objetivo
Prover uma plataforma SaaS e API First capaz de orquestrar a operação, manutenção, compliance documental e despesas de frotas de veículos corporativos.

## 4.3 Problema resolvido
Falta de visibilidade unificada e preventiva no controle de frotas, gerando altos custos, ineficiência de processos, multas por documentação vencida e falhas mecânicas inesperadas.

## 4.4 Público-alvo
Empresas de transporte, locadoras de veículos, e negócios com frotas próprias, além de gestores de frota, mecânicos e motoristas.

## 4.5 Personas
* **Gestor de Frota**: Focado no Mission Control, redução de custos e garantia de disponibilidade dos veículos.
* **Mecânico/Inspetor**: Executa OS, checklists e inspeções de pneus.
* **Motorista**: Opera os veículos e participa da prestação de contas.

## 4.6 Casos de uso
* Cadastro e gestão de veículos, motoristas, empresas e filiais.
* Inspeção e checklist mobile para veículos antes/depois do uso.
* Abertura e controle de Ordens de Serviço (OS).
* Registro de Abastecimentos e notas fiscais.
* Execução de motor de regras para automação.

## 4.7 Funcionalidades
* **Gestão de Cadastro**: Veículos, motoristas, filiais.
* **Manutenção Preventiva e Corretiva**: Controle de óleo, OS, peças, fornecedores.
* **Inteligência**: Alertas, insights e regras configuráveis.
* **Pneus**: Ciclo de vida e desgaste.
* **Financeiro**: TCO, abastecimentos, notas fiscais, centros de custo.

## 4.8 Regras de negócio
* Um veículo pode ser bloqueado se estiver com documentação vencida (via motor de regras).
* Abastecimentos geram transações financeiras vinculadas.
* Não conformidades em checklists criam tarefas/alertas automaticamente.

## 4.9 Fluxos principais
1. **Inspeção**: Motorista preenche checklist > Não conformidade gera alerta > Mecânico abre OS > OS é finalizada.
2. **Preventiva**: Regra atinge limite de KM > Alerta gerado > Tarefa criada para troca de óleo.
3. **Despesa**: NF é inserida > Vinculada a Veículo/OS > TCO atualizado.

## 4.10 Requisitos funcionais
* API RESTfull JSON para todos os domínios.
* Controle de Acesso Baseado em Roles (RBAC).
* Execução de tarefas em background (Outbox pattern).

## 4.11 Requisitos não funcionais
* Arquitetura Modular Monolith.
* Hospedagem Serverless (Vercel).
* Banco PostgreSQL (Neon).

## 4.12 Segurança
* Autenticação via Token (Bearer).
* Autorização por middleware de permissões.
* Rastreio e auditoria de ações (append-only log).

## 4.13 Performance
* Uso de cache via Redis.
* Processamento assíncrono para regras.

## 4.14 Escalabilidade
* API Stateless com frontend desacoplado.
* Banco serverless preparado para scaling.

## 4.15 Observabilidade
* Logs de erro.
* Healthchecks e endpoints de readiness.
* Mission Control dashboards.

## 4.16 Critérios de aceite
* O sistema deve ser capaz de passar em todos os testes eLint, TypeCheck e CI (já passando).
* Documentação de API atualizada (`openapi/openapi.yaml`).
