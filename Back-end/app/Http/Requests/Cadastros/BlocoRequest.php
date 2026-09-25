<?php

namespace App\Http\Requests\Cadastros;

use App\Models\Bloco;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BlocoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bloco = $this->route('bloco');
        $ignoraId = $bloco instanceof Bloco ? $bloco->getKey() : null;

        return [
            'codigo' => ['required', 'string', 'max:30', Rule::unique('blocos', 'codigo')->ignore($ignoraId)],
            'nome' => ['required', 'string', 'max:100'],
            'quantidade_andares' => ['nullable', 'integer', 'min:1'],
            'observacoes' => ['nullable', 'string'],
        ];
    }
}
