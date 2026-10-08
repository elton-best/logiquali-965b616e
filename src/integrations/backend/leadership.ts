import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";
import { flattenResource } from "@/integrations/backend/context";

export const POLICY_KEY = ["qhse-policy"] as const;
export const ORG_CHART_KEY = ["org-chart"] as const;
export const JOB_DESCRIPTIONS_KEY = ["job-descriptions"] as const;
export const RESPONSIBILITIES_KEY = ["responsibilities"] as const;

export type QhsePolicy = {
  id?: string | number;
  site_id?: string | number | null;
  version?: string | null;
  is_current?: boolean;
  effective_date?: string | null;
  mission: string;
  vision?: string | null;
  values?: string[];
  axes?: string[];
  commitments?: string[];
  quality_policy?: string | null;
  environmental_policy?: string | null;
  health_safety_policy?: string | null;
  status?: string | null;
  updated_at?: string | null;
};

export type OrgChart = {
  id: string | number;
  file_name: string;
  file_path?: string | null;
  file_type?: string | null;
  file_size?: number | null;
  is_current?: boolean;
  download_url?: string | null;
  updated_at?: string | null;
};

function dataOf<T>(payload: unknown): T | null {
  if (!payload || typeof payload !== "object") return null;
  const root = payload as Record<string, unknown>;
  return (root.data ?? payload) as T;
}

function listOf(payload: unknown): Record<string, unknown>[] {
  const data = dataOf<unknown>(payload);
  if (!Array.isArray(data)) return [];
  return data.map(flattenResource);
}

export async function fetchCurrentPolicy(siteId: string) {
  const payload = await backendApi.request<unknown>(`qhse-policies/current?site_id=${encodeURIComponent(siteId)}`);
  return dataOf<QhsePolicy>(payload);
}

export async function fetchOrgChart(siteId: string) {
  const payload = await backendApi.request<unknown>(`org-chart/current?site_id=${encodeURIComponent(siteId)}`);
  return dataOf<OrgChart>(payload);
}

export async function fetchJobDescriptions() {
  const payload = await backendApi.request<unknown>("job-descriptions");
  return listOf(payload);
}

export async function fetchResponsibilities() {
  const payload = await backendApi.request<unknown>("responsibilities");
  return listOf(payload);
}

export function useCurrentPolicy(siteId: string) {
  return useQuery({ queryKey: [...POLICY_KEY, siteId], queryFn: () => fetchCurrentPolicy(siteId), enabled: Boolean(siteId), staleTime: 20_000 });
}

export function useOrgChart(siteId: string) {
  return useQuery({ queryKey: [...ORG_CHART_KEY, siteId], queryFn: () => fetchOrgChart(siteId), enabled: Boolean(siteId), staleTime: 20_000 });
}

export function useJobDescriptions(siteId: string) {
  return useQuery({ queryKey: [...JOB_DESCRIPTIONS_KEY, siteId], queryFn: fetchJobDescriptions, enabled: Boolean(siteId), staleTime: 20_000 });
}

export function useResponsibilities(siteId: string) {
  return useQuery({ queryKey: [...RESPONSIBILITIES_KEY, siteId], queryFn: fetchResponsibilities, enabled: Boolean(siteId), staleTime: 20_000 });
}

export function usePolicyMutations(siteId: string) {
  const client = useQueryClient();
  const invalidate = () => client.invalidateQueries({ queryKey: [...POLICY_KEY, siteId] });
  return {
    save: useMutation({
      mutationFn: (input: { id?: string; payload: Record<string, unknown> }) => backendApi.request(input.id ? `qhse-policies/${input.id}` : "qhse-policies", { method: input.id ? "PUT" : "POST", body: JSON.stringify(input.payload) }),
      onSuccess: invalidate,
    }),
    submit: useMutation({ mutationFn: (id: string) => backendApi.request(`qhse-policies/${id}/submit`, { method: "POST" }), onSuccess: invalidate }),
    validate: useMutation({ mutationFn: (id: string) => backendApi.request(`qhse-policies/${id}/validate`, { method: "POST" }), onSuccess: invalidate }),
  };
}

export function useOrgChartMutations(siteId: string) {
  const client = useQueryClient();
  const invalidate = () => client.invalidateQueries({ queryKey: [...ORG_CHART_KEY, siteId] });
  return {
    upload: useMutation({
      mutationFn: async (file: File) => { const body = new FormData(); body.append("file", file); body.append("site_id", siteId); return backendApi.request<OrgChart>("org-chart/upload", { method: "POST", body }); },
      onSuccess: invalidate,
    }),
    remove: useMutation({ mutationFn: (id: string) => backendApi.request(`org-chart/${id}`, { method: "DELETE" }), onSuccess: invalidate }),
  };
}

export function useJobDescriptionMutations(siteId: string) {
  const client = useQueryClient();
  const invalidate = () => client.invalidateQueries({ queryKey: [...JOB_DESCRIPTIONS_KEY, siteId] });
  return {
    save: useMutation({ mutationFn: (input: { id?: string; payload: Record<string, unknown> }) => backendApi.request(input.id ? `job-descriptions/${input.id}` : "job-descriptions", { method: input.id ? "PUT" : "POST", body: JSON.stringify(input.payload) }), onSuccess: invalidate }),
    remove: useMutation({ mutationFn: (id: string) => backendApi.request(`job-descriptions/${id}`, { method: "DELETE" }), onSuccess: invalidate }),
  };
}

export function useResponsibilityMutations(siteId: string) {
  const client = useQueryClient();
  const invalidate = () => client.invalidateQueries({ queryKey: [...RESPONSIBILITIES_KEY, siteId] });
  return {
    save: useMutation({ mutationFn: (input: { id?: string; payload: Record<string, unknown> }) => backendApi.request(input.id ? `responsibilities/${input.id}` : "responsibilities", { method: input.id ? "PUT" : "POST", body: JSON.stringify(input.payload) }), onSuccess: invalidate }),
    remove: useMutation({ mutationFn: (id: string) => backendApi.request(`responsibilities/${id}`, { method: "DELETE" }), onSuccess: invalidate }),
  };
}
