<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;

final class PessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome_completo' => ['required', 'string', 'max:200'],
            'cpf' => ['nullable', 'string', 'size:11'],
            'email' => ['nullable', 'email', 'max:254'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'observacoes' => ['nullable', 'string'],
        ];
    }
}
