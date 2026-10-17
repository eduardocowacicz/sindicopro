<?php

namespace App\Http\Requests\Cadastros;

use Illuminate\Foundation\Http\FormRequest;

final class BlocoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('quantidade_andares') && ! $this->has('andares')) {
            $this->merge(['andares' => $this->input('quantidade_andares')]);
        } elseif ($this->has('andares') && ! $this->has('quantidade_andares')) {
            $this->merge(['quantidade_andares' => $this->input('andares')]);
        }
    }

    /**
     * O código é gerado automaticamente pelo banco (sequência) na criação e não pode ser alterado.
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'apelido' => ['nullable', 'string', 'max:100'],
            'andares' => ['required_without:quantidade_andares', 'integer', 'min:1', 'max:100'],
            'quantidade_andares' => ['nullable', 'integer', 'min:1', 'max:100'],
            'apartamentos_por_andar' => ['required', 'integer', 'min:1', 'max:50'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
