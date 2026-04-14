import { useState } from 'react';
import { useStore } from '@/stores/useStore';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';
import { formatDate } from '@/lib/format';

export default function ComunicadosPage() {
  const { comunicados, addComunicado } = useStore();
  const [open, setOpen] = useState(false);
  const [segmentos, setSegmentos] = useState<string[]>(['Todos']);

  const handleAdd = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    addComunicado({
      id: `c${Date.now()}`, titulo: fd.get('titulo') as string,
      conteudo: fd.get('conteudo') as string,
      data: new Date().toISOString().split('T')[0], segmento: segmentos,
      canalEmail: true, canalWhatsapp: false,
    });
    setOpen(false);
    setSegmentos(['Todos']);
    toast.success('Comunicado publicado!');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Comunicados</h1>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) setSegmentos(['Todos']); }}>
          <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Novo Comunicado</Button></DialogTrigger>
          <DialogContent className="max-w-lg">
            <DialogHeader><DialogTitle>Novo Comunicado</DialogTitle></DialogHeader>
            <form onSubmit={handleAdd} className="space-y-4">
              <div><Label>Título</Label><Input name="titulo" required /></div>
              <div><Label>Conteúdo</Label><Textarea name="conteudo" rows={5} required /></div>
              <div>
                <Label>Segmentação</Label>
                <div className="flex gap-4 mt-2">
                  {['Todos', 'Bloco A', 'Bloco B'].map(seg => (
                    <label key={seg} className="flex items-center gap-2 text-sm">
                      <Checkbox checked={segmentos.includes(seg)} onCheckedChange={c => setSegmentos(p => c ? [...p, seg] : p.filter(s => s !== seg))} />
                      {seg}
                    </label>
                  ))}
                </div>
              </div>
              <Button type="submit" className="w-full">Publicar</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid gap-4">
        {comunicados.map(c => (
          <Card key={c.id}>
            <CardHeader className="pb-2">
              <div className="flex items-center justify-between">
                <CardTitle className="text-base">{c.titulo}</CardTitle>
                <span className="text-xs text-muted-foreground">{formatDate(c.data)}</span>
              </div>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground mb-3">{c.conteudo}</p>
              <div className="flex gap-2">
                {c.segmento.map(s => <Badge key={s} variant="secondary">{s}</Badge>)}
                {c.canalEmail && <Badge variant="outline">E-mail</Badge>}
                {c.canalWhatsapp && <Badge variant="outline">WhatsApp</Badge>}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
