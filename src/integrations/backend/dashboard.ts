import { useQuery } from "@tanstack/react-query";
import { backendApi } from "@/integrations/backend/client";

export const DASHBOARD_STATS_KEY = ["backend-dashboard-stats"] as const;
export const COLLABORATOR_ACTIONS_KEY = ["backend-dashboard-collaborator-actions"] as const;

export type DashboardActivity = {
  id: string | number;
  type: string;
  name: string;
  created_at: string;
};

export type DashboardStats = {
  total_sites: number;
  total_users: number;
  total_processes: number;
  total_risks: number;
  total_actions: number;
  total_audits: number;
  total_non_conformities: number;
  active_non_conformities: number;
  overdue_actions: number;
  upcoming_audits: number;
};

export type DashboardLeadership = {
  policy_status: string;
  policy_last_update: string | null;
  total_employees: number;
  employees_with_job_description: number;
  organization_chart_updated: boolean;
};

export type DashboardCharts = {
  activity: Array<{ label: string; documents: number; actions: number }>;
  distribution: { documents: number; nc: number; audits: number; actions: number };
  performance: Array<{ label: string; objectives: number; actions: number }>;
};

export type DashboardPayload = {
  stats: DashboardStats;
  leadership: DashboardLeadership;
  charts: DashboardCharts;
  recent_activities: DashboardActivity[];
  scope?: { applied_scope: "enterprise" | "site"; applied_site_id: number | null };
};

export type CollaboratorAction = {
  id: string | number;
  title: string;
  status: string;
  progress: number;
  deadline?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  process?: { id: string | number; title?: string; code?: string } | null;
  site?: { id: string | number; name?: string } | null;
};

export type CollaboratorActionsPayload = {
  stats: {
    total_assigned: number;
    open_count: number;
    in_progress_count: number;
    overdue_count: number;
    due_soon_count: number;
    completed_count: number;
  };
  actions: CollaboratorAction[];
  by_process: Array<{
    process_id: string | number | null;
    process_label: string;
    process_code?: string | null;
    total_count: number;
    open_count: number;
    overdue_count: number;
  }>;
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

type DashboardResponse = {
  success?: boolean;
  data?: Partial<DashboardPayload>;
} & Partial<DashboardPayload>;

type CollaboratorResponse = {
  success?: boolean;
  data?: Partial<CollaboratorActionsPayload>;
} & Partial<CollaboratorActionsPayload>;

const numberOf = (value: unknown): number => {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
};

function normalizeStats(value: Partial<DashboardStats> | undefined): DashboardStats {
  return {
    total_sites: numberOf(value?.total_sites),
    total_users: numberOf(value?.total_users),
    total_processes: numberOf(value?.total_processes),
    total_risks: numberOf(value?.total_risks),
    total_actions: numberOf(value?.total_actions),
    total_audits: numberOf(value?.total_audits),
    total_non_conformities: numberOf(value?.total_non_conformities),
    active_non_conformities: numberOf(value?.active_non_conformities),
    overdue_actions: numberOf(value?.overdue_actions),
    upcoming_audits: numberOf(value?.upcoming_audits),
  };
}

function normalizeDashboard(payload: DashboardResponse): DashboardPayload {
  const data = payload.data && typeof payload.data === "object" ? payload.data : payload;
  const charts = data.charts ?? {};
  const rawDistribution = charts.distribution ?? {};
  return {
    stats: normalizeStats(data.stats),
    leadership: {
      policy_status: String(data.leadership?.policy_status ?? "not_created"),
      policy_last_update: data.leadership?.policy_last_update ?? null,
      total_employees: numberOf(data.leadership?.total_employees),
      employees_with_job_description: numberOf(data.leadership?.employees_with_job_description),
      organization_chart_updated: Boolean(data.leadership?.organization_chart_updated),
    },
    charts: {
      activity: Array.isArray(charts.activity)
        ? charts.activity.map((item) => ({
            label: String(item.label ?? ""),
            documents: numberOf(item.documents),
            actions: numberOf(item.actions),
          }))
        : [],
      distribution: {
        documents: numberOf(rawDistribution.documents),
        nc: numberOf(rawDistribution.nc),
        audits: numberOf(rawDistribution.audits),
        actions: numberOf(rawDistribution.actions),
      },
      performance: Array.isArray(charts.performance)
        ? charts.performance.map((item) => ({
            label: String(item.label ?? ""),
            objectives: numberOf(item.objectives),
            actions: numberOf(item.actions),
          }))
        : [],
    },
    recent_activities: Array.isArray(data.recent_activities)
      ? data.recent_activities.map((item) => ({
          id: item.id ?? "activity",
          type: String(item.type ?? "activity"),
          name: String(item.name ?? "Activité"),
          created_at: String(item.created_at ?? ""),
        }))
      : [],
    scope: data.scope,
  };
}

function normalizeCollaboratorActions(payload: CollaboratorResponse): CollaboratorActionsPayload {
  const data = payload.data && typeof payload.data === "object" ? payload.data : payload;
  const stats = data.stats ?? {};
  return {
    stats: {
      total_assigned: numberOf(stats.total_assigned),
      open_count: numberOf(stats.open_count),
      in_progress_count: numberOf(stats.in_progress_count),
      overdue_count: numberOf(stats.overdue_count),
      due_soon_count: numberOf(stats.due_soon_count),
      completed_count: numberOf(stats.completed_count),
    },
    actions: Array.isArray(data.actions) ? data.actions : [],
    by_process: Array.isArray(data.by_process)
      ? data.by_process.map((item) => ({
          process_id: item.process_id ?? null,
          process_label: String(item.process_label ?? "Sans processus"),
          process_code: item.process_code == null ? null : String(item.process_code),
          total_count: numberOf(item.total_count),
          open_count: numberOf(item.open_count),
          overdue_count: numberOf(item.overdue_count),
        }))
      : [],
    pagination: data.pagination,
  };
}

export async function fetchDashboardStats(params?: {
  site_id?: string;
  scope?: "enterprise" | "site";
}) {
  const query = new URLSearchParams();
  if (params?.site_id) query.set("site_id", params.site_id);
  if (params?.scope) query.set("scope", params.scope);
  const suffix = query.toString() ? `?${query.toString()}` : "";
  const payload = await backendApi.request<DashboardResponse>(`dashboard/stats${suffix}`);
  return normalizeDashboard(payload);
}

export async function fetchCollaboratorActions(params?: {
  site_id?: string;
  scope?: "enterprise" | "site";
  include_closed?: boolean;
  per_page?: number;
}) {
  const query = new URLSearchParams();
  if (params?.site_id) query.set("site_id", params.site_id);
  if (params?.scope) query.set("scope", params.scope);
  if (params?.include_closed !== undefined)
    query.set("include_closed", String(params.include_closed));
  if (params?.per_page) query.set("per_page", String(params.per_page));
  const suffix = query.toString() ? `?${query.toString()}` : "";
  const payload = await backendApi.request<CollaboratorResponse>(
    `dashboard/collaborator-actions${suffix}`,
  );
  return normalizeCollaboratorActions(payload);
}

export function useDashboardStats(params?: { site_id?: string; scope?: "enterprise" | "site" }) {
  return useQuery({
    queryKey: [...DASHBOARD_STATS_KEY, params ?? {}],
    queryFn: () => fetchDashboardStats(params),
    staleTime: 30_000,
    retry: 1,
  });
}

export function useCollaboratorActions(params?: {
  site_id?: string;
  scope?: "enterprise" | "site";
  include_closed?: boolean;
  per_page?: number;
}) {
  return useQuery({
    queryKey: [...COLLABORATOR_ACTIONS_KEY, params ?? {}],
    queryFn: () => fetchCollaboratorActions(params),
    staleTime: 20_000,
    retry: 1,
  });
}
