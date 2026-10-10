# PROMPT MESTRE PARA CODEX

## Sistema Enterprise de Gestão Autônoma de Frotas — Fleet Intelligence

Você é responsável por projetar e implementar uma plataforma profissional de **Gestão Inteligente e Autônoma de Frotas**, inicialmente para uma empresa com aproximadamente 30 veículos leves e pesados, mas com arquitetura preparada para crescer para centenas ou milhares de veículos.

O sistema deve centralizar a operação da frota e transformar dados dispersos em:

```text
DADOS
→ EVENTOS
→ ALERTAS
→ RISCOS
→ INSIGHTS
→ PREVENTIVAS
→ ORDENS DE SERVIÇO
→ AÇÕES
→ AUDITORIA
```

O sistema não deve ser apenas um CRUD de veículos.

Ele deve funcionar como um **Fleet Intelligence / Fleet Operations Platform**, capaz de monitorar continuamente a frota e informar ao gestor o que precisa ser feito antes que o problema aconteça.

---

# 1. STACK OBRIGATÓRIA

Utilizar:

* PHP 8.4+
* Laravel
* PostgreSQL
* Redis
* Laravel Queue
* Laravel Scheduler
* REST API
* OpenAPI
* Docker
* Docker Compose

Frontend:

* Blade + Livewire ou
* React/Vue integrado ao Laravel

Escolher a abordagem que proporcione melhor manutenção e produtividade, mas manter a API desacoplada para integração futura com aplicativos móveis.

---

# 2. ARQUITETURA

Utilizar inicialmente um **Modular Monolith**, mas com fronteiras arquiteturais suficientemente claras para permitir evolução futura para microsserviços.

Separar os seguintes domínios:

```text
Fleet
Vehicles
Drivers
Documents
Compliance
Maintenance
PreventiveMaintenance
WorkOrders
Tires
Fuel
Inventory
Suppliers
Financial
Invoices
Contracts
Insurance
Fines
Checklists
Operations
Trips
Tracking
Telematics
Notifications
Tasks
Workflow
Rules
Intelligence
MissionControl
Reports
Audit
Integrations
Administration
```

Não permitir que controllers concentrem regras de negócio.

Utilizar:

```text
Domain
Application
Infrastructure
Interfaces
```

quando aplicável.

---

# 3. PRINCIPAL DIFERENCIAL

Criar um núcleo chamado:

# PREVENTIVE INTELLIGENCE ENGINE

Esse mecanismo deverá analisar continuamente:

* quilometragem;
* horímetro;
* tempo;
* histórico;
* checklist;
* manutenção;
* pneus;
* óleo;
* filtros;
* documentos;
* tacógrafo;
* licenças;
* seguros;
* combustível;
* ocorrências;
* utilização do veículo;
* custos;
* padrões históricos.

O sistema não deve esperar o vencimento.

Ele deve prever o vencimento.

---

# 4. CONCEITO DE ALERTA

Todo alerta deverá possuir:

```text
id
tipo
categoria
entidade
entidade_id
severidade
data
data_prevista
prazo
origem
regra
mensagem
recomendacao
status
responsavel
evidencias
created_at
updated_at
```

Severidades:

```text
INFO
ATENCAO
MEDIO
ALTO
CRITICO
```

Status:

```text
ABERTO
VISUALIZADO
EM_ANALISE
EM_EXECUCAO
RESOLVIDO
IGNORADO
CANCELADO
```

---

# 5. ALERTAS DE ÓLEO

Criar controle de troca de óleo.

Registrar:

* veículo;
* motor;
* tipo de óleo;
* viscosidade;
* fabricante;
* KM da troca;
* data;
* KM prevista;
* intervalo configurado;
* próxima troca;
* fornecedor;
* nota fiscal;
* custo;
* OS.

O sistema deve calcular:

```text
KM desde a troca
KM restante
percentual consumido
dias desde a troca
média diária de KM
previsão de vencimento
```

Exemplo:

```text
Veículo ABC-1234

Última troca:
KM 120.000

Próxima troca:
KM 130.000

Atual:
KM 129.100

Consumido:
91%

Restante:
900 KM

Média:
210 KM/dia

Previsão:
4,3 dias
```

Gerar automaticamente:

```text
ALERTA
Óleo próximo do vencimento.
```

E:

```text
INSIGHT
Mantendo a utilização atual, a troca deverá ocorrer em aproximadamente 4 dias.
Recomenda-se programar a preventiva antes desse período.
```

---

# 6. PREVENTIVA POR KM

Permitir configurar manutenções:

```text
5.000 KM
10.000 KM
20.000 KM
30.000 KM
50.000 KM
100.000 KM
```

O intervalo deve ser configurável por:

* veículo;
* modelo;
* fabricante;
* componente;
* plano de manutenção.

Exemplos:

```text
Troca de óleo
Filtros
Freios
Correias
Suspensão
Lubrificação
Revisão geral
```

---

# 7. PREVENTIVA POR TEMPO

Permitir regras:

```text
7 dias
15 dias
30 dias
3 meses
6 meses
12 meses
24 meses
```

Exemplo:

```text
Seguro vence em 30 dias.
Licenciamento vence em 20 dias.
Tacógrafo vence em 45 dias.
```

Criar automaticamente alertas e tarefas.

---

# 8. PREVENTIVA POR HORÍMETRO

Para veículos e equipamentos que utilizam horímetro.

Exemplo:

```text
Atual:
4.820 h

Preventiva:
5.000 h

Restante:
180 h
```

Prever o momento provável do serviço com base na utilização histórica.

---

# 9. TACÓGRAFO

Criar módulo específico para controle de tacógrafos.

Registrar:

* veículo;
* número;
* fabricante;
* modelo;
* instalação;
* aferição;
* certificado;
* validade;
* manutenção;
* histórico.

Alertas:

```text
90 dias
60 dias
30 dias
15 dias
7 dias
vencido
```

Criar insight:

> Tacógrafo do veículo ABC-1234 vencerá em 18 dias. Pela programação operacional, o veículo possui viagens previstas após a data de vencimento. Recomenda-se antecipar a aferição.

---

# 10. LICENÇAS E DOCUMENTAÇÃO

Monitorar:

* CRLV;
* licenciamento;
* ANTT;
* TAC;
* seguros;
* certificados;
* documentos estaduais;
* documentos municipais;
* autorizações;
* contratos;
* documentos dos motoristas.

Criar calendário de vencimentos.

Cada documento deverá possuir:

```text
data_emissao
data_vencimento
status
responsavel
arquivo
entidade
entidade_id
```

---

# 11. DOCUMENTOS DOS MOTORISTAS

Monitorar:

* CNH;
* categoria;
* validade;
* exames;
* certificados;
* treinamentos;
* documentos obrigatórios.

Criar alertas preventivos.

---

# 12. PNEUS

Criar módulo completo de pneus.

Controlar:

* número de fogo;
* fabricante;
* modelo;
* medida;
* DOT;
* data de compra;
* custo;
* KM;
* posição;
* sulco;
* pressão;
* vida;
* recapagem;
* histórico.

Status:

```text
NOVO
ESTOQUE
INSTALADO
REMOVIDO
INSPECAO
RECAPAGEM
RECAPADO
RESERVADO
DANIFICADO
FIM_DE_VIDA
DESCARTADO
```

---

# 13. PREVENTIVA DE PNEUS

O sistema deverá acompanhar:

* profundidade do sulco;
* desgaste;
* desgaste irregular;
* pressão;
* KM;
* idade;
* recapagem;
* posição;
* histórico.

Criar alertas:

```text
Pneu próximo do limite.
Pneu com desgaste anormal.
Pneu com baixa vida útil.
Pneu próximo da recapagem.
Pneu com idade elevada.
```

---

# 14. FOTO DO PNEU

O app de checklist deverá permitir fotografar o pneu.

A imagem deverá ser vinculada a:

```text
veículo
pneu
posição
motorista
checklist
inspeção
data
hora
localização
```

Criar histórico visual.

Permitir comparar imagens de inspeções anteriores.

---

# 15. VISÃO COMPUTACIONAL — PREPARAR ARQUITETURA

Não implementar necessariamente um modelo de IA na primeira versão.

Porém, criar arquitetura para futura análise automática de imagens.

Exemplos:

```text
imagem do pneu
↓
serviço de análise
↓
estimativa de desgaste
↓
confiança
↓
insight
```

Possíveis análises futuras:

* desgaste;
* cortes;
* deformações;
* danos;
* vazamentos;
* componentes visivelmente anormais.

A análise visual deve ser tratada como **assistência**, não como diagnóstico mecânico definitivo.

---

# 16. CHECKLIST

Integrar com o aplicativo de checklist.

O checklist deve poder gerar eventos:

```text
checklist.completed.v1
checklist.nonconformity.created.v1
checklist.vehicle.blocked.v1
checklist.vehicle.released.v1
```

Exemplo:

```text
Motorista executa checklist
↓
Pneu apresenta problema
↓
Foto anexada
↓
Não conformidade
↓
Risco CRÍTICO
↓
Sistema cria OS
↓
Sistema cria tarefa
↓
Gestor é notificado
```

---

# 17. BLOQUEIO AUTOMÁTICO

Criar possibilidade de regras que bloqueiem o veículo.

Exemplo:

```text
SE pneu = crítico
ENTÃO veículo = BLOQUEADO
```

```text
SE documento obrigatório = vencido
ENTÃO veículo = BLOQUEADO
```

```text
SE checklist crítico = verdadeiro
ENTÃO impedir liberação
```

Toda regra deverá ser configurável e auditável.

---

# 18. ORDEM DE SERVIÇO AUTOMÁTICA

Qualquer evento poderá gerar uma OS.

Exemplo:

```text
Checklist
↓
Problema
↓
Regra
↓
OS
```

Ou:

```text
Preventiva
↓
Data/KM atingida
↓
OS automática
```

Ou:

```text
Insight
↓
Gestor aprova
↓
OS
```

---

# 19. MOTOR DE INSIGHTS

Criar uma entidade:

```text
Insight
```

Campos:

```text
tipo
titulo
descricao
evidencias
impacto
probabilidade
confianca
prioridade
recomendacao
entidade
entidade_id
status
```

Tipos:

```text
PREVENTIVO
CUSTO
MANUTENCAO
PNEU
COMBUSTIVEL
DOCUMENTAL
OPERACIONAL
FINANCEIRO
RISCO
PERFORMANCE
```

---

# 20. INSIGHTS PREDITIVOS

Exemplo:

```text
INSIGHT PREVENTIVO

Veículo:
ABC-1234

Componente:
Óleo

Situação:
91% do intervalo consumido.

Previsão:
4 dias.

Recomendação:
Programar troca de óleo.

Impacto:
Evitar manutenção fora de planejamento.

Confiança:
92%
```

---

# 21. INSIGHTS DE MANUTENÇÃO

Detectar:

* repetição da mesma falha;
* aumento de custo;
* aumento de frequência;
* manutenção acima da média;
* veículo com alta indisponibilidade.

Exemplo:

```text
Veículo ABC-1234

3 manutenções relacionadas ao sistema de freio
nos últimos 90 dias.

Custo:
R$ 4.820

Média da frota:
R$ 1.900

Insight:
Investigar causa raiz.
```

---

# 22. INSIGHTS DE CUSTO

Comparar:

```text
veículo
modelo
categoria
filial
fornecedor
período
```

Detectar:

```text
custo acima da média
custo crescente
custo anormal
custo recorrente
```

---

# 23. INSIGHTS DE COMBUSTÍVEL

Detectar:

* consumo anormal;
* abastecimentos duplicados;
* queda de média;
* aumento de custo;
* divergência de KM;
* abastecimento incompatível.

---

# 24. CENTRAL DE CONTROLE

Criar uma tela:

# MISSION CONTROL

Primeiro elemento da aplicação após login para gestores.

Mostrar:

```text
FROTA
30 veículos

ATIVOS
27

PARADOS
3

CRÍTICOS
2

PREVENTIVAS
5

DOCUMENTOS
8 vencendo

PNEUS
3 em atenção

ÓLEO
4 próximos

TACÓGRAFO
2 próximos

OS
6 abertas
2 atrasadas
```

---

# 25. PRIORIDADE AUTOMÁTICA

Calcular prioridade considerando:

```text
severidade
impacto financeiro
risco operacional
prazo
probabilidade
criticidade do veículo
```

Ordenar automaticamente as ações.

---

# 26. CENTRAL DE AÇÕES

Mostrar:

```text
AÇÃO NECESSÁRIA

1. Agendar troca de óleo — ABC-1234
2. Regularizar documento — DEF-5678
3. Aferir tacógrafo — GHI-9012
4. Inspecionar pneu — JKL-3456
5. Aprovar OS — MNO-7890
```

Cada item deverá possuir:

```text
[RESOLVER]
[CRIAR OS]
[ADIAR]
[IGNORAR]
[VER DETALHES]
```

---

# 27. RELATÓRIOS DE PREVENTIVA

Criar relatórios:

* preventivas realizadas;
* preventivas atrasadas;
* próximas preventivas;
* custo de preventivas;
* custo por veículo;
* custo por componente;
* falhas recorrentes;
* indisponibilidade.

---

# 28. CALENDÁRIO DE PREVENTIVAS

Criar calendário visual.

Mostrar:

```text
Óleo
Pneus
Freios
Revisão
Tacógrafo
Licenciamento
Seguro
Documentos
```

Permitir filtros por:

* veículo;
* filial;
* responsável;
* tipo;
* prioridade.

---

# 29. FINANCEIRO

Controlar:

* contas a pagar;
* contas a receber;
* despesas;
* receitas;
* notas;
* fornecedores;
* centros de custo;
* categorias;
* contratos;
* financiamentos.

Relacionar custos diretamente com:

```text
veículo
OS
pneu
combustível
fornecedor
nota
centro de custo
```

---

# 30. NOTAS FISCAIS

Permitir:

* upload;
* cadastro;
* classificação;
* vínculo com fornecedor;
* vínculo com OS;
* vínculo com veículo;
* centro de custo.

Detectar:

```text
nota duplicada
nota sem vínculo
nota sem centro de custo
valor divergente
OS sem nota
```

---

# 31. CUSTO POR VEÍCULO

Calcular:

```text
combustível
manutenção
pneus
seguros
documentos
impostos
financiamento
peças
mão de obra
```

Mostrar:

```text
Custo mensal
Custo anual
Custo acumulado
Custo/KM
```

---

# 32. TCO

Implementar:

# TOTAL COST OF OWNERSHIP

Calcular:

```text
aquisição
depreciação
financiamento
combustível
manutenção
pneus
seguro
impostos
documentação
```

Criar ranking:

```text
VEÍCULOS MAIS CAROS
VEÍCULOS MAIS EFICIENTES
VEÍCULOS COM MAIOR CUSTO/KM
VEÍCULOS COM MAIOR DISPONIBILIDADE
```

---

# 33. MOTOR DE REGRAS

Criar Rule Engine configurável.

Exemplos:

```text
SE óleo > 80%
ENTÃO alerta = ATENCAO
```

```text
SE óleo > 95%
ENTÃO criar preventiva
```

```text
SE documento < 30 dias
ENTÃO criar tarefa
```

```text
SE documento vencido
ENTÃO alerta = CRITICO
```

```text
SE pneu < limite configurado
ENTÃO criar inspeção
```

```text
SE custo manutenção > média + 30%
ENTÃO gerar insight
```

---

# 34. WORKFLOW ENGINE

Criar workflow:

```text
TRIGGER
↓
CONDIÇÃO
↓
AÇÃO
↓
NOTIFICAÇÃO
↓
TAREFA
↓
APROVAÇÃO
↓
EXECUÇÃO
↓
AUDITORIA
```

---

# 35. AUTOMAÇÃO

O sistema deve ser capaz de:

* criar alerta;
* criar tarefa;
* criar preventiva;
* criar OS;
* solicitar aprovação;
* notificar responsável;
* bloquear veículo;
* gerar relatório;
* registrar auditoria.

Nenhuma automação crítica deverá ocorrer sem regra explícita e auditável.

---

# 36. EVENT BUS + OUTBOX

Implementar:

```text
Domain Events
Event Bus
Outbox
Idempotency
Retry
Dead Letter
```

Eventos versionados.

Exemplos:

```text
vehicle.created.v1
maintenance.due.v1
maintenance.order.created.v1
maintenance.order.completed.v1
oil.due.v1
tire.inspection.required.v1
document.expiring.v1
document.expired.v1
tachograph.expiring.v1
checklist.nonconformity.created.v1
invoice.received.v1
```

---

# 37. AUDITORIA

Registrar absolutamente todas as operações relevantes.

Campos:

```text
usuario
data
hora
IP
entidade
acao
valor_anterior
valor_novo
origem
```

Auditoria deve ser append-only para eventos críticos.

---

# 38. NOTIFICAÇÕES

Criar:

* notificações internas;
* e-mail;
* push;
* webhook.

Preparar arquitetura para integração futura com WhatsApp.

---

# 39. TAREFAS

Todo alerta acionável deverá poder gerar uma tarefa.

Exemplo:

```text
TAREFA

Trocar óleo

Veículo:
ABC-1234

Responsável:
Gestor de manutenção

Prazo:
25/08/2026

Origem:
Preventive Intelligence Engine

Prioridade:
ALTA
```

---

# 40. FOTOS E EVIDÊNCIAS

Permitir anexar:

* fotos;
* vídeos;
* documentos;
* PDFs;
* notas;
* comprovantes.

Vincular evidências a:

```text
veículo
checklist
OS
pneu
manutenção
ocorrência
documento
insight
```

---

# 41. APP MOBILE

Criar API preparada para o aplicativo.

O app deverá poder:

* executar checklist;
* tirar fotos;
* registrar KM;
* registrar ocorrências;
* consultar tarefas;
* visualizar OS;
* receber notificações;
* consultar veículo.

A base oficial de veículos será o backend central.

Não duplicar cadastro de veículos no aplicativo.

---

# 42. API

Criar endpoints:

```text
/api/v1/vehicles
/api/v1/drivers
/api/v1/documents
/api/v1/checklists
/api/v1/non-conformities
/api/v1/maintenance
/api/v1/work-orders
/api/v1/preventive
/api/v1/oil
/api/v1/tires
/api/v1/fuel
/api/v1/inventory
/api/v1/invoices
/api/v1/financial
/api/v1/alerts
/api/v1/tasks
/api/v1/insights
/api/v1/intelligence
/api/v1/mission-control
/api/v1/audit
```

Documentar tudo com OpenAPI.

---

# 43. SEGURANÇA

Implementar:

* autenticação;
* RBAC;
* policies;
* validação;
* rate limiting;
* logs;
* auditoria;
* proteção de uploads;
* controle de acesso por empresa/filial.

Preparar multiempresa.

---

# 44. MULTIEMPRESA

Arquitetura:

```text
GRUPO
 ├── EMPRESA
 │    ├── FILIAL
 │    │    ├── VEÍCULOS
 │    │    ├── MOTORISTAS
 │    │    └── OPERAÇÃO
```

Nenhum usuário poderá visualizar dados fora do seu escopo.

---

# 45. DASHBOARD EXECUTIVO

Criar indicadores:

```text
Disponibilidade
Custo/KM
Manutenção
Combustível
Pneus
Preventivas
Documentos
OS
Financeiro
Riscos
```

---

# 46. RELATÓRIO DE AUDITORIA

Permitir gerar relatórios:

* alterações;
* aprovações;
* exclusões;
* OS;
* documentos;
* custos;
* usuários;
* ações automáticas.

Exportação:

```text
PDF
Excel
CSV
```

---

# 47. ESCALABILIDADE

O sistema deverá suportar crescimento sem alteração estrutural.

Não utilizar:

* regras hardcoded;
* limites fixos;
* consultas ineficientes;
* lógica duplicada;
* acesso direto indiscriminado às tabelas.

Utilizar:

* índices;
* cache;
* filas;
* jobs;
* paginação;
* processamento assíncrono;
* eventos;
* locks quando necessário.

---

# 48. OBSERVABILIDADE

Implementar:

```text
/health
/ready
```

Monitorar:

* banco;
* Redis;
* filas;
* jobs;
* integrações;
* erros;
* performance.

---

# 49. TESTES

Criar testes:

```text
Unit
Feature
Integration
API
Authorization
Workflow
Rules
Events
Idempotency
Audit
```

Testar especialmente:

* criação automática de OS;
* geração de alertas;
* previsão de preventiva;
* bloqueio de veículo;
* permissões;
* eventos;
* auditoria.

---

# 50. BANCO

Criar migrations organizadas.

Principais tabelas:

```text
vehicles
drivers
vehicle_documents
driver_documents

maintenance_plans
maintenance_records
maintenance_orders
maintenance_items

oil_services
oil_intervals

tires
tire_inspections
tire_movements
tire_positions

checklist_templates
checklist_executions
checklist_responses
non_conformities
evidences

alerts
tasks
insights
risks
recommendations

rules
rule_executions
workflows
workflow_executions

invoices
financial_transactions
cost_centers

suppliers
inventory_items
inventory_movements

events
outbox
audit_logs
notifications
```

---

# 51. PRINCÍPIO DE IA

Não implementar uma IA genérica apenas para conversar.

Primeiro construir inteligência determinística baseada nos dados reais.

O sistema deve saber explicar:

```text
POR QUE GEROU O ALERTA?
QUAL DADO ORIGINOU?
QUAL REGRA FOI APLICADA?
QUAL É O RISCO?
QUAL É O IMPACTO?
QUAL É A RECOMENDAÇÃO?
```

Somente depois preparar integração com modelos de IA para:

* resumo executivo;
* explicação de indicadores;
* análise de documentos;
* interpretação de tendências;
* geração de relatórios;
* assistência ao gestor.

A IA nunca deverá inventar dados.

---

# 52. EXEMPLO DE INSIGHT FINAL

O sistema deverá ser capaz de apresentar algo semelhante a:

```text
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
INSIGHT PREVENTIVO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🚛 Veículo: ABC-1234

COMPONENTE
Óleo do motor

SITUAÇÃO
9.180 / 10.000 KM

CONSUMO DO INTERVALO
91,8%

PREVISÃO
4 dias

HISTÓRICO
3 trocas realizadas dentro do intervalo.

RISCO
Médio

IMPACTO
Possível parada não planejada.

RECOMENDAÇÃO
Programar troca de óleo antes de
atingir 10.000 KM.

AÇÃO

[CRIAR ORDEM DE SERVIÇO]
[AGENDAR]
[VER HISTÓRICO]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

# 53. EXEMPLO DE INSIGHT DE PNEU

```text
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ALERTA DE PNEU
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🚛 ABC-1234
Posição: Dianteiro esquerdo

Sulco:
3,4 mm

Limite configurado:
3,0 mm

Tendência:
Desgaste acima da média

Histórico:
Desgaste irregular detectado
em 2 inspeções anteriores.

RECOMENDAÇÃO:
Inspecionar alinhamento e suspensão.

[CRIAR TAREFA]
[CRIAR OS]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

# 54. EXEMPLO DE DOCUMENTO

```text
DOCUMENTO VENCENDO

Veículo:
DEF-5678

Documento:
Licenciamento

Vencimento:
18 dias

Programação:
Veículo possui viagem após vencimento.

RISCO:
CRÍTICO

AÇÃO RECOMENDADA:
Regularizar documento antes da viagem.

[RESOLVER]
```

---

# 55. EXEMPLO DE TACÓGRAFO

```text
TACÓGRAFO

Veículo:
GHI-9012

Validade:
23 dias

Utilização:
Alta

Previsão operacional:
Viagem programada após vencimento.

INSIGHT:
Antecipar aferição para evitar
indisponibilidade operacional.
```

---

# 56. MISSION CONTROL

A tela principal deverá responder:

```text
O QUE ESTÁ ERRADO?
O QUE ESTÁ PARA VENCER?
O QUE VAI VENCER?
O QUE ESTÁ CUSTANDO DEMAIS?
O QUE PRECISA DE MANUTENÇÃO?
QUAL VEÍCULO ESTÁ EM RISCO?
O QUE EU PRECISO FAZER HOJE?
```

---

# 57. ROADMAP

Implementar em etapas.

## FASE 1

Core:

* empresas;
* filiais;
* usuários;
* RBAC;
* veículos;
* motoristas;
* documentos;
* auditoria.

## FASE 2

Manutenção:

* preventiva;
* corretiva;
* OS;
* oficinas;
* peças;
* estoque.

## FASE 3

Preventive Intelligence:

* óleo;
* KM;
* horímetro;
* documentos;
* tacógrafo;
* licenças;
* alertas;
* tarefas.

## FASE 4

Pneus:

* lifecycle;
* inspeções;
* posições;
* fotos;
* desgaste;
* recapagem.

## FASE 5

Checklist:

* API;
* evidências;
* fotos;
* não conformidades;
* bloqueios.

## FASE 6

Financeiro:

* notas;
* despesas;
* custos;
* TCO;
* fornecedores.

## FASE 

Intelligence:

* baseline;
* tendências;
* anomalias;
* riscos;
* insights;
* recomendações.

## FASE 8

Mission Control:

* central de controle;
* prioridades;
* ações;
* automações.

## FASE 9

Integrações:

* app mobile;
* rastreamento;
* telemetria;
* sistemas fiscais;
* APIs externas.

---

# 58. REGRA PARA O CODEX

ANTES DE CODIFICAR:

1. analisar o repositório;
2. analisar arquitetura existente;
3. identificar banco;
4. identificar entidades existentes;
5. verificar se existe cadastro de veículos;
6. verificar se existe app de checklist;
7. não duplicar entidades existentes;
8. apresentar plano de implementação;
9. apresentar migrations;
10. implementar por fases;
11. executar testes;
12. corrigir problemas;
13. somente então avançar.

Não apagar funcionalidades existentes.

Não criar dados falsos para simular funcionalidades.

Não criar telas sem backend real.

Não criar APIs que não estejam conectadas ao domínio.

Não criar regras de negócio diretamente dentro de controllers.

Toda funcionalidade crítica deverá possuir testes.

---

# 59. OBJETIVO FINAL

Construir um sistema no qual o gestor de uma frota de 30 veículos não precise ficar procurando informações em:

* planilhas;
* WhatsApp;
* e-mail;
* documentos físicos;
* aplicativos diferentes;
* sistemas separados.

A plataforma deverá concentrar tudo e funcionar como um **copiloto operacional da frota**.

O gestor deverá receber informações como:

> "Seu veículo ABC-1234 provavelmente precisará de troca de óleo em 4 dias."

> "O pneu dianteiro está apresentando desgaste anormal."

> "O tacógrafo vence em 23 dias e existe uma viagem prevista depois do vencimento."

> "O licenciamento vence em 18 dias."

> "Este veículo gastou 34% acima da média da frota."

> "Existem 3 ordens de serviço atrasadas."

> "Esta manutenção apresenta reincidência."

E, principalmente:

> **"Aqui está o problema. Aqui está a evidência. Aqui está o impacto. Aqui está o que recomendamos fazer."**

O sistema deverá evoluir progressivamente de um software de gestão para uma **plataforma autônoma de inteligência operacional de frota**, mantendo o gestor no controle das decisões críticas e automatizando tarefas repetitivas, previsíveis e auditáveis.

# PRIMEIRA ENTREGA

Não implementar todo o sistema de uma vez.

Primeiro entregar:

1. análise do projeto existente;
2. arquitetura;
3. mapa dos módulos;
4. modelo de dados;
5. bounded contexts;
6. estratégia do Preventive Intelligence Engine;
7. estratégia de eventos;
8. estratégia de auditoria;
9. estratégia de integração com checklist;
10. roadmap;
11. riscos técnicos;
12. plano de testes.

Depois implementar a **FASE 1**.

Ao finalizar a FASE 1:

```text
executar testes
↓
corrigir falhas
↓
verificar migrations
↓
verificar API
↓
verificar autorização
↓
verificar auditoria
↓
documentar
```

Somente após a FASE 1 estar estável avançar para a FASE 2.

**Não sacrificar arquitetura, segurança, rastreabilidade ou integridade dos dados em favor de velocidade de implementação.**
