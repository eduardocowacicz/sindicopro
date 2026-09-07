# Ambiente local do SindicoPro

Esta pasta é o único ponto de operação do projeto local. Ela cria o ambiente, constrói as imagens, inicia as aplicações, executa migrations, testes e comandos internos. Nenhuma dependência da aplicação precisa ser instalada fora do Docker.

## Pré-requisito único

- Docker Engine com Docker Compose v2, ou Docker Desktop com Compose.

PHP, Composer, Node, npm, PostgreSQL, OpenSSL e Make não são necessários no computador.

## Primeiro uso em qualquer computador

Na raiz do repositório `sindicopro`:

```bash
./Devops/dev up
```

Se o sistema de arquivos tiver removido a permissão de execução do arquivo, use:

```bash
sh Devops/dev up
```

O comando executa o fluxo completo:

1. cria `Devops/.env` a partir de `.env.example` se ele não existir;
2. gera `APP_KEY` dentro de um contêiner descartável;
3. constrói as imagens do Laravel e do Vue;
4. instala Composer e npm nos volumes Docker usando os lockfiles;
5. inicia PostgreSQL, Laravel, worker, scheduler, Vue e Mailpit;
6. aplica automaticamente as migrations pendentes antes de servir a API;
7. executa o seeder idempotente local e garante o usuário de desenvolvimento.

O primeiro uso demora mais por causa do download das imagens e dependências. As próximas inicializações reutilizam os caches e volumes.

Confira o resultado:

```bash
./Devops/dev ps
```

Endereços:

| Recurso | Endereço |
| --- | --- |
| Frontend Vue | <http://localhost:5173> |
| Status da API | <http://localhost:8000/api/status> |
| Saúde do Laravel | <http://localhost:8000/up> |
| Mailpit | <http://localhost:8025> |
| PostgreSQL | `localhost:5432` |

Credencial criada automaticamente apenas para desenvolvimento local:

```text
E-mail: sindico@pro.com
Senha: 123
Perfil: SINDICO — acesso integral
```

## Preparar o ambiente explicitamente

O comando `up` já faz isto de forma automática. Caso queira conferir ou editar a configuração antes de iniciar:

```bash
./Devops/dev init
./Devops/dev config
./Devops/dev up
```

- `init` cria `Devops/.env`, preenche a chave e não sobrescreve valores existentes;
- `config` valida o Compose sem iniciar os serviços;
- `up` constrói e inicia o ambiente completo.

Não crie `.env` em `Back-end` ou `Front-end` para o ambiente local. O Compose injeta a configuração dos dois projetos a partir de `Devops/.env`. O arquivo é local, tem permissão restrita e não é versionado.

## Iniciar frontend e backend separadamente

Somente o frontend:

```bash
./Devops/dev frontend-up
./Devops/dev frontend-down
```

O Vue inicia mesmo se a API estiver desligada. A tela mostra a indisponibilidade da conexão e volta a consultar o backend quando ele estiver ativo.

Somente o conjunto do backend:

```bash
./Devops/dev backend-up
./Devops/dev backend-down
```

`backend-up` inicia `database`, `mailpit`, `backend`, `worker` e `scheduler`, sem iniciar o frontend. Essa independência também permite trabalhar com apenas uma das aplicações.

Quando ambos estão ativos, `VITE_API_URL` aponta o navegador para `http://localhost:8000`. Não existe dependência de inicialização entre `frontend` e `backend`.

## Comandos diários

| Comando | Função |
| --- | --- |
| `./Devops/dev up` | constrói e inicia tudo |
| `./Devops/dev down` | encerra tudo sem apagar dados |
| `./Devops/dev restart` | reinicia os serviços |
| `./Devops/dev ps` | mostra estado e saúde |
| `./Devops/dev logs` | acompanha todos os logs |
| `./Devops/dev logs frontend` | acompanha somente o Vue |
| `./Devops/dev logs backend` | acompanha somente o Laravel |
| `./Devops/dev build` | reconstrói todas as imagens |
| `./Devops/dev migrate` | aplica migrations manualmente |
| `./Devops/dev seed` | executa os seeders |
| `./Devops/dev database-shell` | abre o `psql` no contêiner |
| `./Devops/dev backend-shell` | abre shell no contêiner PHP |
| `./Devops/dev frontend-shell` | abre shell no contêiner Node |
| `./Devops/dev test-backend` | cria/reutiliza um PostgreSQL isolado e executa testes Laravel |
| `./Devops/dev test-frontend` | executa testes Vue |
| `./Devops/dev quality` | valida Composer, Pint, testes Laravel e toda a qualidade Vue |

O `Makefile` permanece apenas como atalho opcional para quem já usa Make. O fluxo oficial e portátil é `./Devops/dev`.

## Arquivos

```text
Devops/
|-- .env.example          valores locais documentados
|-- compose.yaml          composição de todos os serviços
|-- dev                   interface principal de operação
|-- Makefile              atalhos opcionais para `dev`
|-- scripts/init-env.sh   compatibilidade com o fluxo antigo
`-- README.md
```

Os Dockerfiles continuam nas aplicações:

- `Front-end/Dockerfile` pode construir e executar somente o Vue;
- `Back-end/Dockerfile` pode construir e executar somente o Laravel;
- `Devops/compose.yaml` reúne os dois para o desenvolvimento local.

## Variáveis locais

Edite `Devops/.env` somente quando precisar alterar os padrões:

| Variável | Padrão | Uso |
| --- | --- | --- |
| `PHP_VERSION` | `8.4` | imagem do backend |
| `NODE_VERSION` | `22` | imagem do frontend |
| `BACKEND_PORT` | `8000` | porta HTTP da API |
| `FRONTEND_PORT` | `5173` | porta HTTP do Vite |
| `POSTGRES_PORT` | `5432` | acesso local ao banco |
| `MAILPIT_HTTP_PORT` | `8025` | interface de e-mails |
| `POSTGRES_DB` | `sindicopro` | banco local |
| `POSTGRES_USER` | `sindicopro` | usuário local |
| `POSTGRES_PASSWORD` | `sindicopro_local` | senha exclusivamente local |
| `APP_KEY` | gerada | chave local do Laravel |

Variáveis `VITE_*` ficam expostas no navegador e nunca podem conter segredos.

## Volumes e dependências

- `postgres_data`: preserva os dados do PostgreSQL;
- `backend_vendor`: preserva as dependências Composer;
- `frontend_node_modules`: preserva as dependências npm.

Os entrypoints HTTP comparam o hash de `composer.lock` e `package-lock.json`. Se um lockfile mudar após atualizar o repositório, as dependências do volume são sincronizadas automaticamente no próximo início. Worker e scheduler aguardam o backend concluir essa sincronização, evitando instalações Composer concorrentes.

Ao iniciar o backend em ambiente local, o entrypoint executa `migrate --force` e depois o seeder de desenvolvimento. O seeder pode ser repetido com segurança e mantém a credencial local acima. Produção nunca recebe esse usuário.

`down` preserva os três volumes. Não existe comando no projeto para apagá-los, reduzindo o risco de perda acidental.

## Diagnóstico

Falha de permissão ao executar um script:

```bash
sh Devops/dev up
```

Porta ocupada: altere a porta correspondente em `Devops/.env` e execute `./Devops/dev up` novamente.

API ou frontend indisponível:

```bash
./Devops/dev ps
./Devops/dev logs backend
./Devops/dev logs frontend
```

Banco indisponível:

```bash
./Devops/dev logs database
```

Se o Docker responder com erro de acesso ao socket, ajuste a instalação/permissão do próprio Docker conforme o sistema operacional. Isso não exige instalar dependências do SindicoPro no host.
