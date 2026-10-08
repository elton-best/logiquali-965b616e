import { Link, useNavigate } from "@tanstack/react-router";
import {
  AlertTriangle, Archive, Calendar, Download, History, Link2, MessageSquarePlus, Pencil, Plus, Search, Trash2, X,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import {
  KINDS, TONE_CLASS, availableActions, sectionForKind, toneOf,
  type Field, type KindConfig, type SectionConfig, type Transition,
} from "./sections";
import {
  historyOf, isOverdue, useAddNote, useDeleteRecord, useRecords, useSaveRecord, useSetStatus, useTransition, type QRecord,
} from "@/hooks/use-records";
import { computeWorkspace, useCurrentSite } from "@/hooks/use-workspace";
import { DoubleScroll } from "./DoubleScroll";
import { ColumnPicker, ExportPreview, useColumns } from "./Extras";

/** Which kinds can spawn which follow-up records from the detail sheet. */
const FOLLOW_UPS: Record<string, { label: string; section: string; kind?: string }[]> = {
  nc: [{ label: "Créer une action corrective", section: "actions" }],
  risk: [{ label: "Créer une action", section: "actions" }],
  objective: [{ label: "Créer une action", section: "actions" }, { label: "Créer l'indicateur de mesure", section: "indicateurs" }],
  audit: [{ label: "Déclarer une NC issue de l'audit", section: "non-conformites" }, { label: "Créer une action", section: "actions" }, { label: "Évaluer l'auditeur", section: "auditeurs" }],
  review: [{ label: "Créer une action issue de la revue", section: "actions" }],
  process_review: [{ label: "Créer une action", section: "actions" }],
  complaint: [{ label: "Déclarer une NC", section: "non-conformites" }, { label: "Créer une action", section: "actions" }],
  suggestion: [{ label: "Transformer en projet", section: "amelioration", kind: "improvement" }],
  improvement: [{ label: "Créer une action", section: "actions" }],
  supplier: [{ label: "Déclarer une NC fournisseur", section: "non-conformites" }],
  operation: [{ label: "Créer une libération", section: "liberation" }, { label: "Déclarer une sortie non conforme", section: "sorties-non-conformes" }],
  release: [{ label: "Déclarer une sortie non conforme", section: "sorties-non-conformes" }],
  nonconforming: [{ label: "Ouvrir une NC", section: "non-conformites" }],
  opcontrol: [{ label: "Ouvrir une NC", section: "non-conformites" }],
  env_aspect: [{ label: "Créer une action", section: "actions" }],
  hazard: [{ label: "Créer une action", section: "actions" }],
  incident: [{ label: "Ouvrir une NC", section: "non-conformites" }],
  energy_use: [{ label: "Créer une action", section: "actions" }],
  haccp: [{ label: "Créer une action", section: "actions" }],
  infosec: [{ label: "Créer une action", section: "actions" }],
  collaborator: [{ label: "Ajouter une formation", section: "competences", kind: "training" }, { label: "Ajouter une habilitation", section: "competences", kind: "habilitation" }],
  equipment: [{ label: "Signaler une NC", section: "non-conformites" }],
};

export function StatusBadge({ kind, status }: { kind: string; status: string }) {
  return (
    <span className={`inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-[11px] font-bold ${TONE_CLASS[toneOf(kind, status)]}`}>
      {status || "—"}
    </span>
  );
}

export function formatValue(f: Field, v: unknown, byId: Map<string, QRecord>) {
  if (v === null || v === undefined || v === "") return "—";
  if (f.type === "relation") {
    const r = byId.get(String(v));
    return r ? `${r.reference} · ${r.title}` : "—";
  }
  if (f.type === "date") return new Date(String(v)).toLocaleDateString("fr-FR");
  return String(v);
}

/** Prefill for a new record created from another one (origin). */
function prefillFrom(cfg: KindConfig, origin: QRecord | undefined): Record<string, string> | undefined {
  if (!origin) return undefined;
  const f = cfg.fields.find((x) => x.type === "relation" && x.kinds?.includes(origin.kind));
  const out: Record<string, string> = f ? { [f.key]: origin.id } : {};
  if (cfg.kind === "nc") {
    const map: Record<string, string> = { audit: "Audit", complaint: "Client", supplier: "Fournisseur" };
    if (map[origin.kind]) out["origin"] = map[origin.kind]!;
  }
  if (origin.data["process_id"] && cfg.fields.some((x) => x.key === "process_id")) out["process_id"] = String(origin.data["process_id"]);
  if (origin.data["site_id"] && cfg.fields.some((x) => x.key === "site_id")) out["site_id"] = String(origin.data["site_id"]);
  return out;
}

type Props = { section: SectionConfig; openId?: string | undefined; createNew?: boolean | undefined; newKind?: string | undefined; originId?: string | undefined };

export function SectionView({ section, openId, createNew, newKind, originId }: Props) {
  const navigate = useNavigate();
  const { data: records = [], isLoading } = useRecords();
  const [site, setSite] = useCurrentSite();
  const [tab, setTab] = useState(section.kinds[0].kind);
  const [query, setQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("");
  const [showArchived, setShowArchived] = useState(false);
  const [detailId, setDetailId] = useState<string | null>(null);
  const [editing, setEditing] = useState<{ cfg: KindConfig; record?: QRecord | undefined; prefill?: Record<string, string> | undefined } | null>(null);

  const cfg = KINDS[tab] ?? section.kinds[0];
  const byId = useMemo(() => new Map(records.map((r) => [r.id, r])), [records]);
  const siteRecord = site ? byId.get(site) : undefined;
  const hasSiteField = cfg.fields.some((f) => f.key === "site_id");

  useEffect(() => {
    setTab(section.kinds[0].kind);
    setStatusFilter("");
    setQuery("");
  }, [section.slug, section.kinds]);

  useEffect(() => {
    if (openId && byId.has(openId)) {
      setTab(byId.get(openId)!.kind);
      setDetailId(openId);
    }
  }, [openId, byId]);

  useEffect(() => {
    if (!createNew || isLoading) return;
    const target = (newKind && section.kinds.find((k) => k.kind === newKind)) || section.kinds[0];
    setTab(target.kind);
    setEditing({ cfg: target, prefill: prefillFrom(target, originId ? byId.get(originId) : undefined) });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [createNew, newKind, originId, isLoading]);

  const detail = detailId ? byId.get(detailId) ?? null : null;

  const allOfKind = records.filter((r) => r.kind === cfg.kind && (!hasSiteField || !siteRecord || r.data["site_id"] === site));
  const ORDER: Record<string, number> = { Management: 1, "Réalisation": 2, Support: 3 };
  if (cfg.kind === "process") allOfKind.sort((a, b) => (ORDER[String(a.data["type"])] ?? 9) - (ORDER[String(b.data["type"])] ?? 9));
  const list = allOfKind.filter(
    (r) =>
      (statusFilter ? r.status === statusFilter : showArchived || r.status !== "Archivé") &&
      (!query || `${r.reference} ${r.title}`.toLowerCase().includes(query.toLowerCase()))
  );
  const allColumns = cfg.fields.filter((f) => f.column);
  const colState = useColumns(cfg.kind, ["title", ...allColumns.map((c) => c.key), "status"]);
  const columns = allColumns.filter((c) => colState.isVisible(c.key));
  const [preview, setPreview] = useState(false);

  const clearSearch = () => {
    if (openId || createNew) navigate({ to: "/app/$section", params: { section: section.slug }, search: {}, replace: true });
  };

  const exportRows = (): string[][] => [
    ["Référence", cfg.titleLabel, "Statut", ...cfg.fields.map((f) => f.label), "Créé le"],
    ...list.map((r) => [r.reference, r.title, r.status, ...cfg.fields.map((f) => formatValue(f, r.data[f.key], byId)), new Date(r.created_at).toLocaleDateString("fr-FR")]),
  ];
  const exportCsv = () => setPreview(true);

  if (editing) {
    return (
      <div className="p-4 md:p-8">
        <RecordForm cfg={editing.cfg} record={editing.record} prefill={editing.prefill} records={records} onClose={() => { setEditing(null); if (!detail) clearSearch(); }} />
      </div>
    );
  }

  if (detail) {
    return (
      <div className="mx-auto max-w-4xl p-4 md:p-8">
          <DetailSheet
        record={detail}
        records={records}
        byId={byId}
        onClose={() => { setDetailId(null); clearSearch(); }}
        onEdit={(r) => { const c = KINDS[r.kind]; if (c) setEditing({ cfg: c, record: r }); }}
        onOpen={(r) => {
          const slug = sectionForKind(r.kind);
          if (slug === section.slug) { setTab(r.kind); setDetailId(r.id); }
          else if (slug) navigate({ to: "/app/$section", params: { section: slug }, search: { open: r.id } });
        }}
      />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-7xl p-4 md:p-8">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <Link to="/app" className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Vue d'ensemble</Link>
          <h1 className="mt-1 font-display text-2xl font-extrabold text-foreground md:text-3xl">{section.title}</h1>
          <p className="mt-1 text-sm text-muted-foreground">{section.description}</p>
        </div>
        <ExportPreview open={preview} onClose={() => setPreview(false)} filename={`${section.slug}-${cfg.kind}.csv`} rows={preview ? exportRows() : []} />
        <div className="flex flex-wrap gap-2">
          <ColumnPicker cols={["title", ...allColumns.map((c) => c.key), "status"]} labels={{ title: cfg.titleLabel, status: "Statut", ...Object.fromEntries(allColumns.map((c) => [c.key, c.label])) }} state={colState} />
          <button onClick={exportCsv} className="inline-flex h-11 items-center gap-2 rounded-xl border border-border bg-card px-4 text-sm font-semibold text-foreground hover:border-primary hover:text-primary">
            <Download className="h-4 w-4" /> Exporter
          </button>
          <button
            onClick={() => setEditing({ cfg })}
            className="inline-flex h-11 items-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/25 transition-transform hover:bg-primary-dark active:scale-95"
          >
            <Plus className="h-4 w-4" /> Ajouter : {cfg.singular}
          </button>
        </div>
      </div>

      {section.kinds.length > 1 && (
        <select
          aria-label="Type d'élément"
          value={tab}
          onChange={(e) => { setTab(e.target.value); setStatusFilter(""); }}
          className="mt-6 h-11 w-full rounded-xl border border-input bg-card px-4 text-sm font-semibold outline-none focus:border-primary sm:w-80"
        >
          {section.kinds.map((k) => (
            <option key={k.kind} value={k.kind}>{k.label} ({records.filter((r) => r.kind === k.kind && r.status !== "Archivé").length})</option>
          ))}
        </select>
      )}

      {hasSiteField && siteRecord && (
        <p className="mt-4 inline-flex items-center gap-2 rounded-full bg-primary-soft px-3 py-1 text-xs font-bold text-primary">
          Site : {siteRecord.title}
          <button onClick={() => setSite("")} aria-label="Voir tous les sites"><X className="h-3.5 w-3.5" /></button>
        </p>
      )}

      <div className="mt-6 flex gap-2 overflow-x-auto pb-1">
        <button
          onClick={() => setStatusFilter("")}
          className={`shrink-0 rounded-xl border px-4 py-2.5 text-left transition-colors ${!statusFilter ? "border-primary bg-primary-soft" : "border-border bg-card"}`}
        >
          <p className="font-display text-xl font-extrabold text-foreground">{allOfKind.length}</p>
          <p className="text-xs font-semibold text-muted-foreground">Total</p>
        </button>
        {cfg.statuses.map((s) => (
          <button
            key={s.value}
            onClick={() => setStatusFilter(statusFilter === s.value ? "" : s.value)}
            className={`shrink-0 rounded-xl border px-4 py-2.5 text-left transition-colors ${statusFilter === s.value ? "border-primary bg-primary-soft" : "border-border bg-card"}`}
          >
            <p className="font-display text-xl font-extrabold text-foreground">{allOfKind.filter((r) => r.status === s.value).length}</p>
            <p className="whitespace-nowrap text-xs font-semibold text-muted-foreground">{s.value}</p>
          </button>
        ))}
      </div>

      <div className="mt-4 flex flex-wrap items-center gap-3">
        <div className="relative min-w-[220px] flex-1">
          <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-primary" />
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={`Rechercher dans ${cfg.label.toLowerCase()}…`}
            className="h-11 w-full rounded-xl border border-input bg-card pl-11 pr-4 text-sm outline-none focus:border-primary"
          />
        </div>
        {cfg.statuses.some((s) => s.value === "Archivé") && (
          <label className="flex items-center gap-2 text-xs font-semibold text-muted-foreground">
            <input type="checkbox" checked={showArchived} onChange={(e) => setShowArchived(e.target.checked)} className="h-4 w-4 accent-primary" />
            Afficher les archives
          </label>
        )}
      </div>

      <div className="mt-4 overflow-hidden rounded-2xl border border-border bg-card">
        {isLoading ? (
          <p className="p-8 text-center text-sm text-muted-foreground">Chargement…</p>
        ) : list.length === 0 ? (
          <div className="flex flex-col items-center px-6 py-14 text-center">
            <p className="font-display text-base font-bold text-foreground">
              {allOfKind.length === 0 ? `Aucun(e) ${cfg.singular} pour l'instant` : "Aucun résultat"}
            </p>
            <p className="mt-1 text-sm text-muted-foreground">
              {allOfKind.length === 0 ? "Commencez par ajouter votre premier élément." : "Modifiez votre recherche ou le filtre."}
            </p>
            {allOfKind.length === 0 && (
              <button onClick={() => setEditing({ cfg })} className="mt-5 inline-flex h-10 items-center gap-2 rounded-xl border border-primary px-4 text-sm font-semibold text-primary hover:bg-primary-soft">
                <Plus className="h-4 w-4" /> Ajouter
              </button>
            )}
          </div>
        ) : (
          <>
            <DoubleScroll className="hidden md:block"><table className="w-full text-sm">
              <thead className="border-b border-border bg-background text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">
                <tr>
                  <th className="sticky left-0 z-10 bg-background px-4 py-3">Réf.</th>
                  {colState.isVisible("title") && <th className="px-4 py-3">{cfg.titleLabel}</th>}
                  {columns.map((c) => <th key={c.key} className="px-4 py-3">{c.label}</th>)}
                  {colState.isVisible("status") && <th className="px-4 py-3">Statut</th>}
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {list.map((r) => (
                  <tr key={r.id} onClick={() => setDetailId(r.id)} className="cursor-pointer transition-colors hover:bg-primary-soft/40">
                    <td className="sticky left-0 whitespace-nowrap bg-card px-4 py-3 font-mono text-xs font-bold text-primary">{r.reference}</td>
                    {colState.isVisible("title") && <td className="px-4 py-3 font-semibold text-foreground">
                      <span className="flex items-center gap-2">
                        {r.title}
                        {isOverdue(r) && <AlertTriangle className="h-3.5 w-3.5 text-destructive" aria-label="En retard" />}
                      </span>
                    </td>}
                    {columns.map((c) => (
                      <td key={c.key} className="max-w-[200px] truncate px-4 py-3 text-muted-foreground">{formatValue(c, r.data[c.key], byId)}</td>
                    ))}
                    {colState.isVisible("status") && <td className="px-4 py-3">
                      <button title="Filtrer sur ce statut" onClick={(e) => { e.stopPropagation(); setStatusFilter(statusFilter === r.status ? "" : r.status); }}><StatusBadge kind={r.kind} status={r.status} /></button>
                    </td>}
                  </tr>
                ))}
              </tbody>
            </table></DoubleScroll>
            <ul className="divide-y divide-border md:hidden">
              {list.map((r) => (
                <li key={r.id}>
                  <button onClick={() => setDetailId(r.id)} className="w-full p-4 text-left">
                    <div className="flex items-center justify-between gap-2">
                      <span className="font-mono text-xs font-bold text-primary">{r.reference}</span>
                      <StatusBadge kind={r.kind} status={r.status} />
                    </div>
                    <p className="mt-1.5 flex items-center gap-2 text-sm font-semibold text-foreground">
                      {r.title}
                      {isOverdue(r) && <AlertTriangle className="h-3.5 w-3.5 text-destructive" />}
                    </p>
                    <p className="mt-1 truncate text-xs text-muted-foreground">
                      {columns.slice(0, 2).map((c) => formatValue(c, r.data[c.key], byId)).join(" · ")}
                    </p>
                  </button>
                </li>
              ))}
            </ul>
          </>
        )}
      </div>


    </div>
  );
}

// ---------- Workflow buttons (shared with Tasks / Verification / Approval) ----------

export function RecordActions({ record, compact }: { record: QRecord; compact?: boolean }) {
  const run = useTransition();
  const note = useAddNote();
  const [pending, setPending] = useState<Transition | "note" | null>(null);
  const actions = availableActions(record.kind, record.status);

  const click = (t: Transition) => {
    if (t.reason || t.date || t.number) { setPending(t); return; }
    if (t.confirm && !confirm(`${t.label} : « ${record.title} » ?`)) return;
    run.mutate({ record, t });
  };

  const btn = (tone: Transition["tone"], label = "") => {
    const l = label.toLowerCase();
    const color =
      tone === "danger" || /refus|rejet|annul|expir|suspend/.test(l) ? "bg-destructive/10 text-destructive border border-destructive/30 hover:bg-destructive hover:text-destructive-foreground"
      : /approuv|valid|clôtur|publi|conforme|réalis|atteint|termin/.test(l) ? "bg-success text-success-foreground hover:opacity-90"
      : /renouvel|réévalu|surveillance|corriger|à renouveler|remettre/.test(l) ? "bg-warning text-warning-foreground hover:opacity-90"
      : /soumettre|vérifi|transmettre|démarrer|lancer|planifi|évaluer|saisir/.test(l) ? "bg-info text-info-foreground hover:opacity-90"
      : /archiv/.test(l) ? "bg-secondary text-muted-foreground hover:text-foreground"
      : tone === "primary" ? "bg-primary text-primary-foreground hover:bg-primary-dark"
      : "border border-border text-foreground hover:border-primary hover:text-primary";
    return `inline-flex items-center justify-center gap-1.5 rounded-xl px-3 text-xs font-semibold transition-colors disabled:opacity-50 ${compact ? "h-8" : "h-9"} ${color}`;
  };

  return (
    <>
      <div className="flex flex-wrap gap-1.5">
        {actions.map((t) => (
          <button key={t.label} disabled={run.isPending} onClick={() => click(t)} className={btn(t.tone, t.label)}>
            {t.label === "Archiver" && <Archive className="h-3.5 w-3.5" />}
            {t.label}
          </button>
        ))}
        <button disabled={note.isPending} onClick={() => setPending("note")} className={btn("default")}>
          <MessageSquarePlus className="h-3.5 w-3.5" /> Observation / preuve
        </button>
      </div>
      {pending && (
        <ActionDialog
          title={pending === "note" ? "Ajouter une observation ou une preuve" : pending.label}
          t={pending === "note" ? { label: "Observation", reason: "Commentaire, lien vers la preuve ou justification" } : pending}
          busy={run.isPending || note.isPending}
          onClose={() => setPending(null)}
          onSubmit={async (v) => {
            if (pending === "note") await note.mutateAsync({ record, label: "Observation", comment: v.comment ?? "" });
            else await run.mutateAsync({ record, t: pending, ...v });
            setPending(null);
          }}
        />
      )}
    </>
  );
}

function ActionDialog({
  title, t, busy, onClose, onSubmit,
}: {
  title: string;
  t: Transition;
  busy: boolean;
  onClose: () => void;
  onSubmit: (v: { comment?: string; date?: string; number?: number }) => Promise<void>;
}) {
  const [comment, setComment] = useState("");
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
  const [num, setNum] = useState("");
  const [err, setErr] = useState("");
  const input = "w-full rounded-xl border border-input bg-background px-3.5 text-sm outline-none focus:border-primary";
  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (t.reason && !comment.trim()) { setErr(`${t.reason} : champ obligatoire.`); return; }
    if (t.number && num === "") { setErr(`${t.number.label} : champ obligatoire.`); return; }
    try {
      await onSubmit({ ...(t.reason ? { comment: comment.trim() } : {}), ...(t.date ? { date } : {}), ...(t.number ? { number: Number(num) } : {}) });
    } catch { setErr("L'opération a échoué. Vos saisies sont conservées."); }
  };
  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-foreground/40" onClick={onClose} />
      <form onSubmit={submit} className="relative w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-2xl">
        <h3 className="font-display text-lg font-bold text-foreground">{title}</h3>
        <div className="mt-4 space-y-3">
          {t.reason && (
            <label className="block">
              <span className="mb-1.5 block text-xs font-bold text-muted-foreground">{t.reason} *</span>
              <textarea autoFocus className={`${input} h-24 py-2.5`} value={comment} onChange={(e) => setComment(e.target.value)} />
            </label>
          )}
          {t.date && (
            <label className="block">
              <span className="mb-1.5 block text-xs font-bold text-muted-foreground">{t.date.label}</span>
              <input type="date" className={`${input} h-11`} value={date} onChange={(e) => setDate(e.target.value)} />
            </label>
          )}
          {t.number && (
            <label className="block">
              <span className="mb-1.5 block text-xs font-bold text-muted-foreground">{t.number.label} *</span>
              <input type="number" step="any" autoFocus className={`${input} h-11`} value={num} onChange={(e) => setNum(e.target.value)} />
            </label>
          )}
          {err && <p className="rounded-xl bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive">{err}</p>}
        </div>
        <div className="mt-5 flex gap-2">
          <button type="button" onClick={onClose} className="h-11 flex-1 rounded-xl border border-border text-sm font-semibold">Annuler</button>
          <button disabled={busy} className={`h-11 flex-1 rounded-xl text-sm font-semibold text-primary-foreground disabled:opacity-60 ${t.tone === "danger" ? "bg-destructive" : "bg-primary hover:bg-primary-dark"}`}>
            {busy ? "Traitement…" : "Confirmer"}
          </button>
        </div>
      </form>
    </div>
  );
}

export function HistoryList({ record }: { record: QRecord }) {
  const h = [...historyOf(record)].reverse();
  if (!h.length) return <p className="text-sm text-muted-foreground">Aucun historique.</p>;
  return (
    <ol className="space-y-3 border-l-2 border-primary/15 pl-4">
      {h.map((e, i) => (
        <li key={i} className="relative text-sm">
          <span className="absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full bg-primary" />
          <p className="font-semibold text-foreground">
            {e.action}
            {e.to && e.from !== e.to && <span className="font-normal text-muted-foreground"> → {e.to}</span>}
          </p>
          {e.comment && <p className="mt-0.5 whitespace-pre-wrap text-muted-foreground">{e.comment}</p>}
          <p className="mt-0.5 text-xs text-muted-foreground">{new Date(e.at).toLocaleString("fr-FR")} · {e.by || "—"}</p>
        </li>
      ))}
    </ol>
  );
}

function DetailSheet({
  record, records, byId, onClose, onEdit, onOpen,
}: {
  record: QRecord | null;
  records: QRecord[];
  byId: Map<string, QRecord>;
  onClose: () => void;
  onEdit: (r: QRecord) => void;
  onOpen: (r: QRecord) => void;
}) {
  const setStatus = useSetStatus();
  const save = useSaveRecord();
  const del = useDeleteRecord();
  const navigate = useNavigate();
  const cfg = record ? KINDS[record.kind] : undefined;

  const outgoing = record && cfg
    ? cfg.fields.filter((f) => f.type === "relation" && record.data[f.key]).map((f) => ({ f, r: byId.get(String(record.data[f.key])) })).filter((x) => x.r)
    : [];
  const incoming = record ? records.filter((r) => r.id !== record.id && Object.entries(r.data).some(([k, v]) => !k.startsWith("_") && v === record.id)) : [];
  const values = record && Array.isArray(record.data["_values"]) ? (record.data["_values"] as { at: string; key: string; value: number }[]) : [];

  return (
    <div className="rounded-2xl border border-border bg-card p-5 md:p-8">
      <button onClick={onClose} className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline">← Retour à la liste</button>
      <div>
        {record && cfg && (
          <>
            <div>
              <p className="font-mono text-xs font-bold text-primary">{record.reference} · {cfg.singular}</p>
              <h1 className="mt-1 font-display text-2xl font-bold text-foreground">{record.title}</h1>
              <div className="mt-2">
                <div className="flex flex-wrap items-center gap-2">
                  <StatusBadge kind={record.kind} status={record.status} />
                  {record.data["version"] ? <span className="text-xs font-semibold text-muted-foreground">v{String(record.data["version"])}</span> : null}
                  {isOverdue(record) && <span className="text-xs font-bold text-destructive">En retard</span>}
                </div>
              </div>
            </div>

            <div className="mt-5 space-y-2">
              <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Actions</p>
              <RecordActions record={record} />
              <details className="pt-1">
                <summary className="cursor-pointer text-xs font-semibold text-muted-foreground hover:text-primary">Forcer un statut</summary>
                <div className="mt-2 flex flex-wrap gap-1.5">
                  {cfg.statuses.map((s) => (
                    <button
                      key={s.value}
                      disabled={setStatus.isPending || record.status === s.value}
                      onClick={() => setStatus.mutate({ record, status: s.value })}
                      className={`rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors ${
                        record.status === s.value ? "border-primary bg-primary text-primary-foreground" : "border-border text-muted-foreground hover:border-primary hover:text-primary"
                      }`}
                    >
                      {s.value}
                    </button>
                  ))}
                </div>
              </details>
            </div>

            {cfg.owner && (
              <div className="mt-5">
                <label className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Responsable (affectation rapide)</label>
                <select
                  value={String(record.data[cfg.owner] ?? "")}
                  disabled={save.isPending}
                  onChange={(e) => save.mutate({ id: record.id, kind: record.kind, title: record.title, status: record.status, data: { ...record.data, [cfg.owner!]: e.target.value }, previous: record })}
                  className="mt-1.5 h-10 w-full rounded-xl border border-input bg-background px-3 text-sm outline-none focus:border-primary"
                >
                  <option value="">— Non affecté —</option>
                  {records.filter((r) => r.kind === "collaborator" && r.status !== "Archivé").map((c) => (
                    <option key={c.id} value={c.id}>{c.reference} · {c.title}</option>
                  ))}
                </select>
              </div>
            )}

            <dl className="mt-6 divide-y divide-border rounded-2xl border border-border">
              {cfg.fields.map((f) => (
                <div key={f.key} className="grid grid-cols-[40%_1fr] gap-3 px-4 py-2.5 text-sm">
                  <dt className="font-semibold text-muted-foreground">{f.label}</dt>
                  <dd className="whitespace-pre-wrap break-words text-foreground">{formatValue(f, record.data[f.key], byId)}</dd>
                </div>
              ))}
              {record.data["effective_date"] ? (
                <div className="grid grid-cols-[40%_1fr] gap-3 px-4 py-2.5 text-sm">
                  <dt className="font-semibold text-muted-foreground">Date d'effet</dt>
                  <dd className="text-foreground">{new Date(String(record.data["effective_date"])).toLocaleDateString("fr-FR")}</dd>
                </div>
              ) : null}
              {record.kind === "risk" && (
                <div className="grid grid-cols-[40%_1fr] gap-3 px-4 py-2.5 text-sm">
                  <dt className="font-semibold text-muted-foreground">Criticité</dt>
                  <dd className="font-bold text-primary">{Number(record.data["probability"] || 0) * Number(record.data["gravity"] || 0)} / 25</dd>
                </div>
              )}
              {record.kind === "indicator" && record.data["target"] != null && record.data["value"] != null && (
                <div className="grid grid-cols-[40%_1fr] gap-3 px-4 py-2.5 text-sm">
                  <dt className="font-semibold text-muted-foreground">Atteinte</dt>
                  <dd className={`font-bold ${Number(record.data["value"]) >= Number(record.data["target"]) ? "text-primary" : "text-destructive"}`}>
                    {Math.round((Number(record.data["value"]) / (Number(record.data["target"]) || 1)) * 100)} %
                  </dd>
                </div>
              )}
            </dl>

            {values.length > 0 && (
              <div className="mt-6">
                <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Valeurs saisies</p>
                <ul className="mt-2 divide-y divide-border rounded-2xl border border-border text-sm">
                  {[...values].reverse().slice(0, 12).map((v, i) => (
                    <li key={i} className="flex justify-between px-4 py-2"><span className="text-muted-foreground">{new Date(v.at).toLocaleDateString("fr-FR")}</span><span className="font-bold text-foreground">{v.value}</span></li>
                  ))}
                </ul>
              </div>
            )}

            <div className="mt-6">
              <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                <Link2 className="h-3.5 w-3.5" /> Éléments liés ({outgoing.length + incoming.length})
              </p>
              <ul className="mt-2 space-y-1.5">
                {outgoing.map(({ f, r }) => (
                  <li key={`o-${f.key}`}>
                    <button onClick={() => onOpen(r!)} className="flex w-full items-center justify-between gap-2 rounded-xl border border-border px-3 py-2 text-left text-sm hover:border-primary">
                      <span><span className="text-xs text-muted-foreground">{f.label} · </span><span className="font-semibold text-foreground">{r!.reference} {r!.title}</span></span>
                      <StatusBadge kind={r!.kind} status={r!.status} />
                    </button>
                  </li>
                ))}
                {incoming.map((r) => (
                  <li key={`i-${r.id}`}>
                    <button onClick={() => onOpen(r)} className="flex w-full items-center justify-between gap-2 rounded-xl border border-border px-3 py-2 text-left text-sm hover:border-primary">
                      <span><span className="text-xs text-muted-foreground">{KINDS[r.kind]?.singular} · </span><span className="font-semibold text-foreground">{r.reference} {r.title}</span></span>
                      <StatusBadge kind={r.kind} status={r.status} />
                    </button>
                  </li>
                ))}
                {outgoing.length + incoming.length === 0 && <li className="text-sm text-muted-foreground">Aucun élément lié.</li>}
              </ul>
              <div className="mt-3 space-y-2">
                {(FOLLOW_UPS[record.kind] ?? []).map((fu) => (
                  <button
                    key={fu.label}
                    onClick={() => navigate({ to: "/app/$section", params: { section: fu.section }, search: { new: 1, origin: record.id, ...(fu.kind ? { kind: fu.kind } : {}) } })}
                    className="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-primary text-sm font-semibold text-primary hover:bg-primary-soft"
                  >
                    <Plus className="h-4 w-4" /> {fu.label}
                  </button>
                ))}
              </div>
            </div>

            <div className="mt-6">
              <p className="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                <History className="h-3.5 w-3.5" /> Historique
              </p>
              <HistoryList record={record} />
            </div>

            <p className="mt-6 flex items-center gap-2 text-xs text-muted-foreground">
              <Calendar className="h-3.5 w-3.5" /> Créé le {new Date(record.created_at).toLocaleDateString("fr-FR")} · modifié le {new Date(record.updated_at).toLocaleDateString("fr-FR")}
            </p>

            <div className="mt-4 flex gap-2 pb-4">
              <button onClick={() => onEdit(record)} className="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl border border-border text-sm font-semibold hover:border-primary hover:text-primary">
                <Pencil className="h-4 w-4" /> Modifier
              </button>
              <button
                disabled={del.isPending}
                onClick={() => {
                  if (incoming.length > 0) {
                    toast.error(`Suppression impossible : ${incoming.length} élément(s) y sont liés. Archivez-le plutôt pour conserver l'historique.`);
                    return;
                  }
                  if (confirm(`Supprimer définitivement « ${record.title} » ?`)) { del.mutate({ id: record.id, kind: record.kind }); onClose(); }
                }}
                aria-label="Supprimer"
                className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-border px-4 text-sm font-semibold text-destructive hover:border-destructive"
              >
                <Trash2 className="h-4 w-4" />
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}

function RecordForm({
  cfg, record, prefill, records, onClose,
}: {
  cfg: KindConfig;
  record?: QRecord | undefined;
  prefill?: Record<string, string> | undefined;
  records: QRecord[];
  onClose: () => void;
}) {
  const save = useSaveRecord();
  const [title, setTitle] = useState(record?.title ?? "");
  const [data, setData] = useState<QRecord["data"]>(() => ({ ...(cfg.fields.some((f) => f.key === "version") ? { version: "1.0" } : {}), ...(prefill ?? {}), ...(record?.data ?? {}) }));
  const [error, setError] = useState("");
  const status = record?.status ?? cfg.statuses[0]?.value ?? "";
  const activeNorms = useMemo(() => [...computeWorkspace(records, null).active], [records]);
  const multiNorm = activeNorms.length >= 2 && cfg.kind !== "norm";
  const fields = cfg.fields.filter((f) => !(f.key === "norm" && f.type === "select"));
  const chosenNorms: string[] = Array.isArray(data["norms"]) ? (data["norms"] as string[]) : data["norm"] ? [String(data["norm"])] : [];

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim()) { setError(`${cfg.titleLabel} est obligatoire.`); return; }
    let payload = data;
    if (multiNorm) {
      if (chosenNorms.length === 0) { setError("Sélectionnez au moins une norme concernée."); return; }
      payload = { ...data, norms: chosenNorms, norm: chosenNorms.join(", ") };
    } else if (activeNorms.length === 1) payload = { ...data, norms: activeNorms, norm: activeNorms[0] };
    for (const f of fields) {
      if (f.required && (data[f.key] === undefined || data[f.key] === null || data[f.key] === "")) { setError(`${f.label} est obligatoire.`); return; }
    }
    const dup = records.find((r) => r.kind === cfg.kind && r.id !== record?.id && r.title.trim().toLowerCase() === title.trim().toLowerCase() && r.status !== "Archivé");
    if (dup && !confirm(`Un élément « ${dup.title} » (${dup.reference}) existe déjà. Enregistrer quand même ?`)) return;
    setError("");
    try {
      await save.mutateAsync({ id: record?.id, kind: cfg.kind, title: title.trim(), status, data: payload, previous: record });
      onClose();
    } catch {
      setError("L'enregistrement a échoué. Vos saisies sont conservées, réessayez.");
    }
  };

  const input = "h-11 w-full rounded-xl border border-input bg-card px-3.5 text-sm outline-none focus:border-primary";

  return (
    <div className="mx-auto max-w-5xl">
      <button type="button" onClick={onClose} className="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline">← Retour</button>
      <div>
        <div>
          <h1 className="mt-1 font-display text-2xl font-bold text-foreground">{record ? "Modifier" : "Ajouter"} : {cfg.singular}</h1>
          <p className="mt-1 text-sm text-muted-foreground">{record ? `${record.reference} · statut « ${record.status} » (modifiable via les boutons d'action)` : `Créé au statut « ${status} ».`}</p>
        </div>
        <form onSubmit={submit} className="mt-6 space-y-4 pb-6">
          <label className="block">
            <span className="mb-1.5 block text-xs font-bold text-muted-foreground">{cfg.titleLabel} *</span>
            <input className={input} value={title} onChange={(e) => setTitle(e.target.value)} autoFocus />
          </label>
          {multiNorm && (
            <fieldset className="rounded-xl border border-border p-3">
              <legend className="px-1 text-xs font-bold text-muted-foreground">Normes concernées *</legend>
              <div className="flex flex-wrap gap-3">
                {activeNorms.map((n) => (
                  <label key={n} className="flex items-center gap-2 text-sm">
                    <input type="checkbox" className="h-4 w-4 accent-primary" checked={chosenNorms.includes(n)}
                      onChange={(e) => setData((d) => ({ ...d, norms: e.target.checked ? [...chosenNorms, n] : chosenNorms.filter((x) => x !== n) }))} />
                    {n}
                  </label>
                ))}
              </div>
            </fieldset>
          )}
          {fields.map((f) => {
            const val = data[f.key] ?? "";
            const set = (v: string) => setData((d) => ({ ...d, [f.key]: f.type === "number" ? (v === "" ? null : Number(v)) : v }));
            return (
              <label key={f.key} className="block">
                <span className="mb-1.5 block text-xs font-bold text-muted-foreground">{f.label}{f.required ? " *" : ""}</span>
                {f.type === "textarea" ? (
                  <textarea className={`${input} h-24 py-2.5`} value={String(val)} onChange={(e) => set(e.target.value)} />
                ) : f.type === "select" ? (
                  <select className={input} value={String(val)} onChange={(e) => set(e.target.value)}>
                    <option value="">—</option>
                    {f.options!.map((o) => <option key={o}>{o}</option>)}
                  </select>
                ) : f.type === "relation" ? (
                  <select className={input} value={String(val)} onChange={(e) => set(e.target.value)}>
                    <option value="">— Aucun —</option>
                    {f.kinds!.map((k) => {
                      const opts = records.filter((r) => r.kind === k && r.id !== record?.id && r.status !== "Archivé");
                      if (!opts.length) return null;
                      return (
                        <optgroup key={k} label={KINDS[k]?.label}>
                          {opts.map((r) => <option key={r.id} value={r.id}>{r.reference} · {r.title}</option>)}
                        </optgroup>
                      );
                    })}
                  </select>
                ) : (
                  <input
                    className={input}
                    type={f.type === "number" ? "number" : f.type === "date" ? "date" : f.type === "email" ? "email" : "text"}
                    value={String(val)}
                    onChange={(e) => set(e.target.value)}
                  />
                )}
                {f.type === "relation" && !f.kinds!.some((k) => records.some((r) => r.kind === k)) && (
                  <span className="mt-1 block text-xs text-muted-foreground">Aucun élément disponible : créez d'abord {f.kinds!.slice(0, 3).map((k) => KINDS[k]?.label.toLowerCase()).join(" / ")}.</span>
                )}
              </label>
            );
          })}
          {error && <p className="rounded-xl bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive">{error}</p>}
          <div className="flex gap-2 pt-2">
            <button type="button" onClick={onClose} className="h-11 flex-1 rounded-xl border border-border text-sm font-semibold">Annuler</button>
            <button type="submit" disabled={save.isPending} className="h-11 flex-1 rounded-xl bg-primary text-sm font-semibold text-primary-foreground hover:bg-primary-dark disabled:opacity-60">
              {save.isPending ? "Enregistrement…" : "Enregistrer"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
