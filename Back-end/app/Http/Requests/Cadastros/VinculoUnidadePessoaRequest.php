<?php

namespace App\Http\Requests\Cadastros;

use App\Models\VinculoUnidadePessoa;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'unidade_id' => ['required', 'integer', Rule::exists('unidades', 'id')->where('ativo', true)],
            'pessoa_id' => ['required', 'integer', Rule::exists('pessoas', 'id')->where('ativo', true)],
            'tipo_vinculo' => ['required', Rule::in(['PROPRIETARIO', 'LOCATARIO', 'MORADOR', 'DEPENDENTE'])],
            'papel_cobranca' => ['required_if:responsavel_financeiro,true', Rule::in(['PROPRIETARIO', 'MORADOR'])],
            'contato_principal' => ['boolean'],
            'responsavel_financeiro' => ['boolean'],
            'inicio_vigencia' => ['required', 'date'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('responsavel_financeiro') || ! $this->filled('papel_cobranca')) {
                return;
            }

            $jaExiste = VinculoUnidadePessoa::query()
                ->where('unidade_id', $this->integer('unidade_id'))
                ->where('papel_cobranca', $this->string('papel_cobranca')->value())
                ->where('responsavel_financeiro', true)
                ->whereNull('fim_vigencia')
                ->exists();

            if ($jaExiste) {
                $validator->errors()->add('responsavel_financeiro', 'Esta unidade já possui um responsável financeiro vigente para este papel de cobrança.');
            }
        });
    }
}
