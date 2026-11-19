<?php

namespace Tests\Unit;

use App\Rules\CpfValido;
use PHPUnit\Framework\TestCase;

final class CpfValidoTest extends TestCase
{
    public function test_aceita_cpf_valido_com_e_sem_mascara(): void
    {
        $this->assertTrue(CpfValido::valido('529.982.247-25'));
        $this->assertTrue(CpfValido::valido('52998224725'));
    }

    public function test_recusa_digito_verificador_errado(): void
    {
        $this->assertFalse(CpfValido::valido('529.982.247-24'));
    }

    public function test_recusa_sequencia_repetida_e_tamanho_invalido(): void
    {
        $this->assertFalse(CpfValido::valido('111.111.111-11'));
        $this->assertFalse(CpfValido::valido('1234567890'));
        $this->assertFalse(CpfValido::valido(''));
    }
}
