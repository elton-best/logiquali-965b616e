import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";
import { flattenResource } from "@/integrations/backend/context";

/**
 * Alignement front React sur le backend Laravel `feat/modular-backend-iso-exports` :
 * - DUERP dynamique (familles, échelles, unités de travail, tree, submit/reject/approve)
 * - Accidents du travail SST + statistiques + clôture
 * - Situations d'urgence + enregistrement d'exercice (drill)
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
