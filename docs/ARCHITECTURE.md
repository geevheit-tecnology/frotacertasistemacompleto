# ARCHITECTURE

## Visão Geral
Modular Monolith com fronteiras explícitas por domínio. 

### Current Architecture
A arquitetura atual organiza o código seguindo a estrutura de Módulos Bounded Contexts.
Dentro de cada módulo, aplicam-se os seguintes conceitos:
- `Domain`: entidades, value objects, eventos e regras puras. Sem dependências externas.
- `Application`: casos de uso, orquestração, transações e autorização de aplicação.
- `Infrastructure`: Eloquent, banco, filas, provedores externos.
- `Interfaces`: HTTP REST, requests, API resources.

## Módulos (Bounded Contexts)
- **Administration**: grupos, empresas, filiais, usuários, papéis e escopo.
- **Fleet**: veículos, odômetro, horímetro e estado operacional.
- **Drivers**: motoristas e documentos pessoais.
- **Documents**: documentos de veículos, contratos, seguros e licenças.
- **Maintenance**: planos, registros, ordens de serviço e itens.
- **PreventiveMaintenance**: regras por KM, tempo e horímetro.
- **Tires**: ciclo de vida, posições, inspeções e fotos.
- **Checklists**: execuções, respostas, evidências e não conformidades.
- **Intelligence**: alertas, riscos, insights, recomendações e prioridade.
- **Workflow**: regras, workflows, tarefas, aprovações e automações.
- **Audit**: trilha append-only para operações relevantes.
- **Financial**: transações, abastecimentos, notas fiscais, custos.

## Integrações e Componentes Externos
- **Banco de Dados**: PostgreSQL (Neon Serverless).
- **Cache / Fila**: Redis (se necessário), ou uso do Outbox Pattern para processamento assíncrono em DB.
- **Frontend**: Aplicação API-first, consumida por web (Vite/Tailwind) ou mobile.
- **Deploy**: Vercel.

## Target Architecture
A arquitetura alvo mantém a estrutura atual de Modular Monolith, porém com melhor separação de eventos (Event Bus) e estabilidade para o Frontend. A evolução focará na abstração completa do Frontend consumindo os endpoints REST definidos no Swagger, e talvez a extração de Workers para filas independentes conforme o volume de processamento de regras e inteligência artificial aumentar.
