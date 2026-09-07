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
import { Checkbox } from '@/components/ui/checkbox';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';

export default function UsuariosPage() {
  const { users, apartamentos, blocos, addUser, updateUser } = useStore();
  const [open, setOpen] = useState(false);
  const [roles, setRoles] = useState<string[]>([]);
  const [aptId, setAptId] = useState('');

  const getAptLabel = (id?: string) => {
    if (!id) return '-';
    const apt = apartamentos.find(a => a.id === id);
    const bloco = blocos.find(b => b.id === apt?.blocoId);
    return `${bloco?.nome} - ${apt?.numero}`;
  };

  const resetForm = () => { setRoles([]); setAptId(''); };

  const handleAdd = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (roles.length === 0) { toast.error('Selecione ao menos um perfil'); return; }
    const fd = new FormData(e.currentTarget);
    addUser({
      id: `u${Date.now()}`, nome: fd.get('nome') as string, email: fd.get('email') as string,
      roles: roles as any, apartamentoId: aptId || undefined, ativo: true,
    });
    setOpen(false);
    resetForm();
    toast.success('Usuário criado!');
  };

  const roleLabels: Record<string, string> = { master: 'Master', sindico: 'Síndico', subsindico: 'Subsíndico', conselho: 'Conselho', morador: 'Morador' };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Usuários</h1>
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (!v) resetForm(); }}>
          <DialogTrigger asChild><Button size="sm"><Plus className="h-4 w-4 mr-1" />Novo Usuário</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Novo Usuário</DialogTitle></DialogHeader>
            <form onSubmit={handleAdd} className="space-y-4">
              <div><Label>Nome</Label><Input name="nome" required /></div>
              <div><Label>E-mail</Label><Input name="email" type="email" required /></div>
              <div><Label>Senha Temporária</Label><Input name="senha" type="password" required /></div>
              <div>
                <Label>Perfis</Label>
                <div className="flex gap-3 mt-2 flex-wrap">
                  {Object.entries(roleLabels).map(([k, v]) => (
                    <label key={k} className="flex items-center gap-2 text-sm">
                      <Checkbox checked={roles.includes(k)} onCheckedChange={c => setRoles(p => c ? [...p, k] : p.filter(r => r !== k))} />
                      {v}
                    </label>
                  ))}
                </div>
              </div>
              <div><Label>Apartamento (se morador)</Label>
                <Select value={aptId} onValueChange={setAptId}>
                  <SelectTrigger><SelectValue placeholder="Opcional" /></SelectTrigger>
                  <SelectContent>{apartamentos.map(a => <SelectItem key={a.id} value={a.id}>{getAptLabel(a.id)}</SelectItem>)}</SelectContent>
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
            <TableHeader><TableRow><TableHead>Nome</TableHead><TableHead>E-mail</TableHead><TableHead>Perfis</TableHead><TableHead>Apartamento</TableHead><TableHead>Status</TableHead><TableHead>Ações</TableHead></TableRow></TableHeader>
            <TableBody>
              {users.map(u => (
                <TableRow key={u.id} className={!u.ativo ? 'opacity-50' : ''}>
                  <TableCell className="font-medium">{u.nome}</TableCell>
                  <TableCell>{u.email}</TableCell>
                  <TableCell><div className="flex gap-1">{u.roles.map(r => <Badge key={r} variant="secondary" className="text-xs">{roleLabels[r]}</Badge>)}</div></TableCell>
                  <TableCell>{getAptLabel(u.apartamentoId)}</TableCell>
                  <TableCell><Badge variant={u.ativo ? 'default' : 'secondary'}>{u.ativo ? 'Ativo' : 'Inativo'}</Badge></TableCell>
                  <TableCell>
                    <Button size="sm" variant="ghost" onClick={() => { updateUser(u.id, { ativo: !u.ativo }); toast.success(u.ativo ? 'Usuário inativado' : 'Usuário reativado'); }}>
                      {u.ativo ? 'Inativar' : 'Reativar'}
                    </Button>
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
