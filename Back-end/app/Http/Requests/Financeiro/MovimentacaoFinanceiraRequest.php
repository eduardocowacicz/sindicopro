<?php

namespace App\Http\Requests\Financeiro;

use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MovimentacaoFinanceiraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $this->merge([
            'conta_financeira_id' => ResolvedorIdPublico::resolver('contas_financeiras', $this->string('conta_financeira_id')->value() ?: null),
            'categoria_financeira_id' => ResolvedorIdPublico::resolver('categorias_financeiras', $this->string('categoria_financeira_id')->value() ?: null),
            'tipo_cobranca_id' => ResolvedorIdPublico::resolver('tipos_cobranca', $this->string('tipo_cobranca_id')->value() ?: null),
        ]);

        return [
            'conta_financeira_id' => ['required', 'integer', 'exists:contas_financeiras,id'],
            'categoria_financeira_id' => ['required', 'integer', 'exists:categorias_financeiras,id'],
            'tipo_cobranca_id' => ['nullable', 'integer', 'exists:tipos_cobranca,id'],
            'direcao' => ['required', Rule::in(['ENTRADA', 'SAIDA'])],
            'descricao' => ['required', 'string', 'max:255'],
            'numero_documento' => ['nullable', 'string', 'max:80'],
            'nome_contraparte' => ['nullable', 'string', 'max:200'],
            'data_competencia' => ['required', 'date'],
            'data_efetiva' => ['required', 'date'],
            'data_vencimento' => ['nullable', 'date'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'observacoes' => ['nullable', 'string'],
        ];
    }
}
