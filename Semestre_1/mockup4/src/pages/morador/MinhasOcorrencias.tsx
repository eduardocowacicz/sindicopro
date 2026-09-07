import { useState } from 'react';
import { useStore } from '@/stores/useStore';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';
import { formatDate } from '@/lib/format';

export default function MinhasOcorrencias() {
  const { currentUser, ocorrencias, addOcorrencia } = useStore();
  const [open, setOpen] = useState(false);
  const [tipo, setTipo] = useState('');

  const minhas = ocorrencias.filter(o => o.apartamentoId === currentUser?.apartamentoId);
  const statusColors: Record<string, 'default' | 'secondary' | 'destructive'> = { aberta: 'destructive', em_andamento: 'secondary', finalizada: 'default' };
  const statusLabels: Record<string, string> = { aberta: 'Aberta', em_andamento: 'Em Andamento', finalizada: 'Finalizada' };

  const handleAdd = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!tipo) { toast.error('Selecione o tipo'); return; }
    const fd = new FormData(e.currentTarget);
    addOcorrencia({
      id: `o${Date.now()}`, numero: 1000 + ocorrencias.length + 1,
      data: new Date().toISOString().split('T')[0],
      apartamentoId: currentUser?.apartamentoId || '',
      tipo: tipo as 'reclamacao' | 'sugestao' | 'manutencao',
      assunto: fd.get('assunto') as string,
      descricao: fd.get('descricao') as string, status: 'aberta', respostas: [],
    });
    setOpen(false);
    setTipo('');
    toast.success('Ocorrência registrada!');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Minhas Ocorrências</h1>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) setTipo(''); }}>
          <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Nova Ocorrência</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Registrar Ocorrência</DialogTitle></DialogHeader>
            <form onSubmit={handleAdd} className="space-y-4">
              <div><Label>Tipo</Label>
                <Select value={tipo} onValueChange={setTipo}>
                  <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                  <SelectContent><SelectItem value="reclamacao">Reclamação</SelectItem><SelectItem value="sugestao">Sugestão</SelectItem><SelectItem value="manutencao">Manutenção</SelectItem></SelectContent>
                </Select>
              </div>
              <div><Label>Assunto</Label><Input name="assunto" required /></div>
              <div><Label>Descrição</Label><Textarea name="descricao" rows={3} required /></div>
              <Button type="submit" className="w-full">Registrar</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader><TableRow><TableHead>Nº</TableHead><TableHead>Data</TableHead><TableHead>Tipo</TableHead><TableHead>Assunto</TableHead><TableHead>Status</TableHead></TableRow></TableHeader>
            <TableBody>
              {minhas.length === 0 ? (
                <TableRow><TableCell colSpan={5} className="text-center text-muted-foreground py-8">Nenhuma ocorrência registrada.</TableCell></TableRow>
              ) : minhas.map(o => (
                <TableRow key={o.id}>
                  <TableCell className="font-medium">#{o.numero}</TableCell>
                  <TableCell>{formatDate(o.data)}</TableCell>
                  <TableCell>{o.tipo === 'reclamacao' ? 'Reclamação' : o.tipo === 'sugestao' ? 'Sugestão' : 'Manutenção'}</TableCell>
                  <TableCell>{o.assunto}</TableCell>
                  <TableCell><Badge variant={statusColors[o.status]}>{statusLabels[o.status]}</Badge></TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
