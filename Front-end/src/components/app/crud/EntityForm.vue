<script setup lang="ts">
import { Input } from '@/components/ui/input'
import type { CampoFormulario } from '@/lib/crud/types'

defineProps<{
  campos: CampoFormulario[]
  valores: Record<string, unknown>
  criando: boolean
}>()

function definir(chave: string, valor: unknown, valores: Record<string, unknown>): void {
  valores[chave] = valor
}
</script>

<template>
  <div class="space-y-4">
    <div v-for="campo in campos" :key="campo.chave" class="space-y-2">
      <template v-if="!campo.somenteCriacao || criando">
        <label class="text-sm font-medium" :for="campo.chave">
          {{ campo.rotulo }}
          <span v-if="campo.obrigatorio" class="text-destructive">*</span>
        </label>

        <Input
          v-if="campo.tipo === 'texto' || campo.tipo === 'senha'"
          :id="campo.chave"
          :type="campo.tipo === 'senha' ? 'password' : 'text'"
          :model-value="(valores[campo.chave] as string) ?? ''"
          :placeholder="campo.placeholder"
          @update:model-value="(v) => definir(campo.chave, v, valores)"
        />

        <Input
          v-else-if="campo.tipo === 'numero'"
          :id="campo.chave"
          type="number"
          :model-value="(valores[campo.chave] as number) ?? ''"
          :placeholder="campo.placeholder"
          @update:model-value="(v) => definir(campo.chave, v === '' ? null : Number(v), valores)"
        />

        <Input
          v-else-if="campo.tipo === 'data'"
          :id="campo.chave"
          type="date"
          :model-value="(valores[campo.chave] as string) ?? ''"
          @update:model-value="(v) => definir(campo.chave, v, valores)"
        />

        <textarea
          v-else-if="campo.tipo === 'texto-longo'"
          :id="campo.chave"
          rows="3"
          class="border-input flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3"
          :placeholder="campo.placeholder"
          :value="(valores[campo.chave] as string) ?? ''"
          @input="(e) => definir(campo.chave, (e.target as HTMLTextAreaElement).value, valores)"
        />

        <select
          v-else-if="campo.tipo === 'selecao'"
          :id="campo.chave"
          class="border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3"
          :value="(valores[campo.chave] as string | number) ?? ''"
          @change="(e) => definir(campo.chave, (e.target as HTMLSelectElement).value, valores)"
        >
          <option value="" disabled>Selecione...</option>
          <option v-for="opcao in campo.opcoes" :key="opcao.valor" :value="opcao.valor">
            {{ opcao.rotulo }}
          </option>
        </select>

        <label v-else-if="campo.tipo === 'checkbox'" class="flex items-center gap-2">
          <input
            type="checkbox"
            class="size-4 rounded border-input"
            :checked="Boolean(valores[campo.chave])"
            @change="(e) => definir(campo.chave, (e.target as HTMLInputElement).checked, valores)"
          />
          <span class="text-sm text-muted-foreground">{{ campo.ajuda ?? 'Sim' }}</span>
        </label>

        <p v-if="campo.ajuda && campo.tipo !== 'checkbox'" class="text-xs text-muted-foreground">
          {{ campo.ajuda }}
        </p>
      </template>
    </div>
  </div>
</template>
