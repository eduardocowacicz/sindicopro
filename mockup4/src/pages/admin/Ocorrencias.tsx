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
import { Plus, MessageSquare } from 'lucide-react';
import { toast } from 'sonner';
import { formatDate } from '@/lib/format';

export default function OcorrenciasPage() {
  const { ocorrencias, apartamentos, blocos, addOcorrencia, updateOcorrenciaStatus } = useStore();
  const [open, setOpen] = useState(false);
  const [detail, setDetail] = useState<string | null>(null);
  const [resposta, setResposta] = useState('');
  const [formTipo, setFormTipo] = useState('');
  const [formAptId, setFormAptId] = useState('');

  const getAptLabel = (aptId: string) => {
    const apt = apartamentos.find(a => a.id === aptId);
    const bloco = blocos.find(b => b.id === apt?.blocoId);
    return `${bloco?.nome} - ${apt?.numero}`;
  };

  const statusColors: Record<string, 'default' | 'secondary' | 'destructive'> = { aberta: 'destructive', em_andamento: 'secondary', finalizada: 'default' };
  const statusLabels: Record<string, string> = { aberta: 'Aberta', em_andamento: 'Em Andamento', finalizada: 'Finalizada' };
  const tipoLabels: Record<string, string> = { reclamacao: 'Reclamação', sugestao: 'Sugestão', manutencao: 'Manutenção' };

  const resetForm = () => { setFormTipo(''); setFormAptId(''); };

  const handleAdd = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!formTipo) { toast.error('Selecione o tipo'); return; }
    if (!formAptId) { toast.error('Selecione o apartamento'); return; }
    const fd = new FormData(e.currentTarget);
    addOcorrencia({
      id: `o${Date.now()}`, numero: 1000 + ocorrencias.length + 1,
      data: new Date().toISOString().split('T')[0],
      apartamentoId: formAptId,
      tipo: formTipo as 'reclamacao' | 'sugestao' | 'manutencao',
      assunto: fd.get('assunto') as string, descricao: fd.get('descricao') as string,
      status: 'aberta', respostas: [],
    });
    setOpen(false);
    resetForm();
    toast.success('Ocorrência registrada!');
  };

  const selected = ocorrencias.find(o => o.id === detail);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Ocorrências</h1>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) resetForm(); }}>
          <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Registrar Ocorrência</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Nova Ocorrência</DialogTitle></DialogHeader>
            <form onSubmit={handleAdd} className="space-y-4">
              <div><Label>Tipo</Label>
                <Select value={formTipo} onValueChange={setFormTipo}>
                  <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                  <SelectContent><SelectItem value="reclamacao">Reclamação</SelectItem><SelectItem value="sugestao">Sugestão</SelectItem><SelectItem value="manutencao">Manutenção</SelectItem></SelectContent>
                </Select>
              </div>
              <div><Label>Apartamento</Label>
                <Select value={formAptId} onValueChange={setFormAptId}>
                  <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                  <SelectContent>{apartamentos.filter(a => a.status === 'ocupado').map(a => <SelectItem key={a.id} value={a.id}>{getAptLabel(a.id)}</SelectItem>)}</SelectContent>
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
            <TableHeader><TableRow><TableHead>Nº</TableHead><TableHead>Data</TableHead><TableHead>Apartamento</TableHead><TableHead>Tipo</TableHead><TableHead>Assunto</TableHead><TableHead>Status</TableHead><TableHead>Ações</TableHead></TableRow></TableHeader>
            <TableBody>
              {ocorrencias.map(o => (
                <TableRow key={o.id}>
                  <TableCell className="font-medium">#{o.numero}</TableCell>
                  <TableCell>{formatDate(o.data)}</TableCell>
                  <TableCell>{getAptLabel(o.apartamentoId)}</TableCell>
                  <TableCell>{tipoLabels[o.tipo]}</TableCell>
                  <TableCell>{o.assunto}</TableCell>
                  <TableCell><Badge variant={statusColors[o.status]}>{statusLabels[o.status]}</Badge></TableCell>
                  <TableCell><Button size="sm" variant="ghost" onClick={() => setDetail(o.id)}><MessageSquare className="h-4 w-4" /></Button></TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Dialog open={!!detail} onOpenChange={() => setDetail(null)}>
        <DialogContent className="max-w-lg">
          {selected && (
            <>
              <DialogHeader>
                <DialogTitle>Ocorrência #{selected.numero} - {selected.assunto}</DialogTitle>
              </DialogHeader>
              <div className="space-y-4">
                <div className="text-sm space-y-1">
                  <p><strong>Tipo:</strong> {tipoLabels[selected.tipo]}</p>
                  <p><strong>Apartamento:</strong> {getAptLabel(selected.apartamentoId)}</p>
                  <p><strong>Data:</strong> {formatDate(selected.data)}</p>
                  <p><strong>Status:</strong> <Badge variant={statusColors[selected.status]}>{statusLabels[selected.status]}</Badge></p>
                </div>
                <div><p className="text-sm font-medium mb-1">Descrição</p><p className="text-sm text-muted-foreground">{selected.descricao}</p></div>
                {selected.respostas.length > 0 && (
                  <div>
                    <p className="text-sm font-medium mb-2">Histórico</p>
                    {selected.respostas.map(r => (
                      <div key={r.id} className="border-l-2 border-primary pl-3 mb-2">
                        <p className="text-xs text-muted-foreground">{formatDate(r.data)} - {r.autor}</p>
                        <p className="text-sm">{r.texto}</p>
                      </div>
                    ))}
                  </div>
                )}
                {selected.status !== 'finalizada' && (
                  <div className="space-y-2">
                    <Textarea placeholder="Resposta..." value={resposta} onChange={e => setResposta(e.target.value)} />
                    <div className="flex gap-2">
                      <Button
                        variant="outline"
                        onClick={() => {
                          if (!resposta.trim()) { toast.error('Escreva uma resposta'); return; }
                          updateOcorrenciaStatus(selected.id, 'em_andamento', { autor: 'Admin', texto: resposta });
                          setResposta('');
                          toast.success('Resposta salva!');
                        }}
                      >
                        Em Andamento
                      </Button>
                      <Button
                        onClick={() => {
                          if (!resposta.trim()) { toast.error('Escreva uma resposta'); return; }
                          updateOcorrenciaStatus(selected.id, 'finalizada', { autor: 'Admin', texto: resposta });
                          setResposta('');
                          toast.success('Ocorrência finalizada!');
                        }}
                      >
                        Finalizar
                      </Button>
                    </div>
                  </div>
                )}
              </div>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
