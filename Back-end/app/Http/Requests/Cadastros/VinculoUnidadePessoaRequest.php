<?php

namespace App\Http\Requests\Cadastros;

use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VinculoUnidadePessoaRequest extends FormRequest
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
            'pessoa_id' => ['required', 'integer', 'exists:pessoas,id'],
            'tipo_vinculo' => ['required', Rule::in(['PROPRIETARIO', 'LOCATARIO', 'MORADOR', 'DEPENDENTE'])],
            'papel_cobranca' => ['nullable', Rule::in(['PROPRIETARIO', 'MORADOR'])],
            'contato_principal' => ['boolean'],
            'responsavel_financeiro' => ['boolean'],
            'inicio_vigencia' => ['required', 'date'],
            'observacoes' => ['nullable', 'string'],
        ];
    }
}
