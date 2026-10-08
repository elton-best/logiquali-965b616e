import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { BackendApiError, backendApi } from "@/integrations/backend/client";
import { KINDS, type Transition } from "@/components/app/sections";

export type HistoryEntry = {
  at: string;
  by: string;
  action: string;
  from?: string;
  to?: string;
  comment?: string;
};

export type QRecord = {
  id: string;
  kind: string;
  reference: string;
  title: string;
  status: string;
  data: Record<string, unknown>;
  created_at: string;
  updated_at: string;
};

export const RECORDS_KEY = ["qhse-records"];

/** Resource names exposed by the Laravel API. Unsupported UI kinds are kept out
 * of the request instead of silently writing to a second datastore. */
export const BACKEND_RESOURCES: Record<string, string> = {
  site: "sites",
  collaborator: "users",
  context: "contexts",
  party: "stakeholders",
  scope: "application-scopes",
  risk: "risks",
  objective: "objectives",
  action: "actions",
  process: "processes",
  document: "documents",
  nc: "non-conformities",
  complaint: "complaints",
  audit: "audits",
  indicator: "indicateurs",
  review: "management-reviews",
  communication: "communications",
  supplier: "provider-partners",
  obligation: "compliance-obligations",
  env_aspect: "aspects-environnementaux",
  emergency: "emergency-procedures",
  training: "formations",
  habilitation: "habilitations",
  equipment: "equipements",
  energy_use: "consommations-energie",
  work_unit: "duerp-work-units",
  duerp: "duerp",
  sm_change: "modifications",
  sm_plan: "plan-actions",
  suggestion: "improvement-suggestions",
  improvement: "improvement-suggestions",
};

const UI_STATUS: Record<string, Record<string, string>> = {
  action: {
    draft: "Brouillon",
    planned: "Planifiée",
    assigned: "Assignée",
    in_progress: "En cours",
    completed: "Terminée",
    verified: "Vérifiée",
    closed: "Clôturée",
    cancelled: "Annulée",
  },
  process: { draft: "Brouillon", active: "Actif", inactive: "Inactif" },
  document: {
    draft: "Brouillon",
    pending_verification: "En vérification",
    pending_approval: "En approbation",
    awaiting_submitter_confirmation: "À corriger",
    rejected: "À corriger",
    approved: "Publié",
    obsolete: "Archivé",
  },
  audit: {
    planned: "Planifié",
    in_progress: "En cours",
    report_draft: "En rapport",
    report_approved: "En rapport",
    completed: "Terminé",
    closed: "Terminé",
    cancelled: "Annulé",
  },
  nc: {
    open: "Brouillon",
    in_progress: "En analyse",
    analysis: "En analyse",
    corrective_action: "Validée",
    verification: "En vérification",
    verified: "En vérification",
    closed: "Clôturée",
  },
  objective: {
    draft: "Brouillon",
    planned: "Planifié",
    in_progress: "En cours",
    achieved: "Atteint",
    cancelled: "Annulé",
  },
};

const BACKEND_STATUS: Record<string, Record<string, string>> = {
  action: {
    Brouillon: "draft",
    Planifiée: "planned",
    Assignée: "assigned",
    "En cours": "in_progress",
    Terminée: "completed",
    Vérifiée: "verified",
    Clôturée: "closed",
    Annulée: "cancelled",
  },
  process: { Brouillon: "draft", Actif: "active", Inactif: "inactive" },
  document: {
    Brouillon: "draft",
    "En vérification": "pending_verification",
    "En approbation": "pending_approval",
    "À corriger": "rejected",
    Rejeté: "rejected",
    Publié: "approved",
    Archivé: "obsolete",
  },
  audit: {
    Planifié: "planned",
    "En cours": "in_progress",
    "En rapport": "report_draft",
    Terminé: "completed",
    Annulé: "cancelled",
  },
  nc: {
    Brouillon: "open",
    "En analyse": "analysis",
    Validée: "corrective_action",
    "En vérification": "verification",
    Clôturée: "closed",
  },
  objective: {
    Brouillon: "draft",
    Planifié: "planned",
    "En cours": "in_progress",
    Atteint: "achieved",
    Annulé: "cancelled",
  },
};

function asObject(value: unknown): Record<string, unknown> {
  return value && typeof value === "object" && !Array.isArray(value)
    ? (value as Record<string, unknown>)
    : {};
}

function flatten(item: unknown): Record<string, unknown> {
  const raw = asObject(item);
  if (raw.attributes && typeof raw.attributes === "object") {
    const relationships = asObject(raw.relationships);
    const relationValues: Record<string, unknown> = {};
    Object.entries(relationships).forEach(([key, value]) => {
      const rel = asObject(value);
      const relData = rel.data;
      if (Array.isArray(relData)) relationValues[key] = relData;
      else if (relData && typeof relData === "object") relationValues[key] = relData;
    });
    return { id: raw.id, ...asObject(raw.attributes), ...relationValues };
  }
  return raw;
}

function collection(payload: unknown): unknown[] {
  const root = asObject(payload);
  const data = root.data;
  if (Array.isArray(data)) return data;
  if (data && typeof data === "object" && Array.isArray((data as Record<string, unknown>).data))
    return (data as Record<string, unknown>).data as unknown[];
  return Array.isArray(payload) ? payload : [];
}

function uiStatus(kind: string, source: Record<string, unknown>): string {
  if (kind === "site" && typeof source.is_active === "boolean")
    return source.is_active ? "Actif" : "Suspendu";
  const value = String(
    kind === "document"
      ? source.workflow_status ?? source.status ?? source.state ?? ""
      : source.status ?? source.state ?? "",
  );
  return UI_STATUS[kind]?.[value] ?? value;
}

function backendStatus(kind: string, value: string): string {
  return BACKEND_STATUS[kind]?.[value] ?? value;
}

function toRecord(kind: string, item: unknown): QRecord {
  const source = flatten(item);
  const config = KINDS[kind];
  const id = String(source.id ?? source.uuid ?? `${kind}-${Math.random().toString(36).slice(2)}`);
  const reference = String(
    source.reference ?? source.ref ?? source.code ?? `${config?.prefix ?? "REF"}-${id}`,
  );
  const title = String(
    source.title ??
      source.name ??
      source.label ??
      source.description ??
      `${config?.singular ?? kind} ${reference}`,
  );
  const status = uiStatus(kind, source) || config?.statuses[0]?.value || "";
  const now = new Date().toISOString();
  const {
    id: _id,
    uuid: _uuid,
    reference: _reference,
    ref: _ref,
    code: _code,
    title: _title,
    name: _name,
    status: _status,
    state: _state,
    created_at: _created,
    updated_at: _updated,
    ...data
  } = source;
  if (kind === "site" && data.address === undefined) data.address = source.location;
  return {
    id,
    kind,
    reference,
    title,
    status,
    data,
    created_at: String(source.created_at ?? now),
    updated_at: String(source.updated_at ?? source.created_at ?? now),
  };
}

function sanitizePayload(
  kind: string,
  title: string,
  status: string,
  data: Record<string, unknown>,
): Record<string, unknown> {
  const payload: Record<string, unknown> = { ...data, title, status: backendStatus(kind, status) };
  [
    "_history",
    "reference",
    "ref",
    "created_at",
    "updated_at",
    "id",
    "uuid",
    "company_id",
    "target_company_id",
  ].forEach((key) => delete payload[key]);
  if (kind === "site") {
    payload.name = title;
    payload.location = payload.location ?? payload.address ?? title;
    payload.city = payload.city ?? "À renseigner";
    payload.is_active = status !== "Suspendu" && status !== "Archivé";
    delete payload.title;
  }
  if (kind === "collaborator") {
    const names = title.trim().split(/\s+/);
    payload.first_name = payload.first_name ?? names[0] ?? title;
    payload.last_name = payload.last_name ?? names.slice(1).join(" ") ?? names[0] ?? title;
    payload.name = title;
    payload.job_title = payload.job_title ?? payload.role ?? "Collaborateur";
    delete payload.title;
  }
  if (kind === "process") {
    payload.title = title;
    payload.name = title;
    payload.site_id = payload.site_id ?? undefined;
  }
  if (kind === "indicator") {
    payload.name = payload.name ?? title;
    payload.code =
      payload.code ??
      title
        .toUpperCase()
        .replace(/[^A-Z0-9]+/g, "_")
        .slice(0, 50);
    delete payload.title;
  }
  return payload;
}

function isIgnorableResourceError(error: unknown): boolean {
  return error instanceof BackendApiError && [403, 404, 422].includes(error.status);
}

export async function fetchBackendRecords(): Promise<QRecord[]> {
  const entries = Object.entries(BACKEND_RESOURCES);
  const results = await Promise.allSettled(
    entries.map(async ([kind, endpoint]) => {
      const payload = await backendApi.request<unknown>(`${endpoint}?per_page=100`);
      return collection(payload).map((item) => toRecord(kind, item));
    }),
  );
  const errors = results.filter(
    (r): r is PromiseRejectedResult =>
      r.status === "rejected" && !isIgnorableResourceError(r.reason),
  );
  if (errors.length && errors.every((e) => e.reason?.status === 401)) throw errors[0].reason;
  return results.flatMap((result) => (result.status === "fulfilled" ? result.value : []));
}

export function historyOf(r: QRecord): HistoryEntry[] {
  const history = r.data["_history"] ?? r.data["workflow_history"] ?? r.data["history"];
  return Array.isArray(history) ? (history as HistoryEntry[]) : [];
}

function entry(by: string, action: string, extra: Partial<HistoryEntry> = {}): HistoryEntry {
  return { at: new Date().toISOString(), by, action, ...extra };
}

async function updateRecord(
  id: string,
  kind: string,
  patch: { title?: string; status?: string; data?: Record<string, unknown> },
) {
  const endpoint = BACKEND_RESOURCES[kind];
  if (!endpoint)
    throw new Error(
      `Le module « ${KINDS[kind]?.label ?? kind} » n'est pas encore exposé par le backend.`,
    );
  const payload = sanitizePayload(kind, patch.title ?? "", patch.status ?? "", patch.data ?? {});
  return backendApi.request(`${endpoint}/${id}`, { method: "PUT", body: JSON.stringify(payload) });
}

async function transitionRecord(id: string, kind: string, status: string, comment?: string) {
  const endpoint = BACKEND_RESOURCES[kind];
  const value = backendStatus(kind, status);
  if (!endpoint)
    throw new Error(
      `Le module « ${KINDS[kind]?.label ?? kind} » n'est pas encore exposé par le backend.`,
    );
  if (kind === "document") {
    if (value === "pending_verification") {
      return backendApi.request(`${endpoint}/${id}/submit-for-approval`, { method: "POST" });
    }
    if (value === "pending_approval") {
      return backendApi.request(`${endpoint}/${id}/verify`, {
        method: "POST",
        body: JSON.stringify({ comment }),
      });
    }
    if (value === "approved") {
      return backendApi.request(`${endpoint}/${id}/approve`, {
        method: "POST",
        body: JSON.stringify({ comment }),
      });
    }
    if (value === "rejected") {
      return backendApi.request(`${endpoint}/${id}/reject`, {
        method: "POST",
        body: JSON.stringify({ rejection_reason: comment || "Correction demandée" }),
      });
    }
  }
  if (kind === "action") {
    return backendApi.request(`${endpoint}/${id}/status`, {
      method: "POST",
      body: JSON.stringify({ status: value, comments: comment }),
    });
  }
  if (kind === "audit") {
    return backendApi.request(`${endpoint}/${id}/status`, {
      method: "PUT",
      body: JSON.stringify({ status: value }),
    });
  }
  if (kind === "nc") {
    return backendApi.request(`${endpoint}/${id}/status`, {
      method: "POST",
      body: JSON.stringify({ status: value, comments: comment }),
    });
  }
  return updateRecord(id, kind, { status });
}

export function useRecords() {
  return useQuery({ queryKey: RECORDS_KEY, queryFn: fetchBackendRecords, staleTime: 20_000 });
}

export function useSaveRecord() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (input: {
      id?: string;
      kind: string;
      title: string;
      status: string;
      data: QRecord["data"];
      previous?: QRecord;
      silent?: boolean;
    }) => {
      const endpoint = BACKEND_RESOURCES[input.kind];
      if (!endpoint)
        throw new Error(
          `Le module « ${KINDS[input.kind]?.label ?? input.kind} » n'est pas encore exposé par le backend.`,
        );
      const profile = await backendApi.auth.me();
      const history = input.previous ? historyOf(input.previous) : [];
      const data = {
        ...input.data,
        _history: [
          ...history,
          entry(
            profile.email,
            input.id ? "Modification" : "Création",
            input.id && input.previous?.status !== input.status
              ? { from: input.previous.status, to: input.status }
              : { to: input.status },
          ),
        ],
      };
      const payload = sanitizePayload(input.kind, input.title, input.status, data);
      if (input.id)
        return updateRecord(input.id, input.kind, {
          title: input.title,
          status: input.status,
          data,
        });
      return backendApi.request(`${endpoint}`, { method: "POST", body: JSON.stringify(payload) });
    },
    onSuccess: (_d, v) => {
      qc.invalidateQueries({ queryKey: RECORDS_KEY });
      if (!v.silent) toast.success(v.id ? "Modifications enregistrées" : "Élément créé");
    },
    onError: (error) =>
      toast.error(error instanceof Error ? error.message : "L'enregistrement a échoué."),
  });
}

function bump(v: unknown): string {
  const n = parseFloat(String(v ?? "1"));
  return Number.isFinite(n) ? `${Math.floor(n) + 1}.0` : "2.0";
}

export function useTransition() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({
      record,
      t,
      comment,
      date,
      number,
    }: {
      record: QRecord;
      t: Transition;
      comment?: string;
      date?: string;
      number?: number;
    }) => {
      const data: Record<string, unknown> = { ...record.data };
      if (t.date && date) data[t.date.key] = date;
      if (t.number && number !== undefined) data[t.number.key] = number;
      if (t.bumpVersion) data.version = bump(data.version);
      const extra = [
        comment,
        t.date && date ? `${t.date.label} : ${new Date(date).toLocaleDateString("fr-FR")}` : "",
        t.number && number !== undefined ? `${t.number.label} : ${number}` : "",
      ]
        .filter(Boolean)
        .join(" — ");
      data._history = [
        ...historyOf(record),
        entry((await backendApi.auth.me()).email, t.label, {
          from: record.status,
          ...(t.to ? { to: t.to } : {}),
          ...(extra ? { comment: extra } : {}),
        }),
      ];
      await updateRecord(record.id, record.kind, {
        title: record.title,
        status: record.status,
        data,
      });
      if (t.to) await transitionRecord(record.id, record.kind, t.to, extra || undefined);
    },
    onSuccess: (_d, v) => {
      qc.invalidateQueries({ queryKey: RECORDS_KEY });
      toast.success(v.t.to ? `${v.t.label} : statut « ${v.t.to} »` : `${v.t.label} : enregistré`);
    },
    onError: (error) =>
      toast.error(error instanceof Error ? error.message : "L'opération a échoué."),
  });
}

export function useAddNote() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({
      record,
      label,
      comment,
    }: {
      record: QRecord;
      label: string;
      comment: string;
    }) => {
      const data = {
        ...record.data,
        _history: [
          ...historyOf(record),
          entry((await backendApi.auth.me()).email, label, { comment }),
        ],
      };
      await updateRecord(record.id, record.kind, {
        title: record.title,
        status: record.status,
        data,
      });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RECORDS_KEY });
      toast.success("Observation enregistrée");
    },
    onError: (error) =>
      toast.error(
        error instanceof Error ? error.message : "Impossible d'enregistrer l'observation.",
      ),
  });
}

export function useSetStatus() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ record, status }: { record: QRecord; status: string }) => {
      const data = {
        ...record.data,
        _history: [
          ...historyOf(record),
          entry((await backendApi.auth.me()).email, "Changement de statut", {
            from: record.status,
            to: status,
          }),
        ],
      };
      if (["action", "audit", "nc"].includes(record.kind))
        await transitionRecord(record.id, record.kind, status);
      else await updateRecord(record.id, record.kind, { title: record.title, status, data });
    },
    onSuccess: (_d, v) => {
      qc.invalidateQueries({ queryKey: RECORDS_KEY });
      toast.success(`Statut : ${v.status}`);
    },
    onError: (error) =>
      toast.error(error instanceof Error ? error.message : "Impossible de changer le statut."),
  });
}

export function useDeleteRecord() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, kind }: { id: string; kind: string }) => {
      const endpoint = BACKEND_RESOURCES[kind];
      if (!endpoint)
        throw new Error("Le type de l'élément est requis pour le supprimer via le backend.");
      return backendApi.request(`${endpoint}/${id}`, { method: "DELETE" });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: RECORDS_KEY });
      toast.success("Élément supprimé");
    },
    onError: (error) =>
      toast.error(error instanceof Error ? error.message : "Suppression impossible."),
  });
}

export function dueOf(r: QRecord): string | undefined {
  const k = KINDS[r.kind]?.due;
  const v =
    (k ? r.data[k] : undefined) ??
    r.data["due_date"] ??
    r.data["deadline"] ??
    r.data["planned_date"];
  return v ? String(v) : undefined;
}

export function isDone(r: QRecord): boolean {
  const done = KINDS[r.kind]?.done ?? [
    "Terminée",
    "Vérifiée",
    "Clôturée",
    "Clôturé",
    "Atteint",
    "Archivé",
    "Publié",
  ];
  return done.includes(r.status) || r.status === "Archivé";
}

export function isOverdue(r: QRecord): boolean {
  const due = dueOf(r);
  if (!due || isDone(r)) return false;
  return new Date(due) < new Date(new Date().toDateString());
}
