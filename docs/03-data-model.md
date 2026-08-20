# Modelo de dados

## Fase 1

Entidades obrigatorias:

- `groups`
- `companies`
- `branches`
- `users`
- `roles`
- `permissions`
- `role_user`
- `permission_role`
- `vehicles`
- `drivers`
- `vehicle_documents`
- `driver_documents`
- `audit_logs`
- `outbox`

## Multiempresa

Toda entidade operacional deve carregar `group_id`, `company_id` e, quando aplicavel, `branch_id`.

Regra: nenhum usuario acessa registros fora do seu escopo. Essa protecao deve existir em policies, queries e actions, nao apenas na interface.

## Indices obrigatorios

- escopo multiempresa: `group_id`, `company_id`, `branch_id`;
- busca operacional: placa, status, vencimentos;
- auditoria: entidade, acao, usuario e data;
- outbox: status, tipo do evento e data de criacao.
