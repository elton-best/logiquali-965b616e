import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";
import { flattenResource } from "@/integrations/backend/context";

/**
 * Alignement front React sur le backend Laravel `feat/modular-backend-iso-exports` :
 * - DUERP dynamique (familles, échelles, unités de travail, tree, submit/reject/approve)
 * - Accidents du travail SST + statistiques + clôture
 * - Situations d'urgence + enregistrement d'exercice (drill)
 * - Habilitations (stats, expires-soon, renew, export) — porté du frontend Vue
 */

export const DUERP_FAMILIES_KEY = ["duerp-risk-families"] as const;
export const DUERP_SCALES_KEY = ["duerp-scoring-scales"] as const;
export const DUERP_UNITS_KEY = ["duerp-work-units"] as const;
export const WORK_ACCIDENTS_KEY = ["work-accidents"] as const;
export const EMERGENCY_KEY = ["emergency-procedures"] as const;

function listOf(payload: unknown): Record<string, unknown>[] {
  const root =
    payload && typeof payload === "object" ? (payload as Record<string, unknown>) : {};
  const data = root.data ?? payload;
  if (Array.isArray(data)) return data.map(flattenResource);
  if (data && typeof data === "object") {
    const nested = (data as Record<string, unknown>).data;
    if (Array.isArray(nested)) return nested.map(flattenResource);
  }
  return [];
}

function dataOf<T>(payload: unknown): T {
  const root =
    payload && typeof payload === "object" ? (payload as Record<string, unknown>) : {};
  return ((root.data ?? payload) as T);
}

// --- Familles de risques DUERP ---
export async function fetchDuerpRiskFamilies() {
  return listOf(await backendApi.request<unknown>("duerp-risk-families"));
}

export function useDuerpRiskFamilies() {
  return useQuery({
    queryKey: DUERP_FAMILIES_KEY,
    queryFn: fetchDuerpRiskFamilies,
    staleTime: 30_000,
  });
}

export function useDuerpRiskFamilyMutations() {
  const client = useQueryClient();
  const invalidate = () => client.invalidateQueries({ queryKey: DUERP_FAMILIES_KEY });
  return {
    save: useMutation({
      mutationFn: (input: { id?: string | number; payload: Record<string, unknown> }) =>
        backendApi.request(
          input.id ? `duerp-risk-families/${input.id}` : "duerp-risk-families",
          { method: input.id ? "PUT" : "POST", body: JSON.stringify(input.payload) },
        ),
      onSuccess: invalidate,
    }),
    remove: useMutation({
      mutationFn: (id: string | number) =>
        backendApi.request(`duerp-risk-families/${id}`, { method: "DELETE" }),
      onSuccess: invalidate,
    }),
  };
}

// --- Échelles de cotation (gravité / fréquence / maîtrise) ---
export async function fetchDuerpScoringScales() {
  return dataOf<Record<string, unknown>>(
    await backendApi.request<unknown>("duerp-scoring-scales"),
  );
}

export function useDuerpScoringScales() {
  return useQuery({
    queryKey: DUERP_SCALES_KEY,
    queryFn: fetchDuerpScoringScales,
    staleTime: 30_000,
  });
}

export function useDuerpScoringScalesMutation() {
  const client = useQueryClient();
  return useMutation({
    mutationFn: (payload: Record<string, unknown>) =>
      backendApi.request("duerp-scoring-scales", {
        method: "POST",
        body: JSON.stringify(payload),
      }),
    onSuccess: () => client.invalidateQueries({ queryKey: DUERP_SCALES_KEY }),
  });
}

// --- Unités de travail ---
export async function fetchDuerpWorkUnits(params?: { site_id?: string }) {
  const suffix = params?.site_id ? `?site_id=${encodeURIComponent(params.site_id)}` : "";
  return listOf(await backendApi.request<unknown>(`duerp-work-units${suffix}`));
}

export function useDuerpWorkUnits(params?: { site_id?: string }) {
  return useQuery({
    queryKey: [...DUERP_UNITS_KEY, params ?? {}],
    queryFn: () => fetchDuerpWorkUnits(params),
    staleTime: 20_000,
  });
}

export function useDuerpWorkUnitMutations() {
  const client = useQueryClient();
  const invalidate = () => client.invalidateQueries({ queryKey: DUERP_UNITS_KEY });
  return {
    save: useMutation({
      mutationFn: (input: { id?: string | number; payload: Record<string, unknown> }) =>
        backendApi.request(input.id ? `duerp-work-units/${input.id}` : "duerp-work-units", {
          method: input.id ? "PUT" : "POST",
          body: JSON.stringify(input.payload),
        }),
      onSuccess: invalidate,
    }),
    remove: useMutation({
      mutationFn: (id: string | number) =>
        backendApi.request(`duerp-work-units/${id}`, { method: "DELETE" }),
      onSuccess: invalidate,
    }),
  };
}

// --- Workflow DUERP (tree / submit / reject / approve) ---
export async function fetchDuerpTree(id: string | number) {
  return dataOf<unknown>(await backendApi.request<unknown>(`duerp/${id}/tree`));
}

export function useDuerpWorkflowMutations() {
  const client = useQueryClient();
  const invalidate = () => {
    client.invalidateQueries({ queryKey: ["qhse-records"] });
    client.invalidateQueries({ queryKey: DUERP_UNITS_KEY });
  };
  return {
    submit: useMutation({
      mutationFn: (id: string | number) =>
        backendApi.request(`duerp/${id}/submit-verification`, { method: "POST" }),
      onSuccess: invalidate,
    }),
    reject: useMutation({
      mutationFn: (input: { id: string | number; comment?: string }) =>
        backendApi.request(`duerp/${input.id}/reject`, {
          method: "POST",
          body: JSON.stringify({ comment: input.comment }),
        }),
      onSuccess: invalidate,
    }),
    approve: useMutation({
      mutationFn: (input: { id: string | number; comment?: string }) =>
        backendApi.request(`duerp/${input.id}/approve`, {
          method: "POST",
          body: JSON.stringify({ comment: input.comment }),
        }),
      onSuccess: invalidate,
    }),
  };
}

// --- Accidents du travail (ISO 45001) ---
export async function fetchWorkAccidents(params?: { per_page?: number }) {
  const suffix = `?per_page=${params?.per_page ?? 100}`;
  return listOf(await backendApi.request<unknown>(`work-accidents${suffix}`));
}

export async function fetchWorkAccidentStatistics(params?: { site_id?: string }) {
  const suffix = params?.site_id ? `?site_id=${encodeURIComponent(params.site_id)}` : "";
  return dataOf<Record<string, unknown>>(
    await backendApi.request<unknown>(`work-accidents/statistics${suffix}`),
  );
}

export function useWorkAccidents(params?: { per_page?: number }) {
  return useQuery({
    queryKey: [...WORK_ACCIDENTS_KEY, params ?? {}],
    queryFn: () => fetchWorkAccidents(params),
    staleTime: 20_000,
  });
}

export function useWorkAccidentMutations() {
  const client = useQueryClient();
  const invalidate = () => client.invalidateQueries({ queryKey: WORK_ACCIDENTS_KEY });
  return {
    save: useMutation({
      mutationFn: (input: { id?: string | number; payload: Record<string, unknown> }) =>
        backendApi.request(input.id ? `work-accidents/${input.id}` : "work-accidents", {
          method: input.id ? "PUT" : "POST",
          body: JSON.stringify(input.payload),
        }),
      onSuccess: invalidate,
    }),
    close: useMutation({
      mutationFn: (input: { id: string | number; payload?: Record<string, unknown> }) =>
        backendApi.request(`work-accidents/${input.id}/close`, {
          method: "POST",
          body: JSON.stringify(input.payload ?? {}),
        }),
      onSuccess: invalidate,
    }),
    remove: useMutation({
      mutationFn: (id: string | number) =>
        backendApi.request(`work-accidents/${id}`, { method: "DELETE" }),
      onSuccess: invalidate,
    }),
  };
}

// --- Situations d'urgence / exercices ---
export async function fetchEmergencyProcedures(params?: { per_page?: number }) {
  const suffix = `?per_page=${params?.per_page ?? 100}`;
  return listOf(await backendApi.request<unknown>(`emergency-procedures${suffix}`));
}

export function useEmergencyProcedures(params?: { per_page?: number }) {
  return useQuery({
    queryKey: [...EMERGENCY_KEY, params ?? {}],
    queryFn: () => fetchEmergencyProcedures(params),
    staleTime: 20_000,
  });
}

export function useEmergencyProcedureMutations() {
  const client = useQueryClient();
  const invalidate = () => client.invalidateQueries({ queryKey: EMERGENCY_KEY });
  return {
    save: useMutation({
      mutationFn: (input: { id?: string | number; payload: Record<string, unknown> }) =>
        backendApi.request(
          input.id ? `emergency-procedures/${input.id}` : "emergency-procedures",
          { method: input.id ? "PUT" : "POST", body: JSON.stringify(input.payload) },
        ),
      onSuccess: invalidate,
    }),
    recordDrill: useMutation({
      mutationFn: (input: { id: string | number; payload: Record<string, unknown> }) =>
        backendApi.request(`emergency-procedures/${input.id}/record-drill`, {
          method: "POST",
          body: JSON.stringify(input.payload),
        }),
      onSuccess: invalidate,
    }),
    remove: useMutation({
      mutationFn: (id: string | number) =>
        backendApi.request(`emergency-procedures/${id}`, { method: "DELETE" }),
      onSuccess: invalidate,
    }),
  };
}

// --- Habilitations (porté du frontend Vue : HabilitationsView + habilitationService) ---
export const HABILITATIONS_KEY = ["habilitations"] as const;
export const HABILITATIONS_STATS_KEY = ["habilitations-stats"] as const;

export type HabilitationStats = {
  total: number;
  active: number;
  expired: number;
  expiring_soon: number;
  expiring_critical: number;
  by_type: Record<string, number>;
};

export async function fetchHabilitations(params?: { per_page?: number; status?: string }) {
  const query = new URLSearchParams();
  query.set("per_page", String(params?.per_page ?? 100));
  if (params?.status) query.set("status", params.status);
  const payload = await backendApi.request<unknown>(`habilitations?${query.toString()}`);
  const root =
    payload && typeof payload === "object" ? (payload as Record<string, unknown>) : {};
  const data = (root.data ?? payload) as unknown;
  // Laravel paginate: { data: [...] } — collection directe sinon
  const items = Array.isArray(data)
    ? data
    : Array.isArray((data as Record<string, unknown>)?.data)
      ? ((data as Record<string, unknown>).data as unknown[])
      : [];
  return items.map(flattenResource);
}

export async function fetchHabilitationsStats() {
  const payload = await backendApi.request<{ data?: HabilitationStats } & HabilitationStats>(
    "habilitations/stats",
  );
  const data = (payload.data ?? payload) as HabilitationStats;
  return {
    total: Number(data.total ?? 0),
    active: Number(data.active ?? 0),
    expired: Number(data.expired ?? 0),
    expiring_soon: Number(data.expiring_soon ?? 0),
    expiring_critical: Number(data.expiring_critical ?? 0),
    by_type: (data.by_type ?? {}) as Record<string, number>,
  } satisfies HabilitationStats;
}

export async function fetchHabilitationsExpiring(days = 30) {
  return listOf(
    await backendApi.request<unknown>(`habilitations/expires-soon?days=${days}`),
  );
}

export function useHabilitations(params?: { per_page?: number; status?: string }) {
  return useQuery({
    queryKey: [...HABILITATIONS_KEY, params ?? {}],
    queryFn: () => fetchHabilitations(params),
    staleTime: 20_000,
  });
}

export function useHabilitationsStats() {
  return useQuery({
    queryKey: HABILITATIONS_STATS_KEY,
    queryFn: fetchHabilitationsStats,
    staleTime: 30_000,
    retry: 1,
  });
}

export function useHabilitationMutations() {
  const client = useQueryClient();
  const invalidate = () => {
    client.invalidateQueries({ queryKey: HABILITATIONS_KEY });
    client.invalidateQueries({ queryKey: HABILITATIONS_STATS_KEY });
    client.invalidateQueries({ queryKey: ["qhse-records"] });
  };
  return {
    save: useMutation({
      mutationFn: (input: { id?: string | number; payload: Record<string, unknown> }) =>
        backendApi.request(input.id ? `habilitations/${input.id}` : "habilitations", {
          method: input.id ? "PUT" : "POST",
          body: JSON.stringify(input.payload),
        }),
      onSuccess: invalidate,
    }),
    renew: useMutation({
      mutationFn: (input: { id: string | number; payload: Record<string, unknown> }) =>
        backendApi.request(`habilitations/${input.id}/renew`, {
          method: "POST",
          body: JSON.stringify(input.payload),
        }),
      onSuccess: invalidate,
    }),
    remove: useMutation({
      mutationFn: (id: string | number) =>
        backendApi.request(`habilitations/${id}`, { method: "DELETE" }),
      onSuccess: invalidate,
    }),
  };
}

export function downloadHabilitationsExport(rows: string[][]) {
  const filename = `habilitations_${new Date().toISOString().slice(0, 10)}.csv`;
  const csv = rows
    .map((row) => row.map((cell) => `"${String(cell ?? "").replace(/"/g, '""')}"`).join(";"))
    .join("\n");
  const blob = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}
