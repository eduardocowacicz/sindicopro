# Mockup 4

Quarta proposta visual do sistema de gestão condominial, agora estruturada como uma aplicação React com TypeScript, Vite e componentes `shadcn/ui`. Esta versão simula uma SPA com navegação por perfil, estado local em memória e cobertura mais próxima de um front-end real.

## Características

- login demo com troca rápida entre perfis
- área administrativa e área do morador
- dashboards, cadastros, financeiro, reservas, comunicados e ocorrências
- layout responsivo com alternância entre tema claro e escuro
- dados simulados em memória com ações locais para cadastro e atualização

## Stack utilizada

- React 18
- TypeScript
- Vite
- Tailwind CSS
- `shadcn/ui` e Radix UI
- Zustand para estado global
- Recharts para gráficos
- Vitest e Playwright já configurados

## Perfis de demonstração

O formulário de login é ilustrativo. Para navegar pelo mockup, use os botões de acesso demo na tela inicial:

| Perfil | Área inicial | Observação |
| --- | --- | --- |
| Master | `/admin` | Visualiza inclusive o módulo de usuários |
| Síndico | `/admin` | Fluxo administrativo completo |
| Subsíndico | `/admin` | Fluxo administrativo com perfil intermediário |
| Conselho | `/admin` | Navegação administrativa para consulta |
| Morador | `/morador` | Área da unidade e serviços do morador |

## Módulos disponíveis

| Área | Telas principais |
| --- | --- |
| Acesso | Login demo e página 404 tratada |
| Administração | Dashboard, blocos e apartamentos, moradores, veículos, movimentações, contas bancárias, categorias, cota condominial, fechamento mensal, fechamento anual, reservas, comunicados, ocorrências, usuários e configurações |
| Morador | Dashboard, meu apartamento, reservar ambiente, comunicados e minhas ocorrências |

## Estrutura resumida

- [src/pages/admin](src/pages/admin): telas do fluxo administrativo
- [src/pages/morador](src/pages/morador): telas do fluxo do morador
- [src/components/layouts](src/components/layouts): layouts com sidebar e navegação por perfil
- [src/data/mockData.ts](src/data/mockData.ts): base simulada do mockup
- [src/stores/useStore.ts](src/stores/useStore.ts): estado global e ações em memória

## Como executar

Entre na pasta `mockup4` e rode os comandos abaixo:

```bash
npm install
npm run dev
```

Comandos úteis:

```bash
npm run build
npm run test
```

Se preferir Bun, o projeto também já possui arquivos de lock compatíveis.

## Observações

- os dados são simulados e não há backend integrado
- alterações feitas nas telas persistem apenas enquanto a aplicação está aberta
- o `mockup4` é a versão mais próxima de uma futura aplicação web completa

## Papel desta versão

Entre os quatro mockups, esta é a versão mais madura do front-end e a melhor referência para evolução posterior do produto.

## Voltar

- [README principal](../README.md)
