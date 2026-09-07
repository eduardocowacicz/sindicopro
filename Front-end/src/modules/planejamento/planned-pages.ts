import type { RouteLocationRaw } from 'vue-router'

export interface PlannedPageSection {
  title: string
  description?: string
  items: string[]
}

export interface PlannedPageAction {
  label: string
  to?: RouteLocationRaw
  primary?: boolean
}

export interface PlannedPageConfig {
  title: string
  description: string
  module: string
  indicators: string[]
  sections: PlannedPageSection[]
  actions: PlannedPageAction[]
}

const section = (title: string, items: string[], description?: string): PlannedPageSection => ({
  title,
  description,
  items,
})

const action = (label: string, to?: RouteLocationRaw, primary = false): PlannedPageAction => ({
  label,
  to,
  primary,
})

const page = (
  module: string,
  title: string,
  description: string,
  sections: PlannedPageSection[],
  indicators: string[] = [],
  actions: PlannedPageAction[] = [],
): PlannedPageConfig => ({ module, title, description, sections, indicators, actions })

const detail = (name: string): RouteLocationRaw => ({ name, params: { id: 'estrutura-inicial' } })

export const plannedPages = {
  blocksList: page(
    'Condomínio',
    'Blocos',
    'Cadastro dos blocos e prédios do condomínio.',
    [
      section('Filtros', ['Pesquisa por código ou nome', 'Situação ativa ou inativa']),
      section('Tabela', [
        'Código e nome',
        'Quantidade de andares',
        'Unidades ativas e ocupadas',
        'Situação e ações',
      ]),
    ],
    [],
    [action('Novo bloco', undefined, true), action('Abrir detalhe', detail('admin-bloco-detalhe'))],
  ),
  blockDetail: page(
    'Condomínio',
    'Detalhe do bloco',
    'Resumo cadastral, observações e unidades vinculadas.',
    [
      section('Resumo', ['Código', 'Nome', 'Quantidade de andares', 'Situação']),
      section('Unidades vinculadas', [
        'Apartamento',
        'Ocupação',
        'Responsáveis atuais',
        'Situação',
      ]),
    ],
    [],
    [
      action('Voltar para blocos', { name: 'admin-blocos' }),
      action('Editar bloco', undefined, true),
    ],
  ),
  unitsList: page(
    'Condomínio',
    'Apartamentos',
    'Unidades organizadas por bloco, ocupação e vínculos atuais.',
    [
      section('Filtros', ['Bloco', 'Ocupação', 'Situação', 'Número ou pessoa vinculada']),
      section('Tabela', [
        'Bloco e apartamento',
        'Andar e ocupação',
        'Responsáveis e moradores',
        'Situação e ações',
      ]),
    ],
    [],
    [
      action('Novo apartamento', undefined, true),
      action('Abrir detalhe', detail('admin-apartamento-detalhe')),
    ],
  ),
  unitDetail: page(
    'Condomínio',
    'Detalhe do apartamento',
    'Visão completa da unidade e de seus relacionamentos.',
    [
      section('Abas', ['Resumo', 'Pessoas', 'Veículos', 'Boletos', 'Reservas', 'Ocorrências']),
      section('Cabeçalho', [
        'Bloco e apartamento',
        'Ocupação',
        'Contatos principais',
        'Observações',
      ]),
    ],
    [],
    [
      action('Voltar para apartamentos', { name: 'admin-apartamentos' }),
      action('Editar apartamento', undefined, true),
    ],
  ),
  peopleList: page(
    'Condomínio',
    'Pessoas e vínculos',
    'Pessoas cadastradas independentemente de vínculos e usuários.',
    [
      section('Filtros', ['Nome, CPF ou e-mail', 'Bloco e unidade', 'Tipo de vínculo', 'Situação']),
      section('Tabela', [
        'Nome e CPF mascarado',
        'Telefone e e-mail',
        'Vínculos vigentes',
        'Usuário, situação e ações',
      ]),
    ],
    [],
    [
      action('Nova pessoa', undefined, true),
      action('Abrir detalhe', detail('admin-pessoa-detalhe')),
    ],
  ),
  personDetail: page(
    'Condomínio',
    'Detalhe da pessoa',
    'Dados pessoais, vínculos, responsabilidades e acesso.',
    [
      section('Dados pessoais', ['Foto', 'Nome e CPF', 'Telefone e e-mail', 'Situação']),
      section('Relacionamentos', [
        'Vínculos com unidades',
        'Responsabilidades financeiras',
        'Usuário de acesso',
        'Histórico permitido',
      ]),
    ],
    [],
    [
      action('Voltar para pessoas', { name: 'admin-pessoas' }),
      action('Gerenciar vínculo', undefined, true),
    ],
  ),
  vehiclesList: page(
    'Condomínio',
    'Veículos',
    'Veículos relacionados às unidades e aos responsáveis.',
    [
      section('Filtros', ['Unidade', 'Responsável', 'Placa ou modelo', 'Situação']),
      section('Tabela', [
        'Responsável e unidade',
        'Placa e modelo',
        'Cor e vaga',
        'Situação e ações',
      ]),
    ],
    [],
    [action('Novo veículo', undefined, true)],
  ),

  bank: page(
    'Financeiro',
    'Banco',
    'Cadastro da conta bancária utilizada pelo condomínio.',
    [
      section('Dados bancários', [
        'Banco e código',
        'Agência',
        'Conta com exibição mascarada',
        'Titular da conta',
      ]),
      section('Controle', [
        'Situação do cadastro',
        'Data da última alteração',
        'Usuário responsável',
        'Histórico preservado',
      ]),
    ],
    [],
    [action('Editar banco', undefined, true)],
  ),
  payments: page(
    'Financeiro',
    'Pagamentos',
    'Lançamento dos pagamentos já realizados pelo condomínio.',
    [
      section('Filtros', [
        'Competência e data do pagamento',
        'Banco',
        'Tipo de pagamento',
        'Descrição ou favorecido',
      ]),
      section('Tabela e lançamento', [
        'Data, descrição e favorecido',
        'Banco e tipo de pagamento',
        'Valor pago',
        'Comprovante, situação e ações',
      ]),
    ],
    ['Total pago no período', 'Pagamentos do fechamento', 'Pagamentos pendentes de fechamento'],
    [action('Novo pagamento', undefined, true)],
  ),
  receipts: page(
    'Financeiro',
    'Recebimentos',
    'Boletos gerados pelo fechamento para moradores ou proprietários.',
    [
      section('Filtros', [
        'Competência',
        'Bloco e apartamento',
        'Morador ou proprietário responsável',
        'Vencimento e situação do boleto',
      ]),
      section('Tabela', [
        'Número do boleto e competência',
        'Unidade e responsável',
        'Emissão e vencimento',
        'Valor, recebimento e situação',
      ]),
    ],
    ['Total gerado', 'Total recebido', 'Total em aberto', 'Total vencido'],
  ),
  paymentTypes: page(
    'Financeiro',
    'Tipos de pagamentos',
    'Regras usadas para ratear cada pagamento no fechamento.',
    [
      section('Tabela', [
        'Nome e descrição',
        'Cobrar do morador ou do proprietário',
        'Permite isenção',
        'Quantidade de isenções e situação',
      ]),
      section('Cadastro e isenções', [
        'Nome do tipo',
        'Responsável pelo boleto',
        'Moradores ou unidades isentos',
        'Motivo e vigência da isenção',
      ]),
    ],
    [],
    [action('Novo tipo de pagamento', undefined, true)],
  ),
  financialReports: page(
    'Financeiro',
    'Relatórios financeiros',
    'Consultas baseadas em bancos, pagamentos, fechamentos e recebimentos.',
    [
      section('Relatórios disponíveis', [
        'Pagamentos por período, banco e tipo',
        'Recebimentos gerados, pagos, abertos e vencidos',
        'Resumo do fechamento mensal',
        'Valores por unidade e responsável',
      ]),
      section('Resultado', [
        'Filtros por competência',
        'Pré-visualização paginada',
        'Totais do relatório',
        'Exportação em PDF ou Excel',
      ]),
    ],
    [],
    [action('Gerar relatório', undefined, true)],
  ),

  monthlyClosingsList: page(
    'Fechamentos',
    'Fechamentos mensais',
    'Competências fechadas a partir dos pagamentos realizados.',
    [
      section('Filtros', ['Ano', 'Situação']),
      section('Tabela', [
        'Mês e situação',
        'Quantidade e total de pagamentos',
        'Total distribuído',
        'Boletos gerados e ações',
      ]),
    ],
    [],
    [action('Abrir fechamento', detail('admin-fechamento-mensal-detalhe'))],
  ),
  monthlyClosingDetail: page(
    'Fechamentos',
    'Detalhe do fechamento mensal',
    'Pagamentos incluídos, rateio aplicado e boletos gerados.',
    [
      section('Composição', [
        'Pagamentos incluídos',
        'Agrupamento por tipo de pagamento',
        'Isenções aplicadas',
        'Memória de rateio por unidade',
      ]),
      section('Resultado', [
        'Moradores e proprietários responsáveis',
        'Boletos gerados',
        'Valor de cada unidade',
        'Relatório do fechamento',
      ]),
    ],
    ['Total de pagamentos', 'Unidades cobradas', 'Total dos boletos'],
    [
      action('Voltar para fechamentos', { name: 'admin-fechamentos-mensais' }),
      action('Fechar período', undefined, true),
    ],
  ),
  reservationsList: page(
    'Reservas',
    'Agenda de reservas',
    'Calendário e lista administrativa de reservas.',
    [
      section('Agenda', [
        'Calendário ou lista',
        'Legenda por situação',
        'Ambiente e faixa',
        'Período',
      ]),
      section('Filtros e dados', [
        'Bloco, unidade e solicitante',
        'Situação',
        'Origem administrativa',
        'Taxa e exceções',
      ]),
    ],
    [],
    [
      action('Nova reserva', undefined, true),
      action('Abrir detalhe', detail('admin-reserva-detalhe')),
    ],
  ),
  reservationDetail: page(
    'Reservas',
    'Detalhe da reserva',
    'Faixa, solicitante, taxa e histórico.',
    [
      section('Reserva', [
        'Ambiente, data e faixa',
        'Unidade e solicitante',
        'Taxa e origem',
        'Validação e situação',
      ]),
      section('Histórico', ['Linha do tempo', 'Validação', 'Cancelamento', 'Auditoria permitida']),
    ],
    [],
    [
      action('Voltar para agenda', { name: 'admin-reservas' }),
      action('Validar reserva', undefined, true),
    ],
  ),
  environmentsList: page(
    'Reservas',
    'Ambientes e regras',
    'Ambientes, faixas, taxas e políticas de reserva.',
    [
      section('Ambientes', ['Nome e descrição', 'Situação', 'Faixas vigentes', 'Taxas vigentes']),
      section('Regras', [
        'Antecedência mínima',
        'Prazo de cancelamento',
        'Perfis autorizados',
        'Proprietário e confirmação automática',
      ]),
    ],
    [],
    [action('Novo ambiente', undefined, true)],
  ),
  blocksCalendar: page(
    'Reservas',
    'Bloqueios de agenda',
    'Períodos indisponíveis por ambiente e faixa.',
    [
      section('Visualizações', ['Calendário', 'Tabela', 'Ambiente e faixa', 'Situação']),
      section('Novo bloqueio', ['Início e fim', 'Motivo', 'Responsável', 'Reservas afetadas']),
    ],
    [],
    [action('Novo bloqueio', undefined, true)],
  ),

  noticesList: page(
    'Comunicados',
    'Comunicados',
    'Rascunhos, publicações, públicos e entregas.',
    [
      section('Filtros', ['Situação e autor', 'Data e bloco', 'Busca textual']),
      section('Cartões ou tabela', [
        'Título e trecho',
        'Autor, data e público',
        'Destinatários e entregas',
        'Anexos e situação',
      ]),
    ],
    [],
    [
      action('Novo comunicado', { name: 'admin-comunicado-novo' }, true),
      action('Abrir detalhe', detail('admin-comunicado-detalhe')),
    ],
  ),
  noticeCreate: page(
    'Comunicados',
    'Novo comunicado',
    'Composição, público, anexos e publicação.',
    [
      section('Conteúdo', ['Título', 'Editor controlado', 'Anexos', 'Pré-visualização']),
      section('Público e envio', [
        'Todos ou blocos',
        'Seleção de blocos',
        'Estimativa de destinatários',
        'Rascunho ou publicar',
      ]),
    ],
    [],
    [
      action('Voltar para comunicados', { name: 'admin-comunicados' }),
      action('Salvar rascunho', undefined, true),
    ],
  ),
  noticeDetail: page(
    'Comunicados',
    'Detalhe do comunicado',
    'Conteúdo publicado, destinatários e entregas.',
    [
      section('Abas', ['Conteúdo', 'Destinatários', 'Entregas', 'Anexos', 'Auditoria']),
      section('Publicação', ['Autor e data', 'Público congelado', 'Resumo de entrega', 'Situação']),
    ],
    [],
    [action('Voltar para comunicados', { name: 'admin-comunicados' })],
  ),

  occurrencesList: page(
    'Ocorrências',
    'Ocorrências',
    'Protocolos administrativos e acompanhamento.',
    [
      section('Filtros', [
        'Protocolo e situação',
        'Tipo e data',
        'Unidade e solicitante',
        'Interna',
      ]),
      section('Tabela', [
        'Protocolo e abertura',
        'Solicitante e unidades',
        'Tipo e motivo',
        'Situação e atualização',
      ]),
    ],
    ['Abertas', 'Em andamento', 'Resolvidas no período', 'Internas abertas'],
    [
      action('Registrar ocorrência', undefined, true),
      action('Abrir atendimento', detail('admin-ocorrencia-detalhe')),
    ],
  ),
  occurrenceDetail: page(
    'Ocorrências',
    'Atendimento da ocorrência',
    'Conteúdo, respostas, unidades relacionadas e histórico.',
    [
      section('Coluna principal', [
        'Protocolo e situação',
        'Observação e anexos',
        'Linha do tempo',
        'Nova resposta e visibilidade',
      ]),
      section('Coluna lateral', [
        'Tipo, autor e abertura',
        'Marcação interna',
        'Unidades relacionadas',
        'Auditoria permitida',
      ]),
    ],
    [],
    [
      action('Voltar para ocorrências', { name: 'admin-ocorrencias' }),
      action('Responder', undefined, true),
    ],
  ),
  occurrenceTypes: page(
    'Ocorrências',
    'Tipos de ocorrência',
    'Classificações disponíveis para abertura de protocolos.',
    [
      section('Tabela', ['Código', 'Nome', 'Descrição', 'Uso', 'Situação']),
      section('Operações', ['Criar', 'Editar', 'Inativar sem apagar histórico']),
    ],
    [],
    [action('Novo tipo', undefined, true)],
  ),

  usersList: page(
    'Acesso',
    'Usuários',
    'Contas, perfis, unidades e situação de acesso.',
    [
      section('Filtros', ['Busca e perfil', 'Situação', 'Bloco e unidade']),
      section('Tabela', [
        'Pessoa e e-mail',
        'Perfis e unidades',
        'Último acesso',
        'Situação e ações',
      ]),
    ],
    [],
    [
      action('Novo usuário', undefined, true),
      action('Abrir detalhe', detail('admin-usuario-detalhe')),
    ],
  ),
  userDetail: page(
    'Acesso',
    'Detalhe do usuário',
    'Conta, perfis, regras individuais e acessos.',
    [
      section('Abas', ['Conta', 'Perfis', 'Regras individuais', 'Política de reserva']),
      section('Histórico', ['Acessos recentes', 'Auditoria', 'Bloqueios', 'Redefinição de senha']),
    ],
    [],
    [
      action('Voltar para usuários', { name: 'admin-usuarios' }),
      action('Editar acesso', undefined, true),
    ],
  ),
  profilesList: page(
    'Acesso',
    'Perfis e permissões',
    'Perfis oficiais e respectivas permissões.',
    [
      section('Tabela', [
        'Nome e código',
        'Quantidade de usuários',
        'Quantidade de permissões',
        'Situação',
      ]),
      section('Regras', [
        'Síndico integral',
        'Conselho somente consulta',
        'Perfis cumulativos',
        'Regras individuais posteriores',
      ]),
    ],
    [],
    [
      action('Novo perfil', undefined, true),
      action('Abrir matriz', detail('admin-perfil-permissoes')),
    ],
  ),
  profilePermissions: page(
    'Acesso',
    'Matriz de permissões',
    'Ações agrupadas por módulo para o perfil selecionado.',
    [
      section('Matriz', [
        'Módulos recolhíveis',
        'Ações em colunas',
        'Seleção por linha e coluna',
        'Pesquisa',
      ]),
      section('Alterações', [
        'Resumo pendente',
        'Síndico bloqueado',
        'Conselho somente consulta',
        'Confirmação antes de reduzir',
      ]),
    ],
    [],
    [
      action('Voltar para perfis', { name: 'admin-perfis' }),
      action('Salvar permissões', undefined, true),
    ],
  ),
  accessLogs: page(
    'Auditoria',
    'Logs de acesso',
    'Entradas, saídas e tentativas de autenticação.',
    [
      section('Filtros', ['Usuário', 'Evento', 'Período', 'Endereço IP']),
      section('Tabela', [
        'Data e hora',
        'Usuário ou tentativa mascarada',
        'Evento e IP',
        'Agente e sessão',
      ]),
    ],
  ),
  actionLogs: page('Auditoria', 'Logs de ações', 'Trilha de alterações e operações sensíveis.', [
    section('Filtros', ['Usuário', 'Ação', 'Entidade', 'Período', 'Requisição']),
    section('Detalhe lateral', [
      'Dados anteriores',
      'Dados posteriores',
      'Motivo',
      'IP e correlação',
    ]),
  ]),

  personalDashboard: page(
    'Minha área',
    'Início',
    'Resumo pessoal da unidade e atalhos principais.',
    [
      section('Contexto', ['Saudação', 'Bloco e apartamento', 'Perfil ativo']),
      section('Atalhos', ['Reserva', 'Nova ocorrência', 'Comunicados', 'Boletos']),
    ],
    ['Próximo boleto', 'Reservas ativas', 'Ocorrências abertas'],
    [action('Minha unidade', { name: 'app-unidade' })],
  ),
  myUnit: page(
    'Minha área',
    'Minha unidade',
    'Dados permitidos da unidade atual em modo de consulta.',
    [
      section('Abas', ['Dados', 'Pessoas', 'Veículos', 'Boletos']),
      section('Contexto', [
        'Bloco e apartamento',
        'Andar e ocupação',
        'Vínculos permitidos',
        'Somente leitura',
      ]),
    ],
  ),
  myReceipts: page(
    'Minha área',
    'Meus boletos',
    'Recebimentos gerados pelos fechamentos sob responsabilidade do usuário.',
    [
      section('Lista', [
        'Referência',
        'Competência do fechamento',
        'Unidade e vencimento',
        'Valor, saldo e situação',
      ]),
      section('Escopo', [
        'Somente boletos permitidos',
        'Morador ou proprietário responsável',
        'Composição do fechamento',
        'Próximo vencimento',
      ]),
    ],
    ['Total em aberto', 'Total recebido', 'Total vencido', 'Próximo vencimento'],
    [action('Abrir boleto', detail('app-recebimento-detalhe'))],
  ),
  myReceiptDetail: page(
    'Minha área',
    'Detalhe do meu boleto',
    'Composição do recebimento gerado pelo fechamento.',
    [
      section('Composição', [
        'Pagamentos e tipos incluídos',
        'Responsável e papel',
        'Valor e vencimento',
        'Situação',
      ]),
      section('Origem e recebimento', [
        'Fechamento mensal',
        'Memória de rateio permitida',
        'Data do recebimento',
        'Identificação do boleto',
      ]),
    ],
    [],
    [action('Voltar para boletos', { name: 'app-recebimentos' })],
  ),
  publishedClosings: page(
    'Minha área',
    'Fechamentos',
    'Fechamentos publicados disponíveis para a unidade.',
    [
      section('Lista', [
        'Competência',
        'Pagamentos do período',
        'Total distribuído',
        'Valor individual',
      ]),
      section('Documentos', ['Relatório publicado', 'PDF', 'Excel', 'Autorização de download']),
    ],
    [],
    [action('Abrir fechamento', detail('app-fechamento-detalhe'))],
  ),
  publishedClosingDetail: page(
    'Minha área',
    'Fechamento publicado',
    'Relatório oficial autorizado para a unidade.',
    [
      section('Resumo', [
        'Competência',
        'Pagamentos incluídos',
        'Total distribuído',
        'Rateio permitido',
      ]),
      section('Unidade', ['Valor individual', 'Composição permitida', 'Relatório', 'Downloads']),
    ],
    [],
    [action('Voltar para fechamentos', { name: 'app-fechamentos' })],
  ),
  myReservations: page(
    'Minha área',
    'Reservas',
    'Disponibilidade e reservas da unidade.',
    [
      section('Calendário', ['Ambiente', 'Data disponível', 'Faixa', 'Taxa e regras']),
      section('Minhas reservas', [
        'Data e ambiente',
        'Faixa e taxa',
        'Situação',
        'Cancelamento permitido',
      ]),
    ],
    [],
    [
      action('Nova reserva', undefined, true),
      action('Abrir reserva', detail('app-reserva-detalhe')),
    ],
  ),
  myReservationDetail: page(
    'Minha área',
    'Detalhe da reserva',
    'Informações e histórico permitidos da reserva.',
    [
      section('Reserva', ['Ambiente e data', 'Faixa e taxa', 'Unidade', 'Situação']),
      section('Histórico', ['Criação', 'Validação', 'Cancelamento', 'Taxa informada']),
    ],
    [],
    [action('Voltar para reservas', { name: 'app-reservas' })],
  ),
  myNotices: page(
    'Minha área',
    'Comunicados',
    'Publicações destinadas ao usuário e à unidade.',
    [
      section('Cartões', ['Título e trecho', 'Data e autor', 'Não lido', 'Anexos']),
      section('Escopo', [
        'Somente destinatários autorizados',
        'Sem segmentação administrativa',
        'Marcação de leitura',
      ]),
    ],
    [],
    [action('Abrir comunicado', detail('app-comunicado-detalhe'))],
  ),
  myNoticeDetail: page(
    'Minha área',
    'Leitura do comunicado',
    'Conteúdo publicado e anexos autorizados.',
    [
      section('Conteúdo', [
        'Título',
        'Autor e publicação',
        'Texto sanitizado',
        'Situação de leitura',
      ]),
      section('Anexos', ['Nome e tamanho', 'Visualização', 'Download temporário']),
    ],
    [],
    [action('Voltar para comunicados', { name: 'app-comunicados' })],
  ),
  myOccurrences: page(
    'Minha área',
    'Ocorrências',
    'Protocolos que o usuário pode consultar.',
    [
      section('Lista', ['Protocolo e abertura', 'Tipo e motivo', 'Situação', 'Última atualização']),
      section('Visibilidade', [
        'Comentários liberados',
        'Anexos permitidos',
        'Sem conteúdo interno',
      ]),
    ],
    [],
    [
      action('Nova ocorrência', { name: 'app-ocorrencia-nova' }, true),
      action('Abrir ocorrência', detail('app-ocorrencia-detalhe')),
    ],
  ),
  myOccurrenceCreate: page(
    'Minha área',
    'Nova ocorrência',
    'Registro de uma ocorrência vinculada ao contexto atual.',
    [
      section('Formulário', ['Tipo', 'Motivo', 'Observação', 'Anexos']),
      section('Contexto', ['Unidade atual', 'Formatos e limite de 5 MB', 'Revisão antes do envio']),
    ],
    [],
    [
      action('Voltar para ocorrências', { name: 'app-ocorrencias' }),
      action('Registrar ocorrência', undefined, true),
    ],
  ),
  myOccurrenceDetail: page(
    'Minha área',
    'Acompanhamento da ocorrência',
    'Histórico e comentários autorizados do protocolo.',
    [
      section('Ocorrência', ['Protocolo e situação', 'Motivo e observação', 'Anexos liberados']),
      section('Linha do tempo', [
        'Comentários permitidos',
        'Mudanças de situação',
        'Datas e autores autorizados',
      ]),
    ],
    [],
    [action('Voltar para ocorrências', { name: 'app-ocorrencias' })],
  ),
} satisfies Record<string, PlannedPageConfig>
