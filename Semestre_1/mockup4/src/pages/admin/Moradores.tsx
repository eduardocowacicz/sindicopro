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
import { formatCPF, formatPhone } from '@/lib/format';

export default function MoradoresPage() {
  const { moradores, apartamentos, blocos, addMorador } = useStore();
  const [open, setOpen] = useState(false);
  const [apartamentoId, setApartamentoId] = useState('');
  const [tipo, setTipo] = useState('');

  const getAptLabel = (aptId: string) => {
    const apt = apartamentos.find(a => a.id === aptId);
    if (!apt) return '-';
    const bloco = blocos.find(b => b.id === apt.blocoId);
    return `${bloco?.nome} - ${apt.numero}`;
  };

  const handleAdd = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!apartamentoId) { toast.error('Selecione um apartamento'); return; }
    if (!tipo) { toast.error('Selecione o tipo'); return; }
    const fd = new FormData(e.currentTarget);
    addMorador({
      id: `m${Date.now()}`, nome: fd.get('nome') as string, cpf: fd.get('cpf') as string,
      telefone: fd.get('telefone') as string, email: fd.get('email') as string,
      apartamentoId, tipo: tipo as 'proprietario' | 'inquilino',
      status: 'ativo',
    });
    setOpen(false);
    setApartamentoId('');
    setTipo('');
    toast.success('Morador cadastrado com sucesso!');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Moradores</h1>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) { setApartamentoId(''); setTipo(''); } }}>
          <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Novo Morador</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Novo Morador</DialogTitle></DialogHeader>
            <form onSubmit={handleAdd} className="space-y-4">
              <div><Label>Nome Completo</Label><Input name="nome" required /></div>
              <div><Label>CPF</Label><Input name="cpf" required /></div>
              <div><Label>Telefone</Label><Input name="telefone" required /></div>
              <div><Label>E-mail</Label><Input name="email" type="email" required /></div>
              <div><Label>Apartamento</Label>
                <Select value={apartamentoId} onValueChange={setApartamentoId}>
                  <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                  <SelectContent>{apartamentos.map(a => <SelectItem key={a.id} value={a.id}>{getAptLabel(a.id)}</SelectItem>)}</SelectContent>
                </Select>
              </div>
              <div><Label>Tipo</Label>
                <Select value={tipo} onValueChange={setTipo}>
                  <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="proprietario">Proprietário</SelectItem>
                    <SelectItem value="inquilino">Inquilino</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <Button type="submit" className="w-full">Salvar</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Nome</TableHead><TableHead>CPF</TableHead><TableHead>Apartamento</TableHead><TableHead>Tipo</TableHead><TableHead>Telefone</TableHead><TableHead>E-mail</TableHead><TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {moradores.map(m => (
                <TableRow key={m.id} className={m.status === 'inativo' ? 'opacity-50' : ''}>
                  <TableCell className="font-medium">{m.nome}</TableCell>
                  <TableCell>{formatCPF(m.cpf)}</TableCell>
                  <TableCell>{getAptLabel(m.apartamentoId)}</TableCell>
                  <TableCell>{m.tipo === 'proprietario' ? 'Proprietário' : 'Inquilino'}</TableCell>
                  <TableCell>{formatPhone(m.telefone)}</TableCell>
                  <TableCell>{m.email}</TableCell>
                  <TableCell><Badge variant={m.status === 'ativo' ? 'default' : 'secondary'}>{m.status === 'ativo' ? 'Ativo' : 'Inativo'}</Badge></TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
