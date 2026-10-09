<script setup lang="ts">
import { Input } from '@/components/ui/input'
import type { CampoFormulario } from '@/lib/crud/types'
import { formatarCpf } from '@/lib/mascaras'

const props = withDefaults(
  defineProps<{
    campos: CampoFormulario[]
    valores: Record<string, unknown>
    erros?: Record<string, string>
    criando: boolean
  }>(),
  { erros: () => ({}) },
)

function definir(chave: string, valor: unknown, valores: Record<string, unknown>): void {
  valores[chave] = valor
}

function temErro(chave: string): boolean {
  return Boolean(props.erros[chave])
}

const classeCampoNativo =
  'border-input w-full min-w-0 rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3 aria-invalid:border-destructive aria-invalid:ring-destructive/20'
</script>

<template>
  <div class="space-y-4">
    <div v-for="campo in campos" :key="campo.chave" class="space-y-2">
      <template v-if="(!campo.somenteCriacao || criando) && (!campo.somenteEdicao || !criando)">
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
          :disabled="campo.desabilitado"
          :aria-invalid="temErro(campo.chave)"
          @update:model-value="(v) => definir(campo.chave, v, valores)"
        />

        <Input
          v-else-if="campo.tipo === 'cpf'"
          :id="campo.chave"
          type="text"
          inputmode="numeric"
          maxlength="14"
          :model-value="(valores[campo.chave] as string) ?? ''"
          :placeholder="campo.placeholder"
          :disabled="campo.desabilitado"
          :aria-invalid="temErro(campo.chave)"
          @update:model-value="(v) => definir(campo.chave, formatarCpf(String(v)), valores)"
        />

        <Input
          v-else-if="campo.tipo === 'numero'"
          :id="campo.chave"
          type="number"
          :model-value="(valores[campo.chave] as number) ?? ''"
          :placeholder="campo.placeholder"
          :aria-invalid="temErro(campo.chave)"
          @update:model-value="(v) => definir(campo.chave, v === '' ? null : Number(v), valores)"
        />

        <Input
          v-else-if="campo.tipo === 'data'"
          :id="campo.chave"
          type="date"
          :model-value="(valores[campo.chave] as string) ?? ''"
          :aria-invalid="temErro(campo.chave)"
          @update:model-value="(v) => definir(campo.chave, v, valores)"
        />

        <textarea
          v-else-if="campo.tipo === 'texto-longo'"
          :id="campo.chave"
          rows="3"
          :class="[classeCampoNativo, 'py-2']"
          :placeholder="campo.placeholder"
          :value="(valores[campo.chave] as string) ?? ''"
          :aria-invalid="temErro(campo.chave)"
          @input="(e) => definir(campo.chave, (e.target as HTMLTextAreaElement).value, valores)"
        />

        <select
          v-else-if="campo.tipo === 'selecao'"
          :id="campo.chave"
          :class="[classeCampoNativo, 'h-9 py-1']"
          :value="(valores[campo.chave] as string | number) ?? ''"
          :aria-invalid="temErro(campo.chave)"
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
            :class="{ 'border-destructive': temErro(campo.chave) }"
            :checked="Boolean(valores[campo.chave])"
            :aria-invalid="temErro(campo.chave)"
            @change="(e) => definir(campo.chave, (e.target as HTMLInputElement).checked, valores)"
          />
          <span class="text-sm text-muted-foreground">{{ campo.ajuda ?? 'Sim' }}</span>
        </label>

        <p v-if="campo.ajuda && campo.tipo !== 'checkbox'" class="text-xs text-muted-foreground">
          {{ campo.ajuda }}
        </p>

        <p v-if="temErro(campo.chave)" class="text-xs text-destructive" role="alert">
          {{ erros[campo.chave] }}
        </p>
      </template>
    </div>
  </div>
</template>
