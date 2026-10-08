import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";

export const CONTEXT_KEY = ["organisation-context"] as const;
export const STAKEHOLDERS_KEY = ["organisation-stakeholders"] as const;
export const SCOPE_KEY = ["organisation-scope"] as const;
export const PROCESSES_KEY = ["organisation-processes"] as const;
export const CARTOGRAPHY_KEY = ["organisation-cartography"] as const;

function objectOf(value: unknown): Record<string, unknown> {
  return value && typeof value === "object" && !Array.isArray(value) ? value as Record<string, unknown> : {};
}

function collection(payload: unknown): unknown[] {
  if (Array.isArray(payload)) return payload;
  const root = objectOf(payload);
  if (Array.isArray(root.data)) return root.data;
  const data = objectOf(root.data);
  return Array.isArray(data.data) ? data.data : [];
}

export function flattenResource(item: unknown): Record<string, unknown> {
  const raw = objectOf(item);
  const attributes = raw.attributes && typeof raw.attributes === "object" ? raw.attributes as Record<string, unknown> : raw;
  return { ...attributes, id: raw.id ?? attributes.id };
}

async function fetchResources(endpoint: string, params: Record<string, string | undefined>) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => { if (value) query.set(key, value); });
  const suffix = query.toString() ? `?${query.toString()}` : "";
  const payload = await backendApi.request<unknown>(`${endpoint}${suffix}`);
  return collection(payload).map(flattenResource);
}

export async function fetchContexts(siteId: string) {
  return fetchResources("contexts", { site_id: siteId, type: "swot_pestel" });
}

export async function fetchStakeholders(siteId: string) {
  return fetchResources("stakeholders", { site_id: siteId, per_page: "100" });
}

export async function fetchScopes(siteId: string) {
  return fetchResources("application-scopes", { site_id: siteId, is_current: "true" });
}

export async function fetchProcesses(siteId: string) {
  return fetchResources("processes", { site_id: siteId, per_page: "100" });
}

export async function fetchCartography(siteId: string) {
  const payload = await backendApi.request<unknown>(`processes-cartography?site_id=${encodeURIComponent(siteId)}`);
  const root = objectOf(payload);
  const data = objectOf(root.data);
  return Object.entries(data).flatMap(([category, items]) =>
    Array.isArray(items) ? items.map((item) => ({ ...flattenResource(item), category })) : [],
  );
}

export function useContexts(siteId: string) {
  return useQuery({
    queryKey: [...CONTEXT_KEY, siteId],
    queryFn: () => fetchContexts(siteId),
    enabled: Boolean(siteId),
    staleTime: 20_000,
  });
}

export function useStakeholders(siteId: string) {
  return useQuery({
    queryKey: [...STAKEHOLDERS_KEY, siteId],
    queryFn: () => fetchStakeholders(siteId),
    enabled: Boolean(siteId),
    staleTime: 20_000,
  });
}

export function useScope(siteId: string) {
  return useQuery({
    queryKey: [...SCOPE_KEY, siteId],
    queryFn: () => fetchScopes(siteId),
    enabled: Boolean(siteId),
    staleTime: 20_000,
  });
}

export function useProcesses(siteId: string) {
  return useQuery({
    queryKey: [...PROCESSES_KEY, siteId],
    queryFn: () => fetchProcesses(siteId),
    enabled: Boolean(siteId),
    staleTime: 20_000,
  });
}

export function useCartography(siteId: string) {
  return useQuery({
    queryKey: [...CARTOGRAPHY_KEY, siteId],
    queryFn: () => fetchCartography(siteId),
    enabled: Boolean(siteId),
    staleTime: 20_000,
  });
}

export function useContextMutations(siteId: string) {
  const queryClient = useQueryClient();
  const invalidate = () => queryClient.invalidateQueries({ queryKey: [...CONTEXT_KEY, siteId] });
  return {
    save: useMutation({
      mutationFn: async (input: { id?: string; payload: Record<string, unknown> }) => {
        const path = input.id ? `contexts/${input.id}` : "contexts";
        return backendApi.request(path, {
          method: input.id ? "PUT" : "POST",
          body: JSON.stringify(input.payload),
        });
      },
      onSuccess: invalidate,
    }),
  };
}

export function useStakeholderMutations(siteId: string) {
  const queryClient = useQueryClient();
  const invalidate = () => queryClient.invalidateQueries({ queryKey: [...STAKEHOLDERS_KEY, siteId] });
  return {
    save: useMutation({
      mutationFn: async (input: { id?: string; payload: Record<string, unknown> }) => {
        const path = input.id ? `stakeholders/${input.id}` : "stakeholders";
        return backendApi.request(path, {
          method: input.id ? "PUT" : "POST",
          body: JSON.stringify(input.payload),
        });
      },
      onSuccess: invalidate,
    }),
    remove: useMutation({
      mutationFn: (id: string) => backendApi.request(`stakeholders/${id}`, { method: "DELETE" }),
      onSuccess: invalidate,
    }),
  };
}

export function useScopeMutations(siteId: string) {
  const queryClient = useQueryClient();
  const invalidate = () => queryClient.invalidateQueries({ queryKey: [...SCOPE_KEY, siteId] });
  return {
    save: useMutation({
      mutationFn: async (input: { id?: string; payload: Record<string, unknown> }) => {
        const path = input.id ? `application-scopes/${input.id}` : "application-scopes";
        return backendApi.request(path, {
          method: input.id ? "PUT" : "POST",
          body: JSON.stringify(input.payload),
        });
      },
      onSuccess: invalidate,
    }),
  };
}
