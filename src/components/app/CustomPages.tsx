import { getRouteApi, Link, useRouter } from "@tanstack/react-router";
import { useQueryClient } from "@tanstack/react-query";
import { Building2, Check, CreditCard, Download, ExternalLink, Minus, RotateCcw, Search } from "lucide-react";
import { useMemo, useState } from "react";
import { toast } from "sonner";
import { supabase } from "@/integrations/supabase/client";
import { historyOf, RECORDS_KEY, useRecords, useSaveRecord, useTransition, type QRecord } from "@/hooks/use-records";
import { downloadCsv, useWorkspace, type Task } from "@/hooks/use-workspace";
import { KINDS, NORM_CATALOG, sectionForKind } from "./sections";
import { HistoryList, RecordActions, StatusBadge } from "./SectionView";
import { NAV_GROUPS } from "./nav";

const appRoute = getRouteApi("/_authenticated/app");

function Header({ title, desc, children }: { title: string; desc: string; children?: React.ReactNode }) {
  return (
    <div className="flex flex-wrap items-end justify-between gap-4">
      <div>
        <Link to="/app" className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Vue d'ensemble</Link>
        <h1 className="mt-1 font-display text-2xl font-extrabold text-foreground md:text-3xl">{title}</h1>
        <p className="mt-1 text-sm text-muted-foreground">{desc}</p>
      </div>
      {children && <div className="flex flex-wrap gap-2">{children}</div>}
    </div>
  );
}

const ghostBtn = "inline-flex h-10 items-center gap-2 rounded-xl border border-border bg-card px-4 text-sm font-semibold text-foreground hover:border-primary hover:text-primary";
const primaryBtn = "inline-flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground hover:bg-primary-dark disabled:opacity-60";

function useWs() {
  const profile = appRoute.useLoaderData();
  const { data: records = [], isLoading } = useRecords();
  const ws = useWorkspace(records, profile.created_at, profile.email ?? "");
  return { profile, records, isLoading, ws };
}

function OpenLink({ r }: { r: QRecord }) {
  const slug = sectionForKind(r.kind);
  if (!slug) return null;
  return (
    <Link to="/app/$section" params={{ section: slug }} search={{ open: r.id }} className="inline-flex h-8 items-center gap-1.5 rounded-xl border border-border px-3 text-xs font-semibold hover:border-primary hover:text-primary">
      <ExternalLink className="h-3.5 w-3.5" /> Ouvrir
    </Link>
  );
}

// ---------- Mes tâches ----------
const TASK_FILTERS: { id: Task["type"] | ""; label: string }[] = [
  { id: "", label: "Toutes" }, { id: "retard", label: "En retard" }, { id: "action", label: "Actions" },
  { id: "verification", label: "À vérifier" }, { id: "approbation", label: "À approuver" }, { id: "audit", label: "Audits" },
  { id: "indicateur", label: "Indicateurs" }, { id: "formation", label: "Formations & habilitations" }, { id: "autre", label: "Autres" },
];

export function TasksPage() {
  const { ws, isLoading } = useWs();
  const [filter, setFilter] = useState<Task["type"] | "">("");
  const [q, setQ] = useState("");
  const list = ws.tasks.filter((t) => (!filter || t.type === filter) && (!q || `${t.record.reference} ${t.record.title}`.toLowerCase().includes(q.toLowerCase())));
  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <Header title="Mes tâches" desc="Tout ce qui attend une action de votre part : actions, validations, audits, indicateurs, formations et échéances." />
      <select
        aria-label="Filtrer les tâches"
        value={filter}
        onChange={(e) => setFilter(e.target.value as Task["type"] | "")}
        className="h-11 w-full rounded-xl border border-input bg-card px-4 text-sm font-semibold outline-none focus:border-primary sm:w-72"
      >
        {TASK_FILTERS.map((f) => (
          <option key={f.id} value={f.id}>{f.label} ({f.id ? ws.tasks.filter((t) => t.type === f.id).length : ws.tasks.length})</option>
        ))}
      </select>
      <div className="relative">
        <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-primary" />
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Rechercher une tâche par titre ou référence…" className="h-11 w-full rounded-xl border border-input bg-card pl-11 pr-4 text-sm outline-none focus:border-primary" />
      </div>
      {isLoading ? <p className="text-sm text-muted-foreground">Chargement…</p> : list.length === 0 ? (
        <div className="rounded-2xl border border-border bg-card p-10 text-center">
          <p className="font-display font-bold text-foreground">Rien à traiter</p>
          <p className="mt-1 text-sm text-muted-foreground">Aucune tâche ne correspond. Les éléments attribués, à valider ou en retard apparaîtront ici.</p>
        </div>
      ) : (
        <ul className="space-y-3">
          {list.map((t) => (
            <li key={t.record.id} className="rounded-2xl border border-border bg-card p-4">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="min-w-0">
                  <p className="text-xs font-bold text-primary">{t.record.reference} · {KINDS[t.record.kind]?.singular}</p>
                  <p className="mt-0.5 font-semibold text-foreground">{t.record.title}</p>
                  <p className={`mt-0.5 text-xs font-semibold ${t.type === "retard" ? "text-destructive" : "text-muted-foreground"}`}>{t.reason}</p>
                </div>
                <div className="flex items-center gap-2"><StatusBadge kind={t.record.kind} status={t.record.status} /><OpenLink r={t.record} /></div>
              </div>
              <div className="mt-3"><RecordActions record={t.record} compact /></div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

// ---------- Vérification / Approbation ----------
export function QueuePage({ mode }: { mode: "verification" | "approbation" }) {
  const { records, isLoading } = useWs();
  const [open, setOpen] = useState<string | null>(null);
  const status = mode === "verification" ? "En vérification" : "En approbation";
  const returned = mode === "verification" ? records.filter((r) => r.status === "À corriger") : [];
  const list = records.filter((r) => r.status === status);
  const byId = new Map(records.map((r) => [r.id, r]));
  const submittedAt = (r: QRecord) => [...historyOf(r)].reverse().find((h) => h.to === status);
  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <Header
        title={mode === "verification" ? "Vérification" : "Approbation"}
        desc={mode === "verification" ? "Documents et informations soumis au contrôle. Vérifiez, demandez une correction ou transmettez à l'approbation." : "Éléments vérifiés prêts à décision. Approuvez (avec date d'effet), rejetez ou renvoyez en correction."}
      />
      {isLoading ? <p className="text-sm text-muted-foreground">Chargement…</p> : list.length === 0 ? (
        <div className="rounded-2xl border border-border bg-card p-10 text-center">
          <p className="font-display font-bold text-foreground">File vide</p>
          <p className="mt-1 text-sm text-muted-foreground">Aucun élément « {status} » pour le moment.</p>
        </div>
      ) : (
        <ul className="space-y-3">
          {list.map((r) => {
            const sub = submittedAt(r);
            const creator = historyOf(r)[0]?.by;
            const verifier = mode === "approbation" ? sub?.by : undefined;
            const pid = r.data["process_id"] ? byId.get(String(r.data["process_id"])) : undefined;
            const comments = historyOf(r).filter((h) => h.comment).length;
            return (
              <li key={r.id} className="rounded-2xl border border-border bg-card p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="text-xs font-bold text-primary">{r.reference} · {KINDS[r.kind]?.singular}{r.data["version"] ? ` · v${String(r.data["version"])}` : ""}</p>
                    <p className="mt-0.5 font-semibold text-foreground">{r.title}</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      Créé par {creator || "—"}{verifier ? ` · vérifié par ${verifier}` : ""}
                      {sub ? ` · soumis le ${new Date(sub.at).toLocaleDateString("fr-FR")}` : ""}
                      {pid ? ` · ${pid.title}` : ""}
                      {r.data["norm"] ? ` · ${String(r.data["norm"])}` : ""} · {comments} commentaire(s)
                    </p>
                  </div>
                  <div className="flex gap-2">
                    <button onClick={() => setOpen(open === r.id ? null : r.id)} className="inline-flex h-8 items-center rounded-xl border border-border px-3 text-xs font-semibold hover:border-primary hover:text-primary">{open === r.id ? "Masquer" : "Historique"}</button>
                    <OpenLink r={r} />
                  </div>
                </div>
                <div className="mt-3"><RecordActions record={r} compact /></div>
                {open === r.id && <div className="mt-4 rounded-xl bg-background p-4"><HistoryList record={r} /></div>}
              </li>
            );
          })}
        </ul>
      )}
      {returned.length > 0 && (
        <div>
          <p className="mb-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">Retournés pour correction ({returned.length})</p>
          <ul className="space-y-2">
            {returned.map((r) => (
              <li key={r.id} className="flex items-center justify-between gap-2 rounded-xl border border-border bg-card px-4 py-3 text-sm">
                <span><span className="font-mono text-xs font-bold text-primary">{r.reference}</span> {r.title}</span>
                <OpenLink r={r} />
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}

// ---------- Bibliothèque des normes ----------
export function NormsPage() {
  const { records, ws } = useWs();
  const save = useSaveRecord();
  const run = useTransition();
  const [openCode, setOpenCode] = useState<string | null>(null);

  const activate = async (code: string, rec?: QRecord) => {
    const end = ws.trialEnd > new Date() ? ws.trialEnd : new Date(Date.now() + 365 * 86_400_000);
    const data = { activated_at: new Date().toISOString().slice(0, 10), expires_at: end.toISOString().slice(0, 10) };
    if (rec) await run.mutateAsync({ record: rec, t: { label: "Activer la norme", to: "Active", date: { key: "expires_at", label: "Expire le" } }, date: data.expires_at });
    else await save.mutateAsync({ kind: "norm", title: code, status: "Active", data });
  };
  const exportCsv = () => downloadCsv("normes.csv", [["Norme", "Nom", "État", "Expiration", "Progression"], ...ws.norms.map((n) => {
    const info = NORM_CATALOG.find((c) => c.code === n.code)!;
    const done = info.kinds.filter((k) => records.some((r) => r.kind === k)).length;
    return [n.code, info.name, n.status, n.expiresAt?.toLocaleDateString("fr-FR") ?? "", `${Math.round((done / info.kinds.length) * 100)} %`];
  })]);

  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <Header title="Bibliothèque des normes" desc="Activez les référentiels de votre système : chaque norme rend visibles ses sous-sections dans le menu.">
        <button onClick={exportCsv} className={ghostBtn}><Download className="h-4 w-4" /> Exporter</button>
      </Header>
      <div className="grid gap-4 md:grid-cols-2">
        {NORM_CATALOG.map((info) => {
          const st = ws.norms.find((n) => n.code === info.code)!;
          const doneKinds = info.kinds.filter((k) => records.some((r) => r.kind === k));
          const pct = Math.round((doneKinds.length / info.kinds.length) * 100);
          const subs = NAV_GROUPS.flatMap((g) => g.items).filter((i) => i.norms?.includes(info.code));
          const busy = save.isPending || run.isPending;
          return (
            <div key={info.code} className={`rounded-2xl border bg-card p-5 ${st.status === "Active" ? "border-primary" : "border-border"}`}>
              <div className="flex items-start justify-between gap-2">
                <div>
                  <p className="font-display text-lg font-extrabold text-foreground">{info.code}</p>
                  <p className="text-sm text-muted-foreground">{info.name} · {info.domain}</p>
                </div>
                <span className={`rounded-full px-2.5 py-0.5 text-[11px] font-bold ${st.status === "Active" ? "bg-primary text-primary-foreground" : st.status === "Expirée" ? "bg-destructive/10 text-destructive" : "bg-secondary text-muted-foreground"}`}>{st.status}</span>
              </div>
              {st.expiresAt && st.status !== "Inactive" && <p className="mt-2 text-xs text-muted-foreground">Expire le {st.expiresAt.toLocaleDateString("fr-FR")}{st.implicit ? " (incluse dans l'essai)" : ""}</p>}
              <div className="mt-3">
                <div className="flex justify-between text-xs"><span className="font-semibold text-foreground">Progression de mise en œuvre</span><span className="text-muted-foreground">{pct} %</span></div>
                <div className="mt-1 h-2 rounded-full bg-secondary"><div className="h-2 rounded-full bg-primary" style={{ width: `${pct}%` }} /></div>
              </div>
              {openCode === info.code && (
                <div className="mt-4 space-y-3 text-sm">
                  <div>
                    <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Exigences principales</p>
                    <ul className="mt-1 list-disc pl-5 text-foreground">{info.requirements.map((r) => <li key={r}>{r}</li>)}</ul>
                  </div>
                  <div>
                    <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Éléments en place / manquants</p>
                    <ul className="mt-1 space-y-1">
                      {info.kinds.map((k) => {
                        const ok = doneKinds.includes(k);
                        const slug = sectionForKind(k);
                        return (
                          <li key={k} className="flex items-center gap-2">
                            {ok ? <Check className="h-4 w-4 text-primary" /> : <Minus className="h-4 w-4 text-destructive" />}
                            {slug ? <Link to="/app/$section" params={{ section: slug }} className="hover:text-primary">{KINDS[k]?.label}</Link> : KINDS[k]?.label}
                          </li>
                        );
                      })}
                    </ul>
                  </div>
                  {subs.length > 0 && <p className="text-xs text-muted-foreground">Sous-sections ajoutées au menu : {subs.map((s) => s.label).join(", ")}</p>}
                </div>
              )}
              <div className="mt-4 flex flex-wrap gap-2">
                <button onClick={() => setOpenCode(openCode === info.code ? null : info.code)} className="h-9 rounded-xl border border-border px-3 text-xs font-semibold hover:border-primary hover:text-primary">{openCode === info.code ? "Masquer" : "Consulter / progression"}</button>
                {(st.status === "Inactive" || st.status === "Désactivée") && <button disabled={busy} onClick={() => activate(info.code, st.record)} className="h-9 rounded-xl bg-primary px-3 text-xs font-semibold text-primary-foreground disabled:opacity-60">Activer</button>}
                {st.status === "Expirée" && <Link to="/app/$section" params={{ section: "abonnement" }} className="inline-flex h-9 items-center rounded-xl bg-primary px-3 text-xs font-semibold text-primary-foreground">Renouveler</Link>}
                {st.status === "Active" && st.record && (
                  <button disabled={busy} onClick={() => { if (confirm(`Désactiver ${info.code} ? Les données restent conservées.`)) run.mutate({ record: st.record!, t: { label: "Désactiver la norme", to: "Désactivée" } }); }} className="h-9 rounded-xl border border-destructive/40 px-3 text-xs font-semibold text-destructive">Désactiver</button>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

// ---------- Fiche entreprise / Paramètres ----------
export function CompanyPage() {
  const profile = appRoute.useLoaderData();
  const router = useRouter();
  const { data: records = [] } = useRecords();
  const initial = {
    company_name: profile.company_name ?? "",
    company_rccm: profile.company_rccm ?? "",
    company_ifu: profile.company_ifu ?? "",
    company_address: profile.company_address ?? "",
    phone: profile.phone ?? "",
    first_name: profile.first_name ?? "",
    last_name: profile.last_name ?? "",
  };
  const [form, setForm] = useState(initial);
  const [busy, setBusy] = useState(false);

  const save = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    const { error } = await supabase.from("profiles").update(form).eq("id", profile.id);
    setBusy(false);
    if (error) { toast.error("Enregistrement impossible. Vos saisies sont conservées."); return; }
    toast.success("Paramètres de l'organisation enregistrés");
    router.invalidate();
  };

  const fields: [keyof typeof form, string][] = [
    ["company_name", "Raison sociale"], ["company_rccm", "N° RCCM"], ["company_ifu", "N° IFU"],
    ["company_address", "Adresse du siège"], ["phone", "Téléphone"], ["first_name", "Prénom de l'administrateur"], ["last_name", "Nom de l'administrateur"],
  ];
  const missing = fields.filter(([k]) => !form[k].trim());
  const stats = [
    ["Sites", records.filter((r) => r.kind === "site").length],
    ["Collaborateurs", records.filter((r) => r.kind === "collaborator").length],
    ["Processus", records.filter((r) => r.kind === "process").length],
    ["Normes actives", records.filter((r) => r.kind === "norm" && r.status === "Active").length || 1],
  ] as const;

  return (
    <div className="mx-auto max-w-5xl space-y-6 p-4 md:p-8">
      <Header title="Paramètres de l'organisation" desc="Identité légale, contacts, sites et normes de l'entreprise.">
        <Link to="/app/$section" params={{ section: "sites" }} search={{ new: 1 }} className={ghostBtn}>Ajouter un site</Link>
        <Link to="/app/$section" params={{ section: "normes" }} className={ghostBtn}>Gérer les normes</Link>
      </Header>
      {missing.length > 0 && (
        <div className="rounded-2xl border border-destructive/30 bg-destructive/5 p-4 text-sm">
          <p className="font-bold text-destructive">Configuration incomplète</p>
          <p className="mt-1 text-muted-foreground">À compléter : {missing.map(([, l]) => l).join(", ")}.</p>
        </div>
      )}
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        {stats.map(([l, v]) => (
          <div key={l} className="rounded-2xl border border-border bg-card p-4">
            <p className="font-display text-2xl font-extrabold text-foreground">{v}</p>
            <p className="text-xs font-semibold text-muted-foreground">{l}</p>
          </div>
        ))}
      </div>
      <form onSubmit={save} className="rounded-2xl border border-border bg-card p-6">
        <div className="flex items-center gap-3">
          <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-soft text-primary"><Building2 className="h-5 w-5" /></span>
          <div>
            <p className="font-display font-bold text-foreground">{form.company_name || "Votre entreprise"}</p>
            <p className="text-xs text-muted-foreground">{profile.email} · Compte {profile.status === "active" ? "actif" : profile.status}</p>
          </div>
        </div>
        <div className="mt-6 grid gap-4 md:grid-cols-2">
          {fields.map(([k, l]) => (
            <label key={k} className="block">
              <span className="mb-1.5 block text-xs font-bold text-muted-foreground">{l}</span>
              <input value={form[k]} onChange={(e) => setForm({ ...form, [k]: e.target.value })} className={`h-11 w-full rounded-xl border bg-background px-3.5 text-sm outline-none focus:border-primary ${!form[k].trim() ? "border-destructive/40" : "border-input"}`} />
            </label>
          ))}
        </div>
        <div className="mt-6 flex gap-2">
          <button disabled={busy} className={primaryBtn}>{busy ? "Enregistrement…" : "Enregistrer"}</button>
          <button type="button" onClick={() => setForm(initial)} className={ghostBtn}><RotateCcw className="h-4 w-4" /> Réinitialiser</button>
        </div>
      </form>
    </div>
  );
}

// ---------- Utilisateurs & permissions ----------
const PERMS = ["Documents", "Processus", "Non-conformités", "Actions", "Audits", "Indicateurs", "Sites & utilisateurs", "Abonnement"];
const ROLES: { name: string; desc: string; rights: ("full" | "read" | "none")[] }[] = [
  { name: "Administrateur", desc: "Gère l'entreprise, les sites, les utilisateurs et les droits.", rights: ["full", "full", "full", "full", "full", "full", "full", "full"] },
  { name: "Responsable de site", desc: "Pilote les activités de son ou ses sites.", rights: ["full", "full", "full", "full", "full", "full", "read", "none"] },
  { name: "Collaborateur", desc: "Consulte et contribue selon ses affectations.", rights: ["read", "read", "full", "full", "read", "read", "none", "none"] },
  { name: "Auditeur", desc: "Accès en lecture et saisie des constats d'audit.", rights: ["read", "read", "read", "read", "full", "read", "none", "none"] },
];

export function RolesPage() {
  const { data: records = [] } = useRecords();
  const save = useSaveRecord();
  const collabs = records.filter((r) => r.kind === "collaborator");
  const sites = records.filter((r) => r.kind === "site");
  const assign = (r: QRecord, key: string, value: string, label: string) => {
    if (!confirm(`${label} pour ${r.title} ?`)) return;
    save.mutate({ id: r.id, kind: r.kind, title: r.title, status: r.status, data: { ...r.data, [key]: value }, previous: r });
  };
  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <Header title="Utilisateurs & permissions" desc="Rôles, affectations aux sites et matrice des droits. Chaque changement est inscrit au journal.">
        <Link to="/app/$section" params={{ section: "collaborateurs" }} search={{ new: 1 }} className={primaryBtn}>Inviter un collaborateur</Link>
        <button onClick={() => downloadCsv("utilisateurs.csv", [["Nom", "E-mail", "Rôle", "Site", "Statut"], ...collabs.map((c) => [c.title, String(c.data["email"] ?? ""), String(c.data["role"] ?? ""), sites.find((s) => s.id === c.data["site_id"])?.title ?? "", c.status])])} className={ghostBtn}><Download className="h-4 w-4" /> Exporter</button>
      </Header>
      <div className="overflow-x-auto rounded-2xl border border-border bg-card">
        <table className="w-full min-w-[720px] text-sm">
          <thead className="border-b border-border bg-background text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">
            <tr><th className="px-4 py-3">Collaborateur</th><th className="px-4 py-3">Rôle</th><th className="px-4 py-3">Site</th><th className="px-4 py-3">Statut</th><th className="px-4 py-3" /></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {collabs.length === 0 && <tr><td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">Aucun collaborateur. Invitez votre première personne.</td></tr>}
            {collabs.map((c) => (
              <tr key={c.id}>
                <td className="px-4 py-3"><p className="font-semibold text-foreground">{c.title}</p><p className="text-xs text-muted-foreground">{String(c.data["email"] ?? "")}</p></td>
                <td className="px-4 py-3">
                  <select value={String(c.data["role"] ?? "")} onChange={(e) => assign(c, "role", e.target.value, `Attribuer le rôle « ${e.target.value} »`)} className="h-9 rounded-lg border border-input bg-background px-2 text-sm">
                    <option value="">—</option>{ROLES.map((r) => <option key={r.name}>{r.name}</option>)}
                  </select>
                </td>
                <td className="px-4 py-3">
                  <select value={String(c.data["site_id"] ?? "")} onChange={(e) => assign(c, "site_id", e.target.value, "Affecter au site")} className="h-9 rounded-lg border border-input bg-background px-2 text-sm">
                    <option value="">— Aucun —</option>{sites.map((s) => <option key={s.id} value={s.id}>{s.title}</option>)}
                  </select>
                </td>
                <td className="px-4 py-3"><StatusBadge kind="collaborator" status={c.status} /></td>
                <td className="px-4 py-3"><RecordActions record={c} compact /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="overflow-x-auto rounded-2xl border border-border bg-card">
        <table className="w-full min-w-[720px] text-sm">
          <thead className="border-b border-border bg-background text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">
            <tr><th className="px-4 py-3">Rôle</th>{PERMS.map((p) => <th key={p} className="px-3 py-3 text-center">{p}</th>)}</tr>
          </thead>
          <tbody className="divide-y divide-border">
            {ROLES.map((r) => (
              <tr key={r.name}>
                <td className="px-4 py-3">
                  <p className="font-semibold text-foreground">{r.name}</p>
                  <p className="text-xs text-muted-foreground">{r.desc}</p>
                </td>
                {r.rights.map((v, i) => (
                  <td key={i} className="px-3 py-3 text-center">
                    {v === "full" ? <span className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary text-primary-foreground"><Check className="h-4 w-4" /></span>
                      : v === "read" ? <span className="text-xs font-bold text-primary">Lecture</span>
                      : <Minus className="mx-auto h-4 w-4 text-muted-foreground" />}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <p className="text-xs text-muted-foreground">Note : les collaborateurs n'ont pas encore leur propre connexion ; la matrice décrit les droits qui s'appliqueront à leurs comptes.</p>
    </div>
  );
}

// ---------- Préférences ----------
const PREF_KEY = "lq-prefs";
const DEFAULT_PREFS = { notif_tasks: true, notif_deadlines: true, notif_validation: true, widget_kpis: true, widget_priorities: true, widget_activity: true, widget_processes: true };
type Prefs = typeof DEFAULT_PREFS;
const PREF_LABELS: Record<keyof Prefs, string> = {
  notif_tasks: "Notifier les nouvelles tâches", notif_deadlines: "Alerter avant les échéances", notif_validation: "Notifier les retours de validation",
  widget_kpis: "Tableau de bord : indicateurs clés", widget_priorities: "Tableau de bord : priorités", widget_activity: "Tableau de bord : activité", widget_processes: "Tableau de bord : processus",
};

export function PreferencesPage() {
  const [prefs, setPrefs] = useState<Prefs>(() => {
    if (typeof window === "undefined") return DEFAULT_PREFS;
    try { return { ...DEFAULT_PREFS, ...JSON.parse(localStorage.getItem(PREF_KEY) ?? "{}") }; } catch { return DEFAULT_PREFS; }
  });
  return (
    <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
      <Header title="Préférences" desc="Vos préférences personnelles : elles ne modifient pas l'affichage des autres utilisateurs." />
      <div className="divide-y divide-border rounded-2xl border border-border bg-card">
        {(Object.keys(PREF_LABELS) as (keyof Prefs)[]).map((k) => (
          <label key={k} className="flex cursor-pointer items-center justify-between gap-3 px-5 py-3.5 text-sm">
            <span className="font-semibold text-foreground">{PREF_LABELS[k]}</span>
            <input type="checkbox" checked={prefs[k]} onChange={(e) => setPrefs({ ...prefs, [k]: e.target.checked })} className="h-5 w-5 accent-primary" />
          </label>
        ))}
      </div>
      <div className="flex flex-wrap gap-2">
        <button onClick={() => { localStorage.setItem(PREF_KEY, JSON.stringify(prefs)); toast.success("Préférences enregistrées"); }} className={primaryBtn}>Enregistrer</button>
        <button onClick={() => { setPrefs(DEFAULT_PREFS); localStorage.removeItem(PREF_KEY); toast.success("Préférences réinitialisées"); }} className={ghostBtn}><RotateCcw className="h-4 w-4" /> Réinitialiser</button>
        <button onClick={() => toast.info("Ceci est une notification de test LOGIQUALI.")} className={ghostBtn}>Tester la notification</button>
      </div>
    </div>
  );
}

// ---------- Journal sécurité ----------
export function JournalPage() {
  const { records } = useWs();
  const [q, setQ] = useState("");
  const [type, setType] = useState("");
  const [from, setFrom] = useState("");
  const events = useMemo(
    () => records.flatMap((r) => historyOf(r).map((h) => ({ ...h, r }))).sort((a, b) => b.at.localeCompare(a.at)),
    [records]
  );
  const types = [...new Set(events.map((e) => e.action))].sort();
  const list = events.filter((e) => (!type || e.action === type) && (!from || e.at >= from) && (!q || `${e.by} ${e.action} ${e.r.reference} ${e.r.title} ${e.comment ?? ""}`.toLowerCase().includes(q.toLowerCase())));
  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <Header title="Journal sécurité" desc="Créations, modifications, validations, changements de statut et de droits. Lecture seule.">
        <button onClick={() => downloadCsv("journal.csv", [["Date", "Utilisateur", "Événement", "Élément", "De", "Vers", "Commentaire"], ...list.map((e) => [new Date(e.at).toLocaleString("fr-FR"), e.by, e.action, `${e.r.reference} ${e.r.title}`, e.from ?? "", e.to ?? "", e.comment ?? ""])])} className={ghostBtn}><Download className="h-4 w-4" /> Exporter</button>
      </Header>
      <div className="flex flex-wrap gap-2">
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Rechercher un événement…" className="h-10 min-w-[220px] flex-1 rounded-xl border border-input bg-card px-4 text-sm outline-none focus:border-primary" />
        <select value={type} onChange={(e) => setType(e.target.value)} className="h-10 rounded-xl border border-input bg-card px-3 text-sm"><option value="">Tous les types</option>{types.map((t) => <option key={t}>{t}</option>)}</select>
        <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="h-10 rounded-xl border border-input bg-card px-3 text-sm" aria-label="Depuis le" />
      </div>
      <div className="overflow-x-auto rounded-2xl border border-border bg-card">
        <table className="w-full min-w-[720px] text-sm">
          <thead className="border-b border-border bg-background text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">
            <tr><th className="px-4 py-3">Date</th><th className="px-4 py-3">Utilisateur</th><th className="px-4 py-3">Événement</th><th className="px-4 py-3">Élément</th><th className="px-4 py-3">Détail</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {list.length === 0 && <tr><td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">Aucun événement.</td></tr>}
            {list.slice(0, 300).map((e, i) => (
              <tr key={i}>
                <td className="whitespace-nowrap px-4 py-2.5 text-muted-foreground">{new Date(e.at).toLocaleString("fr-FR")}</td>
                <td className="px-4 py-2.5">{e.by || "—"}</td>
                <td className="px-4 py-2.5 font-semibold text-foreground">{e.action}{e.to && e.from !== e.to ? ` → ${e.to}` : ""}</td>
                <td className="px-4 py-2.5"><span className="font-mono text-xs font-bold text-primary">{e.r.reference}</span> {e.r.title}</td>
                <td className="max-w-[280px] truncate px-4 py-2.5 text-muted-foreground" title={e.comment}>{e.comment ?? ""}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ---------- Abonnement ----------
const OFFERS = [
  { name: "Essentiel", price: "49 000 FCFA / mois", norms: 1, sites: 1, users: 10, desc: "1 norme au choix, 1 site." },
  { name: "Pack Intégré", price: "89 000 FCFA / mois", norms: 3, sites: 3, users: 30, desc: "Jusqu'à 3 normes (QSE), 3 sites." },
  { name: "Enterprise", price: "Sur devis", norms: 6, sites: 99, users: 999, desc: "Toutes les normes, sites illimités." },
];

export function SubscriptionPage() {
  const { records, ws } = useWs();
  const save = useSaveRecord();
  const qc = useQueryClient();
  const requests = records.filter((r) => r.kind === "subscription_request");
  const users = records.filter((r) => r.kind === "collaborator").length;
  const sites = records.filter((r) => r.kind === "site").length;
  const request = (offer: string, price: string, what: string) => {
    if (!confirm(`${what} : ${offer} ?`)) return;
    save.mutate({ kind: "subscription_request", title: `${what} — ${offer}`, status: "En attente", data: { offer, amount: price } });
  };
  const activeNorms = ws.norms.filter((n) => n.status === "Active");
  return (
    <div className="mx-auto max-w-5xl space-y-6 p-4 md:p-8">
      <Header title="Abonnement" desc="Offre, normes actives, échéances et historique." />
      <div className={`rounded-3xl border-[1.5px] bg-card p-6 shadow-xl shadow-primary/10 ${ws.expired ? "border-destructive" : "border-primary"}`}>
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex items-center gap-3">
            <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-primary-foreground"><CreditCard className="h-6 w-6" /></span>
            <div>
              <p className="text-xs font-bold uppercase tracking-wider text-primary">Offre actuelle</p>
              <p className="font-display text-xl font-extrabold text-foreground">{ws.inTrial ? "Période d'essai" : "Abonnement"}</p>
              <p className="text-xs text-muted-foreground">
                {ws.expired ? "Expiré — les rubriques normatives sont verrouillées, vos données sont conservées." : `${ws.daysLeft} jour(s) restant(s) · expiration le ${ws.nextExpiry?.toLocaleDateString("fr-FR")}`}
              </p>
            </div>
          </div>
          <button onClick={() => request(OFFERS[1]!.name, OFFERS[1]!.price, "Renouvellement")} className={primaryBtn}>Renouveler</button>
        </div>
        <div className="mt-6 grid gap-4 sm:grid-cols-3">
          {([["Utilisateurs", users, 10], ["Sites", sites, 3], ["Normes actives", activeNorms.length, 3]] as const).map(([l, v, max]) => (
            <div key={l}>
              <div className="flex justify-between text-sm"><span className="font-semibold text-foreground">{l}</span><span className="text-muted-foreground">{v} / {max}</span></div>
              <div className="mt-1.5 h-2 rounded-full bg-secondary"><div className="h-2 rounded-full bg-primary" style={{ width: `${Math.min(100, (v / max) * 100)}%` }} /></div>
            </div>
          ))}
        </div>
        <div className="mt-5 flex flex-wrap gap-2">
          {ws.norms.filter((n) => n.status !== "Inactive").map((n) => (
            <span key={n.code} className={`rounded-full px-3 py-1 text-xs font-bold ${n.status === "Active" ? "bg-primary-soft text-primary" : "bg-destructive/10 text-destructive"}`}>{n.code} · {n.status}{n.expiresAt ? ` · ${n.expiresAt.toLocaleDateString("fr-FR")}` : ""}</span>
          ))}
          <Link to="/app/$section" params={{ section: "normes" }} className="rounded-full border border-primary px-3 py-1 text-xs font-bold text-primary">Ajouter une norme</Link>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        {OFFERS.map((o) => (
          <div key={o.name} className="rounded-2xl border border-border bg-card p-5">
            <p className="font-display font-bold text-foreground">{o.name}</p>
            <p className="mt-1 text-sm font-semibold text-primary">{o.price}</p>
            <p className="mt-2 text-xs text-muted-foreground">{o.desc} {o.users} utilisateurs.</p>
            <button onClick={() => request(o.name, o.price, "Souscription")} className="mt-4 h-10 w-full rounded-xl border border-border text-sm font-semibold hover:border-primary hover:text-primary">Choisir cette offre</button>
          </div>
        ))}
      </div>

      <div className="rounded-2xl border border-border bg-card p-5">
        <div className="flex items-center justify-between">
          <p className="font-display font-bold text-foreground">Historique & paiements</p>
          <button onClick={() => { qc.invalidateQueries({ queryKey: RECORDS_KEY }); toast.success("Statut des paiements actualisé"); }} className="text-xs font-bold text-primary">Vérifier le paiement</button>
        </div>
        <p className="mt-1 text-xs text-muted-foreground">Le paiement en ligne n'est pas encore activé : chaque demande est transmise à notre équipe, qui vous recontacte pour le règlement.</p>
        <ul className="mt-4 divide-y divide-border">
          {requests.length === 0 && <li className="py-3 text-sm text-muted-foreground">Aucune demande pour le moment.</li>}
          {requests.map((r) => (
            <li key={r.id} className="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
              <span><span className="font-mono text-xs font-bold text-primary">{r.reference}</span> {r.title} · {String(r.data["amount"] ?? "")}</span>
              <span className="flex items-center gap-2">
                <span className="text-xs text-muted-foreground">{new Date(r.created_at).toLocaleDateString("fr-FR")}</span>
                <StatusBadge kind={r.kind} status={r.status} />
              </span>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}
