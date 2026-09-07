# SindicoPro

Sistema de gestão condominial com API Laravel, SPA Vue e PostgreSQL. Os projetos já estão inicializados e todo o ambiente local é administrado pela pasta `Devops`.

## Início rápido

O único pré-requisito é Docker com Docker Compose v2.

Depois de baixar o repositório, entre na pasta `sindicopro` e execute:

```bash
./Devops/dev up
```

Esse comando cria `Devops/.env` quando necessário, gera a chave local, constrói as imagens, instala dependências pelos lockfiles, inicia todos os serviços, aplica as migrations e prepara os dados locais de desenvolvimento.

Entre em <http://localhost:5173> com o usuário local:

```text
E-mail: sindico@pro.com
Senha: 123
```

Esse acesso é criado somente nos ambientes `local` e `testing`, possui o perfil `SINDICO` e acesso integral. Ele não é criado em produção.

Endereços locais:

- frontend: <http://localhost:5173>;
- API: <http://localhost:8000/api/status>;
- saúde do Laravel: <http://localhost:8000/up>;
- e-mails locais: <http://localhost:8025>;
- PostgreSQL: `localhost:5432`.

Consulte [Devops/README.md](Devops/README.md) para configuração do ambiente, execução separada, comandos e diagnóstico.

## Execução independente

```bash
./Devops/dev frontend-up   # somente Vue
./Devops/dev backend-up    # Laravel, PostgreSQL, worker, scheduler e Mailpit
```

O frontend não depende do backend para iniciar. Quando os dois estão ativos, o navegador usa `VITE_API_URL` para acessar a API. O backend também não depende do frontend.

Para encerrar separadamente:

```bash
./Devops/dev frontend-down
./Devops/dev backend-down
```

## Estrutura do repositório

```text
sindicopro/
|-- Back-end/          API Laravel e regras de negócio
|-- Front-end/         SPA Vue, layout e telas
|-- Devops/            ambiente Docker e comandos de operação
|-- Semestre_1/        material histórico preservado
|-- .gitignore
`-- README.md
```

`Devops` contém somente o necessário para executar o sistema. `Back-end` e `Front-end` possuem Dockerfiles independentes, código e documentação próprios.

## Arquitetura

```text
Vue -> HTTP/JSON -> Laravel -> PostgreSQL
                         |-> worker
                         `-> scheduler
```

No backend, cada função segue o fluxo simples:

```text
Rota -> Form Request -> Controller -> Service -> Repository -> Model/Banco
                              `-> Resource -> JSON
```

- Form Request valida formato e campos de entrada;
- Controller apenas direciona a requisição;
- Service valida e executa regras de negócio;
- Repository concentra o acesso ao banco;
- Model representa dados e relacionamentos;
- Resource define a resposta da API.

Não existe divisão de API por pastas `V1` ou `V2`. Controllers, Requests, Resources, Services e Repositories são agrupados diretamente por módulo de negócio.

No frontend, as 60 telas planejadas já possuem rota e estrutura navegável. A separação ocorre por módulo e tela:

```text
src/modules/<modulo>/
|-- api/
|-- components/
|-- pages/<tela>/
|-- queries/
|-- schemas/
`-- types/
```

Código exclusivo permanece junto da tela. Algo só sobe para o módulo ou para uma pasta compartilhada quando passa a ser realmente reutilizado.

As estruturas navegáveis ainda sem função exibem os blocos de conteúdo previstos e mantêm operações sem backend desabilitadas. Consulte o [README do frontend](Front-end/README.md) antes de transformar uma delas em tela funcional.

## Documentação

- [Backend](Back-end/README.md)
- [Frontend](Front-end/README.md)
- [Devops](Devops/README.md)
- [Planejamento de desenvolvimento](../Desenvolvimento/README.md)

O material histórico não é fonte ativa do desenvolvimento e deve permanecer preservado.
