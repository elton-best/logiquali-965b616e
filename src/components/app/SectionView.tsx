import { Link, useNavigate } from "@tanstack/react-router";
import { ArrowRight, Calendar, Link2, Pencil, Plus, Search, Trash2, AlertTriangle } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from "@/components/ui/sheet";
import {
  KINDS,
  TONE_CLASS,
  sectionForKind,
  toneOf,
  type Field,
  type KindConfig,
  type SectionConfig,
} from "./sections";
import {
  isOverdue,
  useDeleteRecord,
  useRecords,
  useSaveRecord,
  useSetStatus,
  type QRecord,
} from "@/hooks/use-records";

const ACTION_SOURCES = ["nc", "risk", "audit", "objective", "review", "complaint", "suggestion"];

export function StatusBadge({ kind, status }: { kind: string; status: string }) {
  return (
    <span className={`inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-[11px] font-bold ${TONE_CLASS[toneOf(kind, status)]}`}>
      {status || "—"}
    </span>
  );
}

function formatValue(f: Field, v: unknown, byId: Map<string, QRecord>) {
  if (v === null || v === undefined || v === "") return "—";
  if (f.type === "relation") {
    const r = byId.get(String(v));
    return r ? `${r.reference} · ${r.title}` : "—";
  }
  if (f.type === "date") return new Date(String(v)).toLocaleDateString("fr-FR");
  return String(v);
}

type Props = { section: SectionConfig; openId?: string; createNew?: boolean; prefill?: Record<string, string> };

export function SectionView({ section, openId, createNew, prefill }: Props) {
  const navigate = useNavigate();
  const { data: records = [], isLoading } = useRecords();
  const [tab, setTab] = useState(section.kinds[0].kind);
  const [query, setQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("");
  const [detail, setDetail] = useState<QRecord | null>(null);
  const [editing, setEditing] = useState<{ cfg: KindConfig; record?: QRecord; prefill?: Record<string, string> } | null>(null);

  const cfg = KINDS[tab] ?? section.kinds[0];
  const byId = useMemo(() => new Map(records.map((r) => [r.id, r])), [records]);

  useEffect(() => {
    setTab(section.kinds[0].kind);
    setStatusFilter("");
    setQuery("");
  }, [section.slug, section.kinds]);

  // Deep links: ?open=<id> and ?new=1
  useEffect(() => {
    if (openId && byId.has(openId)) {
      const r = byId.get(openId)!;
      setTab(r.kind);
      setDetail(r);
    }
  }, [openId, byId]);
  useEffect(() => {
    if (createNew) setEditing({ cfg: section.kinds[0], prefill });
  }, [createNew, section.kinds, prefill]);

  // Keep detail fresh after mutations
  const liveDetail = detail ? byId.get(detail.id) ?? null : null;

  const list = records.filter(
    (r) =>
      r.kind === cfg.kind &&
      (!statusFilter || r.status === statusFilter) &&
      (!query || `${r.reference} ${r.title}`.toLowerCase().includes(query.toLowerCase()))
  );
  const allOfKind = records.filter((r) => r.kind === cfg.kind);
  const columns = cfg.fields.filter((f) => f.column).slice(0, 4);

  const clearSearch = () => {
    if (openId || createNew) navigate({ to: "/app/$section", params: { section: section.slug }, search: {}, replace: true });
  };

  return (
    <div className="mx-auto max-w-7xl p-4 md:p-8">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <Link to="/app" className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Tableau de bord</Link>
          <h1 className="mt-1 font-display text-2xl font-extrabold text-foreground md:text-3xl">{section.title}</h1>
          <p className="mt-1 text-sm text-muted-foreground">{section.description}</p>
        </div>
        <button
          onClick={() => setEditing({ cfg })}
          className="inline-flex h-11 items-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/25 transition-transform hover:bg-primary-dark active:scale-95"
        >
          <Plus className="h-4 w-4" /> Ajouter {cfg.singular === "processus" ? "un processus" : `un(e) ${cfg.singular}`}
        </button>
      </div>

      {section.kinds.length > 1 && (
        <div className="mt-6 flex gap-1 overflow-x-auto rounded-2xl border border-border bg-card p-1">
          {section.kinds.map((k) => (
            <button
              key={k.kind}
              onClick={() => { setTab(k.kind); setStatusFilter(""); }}
              className={`whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition-colors ${
                tab === k.kind ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground"
              }`}
            >
              {k.label} <span className="ml-1 opacity-70">{records.filter((r) => r.kind === k.kind).length}</span>
            </button>
          ))}
        </div>
      )}

      {/* Status counters */}
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

      <div className="relative mt-4">
        <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-primary" />
        <input
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={`Rechercher dans ${cfg.label.toLowerCase()}…`}
          className="h-11 w-full rounded-xl border border-input bg-card pl-11 pr-4 text-sm outline-none focus:border-primary"
        />
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
            {/* Desktop table */}
            <table className="hidden w-full text-sm md:table">
              <thead className="border-b border-border bg-background text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">
                <tr>
                  <th className="px-4 py-3">Réf.</th>
                  <th className="px-4 py-3">{cfg.titleLabel}</th>
                  {columns.map((c) => <th key={c.key} className="px-4 py-3">{c.label}</th>)}
                  <th className="px-4 py-3">Statut</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {list.map((r) => (
                  <tr key={r.id} onClick={() => setDetail(r)} className="cursor-pointer transition-colors hover:bg-primary-soft/40">
                    <td className="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-primary">{r.reference}</td>
                    <td className="px-4 py-3 font-semibold text-foreground">
                      <span className="flex items-center gap-2">
                        {r.title}
                        {isOverdue(r) && <AlertTriangle className="h-3.5 w-3.5 text-destructive" aria-label="En retard" />}
                      </span>
                    </td>
                    {columns.map((c) => (
                      <td key={c.key} className="max-w-[200px] truncate px-4 py-3 text-muted-foreground">{formatValue(c, r.data[c.key], byId)}</td>
                    ))}
                    <td className="px-4 py-3"><StatusBadge kind={r.kind} status={r.status} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
            {/* Mobile cards */}
            <ul className="divide-y divide-border md:hidden">
              {list.map((r) => (
                <li key={r.id}>
                  <button onClick={() => setDetail(r)} className="w-full p-4 text-left">
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

      <DetailSheet
        record={liveDetail}
        records={records}
        byId={byId}
        onClose={() => { setDetail(null); clearSearch(); }}
        onEdit={(r) => setEditing({ cfg: KINDS[r.kind], record: r })}
        onOpen={(r) => {
          const slug = sectionForKind(r.kind);
          if (slug === section.slug) { setTab(r.kind); setDetail(r); }
          else if (slug) navigate({ to: "/app/$section", params: { section: slug }, search: { open: r.id } });
        }}
      />

      {editing && (
        <RecordForm
          cfg={editing.cfg}
          record={editing.record}
          prefill={editing.prefill}
          records={records}
          onClose={() => { setEditing(null); clearSearch(); }}
        />
      )}
    </div>
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
  const del = useDeleteRecord();
  const navigate = useNavigate();
  const cfg = record ? KINDS[record.kind] : undefined;

  const outgoing = record && cfg
    ? cfg.fields.filter((f) => f.type === "relation" && record.data[f.key]).map((f) => ({ f, r: byId.get(String(record.data[f.key])) })).filter((x) => x.r)
    : [];
  const incoming = record ? records.filter((r) => r.id !== record.id && Object.values(r.data).includes(record.id)) : [];

  const idx = cfg && record ? cfg.statuses.findIndex((s) => s.value === record.status) : -1;
  const next = cfg?.workflow && idx >= 0 && idx < cfg.statuses.length - 1 ? cfg.statuses[idx + 1].value : null;

  return (
    <Sheet open={!!record} onOpenChange={(o) => !o && onClose()}>
      <SheetContent className="w-full overflow-y-auto sm:max-w-lg">
        {record && cfg && (
          <>
            <SheetHeader>
              <p className="font-mono text-xs font-bold text-primary">{record.reference} · {cfg.singular}</p>
              <SheetTitle className="font-display text-xl">{record.title}</SheetTitle>
              <SheetDescription asChild>
                <div className="flex flex-wrap items-center gap-2">
                  <StatusBadge kind={record.kind} status={record.status} />
                  {isOverdue(record) && <span className="text-xs font-bold text-destructive">En retard</span>}
                </div>
              </SheetDescription>
            </SheetHeader>

            <div className="mt-5 space-y-2">
              <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Changer le statut</p>
              <div className="flex flex-wrap gap-1.5">
                {cfg.statuses.map((s) => (
                  <button
                    key={s.value}
                    disabled={setStatus.isPending}
                    onClick={() => setStatus.mutate({ id: record.id, status: s.value })}
                    className={`rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors ${
                      record.status === s.value ? "border-primary bg-primary text-primary-foreground" : "border-border text-muted-foreground hover:border-primary hover:text-primary"
                    }`}
                  >
                    {s.value}
                  </button>
                ))}
              </div>
              {next && (
                <button
                  onClick={() => setStatus.mutate({ id: record.id, status: next })}
                  className="mt-2 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-primary text-sm font-semibold text-primary-foreground hover:bg-primary-dark"
                >
                  Passer à « {next} » <ArrowRight className="h-4 w-4" />
                </button>
              )}
            </div>

            <dl className="mt-6 divide-y divide-border rounded-2xl border border-border">
              {cfg.fields.map((f) => (
                <div key={f.key} className="grid grid-cols-[40%_1fr] gap-3 px-4 py-2.5 text-sm">
                  <dt className="font-semibold text-muted-foreground">{f.label}</dt>
                  <dd className="whitespace-pre-wrap break-words text-foreground">{formatValue(f, record.data[f.key], byId)}</dd>
                </div>
              ))}
              {record.kind === "risk" && (
                <div className="grid grid-cols-[40%_1fr] gap-3 px-4 py-2.5 text-sm">
                  <dt className="font-semibold text-muted-foreground">Criticité</dt>
                  <dd className="font-bold text-primary">{Number(record.data.probability || 0) * Number(record.data.gravity || 0)} / 25</dd>
                </div>
              )}
            </dl>

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
              {ACTION_SOURCES.includes(record.kind) && (
                <button
                  onClick={() => navigate({ to: "/app/$section", params: { section: "actions" }, search: { new: 1, origin: record.id } })}
                  className="mt-3 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-primary text-sm font-semibold text-primary hover:bg-primary-soft"
                >
                  <Plus className="h-4 w-4" /> Créer une action liée
                </button>
              )}
              {record.kind === "audit" && (
                <button
                  onClick={() => navigate({ to: "/app/$section", params: { section: "non-conformites" }, search: { new: 1, origin: record.id } })}
                  className="mt-2 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-border text-sm font-semibold text-foreground hover:border-primary"
                >
                  <Plus className="h-4 w-4" /> Déclarer une NC issue de l'audit
                </button>
              )}
            </div>

            <p className="mt-6 flex items-center gap-2 text-xs text-muted-foreground">
              <Calendar className="h-3.5 w-3.5" /> Créé le {new Date(record.created_at).toLocaleDateString("fr-FR")} · modifié le {new Date(record.updated_at).toLocaleDateString("fr-FR")}
            </p>

            <div className="mt-4 flex gap-2 pb-4">
              <button onClick={() => onEdit(record)} className="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl border border-border text-sm font-semibold hover:border-primary hover:text-primary">
                <Pencil className="h-4 w-4" /> Modifier
              </button>
              <button
                onClick={() => {
                  if (confirm(`Supprimer « ${record.title} » ?`)) { del.mutate(record.id); onClose(); }
                }}
                className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-border px-4 text-sm font-semibold text-destructive hover:border-destructive"
              >
                <Trash2 className="h-4 w-4" />
              </button>
            </div>
          </>
        )}
      </SheetContent>
    </Sheet>
  );
}

function RecordForm({
  cfg, record, prefill, records, onClose,
}: {
  cfg: KindConfig;
  record?: QRecord;
  prefill?: Record<string, string>;
  records: QRecord[];
  onClose: () => void;
}) {
  const save = useSaveRecord();
  const [title, setTitle] = useState(record?.title ?? "");
  const [status, setStatus] = useState(record?.status ?? cfg.statuses[0].value);
  const [data, setData] = useState<QRecord["data"]>(() => ({ ...(prefill ?? {}), ...(record?.data ?? {}) }));
  const [error, setError] = useState("");

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim()) { setError(`${cfg.titleLabel} est obligatoire.`); return; }
    for (const f of cfg.fields) {
      if (f.required && !data[f.key]) { setError(`${f.label} est obligatoire.`); return; }
    }
    setError("");
    await save.mutateAsync({ id: record?.id, kind: cfg.kind, title: title.trim(), status, data });
    onClose();
  };

  const input = "h-11 w-full rounded-xl border border-input bg-card px-3.5 text-sm outline-none focus:border-primary";

  return (
    <Sheet open onOpenChange={(o) => !o && onClose()}>
      <SheetContent className="w-full overflow-y-auto sm:max-w-lg">
        <SheetHeader>
          <SheetTitle className="font-display text-xl">{record ? "Modifier" : "Ajouter"} : {cfg.singular}</SheetTitle>
          <SheetDescription>{record ? record.reference : "Renseignez les informations puis enregistrez."}</SheetDescription>
        </SheetHeader>
        <form onSubmit={submit} className="mt-6 space-y-4 pb-6">
          <label className="block">
            <span className="mb-1.5 block text-xs font-bold text-muted-foreground">{cfg.titleLabel} *</span>
            <input className={input} value={title} onChange={(e) => setTitle(e.target.value)} autoFocus />
          </label>
          <label className="block">
            <span className="mb-1.5 block text-xs font-bold text-muted-foreground">Statut</span>
            <select className={input} value={status} onChange={(e) => setStatus(e.target.value)}>
              {cfg.statuses.map((s) => <option key={s.value}>{s.value}</option>)}
            </select>
          </label>
          {cfg.fields.map((f) => {
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
                      const opts = records.filter((r) => r.kind === k && r.id !== record?.id);
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
                  <span className="mt-1 block text-xs text-muted-foreground">Aucun élément disponible : créez d'abord {f.kinds!.map((k) => KINDS[k]?.label.toLowerCase()).join(" / ")}.</span>
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
      </SheetContent>
    </Sheet>
  );
}
