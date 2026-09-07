import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Settings as SettingsIcon } from 'lucide-react';

export default function ConfiguracoesPage() {
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Configurações</h1>
      <Card>
        <CardContent className="flex flex-col items-center justify-center py-12 text-center">
          <SettingsIcon className="h-12 w-12 text-muted-foreground mb-4" />
          <p className="text-lg font-medium">Configurações do Sistema</p>
          <p className="text-sm text-muted-foreground mt-1">Módulo de configurações em desenvolvimento.</p>
        </CardContent>
      </Card>
    </div>
  );
}
