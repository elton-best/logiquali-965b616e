import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { supabase } from "@/integrations/supabase/client";
import { KINDS } from "@/components/app/sections";

export type QRecord = {
  id: string;
  kind: string;
  reference: string;
  title: string;
  status: string;
  data: Record<string, string | number | null>;
  created_at: string;
  updated_at: string;
};

const KEY = ["qhse-records"];

export function useRecords() {
  return useQuery({
    queryKey: KEY,
    queryFn: async () => {
      const { data, error } = await supabase
        .from("qhse_records")
        .select("id, kind, reference, title, status, data, created_at, updated_at")
        .order("created_at", { ascending: false })
        .limit(5000);
      if (error) throw error;
      return (data ?? []) as unknown as QRecord[];
    },
  });
}

export function useSaveRecord() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (input: { id?: string; kind: string; title: string; status: string; data: QRecord["data"] }) => {
      if (input.id) {
        const { error } = await supabase
          .from("qhse_records")
          .update({ title: input.title, status: input.status, data: input.data })
          .eq("id", input.id);
        if (error) throw error;
        return;
      }
      const cfg = KINDS[input.kind];
      const { count } = await supabase
        .from("qhse_records")
        .select("id", { count: "exact", head: true })
        .eq("kind", input.kind);
      const reference = `${cfg?.prefix ?? "REF"}-${String((count ?? 0) + 1).padStart(3, "0")}`;
      const { data: u } = await supabase.auth.getUser();
      const { error } = await supabase.from("qhse_records").insert({
        company_id: u.user?.id,
        kind: input.kind,
        reference,
        title: input.title,
        status: input.status,
        data: input.data,
      });
      if (error) throw error;
    },
    onSuccess: (_d, v) => {
      qc.invalidateQueries({ queryKey: KEY });
      toast.success(v.id ? "Modifications enregistrées" : "Élément créé");
    },
    onError: () => toast.error("L'enregistrement a échoué. Réessayez."),
  });
}

export function useSetStatus() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, status }: { id: string; status: string }) => {
      const { error } = await supabase.from("qhse_records").update({ status }).eq("id", id);
      if (error) throw error;
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

export function isOverdue(r: QRecord): boolean {
  const due = (r.data.due_date ?? r.data.review_date ?? r.data.next_maintenance) as string | undefined;
  if (!due) return false;
  const done = ["Terminée", "Vérifiée", "Clôturée", "Atteint", "Archivé"].includes(r.status);
  return !done && new Date(due) < new Date(new Date().toDateString());
}
