<p align="center">
  <img src="public/readme-header.png" alt="Mapa Orion — PMMA: emblema e constelação sobre fundo azul-marinho" width="1000">
</p>

# Mapa Orion

Aplicação web para visualizar unidades policiais em um mapa e consultar informações cadastrais, efetivo e população atendida, com acesso autenticado.

O sistema reúne a localização das unidades e seus dados em uma interface de consulta. A implementação utiliza Laravel no servidor, SQLite para persistência local e Leaflet para o mapa interativo.

## Sumário

- [Sobre o sistema](#sobre-o-sistema)
- [Tecnologias](#tecnologias)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Execução local](#execução-local)
- [Testes](#testes)
- [Configuração do mapa](#configuração-do-mapa)
- [Estrutura do projeto](#estrutura-do-projeto)

## Sobre o sistema

O Mapa Orion permite:

- Acessar o sistema com nome de usuário e senha.
- Visualizar unidades georreferenciadas em um mapa com base OpenStreetMap.
- Navegar pelo mapa, ajustar o zoom e retornar à visualização inicial de São Luís, no Maranhão.
- Abrir os detalhes de uma unidade pelo marcador, usando mouse ou teclado.
- Consultar identificação, tipo, comando, contatos, endereço e localidades atendidas.
- Consultar oficiais, praças, efetivo total calculado e população atendida, quando informados.

Os indicadores distinguem valores desconhecidos (`—`), quantidades zeradas (`0`) e dados demonstrativos, identificados com um aviso. O efetivo total é calculado somente quando as quantidades de oficiais e praças estão disponíveis.

**Escopo atual:** a aplicação oferece consulta aos dados. Ainda não há tela de cadastro ou edição de unidades, importação de planilhas ou exportação. O mapa exibe áreas operacionais quando cadastradas. A criação de usuários é feita pelo terminal.

## Tecnologias

| Camada | Tecnologia |
| --- | --- |
| Backend | PHP, Laravel 13 e Eloquent |
| Banco de dados local | SQLite |
| Interface | Blade e Tailwind CSS 4 |
| Interações dos formulários | Datastar |
| Mapa | Leaflet 1.9.4 e OpenStreetMap |
| Compilação de CSS e JavaScript | Vite 8 |
| Testes automatizados | Pest 5 e PHPUnit |
| Formatação PHP | Laravel Pint |

As versões exatas das dependências estão registradas em [`composer.lock`](composer.lock) e [`package-lock.json`](package-lock.json).

## Requisitos

Para instalar e desenvolver com as dependências atualmente registradas:

- **PHP 8.4.1 ou posterior da série 8.4, ou PHP 8.5.**
- **Composer 2.**
- **Node.js 22.13 ou posterior**, com npm, atendendo aos requisitos das ferramentas do projeto.
- **Git** para clonar o repositório.
- Extensões PHP exigidas pelo Composer e suporte a SQLite por `pdo_sqlite` e `sqlite3`.
- Navegador com JavaScript habilitado e acesso à internet para carregar a base cartográfica.

Embora o `composer.json` declare PHP `^8.3`, o conjunto de dependências no `composer.lock`, incluindo componentes Symfony e PHPUnit, exige pelo menos PHP **8.4.1**. O requisito de Node.js também considera as ferramentas de desenvolvimento, além do Vite.

É possível conferir as exigências PHP do lock com:

```bash
composer check-platform-reqs --lock
```

Confira também o suporte local a SQLite:

```bash
php --ri pdo_sqlite
php --ri sqlite3
```

## Instalação

Os passos abaixo preparam uma **nova instalação local com SQLite**. Execute os comandos na raiz do projeto após a clonagem.

### 1. Clonar o repositório

```bash
git clone https://github.com/MarcusImbiriba/mapa-orion.git
cd mapa-orion
```

### 2. Instalar as dependências

```bash
composer install
npm ci
```

Esses comandos utilizam as versões dos arquivos de lock, conforme a documentação de [instalação do Composer](https://getcomposer.org/doc/01-basic-usage.md#installing-from-composer-lock) e do [`npm ci`](https://docs.npmjs.com/cli/v11/commands/npm-ci/). As dependências de desenvolvimento são necessárias para executar os testes. O arquivo `.npmrc` do projeto já configura `ignore-scripts=true` para o npm.

### 3. Configurar o ambiente

Crie o arquivo de configuração local a partir do exemplo:

```bash
php -r "file_exists('.env') || copy('.env.example', '.env');"
```

No `.env`, ajuste o nome da aplicação e confira estes valores:

```dotenv
APP_NAME="Mapa Orion"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Para o SQLite padrão, mantenha `DB_DATABASE` sem definição: a aplicação utiliza `database/database.sqlite`. O `.env` contém configurações locais e não deve ser incluído no Git.

Gere a chave da nova instalação:

```bash
php artisan key:generate --no-interaction
```

### 4. Criar o banco e executar as migrações

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed --no-interaction
```

As migrações criam a estrutura das unidades, dos usuários e dos serviços que utilizam o banco, como sessões e cache. A opção `--seed` também cria o usuário inicial e carrega o catálogo de unidades descritos abaixo.

### 5. Usuário inicial e contas adicionais

O `InitialUserSeeder` cria o usuário inicial com os valores definidos em `config/mapa_orion.php`:

- **Usuário:** `comando`.
- **Nome:** `Comando`.
- **Senha inicial padrão:** `MapaOrion-2026`.

A variável `COMANDO_INITIAL_PASSWORD` pode sobrescrever a senha inicial padrão. Quando ela não está definida, o sistema utiliza `MapaOrion-2026`. Para usar o padrão ao copiar `.env.example`, remova ou comente a linha `COMANDO_INITIAL_PASSWORD=` no `.env`: uma variável definida como vazia não ativa o padrão e é rejeitada pelo seeder. Uma senha personalizada deve ter entre **12 e 1024 caracteres**.

Essa configuração só é utilizada durante a criação inicial. Se o usuário `comando` já existir (ou outro `username` configurado), executar novamente o seeder não altera seu nome ou sua senha, não recria a conta e não restaura a senha inicial, mesmo após uma troca de senha.

Para criar contas adicionais, utilize:

```bash
php artisan users:create nome.usuario --name="Nome completo"
```

O comando solicita a senha e sua confirmação sem exibi-las. Utilize uma senha com pelo menos **12 caracteres**. O nome de usuário aceita letras, números, ponto, hífen e sublinhado, e é convertido para minúsculas.

O login utiliza o **nome de usuário**, não o e-mail.

### 6. Compilar os arquivos da interface

```bash
npm run build
```

O comando gera o CSS, o JavaScript e os demais assets em `public/build`. A configuração atual também utiliza fontes do Bunny Fonts; mantenha acesso à internet durante a compilação para permitir seu download.

### Dados da instalação

A instalação com `--seed` cria **21 unidades**: o QCG e o 1º ao 20º BPM. O `PoliceUnitSeeder` lê exclusivamente o catálogo versionado `database/seeders/data/police_units.json`. Os 16 registros extraídos do SQLite do próprio `mapa.orion` em **22/09/2026** foram preservados integralmente. Os cinco novos BPMs vieram da aba **Dados reais** da planilha `mapa-orion-unidades.ods`, incorporada em **23/09/2026**. A aba **Referência antiga**, marcada como fictícia, não participa da carga. IDs internos e timestamps são gerados pela instalação.

**Os indicadores das 16 unidades anteriores são demonstrativos** e mantêm seus valores e `metrics_are_demo=true`. Os cinco novos BPMs não possuem indicadores informados: efetivo, população e referências ficam como `null`, com `metrics_are_demo=false`. Ausência de informação não equivale a zero. Na preparação do catálogo, campos vazios e comandantes indicados como “Indefinido” foram convertidos em `null`, localidades foram separadas por ponto e vírgula e coordenadas foram armazenadas como números na ordem GeoJSON `[longitude, latitude]`. Nomes e endereços foram preservados conforme a fonte.

O `DatabaseSeeder` chama `InitialUserSeeder` e depois `PoliceUnitSeeder`. Para carregar o catálogo após executar as migrações, use:

```bash
php artisan db:seed --no-interaction
```

O catálogo inteiro é validado antes da carga: estrutura, campos obrigatórios, códigos únicos, contatos, indicadores e coordenadas. Uma localização pode ser `null`; quando informada, deve ser um GeoJSON `Point` com longitude e latitude numéricas válidas. Um arquivo ausente, JSON inválido ou registro inválido interrompe a carga de unidades sem inserções parciais. Falhas durante a persistência também revertem as inserções daquela execução.

A carga identifica cada unidade por `code` e acrescenta somente códigos ausentes, preservando registros existentes, suas alterações, IDs e timestamps. Unidades adicionais também são preservadas. Alterações no JSON afetam instalações novas e unidades ainda ausentes; não corrigem automaticamente registros já cadastrados. Atualizações de unidades existentes exigem um procedimento administrativo separado e revisado.

Para carregar apenas as unidades, sem executar o seeder de usuários:

```bash
php artisan db:seed --class=PoliceUnitSeeder --no-interaction
```

A manutenção das unidades ainda depende de um procedimento administrativo no backend; o projeto não fornece um importador geral ou tela de cadastro.

## Execução local

Com a instalação concluída, inicie o ambiente de desenvolvimento:

```bash
composer run dev
```

Esse comando inicia o servidor Laravel, o Vite e os processos auxiliares configurados pelo framework, incluindo fila e, quando disponível, acompanhamento de logs. Acesse [http://localhost:8000](http://localhost:8000) e entre com o usuário criado. Para encerrar os processos, pressione `Ctrl+C`.

Se preferir iniciar somente o servidor web e o Vite, execute em dois terminais separados:

```bash
# Terminal 1: servidor da aplicação
php artisan serve
```

```bash
# Terminal 2: assets com atualização durante o desenvolvimento
npm run dev
```

Para conferir a aplicação usando os assets já compilados, execute `npm run build` e inicie apenas `php artisan serve`, com o Vite de desenvolvimento encerrado. O comando `npm run dev`, sozinho, não inicia o servidor Laravel.

Esses comandos são destinados ao ambiente local de desenvolvimento.

## Testes

A suíte utiliza Pest com testes unitários e de aplicação. Depois de instalar as dependências, configurar o `.env` e compilar os assets, execute:

```bash
composer test
```

O script limpa o cache de configuração antes de executar a suíte completa. Para obter a saída compacta diretamente pelo Artisan:

```bash
php artisan config:clear --no-interaction
php artisan test --compact
```

Para executar apenas os testes relacionados ao mapa e às unidades:

```bash
php artisan test --compact tests/Feature/MapTest.php tests/Feature/Models/PoliceUnitTest.php
```

Para executar apenas os testes de autenticação e criação de usuários:

```bash
php artisan test --compact tests/Feature/Auth/SessionControllerTest.php tests/Feature/Console/CreateUserTest.php
```

O [`phpunit.xml`](phpunit.xml) configura **SQLite em memória**, sessão e cache em arrays e fila síncrona. Mantenha essa configuração ao executar a suíte; ela prepara seus próprios registros de teste e não precisa das unidades do banco local.

### O que a suíte verifica

- Login, logout, proteção das rotas, sessão, CSRF e limitação de tentativas de autenticação.
- Criação de usuários, normalização do nome, duplicidade e validação de senha.
- Persistência das unidades, localização, campos opcionais e unicidade dos códigos.
- Indicadores, cálculo do efetivo e distinção entre zero e informação ausente.
- Dados enviados ao mapa, escape de conteúdo e estrutura da janela de detalhes.

Os testes atuais não automatizam a interação do Leaflet em um navegador. Após mudanças na interface, confira também abertura e fechamento dos detalhes, navegação por teclado, retorno do foco, zoom e recentralização.

### Compilação e formatação

Valide a compilação da interface com:

```bash
npm run build
```

Para aplicar a formatação PHP aos arquivos alterados no Git:

```bash
vendor/bin/pint --dirty --format agent
```

O Pint pode modificar os arquivos para ajustar sua formatação. O projeto não possui um script `npm test`; os testes documentados acima são executados pelo PHP.

## Configuração do mapa

O arquivo [`config/map.php`](config/map.php) define o centro inicial, o zoom, o limite de zoom e a base cartográfica.

O provedor padrão é o OpenStreetMap. As variáveis opcionais `MAP_TILE_URL` e `MAP_ATTRIBUTION`, no `.env`, permitem configurar outro provedor e sua atribuição. Após alterar configurações, execute `php artisan config:clear` e recarregue a página.

A localização de uma unidade utiliza uma geometria GeoJSON `Point`, com coordenadas na ordem **longitude, latitude**. O salvamento pelo Model aceita `null` ou um Point com exatamente duas coordenadas numéricas finitas: longitude entre −180 e 180 e latitude entre −90 e 90. Strings numéricas, outros tipos de geometria e posições com altitude são rejeitados. Uma localização inválida impede a criação ou a alteração da unidade, preservando o registro anterior; `[0, 0]` é uma localização válida. Unidades sem localização permanecem no banco e não aparecem como marcadores; a verificação defensiva do navegador continua ignorando pontos inválidos. Escritas SQL diretas, atualizações em massa e operações que suprimem eventos do Model podem contornar essa validação.

A área operacional é opcional e fica em `police_units.operational_area`, separada do ponto da sede e associada à unidade pelo próprio registro. Aceita uma geometria GeoJSON `Polygon` ou `MultiPolygon` (incluindo anéis internos), com coordenadas bidimensionais `[longitude, latitude]`. Não recebe `Feature` nem `FeatureCollection` diretamente. O salvamento pelo Model valida listas não vazias, anéis fechados com pelo menos quatro posições e limites numéricos das coordenadas. A validação é estrutural: não certifica limites oficiais, auto-interseções ou a posição dos recortes internos.

Unidades com apenas área também são enviadas ao mapa. Pontos e áreas são renderizados independentemente, e ambos abrem os detalhes da unidade por clique ou teclado (Enter/Espaço), com retorno de foco ao fechar. Uma geometria de área inválida é ignorada no navegador sem impedir outros marcadores ou áreas. O painel lateral lista todas as unidades, incluindo as que não possuem geometria, e identifica as que estão sem ponto de sede. Cada item abre o mesmo diálogo de detalhes por clique ou teclado, sem criar coordenadas ou marcadores artificiais.

A busca filtra simultaneamente a lista e as geometrias do mapa por nome, sigla, código, localidades atendidas ou endereço. Ignora acentos e maiúsculas e exige todos os termos digitados, mesmo quando aparecem em campos diferentes. O botão **Limpar** restaura a lista. As mensagens distinguem banco vazio de busca sem resultados.

Os controles **Sedes** e **Áreas operacionais** mostram ou ocultam cada camada independentemente, preservando a consulta de todas as unidades na lista. As contagens indicam unidades encontradas/total, unidades encontradas sem ponto válido e geometrias atualmente exibidas em cada camada. Uma unidade com ponto e área conta uma vez na lista e uma vez em cada camada; uma área MultiPolygon conta como uma área da unidade. Desativar camadas não altera a busca nem os dados persistidos. As opções são locais à página e voltam ao estado inicial ao recarregar.

Em instalações existentes, execute `php artisan migrate` para adicionar o campo opcional; a migração preserva os registros e inicia as áreas como `null`. O catálogo inicial não fornece polígonos e o seeder preserva áreas cadastradas posteriormente. O cadastro das áreas reais fica para uma etapa posterior; não há editor ou importador de áreas nesta implementação. Reverter essa migração remove o campo e quaisquer áreas que tenham sido cadastradas nele.

Os testes JavaScript de geometria podem ser executados com `node --test tests/JavaScript/*.test.js`.


## Estrutura do projeto

```text
app/
├── Console/Commands/          # Criação administrativa de usuários
├── Http/Controllers/         # Autenticação e dados da página do mapa
└── Models/                   # Usuários e unidades policiais
config/map.php               # Configuração da base cartográfica
database/
├── factories/                # Dados utilizados pelos testes
├── migrations/               # Estrutura do banco
└── seeders/                  # Carga inicial e catálogo de unidades
resources/
├── css/                      # Estilos da aplicação
├── js/                       # Componente Leaflet e interações
└── views/                    # Páginas e componentes Blade
routes/web.php               # Rotas de login, mapa e logout
tests/
├── Feature/                  # Testes de aplicação
└── Unit/                     # Testes unitários
```
