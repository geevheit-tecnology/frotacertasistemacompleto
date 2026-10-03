# RULES

## Código
* **Nomenclatura**: Em inglês, camelCase para variáveis/funções, PascalCase para classes, snake_case para tabelas/colunas de banco.
* **Organização**: Manter cada contexto isolado em `/app/{Context}`.
* **Responsabilidade de classes**: Controllers não devem ter regra de negócio, apenas orquestrar Application/Use Cases.
* **Tipagem**: Uso rigoroso de tipos no PHP 8.4 (strict_types = 1 recomendado nas classes principais).
* **Tratamento de erros**: Lançamento de exceptions de domínio para regras de negócio violadas (ex: `DomainException`), capturadas globalmente via Handler.

## Arquitetura
* **Dependências permitidas**: Domain não pode depender do Eloquent/Laravel. Application e Infrastructure podem.
* **Comunicação entre camadas**: Use Cases / Events.
* **Acesso ao banco**: Repositories no Infrastructure. Não chamar Models diretamente do Controller se houver lógica complexa.

## Banco
* **Migrations**: Sempre usar migrations, não alterar DB manual.
* **Integridade**: Constraints e Foreign Keys no DB, usar UUIDs como Primary Keys preferencialmente ou IDs incrementais com UUIDs públicos.

## API
* **Versionamento**: Em URI `/api/v1/...`.
* **Erros**: Formato padronizado de erro JSON.
* **Autenticação**: Bearer tokens.
* **Autorização**: Validada nos Use Cases ou FormRequests via middleware e Policies.

## Frontend
* **Design**: TailwindCSS 4, consistência com componentes reutilizáveis.
* **Acessibilidade**: Elementos interativos devem ser plenamente acessíveis via teclado.
