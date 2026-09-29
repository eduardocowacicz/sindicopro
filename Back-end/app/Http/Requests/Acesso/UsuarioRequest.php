<?php

namespace App\Http\Requests\Acesso;

use App\Models\Usuario;
use App\Support\Database\ResolvedorIdPublico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $ignoraId = $usuario instanceof Usuario ? $usuario->getKey() : null;
        $criando = ! $usuario instanceof Usuario;

        $merge = [];

        if ($this->filled('pessoa_id')) {
            $merge['pessoa_id'] = ResolvedorIdPublico::resolver('pessoas', $this->string('pessoa_id')->value());
        }

        if ($this->has('perfis')) {
            $merge['perfis'] = ResolvedorIdPublico::resolverMuitos('perfis', (array) $this->input('perfis'));
        }

        $this->merge($merge);

        return [
            'pessoa_id' => [$criando ? 'required' : 'sometimes', 'integer', 'exists:pessoas,id'],
            'email' => ['required', 'email', 'max:254', Rule::unique('usuarios', 'email')->ignore($ignoraId)],
            'senha' => [$criando ? 'required' : 'nullable', 'string', 'min:8'],
            'perfis' => ['sometimes', 'array'],
            'perfis.*' => ['integer', 'exists:perfis,id'],
        ];
    }
}
