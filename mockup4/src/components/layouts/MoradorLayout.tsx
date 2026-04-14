import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import { Home, CalendarDays, Megaphone, AlertTriangle, LogOut, Building2, Bell } from 'lucide-react';
import { useStore } from '@/stores/useStore';
import { Sidebar, SidebarContent, SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarProvider, SidebarTrigger, useSidebar } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { ThemeToggle } from '@/components/ThemeToggle';

const moradorNav = [
  { title: 'Meu Apartamento', url: '/morador', icon: Home },
  { title: 'Reservar Ambiente', url: '/morador/reservas', icon: CalendarDays },
  { title: 'Comunicados', url: '/morador/comunicados', icon: Megaphone },
  { title: 'Minhas Ocorrências', url: '/morador/ocorrencias', icon: AlertTriangle },
];

function MoradorSidebarContent() {
  const { state } = useSidebar();
  const collapsed = state === 'collapsed';
  const location = useLocation();
  const navigate = useNavigate();
  const { logout } = useStore();

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
              {moradorNav.map(item => (
                <SidebarMenuItem key={item.url}>
                  <SidebarMenuButton onClick={() => navigate(item.url)} className={cn("cursor-pointer", location.pathname === item.url && "bg-sidebar-accent text-sidebar-primary font-medium")}>
                    <item.icon className="h-4 w-4" />
                    {!collapsed && item.title}
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
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

export default function MoradorLayout() {
  const { currentUser } = useStore();
  const navigate = useNavigate();
  if (!currentUser) { navigate('/'); return null; }

  return (
    <SidebarProvider>
      <div className="min-h-screen flex w-full">
        <MoradorSidebarContent />
        <div className="flex-1 flex flex-col min-w-0">
          <header className="h-14 flex items-center justify-between border-b bg-background px-4 shrink-0">
            <SidebarTrigger />
            <div className="flex items-center gap-3">
              <ThemeToggle />
              <Button variant="ghost" size="icon" className="h-9 w-9 relative">
                <Bell className="h-4 w-4" />
                <span className="absolute top-1 right-1 h-2 w-2 rounded-full bg-destructive" />
              </Button>
              <span className="text-sm text-muted-foreground">{currentUser.nome}</span>
              <span className="text-xs bg-primary/10 text-primary px-2 py-1 rounded-full font-medium">Morador</span>
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
