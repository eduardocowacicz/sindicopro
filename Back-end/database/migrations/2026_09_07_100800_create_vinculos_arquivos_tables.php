<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VINCULOS = [
        'movimentacao_financeira_arquivos' => ['movimentacao_financeira_id', 'movimentacoes_financeiras'],
        'comunicado_arquivos' => ['comunicado_id', 'comunicados'],
        'ocorrencia_arquivos' => ['ocorrencia_id', 'ocorrencias'],
        'comentario_ocorrencia_arquivos' => ['comentario_ocorrencia_id', 'comentarios_ocorrencia'],
        'exportacao_relatorio_arquivos' => ['exportacao_relatorio_id', 'exportacoes_relatorio'],
    ];

    public function up(): void
    {
        foreach (self::VINCULOS as $nomeTabela => [$colunaDominio, $tabelaDominio]) {
            Schema::create($nomeTabela, function (Blueprint $table) use ($nomeTabela, $colunaDominio, $tabelaDominio): void {
                $table->id();
                $table->foreignId($colunaDominio);
                $table->foreignId('arquivo_id');
                $table->timestampTz('criado_em')->useCurrent();

                $table->unique([$colunaDominio, 'arquivo_id'], "uq_{$nomeTabela}");
                $table->foreign($colunaDominio, "fk_{$nomeTabela}_dominio")->references('id')->on($tabelaDominio)->restrictOnDelete();
                $table->foreign('arquivo_id', "fk_{$nomeTabela}_arquivo")->references('id')->on('arquivos')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys(self::VINCULOS)) as $nomeTabela) {
            Schema::dropIfExists($nomeTabela);
        }
    }
};
