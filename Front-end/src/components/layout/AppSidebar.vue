<script setup lang="ts">
import { Building2, ChevronRight, LogOut, ShieldCheck, Users } from '@lucide/vue'
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  SidebarRail,
} from '@/components/ui/sidebar'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

import {
  adminNavigationGroups,
  filtrarNavegacao,
  navegacaoAtiva,
  type NavigationGroup,
  userNavigationGroups,
} from './navigation'

const route = useRoute()
const router = useRouter()
const autenticacao = useAutenticacaoStore()
const saindo = ref(false)

const areaUsuario = computed(() => route.path === '/app' || route.path.startsWith('/app/'))
const podeAlternarArea = computed(() => {
  if (areaUsuario.value) {
    return autenticacao.temAcessoAdmin
  }
  return autenticacao.temAcessoMorador
})
const paginaInicial = computed(() => (areaUsuario.value ? 'app-inicio' : 'inicio'))
const gruposDaArea = computed(() =>
  areaUsuario.value ? userNavigationGroups : adminNavigationGroups,
)
const gruposVisiveis = computed(() =>
  filtrarNavegacao(
    gruposDaArea.value,
    autenticacao.acessoIntegral ? undefined : new Set(autenticacao.permissoes),
  ),
)
const nomeRotaAtual = computed(() => (typeof route.name === 'string' ? route.name : undefined))

const grupoPainel = computed(() => gruposVisiveis.value.find((g) => g.label === 'Painel'))
const itemPainel = computed(() => grupoPainel.value?.items[0])
const gruposDropdown = computed(() => gruposVisiveis.value.filter((g) => g.label !== 'Painel'))

const grupoAberto = ref<string | null>(null)

function grupoTemItemAtivo(group: NavigationGroup): boolean {
  if (!nomeRotaAtual.value) return false
  return group.items.some((item) => navegacaoAtiva(item, nomeRotaAtual.value))
}

function encontrarGrupoDaRota(nomeRota?: string): string | null {
  if (!nomeRota) return null
  for (const grupo of gruposDropdown.value) {
    if (grupo.items.some((item) => navegacaoAtiva(item, nomeRota))) {
      return grupo.label
    }
  }
  return null
}

function alternarGrupo(labelGrupo: string, aberto: boolean): void {
  if (aberto) {
    grupoAberto.value = labelGrupo
  } else if (grupoAberto.value === labelGrupo) {
    grupoAberto.value = null
  }
}

watch(
  [() => route.name, () => gruposVisiveis.value],
  ([novoNome]) => {
    const nome = typeof novoNome === 'string' ? novoNome : undefined
    const grupo = encontrarGrupoDaRota(nome)
    if (grupo) {
      grupoAberto.value = grupo
    } else if (nome === 'inicio' || !nome) {
      grupoAberto.value = null
    }
  },
  { immediate: true },
)

async function sair(): Promise<void> {
  saindo.value = true

  try {
    await autenticacao.sair()
    await router.replace({ name: 'entrar' })
  } finally {
    saindo.value = false
  }
}
</script>

<template>
  <Sidebar collapsible="icon">
    <SidebarHeader class="border-b border-sidebar-border p-3">
      <RouterLink
        class="flex items-center gap-3 overflow-hidden rounded-md"
        :to="{ name: paginaInicial }"
      >
        <span
          class="grid size-8 shrink-0 place-items-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
        >
          <Building2 class="size-4" aria-hidden="true" />
        </span>
        <span class="truncate font-semibold text-sidebar-foreground">SindicoPro</span>
      </RouterLink>
    </SidebarHeader>

    <SidebarContent>
      <!-- Menu de morador / minha área -->
      <SidebarGroup v-if="areaUsuario">
        <SidebarGroupLabel>Minha área</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in gruposVisiveis[0]?.items ?? []" :key="item.routeName">
              <SidebarMenuButton
                as-child
                :is-active="navegacaoAtiva(item, nomeRotaAtual)"
                :tooltip="item.label"
              >
                <RouterLink :to="{ name: item.routeName }">
                  <component :is="item.icon" aria-hidden="true" />
                  <span>{{ item.label }}</span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <!-- Menu administrativo com dropdowns sanfonados -->
      <SidebarGroup v-else>
        <SidebarGroupLabel>Administração</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <!-- Painel / Visão Geral direto no topo -->
            <SidebarMenuItem v-if="itemPainel">
              <SidebarMenuButton
                as-child
                :is-active="navegacaoAtiva(itemPainel, nomeRotaAtual)"
                :tooltip="itemPainel.label"
              >
                <RouterLink :to="{ name: itemPainel.routeName }">
                  <component :is="itemPainel.icon" aria-hidden="true" />
                  <span>{{ itemPainel.label }}</span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <!-- Grupos sanfonados / dropdowns -->
            <Collapsible
              v-for="group in gruposDropdown"
              :key="group.label"
              as-child
              :open="grupoAberto === group.label"
              class="group/collapsible"
              @update:open="(aberto: boolean) => alternarGrupo(group.label, aberto)"
            >
              <SidebarMenuItem>
                <CollapsibleTrigger as-child>
                  <SidebarMenuButton
                    :tooltip="group.label"
                    :is-active="grupoTemItemAtivo(group) && grupoAberto !== group.label"
                  >
                    <component :is="group.icon" v-if="group.icon" aria-hidden="true" />
                    <span>{{ group.label }}</span>
                    <ChevronRight
                      class="ml-auto size-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90 group-data-[collapsible=icon]:hidden"
                    />
                  </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                  <SidebarMenuSub>
                    <SidebarMenuSubItem v-for="item in group.items" :key="item.routeName">
                      <SidebarMenuSubButton
                        as-child
                        :is-active="navegacaoAtiva(item, nomeRotaAtual)"
                      >
                        <RouterLink :to="{ name: item.routeName }">
                          <span>{{ item.label }}</span>
                        </RouterLink>
                      </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                  </SidebarMenuSub>
                </CollapsibleContent>
              </SidebarMenuItem>
            </Collapsible>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>

    <SidebarFooter class="border-t border-sidebar-border p-2">
      <SidebarMenu>
        <SidebarMenuItem v-if="podeAlternarArea">
          <SidebarMenuButton
            as-child
            :tooltip="areaUsuario ? 'Painel Administrativo' : 'Área do Morador'"
          >
            <RouterLink :to="{ name: areaUsuario ? 'inicio' : 'app-inicio' }">
              <ShieldCheck v-if="areaUsuario" aria-hidden="true" />
              <Users v-else aria-hidden="true" />
              <span>{{ areaUsuario ? 'Administração' : 'Área do Morador' }}</span>
            </RouterLink>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem>
          <SidebarMenuButton
            tooltip="Sair"
            :disabled="saindo"
            aria-label="Sair do SindicoPro"
            @click="sair"
          >
            <LogOut aria-hidden="true" />
            <span>{{ saindo ? 'Saindo...' : 'Sair' }}</span>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>
</template>
