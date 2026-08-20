# Roadmap, riscos e testes

## Roadmap

1. Fase 1: empresas, filiais, usuarios, RBAC, veiculos, motoristas, documentos e auditoria.
2. Fase 2: manutencao, OS, oficinas, pecas e estoque.
3. Fase 3: Preventive Intelligence para oleo, KM, horimetro, documentos, tacografo, alertas e tarefas.
4. Fase 4: pneus, inspeções, fotos, desgaste e recapagem.
5. Fase 5: checklist, evidencias, nao conformidades e bloqueios.
6. Fase 6: financeiro, notas, despesas, custos e TCO.
7. Fase 7: tendencias, anomalias, riscos e recomendacoes.
8. Fase 8: Mission Control, prioridades e automacoes.
9. Fase 9: mobile, rastreamento, telemetria e integracoes externas.

## Riscos tecnicos

- Misturar regra em controller.
- Criar telas antes de backend persistido.
- Tratar alertas como simples notificacoes sem evidencias.
- Permitir acesso cross-tenant por query sem escopo.
- Automatizar bloqueios ou OS sem regra auditavel.
- Duplicar veiculos entre backend e app mobile.

## Plano de testes

- Unit: regras deterministicas e value objects.
- Feature: APIs versionadas e validacao.
- Authorization: RBAC, policies e escopo multiempresa.
- Integration: outbox, filas, auditoria e workflows.
- API contract: OpenAPI sincronizado com rotas.
- Workflow: criacao de alertas, tarefas, OS e bloqueios.
- Audit: append-only e rastreabilidade de origem.
