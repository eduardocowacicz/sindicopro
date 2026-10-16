<?php

namespace App\Http\Requests\Cadastros;

use App\Models\Pessoa;
use App\Rules\CpfValido;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

final class PessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pessoa = $this->route('pessoa');
        $ignoraId = $pessoa instanceof Pessoa ? $pessoa->getKey() : null;

        return [
            'nome_completo' => ['required', 'string', 'min:3', 'max:200'],
            'cpf' => ['nullable', 'string', 'max:14', 'bail', new CpfValido, $this->cpfNaoDuplicado($ignoraId)],
            'email' => ['nullable', 'string', 'email:rfc', 'max:254'],
            'telefone' => ['nullable', 'string', 'max:20', $this->telefoneValido()],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function cpfNaoDuplicado(?int $ignoraId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoraId): void {
            if (blank($value)) {
                return;
            }

            $hash = hash('sha256', preg_replace('/\D/', '', (string) $value));

            $existe = DB::table('pessoas')
                ->where('hash_cpf', $hash)
                ->when($ignoraId, fn ($query) => $query->where('id', '!=', $ignoraId))
                ->exists();

            if ($existe) {
                $fail('Já existe uma pessoa cadastrada com este CPF.');
            }
        };
    }

    private function telefoneValido(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (blank($value)) {
                return;
            }

            $digitos = preg_replace('/\D/', '', (string) $value);

            if (! in_array(strlen($digitos), [10, 11], true)) {
                $fail('Informe um telefone com DDD, com 10 ou 11 dígitos.');
            }
        };
    }
}
