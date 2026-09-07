import { useState } from 'react';
import { useStore } from '@/stores/useStore';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Landmark, Wallet, PiggyBank } from 'lucide-react';
import { toast } from 'sonner';
import { formatCurrency, formatDate } from '@/lib/format';

const icons = { corrente: Landmark, poupanca: PiggyBank, caixa: Wallet };

export default function ContasBancariasPage() {
  const { contasBancarias, addContaBancaria } = useStore();
  const [open, setOpen] = useState(false);
  const [tipoConta, setTipoConta] = useState('');

  const handleAdd = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!tipoConta) { toast.error('Selecione o tipo de conta'); return; }
    const fd = new FormData(e.currentTarget);
    addContaBancaria({
      id: `cb${Date.now()}`, nome: fd.get('nome') as string, banco: fd.get('banco') as string,
      agencia: fd.get('agencia') as string, conta: fd.get('conta') as string,
      tipo: tipoConta as 'corrente' | 'poupanca' | 'caixa', saldo: 0,
      ultimaAtualizacao: new Date().toISOString().split('T')[0],
    });
    setOpen(false);
    setTipoConta('');
    toast.success('Conta bancária adicionada!');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Contas Bancárias</h1>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) setTipoConta(''); }}>
          <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Nova Conta</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Nova Conta Bancária</DialogTitle></DialogHeader>
            <form onSubmit={handleAdd} className="space-y-4">
              <div><Label>Nome</Label><Input name="nome" required /></div>
              <div><Label>Banco</Label><Input name="banco" required /></div>
              <div><Label>Agência</Label><Input name="agencia" required /></div>
              <div><Label>Conta</Label><Input name="conta" required /></div>
              <div><Label>Tipo</Label>
                <Select value={tipoConta} onValueChange={setTipoConta}>
                  <SelectTrigger><SelectValue placeholder="Selecione" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="corrente">Corrente</SelectItem>
                    <SelectItem value="poupanca">Poupança</SelectItem>
                    <SelectItem value="caixa">Caixa</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <Button type="submit" className="w-full">Salvar</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {contasBancarias.map(conta => {
          const Icon = icons[conta.tipo];
          return (
            <Card key={conta.id}>
              <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Icon className="h-5 w-5 text-primary" />
                    <CardTitle className="text-base">{conta.nome}</CardTitle>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-2">
                <p className="text-sm text-muted-foreground">{conta.banco} • Ag {conta.agencia} • CC {conta.conta}</p>
                <p className="text-sm text-muted-foreground capitalize">{conta.tipo === 'poupanca' ? 'Poupança' : conta.tipo === 'corrente' ? 'Corrente' : 'Caixa'}</p>
                <p className="text-2xl font-bold">{formatCurrency(conta.saldo)}</p>
                <p className="text-xs text-muted-foreground">Atualizado em {formatDate(conta.ultimaAtualizacao)}</p>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
}
