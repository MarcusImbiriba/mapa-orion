<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>


# Mapa Orion — Regras específicas do projeto

As regras desta seção são específicas do projeto **Mapa Orion**.

Em caso de conflito entre estas regras e instruções anteriores do Laravel Boost, **estas regras específicas do projeto prevalecem**.

Essa precedência aplica-se especialmente a instruções que orientem o agente a executar comandos de terminal, Artisan, Composer, NPM, testes, formatadores, Git, GitHub CLI, navegador, banco de dados ou outras operações no ambiente.

---

## Contexto técnico

O Mapa Orion utiliza:

- Laravel;
- SQLite;
- Blade;
- Datastar;
- Leaflet 1.9.4;
- Tailwind CSS;
- Lucide.

A aplicação não deve utilizar React, Vue, Livewire, Inertia, Breeze, Jetstream ou outro starter kit, salvo decisão futura expressamente solicitada pelo usuário.

A autenticação utiliza nome de usuário (`username`) e senha.

As geometrias geográficas são armazenadas em formato GeoJSON no SQLite.

Leaflet deve permanecer encapsulado em um Web Component próprio.

Datastar não deve manipular diretamente o DOM interno criado pelo Leaflet nem objetos internos da biblioteca.

A comunicação entre o restante da aplicação e o componente do mapa deve ocorrer por propriedades, métodos públicos e eventos personalizados.

O Web Component do mapa deve utilizar inicialmente Light DOM, e não Shadow DOM, salvo necessidade futura explicitamente identificada.

---

# Política de atuação do Codex

O Codex deve ser utilizado prioritariamente para:

- analisar arquivos do projeto;
- ler código existente;
- pesquisar documentação técnica quando necessário;
- criar código-fonte;
- editar código-fonte;
- criar ou editar testes;
- criar ou editar arquivos de configuração pertencentes ao projeto;
- analisar erros e resultados fornecidos pelo usuário.

O Codex **não deve executar tarefas operacionais no ambiente**, salvo quando o usuário solicitar explicitamente uma exceção.

---

## Execução manual obrigatória

Por padrão, o Codex NÃO deve executar:

- comandos de terminal ou shell;
- `php artisan`;
- `composer`;
- `npm`;
- `npx`;
- instalação ou atualização de dependências;
- migrations;
- seeders;
- servidores de desenvolvimento;
- builds;
- Laravel Pint;
- linters;
- formatadores;
- Pest;
- PHPUnit;
- testes automatizados;
- testes no navegador;
- testes de interface;
- automação de navegador;
- consultas ao banco destinadas à validação da implementação;
- Git;
- GitHub CLI;
- criação de branches;
- `git add`;
- `git commit`;
- `git merge`;
- `git rebase`;
- `git push`;
- criação ou fechamento de GitHub Issues;
- criação ou merge de Pull Requests.

Essas operações serão executadas manualmente pelo usuário.

Quando uma regra anterior do Laravel Boost determinar que algum desses comandos deve ser executado, o Codex deve **fornecer o comando no checklist manual em vez de executá-lo**.

Por exemplo, se as regras Laravel determinarem:

```text
vendor/bin/pint --dirty --format agent
```

o Codex não deve executá-lo.

Deve incluí-lo no checklist manual para execução pelo usuário.

O mesmo princípio vale para Artisan, testes, Composer, NPM, Git e demais comandos.

---

## Inspeção permitida

A proibição de execução de comandos não impede o Codex de:

- abrir arquivos;
- ler arquivos;
- pesquisar o código;
- comparar arquivos;
- consultar documentação;
- utilizar documentação do Laravel Boost;
- inspecionar arquivos de configuração;
- analisar `composer.json`;
- analisar `composer.lock`;
- analisar `package.json`;
- analisar migrations existentes;
- analisar Models, Controllers, Blade views, JavaScript e demais arquivos do repositório.

Sempre que uma informação puder ser obtida diretamente dos arquivos existentes, prefira essa abordagem em vez de solicitar que o usuário execute um comando.

---

# Desenvolvimento incremental

Cada solicitação deve ser tratada como uma unidade pequena e coerente de trabalho.

Antes de editar código, o Codex deve analisar apenas os arquivos necessários ao escopo atual.

Não amplie automaticamente o escopo para funcionalidades relacionadas que não tenham sido solicitadas.

Quando identificar uma melhoria ou tarefa adicional fora do escopo, informe-a separadamente para que possa ser transformada em outra GitHub Issue.

Não implemente trabalho adicional apenas porque ele parece conveniente.

---

# GitHub Issues

Toda etapa funcional relevante do desenvolvimento deve, preferencialmente, estar associada a uma GitHub Issue.

A Issue representa uma **unidade de trabalho**, e não cada comando necessário para executá-la.

Exemplo adequado:

```text
Issue #12 — Implementar autenticação por username
```

Exemplos inadequados:

```text
Issue — Executar migration
Issue — Executar npm run build
Issue — Fazer commit
```

Comandos, verificações e testes pertencem ao checklist de validação da Issue.

Quando o usuário informar o número ou conteúdo de uma Issue, o Codex deve limitar suas alterações ao escopo definido nela.

Se a tarefa for grande demais para uma única implementação coerente, o Codex deve recomendar sua divisão em Issues ou sub-issues menores, mas não deve criá-las automaticamente.

---

# Branches

Quando uma Issue for implementada em branch própria, a criação e o gerenciamento da branch serão realizados manualmente pelo usuário.

Uma convenção recomendada é:

```text
<numero-da-issue>-<descricao-curta>
```

Exemplo:

```text
12-authentication
```

O Codex pode sugerir o nome da branch, mas não deve criá-la.

---

# Commits

O Codex não deve executar commits.

Ao concluir uma implementação validada, pode sugerir uma mensagem de commit curta e semanticamente clara.

Os commits devem representar alterações pequenas e coerentes.

Evite misturar funcionalidades independentes em um mesmo commit.

O usuário será responsável por:

```text
git status
git diff
git add
git commit
git push
```

e demais operações Git.

O Codex apenas fornecerá esses comandos quando forem necessários.

---

# GitHub

Operações no GitHub são responsabilidade manual do usuário.

O Codex não deve:

- criar Issues;
- fechar Issues;
- realizar push;
- criar Pull Requests;
- fazer merge;
- manipular branches remotas.

O Codex pode fornecer:

- título sugerido para uma Issue;
- descrição sugerida;
- critérios de aceitação;
- nome sugerido para branch;
- mensagem sugerida para commit;
- comandos Git/GitHub que o usuário deverá executar manualmente.

---

# Checklist obrigatório após alterações

Ao concluir qualquer tarefa que envolva criação ou edição de código, o Codex deve apresentar um **checklist manual de validação**.

Inclua somente itens pertinentes à alteração realizada.

O checklist deve seguir esta estrutura quando aplicável:

## 1. Arquivos alterados

Informar:

```text
Criado:
- caminho/do/arquivo

Modificado:
- caminho/do/arquivo

Removido:
- caminho/do/arquivo
```

Acrescente uma descrição curta da finalidade de cada alteração.

---

## 2. Comandos manuais

Para cada comando necessário:

```text
[ ] Executar:

<comando>

Resultado esperado:
<descrição objetiva>
```

Não execute o comando.

Os comandos devem aparecer na ordem correta.

---

## 3. Testes automatizados

Quando houver testes relacionados:

```text
[ ] Executar:

<comando do teste>

Resultado esperado:
<resultado>
```

Prefira o conjunto mínimo de testes capaz de validar a alteração.

Se todos os testes específicos passarem, poderá ser indicado posteriormente o comando da suíte completa quando apropriado.

---

## 4. Formatação e análise estática

Quando aplicável, indicar manualmente comandos como:

```text
vendor/bin/pint --dirty --format agent
```

ou outros verificadores configurados no projeto.

O Codex não deve executá-los.

---

## 5. Validação funcional

Descrever testes manuais objetivos.

Exemplo:

```text
[ ] Acessar a tela de login.

Ação:
Informar username e senha válidos.

Resultado esperado:
Usuário autenticado e redirecionado para a página protegida.
```

Não utilize instruções vagas como:

```text
Verificar se está funcionando.
```

---

## 6. Verificação Git

Quando a implementação estiver pronta para revisão:

```text
[ ] Conferir branch e alterações:

git status --short --branch
```

```text
[ ] Revisar alterações não preparadas:

git diff
```

```text
[ ] Verificar problemas de whitespace:

git diff --check
```

Somente após a validação funcional:

```text
[ ] Preparar explicitamente os arquivos relacionados:

git add <arquivo1> <arquivo2>
```

Evite sugerir `git add .` quando for possível listar explicitamente os arquivos relacionados à tarefa.

Depois:

```text
[ ] Conferir alterações preparadas:

git diff --cached
```

```text
[ ] Verificar alterações preparadas:

git diff --cached --check
```

Por fim, sugerir a mensagem:

```text
git commit -m "<mensagem sugerida>"
```

O usuário decidirá se fará o commit.

---

# Retorno do usuário

O usuário executará manualmente o checklist.

Resultados bem-sucedidos poderão ser retornados apenas como:

```text
✅ item ou comando
```

Não solicite a saída completa de comandos executados com sucesso.

Quando houver falha, o usuário poderá retornar:

```text
❌ item ou comando

Erro:
<trecho relevante>
```

Ao receber o retorno, concentre a análise somente nos itens que apresentaram problema.

Não solicite logs completos quando poucas linhas forem suficientes para diagnosticar o erro.

---

# Economia de contexto e tokens

Evite produzir ou solicitar conteúdo desnecessariamente extenso.

Não solicite:

- saída completa de comandos que tiveram sucesso;
- logs completos quando apenas um trecho é relevante;
- dumps extensos do banco;
- conteúdo de arquivos que já podem ser lidos diretamente;
- repetição do contexto do projeto que já esteja disponível;
- resultados completos de testes quando apenas a falha é necessária.

Prefira informações mínimas suficientes para diagnosticar e corrigir o problema.

---

# Fluxo padrão de trabalho

O fluxo padrão do Mapa Orion é:

```text
GitHub Issue
      ↓
usuário prepara branch, quando aplicável
      ↓
Codex analisa arquivos
      ↓
Codex cria/edita código
      ↓
Codex gera checklist
      ↓
usuário executa comandos e testes
      ↓
usuário retorna resultados
      ↓
Codex corrige somente se necessário
      ↓
usuário revisa git diff
      ↓
usuário executa commit/push/merge
      ↓
usuário encerra a Issue
```

O Codex deve permanecer focado principalmente na **análise, criação e edição do código do Mapa Orion**.

A execução operacional e a validação final permanecem sob controle do usuário.
