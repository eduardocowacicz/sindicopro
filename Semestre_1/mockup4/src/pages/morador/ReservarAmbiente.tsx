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
import { formatDate } from '@/lib/format';

export default function ReservarAmbiente() {
  const { currentUser, reservas, addReserva, updateReservaStatus } = useStore();
  const [open, setOpen] = useState(false);
  const [ambiente, setAmbiente] = useState('');

  const minhas = reservas.filter(r => r.apartamentoId === currentUser?.apartamentoId);

  const handleAdd = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!ambiente) { toast.error('Selecione o ambiente'); return; }
    const fd = new FormData(e.currentTarget);
    const data = fd.get('data') as string;
    const conflict = reservas.some(r => r.data === data && r.ambiente === ambiente && r.status !== 'cancelada');
    if (conflict) { toast.error('Este ambiente já está reservado nesta data!'); return; }
    addReserva({
      id: `r${Date.now()}`, ambiente, data,
      horarioInicio: fd.get('horarioInicio') as string,
      horarioFim: fd.get('horarioFim') as string,
      apartamentoId: currentUser?.apartamentoId || '',
      moradorId: '', status: 'pendente',
    });
    setOpen(false);
    setAmbiente('');
    toast.success('Reserva solicitada!');
  };

  const statusColors: Record<string, 'default' | 'secondary' | 'destructive'> = { confirmada: 'default', pendente: 'secondary', cancelada: 'destructive' };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Reservar Ambiente</h1>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) setAmbiente(''); }}>
          <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Nova Reserva</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Nova Reserva</DialogTitle></DialogHeader>
            <form onSubmit={handleAdd} className="space-y-4">
              <div><Label>Ambiente</Label>
                <Select value={ambiente} onValueChange={setAmbiente}>
                  <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                  <SelectContent><SelectItem value="Salão de Festas 1">Salão de Festas 1</SelectItem><SelectItem value="Salão de Festas 2">Salão de Festas 2</SelectItem></SelectContent>
                </Select>
              </div>
              <div><Label>Data</Label><Input name="data" type="date" required /></div>
              <div className="grid grid-cols-2 gap-2">
                <div><Label>Início</Label><Input name="horarioInicio" type="time" required /></div>
                <div><Label>Fim</Label><Input name="horarioFim" type="time" required /></div>
              </div>
              <Button type="submit" className="w-full">Solicitar</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader><TableRow><TableHead>Data</TableHead><TableHead>Ambiente</TableHead><TableHead>Horário</TableHead><TableHead>Status</TableHead><TableHead>Ações</TableHead></TableRow></TableHeader>
            <TableBody>
              {minhas.length === 0 ? (
                <TableRow><TableCell colSpan={5} className="text-center text-muted-foreground py-8">Nenhuma reserva encontrada.</TableCell></TableRow>
              ) : minhas.map(r => (
                <TableRow key={r.id} className={r.status === 'cancelada' ? 'opacity-50' : ''}>
                  <TableCell>{formatDate(r.data)}</TableCell>
                  <TableCell>{r.ambiente}</TableCell>
                  <TableCell>{r.horarioInicio} - {r.horarioFim}</TableCell>
                  <TableCell><Badge variant={statusColors[r.status]}>{r.status === 'confirmada' ? 'Confirmada' : r.status === 'pendente' ? 'Pendente' : 'Cancelada'}</Badge></TableCell>
                  <TableCell>
                    {r.status !== 'cancelada' && <Button size="sm" variant="ghost" className="text-destructive" onClick={() => { updateReservaStatus(r.id, 'cancelada'); toast.info('Reserva cancelada.'); }}>Cancelar</Button>}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
