<?php

namespace App\Http\Requests\Cadastros;

use App\Models\Unidade;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UnidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unidade = $this->route('unidade');
        $ignoraId = $unidade instanceof Unidade ? $unidade->getKey() : null;

        $blocoIdInterno = ResolvedorIdPublico::resolver('blocos', $this->string('bloco_id')->value() ?: null);
        $this->merge(['bloco_id' => $blocoIdInterno]);

        return [
            'bloco_id' => ['required', 'integer', 'exists:blocos,id'],
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('unidades', 'codigo')->where(fn ($query) => $query->where('bloco_id', $blocoIdInterno))->ignore($ignoraId),
            ],
            'numero_andar' => ['nullable', 'integer'],
            'situacao_ocupacao' => ['required', Rule::in(['OCUPADO', 'VAGO'])],
            'observacoes' => ['nullable', 'string'],
        ];
    }
}
