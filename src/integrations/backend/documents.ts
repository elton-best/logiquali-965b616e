import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";

export const PENDING_DOCUMENTS_KEY = ["pending-workflow-documents"] as const;

export type PendingDocument = {
  id: string;
  code: string;
  title: string;
  workflow_status: string;
  status: string;
  version: string;
  source_label: string;
  process_label: string;
  author_name: string;
  approver_name: string;
  created_at: string | null;
  metadata: Record<string, unknown>;
};

function objectOf(value: unknown): Record<string, unknown> {
  return value && typeof value === "object" && !Array.isArray(value)
    ? (value as Record<string, unknown>)
    : {};
}

function relationValue(value: unknown): Record<string, unknown> {
  const raw = objectOf(value);
  return raw.attributes && typeof raw.attributes === "object"
    ? (raw.attributes as Record<string, unknown>)
    : raw;
}

function collection(payload: unknown): unknown[] {
  if (Array.isArray(payload)) return payload;
  const root = objectOf(payload);
  if (Array.isArray(root.data)) return root.data;
  if (root.data && typeof root.data === "object") {
    const nested = objectOf(root.data);
    if (Array.isArray(nested.data)) return nested.data;
  }
  return [];
}

function normalizeDocument(item: unknown): PendingDocument | null {
  const raw = objectOf(item);
  const attrs =
    raw.attributes && typeof raw.attributes === "object"
      ? (raw.attributes as Record<string, unknown>)
      : raw;
  const id = raw.id ?? attrs.id;
  if (id == null) return null;
  const author = relationValue(attrs.author ?? raw.author);
  const approver = relationValue(attrs.approver ?? raw.approver);
  const process = relationValue(attrs.process ?? raw.process);
  const site = relationValue(attrs.site ?? raw.site);
  const source = [attrs.source_module, attrs.source_submodule, attrs.source_section]
    .filter(Boolean)
    .join(" > ");
  return {
    id: String(id),
    code: String(attrs.code ?? `DOC-${id}`),
    title: String(attrs.title ?? attrs.name ?? "Document sans titre"),
    workflow_status: String(attrs.workflow_status ?? attrs.status ?? ""),
    status: String(attrs.status ?? ""),
    version: String(attrs.version ?? "1.0"),
    source_label:
      source ||
      String(
        attrs.module_type
          ? `${attrs.module_type}${attrs.module_id ? ` #${attrs.module_id}` : ""}`
          : (attrs.source_type ?? "—"),
      ),
    process_label: String(
      process.code && process.title
        ? `${process.code} — ${process.title}`
        : (process.code ?? process.title ?? "—"),
    ),
    author_name: String(author.name ?? author.email ?? "—"),
    approver_name: String(approver.name ?? approver.email ?? "—"),
    created_at: attrs.created_at == null ? null : String(attrs.created_at),
    metadata: objectOf(attrs.metadata),
  };
}

export async function fetchPendingDocuments(
  workflowStatus: "pending_verification" | "pending_approval",
) {
  const payload = await backendApi.request<unknown>(
    `documents?workflow_status=${encodeURIComponent(workflowStatus)}`,
  );
  return collection(payload)
    .map(normalizeDocument)
    .filter((item): item is PendingDocument => item !== null);
}

export function usePendingDocuments(workflowStatus: "pending_verification" | "pending_approval") {
  return useQuery({
    queryKey: [...PENDING_DOCUMENTS_KEY, workflowStatus],
    queryFn: () => fetchPendingDocuments(workflowStatus),
    staleTime: 15_000,
    retry: 1,
  });
}

export function useDocumentWorkflow() {
  const queryClient = useQueryClient();
  const invalidate = () => queryClient.invalidateQueries({ queryKey: PENDING_DOCUMENTS_KEY });
  return {
    verify: useMutation({
      mutationFn: (id: string) => backendApi.request(`documents/${id}/verify`, { method: "POST" }),
      onSuccess: invalidate,
    }),
    approve: useMutation({
      mutationFn: (input: { id: string; effective_date?: string; comment?: string }) =>
        backendApi.request(`documents/${input.id}/approve`, {
          method: "POST",
          body: JSON.stringify({ effective_date: input.effective_date, comment: input.comment }),
        }),
      onSuccess: invalidate,
    }),
    reject: useMutation({
      mutationFn: (input: { id: string; rejection_reason: string }) =>
        backendApi.request(`documents/${input.id}/reject`, {
          method: "POST",
          body: JSON.stringify({ rejection_reason: input.rejection_reason }),
        }),
      onSuccess: invalidate,
    }),
  };
}
