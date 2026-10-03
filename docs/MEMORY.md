# MEMORY

## Projeto
**Nome**: FrotaCerta Fleet Intelligence
**Objetivo**: Plataforma API-First para gestão de frotas focada em operação preventiva, manutenção e finanças.
**Stack**: PHP 8.4, Laravel 12, PostgreSQL (Neon), Vercel.
**Arquitetura**: Modular Monolith.

## Estado atual
**O que funciona**: Bounded contexts de Administration, Fleet, Drivers, Documents, Intelligence (Rules engine, Alertas) já possuem backend robusto e testado (conforme docs do Split 01).
**O que está parcialmente funcionando**: Frontend (existem views iniciais blade mas devem ser evoluídas para um design premium).
**O que está quebrado**: Local env execution (Docker e PHP não configurados na máquina atual do agente/dev).

## Decisões importantes

`ADR-001` (Referência das docs do projeto)
**Decisão**: Uso do Vercel e Neon DB.
**Motivo**: Redução de infraestrutura, foco em scale to zero e arquitetura serverless para PHP Bref-like/Vercel.

## Regras importantes
1. Proibido colocar lógica de negócio nos Controllers.
2. Todo evento de domínio deve usar o Outbox pattern para assincronicidade garantida se houver comunicação cruzada.

## Problemas conhecidos
* Ambiente de desenvolvimento local atual sem PHP / Docker funcional. Depende do Vercel CLI ou re-configuração local para testar a API.

## Próximos passos
* Finalizar a fase DISCOVER + AUDIT (documentação entregue).
* Prosseguir para o planejamento das evoluções em frontend (fase POLISH/FIX).
* Criar e rodar o projeto localmente se o docker daemon for ligado.

## Contexto para futuros agentes
Você está assumindo o FrotaCerta como um Senior Full-Stack Engineer. A documentação inicial (Audit, Rules, Design System, PRD, Architecture, Tasks) foi preenchida a partir da auditoria. 
Não recrie o mundo; o backend já tem bastante estrutura feita nos `docs/` originais (01 ao 09).
Atenção à regra de não quebrar a arquitetura Modular Monolith estabelecida.
