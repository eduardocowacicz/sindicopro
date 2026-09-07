import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Route, Routes } from "react-router-dom";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import Login from "./pages/Login";
import AdminLayout from "./components/layouts/AdminLayout";
import MoradorLayout from "./components/layouts/MoradorLayout";
import AdminDashboard from "./pages/admin/Dashboard";
import BlocosApartamentos from "./pages/admin/BlocosApartamentos";
import MoradoresPage from "./pages/admin/Moradores";
import VeiculosPage from "./pages/admin/Veiculos";
import MovimentacoesPage from "./pages/admin/Movimentacoes";
import ContasBancariasPage from "./pages/admin/ContasBancarias";
import CategoriasPage from "./pages/admin/Categorias";
import CotaCondominialPage from "./pages/admin/CotaCondominial";
import FechamentoMensalPage from "./pages/admin/FechamentoMensal";
import FechamentoAnualPage from "./pages/admin/FechamentoAnual";
import ReservasPage from "./pages/admin/Reservas";
import ComunicadosPage from "./pages/admin/Comunicados";
import OcorrenciasPage from "./pages/admin/Ocorrencias";
import UsuariosPage from "./pages/admin/Usuarios";
import ConfiguracoesPage from "./pages/admin/Configuracoes";
import DashboardMorador from "./pages/morador/DashboardMorador";
import MeuApartamento from "./pages/morador/MeuApartamento";
import ReservarAmbiente from "./pages/morador/ReservarAmbiente";
import MeusComunicados from "./pages/morador/MeusComunicados";
import MinhasOcorrencias from "./pages/morador/MinhasOcorrencias";
import NotFound from "./pages/NotFound";

const queryClient = new QueryClient();

const App = () => (
  <QueryClientProvider client={queryClient}>
    <TooltipProvider>
      <Sonner />
      <BrowserRouter>
        <Routes>
          <Route path="/" element={<Login />} />
          <Route path="/admin" element={<AdminLayout />}>
            <Route index element={<AdminDashboard />} />
            <Route path="blocos" element={<BlocosApartamentos />} />
            <Route path="moradores" element={<MoradoresPage />} />
            <Route path="veiculos" element={<VeiculosPage />} />
            <Route path="movimentacoes" element={<MovimentacoesPage />} />
            <Route path="contas" element={<ContasBancariasPage />} />
            <Route path="categorias" element={<CategoriasPage />} />
            <Route path="cotas" element={<CotaCondominialPage />} />
            <Route path="fechamento-mensal" element={<FechamentoMensalPage />} />
            <Route path="fechamento-anual" element={<FechamentoAnualPage />} />
            <Route path="reservas" element={<ReservasPage />} />
            <Route path="comunicados" element={<ComunicadosPage />} />
            <Route path="ocorrencias" element={<OcorrenciasPage />} />
            <Route path="usuarios" element={<UsuariosPage />} />
            <Route path="configuracoes" element={<ConfiguracoesPage />} />
          </Route>
          <Route path="/morador" element={<MoradorLayout />}>
            <Route index element={<DashboardMorador />} />
            <Route path="apartamento" element={<MeuApartamento />} />
            <Route path="reservas" element={<ReservarAmbiente />} />
            <Route path="comunicados" element={<MeusComunicados />} />
            <Route path="ocorrencias" element={<MinhasOcorrencias />} />
          </Route>
          <Route path="*" element={<NotFound />} />
        </Routes>
      </BrowserRouter>
    </TooltipProvider>
  </QueryClientProvider>
);

export default App;
