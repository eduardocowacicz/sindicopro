<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { computed } from 'vue'

import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import type { RegistroAuditoria } from '@/lib/api-types'

const props = defineProps<{
  aberto: boolean
  titulo: string
  queryKey: string
  buscar: (() => Promise<RegistroAuditoria[]>) | null
}>()

const emit = defineEmits<{
  (e: 'update:aberto', valor: boolean): void
}>()

const habilitado = computed(() => props.aberto && props.buscar !== null)

const { data, isPending } = useQuery({
  queryKey: [props.queryKey, 'historico'],
  queryFn: () => props.buscar!(),
  enabled: habilitado,
})

const acaoRotulo: Record<string, string> = {
  CRIACAO: 'Criação',
  ATUALIZACAO: 'Atualização',
  INATIVACAO: 'Inativação',
  REATIVACAO: 'Reativação',
  BLOQUEIO: 'Bloqueio',
  DESBLOQUEIO: 'Desbloqueio',
  REDEFINICAO_SENHA: 'Redefinição de senha',
  GERENCIAR_PERMISSOES: 'Permissões alteradas',
}

function formatarData(data: string): string {
  return new Date(data).toLocaleString('pt-BR')
}
</script>

<template>
  <Sheet :open="aberto" @update:open="(v) => emit('update:aberto', v)">
    <SheetContent class="w-full overflow-y-auto sm:max-w-md">
      <SheetHeader>
        <SheetTitle>Histórico — {{ titulo }}</SheetTitle>
      </SheetHeader>

      <div class="space-y-3 px-4 pb-6">
        <p v-if="isPending" class="text-sm text-muted-foreground">Carregando...</p>
        <p v-else-if="!data || data.length === 0" class="text-sm text-muted-foreground">
          Nenhum registro de histórico ainda.
        </p>
        <div v-for="(registro, indice) in data" :key="indice" class="rounded-md border p-3 text-sm">
          <div class="flex items-center justify-between">
            <span class="font-medium">{{ acaoRotulo[registro.acao] ?? registro.acao }}</span>
            <span class="text-xs text-muted-foreground">{{
              formatarData(registro.ocorrido_em)
            }}</span>
          </div>
          <p v-if="registro.usuario_nome" class="mt-1 text-xs text-muted-foreground">
            Por {{ registro.usuario_nome }}
          </p>
          <p v-if="registro.motivo" class="mt-1 text-xs">Motivo: {{ registro.motivo }}</p>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>
