# Sprint ERP Forms

## Objetivo

Transformar o FrotaCerta de uma API com Mission Control em um ERP SaaS operacional de frotas, com telas de cadastro, operacao, compliance, manutencao, financeiro, workflow e relatorios.

## Principio De Produto

O FrotaCerta deve permitir que uma empresa opere sua frota pelo navegador sem depender de Postman, scripts ou integracoes externas obrigatorias.

As integracoes continuam importantes, mas o usuario tambem deve conseguir cadastrar e operar manualmente:

- empresas e filiais;
- usuarios;
- veiculos;
- motoristas;
- documentos;
- planos de manutencao;
- troca de oleo;
- tacografos;
- fornecedores;
- pecas e estoque;
- ordens de servico;
- pneus;
- inspecoes de pneus;
- abastecimentos;
- multas;
- transacoes financeiras;
- centros de custo;
- notas fiscais;
- regras e workflows;
- viagens;
- eventos telematicos.

## Arquitetura De Interface

Manter a base atual com Blade, Vite e JavaScript modular, evoluindo sem reescrever o backend.

Estrutura proposta:

```text
resources/js/
  app.js
  api/
    client.js
  components/
    DataTable.js
    EmptyState.js
    Field.js
    FormDrawer.js
    Modal.js
    Select.js
    Toast.js
  modules/
    admin/
    fleet/
    drivers/
    documents/
    maintenance/
    inventory/
    tires/
    financial/
    operations/
    workflow/
    reports/
```

## Layout ERP

Menu lateral:

```text
Mission Control
Administracao
  Empresas
  Filiais
  Usuarios
Frota
  Veiculos
  Motoristas
  Viagens
Documentos
  Documentos de veiculos
  Documentos de motoristas
  Contratos
  Seguros
  Tacografos
Manutencao
  Planos
  Troca de oleo
  Ordens de servico
  Fornecedores
  Pecas / estoque
Pneus
  Pneus
  Inspecoes
Financeiro
  Abastecimentos
  Multas
  Transacoes
  Centros de custo
  Notas fiscais
Workflow
  Regras
  Aprovacoes
  Tarefas
Relatorios
  Custo por veiculo
  Auditoria
```

## Padrao De Tela CRUD

Cada tela deve conter:

- lista com dados reais da API;
- busca simples;
- filtros principais;
- paginacao;
- botao novo;
- formulario em drawer ou modal;
- criacao;
- edicao;
- detalhe quando necessario;
- inativacao ou exclusao conforme regra do dominio;
- loading;
- empty state;
- tratamento de erro;
- feedback por toast;
- responsividade;
- respeito ao token e permissoes.

## Fases

### Fase 1 - Fundacao ERP

Criar:

- layout com sidebar;
- topbar;
- navegacao por modulos;
- cliente API centralizado;
- tratamento global de erro 401/403/422/500;
- toast;
- drawer/modal;
- tabela reutilizavel;
- campos reutilizaveis;
- estados vazios;
- loading skeleton simples.

Entrega:

- Mission Control continua funcionando;
- menu ERP aparece;
- estrutura permite adicionar novas telas sem duplicacao pesada.

### Fase 2 - Administracao

Telas:

- Empresas;
- Filiais;
- Usuarios.

Campos principais:

- Empresa: razao social, nome fantasia, CNPJ ou identificador fiscal, status quando existir.
- Filial: empresa, nome.
- Usuario: nome, email, senha, filial, status.

Entrega:

- onboarding manual de tenant e usuarios pelo navegador.

### Fase 3 - Frota Base

Telas:

- Veiculos;
- Motoristas.

Campos de veiculos:

- placa;
- numero de frota;
- fabricante;
- modelo;
- ano;
- odometro atual;
- horimetro atual;
- status;
- filial.

Campos de motoristas:

- nome;
- documento;
- CNH;
- categoria;
- vencimento CNH;
- status;
- filial.

Entrega:

- Mission Control passa a mostrar dados criados pelo usuario.

### Fase 4 - Manutencao E Estoque

Telas:

- Fornecedores;
- Pecas / estoque;
- Planos de manutencao;
- Troca de oleo;
- Ordens de servico.

Entrega:

- operacao de oficina e preventiva pelo navegador.

### Fase 5 - Financeiro

Telas:

- Abastecimentos;
- Multas;
- Transacoes financeiras;
- Centros de custo;
- Notas fiscais.

Entrega:

- custos por veiculo e indicadores financeiros alimentados pela interface.

### Fase 6 - Compliance

Telas:

- Documentos de veiculos;
- Documentos de motoristas;
- Contratos;
- Seguros;
- Tacografos.

Entrega:

- calendario preventivo e alertas de vencimento ficam operacionais.

### Fase 7 - Pneus

Telas:

- Pneus;
- Inspecoes de pneus;
- movimentacoes de pneus quando a API estiver exposta.

Entrega:

- controle de ciclo de vida de pneus.

### Fase 8 - Operacao

Telas:

- Viagens;
- Eventos telematicos;
- Checklists;
- Evidencias;
- Nao conformidades.

Entrega:

- registro operacional completo da frota.

### Fase 9 - Workflow Inteligente

Telas:

- Regras;
- Execucoes de regras;
- Aprovacoes;
- Tarefas;
- Alertas;
- Insights;
- Notificacoes.

Entrega:

- automacao operacional gerenciavel pelo usuario.

### Fase 10 - Polimento SaaS

Itens:

- permissao por menu;
- onboarding inicial;
- dados demo opcionais;
- exportacao CSV;
- filtros avancados;
- relatorios consolidados;
- logs e observabilidade;
- documentacao final;
- testes E2E dos fluxos principais.

## Sprint Inicial Recomendado

Implementar primeiro:

1. layout ERP com sidebar;
2. API client centralizado;
3. componentes `DataTable`, `FormDrawer`, `Toast` e `Field`;
4. tela de Veiculos;
5. tela de Motoristas;
6. tela de Fornecedores;
7. tela de Centros de custo;
8. validacao manual e deploy.

## Definition Of Done

Uma tela so pode ser marcada como pronta quando:

- lista dados reais da API;
- cria registro;
- edita registro;
- exibe erros de validacao;
- respeita token/login;
- tem loading;
- tem empty state;
- e responsiva;
- nao quebra o Mission Control;
- possui validacao manual documentada ou teste automatizado.

## Riscos

- Crescimento excessivo de JavaScript sem modularizacao.
- Divergencia entre formularios e regras reais da API.
- Falta de testes E2E para fluxos criticos.
- Necessidade futura de migrar para Vue, React ou Inertia se a UI crescer muito.
- Permissoes por menu ainda precisam ser refinadas.

## Proximo Passo

Executar a Fase 1 e, em seguida, implementar Veiculos e Motoristas como padrao para os demais CRUDs.
