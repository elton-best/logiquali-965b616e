import { useEffect, useMemo, useState } from "react";
import { KINDS, NORM_CODES } from "@/components/app/sections";
import { dueOf, isDone, isOverdue, type QRecord } from "./use-records";

export const TRIAL_DAYS = 30;
const DAY = 86_400_000;

export type NormState = { code: string; status: "Active" | "Expirée" | "Désactivée" | "Inactive"; record?: QRecord | undefined; expiresAt?: Date | undefined; implicit?: boolean };

/** Norms & subscription state, derived from "norm" records and the account creation date. */
export function computeWorkspace(records: QRecord[], createdAt: string | null | undefined) {
  const trialEnd = new Date(new Date(createdAt ?? Date.now()).getTime() + TRIAL_DAYS * DAY);
  const today = new Date(new Date().toDateString());
  const normRecords = records.filter((r) => r.kind === "norm");
  const norms: NormState[] = NORM_CODES.map((code) => {
    const rec = normRecords.find((r) => r.title === code);
    if (!rec) {
      // ISO 9001 is included by default during the trial.
      if (code === "ISO 9001" && normRecords.length === 0) {
        return { code, status: trialEnd >= today ? "Active" : "Expirée", expiresAt: trialEnd, implicit: true };
      }
      return { code, status: "Inactive" };
    }
    const exp = rec.data["expires_at"] ? new Date(String(rec.data["expires_at"])) : trialEnd;
    if (rec.status === "Désactivée") return { code, status: "Désactivée", record: rec, expiresAt: exp };
    return { code, status: exp >= today ? "Active" : "Expirée", record: rec, expiresAt: exp };
  });
  const active = new Set(norms.filter((n) => n.status === "Active").map((n) => n.code));
  const expired = active.size === 0;
  const nextExpiry = norms.filter((n) => n.status === "Active" && n.expiresAt).map((n) => n.expiresAt!).sort((a, b) => a.getTime() - b.getTime())[0];
  const daysLeft = nextExpiry ? Math.ceil((nextExpiry.getTime() - today.getTime()) / DAY) : 0;
  const paid = records.some((r) => r.kind === "subscription_request" && r.status === "Payée");
  return { norms, active, expired, trialEnd, nextExpiry, daysLeft, inTrial: !paid && trialEnd >= today };
}

// ---------- Current site (persisted per browser) ----------
const SITE_KEY = "lq-site";
export function useCurrentSite(): [string, (id: string) => void] {
  const [site, setSite] = useState("");
  useEffect(() => {
    setSite(localStorage.getItem(SITE_KEY) ?? "");
    const on = () => setSite(localStorage.getItem(SITE_KEY) ?? "");
    window.addEventListener("lq-site", on);
    return () => window.removeEventListener("lq-site", on);
  }, []);
  return [
    site,
    (id: string) => {
      localStorage.setItem(SITE_KEY, id);
      window.dispatchEvent(new Event("lq-site"));
    },
  ];
}

// ---------- Tasks ----------
export type Task = { record: QRecord; reason: string; type: "action" | "verification" | "approbation" | "audit" | "indicateur" | "formation" | "retard" | "autre" };

export function computeTasks(records: QRecord[], email: string): Task[] {
  const mine = new Set(records.filter((r) => r.kind === "collaborator" && String(r.data["email"] ?? "").toLowerCase() === email.toLowerCase()).map((r) => r.id));
  const out = new Map<string, Task>();
  const add = (t: Task) => { if (!out.has(t.record.id)) out.set(t.record.id, t); };
  const in30 = Date.now() + 30 * DAY;
  for (const r of records) {
    if (r.kind === "norm" || r.kind === "subscription_request") continue;
    const cfg = KINDS[r.kind];
    if (r.status === "En vérification") { add({ record: r, reason: "À vérifier", type: "verification" }); continue; }
    if (r.status === "En approbation") { add({ record: r, reason: "À approuver", type: "approbation" }); continue; }
    if (isDone(r)) continue;
    if (isOverdue(r)) { add({ record: r, reason: `En retard (échéance ${new Date(dueOf(r)!).toLocaleDateString("fr-FR")})`, type: "retard" }); continue; }
    const ownerId = cfg?.owner ? String(r.data[cfg.owner] ?? "") : "";
    const assignedToMe = ownerId && mine.has(ownerId);
    if (r.kind === "action" && (assignedToMe || !ownerId)) add({ record: r, reason: assignedToMe ? "Action qui vous est attribuée" : "Action sans responsable", type: "action" });
    else if (r.kind === "audit" && ["Planifié", "En préparation", "En cours"].includes(r.status)) add({ record: r, reason: r.status === "En cours" ? "Audit à réaliser" : "Audit à préparer", type: "audit" });
    else if (r.kind === "indicator" && r.status === "Actif" && (r.data["value"] === undefined || r.data["value"] === null || r.data["value"] === "")) add({ record: r, reason: "Indicateur à renseigner", type: "indicateur" });
    else if ((r.kind === "habilitation" || r.kind === "training") && dueOf(r) && new Date(dueOf(r)!).getTime() < in30) add({ record: r, reason: r.kind === "training" ? "Formation à réaliser" : "Habilitation à renouveler", type: "formation" });
    else if (assignedToMe) add({ record: r, reason: "Vous êtes responsable", type: "autre" });
  }
  return [...out.values()];
}

export function useWorkspace(records: QRecord[], createdAt: string | null | undefined, email: string) {
  return useMemo(() => {
    const ws = computeWorkspace(records, createdAt);
    const tasks = computeTasks(records, email);
    return {
      ...ws,
      tasks,
      verifyCount: records.filter((r) => r.status === "En vérification").length,
      approveCount: records.filter((r) => r.status === "En approbation").length,
    };
  }, [records, createdAt, email]);
}

// ---------- CSV export ----------
export function downloadCsv(filename: string, rows: (string | number | null | undefined)[][]) {
  const esc = (v: unknown) => `"${String(v ?? "").replace(/"/g, '""')}"`;
  const csv = "\uFEFF" + rows.map((r) => r.map(esc).join(";")).join("\n");
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
  URL.revokeObjectURL(a.href);
}
