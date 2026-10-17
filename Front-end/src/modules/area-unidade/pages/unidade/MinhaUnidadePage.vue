<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query'
import { Car, Home, LoaderCircle, Mail, Phone, Users } from '@lucide/vue'

import PageHeader from '@/components/app/PageHeader.vue'
import { Badge } from '@/components/ui/badge'
import * as moradorApi from '@/modules/area-unidade/api/morador.api'

const { data: unidade, isPending } = useQuery({
  queryKey: ['morador-minha-unidade'],
  queryFn: () => moradorApi.obterMinhaUnidade(),
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      titulo="Minha Unidade"
      descricao="Dados cadastrais do seu apartamento, moradores vinculados e veículos registrados."
      modulo="Meu Espaço"
    />

    <div v-if="isPending" class="flex justify-center py-16">
      <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="unidade" class="space-y-6">
      <!-- Cartão da Unidade -->
      <div class="rounded-2xl border bg-card p-6 shadow-sm">
        <div
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b pb-4"
        >
          <div class="flex items-center gap-3">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary"
            >
              <Home class="h-6 w-6" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-foreground">Apartamento {{ unidade.codigo }}</h2>
              <p class="text-sm text-muted-foreground">
                {{ unidade.bloco?.nome }}
                {{ unidade.bloco?.apelido ? `(${unidade.bloco.apelido})` : '' }}
              </p>
            </div>
          </div>

          <Badge variant="secondary" class="self-start sm:self-auto font-medium">
            {{ unidade.situacao_ocupacao ?? 'Ocupado' }}
          </Badge>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-sm">
          <div>
            <span class="text-xs text-muted-foreground block">Andar</span>
            <span class="font-semibold">{{
              unidade.andar ? `${unidade.andar}º andar` : 'Térreo'
            }}</span>
          </div>
          <div>
            <span class="text-xs text-muted-foreground block">Área Privativa</span>
            <span class="font-semibold">{{
              unidade.metragem ? `${unidade.metragem} m²` : '—'
            }}</span>
          </div>
          <div>
            <span class="text-xs text-muted-foreground block">Fração Ideal</span>
            <span class="font-semibold">{{
              unidade.fracao_ideal ? Number(unidade.fracao_ideal).toFixed(4) : '—'
            }}</span>
          </div>
          <div>
            <span class="text-xs text-muted-foreground block">Condomínio</span>
            <span class="font-semibold">SíndicoPro Residencial</span>
          </div>
        </div>

        <div v-if="unidade.observacoes" class="mt-4 pt-4 border-t">
          <span class="text-xs text-muted-foreground block mb-1">Observações do Imóvel</span>
          <p class="text-sm bg-muted/30 p-3 rounded-lg">{{ unidade.observacoes }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Moradores e Vínculos -->
        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
          <div class="border-b bg-muted/30 p-4 flex items-center justify-between">
            <h3 class="font-semibold text-base flex items-center gap-2">
              <Users class="h-4 w-4 text-primary" />
              Moradores e Contatos Cadastrados
            </h3>
          </div>

          <div
            v-if="!unidade.moradores || unidade.moradores.length === 0"
            class="p-8 text-center text-sm text-muted-foreground"
          >
            Nenhum morador registrado no momento.
          </div>

          <div v-else class="divide-y divide-border">
            <div
              v-for="(m, idx) in unidade.moradores"
              :key="idx"
              class="p-4 flex items-start justify-between gap-3 hover:bg-muted/10 transition-colors"
            >
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-sm">{{ m.pessoa?.nome_completo }}</span>
                  <Badge v-if="m.responsavel_financeiro" variant="secondary" class="text-xs">
                    Resp. Financeiro
                  </Badge>
                </div>

                <div class="flex flex-col gap-0.5 text-xs text-muted-foreground">
                  <span v-if="m.pessoa?.email" class="flex items-center gap-1">
                    <Mail class="h-3 w-3" />
                    {{ m.pessoa.email }}
                  </span>
                  <span v-if="m.pessoa?.telefone" class="flex items-center gap-1">
                    <Phone class="h-3 w-3" />
                    {{ m.pessoa.telefone }}
                  </span>
                </div>
              </div>

              <Badge variant="outline" class="text-xs shrink-0">
                {{ m.tipo_vinculo }}
              </Badge>
            </div>
          </div>
        </div>

        <!-- Veículos Registrados -->
        <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
          <div class="border-b bg-muted/30 p-4 flex items-center justify-between">
            <h3 class="font-semibold text-base flex items-center gap-2">
              <Car class="h-4 w-4 text-primary" />
              Veículos e Vagas de Garagem
            </h3>
          </div>

          <div
            v-if="!unidade.veiculos || unidade.veiculos.length === 0"
            class="p-8 text-center text-sm text-muted-foreground"
          >
            Nenhum veículo cadastrado para esta unidade.
          </div>

          <div v-else class="divide-y divide-border">
            <div
              v-for="(v, idx) in unidade.veiculos"
              :key="idx"
              class="p-4 flex items-center justify-between hover:bg-muted/10 transition-colors"
            >
              <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                  <span
                    class="font-bold text-sm text-foreground tracking-wide font-mono bg-muted px-2 py-0.5 rounded border"
                  >
                    {{ v.placa }}
                  </span>
                  <span class="font-medium text-sm">{{ v.modelo }}</span>
                </div>
                <div class="text-xs text-muted-foreground">Cor: {{ v.cor }}</div>
              </div>

              <div class="text-right">
                <Badge variant="secondary" class="font-medium">
                  {{ v.vaga ?? 'Vaga rotativa' }}
                </Badge>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
