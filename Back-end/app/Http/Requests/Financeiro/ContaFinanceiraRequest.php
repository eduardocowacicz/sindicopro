<?php

namespace App\Http\Requests\Financeiro;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ContaFinanceiraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'tipo_conta' => ['required', Rule::in(['CONTA_CORRENTE', 'POUPANCA', 'CAIXA'])],
            'codigo_banco' => ['nullable', 'string', 'max:10'],
            'nome_banco' => ['nullable', 'string', 'max:100'],
            'agencia' => ['nullable', 'string', 'max:20'],
            'numero_conta' => ['nullable', 'string', 'max:30'],
            'saldo_inicial' => ['required', 'numeric'],
            'data_saldo_inicial' => ['required', 'date'],
        ];
    }
}
