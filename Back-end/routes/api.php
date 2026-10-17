<?php

use App\Http\Controllers\Acesso\PerfilController;
use App\Http\Controllers\Acesso\PerfilPermissoesController;
use App\Http\Controllers\Acesso\PermissaoController;
use App\Http\Controllers\Acesso\UsuarioController;
use App\Http\Controllers\Autenticacao\SessaoController;
use App\Http\Controllers\Cadastros\BlocoController;
use App\Http\Controllers\Cadastros\PessoaController;
use App\Http\Controllers\Cadastros\UnidadeController;
use App\Http\Controllers\Cadastros\VeiculoController;
use App\Http\Controllers\Cadastros\VinculoUnidadePessoaController;
use App\Http\Controllers\Comunicados\ComunicadoController;
use App\Http\Controllers\Fechamentos\FechamentoMensalController;
use App\Http\Controllers\Financeiro\CategoriaFinanceiraController;
use App\Http\Controllers\Financeiro\CobrancaController;
use App\Http\Controllers\Financeiro\ContaFinanceiraController;
use App\Http\Controllers\Financeiro\MovimentacaoFinanceiraController;
use App\Http\Controllers\Financeiro\TipoCobrancaController;
use App\Http\Controllers\Morador\MoradorAreaController;
use App\Http\Controllers\Ocorrencias\OcorrenciaController;
use App\Http\Controllers\Ocorrencias\TipoOcorrenciaController;
use App\Http\Controllers\Reservas\AmbienteController;
use App\Http\Controllers\Reservas\BloqueioAmbienteController;
use App\Http\Controllers\Reservas\ReservaController;
use App\Http\Controllers\Sistema\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/status', StatusController::class);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/sessao', SessaoController::class);

    // Acesso
    Route::prefix('usuarios')->group(function (): void {
        Route::get('/', [UsuarioController::class, 'index'])->middleware('permissao:acesso.usuarios.consultar');
        Route::post('/', [UsuarioController::class, 'store'])->middleware('permissao:acesso.usuarios.cadastrar');
        Route::get('/{usuario}', [UsuarioController::class, 'show'])->middleware('permissao:acesso.usuarios.consultar');
        Route::get('/{usuario}/historico', [UsuarioController::class, 'historico'])->middleware('permissao:acesso.usuarios.consultar');
        Route::put('/{usuario}', [UsuarioController::class, 'update'])->middleware('permissao:acesso.usuarios.editar');
        Route::post('/{usuario}/inativar', [UsuarioController::class, 'inativar'])->middleware('permissao:acesso.usuarios.inativar');
        Route::post('/{usuario}/reativar', [UsuarioController::class, 'reativar'])->middleware('permissao:acesso.usuarios.inativar');
        Route::post('/{usuario}/bloquear', [UsuarioController::class, 'bloquear'])->middleware('permissao:acesso.usuarios.bloquear');
        Route::post('/{usuario}/desbloquear', [UsuarioController::class, 'desbloquear'])->middleware('permissao:acesso.usuarios.desbloquear');
        Route::post('/{usuario}/redefinir-senha', [UsuarioController::class, 'redefinirSenha'])->middleware('permissao:acesso.usuarios.redefinir_senha');
    });

    Route::prefix('perfis')->group(function (): void {
        Route::get('/', [PerfilController::class, 'index'])->middleware('permissao:acesso.perfis.consultar');
        Route::post('/', [PerfilController::class, 'store'])->middleware('permissao:acesso.perfis.cadastrar');
        Route::get('/{perfil}', [PerfilController::class, 'show'])->middleware('permissao:acesso.perfis.consultar');
        Route::get('/{perfil}/historico', [PerfilController::class, 'historico'])->middleware('permissao:acesso.perfis.consultar');
        Route::put('/{perfil}', [PerfilController::class, 'update'])->middleware('permissao:acesso.perfis.editar');
        Route::post('/{perfil}/inativar', [PerfilController::class, 'inativar'])->middleware('permissao:acesso.perfis.inativar');
        Route::post('/{perfil}/reativar', [PerfilController::class, 'reativar'])->middleware('permissao:acesso.perfis.inativar');
        Route::get('/{perfil}/permissoes', [PerfilPermissoesController::class, 'index'])->middleware('permissao:acesso.perfis.consultar');
        Route::put('/{perfil}/permissoes', [PerfilPermissoesController::class, 'update'])->middleware('permissao:acesso.perfis.gerenciar_permissoes');
    });

    Route::get('/permissoes', [PermissaoController::class, 'index'])->middleware('permissao:acesso.perfis.consultar');

    // Cadastros
    Route::prefix('blocos')->group(function (): void {
        Route::get('/', [BlocoController::class, 'index'])->middleware('permissao:cadastros.blocos.consultar');
        Route::post('/', [BlocoController::class, 'store'])->middleware('permissao:cadastros.blocos.cadastrar');
        Route::get('/{bloco}', [BlocoController::class, 'show'])->middleware('permissao:cadastros.blocos.consultar');
        Route::get('/{bloco}/apartamentos', [BlocoController::class, 'apartamentos'])->middleware('permissao:cadastros.blocos.consultar');
        Route::get('/{bloco}/historico', [BlocoController::class, 'historico'])->middleware('permissao:cadastros.blocos.consultar');
        Route::put('/{bloco}', [BlocoController::class, 'update'])->middleware('permissao:cadastros.blocos.editar');
        Route::post('/{bloco}/inativar', [BlocoController::class, 'inativar'])->middleware('permissao:cadastros.blocos.inativar');
        Route::post('/{bloco}/reativar', [BlocoController::class, 'reativar'])->middleware('permissao:cadastros.blocos.inativar');
    });

    Route::prefix('unidades')->group(function (): void {
        Route::get('/', [UnidadeController::class, 'index'])->middleware('permissao:cadastros.unidades.consultar');
        Route::post('/', [UnidadeController::class, 'store'])->middleware('permissao:cadastros.unidades.cadastrar');
        Route::get('/{unidade}', [UnidadeController::class, 'show'])->middleware('permissao:cadastros.unidades.consultar');
        Route::get('/{unidade}/historico', [UnidadeController::class, 'historico'])->middleware('permissao:cadastros.unidades.consultar');
        Route::put('/{unidade}', [UnidadeController::class, 'update'])->middleware('permissao:cadastros.unidades.editar');
        Route::patch('/{unidade}/observacoes', [UnidadeController::class, 'atualizarObservacoes'])->middleware('permissao:cadastros.unidades.editar');
        Route::post('/{unidade}/inativar', [UnidadeController::class, 'inativar'])->middleware('permissao:cadastros.unidades.inativar');
        Route::post('/{unidade}/reativar', [UnidadeController::class, 'reativar'])->middleware('permissao:cadastros.unidades.inativar');
    });

    Route::prefix('apartamentos')->group(function (): void {
        Route::patch('/{unidade}/observacoes', [UnidadeController::class, 'atualizarObservacoes'])->middleware('permissao:cadastros.unidades.editar');
    });

    Route::prefix('pessoas')->group(function (): void {
        Route::get('/', [PessoaController::class, 'index'])->middleware('permissao:cadastros.pessoas.consultar');
        Route::post('/', [PessoaController::class, 'store'])->middleware('permissao:cadastros.pessoas.cadastrar');
        Route::get('/{pessoa}', [PessoaController::class, 'show'])->middleware('permissao:cadastros.pessoas.consultar');
        Route::get('/{pessoa}/historico', [PessoaController::class, 'historico'])->middleware('permissao:cadastros.pessoas.consultar');
        Route::put('/{pessoa}', [PessoaController::class, 'update'])->middleware('permissao:cadastros.pessoas.editar');
        Route::post('/{pessoa}/inativar', [PessoaController::class, 'inativar'])->middleware('permissao:cadastros.pessoas.inativar');
        Route::post('/{pessoa}/reativar', [PessoaController::class, 'reativar'])->middleware('permissao:cadastros.pessoas.inativar');
    });

    Route::prefix('vinculos')->group(function (): void {
        Route::get('/', [VinculoUnidadePessoaController::class, 'index'])->middleware('permissao:cadastros.vinculos.consultar');
        Route::post('/', [VinculoUnidadePessoaController::class, 'store'])->middleware('permissao:cadastros.vinculos.cadastrar');
        Route::get('/{vinculo}', [VinculoUnidadePessoaController::class, 'show'])->middleware('permissao:cadastros.vinculos.consultar');
        Route::get('/{vinculo}/historico', [VinculoUnidadePessoaController::class, 'historico'])->middleware('permissao:cadastros.vinculos.consultar');
        Route::post('/{vinculo}/encerrar', [VinculoUnidadePessoaController::class, 'encerrar'])->middleware('permissao:cadastros.vinculos.encerrar');
    });

    Route::prefix('veiculos')->group(function (): void {
        Route::get('/', [VeiculoController::class, 'index'])->middleware('permissao:cadastros.veiculos.consultar');
        Route::post('/', [VeiculoController::class, 'store'])->middleware('permissao:cadastros.veiculos.cadastrar');
        Route::get('/{veiculo}', [VeiculoController::class, 'show'])->middleware('permissao:cadastros.veiculos.consultar');
        Route::get('/{veiculo}/historico', [VeiculoController::class, 'historico'])->middleware('permissao:cadastros.veiculos.consultar');
        Route::put('/{veiculo}', [VeiculoController::class, 'update'])->middleware('permissao:cadastros.veiculos.editar');
        Route::post('/{veiculo}/inativar', [VeiculoController::class, 'inativar'])->middleware('permissao:cadastros.veiculos.inativar');
        Route::post('/{veiculo}/reativar', [VeiculoController::class, 'reativar'])->middleware('permissao:cadastros.veiculos.inativar');
    });

    // Financeiro inicial
    Route::prefix('financeiro/contas')->group(function (): void {
        Route::get('/', [ContaFinanceiraController::class, 'index'])->middleware('permissao:financeiro.contas.consultar');
        Route::post('/', [ContaFinanceiraController::class, 'store'])->middleware('permissao:financeiro.contas.cadastrar');
        Route::get('/{conta_financeira}', [ContaFinanceiraController::class, 'show'])->middleware('permissao:financeiro.contas.consultar');
        Route::get('/{conta_financeira}/historico', [ContaFinanceiraController::class, 'historico'])->middleware('permissao:financeiro.contas.consultar');
        Route::put('/{conta_financeira}', [ContaFinanceiraController::class, 'update'])->middleware('permissao:financeiro.contas.editar');
        Route::post('/{conta_financeira}/inativar', [ContaFinanceiraController::class, 'inativar'])->middleware('permissao:financeiro.contas.inativar');
        Route::post('/{conta_financeira}/reativar', [ContaFinanceiraController::class, 'reativar'])->middleware('permissao:financeiro.contas.inativar');
    });

    Route::prefix('financeiro/categorias')->group(function (): void {
        Route::get('/', [CategoriaFinanceiraController::class, 'index'])->middleware('permissao:financeiro.categorias.consultar');
        Route::post('/', [CategoriaFinanceiraController::class, 'store'])->middleware('permissao:financeiro.categorias.cadastrar');
        Route::get('/{categoria_financeira}', [CategoriaFinanceiraController::class, 'show'])->middleware('permissao:financeiro.categorias.consultar');
        Route::get('/{categoria_financeira}/historico', [CategoriaFinanceiraController::class, 'historico'])->middleware('permissao:financeiro.categorias.consultar');
        Route::put('/{categoria_financeira}', [CategoriaFinanceiraController::class, 'update'])->middleware('permissao:financeiro.categorias.editar');
        Route::post('/{categoria_financeira}/inativar', [CategoriaFinanceiraController::class, 'inativar'])->middleware('permissao:financeiro.categorias.inativar');
        Route::post('/{categoria_financeira}/reativar', [CategoriaFinanceiraController::class, 'reativar'])->middleware('permissao:financeiro.categorias.inativar');
    });

    Route::prefix('financeiro/tipos-cobranca')->group(function (): void {
        Route::get('/', [TipoCobrancaController::class, 'index'])->middleware('permissao:financeiro.tipos_cobranca.consultar');
        Route::post('/', [TipoCobrancaController::class, 'store'])->middleware('permissao:financeiro.tipos_cobranca.cadastrar');
        Route::get('/{tipo_cobranca}', [TipoCobrancaController::class, 'show'])->middleware('permissao:financeiro.tipos_cobranca.consultar');
        Route::get('/{tipo_cobranca}/historico', [TipoCobrancaController::class, 'historico'])->middleware('permissao:financeiro.tipos_cobranca.consultar');
        Route::put('/{tipo_cobranca}', [TipoCobrancaController::class, 'update'])->middleware('permissao:financeiro.tipos_cobranca.editar');
        Route::post('/{tipo_cobranca}/inativar', [TipoCobrancaController::class, 'inativar'])->middleware('permissao:financeiro.tipos_cobranca.inativar');
        Route::post('/{tipo_cobranca}/reativar', [TipoCobrancaController::class, 'reativar'])->middleware('permissao:financeiro.tipos_cobranca.inativar');
    });

    Route::prefix('financeiro/movimentacoes')->group(function (): void {
        Route::get('/', [MovimentacaoFinanceiraController::class, 'index'])->middleware('permissao:financeiro.movimentacoes.consultar');
        Route::post('/', [MovimentacaoFinanceiraController::class, 'store'])->middleware('permissao:financeiro.movimentacoes.cadastrar');
        Route::get('/{movimentacao_financeira}', [MovimentacaoFinanceiraController::class, 'show'])->middleware('permissao:financeiro.movimentacoes.consultar');
        Route::get('/{movimentacao_financeira}/historico', [MovimentacaoFinanceiraController::class, 'historico'])->middleware('permissao:financeiro.movimentacoes.consultar');
        Route::put('/{movimentacao_financeira}', [MovimentacaoFinanceiraController::class, 'update'])->middleware('permissao:financeiro.movimentacoes.editar');
        Route::post('/{movimentacao_financeira}/cancelar', [MovimentacaoFinanceiraController::class, 'cancelar'])->middleware('permissao:financeiro.movimentacoes.editar');
    });

    Route::prefix('financeiro/cobrancas')->group(function (): void {
        Route::get('/', [CobrancaController::class, 'index'])->middleware('permissao:financeiro.cobrancas.consultar');
        Route::get('/{cobranca}', [CobrancaController::class, 'show'])->middleware('permissao:financeiro.cobrancas.consultar');
        Route::post('/{cobranca}/registrar-pagamento', [CobrancaController::class, 'registrarPagamento'])->middleware('permissao:financeiro.recebimentos.registrar');
        Route::post('/{cobranca}/cancelar', [CobrancaController::class, 'cancelar'])->middleware('permissao:financeiro.cobrancas.cancelar');
    });

    // Fechamentos
    Route::prefix('fechamentos/mensais')->group(function (): void {
        Route::get('/', [FechamentoMensalController::class, 'index'])->middleware('permissao:fechamentos.mensais.consultar');
        Route::post('/', [FechamentoMensalController::class, 'store'])->middleware('permissao:fechamentos.mensais.fechar');
        Route::get('/{identificador}', [FechamentoMensalController::class, 'show'])->middleware('permissao:fechamentos.mensais.consultar');
        Route::post('/{fechamento}/reabrir', [FechamentoMensalController::class, 'reabrir'])->middleware('permissao:fechamentos.mensais.reabrir');
    });

    // Reservas
    Route::prefix('reservas/ambientes')->group(function (): void {
        Route::get('/', [AmbienteController::class, 'index'])->middleware('permissao:reservas.ambientes.consultar');
        Route::post('/', [AmbienteController::class, 'store'])->middleware('permissao:reservas.ambientes.cadastrar');
        Route::get('/{ambiente}', [AmbienteController::class, 'show'])->middleware('permissao:reservas.ambientes.consultar');
        Route::put('/{ambiente}', [AmbienteController::class, 'update'])->middleware('permissao:reservas.ambientes.editar');
        Route::post('/{ambiente}/inativar', [AmbienteController::class, 'inativar'])->middleware('permissao:reservas.ambientes.inativar');
        Route::post('/{ambiente}/reativar', [AmbienteController::class, 'reativar'])->middleware('permissao:reservas.ambientes.inativar');
        Route::get('/{ambiente}/historico', [AmbienteController::class, 'historico'])->middleware('permissao:reservas.ambientes.consultar');
    });

    Route::prefix('reservas/bloqueios')->group(function (): void {
        Route::get('/', [BloqueioAmbienteController::class, 'index'])->middleware('permissao:reservas.bloqueios.consultar');
        Route::post('/', [BloqueioAmbienteController::class, 'store'])->middleware('permissao:reservas.bloqueios.cadastrar');
        Route::post('/{bloqueio}/cancelar', [BloqueioAmbienteController::class, 'cancelar'])->middleware('permissao:reservas.bloqueios.cancelar');
    });

    Route::prefix('reservas/agenda')->group(function (): void {
        Route::get('/', [ReservaController::class, 'index'])->middleware('permissao:reservas.agenda.consultar');
        Route::post('/', [ReservaController::class, 'store'])->middleware('permissao:reservas.agenda.cadastrar');
        Route::get('/{reserva}', [ReservaController::class, 'show'])->middleware('permissao:reservas.agenda.consultar');
        Route::post('/{reserva}/validar', [ReservaController::class, 'validar'])->middleware('permissao:reservas.agenda.validar');
        Route::post('/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->middleware('permissao:reservas.agenda.cancelar');
        Route::get('/{reserva}/historico', [ReservaController::class, 'historico'])->middleware('permissao:reservas.agenda.consultar');
    });

    // Comunicados
    Route::prefix('comunicados')->group(function (): void {
        Route::get('/', [ComunicadoController::class, 'index'])->middleware('permissao:comunicados.consultar');
        Route::post('/', [ComunicadoController::class, 'store'])->middleware('permissao:comunicados.cadastrar');
        Route::get('/{comunicado}', [ComunicadoController::class, 'show'])->middleware('permissao:comunicados.consultar');
        Route::post('/{comunicado}/publicar', [ComunicadoController::class, 'publicar'])->middleware('permissao:comunicados.publicar');
        Route::post('/{comunicado}/cancelar', [ComunicadoController::class, 'cancelar'])->middleware('permissao:comunicados.cancelar');
    });

    // Ocorrências
    Route::prefix('ocorrencias/tipos')->group(function (): void {
        Route::get('/', [TipoOcorrenciaController::class, 'index'])->middleware('permissao:ocorrencias.tipos.consultar');
        Route::post('/', [TipoOcorrenciaController::class, 'store'])->middleware('permissao:ocorrencias.tipos.cadastrar');
        Route::get('/{tipo}', [TipoOcorrenciaController::class, 'show'])->middleware('permissao:ocorrencias.tipos.consultar');
        Route::put('/{tipo}', [TipoOcorrenciaController::class, 'update'])->middleware('permissao:ocorrencias.tipos.editar');
        Route::post('/{tipo}/inativar', [TipoOcorrenciaController::class, 'inativar'])->middleware('permissao:ocorrencias.tipos.inativar');
        Route::post('/{tipo}/reativar', [TipoOcorrenciaController::class, 'reativar'])->middleware('permissao:ocorrencias.tipos.inativar');
    });

    Route::prefix('ocorrencias')->group(function (): void {
        Route::get('/', [OcorrenciaController::class, 'index'])->middleware('permissao:ocorrencias.consultar');
        Route::post('/', [OcorrenciaController::class, 'store'])->middleware('permissao:ocorrencias.cadastrar');
        Route::get('/{ocorrencia}', [OcorrenciaController::class, 'show'])->middleware('permissao:ocorrencias.consultar');
        Route::post('/{ocorrencia}/comentar', [OcorrenciaController::class, 'comentar'])->middleware('permissao:ocorrencias.responder');
        Route::post('/{ocorrencia}/alterar-situacao', [OcorrenciaController::class, 'alterarSituacao'])->middleware('permissao:ocorrencias.alterar_situacao');
        Route::get('/{ocorrencia}/historico', [OcorrenciaController::class, 'historico'])->middleware('permissao:ocorrencias.consultar');
    });

    // Área do Morador
    Route::prefix('morador')->group(function (): void {
        Route::get('/dashboard', [MoradorAreaController::class, 'dashboard']);
        Route::get('/unidade', [MoradorAreaController::class, 'unidade']);
        Route::get('/cobrancas', [MoradorAreaController::class, 'cobrancas']);
        Route::get('/fechamentos', [MoradorAreaController::class, 'fechamentos']);
        Route::get('/ambientes', [MoradorAreaController::class, 'ambientes']);
        Route::get('/reservas', [MoradorAreaController::class, 'reservas']);
        Route::post('/reservas', [MoradorAreaController::class, 'storeReserva']);
        Route::post('/reservas/{reserva}/cancelar', [MoradorAreaController::class, 'cancelarReserva']);
        Route::get('/comunicados', [MoradorAreaController::class, 'comunicados']);
        Route::post('/comunicados/{comunicado}/lido', [MoradorAreaController::class, 'marcarComunicadoLido']);
        Route::get('/ocorrencias', [MoradorAreaController::class, 'ocorrencias']);
        Route::post('/ocorrencias', [MoradorAreaController::class, 'storeOcorrencia']);
        Route::post('/ocorrencias/{ocorrencia}/comentar', [MoradorAreaController::class, 'comentarOcorrencia']);
    });
});
