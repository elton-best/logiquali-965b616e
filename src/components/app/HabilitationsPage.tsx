import { Link } from "@tanstack/react-router";
import { Download, Plus, RefreshCw, Search, ShieldCheck } from "lucide-react";
import { useMemo, useState } from "react";
import { toast } from "sonner";
import {
  downloadHabilitationsExport,
  fetchHabilitationsExpiring,
  useHabilitationMutations,
  useHabilitations,
  useHabilitationsStats,
} from "@/integrations/backend/hse";

const fmt = (d: string) => new Date(d).toLocaleDateString("fr-FR");

type Hab = Record<string, unknown> & {
  id?: string | number;
  title?: string;
  name?: string;
  type?: string;
  status?: string;
  expiry_date?: string;
  issued_date?: string;
  certificate_number?: string;
  issuing_authority?: string;
  user?: { name?: string; email?: string } | null;
};

function str(v: unknown): string {
  return v == null ? "" : String(v);
}

function daysUntil(expiry?: string): number | null {
  if (!expiry) return null;
  const date = new Date(expiry);
  if (Number.isNaN(date.getTime())) return null;
  return Math.ceil((date.getTime() - new Date(new Date().toDateString()).getTime()) / 86_400_000);
}

function statusLabel(h: Hab, days: number | null): string {
  if (str(h.status) === "expired" || (days !== null && days < 0)) return "Expirée";
  if (days !== null && days <= 30) return "Expire bientôt";
  return "Active";
}

function RenewDialog({
  hab,
  onClose,
}: {
  hab: Hab;
  onClose: () => void;
}) {
  const { renew } = useHabilitationMutations();
  const [date, setDate] = useState("");
  const [certificateNumber, setCertificateNumber] = useState(str(hab.certificate_number));
  const [authority, setAuthority] = useState(str(hab.issuing_authority));
  const [notes, setNotes] = useState("");
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/40 p-4" onClick={onClose}>
      <div className="w-full max-w-md rounded-2xl bg-card p-6 shadow-xl" onClick={(e) => e.stopPropagation()}>
        <p className="font-display text-lg font-bold text-foreground">Renouveler l'habilitation</p>
        <p className="mt-1 text-sm text-muted-foreground">
          {str(hab.title ?? hab.name) || `HAB-${hab.id}`} — {hab.user?.name ?? "—"}
        </p>
        <label className="mt-4 block text-xs font-bold uppercase tracking-wider text-muted-foreground">
          Nouvelle date d'expiration *
          <input
            type="date"
            value={date}
            onChange={(e) => setDate(e.target.value)}
            className="mt-1 h-11 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground outline-none focus:border-primary"
          />
        </label>
        <label className="mt-3 block text-xs font-bold uppercase tracking-wider text-muted-foreground">
          N° certificat
          <input
            value={certificateNumber}
            onChange={(e) => setCertificateNumber(e.target.value)}
            className="mt-1 h-11 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground outline-none focus:border-primary"
          />
        </label>
        <label className="mt-3 block text-xs font-bold uppercase tracking-wider text-muted-foreground">
          Autorité émettrice
          <input
            value={authority}
            onChange={(e) => setAuthority(e.target.value)}
            className="mt-1 h-11 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground outline-none focus:border-primary"
          />
        </label>
        <label className="mt-3 block text-xs font-bold uppercase tracking-wider text-muted-foreground">
          Notes
          <input
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            className="mt-1 h-11 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground outline-none focus:border-primary"
          />
        </label>
        <div className="mt-5 flex justify-end gap-2">
          <button onClick={onClose} className="h-11 rounded-xl border border-border px-4 text-sm font-semibold">
            Annuler
          </button>
          <button
            disabled={renew.isPending}
            onClick={() => {
              if (!date) {
                toast.error("La nouvelle date d'expiration est requise.");
                return;
              }
              renew.mutate(
                {
                  id: hab.id as string | number,
                  payload: {
                    new_expiry_date: date,
                    certificate_number: certificateNumber || undefined,
                    issuing_authority: authority || undefined,
                    notes: notes || undefined,
                  },
                },
                {
                  onSuccess: () => {
                    toast.success("Habilitation renouvelée");
                    onClose();
                  },
                  onError: (error) =>
                    toast.error(error instanceof Error ? error.message : "Renouvellement impossible."),
                },
              );
            }}
            className="inline-flex h-11 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground disabled:opacity-50"
          >
            <RefreshCw className="h-4 w-4" /> {renew.isPending ? "Renouvellement…" : "Renouveler"}
          </button>
        </div>
      </div>
    </div>
  );
}

export function HabilitationsPage() {
  const { data: stats } = useHabilitationsStats();
  const { data: items = [], isLoading, isError } = useHabilitations({ per_page: 100 });
  const [filter, setFilter] = useState<"" | "active" | "expiring" | "expired">("");
  const [query, setQuery] = useState("");
  const [renewTarget, setRenewTarget] = useState<Hab | null>(null);

  const rows = useMemo(
    () =>
      (items as Hab[]).map((h) => {
        const days = daysUntil(h.expiry_date);
        return { hab: h, days, label: statusLabel(h, days) };
      }),
    [items],
  );

  const filtered = useMemo(
    () =>
      rows
        .filter(({ hab, days, label }) => {
          const matchesFilter =
            !filter ||
            (filter === "expired" ? label === "Expirée" : filter === "expiring" ? label === "Expire bientôt" : label === "Active");
          void days;
          const haystack = [
            hab.user?.name,
            hab.user?.email,
            hab.title,
            hab.name,
            hab.type,
            hab.certificate_number,
            hab.issuing_authority,
          ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();
          return matchesFilter && (!query || haystack.includes(query.toLowerCase()));
        })
        .sort((a, b) => (a.hab.expiry_date ?? "9999").localeCompare(b.hab.expiry_date ?? "9999")),
    [rows, filter, query],
  );

  const expiringCount = stats?.expiring_soon ?? rows.filter((r) => r.label === "Expire bientôt").length;

  const cards = [
    { key: "", label: "Total", value: stats?.total ?? rows.length, tone: "text-primary" },
    { key: "active", label: "Actives", value: stats?.active ?? 0, tone: "text-success" },
    { key: "expiring", label: "Expirent ≤ 30 j", value: expiringCount, tone: "text-warning" },
    { key: "expired", label: "Expirées", value: stats?.expired ?? 0, tone: "text-destructive" },
  ] as const;

  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <Link to="/app" className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Vue d'ensemble</Link>
          <h1 className="mt-1 font-display text-2xl font-extrabold text-foreground md:text-3xl">Habilitations</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Certifications et habilitations employés, expirations et renouvellements — depuis le backend Laravel.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => {
              const header = ["Employé", "Type", "Titre", "N° certificat", "Expiration", "Jours restants", "Statut"];
              downloadHabilitationsExport([
                header,
                ...filtered.map(({ hab, days, label }) => [
                  hab.user?.name ?? "—",
                  str(hab.type) || "—",
                  str(hab.title ?? hab.name) || "—",
                  str(hab.certificate_number) || "—",
                  hab.expiry_date ? fmt(hab.expiry_date) : "—",
                  days == null ? "—" : String(days),
                  label,
                ]),
              ]);
              toast.success("Export téléchargé");
            }}
            className="inline-flex h-11 items-center gap-2 rounded-xl border border-border bg-card px-4 text-sm font-semibold hover:border-primary"
          >
            <Download className="h-4 w-4" /> Exporter
          </button>
          <Link
            to="/app/$section"
            params={{ section: "habilitation" }}
            search={{ new: 1 }}
            className="inline-flex h-11 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground"
          >
            <Plus className="h-4 w-4" /> Nouvelle habilitation
          </Link>
        </div>
      </div>

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {cards.map((card) => (
          <button
            key={card.key}
            onClick={() => setFilter(card.key)}
            className={`rounded-2xl border bg-card p-4 text-left transition-all hover:-translate-y-0.5 hover:border-primary/50 ${filter === card.key ? "border-primary shadow-md" : "border-border"}`}
          >
            <div className="flex items-center justify-between gap-2">
              <span className="text-xs font-semibold text-muted-foreground">{card.label}</span>
              <span className={`text-2xl font-extrabold ${card.tone}`}>{card.value}</span>
            </div>
          </button>
        ))}
      </div>

      {expiringCount > 0 && (
        <p className="rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm text-warning">
          <ShieldCheck className="mr-2 inline h-4 w-4" />
          {expiringCount} habilitation(s) expirent dans les 30 prochains jours.
          <button onClick={() => setFilter("expiring")} className="ml-2 font-bold underline">Voir</button>
        </p>
      )}

      <div className="flex flex-wrap items-center gap-3 rounded-2xl border border-border bg-card p-4">
        <div className="relative min-w-[240px] flex-1">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-primary" />
          <input
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Rechercher par employé, type, titre, certificat…"
            className="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-4 text-sm outline-none focus:border-primary"
          />
        </div>
        <button
          onClick={() => void fetchHabilitationsExpiring(7).then((list) => toast.info(`${list.length} habilitation(s) critique(s) ≤ 7 j`))}
          className="h-11 rounded-xl border border-border px-4 text-sm font-semibold hover:border-primary"
        >
          Critiques ≤ 7 j
        </button>
      </div>

      {isError && (
        <p className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
          Impossible de charger les habilitations depuis le backend Laravel.
        </p>
      )}
      {isLoading ? (
        <div className="rounded-2xl border border-border bg-card p-6 text-sm text-muted-foreground" aria-busy="true">
          Chargement des habilitations…
        </div>
      ) : filtered.length === 0 ? (
        <div className="rounded-2xl border border-border bg-card p-10 text-center">
          <p className="font-display font-bold text-foreground">Aucune habilitation correspondante</p>
          <p className="mt-1 text-sm text-muted-foreground">Créez la première habilitation via le bouton ci-dessus.</p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-2xl border border-border bg-card">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[900px] text-sm">
              <thead className="border-b border-border bg-background text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">
                <tr>
                  <th className="px-4 py-3">Employé</th>
                  <th className="px-4 py-3">Type</th>
                  <th className="px-4 py-3">Titre</th>
                  <th className="px-4 py-3">Expiration</th>
                  <th className="px-4 py-3">Reste</th>
                  <th className="px-4 py-3">Statut</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {filtered.map(({ hab, days, label }) => (
                  <tr key={String(hab.id)} className="hover:bg-primary-soft/40">
                    <td className="px-4 py-3 font-semibold text-foreground">{hab.user?.name ?? "—"}</td>
                    <td className="px-4 py-3 text-muted-foreground">{str(hab.type) || "—"}</td>
                    <td className="px-4 py-3 text-muted-foreground">{str(hab.title ?? hab.name) || "—"}</td>
                    <td className={`px-4 py-3 ${label !== "Active" ? "font-bold text-destructive" : "text-muted-foreground"}`}>
                      {hab.expiry_date ? fmt(hab.expiry_date) : "—"}
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">
                      {days == null ? "—" : days < 0 ? `Dépassée de ${-days} j` : `${days} j`}
                    </td>
                    <td className="px-4 py-3">
                      <span className={`rounded-full px-2.5 py-1 text-xs font-bold ${label === "Active" ? "bg-success/10 text-success" : label === "Expire bientôt" ? "bg-warning/15 text-warning" : "bg-destructive/10 text-destructive"}`}>
                        {label}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right">
                      <button
                        onClick={() => setRenewTarget(hab)}
                        className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-border px-3 text-xs font-bold hover:border-primary"
                      >
                        <RefreshCw className="h-3.5 w-3.5" /> Renouveler
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {renewTarget && <RenewDialog hab={renewTarget} onClose={() => setRenewTarget(null)} />}
    </div>
  );
}
