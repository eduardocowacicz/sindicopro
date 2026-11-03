<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class Ambiente extends ModeloBase
{
    protected $table = 'ambientes';

    public function faixasHorario(): HasMany
    {
        return $this->hasMany(FaixaHorarioAmbiente::class, 'ambiente_id');
    }

    public function taxas(): HasMany
    {
        return $this->hasMany(TaxaAmbiente::class, 'ambiente_id');
    }

    public function bloqueios(): HasMany
    {
        return $this->hasMany(BloqueioAmbiente::class, 'ambiente_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'ambiente_id');
    }

    protected function casts(): array
    {
        return [
            'horas_antecedencia_minima_morador' => 'integer',
            'horas_limite_cancelamento_morador' => 'integer',
            'bloquear_unidades_inadimplentes' => 'boolean',
            'permitir_proprietario_nao_residente' => 'boolean',
            'aprovacao_automatica' => 'boolean',
            'ativo' => 'boolean',
        ];
    }
}
