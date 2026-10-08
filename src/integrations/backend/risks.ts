import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";
import { flattenResource } from "@/integrations/backend/context";

export const RISKS_KEY = ["organisation-risks-opportunities"] as const;
export const USERS_KEY = ["organisation-users"] as const;

type CollectionPayload = { data?: unknown };

function collection(payload: unknown): unknown[] {
  if (Array.isArray(payload)) return payload;
  if (!payload || typeof payload !== "object") return [];
  const root = payload as CollectionPayload;
  if (Array.isArray(root.data)) return root.data;
  if (root.data && typeof root.data === "object") {
    const nested = root.data as CollectionPayload;
    if (Array.isArray(nested.data)) return nested.data;
  }
  return [];
}

export async function fetchRisks(siteId: string) {
  const payload = await backendApi.request<unknown>(`risks-opportunities?site_id=${encodeURIComponent(siteId)}`);
  return collection(payload).map(flattenResource);
}

export async function fetchSiteUsers(siteId: string) {
  const payload = await backendApi.request<unknown>(`users?site_id=${encodeURIComponent(siteId)}&per_page=300`);
  return collection(payload).map(flattenResource);
}

export function useRisks(siteId: string) {
  return useQuery({
    queryKey: [...RISKS_KEY, siteId],
    queryFn: () => fetchRisks(siteId),
    enabled: Boolean(siteId),
    staleTime: 20_000,
  });
}

export function useSiteUsers(siteId: string) {
  return useQuery({
    queryKey: [...USERS_KEY, siteId],
    queryFn: () => fetchSiteUsers(siteId),
    enabled: Boolean(siteId),
    staleTime: 60_000,
  });
}

export function useRiskMutations(siteId: string) {
  const queryClient = useQueryClient();
  const invalidate = () => queryClient.invalidateQueries({ queryKey: [...RISKS_KEY, siteId] });

  return {
    save: useMutation({
      mutationFn: async (input: { id?: string; processId: string; payload: Record<string, unknown> }) => {
        const path = input.id ? `risks-opportunities/${input.id}` : `processes/${input.processId}/risks-opportunities`;
        return backendApi.request(path, {
          method: input.id ? "PUT" : "POST",
          body: JSON.stringify(input.payload),
        });
      },
      onSuccess: invalidate,
    }),
    remove: useMutation({
      mutationFn: (id: string) => backendApi.request(`risks-opportunities/${id}`, { method: "DELETE" }),
      onSuccess: invalidate,
    }),
  };
}
