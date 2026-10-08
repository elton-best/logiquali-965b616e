import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";
import { flattenResource } from "@/integrations/backend/context";

export const PROCESS_KEY = ["organisation-process"] as const;
export const PROCESSES_KEY = ["organisation-processes"] as const;

function resource(payload: unknown) {
  if (!payload || typeof payload !== "object") return {};
  const root = payload as Record<string, unknown>;
  return flattenResource(root.data ?? payload);
}

function arrayValue(value: unknown): unknown[] {
  if (Array.isArray(value)) return value;
  if (typeof value !== "string") return [];
  try { const parsed = JSON.parse(value); return Array.isArray(parsed) ? parsed : []; } catch { return []; }
}

export async function fetchProcess(id: string) {
  const payload = await backendApi.request<unknown>(`processes/${id}`);
  return resource(payload);
}

export function useProcess(id?: string) {
  return useQuery({
    queryKey: [...PROCESS_KEY, id ?? "new"],
    queryFn: () => fetchProcess(id!),
    enabled: Boolean(id),
    staleTime: 20_000,
  });
}

export function normalizeProcessSequences(value: unknown) {
  return arrayValue(value).map((item, index) => {
    const source = item && typeof item === "object" ? item as Record<string, unknown> : {};
    return {
      sequence_order: Number(source.sequence_order ?? source.order ?? index + 1),
      input_description: String(source.input_description ?? source.inputs ?? ""),
      activity_description: String(source.activity_description ?? source.activities ?? ""),
      output_description: String(source.output_description ?? source.outputs ?? ""),
      sub_activities: Array.isArray(source.sub_activities) ? source.sub_activities.map(String) : [],
      supplier_processes: Array.isArray(source.supplier_processes) ? source.supplier_processes.map(String) : [],
      client_processes: Array.isArray(source.client_processes) ? source.client_processes.map(String) : [],
    };
  });
}

export function useProcessMutations(siteId: string) {
  const client = useQueryClient();
  const invalidate = () => {
    void client.invalidateQueries({ queryKey: PROCESSES_KEY });
    void client.invalidateQueries({ queryKey: ["organisation-cartography", siteId] });
  };
  return {
    save: useMutation({
      mutationFn: (input: { id?: string; payload: Record<string, unknown> }) => backendApi.request(input.id ? `processes/${input.id}` : "processes", { method: input.id ? "PUT" : "POST", body: JSON.stringify(input.payload) }),
      onSuccess: invalidate,
    }),
    remove: useMutation({ mutationFn: (id: string) => backendApi.request(`processes/${id}`, { method: "DELETE" }), onSuccess: invalidate }),
  };
}
