import { createFileRoute, getRouteApi, Link } from "@tanstack/react-router";
import {
  AlertTriangle, ArrowUpRight, ClipboardCheck, Clock3, FileText, GitBranch, MapPin, Plus, ShieldAlert, Target, Users,
} from "lucide-react";
import { useMemo } from "react";
import { isOverdue, useRecords, type QRecord } from "@/hooks/use-records";
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

function monthKey(d: Date) { return `${d.getFullYear()}-${d.getMonth()}`; }

function Dashboard() {
  const profile = parent.useLoaderData();
  const { data: records = [], isLoading } = useRecords();
  const of = (k: string) => records.filter((r) => r.kind === k);

  const today = new Date(new Date().toDateString());
  const in30 = new Date(today.getTime() + 30 * 864e5);

  const kpis = [
    { label: "Sites actifs", value: of("site").filter((r) => r.status === "Actif").length, icon: MapPin, section: "sites" },
    { label: "Collaborateurs actifs", value: of("collaborator").filter((r) => r.status === "Actif").length, icon: Users, section: "collaborateurs" },
    { label: "Actions en retard", value: of("action").filter(isOverdue).length, icon: Target, section: "actions", alert: true },
    { label: "NC ouvertes", value: of("nc").filter((r) => r.status !== "Clôturée").length, icon: ShieldAlert, section: "non-conformites", alert: true },
    { label: "Audits à venir", value: of("audit").filter((r) => r.data["date"] && new Date(String(r.data["date"])) >= today && r.status !== "Clôturé").length, icon: ClipboardCheck, section: "audits" },
    { label: "Processus actifs", value: of("process").filter((r) => r.status === "Actif").length, icon: GitBranch, section: "processus" },
  ];

  const priorities: QRecord[] = [
    ...of("action").filter(isOverdue),
    ...of("nc").filter((r) => r.data["severity"] === "Critique" && r.status !== "Clôturée"),
    ...of("audit").filter((r) => r.data["date"] && new Date(String(r.data["date"])) >= today && new Date(String(r.data["date"])) <= in30 && r.status !== "Clôturé"),
    ...of("document").filter((r) => r.status === "En approbation" || r.status === "En vérification" || isOverdue(r)),
    ...of("objective").filter((r) => r.status === "En cours" && Number(r.data["progress"] ?? 0) < 50),
  ].slice(0, 8);

  // Activity evolution: last 6 months — created vs closed (actions + NC)
  const months = useMemo(() => {
    const arr: { key: string; label: string; created: number; closed: number }[] = [];
    for (let i = 5; i >= 0; i--) {
      const d = new Date(); d.setDate(1); d.setMonth(d.getMonth() - i);
      arr.push({ key: monthKey(d), label: d.toLocaleDateString("fr-FR", { month: "short" }), created: 0, closed: 0 });
    }
    for (const r of records) {
      if (!["action", "nc", "document", "audit"].includes(r.kind)) continue;
      const c = arr.find((m) => m.key === monthKey(new Date(r.created_at)));
      if (c) c.created++;
      if (CLOSED.includes(r.status) || r.status === "Publié" || r.status === "Clôturé") {
        const u = arr.find((m) => m.key === monthKey(new Date(r.updated_at)));
        if (u) u.closed++;
      }
    }
    return arr;
  }, [records]);
  const maxBar = Math.max(1, ...months.flatMap((m) => [m.created, m.closed]));

  const distribution = [
    { label: "Documents", n: of("document").length, section: "documents" },
    { label: "Non-conformités", n: of("nc").length, section: "non-conformites" },
    { label: "Audits", n: of("audit").length, section: "audits" },
    { label: "Actions", n: of("action").length, section: "actions" },
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
  const governance = [
    { label: "Politique QHSE", rec: docOf("Politique") },
    { label: "Manuel qualité", rec: docOf("Manuel") },
    { label: "Périmètre du système", rec: of("scope")[0] },
    { label: "Revue de direction", rec: of("review")[0] },
    { label: "Objectifs stratégiques", rec: of("objective")[0] },
  ];

  const indicators = of("indicator").filter((i) => i.data["target"]);
  const recent = [...records].sort((a, b) => b.updated_at.localeCompare(a.updated_at)).slice(0, 7);
  const name = profile.first_name || "et bienvenue";

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

      {!isLoading && records.length === 0 && (
        <div className="rounded-2xl border border-primary/30 bg-primary-soft/50 p-5">
          <p className="font-display font-bold text-foreground">Bienvenue dans votre espace</p>
          <p className="mt-1 text-sm text-muted-foreground">Commencez par créer vos sites, vos collaborateurs puis vos processus : tout le tableau de bord se remplira automatiquement.</p>
          <div className="mt-3 flex flex-wrap gap-2">
            {[["1. Sites", "sites"], ["2. Collaborateurs", "collaborateurs"], ["3. Processus", "processus"]].map(([l, s]) => (
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
                {g.rec ? <StatusBadge kind={g.rec.kind} status={g.rec.status} /> : <span className="rounded-full bg-destructive/10 px-2.5 py-0.5 text-[11px] font-bold text-destructive">Non créé</span>}
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
              <span className="flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-sm bg-primary/30" /> Créés</span>
              <span className="flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-sm bg-primary" /> Clôturés / publiés</span>
            </div>
          </div>
          <p className="text-xs text-muted-foreground">6 derniers mois · documents, NC, audits, actions</p>
          <div className="mt-5 flex h-44 items-end gap-2 sm:gap-4">
            {months.map((m) => (
              <div key={m.key} className="flex flex-1 flex-col items-center gap-2">
                <div className="flex h-36 w-full items-end justify-center gap-1">
                  <div className="w-1/2 max-w-6 rounded-t-md bg-primary/30" style={{ height: `${(m.created / maxBar) * 100}%`, minHeight: m.created ? 4 : 0 }} title={`${m.created} créés`} />
                  <div className="w-1/2 max-w-6 rounded-t-md bg-primary" style={{ height: `${(m.closed / maxBar) * 100}%`, minHeight: m.closed ? 4 : 0 }} title={`${m.closed} clôturés`} />
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
                      <span className={`font-bold ${rate >= 100 ? "text-primary" : "text-destructive"}`}>{i.data["value"] ?? 0} / {i.data["target"]} {i.data["unit"] ?? ""}</span>
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
