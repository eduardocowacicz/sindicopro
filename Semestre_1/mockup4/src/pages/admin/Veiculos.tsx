import { useState } from 'react';
import { useStore } from '@/stores/useStore';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';

export default function VeiculosPage() {
  const { veiculos, moradores, apartamentos, blocos, addVeiculo } = useStore();
  const [open, setOpen] = useState(false);
  const [moradorId, setMoradorId] = useState('');

  const getMorador = (id: string) => moradores.find(m => m.id === id)?.nome || '-';
  const getApt = (mId: string) => {
    const m = moradores.find(m => m.id === mId);
    if (!m) return '-';
    const apt = apartamentos.find(a => a.id === m.apartamentoId);
    const bloco = blocos.find(b => b.id === apt?.blocoId);
    return `${bloco?.nome} - ${apt?.numero}`;
  };

  const handleAdd = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!moradorId) { toast.error('Selecione um morador'); return; }
    const fd = new FormData(e.currentTarget);
    addVeiculo({
      id: `v${Date.now()}`,
      moradorId,
      placa: fd.get('placa') as string,
      modelo: fd.get('modelo') as string,
      cor: fd.get('cor') as string,
      vaga: (fd.get('vaga') as string) || undefined,
    });
    setOpen(false);
    setMoradorId('');
    toast.success('Veículo cadastrado com sucesso!');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Veículos</h1>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) setMoradorId(''); }}>
          <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Novo Veículo</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Novo Veículo</DialogTitle></DialogHeader>
            <form onSubmit={handleAdd} className="space-y-4">
              <div>
                <Label>Morador</Label>
                <Select value={moradorId} onValueChange={setMoradorId}>
                  <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                  <SelectContent>{moradores.filter(m => m.status === 'ativo').map(m => <SelectItem key={m.id} value={m.id}>{m.nome}</SelectItem>)}</SelectContent>
                </Select>
              </div>
              <div><Label>Placa</Label><Input name="placa" placeholder="ABC-1234" required /></div>
              <div><Label>Modelo</Label><Input name="modelo" required /></div>
              <div><Label>Cor</Label><Input name="cor" required /></div>
              <div><Label>Vaga (opcional)</Label><Input name="vaga" /></div>
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
                <TableHead>Morador</TableHead><TableHead>Apartamento</TableHead><TableHead>Placa</TableHead><TableHead>Modelo</TableHead><TableHead>Cor</TableHead><TableHead>Vaga</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {veiculos.map(v => (
                <TableRow key={v.id}>
                  <TableCell className="font-medium">{getMorador(v.moradorId)}</TableCell>
                  <TableCell>{getApt(v.moradorId)}</TableCell>
                  <TableCell>{v.placa}</TableCell>
                  <TableCell>{v.modelo}</TableCell>
                  <TableCell>{v.cor}</TableCell>
                  <TableCell>{v.vaga || '-'}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
