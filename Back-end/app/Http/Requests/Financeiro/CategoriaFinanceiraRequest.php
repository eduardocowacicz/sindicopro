<?php

namespace App\Http\Requests\Financeiro;

use App\Models\CategoriaFinanceira;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CategoriaFinanceiraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoria = $this->route('categoria_financeira');
        $ignoraId = $categoria instanceof CategoriaFinanceira ? $categoria->getKey() : null;

        return [
            'codigo' => ['required', 'string', 'max:30', Rule::unique('categorias_financeiras', 'codigo')->ignore($ignoraId)],
            'nome' => ['required', 'string', 'max:100'],
            'direcao' => ['required', Rule::in(['ENTRADA', 'SAIDA'])],
        ];
    }
}
