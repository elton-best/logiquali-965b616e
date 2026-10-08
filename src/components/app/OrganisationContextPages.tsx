import { useEffect, useMemo, useState, type ReactNode } from "react";
import { toast } from "sonner";
import {
  BarChart3,
  Building2,
  Check,
  ChevronRight,
  CircleAlert,
  Compass,
  Edit3,
  FileText,
  GitBranch,
  Globe2,
  Handshake,
  Leaf,
  Mail,
  MapPin,
  Plus,
  Save,
  Scale,
  Search,
  Settings2,
  ShieldCheck,
  Sparkles,
  Trash2,
  Users,
  X,
  Zap,
} from "lucide-react";
import { useCurrentSite } from "@/hooks/use-workspace";
import {
  useCartography,
  useContextMutations,
  useContexts,
  useProcesses,
  useScope,
  useScopeMutations,
  useStakeholderMutations,
  useStakeholders,
} from "@/integrations/backend/context";

const card = "rounded-2xl border border-border bg-card";
const input = "w-full rounded-xl border border-input bg-background px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15";
const button = "inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50";
const primary = `${button} bg-primary text-primary-foreground hover:bg-primary-dark`;
const secondary = `${button} border border-border bg-card hover:border-primary hover:text-primary`;
const muted = "text-sm text-muted-foreground";

function PageHeader({ eyebrow, title, description, icon: Icon, children }: { eyebrow: string; title: string; description: string; icon: typeof Building2; children?: ReactNode }) {
  return (
    <div className="flex flex-wrap items-start justify-between gap-5">
      <div className="flex min-w-0 items-start gap-4">
        <div className="mt-1 rounded-2xl bg-primary-soft p-3 text-primary"><Icon className="h-6 w-6" /></div>
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.16em] text-primary">{eyebrow}</p>
          <h1 className="mt-1 font-display text-2xl font-extrabold text-foreground md:text-3xl">{title}</h1>
          <p className="mt-1 max-w-2xl text-sm text-muted-foreground">{description}</p>
        </div>
      </div>
      {children && <div className="flex flex-wrap gap-2">{children}</div>}
    </div>
  );
}

function SiteRequired() {
  return <div className={`${card} mx-auto max-w-3xl p-8 text-center`}><MapPin className="mx-auto h-9 w-9 text-primary" /><h2 className="mt-3 font-display text-lg font-bold">Sélectionnez un site</h2><p className={`mt-2 ${muted}`}>Les éléments du contexte sont rattachés au site courant. Sélectionnez un site dans la barre supérieure pour consulter ou modifier ces données.</p></div>;
}

function EmptyState({ title, description, onCreate, label = "Commencer" }: { title: string; description: string; onCreate?: () => void; label?: string }) {
  return <div className={`${card} border-dashed p-10 text-center`}><Sparkles className="mx-auto h-8 w-8 text-primary" /><h2 className="mt-3 font-display text-lg font-bold">{title}</h2><p className={`mx-auto mt-2 max-w-lg ${muted}`}>{description}</p>{onCreate && <button className={`${primary} mt-5`} onClick={onCreate}><Plus className="h-4 w-4" /> {label}</button>}</div>;
}

function Label({ children }: { children: ReactNode }) { return <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">{children}</label>; }
function Stat({ value, label, tone = "text-primary" }: { value: string | number; label: string; tone?: string }) { return <div className="rounded-xl bg-background p-3"><p className={`font-display text-2xl font-extrabold ${tone}`}>{value}</p><p className="mt-0.5 text-xs font-semibold text-muted-foreground">{label}</p></div>; }

const SWOT_FIELDS = [
  { key: "swot_strengths", title: "Forces", hint: "Ce qui donne un avantage à l'organisme.", tone: "border-l-emerald-500", icon: ShieldCheck },
  { key: "swot_weaknesses", title: "Faiblesses", hint: "Les limites internes à maîtriser.", tone: "border-l-amber-500", icon: CircleAlert },
  { key: "swot_opportunities", title: "Opportunités", hint: "Les évolutions externes favorables.", tone: "border-l-sky-500", icon: Globe2 },
  { key: "swot_threats", title: "Menaces", hint: "Les facteurs externes susceptibles d'affecter les résultats.", tone: "border-l-rose-500", icon: Zap },
] as const;

const PESTEL_FIELDS = [
  ["pestel_political", "Politique", "Décisions publiques, politiques sectorielles…"],
  ["pestel_economic", "Économique", "Marché, inflation, financement, concurrence…"],
  ["pestel_social", "Social", "Évolutions démographiques, usages, attentes…"],
  ["pestel_technological", "Technologique", "Technologies, innovation, cybersécurité…"],
  ["pestel_environmental", "Environnemental", "Climat, ressources, impacts et contraintes…"],
  ["pestel_legal", "Légal", "Lois, normes et exigences applicables…"],
] as const;

type ContextForm = Record<(typeof SWOT_FIELDS)[number]["key"] | (typeof PESTEL_FIELDS)[number][0], string>;
const emptyContext: ContextForm = { swot_strengths: "", swot_weaknesses: "", swot_opportunities: "", swot_threats: "", pestel_political: "", pestel_economic: "", pestel_social: "", pestel_technological: "", pestel_environmental: "", pestel_legal: "" };

function asText(value: unknown) {
  if (value == null) return "";
  if (typeof value === "string") return value;
  try { return JSON.stringify(value, null, 2); } catch { return String(value); }
}

export function ContextOrganisationPage() {
  const [siteId] = useCurrentSite();
  const { data = [], isLoading, isError } = useContexts(siteId);
  const { save } = useContextMutations(siteId);
  const [form, setForm] = useState<ContextForm>(emptyContext);
  const [activeTab, setActiveTab] = useState<"swot" | "pestel">("swot");
  const existing = data[0] as Record<string, unknown> | undefined;

  useEffect(() => {
    if (existing) setForm(Object.fromEntries(Object.keys(emptyContext).map((key) => [key, asText(existing[key])])) as ContextForm);
  }, [existing]);

  if (!siteId) return <div className="mx-auto max-w-7xl p-4 md:p-8"><SiteRequired /></div>;
  const update = (key: keyof ContextForm, value: string) => setForm((current) => ({ ...current, [key]: value }));
  const saveForm = async () => {
    try {
      await save.mutateAsync({ id: existing?.id ? String(existing.id) : undefined, payload: { site_id: Number(siteId), type: "swot_pestel", title: "Analyse SWOT/PESTEL", category: "other", description: "Analyse du contexte de l'organisme", ...form } });
      toast.success("Analyse du contexte enregistrée.");
    } catch (error) { toast.error(error instanceof Error ? error.message : "Impossible d'enregistrer l'analyse."); }
  };
  if (isLoading) return <div className="mx-auto max-w-7xl p-4 text-sm text-muted-foreground md:p-8">Chargement du contexte…</div>;
  return <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
    <PageHeader eyebrow="Contexte de l'organisme" title="Compréhension de l'organisme" description="Documentez les enjeux internes et externes qui influencent la finalité, les orientations et les résultats du système de management." icon={Compass}>
      <button className={primary} onClick={saveForm} disabled={save.isPending}><Save className="h-4 w-4" /> {save.isPending ? "Enregistrement…" : "Enregistrer"}</button>
    </PageHeader>
    {isError && <div className="rounded-xl border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">Les données ne sont pas disponibles. Vérifiez la connexion au backend Laravel.</div>}
    <div className={`${card} p-4`}>
      <div className="flex flex-wrap items-center justify-between gap-3"><div><p className="font-display text-lg font-bold">Analyse SWOT / PESTEL</p><p className={muted}>Une même fiche côté Laravel, organisée en deux lectures pour faciliter la revue.</p></div><div className="flex rounded-xl bg-background p-1"><button className={`rounded-lg px-4 py-2 text-sm font-semibold ${activeTab === "swot" ? "bg-card text-primary shadow-sm" : "text-muted-foreground"}`} onClick={() => setActiveTab("swot")}>SWOT</button><button className={`rounded-lg px-4 py-2 text-sm font-semibold ${activeTab === "pestel" ? "bg-card text-primary shadow-sm" : "text-muted-foreground"}`} onClick={() => setActiveTab("pestel")}>PESTEL</button></div></div>
      {activeTab === "swot" ? <div className="mt-5 grid gap-4 md:grid-cols-2">{SWOT_FIELDS.map(({ key, title, hint, tone, icon: Icon }) => <div key={key} className={`${card} border-l-4 ${tone} p-4`}><div className="flex items-start gap-3"><Icon className="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" /><div className="min-w-0 flex-1"><p className="font-display font-bold">{title}</p><p className="mt-1 text-xs text-muted-foreground">{hint}</p><textarea className={`${input} mt-3 min-h-32 resize-y`} value={form[key]} onChange={(event) => update(key, event.target.value)} placeholder="Saisissez les éléments identifiés…" /></div></div></div>)}</div> : <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">{PESTEL_FIELDS.map(([key, title, hint]) => <div key={key} className={`${card} p-4`}><div className="flex items-center gap-2"><Globe2 className="h-4 w-4 text-primary" /><p className="font-display font-bold">{title}</p></div><p className="mt-1 min-h-8 text-xs text-muted-foreground">{hint}</p><textarea className={`${input} mt-3 min-h-32 resize-y`} value={form[key]} onChange={(event) => update(key, event.target.value)} placeholder="Éléments pertinents…" /></div>)}</div>}
    </div>
    <div className="grid gap-3 sm:grid-cols-4"><Stat value={form.swot_strengths.trim() ? "Oui" : "—"} label="Forces renseignées" tone="text-emerald-600" /><Stat value={form.swot_weaknesses.trim() ? "Oui" : "—"} label="Faiblesses renseignées" tone="text-amber-600" /><Stat value={form.swot_opportunities.trim() ? "Oui" : "—"} label="Opportunités renseignées" tone="text-sky-600" /><Stat value={form.swot_threats.trim() ? "Oui" : "—"} label="Menaces renseignées" tone="text-rose-600" /></div>
  </div>;
}

const STAKEHOLDER_TYPES = [["client", "Client"], ["supplier", "Fournisseur"], ["partner", "Partenaire"], ["regulator", "Autorité / organisme"], ["employee", "Collaborateur"], ["shareholder", "Actionnaire"], ["other", "Autre"]] as const;
const RELEVANCE = [["low", "Faible"], ["medium", "Moyenne"], ["high", "Élevée"]] as const;
type StakeholderForm = { name: string; type: string; relevance_degree: string; contact_person: string; contact_email: string; needs_expectations: string };
const emptyStakeholder: StakeholderForm = { name: "", type: "client", relevance_degree: "medium", contact_person: "", contact_email: "", needs_expectations: "" };

export function StakeholdersPage() {
  const [siteId] = useCurrentSite();
  const { data = [], isLoading } = useStakeholders(siteId);
  const { save, remove } = useStakeholderMutations(siteId);
  const [form, setForm] = useState<StakeholderForm>(emptyStakeholder);
  const [editingId, setEditingId] = useState<string>();
  const [query, setQuery] = useState("");
  const [showForm, setShowForm] = useState(false);
  const visible = useMemo(() => data.filter((item) => String(item.name ?? "").toLowerCase().includes(query.toLowerCase())), [data, query]);
  if (!siteId) return <div className="mx-auto max-w-7xl p-4 md:p-8"><SiteRequired /></div>;
  const change = (key: keyof StakeholderForm, value: string) => setForm((current) => ({ ...current, [key]: value }));
  const startCreate = () => { setEditingId(undefined); setForm(emptyStakeholder); setShowForm(true); };
  const startEdit = (item: Record<string, unknown>) => { setEditingId(String(item.id)); setForm({ name: String(item.name ?? ""), type: String(item.type ?? "other"), relevance_degree: String(item.relevance_degree ?? "medium"), contact_person: String(item.contact_person ?? ""), contact_email: String(item.contact_email ?? ""), needs_expectations: String(item.needs_expectations ?? "") }); setShowForm(true); };
  const submit = async () => {
    if (!form.name.trim()) { toast.error("Le nom de la partie intéressée est requis."); return; }
    try { await save.mutateAsync({ id: editingId, payload: { site_id: Number(siteId), ...form, needs: form.needs_expectations.trim() ? [{ description: form.needs_expectations.trim(), priority: form.relevance_degree, requirements: [] }] : [] } }); toast.success("Partie intéressée enregistrée."); setShowForm(false); } catch (error) { toast.error(error instanceof Error ? error.message : "Impossible d'enregistrer la partie intéressée."); }
  };
  const destroy = async (id: string) => { if (!window.confirm("Supprimer cette partie intéressée ?")) return; try { await remove.mutateAsync(id); toast.success("Partie intéressée supprimée."); } catch (error) { toast.error(error instanceof Error ? error.message : "Suppression impossible."); } };
  return <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
    <PageHeader eyebrow="Contexte de l'organisme" title="Parties intéressées" description="Identifiez les parties pertinentes pour le système de management, leurs attentes et le niveau de prise en compte attendu." icon={Handshake}><button className={primary} onClick={startCreate}><Plus className="h-4 w-4" /> Ajouter une partie</button></PageHeader>
    <div className="grid gap-3 sm:grid-cols-3"><Stat value={data.length} label="Parties identifiées" /><Stat value={data.filter((item) => item.relevance_degree === "high").length} label="Priorité élevée" tone="text-rose-600" /><Stat value={data.filter((item) => item.needs_expectations).length} label="Avec attentes décrites" tone="text-emerald-600" /></div>
    {showForm && <div className={`${card} p-5`}><div className="flex items-start justify-between gap-4"><div><h2 className="font-display text-lg font-bold">{editingId ? "Modifier la partie intéressée" : "Nouvelle partie intéressée"}</h2><p className={muted}>Les données sont enregistrées dans le registre Laravel du site courant.</p></div><button className="rounded-lg p-2 hover:bg-background" onClick={() => setShowForm(false)} aria-label="Fermer"><X className="h-5 w-5" /></button></div><div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3"><div className="lg:col-span-2"><Label>Nom / désignation</Label><input className={input} value={form.name} onChange={(e) => change("name", e.target.value)} placeholder="Ex. Clients grands comptes" /></div><div><Label>Type</Label><select className={input} value={form.type} onChange={(e) => change("type", e.target.value)}>{STAKEHOLDER_TYPES.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></div><div><Label>Niveau de pertinence</Label><select className={input} value={form.relevance_degree} onChange={(e) => change("relevance_degree", e.target.value)}>{RELEVANCE.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></div><div><Label>Contact / responsable</Label><input className={input} value={form.contact_person} onChange={(e) => change("contact_person", e.target.value)} placeholder="Nom du contact" /></div><div><Label>E-mail de contact</Label><input className={input} type="email" value={form.contact_email} onChange={(e) => change("contact_email", e.target.value)} placeholder="contact@exemple.com" /></div><div className="md:col-span-2 lg:col-span-3"><Label>Besoins et attentes</Label><textarea className={`${input} min-h-28`} value={form.needs_expectations} onChange={(e) => change("needs_expectations", e.target.value)} placeholder="Attentes, exigences contractuelles, exigences réglementaires…" /></div></div><div className="mt-5 flex justify-end gap-2"><button className={secondary} onClick={() => setShowForm(false)}>Annuler</button><button className={primary} onClick={submit} disabled={save.isPending}><Save className="h-4 w-4" /> {save.isPending ? "Enregistrement…" : "Enregistrer"}</button></div></div>}
    <div className={`${card} p-4`}><div className="flex flex-wrap items-center justify-between gap-3"><div><h2 className="font-display text-lg font-bold">Registre des parties intéressées</h2><p className={muted}>{visible.length} élément(s) pour le site courant</p></div><div className="relative w-full sm:w-72"><Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" /><input className={`${input} pl-9`} value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Rechercher…" /></div></div>{isLoading ? <p className="py-10 text-center text-sm text-muted-foreground">Chargement du registre…</p> : visible.length === 0 ? <EmptyState title="Aucune partie intéressée" description="Commencez par identifier vos clients, fournisseurs, collaborateurs, autorités et partenaires pertinents." onCreate={startCreate} label="Ajouter la première" /> : <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">{visible.map((item) => <div key={String(item.id)} className={`${card} p-4`}><div className="flex items-start justify-between gap-3"><div className="flex min-w-0 items-start gap-3"><div className="rounded-xl bg-primary-soft p-2 text-primary"><Users className="h-4 w-4" /></div><div className="min-w-0"><p className="truncate font-semibold">{String(item.name ?? "Sans nom")}</p><p className="mt-0.5 text-xs text-muted-foreground">{STAKEHOLDER_TYPES.find(([value]) => value === item.type)?.[1] ?? item.type}</p></div></div><span className={`rounded-full px-2 py-1 text-[11px] font-bold ${item.relevance_degree === "high" ? "bg-rose-100 text-rose-700" : item.relevance_degree === "low" ? "bg-slate-100 text-slate-600" : "bg-amber-100 text-amber-700"}`}>{RELEVANCE.find(([value]) => value === item.relevance_degree)?.[1] ?? "Moyenne"}</span></div><div className="mt-4 space-y-2 text-sm">{item.contact_person && <p className="flex items-center gap-2 text-muted-foreground"><Users className="h-3.5 w-3.5" /> {String(item.contact_person)}</p>}{item.contact_email && <p className="flex items-center gap-2 truncate text-muted-foreground"><Mail className="h-3.5 w-3.5" /> {String(item.contact_email)}</p>}<p className="line-clamp-3 whitespace-pre-line text-muted-foreground">{String(item.needs_expectations ?? "Aucune attente décrite.")}</p></div><div className="mt-4 flex justify-end gap-1 border-t border-border pt-3"><button className="rounded-lg p-2 text-muted-foreground hover:bg-background hover:text-primary" onClick={() => startEdit(item)} aria-label="Modifier"><Edit3 className="h-4 w-4" /></button><button className="rounded-lg p-2 text-muted-foreground hover:bg-background hover:text-destructive" onClick={() => destroy(String(item.id))} aria-label="Supprimer"><Trash2 className="h-4 w-4" /></button></div></div>)}</div>}</div>
  </div>;
}

function lines(value: unknown): string[] { if (Array.isArray(value)) return value.map(String).filter(Boolean); if (typeof value !== "string") return []; try { const parsed = JSON.parse(value); return Array.isArray(parsed) ? parsed.map(String).filter(Boolean) : value.split("\n").map((item) => item.trim()).filter(Boolean); } catch { return value.split("\n").map((item) => item.trim()).filter(Boolean); } }
function joinLines(value: unknown) { return lines(value).join("\n"); }

type ScopeForm = { version: string; objective: string; scope: string; scope_definition: string; products_services: string; organizational_units: string; locations: string; scope_exclusions: string; exclusions_justification: string };
const emptyScope: ScopeForm = { version: "1.0", objective: "", scope: "", scope_definition: "", products_services: "", organizational_units: "", locations: "", scope_exclusions: "", exclusions_justification: "" };

export function ApplicationScopePage() {
  const [siteId] = useCurrentSite();
  const { data: scopes = [], isLoading: scopeLoading } = useScope(siteId);
  const { data: processes = [], isLoading: processesLoading } = useProcesses(siteId);
  const { save } = useScopeMutations(siteId);
  const [form, setForm] = useState<ScopeForm>(emptyScope);
  const existing = scopes[0] as Record<string, unknown> | undefined;
  useEffect(() => { if (existing) setForm({ version: String(existing.version ?? "1.0"), objective: String(existing.objective ?? existing.document_objective ?? ""), scope: String(existing.scope ?? ""), scope_definition: String(existing.scope_definition ?? ""), products_services: joinLines(existing.products_services), organizational_units: joinLines(existing.organizational_units), locations: joinLines(existing.locations), scope_exclusions: String(existing.scope_exclusions ?? existing.exclusions ?? ""), exclusions_justification: String(existing.exclusions_justification ?? existing.iso_exclusions_justification ?? "") }); }, [existing]);
  if (!siteId) return <div className="mx-auto max-w-7xl p-4 md:p-8"><SiteRequired /></div>;
  const change = (key: keyof ScopeForm, value: string) => setForm((current) => ({ ...current, [key]: value }));
  const saveForm = async () => { try { await save.mutateAsync({ id: existing?.id ? String(existing.id) : undefined, payload: { site_id: Number(siteId), is_current: true, version: form.version, objective: form.objective, document_objective: form.objective, scope: form.scope, scope_definition: form.scope_definition, products_services: lines(form.products_services), organizational_units: lines(form.organizational_units), locations: lines(form.locations), scope_exclusions: form.scope_exclusions, exclusions: form.scope_exclusions, exclusions_justification: form.exclusions_justification, included_processes: processes.map((item) => item.id).filter(Boolean) } }); toast.success("Domaine d'application enregistré."); } catch (error) { toast.error(error instanceof Error ? error.message : "Impossible d'enregistrer le domaine d'application."); } };
  const groups = [{ key: "management", label: "Management", tone: "bg-emerald-50 text-emerald-700 border-emerald-200", icon: BarChart3 }, { key: "realization", label: "Réalisation", tone: "bg-orange-50 text-orange-700 border-orange-200", icon: GitBranch }, { key: "support", label: "Support", tone: "bg-violet-50 text-violet-700 border-violet-200", icon: Settings2 }];
  const category = (item: Record<string, unknown>) => { const value = String(item.category ?? item.type ?? "").toLowerCase(); if (["management", "pilotage"].includes(value)) return "management"; if (["support", "soutien"].includes(value)) return "support"; return "realization"; };
  return <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8"><PageHeader eyebrow="Contexte de l'organisme" title="Domaine d'application" description="Définissez le périmètre du système de management, ses activités, ses implantations et les éventuelles exclusions justifiées." icon={Scale}><button className={primary} onClick={saveForm} disabled={save.isPending}><Save className="h-4 w-4" /> {save.isPending ? "Enregistrement…" : "Enregistrer"}</button></PageHeader><div className="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]"><div className={`${card} p-5`}><div className="flex items-start justify-between"><div><h2 className="font-display text-lg font-bold">Fiche de périmètre</h2><p className={muted}>Version et formulation validées pour le site courant.</p></div><div className="w-24"><Label>Version</Label><input className={input} value={form.version} onChange={(e) => change("version", e.target.value)} /></div></div><div className="mt-5 space-y-4"><div><Label>Objectif du document</Label><textarea className={`${input} min-h-24`} value={form.objective} onChange={(e) => change("objective", e.target.value)} placeholder="Finalité du système de management…" /></div><div><Label>Domaine d'application</Label><textarea className={`${input} min-h-28`} value={form.scope} onChange={(e) => change("scope", e.target.value)} placeholder="Activités, produits et services couverts…" /></div><div><Label>Description détaillée du périmètre</Label><textarea className={`${input} min-h-28`} value={form.scope_definition} onChange={(e) => change("scope_definition", e.target.value)} placeholder="Décrivez les limites organisationnelles et opérationnelles…" /></div><div className="grid gap-4 md:grid-cols-2"><div><Label>Produits / services <span className="normal-case font-normal">(une ligne par élément)</span></Label><textarea className={`${input} min-h-24`} value={form.products_services} onChange={(e) => change("products_services", e.target.value)} /></div><div><Label>Unités organisationnelles</Label><textarea className={`${input} min-h-24`} value={form.organizational_units} onChange={(e) => change("organizational_units", e.target.value)} /></div><div><Label>Implantations / lieux</Label><textarea className={`${input} min-h-24`} value={form.locations} onChange={(e) => change("locations", e.target.value)} /></div><div><Label>Exclusions du périmètre</Label><textarea className={`${input} min-h-24`} value={form.scope_exclusions} onChange={(e) => change("scope_exclusions", e.target.value)} /></div></div><div><Label>Justification des exclusions</Label><textarea className={`${input} min-h-24`} value={form.exclusions_justification} onChange={(e) => change("exclusions_justification", e.target.value)} /></div></div></div><div className="space-y-4"><div className={`${card} p-5`}><div className="flex items-center gap-2"><FileText className="h-5 w-5 text-primary" /><h2 className="font-display text-lg font-bold">Repères</h2></div><div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-1"><Stat value={processes.length} label="Processus rattachés" /><Stat value={lines(form.products_services).length} label="Produits / services" tone="text-emerald-600" /><Stat value={form.scope_exclusions.trim() ? "Oui" : "Non"} label="Exclusions déclarées" tone="text-amber-600" /></div></div><div className={`${card} p-5`}><div className="flex items-center gap-2"><Check className="h-5 w-5 text-emerald-600" /><h2 className="font-display text-lg font-bold">Checklist ISO</h2></div><ul className="mt-4 space-y-3 text-sm text-muted-foreground"><li className="flex gap-2"><ChevronRight className="h-4 w-4 shrink-0 text-primary" />Activités et services couverts</li><li className="flex gap-2"><ChevronRight className="h-4 w-4 shrink-0 text-primary" />Limites organisationnelles explicites</li><li className="flex gap-2"><ChevronRight className="h-4 w-4 shrink-0 text-primary" />Exclusions documentées et justifiées</li></ul></div></div></div><div className={`${card} p-5`}><div className="flex flex-wrap items-end justify-between gap-3"><div><h2 className="font-display text-lg font-bold">Processus inclus dans le périmètre</h2><p className={muted}>Lecture issue de la ressource <code className="rounded bg-background px-1">processes</code> du backend Laravel.</p></div>{!processesLoading && <span className="rounded-full bg-primary-soft px-3 py-1 text-xs font-bold text-primary">{processes.length} processus</span>}</div>{scopeLoading || processesLoading ? <p className="py-8 text-sm text-muted-foreground">Chargement du périmètre et des processus…</p> : processes.length === 0 ? <EmptyState title="Aucun processus pour ce site" description="Les processus apparaîtront ici dès qu'ils seront créés dans le Système de management." /> : <div className="mt-5 grid gap-4 md:grid-cols-3">{groups.map(({ key, label, tone, icon: Icon }) => { const items = processes.filter((item) => category(item) === key); return <div key={key} className={`rounded-2xl border p-4 ${tone}`}><div className="flex items-center justify-between gap-2"><div className="flex items-center gap-2"><Icon className="h-5 w-5" /><p className="font-display font-bold">{label}</p></div><span className="rounded-full bg-white/70 px-2 py-0.5 text-xs font-bold">{items.length}</span></div><div className="mt-4 space-y-2">{items.length === 0 ? <p className="text-xs opacity-70">Aucun processus</p> : items.map((item) => <div key={String(item.id)} className="rounded-xl bg-white/75 p-3 text-sm font-semibold text-foreground"><div className="flex items-start justify-between gap-2"><span>{String(item.title ?? item.name ?? "Processus sans nom")}</span><ChevronRight className="h-4 w-4 shrink-0 opacity-60" /></div>{item.purpose && <p className="mt-1 line-clamp-2 text-xs font-normal opacity-75">{String(item.purpose)}</p>}</div>)}</div></div>; })}</div>}</div></div>;
}

export function ManagementSystemPage() {
  const [siteId] = useCurrentSite();
  const { data: processes = [], isLoading } = useCartography(siteId);
  const groups = [{ key: "management", label: "Processus de management", color: "text-emerald-700 bg-emerald-50 border-emerald-200", icon: BarChart3 }, { key: "realization", label: "Processus de réalisation", color: "text-orange-700 bg-orange-50 border-orange-200", icon: GitBranch }, { key: "support", label: "Processus support", color: "text-violet-700 bg-violet-50 border-violet-200", icon: Settings2 }];
  const category = (item: Record<string, unknown>) => { const value = String(item.category ?? item.type ?? "").toLowerCase(); if (["management", "pilotage"].includes(value)) return "management"; if (["support", "soutien"].includes(value)) return "support"; return "realization"; };
  if (!siteId) return <div className="mx-auto max-w-7xl p-4 md:p-8"><SiteRequired /></div>;
  return <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8"><PageHeader eyebrow="Contexte de l'organisme" title="Système de management" description="Visualisez la cartographie du système de management et les interactions entre les processus du site courant." icon={GitBranch}><div className="rounded-xl border border-border bg-card px-3 py-2 text-xs font-semibold text-muted-foreground">Lecture des processus Laravel</div></PageHeader><div className="grid gap-3 sm:grid-cols-3"><Stat value={processes.length} label="Processus" /><Stat value={processes.filter((item) => category(item) === "management").length} label="Management" tone="text-emerald-600" /><Stat value={processes.filter((item) => category(item) === "realization").length} label="Réalisation" tone="text-orange-600" /></div>{isLoading ? <div className={`${card} p-8 text-center ${muted}`}>Chargement de la cartographie…</div> : processes.length === 0 ? <EmptyState title="Cartographie vide" description="Créez vos processus pour construire la cartographie du système de management." /> : <div className="space-y-5">{groups.map(({ key, label, color, icon: Icon }) => { const items = processes.filter((item) => category(item) === key); return <section key={key} className={`${card} overflow-hidden`}><div className={`flex items-center gap-3 border-b p-4 ${color}`}><Icon className="h-5 w-5" /><h2 className="font-display font-bold">{label}</h2><span className="ml-auto rounded-full bg-white/70 px-2.5 py-1 text-xs font-bold">{items.length}</span></div>{items.length > 0 && <div className="grid gap-4 p-4 md:grid-cols-2 lg:grid-cols-3">{items.map((item) => <article key={String(item.id)} className="rounded-xl border border-border bg-background p-4"><div className="flex items-start justify-between gap-3"><div><p className="font-mono text-[11px] font-bold uppercase text-primary">{String(item.code ?? "PROCESSUS")}</p><h3 className="mt-1 font-display font-bold">{String(item.title ?? item.name ?? "Processus sans nom")}</h3></div><GitBranch className="h-4 w-4 shrink-0 text-muted-foreground" /></div>{item.purpose && <p className="mt-3 text-sm text-muted-foreground">{String(item.purpose)}</p>}<div className="mt-4 grid gap-2 text-xs text-muted-foreground"><div className="rounded-lg bg-card p-2"><span className="font-bold text-foreground">Entrées : </span>{lines(item.sequences).length ? lines(item.sequences).join(", ") : "À documenter"}</div><div className="rounded-lg bg-card p-2"><span className="font-bold text-foreground">Statut : </span>{String(item.status ?? "draft")}</div></div></article>)}</div>}</section>; })}</div>}</div>;
}
