<?php

namespace App\Http\Requests\Cadastros;

use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;

final class VeiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $this->merge([
            'unidade_id' => ResolvedorIdPublico::resolver('unidades', $this->string('unidade_id')->value() ?: null),
            'pessoa_id' => ResolvedorIdPublico::resolver('pessoas', $this->string('pessoa_id')->value() ?: null),
        ]);

        return [
            'unidade_id' => ['required', 'integer', 'exists:unidades,id'],
            'pessoa_id' => ['nullable', 'integer', 'exists:pessoas,id'],
            'placa' => ['required', 'string', 'max:10'],
            'modelo' => ['required', 'string', 'max:100'],
            'cor' => ['nullable', 'string', 'max:40'],
            'vaga' => ['nullable', 'string', 'max:30'],
            'observacoes' => ['nullable', 'string'],
        ];
    }
}
