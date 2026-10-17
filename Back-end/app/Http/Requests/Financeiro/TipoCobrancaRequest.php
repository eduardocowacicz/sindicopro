<?php

namespace App\Http\Requests\Financeiro;

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
        return [
            'nome' => ['required', 'string', 'max:100'],
            'papel_pagador' => ['required', Rule::in(['MORADOR', 'PROPRIETARIO'])],
            'dia_vencimento_padrao' => ['required', 'integer', 'between:1,28'],
            'descricao' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
