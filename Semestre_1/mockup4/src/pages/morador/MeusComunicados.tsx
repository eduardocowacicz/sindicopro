import { useStore } from '@/stores/useStore';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/format';

export default function MeusComunicados() {
  const { comunicados } = useStore();

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Comunicados</h1>
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
              <div className="flex gap-2">{c.segmento.map(s => <Badge key={s} variant="secondary">{s}</Badge>)}</div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
