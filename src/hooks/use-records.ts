import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { supabase } from "@/integrations/supabase/client";
import type { Json } from "@/integrations/supabase/types";
import { KINDS, type Transition } from "@/components/app/sections";

export type HistoryEntry = { at: string; by: string; action: string; from?: string; to?: string; comment?: string };

export type QRecord = {
  id: string;
  kind: string;
  reference: string;
  title: string;
  status: string;
  data: Record<string, unknown>;
  created_at: string;
  updated_at: string;
};

export const RECORDS_KEY = ["qhse-records"];
const KEY = RECORDS_KEY;

export function historyOf(r: QRecord): HistoryEntry[] {
  const h = r.data["_history"];
  return Array.isArray(h) ? (h as HistoryEntry[]) : [];
}

async function me(): Promise<{ id: string; email: string }> {
  const { data } = await supabase.auth.getSession();
  return { id: data.session?.user.id ?? "", email: data.session?.user.email ?? "" };
}

function entry(by: string, action: string, extra: Partial<HistoryEntry> = {}): HistoryEntry {
  return { at: new Date().toISOString(), by, action, ...extra };
}

export function useRecords() {
  return useQuery({
    queryKey: KEY,
    queryFn: async () => {
      const { data, error } = await supabase
        .from("qhse_records")
        .select("id, kind, reference, title, status, data, created_at, updated_at")
        .is("target_company_id", null)
        .order("created_at", { ascending: false })
        .limit(5000);
      if (error) throw error;
      return (data ?? []) as unknown as QRecord[];
    },
  });
}

async function update(id: string, patch: { title?: string; status?: string; data?: Record<string, unknown> }) {
  const { error } = await supabase
    .from("qhse_records")
    .update({ ...patch, ...(patch.data ? { data: patch.data as Json } : {}) } as never)
    .eq("id", id);
  if (error) throw error;
}

export function useSaveRecord() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (input: { id?: string | undefined; kind: string; title: string; status: string; data: QRecord["data"]; previous?: QRecord | undefined; silent?: boolean }) => {
      const u = await me();
      const prevHistory = input.previous ? historyOf(input.previous) : [];
      if (input.id) {
        const h = entry(u.email, "Modification", input.previous && input.previous.status !== input.status ? { from: input.previous.status, to: input.status } : {});
        await update(input.id, { title: input.title, status: input.status, data: { ...input.data, _history: [...prevHistory, h] } });
        return;
      }
      const cfg = KINDS[input.kind];
      const { count } = await supabase
        .from("qhse_records")
        .select("id", { count: "exact", head: true })
        .eq("kind", input.kind);
      const reference = `${cfg?.prefix ?? "REF"}-${String((count ?? 0) + 1).padStart(3, "0")}`;
      const { error } = await supabase.from("qhse_records").insert({
        company_id: u.id,
        kind: input.kind,
        reference,
        title: input.title,
        status: input.status,
        data: { ...input.data, _history: [entry(u.email, "Création", { to: input.status })] } as Json,
      });
      if (error) throw error;
    },
    onSuccess: (_d, v) => {
      qc.invalidateQueries({ queryKey: KEY });
      if (!v.silent) toast.success(v.id ? "Modifications enregistrées" : "Élément créé");
    },
    onError: () => toast.error("L'enregistrement a échoué. Vos saisies sont conservées, réessayez."),
  });
}

function bump(v: unknown): string {
  const n = parseFloat(String(v ?? "1"));
  return Number.isFinite(n) ? `${Math.floor(n) + 1}.0` : "2.0";
}

/** Runs a workflow button: status change + optional comment/date/number + history. */
export function useTransition() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ record, t, comment, date, number }: { record: QRecord; t: Transition; comment?: string; date?: string; number?: number }) => {
      const u = await me();
      const data: Record<string, unknown> = { ...record.data };
      if (t.date && date) data[t.date.key] = date;
      if (t.number && number !== undefined) {
        data[t.number.key] = number;
        const series = Array.isArray(data["_values"]) ? (data["_values"] as unknown[]) : [];
        data["_values"] = [...series, { at: new Date().toISOString(), key: t.number.key, value: number }];
      }
      if (t.bumpVersion) data["version"] = bump(data["version"]);
      const extra = [comment, t.date && date ? `${t.date.label} : ${new Date(date).toLocaleDateString("fr-FR")}` : "", t.number && number !== undefined ? `${t.number.label} : ${number}` : ""].filter(Boolean).join(" — ");
      data["_history"] = [...historyOf(record), entry(u.email, t.label, { from: record.status, ...(t.to ? { to: t.to } : {}), ...(extra ? { comment: extra } : {}) })];
      await update(record.id, { status: t.to ?? record.status, data });
    },
    onSuccess: (_d, v) => {
      qc.invalidateQueries({ queryKey: KEY });
      toast.success(v.t.to ? `${v.t.label} : statut « ${v.t.to} »` : `${v.t.label} : enregistré`);
    },
    onError: () => toast.error("L'opération a échoué. Réessayez."),
  });
}

/** Adds an observation / proof without changing the content. */
export function useAddNote() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ record, label, comment }: { record: QRecord; label: string; comment: string }) => {
      const u = await me();
      await update(record.id, { data: { ...record.data, _history: [...historyOf(record), entry(u.email, label, { comment })] } });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY });
      toast.success("Observation enregistrée");
    },
    onError: () => toast.error("Impossible d'enregistrer l'observation."),
  });
}

export function useSetStatus() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ record, status }: { record: QRecord; status: string }) => {
      const u = await me();
      await update(record.id, {
        status,
        data: { ...record.data, _history: [...historyOf(record), entry(u.email, "Changement de statut", { from: record.status, to: status })] },
      });
    },
    onSuccess: (_d, v) => {
      qc.invalidateQueries({ queryKey: KEY });
      toast.success(`Statut : ${v.status}`);
    },
    onError: () => toast.error("Impossible de changer le statut."),
  });
}

export function useDeleteRecord() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: string) => {
      const { error } = await supabase.from("qhse_records").delete().eq("id", id);
      if (error) throw error;
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY });
      toast.success("Élément supprimé");
    },
    onError: () => toast.error("Suppression impossible."),
  });
}

export function dueOf(r: QRecord): string | undefined {
  const k = KINDS[r.kind]?.due;
  const v = (k ? r.data[k] : undefined) ?? r.data["due_date"];
  return v ? String(v) : undefined;
}

export function isDone(r: QRecord): boolean {
  const done = KINDS[r.kind]?.done ?? ["Terminée", "Vérifiée", "Clôturée", "Clôturé", "Atteint", "Archivé", "Publié"];
  return done.includes(r.status) || r.status === "Archivé";
}

export function isOverdue(r: QRecord): boolean {
  const due = dueOf(r);
  if (!due || isDone(r)) return false;
  return new Date(due) < new Date(new Date().toDateString());
}
