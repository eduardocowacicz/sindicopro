<?php

namespace App\Http\Requests\Financeiro;

use App\Models\TipoCobranca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TipoCobrancaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipo = $this->route('tipo_cobranca');
        $ignoraId = $tipo instanceof TipoCobranca ? $tipo->getKey() : null;

        return [
            'codigo' => ['required', 'string', 'max:30', Rule::unique('tipos_cobranca', 'codigo')->ignore($ignoraId)],
            'nome' => ['required', 'string', 'max:100'],
            'papel_pagador' => ['required', Rule::in(['MORADOR', 'PROPRIETARIO'])],
            'dia_vencimento_padrao' => ['required', 'integer', 'between:1,28'],
            'descricao' => ['nullable', 'string'],
        ];
    }
}
