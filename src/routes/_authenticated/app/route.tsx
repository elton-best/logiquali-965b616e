import { createFileRoute, Outlet, useNavigate } from "@tanstack/react-router";
import { useQueryClient } from "@tanstack/react-query";
import { Bell, Menu, Search } from "lucide-react";
import { useEffect, useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import { AppSidebar } from "@/components/app/AppSidebar";
import { LqButton } from "@/components/lq/LqButton";
import { getMyProfile } from "@/lib/profile.functions";

export const Route = createFileRoute("/_authenticated/app")({
  head: () => ({
    meta: [
      { title: "Espace entreprise — LOGIQUALI" },
      { name: "description", content: "Pilotez votre système de management QHSE." },
    ],
  }),
  loader: () => getMyProfile(),
  component: AppLayout,
  errorComponent: () => (
    <div className="flex min-h-dvh items-center justify-center bg-background px-4 text-center">
      <div>
        <h1 className="font-display text-xl font-bold text-foreground">Impossible de charger votre espace</h1>
        <LqButton to="/" variant="ghost" className="mt-6">Retour à l'accueil</LqButton>
      </div>
    </div>
  ),
  notFoundComponent: () => <p className="p-8 text-sm text-muted-foreground">Page introuvable.</p>,
});

function AppLayout() {
  const profile = Route.useLoaderData();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);

  useEffect(() => {
    setCollapsed(localStorage.getItem("lq-sidebar") === "1");
  }, []);

  const toggle = () => {
    setCollapsed((c) => {
      localStorage.setItem("lq-sidebar", c ? "0" : "1");
      return !c;
    });
  };

  const signOut = async () => {
    await queryClient.cancelQueries();
    queryClient.clear();
    await supabase.auth.signOut();
    navigate({ to: "/auth/login", replace: true });
  };

  const name = [profile.first_name, profile.last_name].filter(Boolean).join(" ");
  const sidebarProps = {
    name,
    email: profile.email ?? "",
    company: profile.company_name ?? "",
    onSignOut: signOut,
  };

  return (
    <div className="flex h-dvh w-full overflow-hidden bg-background">
      <aside
        className={`hidden shrink-0 border-r border-border transition-[width] duration-300 lg:block ${
          collapsed ? "w-[76px]" : "w-[272px]"
        }`}
      >
        <AppSidebar collapsed={collapsed} onToggle={toggle} {...sidebarProps} />
      </aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="absolute inset-0 bg-foreground/40" onClick={() => setMobileOpen(false)} />
          <aside className="absolute inset-y-0 left-0 w-[280px] shadow-2xl">
            <AppSidebar
              collapsed={false}
              onToggle={() => setMobileOpen(false)}
              onNavigate={() => setMobileOpen(false)}
              {...sidebarProps}
            />
          </aside>
        </div>
      )}

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex h-16 shrink-0 items-center gap-3 border-b border-border bg-card px-4 md:px-6">
          <button
            onClick={() => setMobileOpen(true)}
            className="flex h-10 w-10 items-center justify-center rounded-xl border border-border lg:hidden"
            aria-label="Ouvrir le menu"
          >
            <Menu className="h-5 w-5" />
          </button>
          <div className="relative hidden max-w-md flex-1 md:block">
            <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-primary" />
            <input
              placeholder="Rechercher un document, une action, un audit…"
              className="h-10 w-full rounded-full border border-input bg-background pl-11 pr-4 text-sm outline-none focus:border-primary"
            />
          </div>
          <div className="ml-auto flex items-center gap-2">
            <button className="relative flex h-10 w-10 items-center justify-center rounded-xl border border-border text-muted-foreground hover:text-primary" aria-label="Notifications">
              <Bell className="h-[18px] w-[18px]" />
              <span className="absolute right-2 top-2 h-2 w-2 rounded-full bg-primary" />
            </button>
          </div>
        </header>
        <main className="flex-1 overflow-y-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
