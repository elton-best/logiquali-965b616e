import { Link, useRouterState } from "@tanstack/react-router";
import { ChevronDown, ChevronLeft, ChevronRight, LogOut } from "lucide-react";
import { useState } from "react";
import logoAsset from "@/assets/bestqhse-logo.png.asset.json";
import { BOTTOM_ITEMS, DASHBOARD_ICON, NAV_GROUPS, type NavItem } from "./nav";

type Props = {
  collapsed: boolean;
  onToggle: () => void;
  name: string;
  email: string;
  company: string;
  onSignOut: () => void;
  onNavigate?: () => void;
};

export function AppSidebar({ collapsed, onToggle, name, email, company, onSignOut, onNavigate }: Props) {
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  const activeSlug = pathname.startsWith("/app/") ? pathname.slice(5) : "";
  const [open, setOpen] = useState<Record<string, boolean>>(() =>
    Object.fromEntries(NAV_GROUPS.map((g) => [g.id, true]))
  );
  const Dash = DASHBOARD_ICON;
  const initials = (name || email).split(/\s+/).map((p) => p[0]).join("").slice(0, 2).toUpperCase();

  const itemLink = (it: NavItem, nested: boolean) => {
    const active = activeSlug === it.slug;
    return (
      <Link
        key={it.slug}
        to="/app/$section"
        params={{ section: it.slug }}
        onClick={onNavigate}
        title={collapsed ? it.label : undefined}
        className={`group flex items-center gap-3 rounded-xl text-[13.5px] font-semibold transition-colors ${
          collapsed ? "mx-auto h-10 w-10 justify-center" : `h-9 px-3 ${nested ? "ml-1" : ""}`
        } ${
          active
            ? "bg-primary-soft text-primary"
            : "text-muted-foreground hover:bg-secondary hover:text-foreground"
        }`}
      >
        <it.icon className="h-[18px] w-[18px] shrink-0" />
        {!collapsed && <span className="flex-1 truncate">{it.label}</span>}
        {!collapsed && it.badge && (
          <span className="rounded-full bg-primary px-1.5 text-[10px] font-bold leading-4 text-primary-foreground">
            {it.badge}
          </span>
        )}
      </Link>
    );
  };

  return (
    <div className="flex h-full flex-col bg-card">
      {/* Brand */}
      <div className={`flex items-center border-b border-border py-4 ${collapsed ? "flex-col gap-3 px-2" : "gap-3 px-4"}`}>
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-border bg-card">
          <img src={logoAsset.url} alt="LOGIQUALI" className="h-6 w-auto" />
        </span>
        {!collapsed && (
          <div className="min-w-0 flex-1">
            <p className="font-display text-[15px] font-extrabold leading-tight text-foreground">LOGIQUALI</p>
            <p className="truncate text-[11px] font-semibold text-muted-foreground">{company || "Espace entreprise"}</p>
          </div>
        )}
        <button
          onClick={onToggle}
          aria-label={collapsed ? "Déplier le menu" : "Replier le menu"}
          className="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-primary hover:text-primary lg:flex"
        >
          {collapsed ? <ChevronRight className="h-4 w-4" /> : <ChevronLeft className="h-4 w-4" />}
        </button>
      </div>

      {/* Nav */}
      <nav className="flex-1 space-y-1.5 overflow-y-auto px-3 py-4">
        <Link
          to="/app"
          onClick={onNavigate}
          title={collapsed ? "Tableau de bord" : undefined}
          className={`flex items-center gap-3 rounded-xl text-sm font-bold transition-all ${
            collapsed ? "mx-auto h-10 w-10 justify-center" : "h-11 px-3.5"
          } ${
            activeSlug === "" && pathname.startsWith("/app")
              ? "bg-primary text-primary-foreground shadow-lg shadow-primary/30"
              : "text-foreground hover:bg-secondary"
          }`}
        >
          <Dash className="h-[18px] w-[18px] shrink-0" />
          {!collapsed && "Tableau de bord"}
        </Link>

        {NAV_GROUPS.map((g) =>
          collapsed ? (
            <div key={g.id} className="space-y-1 border-t border-border pt-2">
              {g.items.map((it) => itemLink(it, false))}
            </div>
          ) : (
            <div key={g.id} className="pt-1">
              <button
                onClick={() => setOpen((o) => ({ ...o, [g.id]: !o[g.id] }))}
                className="flex w-full items-center gap-3 rounded-xl border border-border bg-background px-3.5 py-2.5 text-left text-[13.5px] font-bold text-foreground transition-colors hover:border-primary/40"
              >
                <g.icon className="h-[18px] w-[18px] shrink-0 text-primary" />
                <span className="flex-1">{g.label}</span>
                <ChevronDown className={`h-4 w-4 text-muted-foreground transition-transform ${open[g.id] ? "rotate-180" : ""}`} />
              </button>
              <div className="grid transition-all duration-300" style={{ gridTemplateRows: open[g.id] ? "1fr" : "0fr" }}>
                <div className="overflow-hidden">
                  <div className="ml-5 mt-1 space-y-0.5 border-l-2 border-primary/15 pl-2">
                    {g.items.map((it) => itemLink(it, true))}
                  </div>
                </div>
              </div>
            </div>
          )
        )}

        <div className={`space-y-1 border-t border-border pt-3 ${collapsed ? "" : "mt-2"}`}>
          {BOTTOM_ITEMS.map((it) => itemLink(it, false))}
        </div>
      </nav>

      {/* User */}
      <div className={`flex items-center border-t border-border p-3 ${collapsed ? "flex-col gap-2" : "gap-3"}`}>
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary font-display text-sm font-bold text-primary-foreground">
          {initials}
        </span>
        {!collapsed && (
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-bold text-foreground">{name || "Administrateur"}</p>
            <p className="truncate text-xs text-muted-foreground">{email}</p>
          </div>
        )}
        <button
          onClick={onSignOut}
          aria-label="Se déconnecter"
          title="Se déconnecter"
          className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-destructive hover:text-destructive"
        >
          <LogOut className="h-4 w-4" />
        </button>
      </div>
    </div>
  );
}
