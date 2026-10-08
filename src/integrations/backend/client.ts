/**
 * Thin client for the Laravel API.
 *
 * The frontend deliberately talks to the same contract as the Laravel backend
 * (`/api/v1`) instead of duplicating authentication and persistence in the frontend.
 * In development Vite proxies `/api` to BACKEND_URL; in production the reverse
 * proxy should expose the Laravel API under the same origin.
 */

const API_BASE = (import.meta.env.VITE_API_BASE_URL || "/api/v1").replace(/\/$/, "");
const TOKEN_KEY = "lq-backend-token";
const EXPIRES_KEY = "lq-backend-token-expires";

export type BackendUser = {
  id: string | number;
  email: string;
  name?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  username?: string | null;
  phone?: string | null;
  address?: string | null;
  job_title?: string | null;
  position?: string | null;
  user_type?: string | null;
  role?: string | null;
  role_names?: string[];
  is_active?: boolean;
  email_verified_at?: string | null;
  enterprise_id?: string | number | null;
  site_id?: string | number | null;
  enterprise?: {
    id?: string | number;
    name?: string | null;
    enterprise_name?: string | null;
    status?: string | null;
    approval_status?: string | null;
    registration_number?: string | null;
    rccm_number?: string | null;
    ifu_number?: string | null;
    address?: string | null;
    address_line_1?: string | null;
  } | null;
  site?: { id?: string | number; name?: string | null } | null;
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
};

export type BackendProfile = {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  phone: string | null;
  account_type: "company" | "individual";
  status: string;
  company_name: string | null;
  company_rccm: string | null;
  company_ifu: string | null;
  company_address: string | null;
  created_at: string | null;
  user_type: string;
  enterprise_id: string | null;
  site_id: string | null;
  permissions: string[];
  raw: BackendUser;
};

export type ApiErrorShape = {
  message?: string;
  errors?: Record<string, string[] | string>;
  status?: number;
  response?: unknown;
};

export class BackendApiError extends Error {
  status: number;
  errors: Record<string, string[] | string>;
  payload: unknown;

  constructor(
    message: string,
    status = 0,
    payload: unknown = undefined,
    errors: Record<string, string[] | string> = {},
  ) {
    super(message);
    this.name = "BackendApiError";
    this.status = status;
    this.errors = errors;
    this.payload = payload;
  }
}

export type LoginResult =
  | {
      mfa_required: true;
      mfa_token: string;
      mfa_expires_at?: string;
      mfa_code?: string;
      message?: string;
    }
  | {
      mfa_required?: false;
      token: string;
      user: BackendUser;
      email_verified?: boolean;
      [key: string]: unknown;
    };

function emitAuthChanged() {
  if (typeof window !== "undefined") window.dispatchEvent(new CustomEvent("lq-auth-changed"));
}

function getStoredToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

function saveToken(token: string | null, expiresAt?: string | null) {
  if (typeof window === "undefined") return;
  if (token) window.localStorage.setItem(TOKEN_KEY, token);
  else window.localStorage.removeItem(TOKEN_KEY);
  if (expiresAt) window.localStorage.setItem(EXPIRES_KEY, expiresAt);
  else window.localStorage.removeItem(EXPIRES_KEY);
  emitAuthChanged();
}

function apiUrl(path: string): string {
  return `${API_BASE}/${path.replace(/^\//, "")}`;
}

function extractMessage(payload: unknown, fallback: string): string {
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    if (typeof p.message === "string" && p.message) return p.message;
    if (p.errors && typeof p.errors === "object") {
      const first = Object.values(p.errors as Record<string, unknown>)[0];
      if (Array.isArray(first) && typeof first[0] === "string") return first[0];
      if (typeof first === "string") return first;
    }
  }
  return fallback;
}

async function readPayload(response: Response): Promise<unknown> {
  const text = await response.text();
  if (!text) return undefined;
  try {
    return JSON.parse(text);
  } catch {
    return text;
  }
}

let refreshPromise: Promise<boolean> | null = null;

async function refreshToken(): Promise<boolean> {
  if (refreshPromise) return refreshPromise;
  refreshPromise = (async () => {
    const token = getStoredToken();
    if (!token) return false;
    try {
      const response = await fetch(apiUrl("auth/refresh-token"), {
        method: "POST",
        headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
        credentials: "include",
      });
      const payload = await readPayload(response);
      if (
        !response.ok ||
        !payload ||
        typeof payload !== "object" ||
        typeof (payload as Record<string, unknown>).token !== "string"
      ) {
        saveToken(null);
        return false;
      }
      const p = payload as Record<string, unknown>;
      saveToken(String(p.token), typeof p.expires_at === "string" ? p.expires_at : null);
      return true;
    } catch {
      return false;
    } finally {
      refreshPromise = null;
    }
  })();
  return refreshPromise;
}

async function request<T>(path: string, init: RequestInit = {}, retry = true): Promise<T> {
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  if (init.body && !(init.body instanceof FormData) && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }
  const token = getStoredToken();
  if (token) headers.set("Authorization", `Bearer ${token}`);

  let response: Response;
  try {
    response = await fetch(apiUrl(path), { ...init, headers, credentials: "include" });
  } catch {
    throw new BackendApiError("Le serveur LOGIQUALI est momentanément indisponible.");
  }

  const payload = await readPayload(response);
  if (response.status === 401 && retry && token && path !== "auth/refresh-token") {
    if (await refreshToken()) return request<T>(path, init, false);
    saveToken(null);
  }
  if (!response.ok) {
    throw new BackendApiError(
      extractMessage(payload, `La requête a échoué (${response.status}).`),
      response.status,
      payload,
      payload &&
        typeof payload === "object" &&
        (payload as Record<string, unknown>).errors &&
        typeof (payload as Record<string, unknown>).errors === "object"
        ? ((payload as Record<string, unknown>).errors as Record<string, string[] | string>)
        : {},
    );
  }
  return payload as T;
}

async function requestBlob(path: string, init: RequestInit = {}, retry = true): Promise<Blob> {
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/pdf, application/octet-stream, application/json");
  if (init.body && !(init.body instanceof FormData) && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }
  const token = getStoredToken();
  if (token) headers.set("Authorization", `Bearer ${token}`);

  let response: Response;
  try {
    response = await fetch(apiUrl(path), { ...init, headers, credentials: "include" });
  } catch {
    throw new BackendApiError("Le serveur LOGIQUALI est momentanément indisponible.");
  }

  if (response.status === 401 && retry && token && path !== "auth/refresh-token") {
    if (await refreshToken()) return requestBlob(path, init, false);
    saveToken(null);
  }
  if (!response.ok) {
    const payload = await readPayload(response);
    throw new BackendApiError(
      extractMessage(payload, `La requête a échoué (${response.status}).`),
      response.status,
      payload,
    );
  }
  return response.blob();
}

function unwrap<T>(payload: T | { data: T }): T {
  if (payload && typeof payload === "object" && "data" in (payload as object)) {
    return (payload as { data: T }).data;
  }
  return payload as T;
}

function userFromPayload(payload: unknown): BackendUser | null {
  if (!payload || typeof payload !== "object") return null;
  const p = payload as Record<string, unknown>;
  const candidate = p.user ?? p.data ?? p;
  if (!candidate || typeof candidate !== "object") return null;
  const resource = candidate as Record<string, unknown>;
  if (resource.attributes && typeof resource.attributes === "object") {
    const relationships =
      resource.relationships && typeof resource.relationships === "object"
        ? (resource.relationships as Record<string, unknown>)
        : {};
    const relation = (key: string) => {
      const wrapper =
        relationships[key] && typeof relationships[key] === "object"
          ? (relationships[key] as Record<string, unknown>)
          : {};
      const value = wrapper.data;
      if (!value || typeof value !== "object") return null;
      const item = value as Record<string, unknown>;
      return item.attributes && typeof item.attributes === "object"
        ? { id: item.id, ...(item.attributes as Record<string, unknown>) }
        : item;
    };
    return {
      id: resource.id as string | number,
      ...(resource.attributes as Record<string, unknown>),
      enterprise: relation("enterprise"),
      site: relation("site"),
    } as BackendUser;
  }
  return "email" in resource || "id" in resource ? (resource as BackendUser) : null;
}

function profileFromUser(user: BackendUser): BackendProfile {
  const type =
    user.user_type === "clientb" || user.user_type === "client" ? "individual" : "company";
  const enterprise = user.enterprise;
  const enterpriseStatus = String(enterprise?.status ?? enterprise?.approval_status ?? "active");
  const enterpriseRccm = enterprise?.rccm_number ?? enterprise?.registration_number;
  const enterpriseAddress = enterprise?.address ?? enterprise?.address_line_1;
  const nameParts = String(user.name ?? "")
    .trim()
    .split(/\s+/)
    .filter(Boolean);
  return {
    id: String(user.id),
    email: String(user.email ?? ""),
    first_name: String(user.first_name ?? nameParts[0] ?? ""),
    last_name: String(user.last_name ?? nameParts.slice(1).join(" ") ?? ""),
    phone: user.phone ? String(user.phone) : null,
    account_type: type,
    status:
      type === "company" ? enterpriseStatus : user.is_active === false ? "suspended" : "active",
    company_name: enterprise?.name ?? enterprise?.enterprise_name ?? null,
    company_rccm: enterpriseRccm == null ? null : String(enterpriseRccm),
    company_ifu: enterprise?.ifu_number == null ? null : String(enterprise.ifu_number),
    company_address:
      enterpriseAddress == null
        ? user.address
          ? String(user.address)
          : null
        : String(enterpriseAddress),
    created_at: user.created_at ? String(user.created_at) : null,
    user_type: String(user.user_type ?? ""),
    enterprise_id:
      user.enterprise_id == null
        ? enterprise?.id == null
          ? null
          : String(enterprise.id)
        : String(user.enterprise_id),
    site_id: user.site_id == null ? null : String(user.site_id),
    permissions: Array.isArray(user.effective_permissions)
      ? user.effective_permissions.map(String)
      : [],
    raw: user,
  };
}

export const backendApi = {
  auth: {
    async login(email: string, password: string): Promise<LoginResult> {
      const result = await request<LoginResult>("auth/login", {
        method: "POST",
        body: JSON.stringify({ email, password }),
      });
      if ("token" in result && result.token)
        saveToken(result.token, typeof result.expires_at === "string" ? result.expires_at : null);
      return result;
    },
    async verifyMfa(token: string, code: string): Promise<LoginResult> {
      const result = await request<LoginResult>("auth/mfa/verify", {
        method: "POST",
        body: JSON.stringify({ token, code }),
      });
      if ("token" in result && result.token)
        saveToken(result.token, typeof result.expires_at === "string" ? result.expires_at : null);
      return result;
    },
    async resendMfa(token: string) {
      return request<{
        mfa_token: string;
        mfa_expires_at?: string;
        mfa_code?: string;
        message?: string;
      }>(
        "auth/mfa/resend",
        {
          method: "POST",
          body: JSON.stringify({ token }),
        },
      );
    },
    async registerClient(input: {
      username: string;
      email: string;
      password: string;
      password_confirmation: string;
      first_name?: string;
      last_name?: string;
      phone?: string;
      address?: string;
    }) {
      const result = await request<{
        token?: string;
        user?: BackendUser;
        email_verified?: boolean;
        message?: string;
      }>("auth/register/client", {
        method: "POST",
        body: JSON.stringify(input),
      });
      if (result.token) saveToken(result.token);
      return result;
    },
    async registerEnterprise(input: FormData) {
      return request<{ message?: string; enterprise_id?: string | number; admin_email?: string }>(
        "auth/register/enterprise",
        {
          method: "POST",
          body: input,
        },
      );
    },
    async me(): Promise<BackendProfile> {
      const result = await request<{ user: BackendUser }>("auth/me");
      const user = userFromPayload(result);
      if (!user) throw new BackendApiError("Profil utilisateur introuvable.", 500, result);
      return profileFromUser(user);
    },
    async logout() {
      try {
        if (getStoredToken()) await request("auth/logout", { method: "POST" }, false);
      } finally {
        saveToken(null);
      }
    },
    async forgotPassword(email: string) {
      return request<{ message?: string }>("auth/forgot-password", {
        method: "POST",
        body: JSON.stringify({ email }),
      });
    },
    async resetPassword(input: {
      token: string;
      email: string;
      password: string;
      password_confirmation: string;
    }) {
      return request<{ message?: string }>("auth/reset-password", {
        method: "POST",
        body: JSON.stringify(input),
      });
    },
    async resendVerification(email?: string) {
      const path = email ? "auth/resend-verification/public" : "auth/resend-verification";
      return request<{ message?: string; verification_sent?: boolean }>(path, {
        method: "POST",
        body: email ? JSON.stringify({ email }) : undefined,
      });
    },
    hasToken: getStoredToken,
    clearToken: () => saveToken(null),
  },
  profile: {
    async update(input: Record<string, unknown>) {
      const currentResult = await request<{ user: BackendUser }>("auth/me");
      const currentUser = userFromPayload(currentResult);
      if (!currentUser)
        throw new BackendApiError("Profil utilisateur introuvable.", 500, currentResult);

      const userPayload: Record<string, unknown> = {};
      if (input.first_name !== undefined || input.last_name !== undefined) {
        userPayload.name = [input.first_name, input.last_name]
          .filter((value) => typeof value === "string" && value.trim())
          .join(" ");
      }
      for (const key of ["email", "phone", "photo_path", "password", "password_confirmation"]) {
        if (input[key] !== undefined) userPayload[key] = input[key];
      }
      if (Object.keys(userPayload).length > 0) {
        await request("auth/profile", { method: "PUT", body: JSON.stringify(userPayload) });
      }

      const enterprisePayload: Record<string, unknown> = {};
      const enterpriseFields: Record<string, string> = {
        company_name: "name",
        company_rccm: "rccm_number",
        company_ifu: "ifu_number",
        company_address: "address",
      };
      for (const [source, target] of Object.entries(enterpriseFields)) {
        if (input[source] !== undefined) enterprisePayload[target] = input[source];
      }
      if (Object.keys(enterprisePayload).length > 0 && currentUser.enterprise_id != null) {
        await request(`enterprises/${currentUser.enterprise_id}`, {
          method: "PUT",
          body: JSON.stringify(enterprisePayload),
        });
      }

      const refreshed = await request<{ user: BackendUser }>("auth/me");
      const user = userFromPayload(refreshed);
      if (!user) throw new BackendApiError("Profil introuvable après mise à jour.", 500, refreshed);
      return profileFromUser(user);
    },
  },
  request,
  blob: requestBlob,
  unwrap,
  profileFromUser,
};

export { API_BASE, getStoredToken };
