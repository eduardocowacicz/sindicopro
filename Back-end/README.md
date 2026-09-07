# Backend do SindicoPro

API Laravel 13 responsável por validação de regras de negócio, autorização e persistência no PostgreSQL. O projeto e o lockfile já estão inicializados.

## Como executar

Use sempre os comandos da raiz `sindicopro`; não instale PHP ou Composer na máquina.

Somente o backend e seus serviços:

```bash
./Devops/dev backend-up
```

Ambiente completo, incluindo o frontend:

```bash
./Devops/dev up
```

O Compose injeta as variáveis de `Devops/.env`, instala as dependências no volume Docker, aplica migrations pendentes e executa o seeder local idempotente. Não é necessário criar `Back-end/.env` para desenvolvimento local.

Verificação:

- API mínima: <http://localhost:8000/api/status>;
- saúde do processo: <http://localhost:8000/up>.

O login local é `sindico@pro.com` com senha `123`. Esse usuário existe somente em `local` e `testing`, recebe o perfil `SINDICO` e todas as permissões.

Comandos úteis:

```bash
./Devops/dev logs backend
./Devops/dev migrate
./Devops/dev seed
./Devops/dev test-backend
./Devops/dev backend-shell
./Devops/dev backend-down
```

Dentro de `backend-shell` ficam disponíveis `php`, `artisan`, `composer` e `vendor/bin/pint`.

## Arquitetura simples

Cada função segue somente as responsabilidades necessárias:

```text
Rota
  -> Form Request
  -> Controller
  -> Service
  -> Repository
  -> Model/PostgreSQL
  -> Resource/JSON
```

- Form Request valida formato, tipo, obrigatoriedade e normalização da entrada;
- Controller recebe a entrada validada, chama o Service e escolhe a resposta;
- Service valida e executa a regra de negócio, transação e auditoria;
- Repository executa consultas e gravações no banco;
- Model define tabela, relações e casts;
- Policy autoriza a ação e Resource controla os dados devolvidos.

O Controller não contém regra de negócio e o Service não faz consulta Eloquent direta. Para uma operação simples, use uma classe por responsabilidade e evite camadas adicionais.

Não criar Actions, DTOs, classes Query, interfaces ou `BaseRepository` automaticamente. Uma abstração só entra quando resolver repetição ou variação real.

## Estrutura

```text
Back-end/
|-- app/
|   |-- Http/
|   |   |-- Controllers/<Modulo>/
|   |   |-- Requests/<Modulo>/
|   |   `-- Resources/<Modulo>/
|   |-- Models/
|   |-- Policies/
|   |-- Repositories/<Modulo>/
|   `-- Services/<Modulo>/
|-- config/
|-- database/
|   |-- factories/
|   |-- migrations/
|   `-- seeders/
|-- routes/api.php
|-- tests/
|-- docker/
|-- composer.json
|-- composer.lock
|-- Dockerfile
`-- README.md
```

As migrations estão agrupadas por domínio, na ordem oficial: cadastro e acesso, arquivos, financeiro, fechamentos, reservas, cobranças, comunicados, ocorrências, vínculos de arquivos, auditoria e filas. A base possui 64 tabelas funcionais, além da tabela técnica de controle de migrations.

As pastas são criadas quando a primeira função do módulo precisar delas. Não existem pastas `Api/V1`, `V1` ou `V2`; a rota usa apenas o prefixo `/api`.

## Exemplo de uma função

Para o cadastro de blocos:

```text
app/Http/Controllers/Cadastros/BlocoController.php
app/Http/Requests/Cadastros/SalvarBlocoRequest.php
app/Http/Resources/Cadastros/BlocoResource.php
app/Services/Cadastros/BlocoService.php
app/Repositories/Cadastros/BlocoRepository.php
app/Models/Bloco.php
app/Policies/BlocoPolicy.php
```

Sequência mínima:

1. criar migration e Model quando houver persistência;
2. criar Repository com o acesso necessário ao banco;
3. criar Service com a regra de negócio;
4. criar Form Request para a entrada;
5. criar Controller para direcionar o fluxo;
6. criar Resource apenas quando for necessário controlar a saída;
7. registrar a rota em `routes/api.php`;
8. adicionar testes do comportamento.

## Regras essenciais

- PostgreSQL é o banco da aplicação;
- IDs públicos usam UUID; IDs internos não saem da API;
- valores monetários não usam `float`;
- regras críticas usam transações e constraints;
- autorização oficial sempre ocorre no backend;
- arquivos privados são entregues somente após autorização;
- chamadas HTTP retornam JSON nos caminhos `/api/*`;
- código é organizado por módulo de negócio, sem versionamento por pasta.

## Autenticação implementada

O fluxo usa Sanctum stateful e cookie de sessão no banco:

```text
GET  /sanctum/csrf-cookie
POST /login
GET  /api/sessao
POST /logout
```

`AutenticacaoController` não existe como classe genérica: login, sessão e logout têm Controllers pequenos, todos direcionados ao mesmo `AutenticacaoService` e `AutenticacaoRepository`. A sessão retorna identidade, perfis, permissões efetivas, indicador de acesso integral e unidades vinculadas. Senha e IDs internos nunca são devolvidos.

O contrato está em [docs/openapi.yaml](docs/openapi.yaml).

O padrão completo está em [PADRAO_DESENVOLVIMENTO_BACKEND.md](../../Desenvolvimento/doc/PADRAO_DESENVOLVIMENTO_BACKEND.md).
