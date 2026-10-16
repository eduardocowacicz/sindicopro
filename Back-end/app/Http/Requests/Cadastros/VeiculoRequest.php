<?php

namespace App\Http\Requests\Cadastros;

use App\Models\Veiculo;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VeiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $veiculo = $this->route('veiculo');
        $ignoraId = $veiculo instanceof Veiculo ? $veiculo->getKey() : null;

        $this->merge([
            'unidade_id' => ResolvedorIdPublico::resolver('unidades', $this->string('unidade_id')->value() ?: null),
            'pessoa_id' => ResolvedorIdPublico::resolver('pessoas', $this->string('pessoa_id')->value() ?: null),
            // Placa sem máscara e em maiúsculas, para comparar com a regra de unicidade.
            'placa' => strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('placa'))),
        ]);

        return [
            'unidade_id' => ['required', 'integer', Rule::exists('unidades', 'id')->where('ativo', true)],
            'pessoa_id' => ['nullable', 'integer', Rule::exists('pessoas', 'id')->where('ativo', true)],
            'placa' => [
                'required', 'string', 'size:7',
                'regex:/^([A-Z]{3}\d{4}|[A-Z]{3}\d[A-Z]\d{2})$/',
                Rule::unique('veiculos', 'placa')->where('ativo', true)->ignore($ignoraId),
            ],
            'modelo' => ['required', 'string', 'max:100'],
            'cor' => ['nullable', 'string', 'max:40'],
            'vaga' => ['nullable', 'string', 'max:30'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'placa.regex' => 'Informe uma placa válida no formato ABC1234 ou ABC1D23.',
        ];
    }
}
