<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { LoaderCircle } from '@lucide/vue'
import { computed, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Button } from '@/components/ui/button'
import { mensagemDeErro } from '@/lib/http-error'
import * as perfisApi from '@/modules/acesso/api/perfis.api'

const rota = useRoute()
const idPerfil = computed(() => rota.params.id as string)
const filaCliente = useQueryClient()

const { data: perfil } = useQuery({
  queryKey: ['perfil', idPerfil],
  queryFn: () => perfisApi.mostrarPerfil(idPerfil.value),
})

const { data: permissoes, isPending } = useQuery({
  queryKey: ['perfil-permissoes', idPerfil],
  queryFn: () => perfisApi.listarPermissoesDoPerfil(idPerfil.value),
})

const selecionadas = reactive(new Set<string>())

watch(permissoes, (valor) => {
  selecionadas.clear()
  valor?.filter((p) => p.concedida).forEach((p) => selecionadas.add(p.id_publico))
})

const porModulo = computed(() => {
  const grupos = new Map<string, NonNullable<typeof permissoes.value>>()
  for (const permissao of permissoes.value ?? []) {
    const lista = grupos.get(permissao.modulo) ?? []
    lista.push(permissao)
    grupos.set(permissao.modulo, lista)
  }
  return grupos
})

function alternar(idPublico: string): void {
  if (selecionadas.has(idPublico)) {
    selecionadas.delete(idPublico)
  } else {
    selecionadas.add(idPublico)
  }
}

const erro = ref('')

const mutacaoSalvar = useMutation({
  mutationFn: () => perfisApi.definirPermissoesDoPerfil(idPerfil.value, [...selecionadas]),
  onSuccess: async () => {
    erro.value = ''
    await filaCliente.invalidateQueries({ queryKey: ['perfil-permissoes', idPerfil] })
  },
  onError: (e: unknown) => {
    erro.value = mensagemDeErro(e)
  },
})
</script>

<template>
  <section class="mx-auto w-full max-w-4xl space-y-6">
    <PageHeader
      :title="`Permissões — ${perfil?.nome ?? ''}`"
      description="Selecione as permissões concedidas a este perfil de acesso."
      eyebrow="Acesso"
    />

    <p v-if="isPending" class="text-sm text-muted-foreground">
      <LoaderCircle class="inline size-4 animate-spin" aria-hidden="true" /> Carregando...
    </p>

    <div v-else class="space-y-6">
      <div v-for="[modulo, lista] in porModulo" :key="modulo" class="rounded-md border p-4">
        <h2 class="mb-3 text-sm font-semibold uppercase text-muted-foreground">{{ modulo }}</h2>
        <div class="grid gap-2 sm:grid-cols-2">
          <label
            v-for="permissao in lista"
            :key="permissao.id_publico"
            class="flex items-center gap-2 text-sm"
          >
            <input
              type="checkbox"
              class="size-4 rounded border-input"
              :checked="selecionadas.has(permissao.id_publico)"
              @change="alternar(permissao.id_publico)"
            />
            <span>{{ permissao.nome }}</span>
          </label>
        </div>
      </div>

      <p v-if="erro" class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
        {{ erro }}
      </p>

      <Button :disabled="mutacaoSalvar.isPending.value" @click="mutacaoSalvar.mutate()">
        <LoaderCircle
          v-if="mutacaoSalvar.isPending.value"
          class="size-4 animate-spin"
          aria-hidden="true"
        />
        Salvar permissões
      </Button>
    </div>
  </section>
</template>
