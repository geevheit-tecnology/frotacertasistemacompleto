# Preventive Intelligence Engine

## Principio

Primeiro inteligencia deterministica baseada em dados reais. IA generativa entra depois apenas para explicar, resumir ou apoiar analises, sem inventar dados.

## Pipeline

```text
Dados oficiais
-> Eventos de dominio
-> Regras explicitas
-> Alertas
-> Riscos
-> Insights
-> Tarefas ou OS
-> Auditoria
```

## Primeira regra implementada

O nucleo `OilServiceProjectionCalculator` calcula:

- KM desde a ultima troca;
- KM restante;
- percentual consumido;
- dias desde a troca;
- media diaria de KM;
- previsao de vencimento;
- severidade sugerida;
- mensagem explicavel.

Esse calculo nao cria OS automaticamente. Ele apenas produz uma projecao. A criacao de alerta, tarefa ou OS deve passar por regra configurada, workflow e auditoria.
