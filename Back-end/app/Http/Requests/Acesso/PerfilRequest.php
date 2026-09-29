<?php

namespace App\Http\Requests\Acesso;

use App\Models\Perfil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $perfil = $this->route('perfil');
        $ignoraId = $perfil instanceof Perfil ? $perfil->getKey() : null;

        return [
            'codigo' => ['required', 'string', 'max:40', Rule::unique('perfis', 'codigo')->ignore($ignoraId)],
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
        ];
    }
}
