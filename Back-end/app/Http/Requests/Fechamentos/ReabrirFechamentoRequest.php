<?php

namespace App\Http\Requests\Fechamentos;

use Illuminate\Foundation\Http\FormRequest;

final class ReabrirFechamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
