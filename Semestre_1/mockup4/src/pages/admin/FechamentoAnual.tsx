import { useStore } from '@/stores/useStore';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { FileDown } from 'lucide-react';
import { formatCurrency } from '@/lib/format';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { toast } from 'sonner';

const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

export default function FechamentoAnualPage() {
  const { movimentacoes } = useStore();

  const dados = meses.map((mes, i) => {
    const prefix = i < 9 ? `2025-${String(i + 4).padStart(2, '0')}` : `2026-${String(i - 8).padStart(2, '0')}`;
    const movs = movimentacoes.filter(m => m.data.startsWith(prefix));
    return {
      mes,
      receitas: movs.filter(m => m.tipo === 'entrada').reduce((s, m) => s + m.valor, 0),
      despesas: movs.filter(m => m.tipo === 'saida').reduce((s, m) => s + m.valor, 0),
    };
  }).filter(d => d.receitas > 0 || d.despesas > 0);

  const totalReceitas = dados.reduce((s, d) => s + d.receitas, 0);
  const totalDespesas = dados.reduce((s, d) => s + d.despesas, 0);

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Fechamento Anual - 2026</h1>

      <div className="grid grid-cols-3 gap-4">
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Total Receitas</p><p className="text-xl font-bold text-success">{formatCurrency(totalReceitas)}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Total Despesas</p><p className="text-xl font-bold text-destructive">{formatCurrency(totalDespesas)}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Resultado</p><p className="text-xl font-bold">{formatCurrency(totalReceitas - totalDespesas)}</p></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">Receitas vs Despesas por Mês</CardTitle></CardHeader>
        <CardContent>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={dados}>
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
        <CardContent className="p-0">
          <Table>
            <TableHeader><TableRow><TableHead>Mês</TableHead><TableHead className="text-right">Receitas</TableHead><TableHead className="text-right">Despesas</TableHead><TableHead className="text-right">Saldo</TableHead></TableRow></TableHeader>
            <TableBody>
              {dados.map(d => (
                <TableRow key={d.mes}>
                  <TableCell className="font-medium">{d.mes}</TableCell>
                  <TableCell className="text-right text-success">{formatCurrency(d.receitas)}</TableCell>
                  <TableCell className="text-right text-destructive">{formatCurrency(d.despesas)}</TableCell>
                  <TableCell className="text-right font-medium">{formatCurrency(d.receitas - d.despesas)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div className="flex gap-2">
        <Button variant="outline" onClick={() => toast.info('Exportação PDF em desenvolvimento')}><FileDown className="h-4 w-4 mr-1" />Exportar PDF</Button>
        <Button variant="outline" onClick={() => toast.info('Exportação Excel em desenvolvimento')}><FileDown className="h-4 w-4 mr-1" />Exportar Excel</Button>
      </div>
    </div>
  );
}
