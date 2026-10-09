<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;

final class BlocoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O código é gerado automaticamente pelo banco (sequência) na criação e não pode ser alterado.
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'quantidade_andares' => ['nullable', 'integer', 'between:1,100'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
