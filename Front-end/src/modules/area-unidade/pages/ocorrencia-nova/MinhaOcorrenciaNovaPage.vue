<script setup lang="ts">
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { ArrowLeft, Send } from '@lucide/vue'
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import PageHeader from '@/components/app/PageHeader.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'
import * as ocorrenciasApi from '@/modules/ocorrencias/api/ocorrencias.api'

const router = useRouter()
const queryClient = useQueryClient()

const formulario = reactive({
  tipo_id: '',
  motivo: '',
  descricao: '',
})
const erroFormulario = ref('')

const { data: tiposData } = useQuery({
  queryKey: ['morador-tipos-ocorrencia'],
  queryFn: () => ocorrenciasApi.listarTiposOcorrencia({ somente_ativos: true }),
})
const tipos = computed(() => tiposData.value?.dados ?? [])

const mutacaoAbrir = useMutation({
  mutationFn: () =>
    moradorApi.abrirMinhaOcorrencia({
      tipo_id: formulario.tipo_id,
      motivo: formulario.motivo,
      descricao: formulario.descricao,
    }),
  onSuccess: (ocorrencia) => {
    queryClient.invalidateQueries({ queryKey: ['morador-minhas-ocorrencias'] })
    queryClient.invalidateQueries({ queryKey: ['morador-dashboard'] })
    router.push({ name: 'app-ocorrencia-detalhe', params: { id: ocorrencia.id_publico } })
  },
  onError: (err: unknown) => {
    erroFormulario.value =
      (err as { response?: { data?: { mensagem?: string } } })?.response?.data?.mensagem ||
      'Erro ao registrar ocorrência.'
  },
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <Button
        variant="ghost"
        size="sm"
        class="gap-1.5"
        @click="router.push({ name: 'app-ocorrencias' })"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar para meus chamados
      </Button>
    </div>

    <PageHeader
      titulo="Abrir Novo Chamado / Ocorrência"
      descricao="Registre solicitações de manutenção, perturbação de sossego ou dúvidas para a equipe administrativa."
      modulo="Meu Espaço"
    />

    <form class="max-w-2xl space-y-6" @submit.prevent="mutacaoAbrir.mutate()">
      <div
        v-if="erroFormulario"
        class="rounded-lg bg-destructive/10 p-4 text-sm text-destructive font-medium"
      >
        {{ erroFormulario }}
      </div>

      <div class="rounded-2xl border bg-card p-6 shadow-sm space-y-5">
        <div class="space-y-2">
          <Label for="oco-cat" class="text-sm font-semibold">Categoria da Solicitação *</Label>
          <select
            id="oco-cat"
            v-model="formulario.tipo_id"
            required
            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
          >
            <option value="" disabled>Selecione uma categoria...</option>
            <option v-for="t in tipos" :key="t.id_publico" :value="t.id_publico">
              {{ t.nome }}
            </option>
          </select>
        </div>

        <div class="space-y-2">
          <Label for="oco-assunto" class="text-sm font-semibold">Assunto / Motivo *</Label>
          <Input
            id="oco-assunto"
            v-model="formulario.motivo"
            required
            placeholder="Ex: Lâmpada queimada no corredor do 3º andar"
            class="text-base"
          />
        </div>

        <div class="space-y-2">
          <Label for="oco-msg" class="text-sm font-semibold">Descrição Detalhada *</Label>
          <textarea
            id="oco-msg"
            v-model="formulario.descricao"
            required
            rows="6"
            placeholder="Descreva o que aconteceu ou o que precisa de reparo com o máximo de detalhes possível..."
            class="w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
          ></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3">
        <Button type="button" variant="outline" @click="router.push({ name: 'app-ocorrencias' })">
          Cancelar
        </Button>
        <Button type="submit" :disabled="mutacaoAbrir.isPending.value" class="gap-2">
          <Send class="h-4 w-4" />
          Registrar Chamado
        </Button>
      </div>
    </form>
  </div>
</template>
