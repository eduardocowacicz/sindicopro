import { useStore } from '@/stores/useStore';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/format';
import { Receipt } from 'lucide-react';
import { toast } from 'sonner';

export default function CotaCondominialPage() {
  const { cotas, apartamentos, blocos, moradores, movimentacoes } = useStore();

  const despesasMes = movimentacoes.filter(m => m.tipo === 'saida' && m.data.startsWith('2026-03')).reduce((s, m) => s + m.valor, 0);
  const unidadesAtivas = apartamentos.filter(a => a.status === 'ocupado').length;
  const cotaCalculada = unidadesAtivas > 0 ? despesasMes / unidadesAtivas : 0;

  const getAptLabel = (aptId: string) => {
    const apt = apartamentos.find(a => a.id === aptId);
    const bloco = blocos.find(b => b.id === apt?.blocoId);
    return `${bloco?.nome} - ${apt?.numero}`;
  };
  const getMorador = (aptId: string) => {
    const apt = apartamentos.find(a => a.id === aptId);
    return moradores.find(m => m.id === apt?.moradorId)?.nome || '-';
  };

  const statusColors: Record<string, 'default' | 'secondary' | 'destructive'> = { pago: 'default', pendente: 'secondary', atrasado: 'destructive' };
  const statusLabels: Record<string, string> = { pago: 'Pago', pendente: 'Pendente', atrasado: 'Atrasado' };

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Cota Condominial</h1>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Despesas do Mês</p><p className="text-2xl font-bold">{formatCurrency(despesasMes)}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Unidades Ativas</p><p className="text-2xl font-bold">{unidadesAtivas}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Cota Calculada</p><p className="text-2xl font-bold">{formatCurrency(cotaCalculada)}</p><p className="text-xs text-muted-foreground">por unidade</p></CardContent></Card>
      </div>

      <Button onClick={() => toast.success('Cotas geradas com sucesso!')}><Receipt className="h-4 w-4 mr-2" />Gerar Cotas do Mês</Button>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader><TableRow><TableHead>Apartamento</TableHead><TableHead>Morador</TableHead><TableHead>Valor</TableHead><TableHead>Status</TableHead><TableHead>Data Pagamento</TableHead></TableRow></TableHeader>
            <TableBody>
              {cotas.map(c => (
                <TableRow key={c.id}>
                  <TableCell className="font-medium">{getAptLabel(c.apartamentoId)}</TableCell>
                  <TableCell>{getMorador(c.apartamentoId)}</TableCell>
                  <TableCell>{formatCurrency(c.valor)}</TableCell>
                  <TableCell><Badge variant={statusColors[c.status]}>{statusLabels[c.status]}</Badge></TableCell>
                  <TableCell>{c.dataPagamento ? new Intl.DateTimeFormat('pt-BR').format(new Date(c.dataPagamento)) : '-'}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
