# FrotaCerta Fleet Intelligence

Aplicacao Laravel modular para gestao inteligente de frotas.

## Stack

- PHP 8.4
- Laravel 12
- PostgreSQL
- Redis
- Docker Compose
- REST API
- OpenAPI

## Rodar local

Setup completo:

```bash
chmod +x scripts/setup-local.sh
./scripts/setup-local.sh
```

Ou manualmente:

```bash
docker compose up -d postgres redis
docker compose run --rm app php artisan migrate --force
docker compose run --rm --service-ports app php artisan serve --host=0.0.0.0 --port=8000
```

Abra:

```text
http://localhost:8000/api/health
```

## Testes

```bash
docker compose run --rm app php artisan test
docker compose run --rm app php tests/run.php
```

## API

Rotas iniciais publicas:

- `GET /api/health`
- `GET /api/ready`

Autenticacao:

- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`

Rotas autenticadas por Bearer token e permissao:

- `GET /api/v1/companies`
- `POST /api/v1/companies`
- `GET /api/v1/companies/{id}`
- `PATCH /api/v1/companies/{id}`
- `GET /api/v1/branches`
- `POST /api/v1/branches`
- `GET /api/v1/branches/{id}`
- `PATCH /api/v1/branches/{id}`
- `DELETE /api/v1/branches/{id}`
- `GET /api/v1/users`
- `POST /api/v1/users`
- `GET /api/v1/users/{id}`
- `PATCH /api/v1/users/{id}`
- `DELETE /api/v1/users/{id}`
- `GET /api/v1/vehicles`
- `POST /api/v1/vehicles`
- `GET /api/v1/vehicles/{id}`
- `PATCH /api/v1/vehicles/{id}`
- `DELETE /api/v1/vehicles/{id}`
- `GET /api/v1/drivers`
- `POST /api/v1/drivers`
- `GET /api/v1/drivers/{id}`
- `PATCH /api/v1/drivers/{id}`
- `DELETE /api/v1/drivers/{id}`
- `GET /api/v1/vehicle-documents`
- `POST /api/v1/vehicle-documents`
- `GET /api/v1/vehicle-documents/{id}`
- `PATCH /api/v1/vehicle-documents/{id}`
- `DELETE /api/v1/vehicle-documents/{id}`
- `GET /api/v1/driver-documents`
- `POST /api/v1/driver-documents`
- `GET /api/v1/driver-documents/{id}`
- `PATCH /api/v1/driver-documents/{id}`
- `DELETE /api/v1/driver-documents/{id}`
- `GET /api/v1/mission-control`

Permissoes iniciais:

- `administration.manage`
- `fleet.manage`
- `documents.manage`
- `mission-control.view`

## Bootstrap inicial

```bash
docker compose run --rm app php artisan fleet:bootstrap \
  --email=admin@example.com \
  --password='ChangeMe123!'
```

## Neon Postgres

1. Crie um projeto no Neon.
2. Copie a connection string no formato:

```text
postgresql://user:password@host.neon.tech/dbname?sslmode=require
```

3. Aplique migrations e bootstrap no Neon:

```bash
DATABASE_URL='postgresql://...' ./scripts/setup-neon.sh
```

4. Configure o preview Vercel com a mesma connection string:

```bash
DATABASE_URL='postgresql://...' ./scripts/vercel-set-neon-env.sh
```

## Documentacao

- Arquitetura: `docs/02-architecture.md`
- Modelo de dados: `docs/03-data-model.md`
- Manual SaaS: `docs/SAAS_MANUAL.md`
- Preventive Intelligence Engine: `docs/04-preventive-intelligence-engine.md`
- Eventos e auditoria: `docs/05-events-audit.md`
- Controle do Split 01: `docs/08-split-01-execution-control.md`
- OpenAPI: `openapi/openapi.yaml`

## Deploy

O projeto possui configuracao inicial para Vercel serverless:

- `vercel.json`
- `api/index.php`

Para preview/staging/producao, configure variaveis no provedor, sem commitar segredos:

- `APP_KEY`
- `APP_URL`
- `DB_CONNECTION=pgsql`
- `DB_URL` ou `DATABASE_URL`
- `DB_SSLMODE=require`
- `VIEW_COMPILED_PATH=/tmp/frotacerta/views`

O preview sem PostgreSQL hospedado valida a aplicacao e endpoints sem persistencia, como `/api/health`. Endpoints autenticados e persistentes devem receber variaveis `DB_*` de um PostgreSQL gerenciado antes do uso operacional.
