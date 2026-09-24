# Mapa Orion — Visão Geral do Projeto

Atualizado em **24/09/2026** a partir do código, das configurações, das migrações e dos testes do projeto em `/home/meci/Dev/WebApp/MapaOrion/mapa.orion`.

A **implementação Laravel é a versão definitiva do projeto**, conforme decisão do responsável. Este documento descreve exclusivamente seu estado implementado. Substitui a consolidação de 10/09/2026 e pode ser utilizado de forma independente dos documentos anteriores. Funcionalidades ainda ausentes não são apresentadas como concluídas nem como compromissos de implementação.

## 1. Visão geral e escopo disponível

O Mapa Orion é uma aplicação web com acesso autenticado para visualizar unidades policiais em um mapa e consultar seus dados cadastrais e indicadores. O servidor Laravel consulta o banco e renderiza as páginas; o navegador executa o mapa Leaflet e a interação com os detalhes das unidades.

Estão implementados:

- Login por nome de usuário e senha, controle de sessão e logout.
- Mapa centrado inicialmente em São Luís, com base OpenStreetMap por padrão.
- Marcadores para unidades com localização geográfica válida.
- Áreas operacionais opcionais em GeoJSON Polygon/MultiPolygon, com acesso aos detalhes da unidade.
- Painel lateral com busca, listagem geral, contagens de unidades/geometrias e controle independente das camadas de sedes e áreas.
- Modal de detalhes com identificação, tipo, comando, contatos, endereço, localidades atendidas e indicadores.
- Indicadores de oficiais, praças, efetivo total calculado e população atendida.
- Distinção entre informação ausente e quantidade zero; aviso de indicadores demonstrativos.
- Zoom, recentralização e mensagens de carregamento ou falha do mapa.
- Operação dos marcadores por mouse ou teclado e retorno de foco ao fechar os detalhes.

O estado atual não inclui interface de cadastro/edição/exclusão de unidades, importador de planilhas ou GeoJSON, exportador de dados, totais globais de efetivo/população, interseções táticas ou alternador de bases cartográficas. A aplicação não atualiza os indicadores periodicamente: os dados são carregados ao renderizar a página.

## 2. Arquitetura

| Camada | Implementação |
| --- | --- |
| Backend | Laravel 13, rotas web, controllers, autenticação por sessão e validação |
| Persistência | SQLite no ambiente local consultado; modelos Eloquent e migrações |
| Interface | Blade, Tailwind CSS 4 e SVGs incorporados aos componentes |
| Interações com servidor | Datastar integrado ao Laravel, utilizado nos formulários de autenticação |
| Mapa | Leaflet 1.9.4 encapsulado no custom element `mapa-orion-map` |
| JavaScript | ES Modules; carregamento dinâmico do componente do mapa |
| Assets | Vite 8 e integração Laravel/Vite |
| Testes | Pest 5 e testes de aplicação Laravel |
| Formatação PHP | Laravel Pint |

Versões registradas nos arquivos de lock consultados: Laravel **13.31.0**, integração Laravel Datastar **1.0.3**, Leaflet **1.9.4**, Tailwind CSS **4.3.3**, Vite **8.3.0**, Pest **5.1.4** e Pint **1.32.1**. São as versões registradas no projeto nesta revisão, não uma afirmação sobre versões publicadas mais recentes. O `../composer.json` declara PHP `^8.3`, mas as dependências de desenvolvimento do lock exigem pelo menos PHP **8.4.1**. O ambiente documentado no README usa Node.js **22.13 ou posterior**, considerando também as ferramentas auxiliares.

O navegador recebe dados do servidor na página autenticada. Não há dependência de cinco arquivos públicos de cadastro nem de seletores JavaScript para unir unidades e indicadores. O banco é a fonte persistente dos dados da unidade.

As imagens da base cartográfica são obtidas do provedor configurado. Portanto, o funcionamento integral do mapa depende da disponibilidade desse serviço e da conexão de rede.

## 3. Estrutura relevante

```text
app/
├── Console/Commands/CreateUser.php
├── Http/Controllers/
│   ├── Auth/SessionController.php
│   └── MapController.php
├── Models/
│   ├── PoliceUnit.php
│   └── User.php
└── Rules/
    ├── GeoJsonPoint.php
    └── OperationalArea.php
config/
├── map.php
└── mapa_orion.php
database/
├── factories/PoliceUnitFactory.php
├── migrations/               # Usuários, unidades, indicadores e área operacional
└── seeders/
    ├── DatabaseSeeder.php
    ├── InitialUserSeeder.php
    ├── PoliceUnitSeeder.php
    └── data/police_units.json
resources/
├── css/app.css
├── js/
│   ├── app.js
│   └── components/
│       ├── mapa-orion-map.js
│       ├── operational-area.js
│       ├── operations-sidebar.js
│       └── unit-filters.js
└── views/
    ├── auth/login.blade.php
    ├── auth/partials/login-errors.blade.php
    ├── dashboard.blade.php
    └── components/
        ├── layouts/app.blade.php
        ├── operations-sidebar.blade.php
        └── unit-details.blade.php
routes/web.php
tests/
├── Feature/                  # Autenticação, comandos, seeders, modelos, mapa e painel
├── Fixtures/operational-areas.json
├── JavaScript/               # Geometrias, busca e contagens
└── Unit/
composer.json
package.json
phpunit.xml
vite.config.js
```

Essa relação destaca os arquivos de implementação do domínio e não representa todos os arquivos do framework.

## 4. Rotas e autenticação

| Método e caminho | Nome | Comportamento |
| --- | --- | --- |
| `GET /login` | `login` | Formulário de login, protegido por `guest` |
| `POST /login` | `login.store` | Validação das credenciais e início de sessão |
| `GET /` | `dashboard` | Mapa, protegido por `auth` |
| `POST /logout` | `logout` | Encerramento de sessão, protegido por `auth` |

`SessionController` valida usuário e senha, normaliza o usuário para minúsculas e limita tentativas de login por combinação de usuário e IP. O limite implementado é de cinco tentativas malsucedidas dentro da janela de 60 segundos.

O login bem-sucedido regenera a sessão. O logout invalida a sessão e regenera o token CSRF. Para requisições Datastar, a navegação usa resposta SSE e os erros são atualizados no fragmento Blade correspondente. Requisições convencionais têm redirecionamentos/respostas HTTP próprios. Os formulários utilizam proteção CSRF.

A instalação por `DatabaseSeeder` cria a conta inicial descrita na seção 9. A criação administrativa de contas adicionais está disponível por comando interativo:

```bash
php artisan users:create nome.usuario --name="Nome completo"
```

Esse comando **grava um usuário no banco** e solicita senha e confirmação de forma oculta. Valida unicidade do usuário, caracteres permitidos e senha com pelo menos 12 caracteres. Não há tela de cadastro público de usuários nas rotas atuais.

## 5. Modelo de dados: `PoliceUnit`

A tabela `police_units` concentra identificação, localização, dados descritivos e indicadores atuais. Cada registro possui `id` interno numérico e `code` único, além de timestamps.

### 5.1. Identificação, comando e cobertura descritiva

| Campo | Significado |
| --- | --- |
| `code` | Código estável e único, até 64 caracteres |
| `name` | Nome da unidade |
| `acronym` | Sigla, até 64 caracteres |
| `unit_type` | Tipo da unidade, opcional |
| `region` | Região, opcional; armazenada, mas não enviada pelo controller atual ao mapa |
| `address` | Endereço, opcional |
| `location` | Geometria GeoJSON do ponto da sede, opcional |
| `operational_area` | Geometria GeoJSON Polygon/MultiPolygon da área operacional, opcional |
| `commander` | Comandante, opcional |
| `deputy_commander` | Subcomandante, opcional |
| `phone` | Contato telefônico em texto, opcional |
| `email` | E-mail, opcional |
| `served_localities` | Localidades atendidas em texto, separadas por ponto e vírgula, opcional |

`code`, `name` e `acronym` são obrigatórios no esquema. O código deve permanecer estável ao atualizar nome, contato, localização ou indicadores. O banco impõe unicidade de `code`.

`served_localities` descreve cobertura textual, não geometria territorial. No modal, o JavaScript separa o texto por `;`, remove espaços nas extremidades e descarta entradas vazias. Cada localidade vira um marcador textual, acompanhado da contagem de itens. Não há deduplicação automática.

### 5.2. Localização GeoJSON

`location` é armazenado em coluna JSON e convertido para array pelo Eloquent. O conteúdo esperado para exibir um marcador é uma **geometria Point**, não um `Feature` nem um `FeatureCollection`:

```json
{
  "type": "Point",
  "coordinates": [-44.205670731012624, -2.537604436030353]
}
```

O exemplo ilustra o formato; não certifica o cadastro atual de uma unidade específica.

A ordem persistida é `[longitude, latitude]`. O JavaScript converte para `[latitude, longitude]` ao criar o marcador Leaflet. Não inverter a geometria armazenada.

### 5.3. Indicadores e referências

| Campo | Regra e uso |
| --- | --- |
| `officers_count` | Quantidade de oficiais, inteiro não negativo ou `null` |
| `enlisted_count` | Quantidade de praças, inteiro não negativo ou `null` |
| `total_personnel` | Atributo calculado; não é coluna de cadastro |
| `served_population` | População atendida, inteiro não negativo ou `null` |
| `personnel_reference_date` | Data de referência do efetivo, opcional |
| `population_reference_year` | Ano de referência da população, opcional |
| `population_source` | Fonte da população, opcional |
| `metrics_are_demo` | Indica métricas demonstrativas; padrão `false` no banco |

A regra de efetivo total é:

```text
Se oficiais e praças são conhecidos:
    total_personnel = officers_count + enlisted_count
Se qualquer um deles é null:
    total_personnel = null
```

**Zero é uma quantidade conhecida; `null` significa ausência de informação.** Não preencher dados desconhecidos com zero. O total acompanha as quantidades atuais, sem necessidade de gravar uma terceira medida que possa divergir.

No modal, valores desconhecidos aparecem como `—`, com descrição acessível “Não informado”. Zero aparece como zero. A população pode ser abreviada com `k` ou `M`, mantendo o valor completo em descrição acessível e no título do elemento. Se `metrics_are_demo` for `true`, é exibido o aviso “Indicadores com valores fictícios para demonstração.”

Os campos de data, ano e fonte existem na persistência, mas **a consulta não os envia ao navegador e o modal não os apresenta**. A exibição desses metadados foi cancelada pelo responsável em 24/09/2026 (item 7.7); os campos existentes são preservados, sem implementação de interface prevista nesta etapa. Os indicadores representam o estado atual do registro; não há tabela de histórico de medições implementada.

## 6. Fluxo de carregamento e responsabilidades

1. O usuário acessa `/`. Sem autenticação, é direcionado ao login.
2. `MapController` consulta `PoliceUnit` e seleciona todas as unidades, inclusive as que não possuem geometria. A coleção é ordenada com `qcg-pmma` primeiro e os demais códigos em ordem natural: 1º BPM, 2º BPM, …, 20º BPM no catálogo atual. Os índices são reorganizados para serializar uma lista JSON.
3. A consulta seleciona apenas os campos necessários para marcadores, áreas e detalhes e acrescenta `total_personnel` à serialização.
4. `dashboard.blade.php` renderiza o custom element com a configuração do mapa e as unidades serializadas em seu atributo `units`, com escape pelo Blade.
5. `../resources/js/app.js` detecta o elemento e importa dinamicamente `mapa-orion-map.js`. A página de login não inicializa o mapa.
6. O custom element cria a instância Leaflet, a camada de tiles, os marcadores e as áreas válidas.
7. Ao ativar um marcador, uma área ou uma unidade da lista lateral, o componente preenche e abre o diálogo já existente na página. Não há consulta adicional ao servidor para abrir os detalhes.

Campos enviados por `MapController`: `code`, `name`, `acronym`, `unit_type`, `address`, `commander`, `deputy_commander`, `phone`, `email`, `served_localities`, `location`, `operational_area`, `officers_count`, `enlisted_count`, `served_population`, `metrics_are_demo` e `total_personnel`.

O mapa usa `data-ignore-morph` e seu canvas usa `data-ignore` para preservar a região gerenciada pelo Leaflet durante interações Datastar. Um `ResizeObserver` atualiza o tamanho do mapa. Na desconexão, o componente encerra eventos, observação e instância do mapa e fecha o diálogo.

A busca do painel usa nome, sigla, código, localidades e endereço, sem diferenciar acentos/maiúsculas; todos os termos precisam ocorrer. A lista e o mapa são filtrados juntos, preservando a ordem recebida do servidor. As camadas de sedes e áreas são independentes da lista: desligá-las preserva o acesso aos detalhes. As contagens refletem a busca, com unidades encontradas/total, unidades sem ponto válido e quantidade visível de sedes/áreas. Não há persistência dos filtros entre recarregamentos.

Não há um serviço que carregue coleções públicas para combinar indicadores com unidades. A consulta, a transformação do modelo e a serialização do controller cumprem essas responsabilidades no fluxo atual.

## 7. Configuração efetiva do mapa

`../config/map.php` é a fonte de configuração utilizada pela view e pelo componente JavaScript:

| Configuração | Valor padrão |
| --- | --- |
| Latitude inicial | `-2.5307` |
| Longitude inicial | `-44.3068` |
| Zoom inicial | `12` |
| Zoom máximo da camada de tiles | `19` |
| URL da base | `https://tile.openstreetmap.org/{z}/{x}/{y}.png` |
| Atribuição | Créditos OpenStreetMap |

A URL e a atribuição podem ser fornecidas por `MAP_TILE_URL` e `MAP_ATTRIBUTION`. A recentralização reaplica o centro e o zoom recebidos da configuração, sem animação. Não há seleção de base pelo usuário na interface atual.

O componente apresenta mensagens distintas para falha de inicialização e falha no carregamento de parte dos tiles. O HTML também informa a necessidade de habilitar JavaScript quando ele está desativado.

## 8. Detalhes da unidade e acessibilidade

O modal é um elemento HTML `dialog`, definido em `../resources/views/components/unit-details.blade.php`. O título utiliza o nome, a identificação lateral utiliza a sigla e a linha complementar utiliza `unit_type`.

O componente exibe quatro indicadores e seções de comando/contatos e localidades. Campos descritivos ausentes são ocultados; a seção de comando é ocultada quando não há nenhuma linha preenchida, e localidades são ocultadas quando não há itens.

Os textos são inseridos com `textContent`; localidades são construídas com elementos DOM. O modal não apresenta código técnico, latitude ou longitude como conteúdo de consulta.

Marcadores, áreas e itens da lista têm rótulos de identificação e podem abrir detalhes por clique, Enter ou espaço. Existem dois botões para fechar o diálogo, que também pode ser fechado com Escape; ao fechar, o foco retorna ao elemento que o abriu. A abertura dos detalhes não implementa deslocamento automático do mapa para a unidade.

## 9. Cadastro e manutenção atuais

`DatabaseSeeder` executa primeiro `InitialUserSeeder` e depois `PoliceUnitSeeder`. Após as migrações, `php artisan db:seed --no-interaction` carrega ambos; `php artisan migrate --seed --no-interaction` também os executa durante a preparação de uma nova instalação.

### Usuário inicial

`../config/mapa_orion.php` centraliza nome **Comando**, username **comando** e senha padrão **MapaOrion-2026**. `COMANDO_INITIAL_PASSWORD` pode sobrescrever a senha quando definida; deve ter entre 12 e 1024 caracteres. Ausência da variável usa o padrão; valor vazio é rejeitado na criação. O `.env.example` atual deixa a variável comentada. Nome e username não dependem do `.env`.

O seeder consulta o username configurado e encerra normalmente se ele já existir, sem alterar nome, senha ou timestamps. A senha só é usada na criação e é armazenada como hash pelo Model `User`. Reexecutar o seeder nunca restaura a senha inicial de uma conta existente.

### Catálogo de unidades

`PoliceUnitSeeder` lê exclusivamente `../database/seeders/data/police_units.json`, com **21 unidades: QCG e 1º ao 20º BPM**. O catálogo preserva os 16 registros extraídos do SQLite em 22/09/2026 e acrescenta cinco BPMs obtidos da aba **Dados reais** de `mapa-orion-unidades.ods`, incorporados em 23/09/2026. A aba **Referência antiga** não participa da carga. IDs internos e timestamps são gerados ao inserir.

As 16 unidades anteriores têm indicadores demonstrativos (`metrics_are_demo=true`). As cinco novas têm indicadores e referências não informados (`null`), com `metrics_are_demo=false`. No catálogo atual, todas as 21 unidades têm ponto da sede; nenhuma fornece área operacional. Esses totais descrevem o arquivo versionado, não um inventário do banco local.

Antes de inserir, o seeder valida a lista completa: campos aceitos, obrigatórios, códigos únicos, contatos, indicadores, referências e pontos. Arquivo ausente, JSON malformado ou dados inválidos interrompem a carga de unidades. A persistência usa uma transação e `firstOrCreate` por `code`: insere apenas unidades ausentes e preserva registros existentes, IDs, timestamps, edições posteriores e unidades adicionais. Uma falha de persistência reverte as unidades inseridas naquela execução. Essa transação não abrange a criação anterior do usuário inicial.

Alterações no JSON afetam instalações novas e códigos ainda ausentes; não atualizam automaticamente unidades existentes. Para carregar somente unidades, sem executar o seeder de usuários:

```bash
php artisan db:seed --class=PoliceUnitSeeder --no-interaction
```

Não existe interface de manutenção de unidades nem importador geral. O cadastro é persistido pelo backend no modelo `PoliceUnit`; a manutenção posterior continua dependendo de um procedimento administrativo.

Para preparar um cadastro compatível com o estado atual:

1. Definir `code` único, `name` e `acronym`.
2. Preencher os campos descritivos disponíveis, preservando ausências nos opcionais.
3. Se houver GeoJSON externo, extrair a geometria `Point` da sede para `location`, preservando coordenadas. Não armazenar a coleção completa nesse campo.
4. Informar localidades em `served_localities`, separadas por `;`, sem confundir descrição textual com área cartográfica.
5. Informar oficiais, praças e população quando conhecidos; usar `null` para desconhecidos e marcar explicitamente dados demonstrativos.
6. Preservar data, ano e fonte quando disponíveis para armazenamento; sua exibição no modal foi cancelada no item 7.7.
7. Persistir pelo fluxo administrativo definido para o banco e conferir o registro e a exibição após recarregar a página.

Usar operações normais de salvamento do modelo permite executar sua validação de indicadores, ponto da sede e área operacional. Escritas SQL diretas ou atualizações em massa que não disparam os eventos do modelo não têm essa mesma garantia. Não há importador automático de planilhas implementado; preparar uma planilha não equivale a importar seus registros.

### Unidades sem área ou sem localização

Uma unidade, inclusive o QCG, pode existir somente com identificação. Não precisa de área operacional para existir ou para ter um ponto. A área opcional fica em `operational_area`, separada de `location`, como geometria GeoJSON Polygon ou MultiPolygon. O catálogo inicial não fornece polígonos; as áreas reais serão cadastradas posteriormente.

- Sem `location`: a unidade aparece na lista geral do painel com indicação **Sem ponto de sede**, com acesso aos detalhes mesmo sem área. Nenhum ponto é criado artificialmente.
- Com área válida, mesmo sem ponto: aparece como polígono e pode abrir os detalhes por clique ou teclado.
- Com `location` válida: aparece como marcador e pode abrir detalhes, mesmo sem dados opcionais.
- Com `location` não nula, mas inválida para o componente: pode ser enviada pelo controller e será ignorada na criação de marcadores.

Os testes incluem unidades sem geometria para verificar esse comportamento. O código do QCG no catálogo é `qcg-pmma`; o conteúdo do banco operacional não foi inventariado nesta revisão documental.

## 10. Validação e limites reais

### Áreas operacionais

O campo `operational_area` aceita `null`, Polygon ou MultiPolygon com listas não vazias, anéis fechados de pelo menos quatro posições e coordenadas bidimensionais finitas nos limites de longitude/latitude. Há validação no salvamento pelo Model e verificação defensiva no navegador. Não há análise topológica de auto-interseções ou certificação territorial. SQL direto e atualizações em massa podem contornar a validação do Model.

### Indicadores no servidor

O evento `saving` de `PoliceUnit` valida:

- `officers_count`, `enlisted_count` e `served_population`: opcionais, inteiros de 0 a 2.147.483.647.
- `population_reference_year`: opcional, inteiro de 1 a 9999.
- `population_source`: opcional, string com até 255 caracteres.
- `metrics_are_demo`: booleano.

Os casts convertem localização, quantidades, data, ano e marca de demonstração aos tipos definidos pelo modelo. O código não deve ser interpretado como validação integral de todos os campos cadastrais: por exemplo, a lista de regras do evento não inclui regra específica para a data de referência do efetivo.

### Ponto da sede no servidor

O evento `saving` de `PoliceUnit` também valida `location`: aceita `null` ou geometria GeoJSON Point com exatamente duas coordenadas numéricas finitas em ordem longitude/latitude, nos limites de −180 a 180 e −90 a 90. Strings numéricas, Feature/FeatureCollection, outras geometrias e altitude não são aceitos. Zero é válido. Uma falha impede toda a criação ou atualização pelo Model, sem alterar os dados já persistidos. Valores JSON brutos malformados não são tratados como ausência de localização. SQL direto, atualizações em massa e operações sem eventos podem contornar essa proteção.

### Geometria no navegador

Antes de criar o marcador, o componente exige:

- `location.type` igual a `Point`.
- `coordinates` como array de exatamente dois elementos.
- Longitude e latitude numéricas e finitas.
- Longitude entre -180 e 180 e latitude entre -90 e 90.

Pontos inválidos são ignorados no navegador. Essa checagem é complementar à validação do Model. Polygon/MultiPolygon também são validados e renderizados pelo fluxo de áreas operacionais, separadamente do ponto da sede. Nenhuma dessas verificações certifica que as coordenadas correspondem à sede real ou aos limites oficiais.

### Limitações funcionais

- Áreas são associadas à unidade no próprio registro. Não há cálculo de interseções, editor ou importador de áreas.
- Não há cálculo de população territorial única nem totais globais de indicadores. As contagens do painel são de unidades e geometrias, sem soma de efetivo ou população.
- Referências temporais e fonte da população não são expostas no modal por decisão de escopo (item 7.7 cancelado).
- Não há histórico de indicadores, atualização automática, exportação ou importação pela interface.
- Não há cadastro/edição de unidades pela interface. A lista geral abre os detalhes existentes e pode ser filtrada por busca; os controles de camadas alteram apenas a exibição no mapa.

## 11. Comandos e verificação

Executar os comandos na raiz da implementação Laravel:

```bash
cd /home/meci/Dev/WebApp/MapaOrion/mapa.orion
```

Para desenvolvimento e compilação:

```bash
composer run dev
npm run dev
npm run build
```

`composer run dev` chama `php artisan dev`, iniciando o servidor Laravel, o Vite e os processos auxiliares configurados. Os comandos do bloco são opções de desenvolvimento/compilação; não é necessário iniciar outro Vite junto de `composer run dev`. `npm run dev` inicia o Vite; isoladamente, não substitui o servidor Laravel. `npm run build` gera os assets para uso pela aplicação Laravel.

Para verificar os comportamentos centrais com os testes existentes:

```bash
php artisan test --compact tests/Feature/Models tests/Feature/Database tests/Feature/MapTest.php tests/Feature/OperationsSidebarTest.php tests/Feature/Auth/SessionControllerTest.php tests/Feature/Console/CreateUserTest.php
```

Para executar a suíte PHP completa, limpando antes o cache de configuração:

```bash
composer test
```

Os testes JavaScript são independentes da suíte PHP e usam o runner nativo do Node.js:

```bash
node --test tests/JavaScript/*.test.js
```

`../phpunit.xml` configura ambiente de teste com SQLite em memória, sessão/cache em array e fila síncrona. Os testes de banco utilizam mecanismos de preparação próprios. Não executar comandos de teste substituindo essas configurações pelo banco operacional.

Pint é o formatador PHP presente no projeto. Após alterações PHP, seguir a orientação do `../AGENTS.md`, incluindo `vendor/bin/pint --dirty --format agent` quando aplicável; esse comando pode modificar arquivos. O `package.json` atual contém apenas os scripts `dev` e `build`: não há scripts de ESLint, Prettier ou teste JavaScript definidos nele.

### Cobertura existente e evidência desta revisão

Os testes consultados cobrem autenticação, criação administrativa de usuários, acesso restrito ao mapa, configuração recebida do servidor, serialização dos dados, escape de atributos, estrutura do modal, identificação única, campos opcionais, localização e indicadores. Há casos específicos para zero versus desconhecido, cálculo do efetivo e rejeição de quantidades inválidas. A cobertura inclui os dois seeders, senha padrão/override e preservação de usuário existente, validação e reversão da carga de unidades, pontos e áreas, painel com unidades sem geometria e ordenação QCG/BPMs. Os testes JavaScript verificam geometrias, busca e contagens; não automatizam a interação do Leaflet no navegador.

**Nesta revisão documental de 24/09/2026 foram conferidos implementação, migração de áreas, configurações, catálogo, testes e locks. Foram revisados o diff e as referências locais da documentação. Não foram executados testes, Pint, build ou inspeção visual no navegador, pois apenas README e VGP foram alterados.** A existência de um teste não equivale a uma execução aprovada nesta data. Não são reaproveitados resultados de verificações da consolidação anterior.

Após mudanças funcionais, a conferência manual deve incluir login/logout, busca e limpeza, ordem da lista, contagens, camadas independentes, unidades sem geometria, painel em tela pequena, marcadores e áreas, abertura por teclado, fechamento e retorno de foco, campos ausentes, indicadores zerados/desconhecidos/demonstrativos, recentralização e mensagens de falha da base cartográfica.

## 12. Decisões e restrições vigentes

1. Laravel é a implementação definitiva do Mapa Orion.
2. Manter o banco e o modelo `PoliceUnit` como base dos dados cadastrais e indicadores atuais.
3. Preservar `code` como identificação única e estável; `id` é a chave interna do banco.
4. Usar nomes técnicos em inglês no modelo e português na interface.
5. Admitir unidades sem localização e sem área; exibir no mapa somente pontos e áreas válidos.
6. Armazenar GeoJSON Point em ordem longitude/latitude.
7. Calcular o efetivo total a partir de oficiais e praças, preservando a distinção entre zero e desconhecido.
8. Identificar indicadores demonstrativos e preservar metadados de referência disponíveis.
9. Usar `../config/map.php` como configuração efetivamente consumida pelo mapa.
10. Não declarar como implementados importadores, interseções, exportadores ou telas cadastrais ausentes no código atual.
11. Preservar autenticação, acessibilidade e separação entre dados persistidos e apresentação.
12. A documentação registra o estado atual; novas funcionalidades e alterações de arquitetura continuam dependentes de definição de escopo.
13. O item 7.7 foi cancelado: não implementar a exibição dos metadados de referência no modal; preservar os campos na persistência.
14. O suporte do item 7.4 está implementado; cadastrar os polígonos reais posteriormente, quando os dados forem fornecidos.
