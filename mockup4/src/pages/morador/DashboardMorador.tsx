import { useStore } from '@/stores/useStore';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useNavigate } from 'react-router-dom';
import { CalendarDays, Megaphone, AlertTriangle, Receipt } from 'lucide-react';
import { formatCurrency } from '@/lib/format';

export default function DashboardMorador() {
  const { currentUser, apartamentos, blocos, moradores, cotas, reservas, ocorrencias } = useStore();
  const navigate = useNavigate();

  const apt = apartamentos.find(a => a.id === currentUser?.apartamentoId);
  const bloco = blocos.find(b => b.id === apt?.blocoId);
  const minhaCota = cotas.find(c => c.apartamentoId === currentUser?.apartamentoId);
  const minhasReservas = reservas.filter(r => r.apartamentoId === currentUser?.apartamentoId && r.status !== 'cancelada');
  const minhasOcorrencias = ocorrencias.filter(o => o.apartamentoId === currentUser?.apartamentoId && o.status !== 'finalizada');

  const statusColors: Record<string, 'default' | 'secondary' | 'destructive'> = { pago: 'default', pendente: 'secondary', atrasado: 'destructive' };
  const statusLabels: Record<string, string> = { pago: 'Pago', pendente: 'Pendente', atrasado: 'Atrasado' };

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Olá, {currentUser?.nome}!</h1>
      <p className="text-muted-foreground">{bloco?.nome} - Apartamento {apt?.numero}</p>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-start justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Minha Cota do Mês</p>
                <p className="text-2xl font-bold mt-1">{minhaCota ? formatCurrency(minhaCota.valor) : '-'}</p>
                {minhaCota && <Badge variant={statusColors[minhaCota.status]} className="mt-2">{statusLabels[minhaCota.status]}</Badge>}
              </div>
              <Receipt className="h-5 w-5 text-primary" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-start justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Minhas Reservas</p>
                <p className="text-2xl font-bold mt-1">{minhasReservas.length}</p>
                <p className="text-xs text-muted-foreground mt-1">ativas</p>
              </div>
              <CalendarDays className="h-5 w-5 text-primary" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-start justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Ocorrências Abertas</p>
                <p className="text-2xl font-bold mt-1">{minhasOcorrencias.length}</p>
              </div>
              <AlertTriangle className="h-5 w-5 text-primary" />
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Button variant="outline" className="h-20 flex-col gap-2" onClick={() => navigate('/morador/reservas')}>
          <CalendarDays className="h-5 w-5" />Fazer Reserva
        </Button>
        <Button variant="outline" className="h-20 flex-col gap-2" onClick={() => navigate('/morador/ocorrencias')}>
          <AlertTriangle className="h-5 w-5" />Registrar Ocorrência
        </Button>
        <Button variant="outline" className="h-20 flex-col gap-2" onClick={() => navigate('/morador/comunicados')}>
          <Megaphone className="h-5 w-5" />Ver Comunicados
        </Button>
      </div>
    </div>
  );
}
