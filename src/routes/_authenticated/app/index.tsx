import { createFileRoute, getRouteApi, Link } from "@tanstack/react-router";
import {
  AlertTriangle,
  ArrowUpRight,
  CalendarClock,
  CheckCircle2,
  ClipboardCheck,
  Clock3,
  FileText,
  Plus,
  ShieldAlert,
  Target,
} from "lucide-react";

const parent = getRouteApi("/_authenticated/app");

export const Route = createFileRoute("/_authenticated/app/")({
  component: Dashboard,
});

const KPIS = [
  { label: "Documents publiés", value: "48", delta: "+6 ce mois", icon: FileText },
  { label: "Non-conformités ouvertes", value: "7", delta: "2 critiques", icon: ShieldAlert },
  { label: "Actions en cours", value: "23", delta: "68 % dans les délais", icon: Target },
  { label: "Audits planifiés", value: "4", delta: "Prochain : 14 oct.", icon: ClipboardCheck },
];

const PRIORITIES = [
  { title: "NC-024 — Écart d'étalonnage balance", meta: "Critique · Atelier Cotonou", tone: "danger" },
  { title: "Procédure achats à approuver", meta: "En attente depuis 5 jours", tone: "warn" },
  { title: "Audit interne ISO 9001", meta: "Dans 6 jours · Siège", tone: "info" },
];

const MONTHS = [
  { m: "Mai", v: 62 }, { m: "Juin", v: 70 }, { m: "Juil.", v: 66 },
  { m: "Août", v: 74 }, { m: "Sept.", v: 81 }, { m: "Oct.", v: 86 },
];

const PROCESSES = [
  { name: "Management", v: 92 }, { name: "Production", v: 78 },
  { name: "Achats", v: 64 }, { name: "Ressources humaines", v: 85 },
];

const ACTIVITY = [
  { text: "Manuel qualité v3 publié", who: "Elton", when: "Il y a 2 h", icon: CheckCircle2 },
  { text: "NC-024 déclarée", who: "Atelier", when: "Hier", icon: AlertTriangle },
  { text: "Audit fournisseurs planifié", who: "Elton", when: "Il y a 2 jours", icon: CalendarClock },
];

function Dashboard() {
  const profile = parent.useLoaderData();
  const pending = profile.status !== "active";

  return (
    <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Tableau de bord</p>
          <h1 className="mt-1 font-display text-2xl font-extrabold text-foreground md:text-3xl">
            Bonjour {profile.first_name || "et bienvenue"} 👋
          </h1>
          <p className="mt-1 text-sm text-muted-foreground">
            {profile.company_name ?? "Votre entreprise"} · Vue d'ensemble de votre système de management
          </p>
        </div>
        <div className="flex gap-2">
          <Link to="/app/$section" params={{ section: "non-conformites" }} className="inline-flex h-10 items-center gap-2 rounded-xl border border-border bg-card px-4 text-sm font-semibold text-foreground hover:border-primary hover:text-primary">
            <Plus className="h-4 w-4" /> Déclarer une NC
          </Link>
          <Link to="/app/$section" params={{ section: "documents" }} className="inline-flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/25 hover:bg-primary-dark">
            <Plus className="h-4 w-4" /> Nouveau document
          </Link>
        </div>
      </div>

      {pending && (
        <div className="flex items-start gap-3 rounded-2xl border border-primary/30 bg-primary-soft/60 p-4 text-sm">
          <Clock3 className="mt-0.5 h-5 w-5 text-primary" />
          <p className="text-foreground">Votre dossier est en cours de validation (24-48h). Certaines fonctions restent limitées.</p>
        </div>
      )}

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {KPIS.map((k) => (
          <div key={k.label} className="rounded-2xl border border-border bg-card p-5">
            <div className="flex items-center justify-between">
              <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-soft text-primary">
                <k.icon className="h-5 w-5" />
              </span>
              <ArrowUpRight className="h-4 w-4 text-muted-foreground" />
            </div>
            <p className="mt-4 font-display text-3xl font-extrabold text-foreground">{k.value}</p>
            <p className="text-sm font-semibold text-foreground">{k.label}</p>
            <p className="mt-0.5 text-xs text-muted-foreground">{k.delta}</p>
          </div>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <section className="rounded-2xl border border-border bg-card p-6 lg:col-span-2">
          <div className="flex items-center justify-between">
            <h2 className="font-display text-base font-bold text-foreground">Performance mensuelle</h2>
            <span className="text-xs font-semibold text-muted-foreground">Taux de conformité (%)</span>
          </div>
          <div className="mt-6 flex h-48 items-end gap-3">
            {MONTHS.map((d, i) => (
              <div key={d.m} className="flex flex-1 flex-col items-center gap-2">
                <span className="text-xs font-bold text-foreground">{d.v}</span>
                <div
                  className={`w-full rounded-t-lg ${i === MONTHS.length - 1 ? "bg-primary" : "bg-primary/25"}`}
                  style={{ height: `${d.v * 1.6}px` }}
                />
                <span className="text-xs text-muted-foreground">{d.m}</span>
              </div>
            ))}
          </div>
        </section>

        <section className="rounded-2xl border border-border bg-card p-6">
          <h2 className="font-display text-base font-bold text-foreground">Priorités</h2>
          <ul className="mt-4 space-y-3">
            {PRIORITIES.map((p) => (
              <li key={p.title} className="flex gap-3 rounded-xl border border-border p-3">
                <span className={`mt-1 h-2.5 w-2.5 shrink-0 rounded-full ${p.tone === "danger" ? "bg-destructive" : p.tone === "warn" ? "bg-primary" : "bg-primary/40"}`} />
                <div>
                  <p className="text-sm font-semibold text-foreground">{p.title}</p>
                  <p className="text-xs text-muted-foreground">{p.meta}</p>
                </div>
              </li>
            ))}
          </ul>
        </section>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <section className="rounded-2xl border border-border bg-card p-6">
          <h2 className="font-display text-base font-bold text-foreground">Vue des processus</h2>
          <ul className="mt-5 space-y-4">
            {PROCESSES.map((p) => (
              <li key={p.name}>
                <div className="flex justify-between text-sm">
                  <span className="font-semibold text-foreground">{p.name}</span>
                  <span className="font-bold text-primary">{p.v} %</span>
                </div>
                <div className="mt-1.5 h-2 rounded-full bg-secondary">
                  <div className="h-2 rounded-full bg-primary" style={{ width: `${p.v}%` }} />
                </div>
              </li>
            ))}
          </ul>
        </section>
        <section className="rounded-2xl border border-border bg-card p-6">
          <h2 className="font-display text-base font-bold text-foreground">Activités récentes</h2>
          <ul className="mt-4 divide-y divide-border">
            {ACTIVITY.map((a) => (
              <li key={a.text} className="flex items-center gap-3 py-3">
                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-soft text-primary">
                  <a.icon className="h-4 w-4" />
                </span>
                <div className="flex-1">
                  <p className="text-sm font-semibold text-foreground">{a.text}</p>
                  <p className="text-xs text-muted-foreground">{a.who} · {a.when}</p>
                </div>
              </li>
            ))}
          </ul>
        </section>
      </div>
    </div>
  );
}
