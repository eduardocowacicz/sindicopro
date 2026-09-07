<script setup lang="ts">
import { Bell } from '@lucide/vue'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { SidebarTrigger } from '@/components/ui/sidebar'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

const autenticacao = useAutenticacaoStore()
const route = useRoute()
const areaUsuario = computed(() => route.path === '/app' || route.path.startsWith('/app/'))
const contexto = computed(() =>
  areaUsuario.value
    ? { titulo: 'Minha unidade', descricao: 'Área do usuário' }
    : { titulo: 'Condomínio de demonstração', descricao: 'Administração condominial' },
)
const nome = computed(() => autenticacao.sessao?.pessoa.nome_completo ?? 'Usuário')
const perfil = computed(() => autenticacao.sessao?.perfis.map((item) => item.nome).join(', ') ?? '')
const iniciais = computed(() =>
  nome.value
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0])
    .join('')
    .toUpperCase(),
)
</script>

<template>
  <header
    class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b bg-background/95 px-4 backdrop-blur md:px-6"
  >
    <SidebarTrigger aria-label="Abrir ou recolher menu" />
    <Separator orientation="vertical" class="h-5" />

    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-medium">{{ contexto.titulo }}</p>
      <p class="hidden truncate text-xs text-muted-foreground sm:block">
        {{ contexto.descricao }}
      </p>
    </div>

    <Button variant="ghost" size="icon" aria-label="Notificações">
      <Bell class="size-4" aria-hidden="true" />
    </Button>

    <div class="flex h-9 items-center gap-2 px-2" :aria-label="`Usuário: ${nome}`">
      <Avatar class="size-7">
        <AvatarFallback>{{ iniciais }}</AvatarFallback>
      </Avatar>
      <span class="hidden min-w-0 text-right sm:block">
        <span class="block max-w-48 truncate text-sm font-medium">{{ nome }}</span>
        <span class="block max-w-48 truncate text-xs text-muted-foreground">{{ perfil }}</span>
      </span>
    </div>
  </header>
</template>
