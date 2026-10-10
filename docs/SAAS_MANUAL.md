# FrotaCerta SaaS Manual

## Objetivo

Este manual descreve como operar o FrotaCerta como um SaaS multiempresa de gestao de frotas. Ele complementa o README e consolida o fluxo para desenvolvimento local, provisionamento de tenant, seguranca, validacao e deploy.

## Produto

O FrotaCerta resolve controle operacional de frotas corporativas:

- cadastro de grupos, empresas, filiais, usuarios, veiculos e motoristas;
- documentos, contratos, seguros e tacografos;
- manutencao preventiva, ordens de servico, fornecedores e estoque;
- pneus, checklists, nao conformidades e evidencias;
- abastecimentos, transacoes financeiras, notas fiscais e custo por veiculo;
- alertas, insights, workflow, tarefas e auditoria.

Este repositorio nao contem motor de calculo de frete. Qualquer modulo de frete deve ser tratado como novo dominio e precisa de regras aprovadas antes de implementacao.

## Modelo SaaS

O isolamento logico usa:

- `groups`: grupo economico ou conta principal;
- `companies`: empresa dentro do grupo;
- `branches`: filial/unidade operacional;
- `users`: usuario vinculado a grupo, empresa e opcionalmente filial;
- `roles` e `permissions`: permissoes globais aplicadas ao usuario;
- `api_tokens`: tokens Bearer com hash, expiracao e revogacao.

Regra de acesso:

- usuario com `branch_id` so deve acessar dados daquela filial;
- usuario sem `branch_id` pode operar no escopo da empresa e filtrar por filial valida;
- controllers e servicos devem filtrar por `company_id` e, quando aplicavel, `branch_id`;
- nenhuma protecao deve depender apenas da interface.

## Rodar Local

```bash
docker compose up -d postgres redis
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan fleet:bootstrap \
  --group="Grupo Demo" \
  --company="Empresa Demo" \
  --branch="Matriz" \
  --email=admin@example.com \
  --password='ChangeMe123!'
docker compose run --rm --service-ports app php artisan serve --host=0.0.0.0 --port=8000
```

Abra:

```text
http://localhost:8000
```

Credenciais do exemplo:

```text
admin@example.com
ChangeMe123!
```

## Provisionar Um Novo Tenant

Para cada cliente SaaS, crie um grupo, empresa, filial inicial e administrador:

```bash
docker compose run --rm app php artisan fleet:bootstrap \
  --group="Cliente ACME" \
  --company="ACME Transportes Ltda" \
  --branch="Matriz" \
  --name="Admin ACME" \
  --email=admin@acme.example \
  --password='senha-forte-aqui'
```

Depois, use a API de administracao para criar filiais e usuarios adicionais.

## Variaveis Obrigatorias

Nunca commitar segredos. Configure no provedor:

- `APP_KEY`
- `APP_URL`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=pgsql`
- `DB_URL` ou `DATABASE_URL`
- `DB_SSLMODE=require`
- `SESSION_DRIVER=array`
- `CACHE_STORE=array`
- `QUEUE_CONNECTION=sync`
- `VIEW_COMPILED_PATH=/tmp/frotacerta/views`

## Deploy Seguro

Fluxo recomendado:

1. Rodar CI em pull request.
2. Configurar variaveis do preview/staging.
3. Rodar migrations em banco gerenciado.
4. Executar `fleet:bootstrap` apenas para tenants necessarios.
5. Validar `/api/health` e `/api/ready`.
6. Validar login e Mission Control.
7. Aprovar producao manualmente.
8. Monitorar logs e outbox.

Nao execute deploy de producao se houver vulnerabilidades abertas, testes falhando ou banco sem backup.

## Qualidade

Comandos de validacao:

```bash
docker compose run --rm app composer validate --strict
docker compose run --rm app ./vendor/bin/phpunit
docker compose run --rm app composer audit --no-interaction
npm run build
npm audit --audit-level=low
```

## Pendencias Para SaaS Produção

- validar preview Vercel com Neon real;
- definir estrategia de backup e restore no banco gerenciado;
- adicionar observabilidade externa de erros e logs;
- ampliar testes negativos de isolamento por filial em todos os endpoints;
- formalizar processo de onboarding/offboarding de tenants;
- definir politicas de retencao de auditoria e dados pessoais.
