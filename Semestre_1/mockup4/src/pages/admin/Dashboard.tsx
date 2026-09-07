import { useStore } from '@/stores/useStore';
import { formatCurrency } from '@/lib/format';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Building2, Users, AlertTriangle, CalendarDays, DollarSign, TrendingUp, TrendingDown } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend } from 'recharts';

export default function AdminDashboard() {
  const { apartamentos, moradores, movimentacoes, ocorrencias, reservas, cotas, categorias } = useStore();

  const totalUnidades = apartamentos.length;
  const ocupadas = apartamentos.filter(a => a.status === 'ocupado').length;
  const inadimplentes = cotas.filter(c => c.status !== 'pago').length;
  const inadimplenciaPerc = cotas.length > 0 ? ((inadimplentes / cotas.length) * 100).toFixed(1) : '0';
  const saldoTotal = 45320.50 + 28750 + 1230;
  const ocorrenciasAbertas = ocorrencias.filter(o => o.status !== 'finalizada').length;
  const reservasMes = reservas.filter(r => r.status !== 'cancelada').length;

  const meses = ['Out/25', 'Nov/25', 'Dez/25', 'Jan/26', 'Fev/26', 'Mar/26'];
  const chartData = meses.map((mes, i) => {
    const mesMovs = movimentacoes.slice(i * 10, (i + 1) * 10);
    return {
      mes,
      receitas: mesMovs.filter(m => m.tipo === 'entrada').reduce((s, m) => s + m.valor, 0),
      despesas: mesMovs.filter(m => m.tipo === 'saida').reduce((s, m) => s + m.valor, 0),
    };
  });

  const despesasPorCat = categorias.filter(c => c.tipo === 'saida').map(cat => ({
    name: cat.nome,
    value: movimentacoes.filter(m => m.categoriaId === cat.id).reduce((s, m) => s + m.valor, 0),
  })).filter(d => d.value > 0);

  const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];

  const kpis = [
    { label: 'Total de Unidades', value: `${ocupadas}/${totalUnidades}`, sub: 'ocupadas', icon: Building2, trend: 'up' as const },
    { label: 'Inadimplência', value: `${inadimplenciaPerc}%`, sub: `${inadimplentes} cotas pendentes`, icon: TrendingDown, trend: 'down' as const },
    { label: 'Saldo em Caixa', value: formatCurrency(saldoTotal), sub: '3 contas', icon: DollarSign, trend: 'up' as const },
    { label: 'Ocorrências Abertas', value: String(ocorrenciasAbertas), sub: 'aguardando resolução', icon: AlertTriangle, trend: 'neutral' as const },
    { label: 'Reservas do Mês', value: String(reservasMes), sub: 'confirmadas/pendentes', icon: CalendarDays, trend: 'up' as const },
  ];

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Dashboard</h1>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        {kpis.map(kpi => (
          <Card key={kpi.label}>
            <CardContent className="pt-6">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm text-muted-foreground">{kpi.label}</p>
                  <p className="text-2xl font-bold mt-1">{kpi.value}</p>
                  <p className="text-xs text-muted-foreground mt-1">{kpi.sub}</p>
                </div>
                <kpi.icon className="h-5 w-5 text-primary" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle className="text-base">Receitas vs Despesas (6 meses)</CardTitle></CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="mes" fontSize={12} />
                <YAxis fontSize={12} tickFormatter={v => `${(v / 1000).toFixed(0)}k`} />
                <Tooltip formatter={(v: number) => formatCurrency(v)} />
                <Bar dataKey="receitas" fill="hsl(142, 76%, 36%)" name="Receitas" radius={[4, 4, 0, 0]} />
                <Bar dataKey="despesas" fill="hsl(0, 84%, 60%)" name="Despesas" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Distribuição de Despesas</CardTitle></CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie data={despesasPorCat} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={100} label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`} labelLine={false} fontSize={11}>
                  {despesasPorCat.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                </Pie>
                <Tooltip formatter={(v: number) => formatCurrency(v)} />
              </PieChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">Atividades Recentes</CardTitle></CardHeader>
        <CardContent>
          <div className="space-y-3">
            {[
              { text: 'Nova ocorrência #1001 registrada - Barulho excessivo', date: '17/03/2026' },
              { text: 'Pagamento de cota recebido - Apto 101 Bloco A', date: '16/03/2026' },
              { text: 'Reserva confirmada - Salão de Festas 1 (22/03)', date: '15/03/2026' },
              { text: 'Comunicado enviado - Assembleia Geral Ordinária', date: '15/03/2026' },
              { text: 'Ocorrência #1002 atualizada para Em Andamento', date: '14/03/2026' },
            ].map((item, i) => (
              <div key={i} className="flex items-center justify-between py-2 border-b last:border-0">
                <span className="text-sm">{item.text}</span>
                <span className="text-xs text-muted-foreground whitespace-nowrap ml-4">{item.date}</span>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
