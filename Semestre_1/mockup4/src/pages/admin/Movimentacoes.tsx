import { useState } from 'react';
import { useStore } from '@/stores/useStore';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';
import { formatCurrency, formatDate } from '@/lib/format';

export default function MovimentacoesPage() {
  const { movimentacoes, categorias, contasBancarias, addMovimentacao } = useStore();
  const [open, setOpen] = useState(false);
  const [filtroTipo, setFiltroTipo] = useState<string>('todos');
  const [formTipo, setFormTipo] = useState('');
  const [formCategoriaId, setFormCategoriaId] = useState('');
  const [formContaId, setFormContaId] = useState('');

  const filtered = filtroTipo === 'todos' ? movimentacoes : movimentacoes.filter(m => m.tipo === filtroTipo);
  const totalEntradas = filtered.filter(m => m.tipo === 'entrada').reduce((s, m) => s + m.valor, 0);
  const totalSaidas = filtered.filter(m => m.tipo === 'saida').reduce((s, m) => s + m.valor, 0);
  const getCat = (id: string) => categorias.find(c => c.id === id)?.nome || '-';
  const getConta = (id: string) => contasBancarias.find(c => c.id === id)?.nome || '-';

  const resetForm = () => { setFormTipo(''); setFormCategoriaId(''); setFormContaId(''); };

  const handleAdd = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!formTipo) { toast.error('Selecione o tipo'); return; }
    if (!formCategoriaId) { toast.error('Selecione a categoria'); return; }
    if (!formContaId) { toast.error('Selecione a conta bancária'); return; }
    const fd = new FormData(e.currentTarget);
    addMovimentacao({
      id: `mov${Date.now()}`, tipo: formTipo as 'entrada' | 'saida',
      data: fd.get('data') as string, descricao: fd.get('descricao') as string,
      valor: Number(fd.get('valor')), categoriaId: formCategoriaId,
      contaId: formContaId,
    });
    setOpen(false);
    resetForm();
    toast.success('Movimentação registrada!');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between flex-wrap gap-4">
        <h1 className="text-2xl font-bold">Movimentações</h1>
        <div className="flex gap-2 items-center">
          <Select value={filtroTipo} onValueChange={setFiltroTipo}>
            <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="todos">Todos</SelectItem>
              <SelectItem value="entrada">Entradas</SelectItem>
              <SelectItem value="saida">Saídas</SelectItem>
            </SelectContent>
          </Select>
          <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) resetForm(); }}>
            <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Nova Movimentação</Button></DialogTrigger>
            <DialogContent>
              <DialogHeader><DialogTitle>Nova Movimentação</DialogTitle></DialogHeader>
              <form onSubmit={handleAdd} className="space-y-4">
                <div><Label>Tipo</Label>
                  <Select value={formTipo} onValueChange={setFormTipo}>
                    <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                    <SelectContent><SelectItem value="entrada">Entrada</SelectItem><SelectItem value="saida">Saída</SelectItem></SelectContent>
                  </Select>
                </div>
                <div><Label>Data</Label><Input name="data" type="date" required /></div>
                <div><Label>Descrição</Label><Input name="descricao" required /></div>
                <div><Label>Valor (R$)</Label><Input name="valor" type="number" step="0.01" min="0" required /></div>
                <div><Label>Categoria</Label>
                  <Select value={formCategoriaId} onValueChange={setFormCategoriaId}>
                    <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                    <SelectContent>{categorias.map(c => <SelectItem key={c.id} value={c.id}>{c.nome}</SelectItem>)}</SelectContent>
                  </Select>
                </div>
                <div><Label>Conta Bancária</Label>
                  <Select value={formContaId} onValueChange={setFormContaId}>
                    <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                    <SelectContent>{contasBancarias.map(c => <SelectItem key={c.id} value={c.id}>{c.nome}</SelectItem>)}</SelectContent>
                  </Select>
                </div>
                <Button type="submit" className="w-full">Salvar</Button>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div className="grid grid-cols-3 gap-4">
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Total Entradas</p><p className="text-xl font-bold text-success">{formatCurrency(totalEntradas)}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Total Saídas</p><p className="text-xl font-bold text-destructive">{formatCurrency(totalSaidas)}</p></CardContent></Card>
        <Card><CardContent className="pt-4"><p className="text-sm text-muted-foreground">Saldo</p><p className="text-xl font-bold">{formatCurrency(totalEntradas - totalSaidas)}</p></CardContent></Card>
      </div>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Data</TableHead><TableHead>Descrição</TableHead><TableHead>Categoria</TableHead><TableHead>Conta</TableHead><TableHead>Tipo</TableHead><TableHead className="text-right">Valor</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.slice(0, 20).map(m => (
                <TableRow key={m.id}>
                  <TableCell>{formatDate(m.data)}</TableCell>
                  <TableCell>{m.descricao}</TableCell>
                  <TableCell>{getCat(m.categoriaId)}</TableCell>
                  <TableCell>{getConta(m.contaId)}</TableCell>
                  <TableCell><Badge variant={m.tipo === 'entrada' ? 'default' : 'destructive'}>{m.tipo === 'entrada' ? 'Entrada' : 'Saída'}</Badge></TableCell>
                  <TableCell className={`text-right font-medium ${m.tipo === 'entrada' ? 'text-success' : 'text-destructive'}`}>{formatCurrency(m.valor)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
