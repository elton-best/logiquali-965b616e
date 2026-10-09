import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";
import { flattenResource } from "@/integrations/backend/context";

/**
 * Workflow SM / Planification aligné sur `feat/modular-backend-iso-exports` :
 * - modifications : submit-verification -> verify-rq -> approve-ceo -> record-results
 * - actions : my-actions (vue collaborateur)
 */

export const MODIFICATIONS_KEY = ["sm-modifications"] as const;
export const MY_ACTIONS_KEY = ["my-actions"] as const;

function listOf(payload: unknown): Record<string, unknown>[] {
  const root =
    payload && typeof payload === "object" ? (payload as Record<string, unknown>) : {};
  const data = root.data ?? payload;
  if (Array.isArray(data)) return data.map(flattenResource);
  return [];
}

export async function fetchMyActions(params?: { per_page?: number }) {
  const suffix = `?per_page=${params?.per_page ?? 100}`;
  return listOf(await backendApi.request<unknown>(`actions/my-actions${suffix}`));
}

export function useMyActions(params?: { per_page?: number }) {
  return useQuery({
    queryKey: [...MY_ACTIONS_KEY, params ?? {}],
    queryFn: () => fetchMyActions(params),
    staleTime: 20_000,
  });
}

export function useModificationWorkflow() {
  const client = useQueryClient();
  const invalidate = () => {
    client.invalidateQueries({ queryKey: MODIFICATIONS_KEY });
    client.invalidateQueries({ queryKey: ["qhse-records"] });
  };
  return {
    submit: useMutation({
      mutationFn: (id: string | number) =>
        backendApi.request(`modifications/${id}/submit-verification`, { method: "POST" }),
      onSuccess: invalidate,
    }),
    verifyRq: useMutation({
      mutationFn: (input: { id: string | number; comment?: string; decision?: string }) =>
        backendApi.request(`modifications/${input.id}/verify-rq`, {
          method: "POST",
          body: JSON.stringify({ comment: input.comment, decision: input.decision }),
        }),
      onSuccess: invalidate,
    }),
    approveCeo: useMutation({
      mutationFn: (input: { id: string | number; comment?: string; decision?: string }) =>
        backendApi.request(`modifications/${input.id}/approve-ceo`, {
          method: "POST",
          body: JSON.stringify({ comment: input.comment, decision: input.decision }),
        }),
      onSuccess: invalidate,
    }),
    recordResults: useMutation({
      mutationFn: (input: { id: string | number; payload: Record<string, unknown> }) =>
        backendApi.request(`modifications/${input.id}/record-results`, {
          method: "POST",
          body: JSON.stringify(input.payload),
        }),
      onSuccess: invalidate,
    }),
  };
}
