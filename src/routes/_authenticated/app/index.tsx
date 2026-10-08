import { createFileRoute, getRouteApi, Link } from "@tanstack/react-router";
import {
  AlertTriangle, ArrowUpRight, ClipboardCheck, Clock3, FileText, GitBranch, MapPin, Plus, ShieldAlert, Target, Users,
} from "lucide-react";
import { useMemo } from "react";
import { isOverdue, useRecords, type QRecord } from "@/hooks/use-records";
import { useCurrentSite } from "@/hooks/use-workspace";
import { useCollaboratorActions, useDashboardStats } from "@/integrations/backend/dashboard";
import { KINDS, sectionForKind } from "@/components/app/sections";
import { StatusBadge } from "@/components/app/SectionView";

const parent = getRouteApi("/_authenticated/app");

export const Route = createFileRoute("/_authenticated/app/")({
  component: Dashboard,
});

const CLOSED = ["Terminée", "Vérifiée", "Clôturée", "Archivé", "Atteint", "Maîtrisé"];
const QUICK = [
  { label: "Document", section: "documents" }, { label: "Non-conformité", section: "non-conformites" },
  { label: "Action", section: "actions" }, { label: "Audit", section: "audits" },
  { label: "Collaborateur", section: "collaborateurs" }, { label: "Processus", section: "processus" },
  { label: "Indicateur", section: "indicateurs" },
];

function Dashboard() {
  const profile = parent.useLoaderData();
  const [currentSite] = useCurrentSite();
  const { data: records = [], isLoading: recordsLoading } = useRecords();
  const {
    data: dashboard,
    isLoading: dashboardLoading,
    isError: dashboardError,
  } = useDashboardStats({
    site_id: currentSite || undefined,
    scope: currentSite ? "site" : "enterprise",
  });
  const { data: collaboratorSummary } = useCollaboratorActions({
    site_id: currentSite || undefined,
    scope: currentSite ? "site" : "enterprise",
    per_page: 20,
  });
  const of = (k: string) => records.filter((r) => r.kind === k);

  const today = new Date(new Date().toDateString());
  const in30 = new Date(today.getTime() + 30 * 864e5);

  const serverStats = dashboard?.stats;
  const kpis = [
    { label: "Sites actifs", value: serverStats?.total_sites ?? 0, icon: MapPin, section: "sites" },
    { label: "Utilisateurs", value: serverStats?.total_users ?? 0, icon: Users, section: "collaborateurs" },
    { label: "Actions en retard", value: serverStats?.overdue_actions ?? 0, icon: Target, section: "actions", alert: true },
    { label: "NC ouvertes", value: serverStats?.active_non_conformities ?? 0, icon: ShieldAlert, section: "non-conformites", alert: true },
    { label: "Audits à venir", value: serverStats?.upcoming_audits ?? 0, icon: ClipboardCheck, section: "audits" },
    { label: "Processus actifs", value: serverStats?.total_processes ?? 0, icon: GitBranch, section: "processus" },
  ];

  const priorities: QRecord[] = [
    ...of("action").filter(isOverdue),
    ...of("nc").filter((r) => r.data["severity"] === "Critique" && r.status !== "Clôturée"),
    ...of("audit").filter((r) => r.data["date"] && new Date(String(r.data["date"])) >= today && new Date(String(r.data["date"])) <= in30 && r.status !== "Clôturé"),
    ...of("document").filter((r) => r.status === "En approbation" || r.status === "En vérification" || isOverdue(r)),
    ...of("objective").filter((r) => r.status === "En cours" && Number(r.data["progress"] ?? 0) < 50),
  ].slice(0, 8);

  // Évolution de l'activité fournie par le dashboard Laravel.
  // Le calcul reste côté backend afin que les agrégats respectent le périmètre
  // entreprise/site et les règles d'accès du compte connecté.
  const months = useMemo(() => {
    return (dashboard?.charts.activity ?? []).map((item, index) => ({
      key: `${item.label}-${index}`,
      label: item.label,
      created: item.documents,
      closed: item.actions,
    }));
  }, [dashboard]);
  const maxBar = Math.max(1, ...months.flatMap((m) => [m.created, m.closed]));

  const distribution = [
    { label: "Documents", n: dashboard?.charts.distribution.documents ?? 0, section: "documents" },
    { label: "Non-conformités", n: dashboard?.charts.distribution.nc ?? 0, section: "non-conformites" },
    { label: "Audits", n: dashboard?.charts.distribution.audits ?? 0, section: "audits" },
    { label: "Actions", n: dashboard?.charts.distribution.actions ?? 0, section: "actions" },
  ];
  const totalDist = Math.max(1, distribution.reduce((s, d) => s + d.n, 0));

  // Actions by process
  const byProcess = of("process").map((p) => {
    const acts = of("action").filter((a) => a.data["process_id"] === p.id);
    const done = acts.filter((a) => CLOSED.includes(a.status)).length;
    return { p, total: acts.length, done, late: acts.filter(isOverdue).length, rate: acts.length ? Math.round((done / acts.length) * 100) : 0 };
  });

  // Governance maturity
  const docOf = (type: string) => of("document").find((d) => d.data["type"] === type);
  const policyStatus = dashboard?.leadership.policy_status && dashboard.leadership.policy_status !== "not_created"
    ? dashboard.leadership.policy_status
    : undefined;
  const governance = [
    { label: "Politique QHSE", rec: of("policy")[0] ?? docOf("Politique"), externalStatus: policyStatus },
    { label: "Manuel qualité", rec: docOf("Manuel"), externalStatus: undefined },
    { label: "Périmètre du système", rec: of("scope")[0], externalStatus: undefined },
    { label: "Revue de direction", rec: of("review")[0], externalStatus: undefined },
    { label: "Objectifs stratégiques", rec: of("objective")[0], externalStatus: undefined },
  ];

  const indicators = of("indicator").filter((i) => i.data["target"]);
  const recent: QRecord[] = (dashboard?.recent_activities ?? []).map((activity) => ({
    id: String(activity.id),
    kind: activity.type === "audit" ? "audit" : "action",
    reference: `${activity.type === "audit" ? "AUD" : "ACT"}-${activity.id}`,
    title: activity.name,
    status: "",
    data: {},
    created_at: activity.created_at,
    updated_at: activity.created_at,
  }));
  const name = profile.first_name || "et bienvenue";
  const isLoading = recordsLoading || dashboardLoading;

  if (isLoading) return <DashboardSkeleton />;

  return (
    <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Tableau de bord</p>
          <h1 className="mt-1 font-display text-2xl font-extrabold text-foreground md:text-3xl">Bonjour {name}</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            {profile.company_name ?? "Votre entreprise"} · Tous les sites · {new Date().toLocaleDateString("fr-FR", { weekday: "long", day: "numeric", month: "long", year: "numeric" })}
          </p>
        </div>
      </div>

      {profile.status !== "active" && (
        <div className="flex items-start gap-3 rounded-2xl border border-primary/30 bg-primary-soft/60 p-4 text-sm">
          <Clock3 className="mt-0.5 h-5 w-5 text-primary" />
          <p className="text-foreground">Votre dossier est en cours de validation (24-48h). Certaines fonctions restent limitées.</p>
        </div>
      )}

      {dashboardError && (
        <div className="flex items-start gap-3 rounded-2xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
          <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0" />
          <p>Les agrégats du tableau de bord Laravel n’ont pas pu être chargés. Vérifiez la connexion au backend pour afficher les indicateurs à jour.</p>
        </div>
      )}

      {!isLoading && records.length === 0 && (
        <div className="rounded-2xl border border-primary/30 bg-primary-soft/50 p-5">
          <p className="font-display font-bold text-foreground">Bienvenue dans votre espace</p>
          <p className="mt-1 text-sm text-muted-foreground">Commencez par créer vos sites, vos collaborateurs puis vos processus : tout le tableau de bord se remplira automatiquement.</p>
          <div className="mt-3 flex flex-wrap gap-2">
            {([["1. Sites", "sites"], ["2. Collaborateurs", "collaborateurs"], ["3. Processus", "processus"]] as const).map(([l, s]) => (
              <Link key={s} to="/app/$section" params={{ section: s }} search={{ new: 1 }} className="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">{l}</Link>
            ))}
          </div>
        </div>
      )}

      {/* KPI cards */}
      <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        {kpis.map((k) => (
          <Link key={k.label} to="/app/$section" params={{ section: k.section }} className="group rounded-2xl border border-border bg-card p-4 transition-all hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/10">
            <div className="flex items-center justify-between">
              <span className={`flex h-9 w-9 items-center justify-center rounded-xl ${k.alert && k.value > 0 ? "bg-destructive/10 text-destructive" : "bg-primary-soft text-primary"}`}>
                <k.icon className="h-[18px] w-[18px]" />
              </span>
              <ArrowUpRight className="h-4 w-4 text-muted-foreground transition-colors group-hover:text-primary" />
            </div>
            <p className="mt-3 font-display text-2xl font-extrabold text-foreground">{k.value}</p>
            <p className="text-xs font-semibold text-muted-foreground">{k.label}</p>
          </Link>
        ))}
      </div>

      {/* Quick actions */}
      <section className="rounded-2xl border border-border bg-card p-5">
        <h2 className="font-display text-base font-bold text-foreground">Actions rapides</h2>
        <div className="mt-3 flex flex-wrap gap-2">
          {QUICK.map((q) => (
            <Link key={q.section} to="/app/$section" params={{ section: q.section }} search={{ new: 1 }} className="inline-flex h-10 items-center gap-1.5 rounded-xl border border-border px-3.5 text-sm font-semibold text-foreground hover:border-primary hover:text-primary">
              <Plus className="h-4 w-4" /> {q.label}
            </Link>
          ))}
        </div>
      </section>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Priorities */}
        <section className="rounded-2xl border border-border bg-card p-6 lg:col-span-2">
          <h2 className="font-display text-base font-bold text-foreground">Priorités</h2>
          {collaboratorSummary && (collaboratorSummary.stats.overdue_count > 0 || collaboratorSummary.stats.open_count > 0) && (
            <div className="mt-4 flex flex-wrap gap-2">
              {collaboratorSummary.stats.overdue_count > 0 && <Link to="/app/$section" params={{ section: "mes-actions" }} className="rounded-xl bg-destructive/10 px-3 py-2 text-xs font-bold text-destructive">{collaboratorSummary.stats.overdue_count} action(s) en retard dans Mes actions</Link>}
              {collaboratorSummary.stats.open_count > 0 && <Link to="/app/$section" params={{ section: "mes-actions" }} className="rounded-xl bg-primary-soft px-3 py-2 text-xs font-bold text-primary">{collaboratorSummary.stats.open_count} action(s) à mener</Link>}
            </div>
          )}
          {priorities.length === 0 ? (
            <p className="mt-4 rounded-xl bg-background p-4 text-sm text-muted-foreground">Aucune priorité : pas d'action en retard, de NC critique, d'audit proche ni de document à valider.</p>
          ) : (
            <ul className="mt-4 space-y-2">
              {priorities.map((p) => (
                <li key={p.id}>
                  <Link to="/app/$section" params={{ section: sectionForKind(p.kind)! }} search={{ open: p.id }} className="flex items-center gap-3 rounded-xl border border-border p-3 hover:border-primary">
                    {isOverdue(p) || p.data["severity"] === "Critique" ? <AlertTriangle className="h-4 w-4 shrink-0 text-destructive" /> : <Clock3 className="h-4 w-4 shrink-0 text-primary" />}
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-semibold text-foreground">{p.reference} · {p.title}</p>
                      <p className="text-xs text-muted-foreground">
                        {KINDS[p.kind]?.singular}
                        {(p.data["due_date"] || p.data["date"]) ? ` · échéance ${new Date(String(p.data["due_date"] || p.data["date"])).toLocaleDateString("fr-FR")}` : ""}
                      </p>
                    </div>
                    <StatusBadge kind={p.kind} status={p.status} />
                  </Link>
                </li>
              ))}
            </ul>
          )}
        </section>

        {/* Governance */}
        <section className="rounded-2xl border border-border bg-card p-6">
          <h2 className="font-display text-base font-bold text-foreground">Pilotage de la direction</h2>
          <ul className="mt-4 space-y-2.5">
            {governance.map((g) => (
              <li key={g.label} className="flex items-center justify-between gap-2 text-sm">
                <span className="font-semibold text-foreground">{g.label}</span>
                {g.rec ? <StatusBadge kind={g.rec.kind} status={g.rec.status} /> : g.externalStatus ? <span className="rounded-full bg-primary-soft px-2.5 py-0.5 text-[11px] font-bold text-primary">{g.externalStatus}</span> : <span className="rounded-full bg-destructive/10 px-2.5 py-0.5 text-[11px] font-bold text-destructive">Non créé</span>}
              </li>
            ))}
          </ul>
        </section>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Evolution */}
        <section className="rounded-2xl border border-border bg-card p-6 lg:col-span-2">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h2 className="font-display text-base font-bold text-foreground">Évolution de l'activité</h2>
            <div className="flex gap-3 text-xs font-semibold text-muted-foreground">
              <span className="flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-sm bg-primary/30" /> Documents</span>
              <span className="flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-sm bg-primary" /> Actions</span>
            </div>
          </div>
          <p className="text-xs text-muted-foreground">Période fournie par le backend · périmètre entreprise/site appliqué</p>
          <div className="mt-5 flex h-44 items-end gap-2 sm:gap-4">
            {months.map((m) => (
              <div key={m.key} className="flex flex-1 flex-col items-center gap-2">
                <div className="flex h-36 w-full items-end justify-center gap-1">
                  <div className="w-1/2 max-w-6 rounded-t-md bg-primary/30" style={{ height: `${(m.created / maxBar) * 100}%`, minHeight: m.created ? 4 : 0 }} title={`${m.created} documents`} />
                  <div className="w-1/2 max-w-6 rounded-t-md bg-primary" style={{ height: `${(m.closed / maxBar) * 100}%`, minHeight: m.closed ? 4 : 0 }} title={`${m.closed} actions`} />
                </div>
                <span className="text-xs capitalize text-muted-foreground">{m.label}</span>
              </div>
            ))}
          </div>
        </section>

        {/* Distribution */}
        <section className="rounded-2xl border border-border bg-card p-6">
          <h2 className="font-display text-base font-bold text-foreground">Répartition des objets qualité</h2>
          <ul className="mt-4 space-y-3">
            {distribution.map((d) => (
              <li key={d.label}>
                <Link to="/app/$section" params={{ section: d.section }} className="block">
                  <div className="flex justify-between text-sm"><span className="font-semibold text-foreground">{d.label}</span><span className="font-bold text-primary">{d.n}</span></div>
                  <div className="mt-1.5 h-2 rounded-full bg-secondary"><div className="h-2 rounded-full bg-primary" style={{ width: `${(d.n / totalDist) * 100}%` }} /></div>
                </Link>
              </li>
            ))}
          </ul>
        </section>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Processes / actions by process */}
        <section className="rounded-2xl border border-border bg-card p-6">
          <div className="flex items-center justify-between">
            <h2 className="font-display text-base font-bold text-foreground">Actions par processus</h2>
            <Link to="/app/$section" params={{ section: "processus" }} className="text-xs font-bold text-primary">Cartographie</Link>
          </div>
          <p className="text-xs text-muted-foreground">
            {of("process").length} processus · {["Management", "Réalisation", "Support"].map((t) => `${of("process").filter((p) => p.data["type"] === t).length} ${t.toLowerCase()}`).join(" · ")}
          </p>
          {byProcess.length === 0 ? (
            <p className="mt-4 rounded-xl bg-background p-4 text-sm text-muted-foreground">Aucun processus créé.</p>
          ) : (
            <ul className="mt-4 space-y-4">
              {byProcess.map(({ p, total, done, late, rate }) => (
                <li key={p.id}>
                  <Link to="/app/$section" params={{ section: "processus" }} search={{ open: p.id }} className="block">
                    <div className="flex justify-between text-sm">
                      <span className="font-semibold text-foreground">{p.title}</span>
                      <span className="text-xs text-muted-foreground">{done}/{total} terminées{late ? ` · ${late} en retard` : ""} · <b className="text-primary">{rate} %</b></span>
                    </div>
                    <div className="mt-1.5 h-2 rounded-full bg-secondary"><div className="h-2 rounded-full bg-primary" style={{ width: `${rate}%` }} /></div>
                  </Link>
                </li>
              ))}
            </ul>
          )}
        </section>

        {/* Indicators performance */}
        <section className="rounded-2xl border border-border bg-card p-6">
          <h2 className="font-display text-base font-bold text-foreground">Performance des indicateurs</h2>
          {indicators.length === 0 ? (
            <p className="mt-4 rounded-xl bg-background p-4 text-sm text-muted-foreground">Aucun indicateur avec cible. Ajoutez-en dans « Indicateurs ».</p>
          ) : (
            <ul className="mt-4 space-y-4">
              {indicators.slice(0, 6).map((i) => {
                const rate = Math.round((Number(i.data["value"] ?? 0) / Number(i.data["target"])) * 100);
                return (
                  <li key={i.id}>
                    <div className="flex justify-between text-sm">
                      <span className="font-semibold text-foreground">{i.title}</span>
                      <span className={`font-bold ${rate >= 100 ? "text-primary" : "text-destructive"}`}>{String(i.data["value"] ?? 0)} / {String(i.data["target"] ?? "")} {String(i.data["unit"] ?? "")}</span>
                    </div>
                    <div className="mt-1.5 h-2 rounded-full bg-secondary"><div className={`h-2 rounded-full ${rate >= 100 ? "bg-primary" : "bg-destructive/70"}`} style={{ width: `${Math.min(100, rate)}%` }} /></div>
                  </li>
                );
              })}
            </ul>
          )}
        </section>
      </div>

      {/* Recent activity */}
      <section className="rounded-2xl border border-border bg-card p-6">
        <h2 className="font-display text-base font-bold text-foreground">Activités récentes</h2>
        {recent.length === 0 ? (
          <p className="mt-4 text-sm text-muted-foreground">Aucune activité pour l'instant.</p>
        ) : (
          <ul className="mt-3 divide-y divide-border">
            {recent.map((r) => (
              <li key={r.id}>
                <Link to="/app/$section" params={{ section: sectionForKind(r.kind)! }} search={{ open: r.id }} className="flex items-center gap-3 py-3">
                  <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary"><FileText className="h-4 w-4" /></span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-foreground">{KINDS[r.kind]?.singular} {r.reference} · {r.title}</p>
                    <p className="text-xs text-muted-foreground">{r.created_at === r.updated_at ? "Créé" : "Mis à jour"} le {new Date(r.updated_at).toLocaleString("fr-FR", { dateStyle: "short", timeStyle: "short" })}</p>
                  </div>
                  <StatusBadge kind={r.kind} status={r.status} />
                </Link>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}

function DashboardSkeleton() {
  return (
    <div
      className="mx-auto max-w-7xl space-y-6 p-4 md:p-8"
      aria-busy="true"
      aria-label="Chargement du tableau de bord"
    >
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div className="space-y-3">
          <div className="h-3 w-28 animate-pulse rounded-full bg-primary/15" />
          <div className="h-9 w-64 animate-pulse rounded-xl bg-secondary" />
          <div className="h-4 w-80 max-w-full animate-pulse rounded-full bg-secondary" />
        </div>
        <div className="h-10 w-36 animate-pulse rounded-xl bg-secondary" />
      </div>

      <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        {Array.from({ length: 6 }, (_, index) => (
          <div key={index} className="rounded-2xl border border-border bg-card p-4 shadow-sm">
            <div className="flex items-center justify-between">
              <div className="h-9 w-9 animate-pulse rounded-xl bg-primary/10" />
              <div className="h-4 w-4 animate-pulse rounded-full bg-secondary" />
            </div>
            <div className="mt-4 h-8 w-14 animate-pulse rounded-lg bg-secondary" />
            <div className="mt-2 h-3 w-24 animate-pulse rounded-full bg-secondary" />
          </div>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="min-h-[280px] rounded-2xl border border-border bg-card p-6 shadow-sm lg:col-span-2">
          <div className="h-5 w-28 animate-pulse rounded-full bg-secondary" />
          <div className="mt-2 h-3 w-56 animate-pulse rounded-full bg-secondary" />
          <div className="mt-8 flex h-40 items-end gap-3 sm:gap-5">
            {[42, 70, 54, 86, 62, 78, 48, 92].map((height, index) => (
              <div key={index} className="flex h-full flex-1 items-end">
                <div className="w-full animate-pulse rounded-t-xl bg-primary/20" style={{ height: `${height}%` }} />
              </div>
            ))}
          </div>
        </div>
        <div className="min-h-[280px] rounded-2xl border border-border bg-card p-6 shadow-sm">
          <div className="h-5 w-44 animate-pulse rounded-full bg-secondary" />
          <div className="mt-6 space-y-5">
            {["w-full", "w-4/5", "w-11/12", "w-2/3"].map((width, index) => (
              <div key={index} className="space-y-2">
                <div className={`h-3 ${width} animate-pulse rounded-full bg-secondary`} />
                <div className="h-2 w-full animate-pulse rounded-full bg-primary/15" />
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {[0, 1].map((panel) => (
          <div key={panel} className="min-h-[190px] rounded-2xl border border-border bg-card p-6 shadow-sm">
            <div className="h-5 w-44 animate-pulse rounded-full bg-secondary" />
            <div className="mt-6 space-y-4">
              {[0, 1, 2].map((row) => (
                <div key={row} className="flex items-center gap-3">
                  <div className="h-9 w-9 animate-pulse rounded-xl bg-primary/10" />
                  <div className="flex-1 space-y-2"><div className="h-3 w-3/4 animate-pulse rounded-full bg-secondary" /><div className="h-2 w-1/2 animate-pulse rounded-full bg-secondary" /></div>
                  <div className="h-5 w-14 animate-pulse rounded-full bg-primary/10" />
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
