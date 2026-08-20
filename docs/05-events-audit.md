# Eventos e auditoria

## Eventos

Eventos sao versionados e enviados para outbox antes de qualquer publicacao externa.

Padrao:

```text
nome.do.evento.v1
```

Eventos iniciais:

- `vehicle.created.v1`
- `vehicle.updated.v1`
- `vehicle.document.expiring.v1`
- `oil.projection.calculated.v1`
- `alert.created.v1`
- `audit.recorded.v1`

## Outbox

Campos minimos:

- `id`
- `event_name`
- `aggregate_type`
- `aggregate_id`
- `payload`
- `idempotency_key`
- `status`
- `attempts`
- `available_at`
- `published_at`
- `last_error`

## Auditoria

Operacoes criticas devem registrar:

- usuario;
- data/hora;
- IP;
- entidade;
- acao;
- valor anterior;
- valor novo;
- origem.

Auditoria critica deve ser append-only.
