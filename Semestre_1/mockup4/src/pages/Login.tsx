import { useNavigate } from 'react-router-dom';
import { useStore } from '@/stores/useStore';
import { Building2, LogIn } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type { UserRole } from '@/types';

const demoRoles: { role: UserRole; label: string; color: string }[] = [
  { role: 'master', label: 'Master', color: 'bg-destructive text-destructive-foreground' },
  { role: 'sindico', label: 'Síndico', color: 'bg-primary text-primary-foreground' },
  { role: 'subsindico', label: 'Subsíndico', color: 'bg-info text-info-foreground' },
  { role: 'conselho', label: 'Conselho', color: 'bg-warning text-warning-foreground' },
  { role: 'morador', label: 'Morador', color: 'bg-success text-success-foreground' },
];

export default function Login() {
  const navigate = useNavigate();
  const { login } = useStore();

  const handleDemoLogin = (role: UserRole) => {
    login(role);
    navigate(role === 'morador' ? '/morador' : '/admin');
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted/30 p-4">
      <div className="w-full max-w-md space-y-6">
        <div className="text-center space-y-2">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary text-primary-foreground mb-2">
            <Building2 className="h-8 w-8" />
          </div>
          <h1 className="text-3xl font-bold tracking-tight">SindicoPro</h1>
          <p className="text-muted-foreground">Sistema de Gestão Condominial</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="text-xl">Entrar</CardTitle>
            <CardDescription>Informe suas credenciais para acessar o sistema</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="email">E-mail</Label>
              <Input id="email" type="email" placeholder="seu@email.com" />
            </div>
            <div className="space-y-2">
              <Label htmlFor="password">Senha</Label>
              <Input id="password" type="password" placeholder="••••••••" />
            </div>
            <Button className="w-full" size="lg">
              <LogIn className="mr-2 h-4 w-4" />
              Entrar
            </Button>

            <div className="relative py-2">
              <Separator />
              <span className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-card px-3 text-xs text-muted-foreground">
                Acesso Demo
              </span>
            </div>

            <div className="grid grid-cols-2 gap-2">
              {demoRoles.map(({ role, label, color }) => (
                <Button key={role} variant="outline" size="sm" onClick={() => handleDemoLogin(role)} className="text-xs">
                  <span className={`w-2 h-2 rounded-full mr-2 ${color}`} />
                  {label}
                </Button>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
