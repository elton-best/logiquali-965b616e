import { useQuery } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";

export const NORMS_KEY = ["enterprise-norms"] as const;

export type NormSection = {
  id: string | number;
  number?: string | null;
  title?: string | null;
  content?: string | null;
  children?: NormSection[];
  [key: string]: unknown;
};

export type EnterpriseNorm = {
  id: string | number;
  code: string;
  name: string;
  title?: string;
  domain?: string | null;
  status?: string | null;
  has_pdf_document?: boolean;
  pdf_file_url?: string | null;
  pdf_original_name?: string | null;
  currentVersion?: { version_code?: string | null; sections?: NormSection[] } | null;
  current_version?: { version_code?: string | null; sections?: NormSection[] } | null;
  versions?: Array<{ version_code?: string | null; sections?: NormSection[] }>;
  [key: string]: unknown;
};

type NormResponse = { data?: unknown; success?: boolean };

function catalogData(payload: NormResponse): Record<string, unknown> {
  if (payload.data && typeof payload.data === "object" && !Array.isArray(payload.data)) {
    return payload.data as Record<string, unknown>;
  }
  return payload as Record<string, unknown>;
}

function itemsFrom(payload: NormResponse): unknown[] {
  if (Array.isArray(payload.data)) return payload.data;
  const data = catalogData(payload);
  if (Array.isArray(data.norms)) return data.norms;
  if (Array.isArray(data.data)) return data.data;
  return [];
}

function normalizeNorm(item: unknown): EnterpriseNorm {
  const raw = item && typeof item === "object" ? (item as Record<string, unknown>) : {};
  const attributes =
    raw.attributes && typeof raw.attributes === "object"
      ? (raw.attributes as Record<string, unknown>)
      : raw;
  return {
    ...attributes,
    id: raw.id ?? attributes.id ?? "",
    code: String(attributes.code ?? ""),
    name: String(attributes.name ?? attributes.title ?? "Norme"),
    title: String(attributes.title ?? attributes.name ?? "Norme"),
    domain: attributes.domain == null ? null : String(attributes.domain),
    status: attributes.status == null ? null : String(attributes.status),
  } as EnterpriseNorm;
}

export async function fetchEnterpriseNorms(params?: {
  site_id?: string;
  search?: string;
  domain?: string;
}) {
  const query = new URLSearchParams();
  if (params?.site_id) query.set("site_id", params.site_id);
  const suffix = query.toString() ? `?${query.toString()}` : "";
  const payload = await backendApi.request<NormResponse>(`access/catalog${suffix}`);
  const data = catalogData(payload);
  const queryText = params?.search?.trim().toLowerCase();
  const queryDomain = params?.domain?.trim().toLowerCase();
  const norms = itemsFrom(payload)
    .map(normalizeNorm)
    .filter((norm) => {
      const matchesText =
        !queryText || `${norm.code} ${norm.name}`.toLowerCase().includes(queryText);
      const matchesDomain = !queryDomain || String(norm.domain ?? "").toLowerCase() === queryDomain;
      return matchesText && matchesDomain;
    });
  const meta =
    data.meta && typeof data.meta === "object" ? (data.meta as Record<string, unknown>) : {};
  return {
    norms,
    trialPeriod: Boolean(meta.is_trial ?? data.trial_period),
    trialDaysRemaining: Number(meta.days_remaining ?? data.trial_days_remaining ?? 0),
  };
}

export async function fetchNorm(id: string | number) {
  const payload = await backendApi.request<{ data?: unknown }>(`norms/${id}`);
  const value = payload.data ?? payload;
  return normalizeNorm(value);
}

export async function fetchNormChapters(id: string | number) {
  const payload = await backendApi.request<NormResponse>(`norms/${id}/chapters`);
  return itemsFrom(payload).map((item) => {
    const raw = item && typeof item === "object" ? (item as Record<string, unknown>) : {};
    const attrs =
      raw.attributes && typeof raw.attributes === "object"
        ? (raw.attributes as Record<string, unknown>)
        : raw;
    return {
      ...attrs,
      id: raw.id ?? attrs.id ?? "",
      number: attrs.number == null ? null : String(attrs.number),
      title: attrs.title == null ? null : String(attrs.title),
      content: attrs.content == null ? null : String(attrs.content),
    } as NormSection;
  });
}

export function useEnterpriseNorms(params?: {
  site_id?: string;
  search?: string;
  domain?: string;
}) {
  return useQuery({
    queryKey: [...NORMS_KEY, params ?? {}],
    queryFn: () => fetchEnterpriseNorms(params),
    staleTime: 60_000,
    retry: 1,
  });
}

export function useNorm(id: string | number | null) {
  return useQuery({
    queryKey: [...NORMS_KEY, "detail", id],
    queryFn: () => fetchNorm(id as string | number),
    enabled: id !== null,
    staleTime: 60_000,
    retry: 1,
  });
}

export function flattenNormSections(sections: NormSection[], output: NormSection[] = []) {
  sections.forEach((section) => {
    output.push(section);
    if (Array.isArray(section.children)) flattenNormSections(section.children, output);
  });
  return output;
}
