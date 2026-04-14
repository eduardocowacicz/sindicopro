import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import { Building2, LayoutDashboard, Users, DollarSign, CalendarClock, CalendarDays, Megaphone, AlertTriangle, UserCog, Settings, ChevronDown, LogOut, Bell } from 'lucide-react';
import { useStore } from '@/stores/useStore';
import { Sidebar, SidebarContent, SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarProvider, SidebarTrigger, useSidebar, SidebarMenuSub, SidebarMenuSubItem, SidebarMenuSubButton } from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { ThemeToggle } from '@/components/ThemeToggle';

const adminNav = [
  { title: 'Dashboard', url: '/admin', icon: LayoutDashboard },
  {
    title: 'Condomínio', icon: Building2, children: [
      { title: 'Blocos e Apartamentos', url: '/admin/blocos' },
      { title: 'Moradores', url: '/admin/moradores' },
      { title: 'Veículos', url: '/admin/veiculos' },
    ],
  },
  {
    title: 'Financeiro', icon: DollarSign, children: [
      { title: 'Movimentações', url: '/admin/movimentacoes' },
      { title: 'Contas Bancárias', url: '/admin/contas' },
      { title: 'Categorias', url: '/admin/categorias' },
      { title: 'Cota Condominial', url: '/admin/cotas' },
    ],
  },
  {
    title: 'Fechamentos', icon: CalendarClock, children: [
      { title: 'Mensal', url: '/admin/fechamento-mensal' },
      { title: 'Anual', url: '/admin/fechamento-anual' },
    ],
  },
  { title: 'Reservas', url: '/admin/reservas', icon: CalendarDays },
  { title: 'Comunicados', url: '/admin/comunicados', icon: Megaphone },
  { title: 'Ocorrências', url: '/admin/ocorrencias', icon: AlertTriangle },
  { title: 'Usuários', url: '/admin/usuarios', icon: UserCog, masterOnly: true },
  { title: 'Configurações', url: '/admin/configuracoes', icon: Settings },
];

function AppSidebarContent() {
  const { state } = useSidebar();
  const collapsed = state === 'collapsed';
  const location = useLocation();
  const navigate = useNavigate();
  const { currentUser, logout } = useStore();
  const isMaster = currentUser?.roles.includes('master');

  return (
    <Sidebar collapsible="icon" className="border-r-0">
      <div className="flex items-center gap-2 px-4 py-4 border-b border-sidebar-border">
        <Building2 className="h-7 w-7 text-sidebar-primary shrink-0" />
        {!collapsed && <span className="font-bold text-lg text-sidebar-accent-foreground">SindicoPro</span>}
      </div>
      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupContent>
            <SidebarMenu>
              {adminNav.filter(item => !item.masterOnly || isMaster).map(item => {
                if (item.children) {
                  const isActive = item.children.some(c => location.pathname === c.url);
                  return (
                    <Collapsible key={item.title} defaultOpen={isActive}>
                      <SidebarMenuItem>
                        <CollapsibleTrigger asChild>
                          <SidebarMenuButton className={cn("justify-between", isActive && "bg-sidebar-accent text-sidebar-accent-foreground")}>
                            <span className="flex items-center gap-2">
                              <item.icon className="h-4 w-4" />
                              {!collapsed && item.title}
                            </span>
                            {!collapsed && <ChevronDown className="h-4 w-4 transition-transform group-data-[state=open]:rotate-180" />}
                          </SidebarMenuButton>
                        </CollapsibleTrigger>
                        {!collapsed && (
                          <CollapsibleContent>
                            <SidebarMenuSub>
                              {item.children.map(child => (
                                <SidebarMenuSubItem key={child.url}>
                                  <SidebarMenuSubButton onClick={() => navigate(child.url)} className={cn("cursor-pointer", location.pathname === child.url && "bg-sidebar-accent text-sidebar-primary font-medium")}>
                                    {child.title}
                                  </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                              ))}
                            </SidebarMenuSub>
                          </CollapsibleContent>
                        )}
                      </SidebarMenuItem>
                    </Collapsible>
                  );
                }
                return (
                  <SidebarMenuItem key={item.title}>
                    <SidebarMenuButton onClick={() => navigate(item.url!)} className={cn("cursor-pointer", location.pathname === item.url && "bg-sidebar-accent text-sidebar-primary font-medium")}>
                      <item.icon className="h-4 w-4" />
                      {!collapsed && item.title}
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                );
              })}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>
      <div className="mt-auto border-t border-sidebar-border p-3">
        <SidebarMenuButton onClick={() => { logout(); navigate('/'); }} className="cursor-pointer text-sidebar-foreground/70 hover:text-sidebar-foreground">
          <LogOut className="h-4 w-4" />
          {!collapsed && 'Sair'}
        </SidebarMenuButton>
      </div>
    </Sidebar>
  );
}

export default function AdminLayout() {
  const { currentUser } = useStore();
  const navigate = useNavigate();
  if (!currentUser) { navigate('/'); return null; }

  return (
    <SidebarProvider>
      <div className="min-h-screen flex w-full">
        <AppSidebarContent />
        <div className="flex-1 flex flex-col min-w-0">
          <header className="h-14 flex items-center justify-between border-b bg-background px-4 shrink-0">
            <div className="flex items-center gap-2">
              <SidebarTrigger />
            </div>
            <div className="flex items-center gap-3">
              <ThemeToggle />
              <Button variant="ghost" size="icon" className="h-9 w-9 relative">
                <Bell className="h-4 w-4" />
                <span className="absolute top-1 right-1 h-2 w-2 rounded-full bg-destructive" />
              </Button>
              <span className="text-sm text-muted-foreground">{currentUser.nome}</span>
              <span className="text-xs bg-primary/10 text-primary px-2 py-1 rounded-full font-medium capitalize">{currentUser.roles[0]}</span>
            </div>
          </header>
          <main className="flex-1 overflow-auto p-6">
            <Outlet />
          </main>
        </div>
      </div>
    </SidebarProvider>
  );
}
