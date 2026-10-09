import { useQuery } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";

/**
 * Guides d'utilisation & matrices méthodologiques (backend `feat/modular-backend-iso-exports`).
 * Sections : risks, duerp, aes, process_reviews.
 * Routes : methodology-guides, methodology-guides/{section},
 *          methodology/matrices, methodology/matrices/{section}.
 */

export const METHODOLOGY_KEY = ["methodology-guides"] as const;

export type MethodologyGuide = {
  section?: string;
  title?: string;
  content?: unknown;
  matrix?: unknown;
  [key: string]: unknown;
};

function dataOf<T>(payload: unknown): T {
  const root =
    payload && typeof payload === "object" ? (payload as Record<string, unknown>) : {};
  return ((root.data ?? payload) as T);
}

export async function fetchMethodologyGuides() {
  return dataOf<Record<string, MethodologyGuide>>(
    await backendApi.request<unknown>("methodology-guides"),
  );
}

export async function fetchMethodologyGuide(section: string) {
  return dataOf<MethodologyGuide>(
    await backendApi.request<unknown>(
      `methodology-guides/${encodeURIComponent(section)}`,
    ),
  );
}

export async function fetchMethodologyMatrices() {
  return dataOf<Record<string, unknown>>(
    await backendApi.request<unknown>("methodology/matrices"),
  );
}

export async function fetchMethodologyMatrix(section: string) {
  return dataOf<unknown>(
    await backendApi.request<unknown>(
      `methodology/matrices/${encodeURIComponent(section)}`,
    ),
  );
}

export function useMethodologyGuides() {
  return useQuery({
    queryKey: METHODOLOGY_KEY,
    queryFn: fetchMethodologyGuides,
    staleTime: 60_000,
    retry: 1,
  });
}

export function useMethodologyGuide(section: string | null) {
  return useQuery({
    queryKey: [...METHODOLOGY_KEY, section],
    queryFn: () => fetchMethodologyGuide(section as string),
    enabled: Boolean(section),
    staleTime: 60_000,
    retry: 1,
  });
}

export function useMethodologyMatrix(section: string | null) {
  return useQuery({
    queryKey: [...METHODOLOGY_KEY, "matrix", section],
    queryFn: () => fetchMethodologyMatrix(section as string),
    enabled: Boolean(section),
    staleTime: 60_000,
    retry: 1,
  });
}
