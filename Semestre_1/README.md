# Sistema de Gestão Condominial

Repositório de documentação acadêmica para um sistema web de gestão condominial. Aqui estão reunidos o escopo revisado do produto, os questionários de validação, a estimativa simplificada de ROI e quatro propostas de mockup: três versões estáticas em HTML, CSS e JavaScript e uma versão SPA em React, TypeScript e Vite.

## Resumo

Este projeto foi pensado para um condomínio com:

- 9 prédios
- 144 apartamentos
- 2 salões de festas

O MVP foi definido para cobrir os seguintes módulos:

- cadastros condominiais
- usuários, perfis e permissões
- financeiro
- fechamento mensal e anual
- reservas de ambientes
- comunicados
- ocorrências

## O que você encontra aqui

| Área | Conteúdo | Acesso rápido |
| --- | --- | --- |
| Documentação principal | Escopo consolidado, requisitos e planejamento visual | [ProjetoRevisado](ProjetoRevisado/README.md) |
| Validação do escopo | Questionários respondidos ao longo do levantamento | [Questionários](Questionários/README.md) |
| Viabilidade inicial | Estimativa de custo mensal e leitura simplificada de ROI | [ROI](ROI/README.md) |
| Protótipos visuais | Quatro versões de mockup navegável | [Mockup 1](mockup1/README.md), [Mockup 2](mockup2/README.md), [Mockup 3](mockup3/README.md), [Mockup 4](mockup4/README.md) |

## Estrutura do repositório

```text
.
|-- ProjetoRevisado/
|   |-- README.md
|   |-- ResumoDeFuncoesRevisado.txt
|   |-- LevantamentoDeRequisitosRevisado.txt
|   `-- PromptPlanejamentoDesenvolvimento.txt
|-- Questionários/
|   |-- README.md
|   |-- 01-QuestionarioInicialSolicitante.txt
|   |-- 02-QuestionarioComplementarRevisao.txt
|   `-- 03-QuestionarioPendenciasFinais.txt
|-- ROI/
|   |-- README.md
|   `-- LevantamentoROIProjeto.txt
|-- mockup1/
|   |-- README.md
|   |-- index.html
|   |-- style.css
|   `-- app.js
|-- mockup2/
|   |-- README.md
|   |-- index.html
|   |-- style.css
|   `-- app.js
|-- mockup3/
|   |-- README.md
|   |-- index.html
|   |-- style.css
|   `-- app.js
`-- mockup4/
    |-- README.md
    |-- package.json
    |-- src/
    `-- public/
```

## Evolução dos mockups

| Versão | Tecnologias | Papel no projeto |
| --- | --- | --- |
| Mockup 1 | HTML, CSS e JavaScript | Primeira exploração visual do sistema |
| Mockup 2 | HTML, CSS e JavaScript | Expansão do escopo funcional do MVP |
| Mockup 3 | HTML, CSS e JavaScript | Versão estática mais madura para apresentação |
| Mockup 4 | React, TypeScript, Vite e `shadcn/ui` | Protótipo SPA mais próximo de uma futura aplicação |

## Fluxo sugerido de leitura

Se a ideia for entender o projeto rapidamente no GitHub, este caminho funciona bem:

1. Leia o [resumo executivo](ProjetoRevisado/ResumoDeFuncoesRevisado.txt)
2. Aprofunde nos [requisitos revisados](ProjetoRevisado/LevantamentoDeRequisitosRevisado.txt)
3. Consulte os [questionários de validação](Questionários/README.md)
4. Veja a [estimativa simplificada de ROI](ROI/README.md)
5. Explore os [mockups navegáveis](mockup4/README.md)

## Módulos principais do sistema

| Módulo | O que cobre no MVP |
| --- | --- |
| Cadastros | Prédios, apartamentos, pessoas, vínculos, observações e veículos |
| Acesso | Login, perfis, permissões e regras por usuário |
| Financeiro | Contas, categorias, lançamentos, cobranças, inadimplência e ajustes |
| Fechamentos | Fechamento mensal e anual, bloqueio de período e histórico de reabertura |
| Reservas | Ambientes, horários, taxas, bloqueios e validações administrativas |
| Comunicados | Publicação segmentada, histórico e anexos |
| Ocorrências | Registro, acompanhamento, tratamento administrativo e anexos |

## Como visualizar os mockups

Os `mockup1`, `mockup2` e `mockup3` são estáticos e não exigem instalação.

1. Abra uma dessas pastas
2. Execute o arquivo `index.html` no navegador
3. Navegue entre as telas pelos links internos

O `mockup4` é uma aplicação React/Vite e exige instalação das dependências.

```bash
cd mockup4
npm install
npm run dev
```

Entradas recomendadas:

- [mockup1/index.html](mockup1/index.html)
- [mockup2/index.html](mockup2/index.html)
- [mockup3/index.html](mockup3/index.html)
- [mockup4/README.md](mockup4/README.md)

## Decisões importantes de escopo

- O sistema preserva histórico e evita exclusão física de registros relevantes
- O síndico possui acesso total
- O conselho possui acesso somente para consulta
- O morador visualiza sua própria cota e os fechamentos publicados
- O proprietário não residente pode acessar o sistema com regras específicas configuráveis
- WhatsApp foi removido do escopo atual
- Integração bancária automática e app mobile nativo ficaram fora do escopo obrigatório do MVP

## Contexto do repositório

Este material foi organizado para apresentação acadêmica e apoio ao planejamento do produto. O foco atual está em documentação, definição do escopo e exploração visual da interface. O `mockup4` amplia essa exploração com uma SPA front-end navegável, ainda sem backend ou banco de dados reais.

## Navegação rápida

- [Documentação revisada](ProjetoRevisado/README.md)
- [Questionários](Questionários/README.md)
- [ROI](ROI/README.md)
- [Mockup 1](mockup1/README.md)
- [Mockup 2](mockup2/README.md)
- [Mockup 3](mockup3/README.md)
- [Mockup 4](mockup4/README.md)
