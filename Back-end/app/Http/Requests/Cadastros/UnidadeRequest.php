<?php

namespace App\Http\Requests\Cadastros;

use App\Models\Bloco;
use App\Models\Unidade;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'bloco_id' => ['required', 'integer', Rule::exists('blocos', 'id')->where('ativo', true)],
            'codigo' => [
                'required', 'string', 'max:30', 'alpha_dash',
                Rule::unique('unidades', 'codigo')->where(fn ($query) => $query->where('bloco_id', $blocoIdInterno))->ignore($ignoraId),
            ],
            'numero_andar' => ['nullable', 'integer', 'between:-5,100'],
            'situacao_ocupacao' => ['required', Rule::in(['OCUPADO', 'VAGO'])],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $bloco = Bloco::query()->find($this->integer('bloco_id'));
            $andar = $this->integer('numero_andar');

            if ($bloco?->quantidade_andares !== null && $this->filled('numero_andar') && $andar > $bloco->quantidade_andares) {
                $validator->errors()->add('numero_andar', "O bloco possui apenas {$bloco->quantidade_andares} andares.");
            }
        });
    }
}
