import { useStore } from '@/stores/useStore';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/lib/format';

export default function MeuApartamento() {
  const { currentUser, apartamentos, blocos, moradores, cotas, veiculos } = useStore();
  const apt = apartamentos.find(a => a.id === currentUser?.apartamentoId);
  const bloco = blocos.find(b => b.id === apt?.blocoId);
  const morador = moradores.find(m => m.apartamentoId === currentUser?.apartamentoId);
  const meusVeiculos = veiculos.filter(v => v.moradorId === morador?.id);
  const minhaCota = cotas.find(c => c.apartamentoId === currentUser?.apartamentoId);

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Meu Apartamento</h1>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card>
          <CardHeader><CardTitle className="text-base">Dados do Apartamento</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p><strong>Bloco:</strong> {bloco?.nome}</p>
            <p><strong>Apartamento:</strong> {apt?.numero}</p>
            <p><strong>Andar:</strong> {apt?.andar}º</p>
            <p><strong>Morador:</strong> {morador?.nome}</p>
            <p><strong>Tipo:</strong> {morador?.tipo === 'proprietario' ? 'Proprietário' : 'Inquilino'}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle className="text-base">Cota Condominial</CardTitle></CardHeader>
          <CardContent>
            {minhaCota ? (
              <div className="space-y-2">
                <p className="text-2xl font-bold">{formatCurrency(minhaCota.valor)}</p>
                <Badge variant={minhaCota.status === 'pago' ? 'default' : 'destructive'}>
                  {minhaCota.status === 'pago' ? 'Pago' : minhaCota.status === 'pendente' ? 'Pendente' : 'Atrasado'}
                </Badge>
              </div>
            ) : <p className="text-muted-foreground">Sem cotas pendentes</p>}
          </CardContent>
        </Card>
      </div>
      {meusVeiculos.length > 0 && (
        <Card>
          <CardHeader><CardTitle className="text-base">Meus Veículos</CardTitle></CardHeader>
          <CardContent className="p-0">
            <Table>
              <TableHeader><TableRow><TableHead>Placa</TableHead><TableHead>Modelo</TableHead><TableHead>Cor</TableHead><TableHead>Vaga</TableHead></TableRow></TableHeader>
              <TableBody>
                {meusVeiculos.map(v => (
                  <TableRow key={v.id}><TableCell>{v.placa}</TableCell><TableCell>{v.modelo}</TableCell><TableCell>{v.cor}</TableCell><TableCell>{v.vaga || '-'}</TableCell></TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
