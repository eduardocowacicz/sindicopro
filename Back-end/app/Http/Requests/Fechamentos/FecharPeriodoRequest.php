<?php

namespace App\Http\Requests\Fechamentos;

use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;

final class FecharPeriodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tipo_cobranca_id')) {
            $this->merge([
                'tipo_cobranca_id' => ResolvedorIdPublico::resolver('tipos_cobranca', $this->string('tipo_cobranca_id')->value() ?: null),
            ]);
        }

        if ($this->has('despesas') && is_array($this->input('despesas'))) {
            $despesas = array_map(function ($item) {
                if (isset($item['movimentacao_id']) && is_string($item['movimentacao_id'])) {
                    $item['movimentacao_id'] = ResolvedorIdPublico::resolver('movimentacoes_financeiras', $item['movimentacao_id']) ?? $item['movimentacao_id'];
                }

                return $item;
            }, $this->input('despesas'));
            $this->merge(['despesas' => $despesas]);
        }
    }

    public function rules(): array
    {
        return [
            'periodo' => ['required', 'string'],
            'tipo_cobranca_id' => ['nullable', 'integer'],
            'data_vencimento' => ['nullable', 'date'],
            'percentual_fundo_reserva' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'despesas' => ['nullable', 'array'],
            'despesas.*.movimentacao_id' => ['required_with:despesas', 'integer'],
            'despesas.*.valor_cobrar' => ['required_with:despesas', 'numeric', 'min:0.01'],
        ];
    }
}
