import { useState } from 'react';
import { useStore } from '@/stores/useStore';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';
import { Lock, FileDown } from 'lucide-react';
import { formatCurrency, formatDate } from '@/lib/format';
import { toast } from 'sonner';

const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

export default function FechamentoMensalPage() {
  const { movimentacoes, categorias } = useStore();
  const [mes, setMes] = useState('3');
  const [ano] = useState('2026');
  const [fechados, setFechados] = useState<string[]>([]);

  const mesStr = `${ano}-${mes.padStart(2, '0')}`;
  const movsMes = movimentacoes.filter(m => m.data.startsWith(mesStr));
  const entradas = movsMes.filter(m => m.tipo === 'entrada').reduce((s, m) => s + m.valor, 0);
  const saidas = movsMes.filter(m => m.tipo === 'saida').reduce((s, m) => s + m.valor, 0);
  const isFechado = fechados.includes(mesStr);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between flex-wrap gap-4">
        <h1 className="text-2xl font-bold">Fechamento Mensal {isFechado && <Lock className="inline h-5 w-5 text-muted-foreground ml-2" />}</h1>
        <div className="flex gap-2 items-center">
          <Select value={mes} onValueChange={setMes}>
            <SelectTrigger className="w-36"><SelectValue /></SelectTrigger>
            <SelectContent>{meses.map((m, i) => <SelectItem key={i} value={String(i + 1)}>{m}</SelectItem>)}</SelectContent>
          </Select>
        </div>
      </div>

      <div className="grid grid-cols-3 gap-4">
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Total Entradas</p><p className="text-xl font-bold text-success">{formatCurrency(entradas)}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Total Saídas</p><p className="text-xl font-bold text-destructive">{formatCurrency(saidas)}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Saldo</p><p className="text-xl font-bold">{formatCurrency(entradas - saidas)}</p></CardContent></Card>
      </div>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader><TableRow><TableHead>Data</TableHead><TableHead>Descrição</TableHead><TableHead>Categoria</TableHead><TableHead>Tipo</TableHead><TableHead className="text-right">Valor</TableHead></TableRow></TableHeader>
            <TableBody>
              {movsMes.map(m => (
                <TableRow key={m.id}>
                  <TableCell>{formatDate(m.data)}</TableCell>
                  <TableCell>{m.descricao}</TableCell>
                  <TableCell>{categorias.find(c => c.id === m.categoriaId)?.nome}</TableCell>
                  <TableCell>{m.tipo === 'entrada' ? 'Entrada' : 'Saída'}</TableCell>
                  <TableCell className={`text-right font-medium ${m.tipo === 'entrada' ? 'text-success' : 'text-destructive'}`}>{formatCurrency(m.valor)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div className="flex gap-2">
        <Button variant="outline" onClick={() => toast.info('Exportação PDF em desenvolvimento')}><FileDown className="h-4 w-4 mr-1" />Exportar PDF</Button>
        <Button variant="outline" onClick={() => toast.info('Exportação Excel em desenvolvimento')}><FileDown className="h-4 w-4 mr-1" />Exportar Excel</Button>
        {!isFechado && (
          <AlertDialog>
            <AlertDialogTrigger asChild><Button variant="destructive"><Lock className="h-4 w-4 mr-1" />Fechar Mês</Button></AlertDialogTrigger>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>Fechar período?</AlertDialogTitle>
                <AlertDialogDescription>Ao fechar o mês de {meses[Number(mes) - 1]}/{ano}, não será possível editar as movimentações deste período. Deseja continuar?</AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                <AlertDialogAction onClick={() => { setFechados(p => [...p, mesStr]); toast.success('Mês fechado com sucesso!'); }}>Confirmar</AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        )}
      </div>
    </div>
  );
}
