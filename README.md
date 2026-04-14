# Sistema de Gestao Condominial

Repositorio de documentacao academica para um sistema web de gestao condominial. Aqui estao reunidos o escopo revisado do produto, os questionarios de validacao, a estimativa simplificada de ROI e tres propostas de mockup em HTML, CSS e JavaScript.

## Resumo

Este projeto foi pensado para um condominio com:

- 9 predios
- 144 apartamentos
- 2 saloes de festas

O MVP foi definido para cobrir os seguintes modulos:

- cadastros condominiais
- usuarios, perfis e permissoes
- financeiro
- fechamento mensal e anual
- reservas de ambientes
- comunicados
- ocorrencias

## O que voce encontra aqui

| Area | Conteudo | Acesso rapido |
| --- | --- | --- |
| Documentacao principal | Escopo consolidado, requisitos e planejamento visual | [ProjetoRevisado](ProjetoRevisado/README.md) |
| Validacao do escopo | Questionarios respondidos ao longo do levantamento | [Questionarios](Questionários/README.md) |
| Viabilidade inicial | Estimativa de custo mensal e leitura simplificada de ROI | [ROI](ROI/README.md) |
| Prototipos visuais | Tres versoes de mockup navegavel | [Mockup 1](mockup1/README.md), [Mockup 2](mockup2/README.md), [Mockup 3](mockup3/README.md) |

## Estrutura do repositorio

```text
.
|-- ProjetoRevisado/
|   |-- README.md
|   |-- ResumoDeFuncoesRevisado.txt
|   |-- LevantamentoDeRequisitosRevisado.txt
|   `-- PromptPlanejamentoDesenvolvimento.txt
|-- Questionarios/
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
`-- mockup3/
    |-- README.md
    |-- index.html
    |-- style.css
    `-- app.js
```

Observacao: a pasta real no repositorio se chama `Questionários`. No bloco acima, o nome foi simplificado para facilitar a leitura.

## Fluxo sugerido de leitura

Se a ideia for entender o projeto rapidamente no GitHub, este caminho funciona bem:

1. Leia o [resumo executivo](ProjetoRevisado/ResumoDeFuncoesRevisado.txt)
2. Aprofunde nos [requisitos revisados](ProjetoRevisado/LevantamentoDeRequisitosRevisado.txt)
3. Consulte os [questionarios de validacao](Questionários/README.md)
4. Veja a [estimativa simplificada de ROI](ROI/README.md)
5. Explore os [mockups navegaveis](mockup3/README.md)

## Modulos principais do sistema

| Modulo | O que cobre no MVP |
| --- | --- |
| Cadastros | Predios, apartamentos, pessoas, vinculos, observacoes e veiculos |
| Acesso | Login, perfis, permissoes e regras por usuario |
| Financeiro | Contas, categorias, lancamentos, cobrancas, inadimplencia e ajustes |
| Fechamentos | Fechamento mensal e anual, bloqueio de periodo e historico de reabertura |
| Reservas | Ambientes, horarios, taxas, bloqueios e validacoes administrativas |
| Comunicados | Publicacao segmentada, historico e anexos |
| Ocorrencias | Registro, acompanhamento, tratamento administrativo e anexos |

## Como visualizar os mockups

Os mockups sao estaticos e nao exigem instalacao.

1. Abra uma das pastas `mockup1`, `mockup2` ou `mockup3`
2. Execute o arquivo `index.html` no navegador
3. Navegue entre as telas pelos links internos

Entradas recomendadas:

- [mockup1/index.html](mockup1/index.html)
- [mockup2/index.html](mockup2/index.html)
- [mockup3/index.html](mockup3/index.html)

## Decisoes importantes de escopo

- O sistema preserva historico e evita exclusao fisica de registros relevantes
- O sindico possui acesso total
- O conselho possui acesso somente para consulta
- O morador visualiza sua propria cota e os fechamentos publicados
- O proprietario nao residente pode acessar o sistema com regras especificas configuraveis
- WhatsApp foi removido do escopo atual
- Integracao bancaria automatica e app mobile nativo ficaram fora do escopo obrigatorio do MVP

## Contexto do repositorio

Este material foi organizado para apresentacao academica e apoio ao planejamento do produto. O foco atual esta em documentacao, definicao do escopo e exploracao visual da interface, e nao em uma aplicacao final implementada em backend e banco de dados.

## Navegacao rapida

- [Documentacao revisada](ProjetoRevisado/README.md)
- [Questionarios](Questionários/README.md)
- [ROI](ROI/README.md)
- [Mockup 1](mockup1/README.md)
- [Mockup 2](mockup2/README.md)
- [Mockup 3](mockup3/README.md)
