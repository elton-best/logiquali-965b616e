import { useMemo, useState } from "react";
import { AlertTriangle, CheckCircle2, Clock3, Edit3, FilterX, Lightbulb, List, Plus, Search, ShieldAlert, Table2, Trash2, X } from "lucide-react";
import { toast } from "sonner";
import { useCurrentSite } from "@/hooks/use-workspace";
import { useProcesses } from "@/integrations/backend/context";
import { useRiskMutations, useRisks, useSiteUsers } from "@/integrations/backend/risks";

const card = "rounded-2xl border border-border bg-card";
const input = "w-full rounded-xl border border-input bg-background px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15";
const button = "inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50";
const primary = `${button} bg-primary text-primary-foreground hover:bg-primary-dark`;
const secondary = `${button} border border-border bg-card hover:border-primary hover:text-primary`;
const muted = "text-sm text-muted-foreground";

const STATUS_OPTIONS = [
  ["identifie", "Identifié"],
  ["en_cours", "En cours"],
  ["traite", "Traité"],
  ["surveille", "Surveillé"],
  ["cloture", "Clôturé"],
] as const;
const LEVEL_OPTIONS = [
  ["faible", "Faible"],
  ["moyen", "Moyen"],
  ["eleve", "Élevé"],
  ["critique", "Critique"],
] as const;
type ViewMode = "grid" | "list" | "actions";
type RiskType = "risque" | "opportunite";
type RiskForm = {
  processId: string;
  type: RiskType;
  title: string;
  description: string;
  cause: string;
  consequence: string;
  probability: number;
  gravity: number;
  strategy: string;
  actions: string;
  targetDate: string;
  responsibleUserId: string;
  status: string;
  residualProbability: number | "";
  residualGravity: number | "";
};

const emptyForm = (type: RiskType = "risque"): RiskForm => ({
  processId: "",
  type,
  title: "",
  description: "",
  cause: "",
  consequence: "",
  probability: 1,
  gravity: 1,
  strategy: "",
  actions: "",
  targetDate: "",
  responsibleUserId: "",
  status: "identifie",
  residualProbability: "",
  residualGravity: "",
});

function objectValue(value: unknown): Record<string, unknown> {
  return value && typeof value === "object" ? value as Record<string, unknown> : {};
}

function scoreOf(risk: Record<string, unknown>): number {
  const stored = Number(risk.criticite ?? 0);
  if (stored > 0) return stored;
  return Number(risk.probabilite ?? 0) * Number(risk.gravite ?? 0);
}

function levelOf(score: number): string {
  if (score >= 12) return "critique";
  if (score >= 8) return "eleve";
  if (score >= 4) return "moyen";
  return "faible";
}

function levelLabel(level: string): string {
  return LEVEL_OPTIONS.find(([value]) => value === level)?.[1] ?? level;
}

function statusLabel(status: string): string {
  return STATUS_OPTIONS.find(([value]) => value === status)?.[1] ?? status;
}

function statusClass(status: string): string {
  if (["traite", "cloture"].includes(status)) return "bg-success/10 text-success";
  if (status === "en_cours") return "bg-primary-soft text-primary";
  if (status === "surveille") return "bg-warning/15 text-warning-foreground";
  return "bg-background text-muted-foreground";
}

function levelClass(level: string): string {
  if (level === "critique") return "bg-destructive/10 text-destructive";
  if (level === "eleve") return "bg-warning/15 text-warning-foreground";
  if (level === "moyen") return "bg-primary/10 text-primary";
  return "bg-success/10 text-success";
}

function processIdOf(risk: Record<string, unknown>): string {
  const process = objectValue(risk.process);
  return String(risk.process_id ?? process.id ?? "");
}

function processNameOf(risk: Record<string, unknown>, processById: Map<string, string>): string {
  const process = objectValue(risk.process);
  return String(process.title ?? process.name ?? processById.get(processIdOf(risk)) ?? "Processus non renseigné");
}

function actionsText(value: unknown): string {
  if (Array.isArray(value)) {
    return value.map((item) => {
      if (typeof item === "string") return item;
      const row = objectValue(item);
      return String(row.title ?? row.description ?? row.action ?? "");
    }).filter(Boolean).join("\n");
  }
  if (typeof value === "string") {
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? actionsText(parsed) : value;
    } catch {
      return value;
    }
  }
  return "";
}

function normalizeRisk(risk: Record<string, unknown>): Record<string, unknown> {
  return {
    ...risk,
    type: risk.type === "opportunite" ? "opportunite" : "risque",
    status: String(risk.status ?? "identifie"),
    process_id: risk.process_id ?? objectValue(risk.process).id,
  };
}

export function RisksPage() {
  const [siteId] = useCurrentSite();
  const { data: rawRisks = [], isLoading: loadingRisks } = useRisks(siteId);
  const { data: processResources = [], isLoading: loadingProcesses } = useProcesses(siteId);
  const { data: userResources = [] } = useSiteUsers(siteId);
  const mutations = useRiskMutations(siteId);
  const [type, setType] = useState<RiskType>("risque");
  const [view, setView] = useState<ViewMode>("grid");
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [level, setLevel] = useState("");
  const [processId, setProcessId] = useState("");
  const [editing, setEditing] = useState<Record<string, unknown> | null>(null);
  const [form, setForm] = useState<RiskForm>(emptyForm());

  const risks = useMemo(() => rawRisks.map((risk) => normalizeRisk(risk)), [rawRisks]);
  const processOptions = useMemo(() => processResources.map((process) => ({
    id: String(process.id ?? ""),
    name: String(process.title ?? process.name ?? `Processus #${process.id}`),
  })).filter((process) => process.id), [processResources]);
  const processById = useMemo(() => new Map(processOptions.map((process) => [process.id, process.name])), [processOptions]);
  const userOptions = useMemo(() => userResources.map((user) => {
    const name = String(user.name ?? (`${user.first_name ?? ""} ${user.last_name ?? ""}`.trim() || user.email || `Utilisateur #${user.id}`));
    return { id: String(user.id ?? ""), name };
  }).filter((user) => user.id), [userResources]);
  const visible = useMemo(() => risks.filter((risk) => {
    const riskType = String(risk.type) as RiskType;
    const score = scoreOf(risk);
    const query = search.trim().toLowerCase();
    const haystack = `${risk.title ?? ""} ${risk.description ?? ""} ${risk.cause ?? ""} ${processNameOf(risk, processById)}`.toLowerCase();
    return riskType === type
      && (!query || haystack.includes(query))
      && (!status || String(risk.status) === status)
      && (!level || levelOf(score) === level)
      && (!processId || processIdOf(risk) === processId);
  }), [level, processById, processId, risks, search, status, type]);
  const modeItems = useMemo(() => risks.filter((risk) => risk.type === type), [risks, type]);
  const highPriority = modeItems.filter((risk) => scoreOf(risk) >= 12).length;
  const closed = modeItems.filter((risk) => ["traite", "cloture"].includes(String(risk.status))).length;
  const inProgress = modeItems.filter((risk) => ["en_cours", "surveille"].includes(String(risk.status))).length;
  const completion = modeItems.length ? Math.round((closed / modeItems.length) * 100) : 0;
  const average = modeItems.length ? Math.round(modeItems.reduce((total, risk) => total + scoreOf(risk), 0) / modeItems.length * 10) / 10 : 0;

  if (!siteId) return <div className={`${card} mx-auto max-w-3xl p-8 text-center`}><ShieldAlert className="mx-auto h-9 w-9 text-primary" /><h2 className="mt-3 font-display text-lg font-bold">Sélectionnez un site</h2><p className={`mt-2 ${muted}`}>Les risques et opportunités sont chargés depuis le site courant.</p></div>;

  const openCreate = (nextType = type) => {
    setEditing(null);
    setType(nextType);
    setForm({ ...emptyForm(nextType), processId: processOptions[0]?.id ?? "" });
  };
  const openEdit = (risk: Record<string, unknown>) => {
    setEditing(risk);
    setType(risk.type === "opportunite" ? "opportunite" : "risque");
    setForm({
      processId: processIdOf(risk),
      type: risk.type === "opportunite" ? "opportunite" : "risque",
      title: String(risk.title ?? ""),
      description: String(risk.description ?? ""),
      cause: String(risk.cause ?? ""),
      consequence: String(risk.consequence ?? ""),
      probability: Number(risk.probabilite ?? 1),
      gravity: Number(risk.gravite ?? 1),
      strategy: String(risk.strategie ?? ""),
      actions: actionsText(risk.planned_actions ?? risk.actions_prevues),
      targetDate: String(risk.target_date ?? "").slice(0, 10),
      responsibleUserId: String(risk.responsible_user_id ?? ""),
      status: String(risk.status ?? "identifie"),
      residualProbability: risk.probabilite_residuelle == null ? "" : Number(risk.probabilite_residuelle),
      residualGravity: risk.gravite_residuelle == null ? "" : Number(risk.gravite_residuelle),
    });
  };
  const update = <K extends keyof RiskForm>(key: K, value: RiskForm[K]) => setForm((current) => ({ ...current, [key]: value }));
  const save = async () => {
    if (!form.processId || !form.title.trim()) {
      toast.error("Le processus et le titre sont obligatoires.");
      return;
    }
    try {
      await mutations.save.mutateAsync({
        id: editing?.id ? String(editing.id) : undefined,
        processId: form.processId,
        payload: {
          type: form.type,
          title: form.title.trim(),
          description: form.description.trim() || null,
          cause: form.cause.trim() || null,
          consequence: form.consequence.trim() || null,
          probabilite: Number(form.probability),
          gravite: Number(form.gravity),
          strategie: form.strategy || null,
          actions_prevues: form.actions.trim() || null,
          target_date: form.targetDate || null,
          responsible_user_id: form.responsibleUserId || null,
          status: form.status,
          probabilite_residuelle: form.residualProbability === "" ? null : Number(form.residualProbability),
          gravite_residuelle: form.residualGravity === "" ? null : Number(form.residualGravity),
        },
      });
      toast.success(editing ? "Élément mis à jour." : "Élément créé.");
      setEditing(null);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Impossible d'enregistrer cet élément.");
    }
  };
  const remove = async (risk: Record<string, unknown>) => {
    if (!window.confirm(`Supprimer « ${String(risk.title ?? "cet élément")} » ?`)) return;
    try {
      await mutations.remove.mutateAsync(String(risk.id));
      toast.success("Élément supprimé.");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Impossible de supprimer cet élément.");
    }
  };
  const resetFilters = () => { setSearch(""); setStatus(""); setLevel(""); setProcessId(""); };
  const currentScore = form.probability * form.gravity;
  const currentLevel = levelOf(currentScore);

  return (
    <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
      <div className="flex flex-wrap items-start justify-between gap-5">
        <div className="flex items-start gap-4"><div className="mt-1 rounded-2xl bg-primary-soft p-3 text-primary"><ShieldAlert className="h-6 w-6" /></div><div><p className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Planification ISO</p><h1 className="mt-1 font-display text-2xl font-extrabold md:text-3xl">Risques & opportunités</h1><p className="mt-1 max-w-2xl text-sm text-muted-foreground">Identifiez, évaluez et traitez les risques et opportunités du site courant.</p></div></div>
        <button className={primary} onClick={() => openCreate()}><Plus className="h-4 w-4" /> Ajouter</button>
      </div>

      <div className={`${card} grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4`}>
        <Metric icon={type === "risque" ? AlertTriangle : Lightbulb} label={type === "risque" ? "Risques affichés" : "Opportunités affichées"} value={modeItems.length} />
        <Metric icon={AlertTriangle} label="Priorité élevée" value={highPriority} />
        <Metric icon={Clock3} label="En cours / surveillé" value={inProgress} />
        <Metric icon={CheckCircle2} label="Traité / clôturé" value={`${completion}%`} />
      </div>

      <div className={`${card} space-y-4 p-4`}>
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex flex-wrap gap-2"><button className={type === "risque" ? primary : secondary} onClick={() => setType("risque")}><AlertTriangle className="h-4 w-4" /> Risques</button><button className={type === "opportunite" ? primary : secondary} onClick={() => setType("opportunite")}><Lightbulb className="h-4 w-4" /> Opportunités</button></div>
          <div className="flex gap-1 rounded-xl border border-border p-1"><ViewButton active={view === "grid"} label="Grille" icon={Table2} onClick={() => setView("grid")} /><ViewButton active={view === "list"} label="Tableau" icon={List} onClick={() => setView("list")} /><ViewButton active={view === "actions"} label="Actions" icon={CheckCircle2} onClick={() => setView("actions")} /></div>
        </div>
        <div className="grid gap-3 md:grid-cols-4"><div className="relative md:col-span-2"><Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" /><input className={`${input} pl-9`} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Rechercher une description, cause ou processus…" /></div><select className={input} value={processId} onChange={(event) => setProcessId(event.target.value)}><option value="">Tous les processus</option>{processOptions.map((process) => <option key={process.id} value={process.id}>{process.name}</option>)}</select><div className="flex gap-2"><select className={input} value={status} onChange={(event) => setStatus(event.target.value)}><option value="">Tous les statuts</option>{STATUS_OPTIONS.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select><button type="button" className="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-border hover:border-primary hover:text-primary" onClick={resetFilters} aria-label="Réinitialiser les filtres"><FilterX className="h-4 w-4" /></button></div></div>
        <div className="flex flex-wrap gap-2">{LEVEL_OPTIONS.map(([value, label]) => <button key={value} type="button" onClick={() => setLevel(level === value ? "" : value)} className={`rounded-full px-3 py-1.5 text-xs font-bold ${level === value ? levelClass(value) : "bg-background text-muted-foreground"}`}>{label} · {modeItems.filter((risk) => levelOf(scoreOf(risk)) === value).length}</button>)}<span className="self-center text-xs text-muted-foreground">Criticité moyenne : {average}</span></div>
      </div>

      {(loadingRisks || loadingProcesses) && <div className={`${card} p-10 text-center ${muted}`}>Chargement depuis Laravel…</div>}
      {!loadingRisks && !loadingProcesses && visible.length === 0 && <div className={`${card} p-10 text-center`}><ShieldAlert className="mx-auto h-9 w-9 text-primary" /><p className="mt-3 font-semibold">Aucun élément dans cette vue</p><p className={`mt-1 ${muted}`}>Aucun risque ou opportunité correspondant aux filtres n'est enregistré pour ce site.</p><button className={`${primary} mt-5`} onClick={() => openCreate()}><Plus className="h-4 w-4" /> Ajouter un élément</button></div>}

      {!loadingRisks && !loadingProcesses && view === "grid" && visible.length > 0 && <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">{visible.map((risk) => <RiskCard key={String(risk.id)} risk={risk} processName={processNameOf(risk, processById)} onEdit={() => openEdit(risk)} onRemove={() => remove(risk)} />)}</div>}
      {!loadingRisks && !loadingProcesses && view === "list" && visible.length > 0 && <RiskTable risks={visible} processName={(risk) => processNameOf(risk, processById)} onEdit={openEdit} onRemove={remove} />}
      {!loadingRisks && !loadingProcesses && view === "actions" && visible.length > 0 && <div className="space-y-3">{visible.map((risk) => <ActionRow key={String(risk.id)} risk={risk} processName={processNameOf(risk, processById)} onEdit={() => openEdit(risk)} />)}</div>}

      {editing && <RiskDialog form={form} score={currentScore} level={currentLevel} processOptions={processOptions} userOptions={userOptions} saving={mutations.save.isPending} onChange={update} onClose={() => setEditing(null)} onSave={save} />}
    </div>
  );
}

function Metric({ icon: Icon, label, value }: { icon: typeof AlertTriangle; label: string; value: string | number }) {
  return <div className="rounded-xl bg-background p-4"><Icon className="h-5 w-5 text-primary" /><p className="mt-3 text-xs font-bold uppercase text-muted-foreground">{label}</p><p className="mt-1 font-display text-2xl font-extrabold">{value}</p></div>;
}
function ViewButton({ active, label, icon: Icon, onClick }: { active: boolean; label: string; icon: typeof List; onClick: () => void }) { return <button type="button" onClick={onClick} className={`inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold ${active ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-primary"}`}><Icon className="h-3.5 w-3.5" /> {label}</button>; }
function RiskCard({ risk, processName, onEdit, onRemove }: { risk: Record<string, unknown>; processName: string; onEdit: () => void; onRemove: () => void }) {
  const score = scoreOf(risk); const level = levelOf(score); const opportunity = risk.type === "opportunite";
  return <article className={`${card} p-5`}><div className="flex items-start justify-between gap-3"><span className={`rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase ${opportunity ? "bg-success/10 text-success" : "bg-destructive/10 text-destructive"}`}>{opportunity ? "Opportunité" : "Risque"}{risk.code ? ` · ${String(risk.code)}` : ""}</span><span className={`rounded-full px-2.5 py-1 text-[11px] font-bold ${levelClass(level)}`}>{levelLabel(level)} · {score}</span></div><p className="mt-4 font-display font-bold">{String(risk.title ?? "Sans titre")}</p><p className="mt-1 text-xs text-muted-foreground">{processName}</p><p className="mt-3 line-clamp-3 text-sm text-muted-foreground">{String(risk.description ?? risk.cause ?? "Aucune description renseignée.")}</p><div className="mt-4 flex items-center justify-between gap-2"><span className={`rounded-full px-2.5 py-1 text-[11px] font-bold ${statusClass(String(risk.status))}`}>{statusLabel(String(risk.status))}</span><div className="flex gap-1"><button className="rounded-lg p-2 text-muted-foreground hover:bg-background hover:text-primary" onClick={onEdit} aria-label="Modifier"><Edit3 className="h-4 w-4" /></button><button className="rounded-lg p-2 text-muted-foreground hover:bg-destructive/10 hover:text-destructive" onClick={onRemove} aria-label="Supprimer"><Trash2 className="h-4 w-4" /></button></div></div></article>;
}
function RiskTable({ risks, processName, onEdit, onRemove }: { risks: Record<string, unknown>[]; processName: (risk: Record<string, unknown>) => string; onEdit: (risk: Record<string, unknown>) => void; onRemove: (risk: Record<string, unknown>) => void }) {
  return <div className={`${card} overflow-x-auto`}><table className="w-full min-w-[900px] text-sm"><thead className="bg-background text-left text-xs font-bold uppercase text-muted-foreground"><tr><th className="px-4 py-3">Élément</th><th className="px-4 py-3">Processus</th><th className="px-4 py-3">P × G</th><th className="px-4 py-3">Criticité</th><th className="px-4 py-3">Statut</th><th className="px-4 py-3">Actions</th></tr></thead><tbody className="divide-y divide-border">{risks.map((risk) => { const score = scoreOf(risk); const level = levelOf(score); return <tr key={String(risk.id)}><td className="px-4 py-3"><p className="font-semibold">{String(risk.title ?? "Sans titre")}</p><p className="text-xs text-muted-foreground">{risk.type === "opportunite" ? "Opportunité" : "Risque"}</p></td><td className="px-4 py-3 text-muted-foreground">{processName(risk)}</td><td className="px-4 py-3">{String(risk.probabilite ?? "—")} × {String(risk.gravite ?? "—")}</td><td className="px-4 py-3"><span className={`rounded-full px-2.5 py-1 text-[11px] font-bold ${levelClass(level)}`}>{levelLabel(level)} · {score}</span></td><td className="px-4 py-3"><span className={`rounded-full px-2.5 py-1 text-[11px] font-bold ${statusClass(String(risk.status))}`}>{statusLabel(String(risk.status))}</span></td><td className="px-4 py-3"><button className="mr-3 text-xs font-bold text-primary" onClick={() => onEdit(risk)}>Modifier</button><button className="text-xs font-bold text-destructive" onClick={() => onRemove(risk)}>Supprimer</button></td></tr>; })}</tbody></table></div>;
}
function ActionRow({ risk, processName, onEdit }: { risk: Record<string, unknown>; processName: string; onEdit: () => void }) { const action = actionsText(risk.planned_actions ?? risk.actions_prevues); return <div className={card}><div className="flex flex-wrap items-center justify-between gap-3 p-4"><div><p className="font-display font-bold">{String(risk.title ?? "Sans titre")}</p><p className="text-xs text-muted-foreground">{processName} · {risk.type === "opportunite" ? "Opportunité" : "Risque"}</p></div><button className={secondary} onClick={onEdit}><Edit3 className="h-4 w-4" /> Modifier</button></div><div className="border-t border-border p-4">{action ? <p className="whitespace-pre-line text-sm">{action}</p> : <p className={muted}>Aucune action de traitement renseignée.</p>}</div></div>; }

function RiskDialog({ form, score, level, processOptions, userOptions, saving, onChange, onClose, onSave }: { form: RiskForm; score: number; level: string; processOptions: { id: string; name: string }[]; userOptions: { id: string; name: string }[]; saving: boolean; onChange: <K extends keyof RiskForm>(key: K, value: RiskForm[K]) => void; onClose: () => void; onSave: () => void }) {
  return <div className="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-3 md:items-center"><div className={`${card} max-h-[92vh] w-full max-w-3xl overflow-y-auto p-5 shadow-2xl md:p-7`}><div className="flex items-start justify-between gap-4"><div><p className="text-xs font-bold uppercase tracking-wide text-primary">Données Laravel</p><h2 className="font-display text-xl font-extrabold">{form.type === "risque" ? "Renseigner un risque" : "Renseigner une opportunité"}</h2></div><button onClick={onClose} className="rounded-lg p-2 text-muted-foreground hover:bg-background" aria-label="Fermer"><X className="h-5 w-5" /></button></div><div className="mt-5 grid gap-4 md:grid-cols-2"><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Processus *</label><select className={input} value={form.processId} onChange={(event) => onChange("processId", event.target.value)}><option value="">Sélectionner un processus</option>{processOptions.map((process) => <option key={process.id} value={process.id}>{process.name}</option>)}</select></div><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Statut</label><select className={input} value={form.status} onChange={(event) => onChange("status", event.target.value)}>{STATUS_OPTIONS.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></div><div className="md:col-span-2"><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Titre *</label><input className={input} value={form.title} onChange={(event) => onChange("title", event.target.value)} placeholder="Ex. Dépendance à un fournisseur critique" /></div><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Description</label><textarea className={`${input} min-h-24`} value={form.description} onChange={(event) => onChange("description", event.target.value)} /></div><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">{form.type === "risque" ? "Cause" : "Bénéfice attendu"}</label><textarea className={`${input} min-h-24`} value={form.cause} onChange={(event) => onChange("cause", event.target.value)} /></div><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Conséquence / impact</label><textarea className={`${input} min-h-24`} value={form.consequence} onChange={(event) => onChange("consequence", event.target.value)} /></div><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Stratégie de traitement</label><select className={input} value={form.strategy} onChange={(event) => onChange("strategy", event.target.value)}><option value="">Non renseignée</option>{[["accepter", "Accepter"], ["reduire", "Réduire"], ["transferer", "Transférer"], ["eviter", "Éviter"], ["exploiter", "Exploiter"]].map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></div><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Actions prévues</label><textarea className={`${input} min-h-24`} value={form.actions} onChange={(event) => onChange("actions", event.target.value)} placeholder="Une action par ligne" /></div><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Échéance</label><input className={input} type="date" value={form.targetDate} onChange={(event) => onChange("targetDate", event.target.value)} /></div><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Probabilité (1 à 4)</label><select className={input} value={form.probability} onChange={(event) => onChange("probability", Number(event.target.value))}>{[1, 2, 3, 4].map((value) => <option key={value} value={value}>{value}</option>)}</select></div><div><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Gravité / pertinence (1 à 4)</label><select className={input} value={form.gravity} onChange={(event) => onChange("gravity", Number(event.target.value))}>{[1, 2, 3, 4].map((value) => <option key={value} value={value}>{value}</option>)}</select></div><div className="rounded-xl bg-background p-4 md:col-span-2"><p className="text-xs font-bold uppercase text-muted-foreground">Évaluation actuelle</p><p className="mt-1 font-display text-2xl font-extrabold">{score} · {levelLabel(level)}</p><div className="mt-3 grid gap-3 md:grid-cols-2"><select className={input} value={form.residualProbability} onChange={(event) => onChange("residualProbability", event.target.value ? Number(event.target.value) : "")}><option value="">Probabilité résiduelle</option>{[1, 2, 3, 4].map((value) => <option key={value} value={value}>{value}</option>)}</select><select className={input} value={form.residualGravity} onChange={(event) => onChange("residualGravity", event.target.value ? Number(event.target.value) : "")}><option value="">Gravité résiduelle</option>{[1, 2, 3, 4].map((value) => <option key={value} value={value}>{value}</option>)}</select></div></div><div className="md:col-span-2"><label className="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Responsable de traitement</label><select className={input} value={form.responsibleUserId} onChange={(event) => onChange("responsibleUserId", event.target.value)}><option value="">Non renseigné</option>{userOptions.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}</select></div></div><div className="mt-6 flex justify-end gap-2 border-t border-border pt-5"><button className={secondary} onClick={onClose}>Annuler</button><button className={primary} onClick={onSave} disabled={saving}>{saving ? "Enregistrement…" : "Enregistrer"}</button></div></div></div>;
}
