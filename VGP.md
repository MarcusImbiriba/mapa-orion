# Mapa Orion — Visão Geral do Projeto

Atualizado em **17/09/2026** a partir do código, das configurações, das migrações e dos testes do projeto em `/home/meci/Dev/WebApp/MapaOrion/mapa.orion`.

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

Versões registradas nos arquivos de lock consultados: Laravel **13.31.0**, integração Laravel Datastar **1.0.3**, Leaflet **1.9.4**, Tailwind CSS **4.3.3**, Vite **8.3.0**, Pest **5.1.4** e Pint **1.32.1**. São as versões registradas no projeto nesta revisão, não uma afirmação sobre versões publicadas mais recentes. O `composer.json` exige PHP `^8.3`.

O navegador recebe dados do servidor na página autenticada. Não há dependência de cinco arquivos públicos de cadastro nem de seletores JavaScript para unir unidades e indicadores. O banco é a fonte persistente dos dados da unidade.

As imagens da base cartográfica são obtidas do provedor configurado. Portanto, o funcionamento integral do mapa depende da disponibilidade desse serviço e da conexão de rede.

## 3. Estrutura relevante

```text
app/
├── Console/Commands/CreateUser.php
├── Http/Controllers/
│   ├── Auth/SessionController.php
│   └── MapController.php
└── Models/
    ├── PoliceUnit.php
    └── User.php
config/
└── map.php
database/
├── factories/PoliceUnitFactory.php
└── migrations/
    ├── ..._add_username_to_users_table.php
    ├── ..._create_police_units_table.php
    ├── ..._add_contact_and_coverage_fields_to_police_units_table.php
    └── ..._add_current_metrics_to_police_units_table.php
resources/
├── css/app.css
├── js/
│   ├── app.js
│   └── components/mapa-orion-map.js
└── views/
    ├── auth/login.blade.php
    ├── auth/partials/login-errors.blade.php
    ├── dashboard.blade.php
    └── components/
        ├── layouts/app.blade.php
        └── unit-details.blade.php
routes/web.php
tests/Feature/
├── Auth/SessionControllerTest.php
├── Console/CreateUserTest.php
├── Models/PoliceUnitTest.php
└── MapTest.php
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

A criação administrativa de usuários está disponível por comando interativo:

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

Os campos de data, ano e fonte existem na persistência, mas **a consulta atual não os envia ao navegador e o modal não os apresenta**. Os indicadores representam o estado atual do registro; não há tabela de histórico de medições implementada.

## 6. Fluxo de carregamento e responsabilidades

1. O usuário acessa `/`. Sem autenticação, é direcionado ao login.
2. `MapController` consulta `PoliceUnit`, seleciona todas as unidades, inclusive as que não possuem geometria e ordena por `code`.
3. A consulta seleciona apenas os campos necessários para marcadores, áreas e detalhes e acrescenta `total_personnel` à serialização.
4. `dashboard.blade.php` renderiza o custom element com a configuração do mapa e as unidades serializadas em seu atributo `units`, com escape pelo Blade.
5. `resources/js/app.js` detecta o elemento e importa dinamicamente `mapa-orion-map.js`. A página de login não inicializa o mapa.
6. O custom element cria a instância Leaflet, a camada de tiles, os marcadores e as áreas válidas.
7. Ao ativar um marcador, uma área ou uma unidade da lista lateral, o componente preenche e abre o diálogo já existente na página. Não há consulta adicional ao servidor para abrir os detalhes.

Campos enviados por `MapController`: `code`, `name`, `acronym`, `unit_type`, `address`, `commander`, `deputy_commander`, `phone`, `email`, `served_localities`, `location`, `operational_area`, `officers_count`, `enlisted_count`, `served_population`, `metrics_are_demo` e `total_personnel`.

O mapa usa `data-ignore-morph` e seu canvas usa `data-ignore` para preservar a região gerenciada pelo Leaflet durante interações Datastar. Um `ResizeObserver` atualiza o tamanho do mapa. Na desconexão, o componente encerra eventos, observação e instância do mapa e fecha o diálogo.

A busca do painel usa nome, sigla, código, localidades e endereço, sem diferenciar acentos/maiúsculas; todos os termos precisam ocorrer. A lista e o mapa são filtrados juntos. As camadas de sedes e áreas são independentes da lista: desligá-las preserva o acesso aos detalhes. As contagens refletem a busca, com unidades encontradas/total, unidades sem ponto válido e quantidade visível de sedes/áreas. Não há persistência dos filtros entre recarregamentos.

Não há um serviço que carregue coleções públicas para combinar indicadores com unidades. A consulta, a transformação do modelo e a serialização do controller cumprem essas responsabilidades no fluxo atual.

## 7. Configuração efetiva do mapa

`config/map.php` é a fonte de configuração utilizada pela view e pelo componente JavaScript:

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

O modal é um elemento HTML `dialog`, definido em `resources/views/components/unit-details.blade.php`. O título utiliza o nome, a identificação lateral utiliza a sigla e a linha complementar utiliza `unit_type`.

O componente exibe quatro indicadores e seções de comando/contatos e localidades. Campos descritivos ausentes são ocultados; a seção de comando é ocultada quando não há nenhuma linha preenchida, e localidades são ocultadas quando não há itens.

Os textos são inseridos com `textContent`; localidades são construídas com elementos DOM. O modal não apresenta código técnico, latitude ou longitude como conteúdo de consulta.

Os marcadores têm rótulos de identificação e podem abrir detalhes por clique, Enter ou espaço. Existem dois botões para fechar o diálogo; ao fechar, o foco retorna ao marcador que o abriu. A abertura dos detalhes não implementa deslocamento automático do mapa para a unidade.

## 9. Cadastro e manutenção atuais

A carga inicial de unidades foi adicionada em **22/09/2026**. `DatabaseSeeder` integra a carga de `PoliceUnitSeeder`, que declara diretamente em arrays PHP os 19 campos de domínio das 16 unidades presentes no SQLite do próprio `mapa.orion` nessa data. O arquivo `database/seeders/data/police_units.json` foi preservado como referência da extração, sem leitura ou decodificação pelo seeder. IDs internos e timestamps não integram o catálogo. Todos os indicadores dessa carga permanecem marcados como demonstrativos.

Após as migrações, `php artisan db:seed --no-interaction` insere as unidades ausentes em uma transação, identificando-as por `code`. A repetição preserva registros existentes, inclusive edições posteriores e unidades adicionais; não sincroniza alterações do catálogo com registros já cadastrados. Nenhum usuário ou credencial é criado por essa carga.

Não existe interface de manutenção de unidades nem importador geral. O cadastro é persistido pelo backend no modelo `PoliceUnit`; a manutenção posterior continua dependendo de um procedimento administrativo.

Para preparar um cadastro compatível com o estado atual:

1. Definir `code` único, `name` e `acronym`.
2. Preencher os campos descritivos disponíveis, preservando ausências nos opcionais.
3. Se houver GeoJSON externo, extrair a geometria `Point` da sede para `location`, preservando coordenadas. Não armazenar a coleção completa nesse campo.
4. Informar localidades em `served_localities`, separadas por `;`, sem confundir descrição textual com área cartográfica.
5. Informar oficiais, praças e população quando conhecidos; usar `null` para desconhecidos e marcar explicitamente dados demonstrativos.
6. Preencher data, ano e fonte quando disponíveis, considerando que ainda não aparecem no modal.
7. Persistir pelo fluxo administrativo definido para o banco e conferir o registro e a exibição após recarregar a página.

Usar operações normais de salvamento do modelo permite executar sua validação de indicadores. Escritas SQL diretas ou atualizações em massa que não disparam os eventos do modelo não têm essa mesma garantia. Não há importador automático de planilhas implementado; preparar uma planilha não equivale a importar seus registros.

### Unidades sem área ou sem localização

Uma unidade, inclusive o QCG, pode existir somente com identificação. Não precisa de área operacional para existir ou para ter um ponto. A área opcional fica em `operational_area`, separada de `location`, como geometria GeoJSON Polygon ou MultiPolygon. O catálogo inicial não fornece polígonos; as áreas reais serão cadastradas posteriormente.

- Sem `location`: a unidade aparece na lista geral do painel com indicação **Sem ponto de sede**, com acesso aos detalhes mesmo sem área. Nenhum ponto é criado artificialmente.
- Com área válida, mesmo sem ponto: aparece como polígono e pode abrir os detalhes por clique ou teclado.
- Com `location` válida: aparece como marcador e pode abrir detalhes, mesmo sem dados opcionais.
- Com `location` não nula, mas inválida para o componente: pode ser enviada pelo controller e será ignorada na criação de marcadores.

O teste de modelo utiliza `qg-pmma` para demonstrar cadastro sem área e sem localização. Isso não comprova a existência desse registro no banco operacional consultado, cujo conteúdo não foi inventariado nesta revisão.

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
- Referências temporais e fonte da população ainda não são expostas no modal.
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

`composer run dev` aciona o ambiente de desenvolvimento configurado pelo projeto. `npm run dev` inicia o Vite; isoladamente, não substitui o servidor Laravel. `npm run build` gera os assets para uso pela aplicação Laravel.

Para verificar os comportamentos centrais com os testes existentes:

```bash
php artisan test --compact tests/Feature/Models/PoliceUnitTest.php tests/Feature/MapTest.php tests/Feature/Auth/SessionControllerTest.php tests/Feature/Console/CreateUserTest.php
```

Para executar a suíte completa:

```bash
php artisan test --compact
```

`phpunit.xml` configura ambiente de teste com SQLite em memória, sessão/cache em array e fila síncrona. Os testes de banco utilizam mecanismos de preparação próprios. Não executar comandos de teste substituindo essas configurações pelo banco operacional.

Pint é o formatador PHP presente no projeto. Após alterações PHP, seguir a orientação do `AGENTS.md`, incluindo `vendor/bin/pint --dirty --format agent` quando aplicável; esse comando pode modificar arquivos. O `package.json` atual contém apenas os scripts `dev` e `build`: não há scripts de ESLint, Prettier ou teste JavaScript definidos nele.

### Cobertura existente e evidência desta revisão

Os testes consultados cobrem autenticação, criação administrativa de usuários, acesso restrito ao mapa, configuração recebida do servidor, serialização dos dados, escape de atributos, estrutura do modal, identificação única, campos opcionais, localização e indicadores. Há casos específicos para zero versus desconhecido, cálculo do efetivo e rejeição de quantidades inválidas.

**Nesta revisão documental foram lidos os arquivos de implementação, migrações, configurações, testes e locks. Não foram executados testes, build ou inspeção visual no navegador.** A existência de um teste não equivale a uma execução aprovada nesta data. Não são reaproveitados resultados de verificações da consolidação anterior.

Após mudanças funcionais, a conferência manual deve incluir login/logout, marcadores, abertura por teclado, fechamento e retorno de foco, campos ausentes, indicadores zerados/desconhecidos/demonstrativos, recentralização e mensagens de falha da base cartográfica.

## 12. Decisões e restrições vigentes

1. Laravel é a implementação definitiva do Mapa Orion.
2. Manter o banco e o modelo `PoliceUnit` como base dos dados cadastrais e indicadores atuais.
3. Preservar `code` como identificação única e estável; `id` é a chave interna do banco.
4. Usar nomes técnicos em inglês no modelo e português na interface.
5. Admitir unidades sem localização e sem área; exibir no mapa somente pontos e áreas válidos.
6. Armazenar GeoJSON Point em ordem longitude/latitude.
7. Calcular o efetivo total a partir de oficiais e praças, preservando a distinção entre zero e desconhecido.
8. Identificar indicadores demonstrativos e preservar metadados de referência disponíveis.
9. Usar `config/map.php` como configuração efetivamente consumida pelo mapa.
10. Não declarar como implementados importadores, interseções, exportadores ou telas cadastrais ausentes no código atual.
11. Preservar autenticação, acessibilidade e separação entre dados persistidos e apresentação.
12. A documentação registra o estado atual; novas funcionalidades e alterações de arquitetura continuam dependentes de definição de escopo.
