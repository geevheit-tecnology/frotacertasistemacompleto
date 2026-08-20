# Integracao com checklist

## Premissa

A base oficial de veiculos sera o backend central. O app mobile nao deve duplicar cadastro de veiculos.

## Eventos aceitos

- `checklist.completed.v1`
- `checklist.nonconformity.created.v1`
- `checklist.vehicle.blocked.v1`
- `checklist.vehicle.released.v1`

## Fluxo alvo

```text
Checklist recebido
-> valida escopo e veiculo
-> persiste execucao e evidencias
-> publica evento versionado
-> regras avaliam risco
-> alerta/tarefa/OS sao criados quando configurado
-> auditoria registra origem e decisao
```

## Fotos

Fotos devem ser evidencias vinculadas a veiculo, checklist, pneu, posicao, motorista, data, hora e localizacao quando disponivel.

Analise visual futura deve ser assistiva, com confianca e evidencias, nunca diagnostico definitivo.
