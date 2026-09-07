<script setup lang="ts">
import { Building2, LogOut, Users } from '@lucide/vue'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

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
  SidebarRail,
} from '@/components/ui/sidebar'
import { useAutenticacaoStore } from '@/modules/auth/stores/autenticacao.store'

import {
  adminNavigationGroups,
  filtrarNavegacao,
  navegacaoAtiva,
  userNavigationGroups,
} from './navigation'

const route = useRoute()
const router = useRouter()
const autenticacao = useAutenticacaoStore()
const saindo = ref(false)
const areaUsuario = computed(() => route.path === '/app' || route.path.startsWith('/app/'))
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
      <SidebarGroup v-for="group in gruposVisiveis" :key="group.label">
        <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in group.items" :key="item.routeName">
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
    </SidebarContent>

    <SidebarFooter class="border-t border-sidebar-border p-2">
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton as-child :tooltip="areaUsuario ? 'Administração' : 'Minha área'">
            <RouterLink :to="{ name: areaUsuario ? 'inicio' : 'app-inicio' }">
              <Users aria-hidden="true" />
              <span>{{ areaUsuario ? 'Administração' : 'Minha área' }}</span>
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
