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

export default function BlocosApartamentos() {
  const { blocos, apartamentos, moradores, addBloco, addApartamento } = useStore();
  const [blocoOpen, setBlocoOpen] = useState(false);
  const [aptOpen, setAptOpen] = useState(false);
  const [blocoId, setBlocoId] = useState('');

  const handleAddBloco = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    addBloco({ id: `b${Date.now()}`, nome: fd.get('nome') as string, andares: Number(fd.get('andares')), aptsPorAndar: Number(fd.get('aptsPorAndar')) });
    setBlocoOpen(false);
    toast.success('Bloco adicionado com sucesso!');
  };

  const handleAddApt = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!blocoId) { toast.error('Selecione um bloco'); return; }
    const fd = new FormData(e.currentTarget);
    addApartamento({ id: `a${Date.now()}`, blocoId, numero: fd.get('numero') as string, andar: Number(fd.get('andar')), status: 'vago' });
    setAptOpen(false);
    setBlocoId('');
    toast.success('Apartamento adicionado com sucesso!');
  };

  const getMorador = (moradorId?: string) => moradores.find(m => m.id === moradorId)?.nome || '-';
  const getBloco = (bId: string) => blocos.find(b => b.id === bId)?.nome || '';

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Blocos e Apartamentos</h1>
        <div className="flex gap-2">
          <Dialog open={blocoOpen} onOpenChange={setBlocoOpen}>
            <DialogTrigger asChild><Button variant="outline" size="sm"><Plus className="h-4 w-4 mr-1" />Novo Bloco</Button></DialogTrigger>
            <DialogContent>
              <DialogHeader><DialogTitle>Novo Bloco</DialogTitle></DialogHeader>
              <form onSubmit={handleAddBloco} className="space-y-4">
                <div><Label>Nome do Bloco</Label><Input name="nome" required /></div>
                <div><Label>Número de Andares</Label><Input name="andares" type="number" min={1} required /></div>
                <div><Label>Apartamentos por Andar</Label><Input name="aptsPorAndar" type="number" min={1} required /></div>
                <Button type="submit" className="w-full">Salvar</Button>
              </form>
            </DialogContent>
          </Dialog>
          <Dialog open={aptOpen} onOpenChange={(v) => { setAptOpen(v); if (!v) setBlocoId(''); }}>
            <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Novo Apartamento</Button></DialogTrigger>
            <DialogContent>
              <DialogHeader><DialogTitle>Novo Apartamento</DialogTitle></DialogHeader>
              <form onSubmit={handleAddApt} className="space-y-4">
                <div><Label>Bloco</Label>
                  <Select value={blocoId} onValueChange={setBlocoId}>
                    <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                    <SelectContent>{blocos.map(b => <SelectItem key={b.id} value={b.id}>{b.nome}</SelectItem>)}</SelectContent>
                  </Select>
                </div>
                <div><Label>Número</Label><Input name="numero" required /></div>
                <div><Label>Andar</Label><Input name="andar" type="number" min={1} required /></div>
                <Button type="submit" className="w-full">Salvar</Button>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Bloco</TableHead><TableHead>Apartamento</TableHead><TableHead>Andar</TableHead><TableHead>Status</TableHead><TableHead>Morador</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {apartamentos.map(apt => (
                <TableRow key={apt.id}>
                  <TableCell className="font-medium">{getBloco(apt.blocoId)}</TableCell>
                  <TableCell>{apt.numero}</TableCell>
                  <TableCell>{apt.andar}º</TableCell>
                  <TableCell><Badge variant={apt.status === 'ocupado' ? 'default' : 'secondary'}>{apt.status === 'ocupado' ? 'Ocupado' : 'Vago'}</Badge></TableCell>
                  <TableCell>{getMorador(apt.moradorId)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
