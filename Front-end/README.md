# Frontend do SindicoPro

SPA responsiva inicializada com Vue 3, TypeScript, Vite, Tailwind CSS 4 e shadcn-vue. O layout compartilhado possui sidebar recolhível, cabeçalho, adaptação para celular e as 60 telas previstas no guia registradas como rotas navegáveis.

## Como executar

Use os comandos da raiz `sindicopro`; não instale Node ou npm na máquina.

Somente o frontend:

```bash
./Devops/dev frontend-up
```

Ambiente completo:

```bash
./Devops/dev up
```

A aplicação abre em <http://localhost:5173>. O código é montado no contêiner e o Vite aplica hot reload.

Para entrar no ambiente local:

```text
E-mail: sindico@pro.com
Senha: 123
```

O frontend inicia mesmo com o backend desligado. Nesse caso a tela informa que a API está indisponível; quando o backend inicia, a consulta é refeita. A URL da API vem de `VITE_API_URL`, definida pelo Compose a partir de `Devops/.env`.

## Estado das telas

As 60 rotas documentadas estão disponíveis para navegação:

- 3 telas públicas de autenticação;
- 44 telas da administração;
- 13 telas da área do usuário.

O menu lateral separa a administração por módulo e possui um atalho no rodapé para alternar entre **Administração** e **Minha área**. Telas de lista possuem atalhos para seus detalhes; recuperação de senha permite abrir a estrutura da redefinição.

Com exceção do login e do painel inicial já integrados, as páginas são estruturas visuais sem dados simulados e sem chamadas ao backend. Botões de operações futuras permanecem desabilitados. Portanto, uma rota navegável não deve ser tratada como função concluída.

Comandos úteis:

```bash
./Devops/dev logs frontend
./Devops/dev test-frontend
./Devops/dev frontend-shell
./Devops/dev frontend-down
```

Dentro de `frontend-shell` ficam disponíveis `npm` e `npx`. Exemplos:

```bash
npm run lint
npm run typecheck
npm test
npm run build
```

## Base instalada

- Vue 3, TypeScript e Vite;
- Vue Router;
- Pinia para sessão e preferências globais futuras;
- TanStack Vue Query para dados remotos;
- Axios como cliente HTTP único;
- Tailwind CSS 4;
- shadcn-vue sobre Reka UI;
- Lucide Vue para ícones;
- Vitest, Vue Test Utils, Testing Library, ESLint e Prettier.

Bibliotecas de formulário, tabelas, gráficos e testes ponta a ponta devem ser adicionadas somente quando a primeira tela realmente precisar delas.

## Estrutura

```text
Front-end/
|-- src/
|   |-- api/                         cliente HTTP compartilhado
|   |-- app/                         raiz da aplicação
|   |-- assets/styles/               tokens e estilos globais
|   |-- components/
|   |   |-- layout/                  sidebar, cabeçalho e navegação
|   |   `-- ui/                      componentes-base do shadcn-vue
|   |-- layouts/                     shells compartilhados
|   |-- modules/
|   |   |-- planejamento/            catálogo temporário das telas ainda não funcionais
|   |   `-- <modulo>/
|   |       |-- api/                 chamadas HTTP do módulo
|   |       |-- components/          componentes reutilizados no módulo
|   |       |-- pages/
|   |       |   `-- <tela>/
|   |       |       |-- NomePage.vue
|   |       |       |-- components/
|   |       |       |-- composables/
|   |       |       `-- schemas/
|   |       |-- queries/             cache e sincronização da API
|   |       `-- types/
|   |-- router/
|   |-- stores/
|   |-- types/
|   `-- main.ts
|-- tests/
|-- docker/
|-- package.json
|-- package-lock.json
|-- Dockerfile
`-- README.md
```

## Separação por tela e função

- `NomePage.vue` coordena a rota e combina as partes da tela;
- componente usado apenas pela tela fica em `pages/<tela>/components`;
- estado exclusivo da tela fica em `pages/<tela>/composables`;
- validação e conversão de formulário ficam em `pages/<tela>/schemas`;
- chamada Axios fica em `modules/<modulo>/api`, nunca dentro da página;
- consulta e invalidação do Vue Query ficam em `modules/<modulo>/queries`;
- componente reutilizado entre telas do módulo sobe para `modules/<modulo>/components`;
- somente componentes realmente genéricos ficam em `src/components`.

Essa regra mantém cada função próxima da sua tela sem criar pastas globais gigantes.

### Como iniciar o desenvolvimento de uma tela estruturada

1. localize a rota em `src/modules/<modulo>/routes.ts`;
2. abra o respectivo `*Page.vue` em `pages/<tela>`;
3. substitua gradualmente `PlannedPage` pela composição real da tela;
4. crie `api`, `queries`, componentes, composables e schemas apenas quando necessários;
5. remova a entrada correspondente de `src/modules/planejamento/planned-pages.ts` quando a tela não depender mais dela;
6. preserve nome, URL, permissão e fluxo de retorno definidos na rota.

`PlannedPage` é somente um andaime visual do frontend. Ele não representa API, regra de negócio ou dado persistido.

## Layout

Áreas autenticadas usam um único `AppLayout`. Não duplicar o mesmo layout para administrador, síndico ou morador; navegação e ações mudam pelas permissões.

```text
AppLayout
|-- AppSidebar
|-- AppHeader
`-- RouterView
```

Para adicionar um componente shadcn-vue, entre no contêiner e execute o gerador lá dentro:

```bash
./Devops/dev frontend-shell
npx shadcn-vue@latest add <componente>
```

O código gerado em `src/components/ui` faz parte do projeto. Composições específicas permanecem junto da tela.

Consulte [LAYOUT_SHADCN_VUE.md](../../Desenvolvimento/frontend/LAYOUT_SHADCN_VUE.md) para o padrão visual.

## Comunicação com o backend

Toda chamada usa `src/api/http.ts`. A autenticação usa cookie de sessão Laravel Sanctum e CSRF: o frontend prepara o cookie, envia as credenciais, consulta `/api/sessao` e mantém somente o contexto do usuário na memória da Pinia. Token, senha e sessão não são gravados em `localStorage` ou `sessionStorage`.

As rotas públicas `/entrar`, `/recuperar-senha` e `/redefinir-senha/:token` usam `AuthLayout`. As áreas `/admin` e `/app` usam `AppLayout` e exigem sessão no guarda global; usuário autenticado não volta às telas exclusivas de visitante. Navegação administrativa é filtrada pelas permissões da sessão, nome e perfil vêm da API, e a ação **Sair** invalida a sessão no backend.

O frontend apresenta dados e valida a interação, mas o backend continua responsável por autorização, regras oficiais e totais.

## Fluxo para uma nova tela

1. confirmar o requisito e o módulo;
2. criar a pasta `modules/<modulo>/pages/<tela>`;
3. criar API e query do módulo quando houver dados remotos;
4. montar a página e manter partes exclusivas próximas dela;
5. registrar a rota e o item de navegação quando aplicável;
6. cobrir carregamento, vazio, erro e acesso negado;
7. validar celular, desktop, teclado e foco;
8. executar `./Devops/dev test-frontend` e o build pelo contêiner.

O padrão completo está em [PADRAO_DESENVOLVIMENTO_FRONTEND.md](../../Desenvolvimento/doc/PADRAO_DESENVOLVIMENTO_FRONTEND.md).
