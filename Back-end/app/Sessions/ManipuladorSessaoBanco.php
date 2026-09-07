<?php

namespace App\Sessions;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Carbon;

final class ManipuladorSessaoBanco extends DatabaseSessionHandler
{
    public function read($sessionId): string|false
    {
        $session = (object) $this->getQuery()->find($sessionId);

        if (isset($session->ultima_atividade)
            && $session->ultima_atividade < Carbon::now()->subMinutes($this->minutes)->getTimestamp()) {
            $this->exists = true;

            return '';
        }

        if (isset($session->conteudo_sessao)) {
            $this->exists = true;

            return base64_decode($session->conteudo_sessao);
        }

        return '';
    }

    protected function getDefaultPayload($data): array
    {
        $payload = [
            'conteudo_sessao' => base64_encode($data),
            'ultima_atividade' => $this->currentTime(),
        ];

        if (! $this->container) {
            return $payload;
        }

        if ($this->container->bound(Guard::class)) {
            $payload['usuario_id'] = $this->container->make(Guard::class)->id();
        }

        if ($this->container->bound('request')) {
            $request = $this->container->make('request');
            $payload['endereco_ip'] = $request->ip();
            $payload['agente_usuario'] = mb_substr((string) $request->userAgent(), 0, 500);
        }

        return $payload;
    }

    public function gc($lifetime): int
    {
        return $this->getQuery()
            ->where('ultima_atividade', '<=', $this->currentTime() - $lifetime)
            ->delete();
    }
}
