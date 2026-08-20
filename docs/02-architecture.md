# Arquitetura

## Estilo

Modular Monolith com fronteiras explicitas por dominio.

Cada modulo deve seguir, quando aplicavel:

- `Domain`: entidades, value objects, eventos e regras puras.
- `Application`: casos de uso, orquestracao, transacoes e autorizacao de aplicacao.
- `Infrastructure`: Eloquent, filas, banco, cache, provedores externos.
- `Interfaces`: REST, requests, resources, Livewire/Blade ou frontend integrado.

## Bounded contexts iniciais

- `Administration`: grupos, empresas, filiais, usuarios, papeis e escopo.
- `Fleet`: veiculos, odometro, horimetro e estado operacional.
- `Drivers`: motoristas e documentos pessoais.
- `Documents`: documentos de veiculos, contratos, seguros e licencas.
- `Maintenance`: planos, registros, ordens de servico e itens.
- `PreventiveMaintenance`: regras por KM, tempo e horimetro.
- `Tires`: ciclo de vida, posicoes, inspecoes e fotos.
- `Checklists`: execucoes, respostas, evidencias e nao conformidades.
- `Intelligence`: alertas, riscos, insights, recomendacoes e prioridade.
- `Workflow`: regras, workflows, tarefas, aprovacoes e automacoes.
- `Audit`: trilha append-only para operacoes relevantes.
- `Integrations`: app mobile, telemetria, webhooks e provedores futuros.

## Regra de dependencia

Controllers nao contem regra de negocio. Eles chamam casos de uso em `Application`.

`Domain` nao depende de Laravel, banco, fila, HTTP ou cache. Isso permite testar regras criticas sem infraestrutura.

## API

A API REST sera versionada em `/api/v1`. A documentacao inicial esta em `openapi/openapi.yaml`.
