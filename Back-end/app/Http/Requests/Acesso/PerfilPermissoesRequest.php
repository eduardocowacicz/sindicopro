<?php

namespace App\Http\Requests\Acesso;

use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;

final class PerfilPermissoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $this->merge([
            'permissoes' => ResolvedorIdPublico::resolverMuitos('permissoes', (array) $this->input('permissoes', [])),
        ]);

        return [
            'permissoes' => ['required', 'array'],
            'permissoes.*' => ['integer', 'exists:permissoes,id'],
        ];
    }
}
