import { getRouteApi, Link } from "@tanstack/react-router";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { AlertTriangle, Columns3, Download, Eye, Inbox, Plus, X } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { supabase } from "@/integrations/supabase/client";
import type { Json } from "@/integrations/supabase/types";
import { dueOf, isDone, isOverdue, useRecords, type QRecord } from "@/hooks/use-records";
import { downloadCsv } from "@/hooks/use-workspace";
import { KINDS, sectionForKind } from "./sections";
import { StatusBadge } from "./SectionView";

const appRoute = getRouteApi("/_authenticated/app");
const fmt = (d: string) => new Date(d).toLocaleDateString("fr-FR");
const inputCls = "h-11 rounded-xl border border-input bg-card px-4 text-sm font-semibold outline-none focus:border-primary";

function Title({ title, desc, children }: { title: string; desc: string; children?: React.ReactNode }) {
  return (
    <div className="flex flex-wrap items-end justify-between gap-4">
      <div>
        <Link to="/app" className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Vue d'ensemble</Link>
        <h1 className="mt-1 font-display text-2xl font-extrabold text-foreground md:text-3xl">{title}</h1>
        <p className="mt-1 text-sm text-muted-foreground">{desc}</p>
      </div>
      {children && <div className="flex flex-wrap gap-2">{children}</div>}
    </div>
  );
}

// ---------- RT-01 : prévisualisation avant export ----------
export function ExportPreview({ open, onClose, filename, rows }: { open: boolean; onClose: () => void; filename: string; rows: string[][] }) {
  if (!open) return null;
  const [head, ...body] = rows;
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/40 p-4" onClick={onClose}>
      <div className="flex max-h-[85vh] w-full max-w-4xl flex-col rounded-2xl bg-card shadow-xl" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between border-b border-border p-4">
          <div>
            <p className="font-display text-lg font-bold text-foreground">Aperçu de l'export</p>
            <p className="text-xs text-muted-foreground">{filename} · {body.length} ligne(s) · {head?.length ?? 0} colonne(s) · {new Date().toLocaleString("fr-FR")}</p>
          </div>
          <button onClick={onClose} aria-label="Fermer"><X className="h-5 w-5" /></button>
        </div>
        <div className="overflow-auto p-4">
          <table className="w-full text-xs">
            <thead><tr>{head?.map((h, i) => <th key={i} className="border-b border-border px-2 py-2 text-left font-bold text-muted-foreground">{h}</th>)}</tr></thead>
            <tbody>{body.slice(0, 50).map((r, i) => <tr key={i}>{r.map((c, j) => <td key={j} className="border-b border-border px-2 py-1.5">{c}</td>)}</tr>)}</tbody>
          </table>
          {body.length > 50 && <p className="mt-2 text-xs text-muted-foreground">… {body.length - 50} ligne(s) supplémentaire(s) dans le fichier.</p>}
        </div>
        <div className="flex justify-end gap-2 border-t border-border p-4">
          <button onClick={onClose} className="h-10 rounded-xl border border-border px-4 text-sm font-semibold">Annuler</button>
          <button onClick={() => { downloadCsv(filename, rows); toast.success("Export téléchargé"); onClose(); }} className="inline-flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground">
            <Download className="h-4 w-4" /> Confirmer le téléchargement
          </button>
        </div>
      </div>
    </div>
  );
}

// ---------- RT-04 : sélecteur de colonnes ----------
export function useColumns(storageKey: string, all: string[]) {
  const [hidden, setHidden] = useState<string[]>([]);
  useEffect(() => {
    try { setHidden(JSON.parse(localStorage.getItem(`cols:${storageKey}`) ?? "[]")); } catch { setHidden([]); }
  }, [storageKey]);
  const save = (h: string[]) => { setHidden(h); localStorage.setItem(`cols:${storageKey}`, JSON.stringify(h)); };
  const visible = all.filter((c) => !hidden.includes(c));
  const toggle = (c: string) => {
    if (hidden.includes(c)) save(hidden.filter((x) => x !== c));
    else if (visible.length <= 1) toast.error("Au moins une colonne doit rester visible.");
    else save([...hidden, c]);
  };
  return { isVisible: (c: string) => !hidden.includes(c), toggle, reset: () => save([]), visibleCount: visible.length };
}

export function ColumnPicker({ cols, labels, state }: { cols: string[]; labels: Record<string, string>; state: ReturnType<typeof useColumns> }) {
  const [open, setOpen] = useState(false);
  return (
    <div className="relative">
      <button onClick={() => setOpen(!open)} className="inline-flex h-11 items-center gap-2 rounded-xl border border-border bg-card px-4 text-sm font-semibold hover:border-primary">
        <Columns3 className="h-4 w-4" /> Colonnes ({state.visibleCount}/{cols.length})
      </button>
      {open && (
        <div className="absolute right-0 z-30 mt-2 w-64 rounded-xl border border-border bg-card p-3 shadow-lg">
          {cols.map((c) => (
            <label key={c} className="flex items-center gap-2 py-1 text-sm">
              <input type="checkbox" className="h-4 w-4 accent-primary" checked={state.isVisible(c)} onChange={() => state.toggle(c)} /> {labels[c] ?? c}
            </label>
          ))}
          <button onClick={state.reset} className="mt-2 w-full rounded-lg bg-primary-soft py-1.5 text-xs font-bold text-primary">Tout afficher</button>
        </div>
      )}
    </div>
  );
}

// ---------- RT-13 : Mes actions ----------
export function MyActionsPage() {
  const profile = appRoute.useLoaderData();
  const { data: records = [], isLoading } = useRecords();
  const [status, setStatus] = useState("");
  const email = (profile.email ?? "").toLowerCase();
  const mine = useMemo(() => new Set(records.filter((r) => r.kind === "collaborator" && String(r.data["email"] ?? "").toLowerCase() === email).map((r) => r.id)), [records, email]);
  const all = records
    .filter((r) => { const o = KINDS[r.kind]?.owner; return o && mine.has(String(r.data[o] ?? "")) && r.status !== "Archivé"; })
    .sort((a, b) => (dueOf(a) ?? "9999").localeCompare(dueOf(b) ?? "9999"));
  const label = (r: QRecord) => (isOverdue(r) ? "En retard" : isDone(r) ? "Réalisé" : r.status || "À planifier");
  const statuses = ["En retard", "Réalisé", ...new Set(all.filter((r) => !isOverdue(r) && !isDone(r)).map((r) => r.status || "À planifier"))];
  const list = all.filter((r) => !status || label(r) === status);
  const late = all.filter(isOverdue).length;
  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <Title title="Mes actions" desc="Toutes les actions qui vous sont assignées, tous modules confondus, triées par échéance.">
        <Link to="/app/$section" params={{ section: "actions" }} search={{ new: 1 }} className="inline-flex h-11 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground"><Plus className="h-4 w-4" /> Nouvelle action</Link>
      </Title>
      {mine.size === 0 && <p className="rounded-xl bg-warning/15 p-3 text-sm text-warning-foreground">Aucune fiche collaborateur ne porte votre adresse e-mail ({profile.email}). Créez-la dans « Liste du personnel » pour recevoir vos actions.</p>}
      {late > 0 && <p className="flex items-center gap-2 rounded-xl bg-destructive/10 p-3 text-sm font-semibold text-destructive"><AlertTriangle className="h-4 w-4" /> {late} action(s) en retard</p>}
      <select value={status} onChange={(e) => setStatus(e.target.value)} className={`${inputCls} w-full sm:w-72`} aria-label="Filtrer par statut">
        <option value="">Tous les statuts ({all.length})</option>
        {statuses.map((s) => <option key={s} value={s}>{s} ({all.filter((r) => label(r) === s).length})</option>)}
      </select>
      {isLoading ? <p className="text-sm text-muted-foreground">Chargement…</p> : (
        <ul className="space-y-2">
          {list.map((r) => {
            const slug = sectionForKind(r.kind);
            const due = dueOf(r);
            return (
              <li key={r.id} className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-card p-4">
                <div className="min-w-0">
                  <p className="text-xs font-bold text-primary">{r.reference} · {KINDS[r.kind]?.singular}</p>
                  <p className="font-semibold text-foreground">{r.title}</p>
                  <p className={`text-xs ${isOverdue(r) ? "font-bold text-destructive" : "text-muted-foreground"}`}>Échéance : {due ? fmt(due) : "—"}</p>
                </div>
                <div className="flex items-center gap-2">
                  <button onClick={() => setStatus(label(r))}><StatusBadge kind={r.kind} status={r.status} /></button>
                  {slug && <Link to="/app/$section" params={{ section: slug }} search={{ open: r.id }} className="inline-flex h-8 items-center gap-1 rounded-xl border border-border px-3 text-xs font-semibold hover:border-primary"><Eye className="h-3.5 w-3.5" /> Ouvrir</Link>}
                </div>
              </li>
            );
          })}
          {list.length === 0 && <li className="rounded-2xl border border-border bg-card p-8 text-center text-sm text-muted-foreground">Aucune action.</li>}
        </ul>
      )}
    </div>
  );
}

// ---------- Boîte de réception des plaintes clients ----------
type Complaint = { id: string; reference: string; title: string; status: string; data: Record<string, unknown>; created_at: string };
const INBOX_KEY = ["client-inbox"];
const STATUSES = ["En attente", "En cours", "Résolue", "Rejetée"];
const TONE: Record<string, string> = {
  "En attente": "bg-warning/15 text-warning-foreground", "En cours": "bg-info/15 text-info",
  "Résolue": "bg-success/15 text-success", "Rejetée": "bg-destructive/10 text-destructive",
};

export function useInbox() {
  return useQuery({
    queryKey: INBOX_KEY,
    queryFn: async () => {
      const { data: u } = await supabase.auth.getUser();
      const { data, error } = await supabase.from("qhse_records").select("id, reference, title, status, data, created_at")
        .eq("kind", "client_complaint").eq("target_company_id", u.user!.id).order("created_at", { ascending: false });
      if (error) throw error;
      return (data ?? []) as unknown as Complaint[];
    },
  });
}

export function InboxPage() {
  const qc = useQueryClient();
  const { data: list = [], isLoading } = useInbox();
  const [filter, setFilter] = useState("");
  const [openId, setOpenId] = useState<string | null>(null);
  const [msg, setMsg] = useState("");
  const upd = useMutation({
    mutationFn: async (v: { c: Complaint; status: string; text: string }) => {
      const hist = Array.isArray(v.c.data["history"]) ? (v.c.data["history"] as unknown[]) : [];
      const data = { ...v.c.data, unread: true, seen_by_company: true, history: [...hist, { at: new Date().toISOString(), text: v.text }] };
      const { error } = await supabase.from("qhse_records").update({ status: v.status, data: data as Json }).eq("id", v.c.id);
      if (error) throw error;
    },
    onSuccess: () => { qc.invalidateQueries({ queryKey: INBOX_KEY }); setMsg(""); toast.success("Le client a été notifié"); },
    onError: () => toast.error("Mise à jour impossible."),
  });
  const shown = list.filter((c) => !filter || c.status === filter);
  const c = openId ? list.find((x) => x.id === openId) : undefined;

  if (c) {
    const hist = Array.isArray(c.data["history"]) ? (c.data["history"] as { at: string; text: string }[]) : [];
    const act = (status: string, label: string) => upd.mutate({ c, status, text: msg.trim() ? `${label} — ${msg.trim()}` : label });
    return (
      <div className="mx-auto max-w-4xl p-4 md:p-8">
        <button onClick={() => setOpenId(null)} className="mb-4 text-sm font-semibold text-primary hover:underline">← Retour à la boîte de réception</button>
        <div className="rounded-2xl border border-border bg-card p-6">
          <p className="font-mono text-xs font-bold text-primary">{c.reference}</p>
          <h1 className="mt-1 font-display text-2xl font-bold text-foreground">{c.title}</h1>
          <span className={`mt-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ${TONE[c.status] ?? ""}`}>{c.status}</span>
          <dl className="mt-5 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt className="font-semibold text-muted-foreground">Catégorie</dt><dd>{String(c.data["category"] ?? "—")}</dd></div>
            <div><dt className="font-semibold text-muted-foreground">Reçue le</dt><dd>{fmt(c.created_at)}</dd></div>
            <div className="sm:col-span-2"><dt className="font-semibold text-muted-foreground">Description</dt><dd className="whitespace-pre-wrap">{String(c.data["description"] ?? "")}</dd></div>
          </dl>
          <h2 className="mt-6 font-display font-bold">Suivi du traitement</h2>
          <ul className="mt-2 space-y-2 border-l-2 border-primary/20 pl-4 text-sm">
            {hist.map((h, i) => <li key={i}><span className="text-xs text-muted-foreground">{fmt(h.at)}</span> — {h.text}</li>)}
          </ul>
          {c.status !== "Résolue" && c.status !== "Rejetée" && (
            <div className="mt-6 space-y-3">
              <textarea value={msg} onChange={(e) => setMsg(e.target.value)} placeholder="Message au client (facultatif)" className="h-24 w-full rounded-xl border border-input bg-background p-3 text-sm outline-none focus:border-primary" />
              <div className="flex flex-wrap gap-2">
                {c.status === "En attente" && <button onClick={() => act("En cours", "Prise en charge par l'entreprise")} className="h-10 rounded-xl bg-info px-4 text-sm font-semibold text-info-foreground">Prendre en charge</button>}
                <button disabled={!msg.trim()} onClick={() => act(c.status, "Réponse de l'entreprise")} className="h-10 rounded-xl border border-border px-4 text-sm font-semibold disabled:opacity-50">Répondre</button>
                <button onClick={() => act("Résolue", "Plainte résolue")} className="h-10 rounded-xl bg-success px-4 text-sm font-semibold text-success-foreground">Marquer résolue</button>
                <button onClick={() => act("Rejetée", "Plainte rejetée")} className="h-10 rounded-xl bg-destructive px-4 text-sm font-semibold text-destructive-foreground">Rejeter</button>
                <Link to="/app/$section" params={{ section: "actions" }} search={{ new: 1 }} className="inline-flex h-10 items-center rounded-xl bg-warning px-4 text-sm font-semibold text-warning-foreground">Créer une action</Link>
              </div>
            </div>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <Title title="Plaintes clients" desc="Plaintes déposées par les particuliers à l'encontre de votre entreprise, avec leur suivi." />
      <div className="flex flex-wrap gap-2">
        {STATUSES.map((s) => (
          <button key={s} onClick={() => setFilter(filter === s ? "" : s)} className={`rounded-xl border px-4 py-2 text-left ${filter === s ? "border-primary bg-primary-soft" : "border-border bg-card"}`}>
            <p className="font-display text-xl font-extrabold">{list.filter((c) => c.status === s).length}</p>
            <p className="text-xs font-semibold text-muted-foreground">{s}</p>
          </button>
        ))}
      </div>
      {isLoading ? <p className="text-sm text-muted-foreground">Chargement…</p> : shown.length === 0 ? (
        <div className="flex flex-col items-center rounded-2xl border border-border bg-card p-10 text-center">
          <Inbox className="h-8 w-8 text-muted-foreground" />
          <p className="mt-2 font-display font-bold">Aucune plainte</p>
        </div>
      ) : (
        <ul className="space-y-2">
          {shown.map((c) => (
            <li key={c.id}>
              <button onClick={() => setOpenId(c.id)} className="flex w-full items-center justify-between gap-3 rounded-2xl border border-border bg-card p-4 text-left hover:border-primary">
                <div className="min-w-0">
                  <p className="text-xs font-bold text-primary">{c.reference} · {String(c.data["category"] ?? "")}{!c.data["seen_by_company"] ? " · Nouveau" : ""}</p>
                  <p className="truncate font-semibold text-foreground">{c.title}</p>
                  <p className="text-xs text-muted-foreground">Reçue le {fmt(c.created_at)}</p>
                </div>
                <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${TONE[c.status] ?? ""}`}>{c.status}</span>
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
