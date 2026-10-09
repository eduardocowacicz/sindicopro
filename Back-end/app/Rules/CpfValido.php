<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class CpfValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::valido((string) $value)) {
            $fail('Informe um CPF válido.');
        }
    }

    public static function valido(string $cpf): bool
    {
        $digitos = preg_replace('/\D/', '', $cpf);

        if (strlen($digitos) !== 11 || preg_match('/^(\d)\1{10}$/', $digitos)) {
            return false;
        }

        foreach ([9, 10] as $posicao) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $digitos[$i] * ($posicao + 1 - $i);
            }
            $digito = ($soma * 10) % 11 % 10;

            if ((int) $digitos[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }
}
