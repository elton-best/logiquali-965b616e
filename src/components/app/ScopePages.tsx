import { Link, useNavigate } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { useEffect, useState, type ReactNode } from "react";
import { toast } from "sonner";
import {
  ArrowLeft,
  ArrowRight,
  Building2,
  Check,
  CheckCircle2,
  FileText,
  GitBranch,
  Layers3,
  MapPin,
  Plus,
  Save,
  Scale,
  ShieldAlert,
  X,
  type LucideIcon,
} from "lucide-react";
import { useCurrentSite } from "@/hooks/use-workspace";
import { backendApi } from "@/integrations/backend/client";
import { flattenResource } from "@/integrations/backend/context";
import { useProcesses, useScope, useScopeMutations } from "@/integrations/backend/context";

const card = "rounded-2xl border border-border bg-card";
const input = "w-full rounded-xl border border-input bg-background px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15";
const button = "inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50";
const primary = `${button} bg-primary text-primary-foreground hover:bg-primary-dark`;
const secondary = `${button} border border-border bg-card hover:border-primary hover:text-primary`;
const muted = "text-sm text-muted-foreground";
const LINKS = [
  ["contexte", "Compréhension"],
  ["parties-interessees", "Parties intéressées"],
  ["perimetre", "Domaine d'application"],
  ["processus", "Processus"],
] as const;
const STEP_META: ReadonlyArray<readonly [string, LucideIcon]> = [
  ["Documents", FileText],
  ["Définition", Scale],
  ["Processus", GitBranch],
  ["Produits & services", Layers3],
  ["Unités", Building2],
  ["Lieux", MapPin],
  ["Exclusions", ShieldAlert],
  ["Récapitulatif", CheckCircle2],
];

type ItemForm = { value: string };
type ScopeProcess = { id?: string; name: string; type: string; abbreviation?: string };
type NormExclusion = { chapters: string; justification: string };
type ScopeForm = {
  version: string;
  objective: string;
  definition: string;
  documents: ItemForm[];
  processes: ScopeProcess[];
  products: ItemForm[];
  units: ItemForm[];
  locations: ItemForm[];
  exclusions: string;
  exclusionsJustification: string;
  isoExclusions: string;
  isoExclusionsJustification: string;
  normExclusions: Record<string, NormExclusion>;
};

type NormOption = { code: string; name: string };

const emptyItem = (): ItemForm => ({ value: "" });
const initialForm = (): ScopeForm => ({
  version: "1.0",
  objective: "",
  definition: "",
  documents: [emptyItem()],
  processes: [],
  products: [emptyItem()],
  units: [emptyItem()],
  locations: [emptyItem()],
  exclusions: "",
  exclusionsJustification: "",
  isoExclusions: "",
  isoExclusionsJustification: "",
  normExclusions: {},
});

function Nav() {
  return (
    <nav aria-label="Contexte de l'organisme" className={`${card} flex flex-wrap gap-1 p-1.5`}>
      {LINKS.map(([slug, label]) => (
        <Link
          key={slug}
          to="/app/$section"
          params={{ section: slug }}
          className={`rounded-xl px-3 py-2 text-xs font-bold transition md:px-4 ${slug === "perimetre" ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:bg-background hover:text-primary"}`}
        >
          {label}
        </Link>
      ))}
    </nav>
  );
}

function Header({ children }: { children?: ReactNode }) {
  return (
    <div className="flex flex-wrap items-start justify-between gap-5">
      <div className="flex items-start gap-4">
        <div className="mt-1 rounded-2xl bg-primary-soft p-3 text-primary"><Scale className="h-6 w-6" /></div>
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Contexte de l'organisme</p>
          <h1 className="mt-1 font-display text-2xl font-extrabold md:text-3xl">Domaine d'application</h1>
          <p className="mt-1 max-w-2xl text-sm text-muted-foreground">Définissez le périmètre du système de management ISO, ses activités, ses lieux et ses exclusions justifiées.</p>
        </div>
      </div>
      {children && <div className="flex flex-wrap gap-2">{children}</div>}
    </div>
  );
}

function Label({ children }: { children: ReactNode }) {
  return <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">{children}</label>;
}

function SiteRequired() {
  return (
    <div className={`${card} mx-auto max-w-3xl p-8 text-center`}>
      <MapPin className="mx-auto h-9 w-9 text-primary" />
      <h2 className="mt-3 font-display text-lg font-bold">Sélectionnez un site</h2>
      <p className={`mt-2 ${muted}`}>Le domaine d'application est rattaché au site courant.</p>
    </div>
  );
}

function lines(items: ItemForm[]) {
  return items.map((item) => item.value.trim()).filter(Boolean);
}

function parseObject(value: unknown): Record<string, unknown> {
  if (value && typeof value === "object" && !Array.isArray(value)) return value as Record<string, unknown>;
  if (typeof value === "string") {
    try {
      const parsed = JSON.parse(value);
      return parsed && typeof parsed === "object" && !Array.isArray(parsed) ? parsed as Record<string, unknown> : {};
    } catch {
      return {};
    }
  }
  return {};
}

function toLines(value: unknown): ItemForm[] {
  if (Array.isArray(value)) {
    const result = value.map((item) => ({
      value: String(typeof item === "object" && item !== null
        ? (item as Record<string, unknown>).name ?? (item as Record<string, unknown>).value ?? ""
        : item),
    })).filter((item) => item.value);
    return result.length ? result : [emptyItem()];
  }
  if (typeof value === "string") {
    try {
      return toLines(JSON.parse(value));
    } catch {
      const result = value.split("\n").map((item) => item.trim()).filter(Boolean).map((item) => ({ value: item }));
      return result.length ? result : [emptyItem()];
    }
  }
  return [emptyItem()];
}

function normalizeProcessType(value: unknown): string {
  const normalized = String(value ?? "").toLowerCase().trim();
  if (["management", "pilotage", "direction", "strategique", "stratégique"].includes(normalized)) return "management";
  if (["support", "supporting", "soutien"].includes(normalized)) return "support";
  return "realization";
}

function processName(value: Record<string, unknown>): string {
  return String(value.title ?? value.name ?? value.nom ?? "").trim();
}

function processFromResource(value: unknown): ScopeProcess | null {
  const resource = flattenResource(value);
  const name = processName(resource);
  if (!name) return null;
  const id = resource.id == null ? undefined : String(resource.id);
  return {
    id,
    name,
    type: normalizeProcessType(resource.category ?? resource.type),
    abbreviation: String(resource.abbreviation ?? "").trim().toUpperCase() || undefined,
  };
}

function processKey(process: ScopeProcess): string {
  return process.id ? `id:${process.id}` : `name:${process.name.toLowerCase()}`;
}

function sameProcess(left: ScopeProcess, right: ScopeProcess): boolean {
  return (left.id && right.id && left.id === right.id) || left.name.toLowerCase() === right.name.toLowerCase();
}

function scopeProcesses(scope: Record<string, unknown>, available: ScopeProcess[]): ScopeProcess[] {
  const stored = Array.isArray(scope.processes) ? scope.processes : [];
  const legacyIds = Array.isArray(scope.included_processes) ? scope.included_processes.map(String) : [];
  const selected: ScopeProcess[] = [];

  for (const item of stored) {
    if (item && typeof item === "object") {
      const normalized = processFromResource(item);
      if (normalized) selected.push(normalized);
    } else {
      const match = available.find((process) => process.id === String(item));
      if (match) selected.push(match);
    }
  }

  for (const id of legacyIds) {
    const match = available.find((process) => process.id === id);
    if (match) selected.push(match);
  }

  return [...new Map(selected.map((process) => [processKey(process), process])).values()];
}

function normText(value: unknown): string {
  if (Array.isArray(value)) return value.map(String).join(", ");
  return String(value ?? "");
}

function useNorms(siteId: string) {
  return useQuery({
    queryKey: ["scope-norms", siteId],
    enabled: Boolean(siteId),
    queryFn: async (): Promise<NormOption[]> => {
      const payload = await backendApi.request<unknown>(`access/catalog?site_id=${encodeURIComponent(siteId)}`);
      const root = payload && typeof payload === "object" ? payload as Record<string, unknown> : {};
      const data = root.data && typeof root.data === "object" && !Array.isArray(root.data) ? root.data as Record<string, unknown> : {};
      const values = root.norms ?? data.norms ?? [];
      return Array.isArray(values)
        ? values.map((item) => {
            const value = flattenResource(item);
            return { code: String(value.code ?? value.name ?? ""), name: String(value.name ?? value.code ?? "") };
          }).filter((item) => item.code)
        : [];
    },
    staleTime: 60_000,
  });
}

function Stepper({ step, onStep }: { step: number; onStep: (step: number) => void }) {
  return (
    <div className={`${card} overflow-x-auto p-3`}>
      <div className="flex min-w-[760px] gap-2">
        {STEP_META.map(([label, Icon], index) => {
          const number = index + 1;
          return (
            <button
              type="button"
              key={label}
              onClick={() => onStep(number)}
              className={`relative flex flex-1 items-center gap-2 rounded-xl p-3 text-left ${step === number ? "bg-primary-soft text-primary" : step > number ? "bg-success/10 text-success" : "text-muted-foreground hover:bg-background"}`}
            >
              <span className={`grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-extrabold ${step === number ? "bg-primary text-primary-foreground" : step > number ? "bg-success text-success-foreground" : "bg-background"}`}>
                {step > number ? <Check className="h-4 w-4" /> : <Icon className="h-4 w-4" />}
              </span>
              <span><span className="block text-[10px] font-extrabold uppercase">Étape {number}</span><span className="block text-xs font-bold">{label}</span></span>
              {number < STEP_META.length && <ArrowRight className="absolute -right-2 z-10 hidden h-4 w-4 text-border lg:block" />}
            </button>
          );
        })}
      </div>
    </div>
  );
}

export function ScopePage() {
  const [siteId] = useCurrentSite();
  const navigate = useNavigate();
  const { data: scopes = [], isLoading: loadingScope } = useScope(siteId);
  const { data: processResources = [], isLoading: loadingProcesses } = useProcesses(siteId);
  const { data: normOptions = [] } = useNorms(siteId);
  const { save } = useScopeMutations(siteId);
  const [step, setStep] = useState(1);
  const [form, setForm] = useState<ScopeForm>(initialForm);
  const availableProcesses = processResources.map(processFromResource).filter((process): process is ScopeProcess => Boolean(process));
  const existing = scopes[0] as Record<string, unknown> | undefined;

  useEffect(() => {
    if (!existing) {
      setForm(initialForm());
      return;
    }

    const rawNormExclusions = parseObject(existing.norm_exclusions);
    const justifications = parseObject(existing.norm_exclusions_justifications);
    const normalized: Record<string, NormExclusion> = {};
    Object.entries(rawNormExclusions).forEach(([key, value]) => {
      normalized[key] = { chapters: normText(value), justification: String(justifications[key] ?? "") };
    });

    setForm({
      version: String(existing.version ?? "1.0"),
      objective: String(existing.document_objective ?? existing.objective ?? ""),
      definition: String(existing.scope_definition ?? existing.scope ?? ""),
      documents: toLines(existing.referenced_documents),
      processes: scopeProcesses(existing, availableProcesses),
      products: toLines(existing.products_services),
      units: toLines(existing.organizational_units),
      locations: toLines(existing.locations),
      exclusions: String(existing.scope_exclusions ?? existing.exclusions ?? ""),
      exclusionsJustification: String(existing.exclusions_justification ?? ""),
      isoExclusions: String(existing.iso_exclusions ?? ""),
      isoExclusionsJustification: String(existing.iso_exclusions_justification ?? ""),
      normExclusions: normalized,
    });
  }, [existing, processResources]);

  if (!siteId) return <div className="mx-auto max-w-7xl p-4 md:p-8"><SiteRequired /></div>;

  const update = <K extends keyof ScopeForm>(key: K, value: ScopeForm[K]) => setForm((current) => ({ ...current, [key]: value }));
  const updateList = (key: "documents" | "products" | "units" | "locations", index: number, value: string) => setForm((current) => ({ ...current, [key]: current[key].map((item, itemIndex) => itemIndex === index ? { value } : item) }));
  const addList = (key: "documents" | "products" | "units" | "locations") => setForm((current) => ({ ...current, [key]: [...current[key], emptyItem()] }));
  const removeList = (key: "documents" | "products" | "units" | "locations", index: number) => setForm((current) => ({ ...current, [key]: current[key].length <= 1 ? [emptyItem()] : current[key].filter((_, itemIndex) => itemIndex !== index) }));
  const toggleProcess = (process: ScopeProcess) => update("processes", form.processes.some((item) => sameProcess(item, process) ? true : false) ? form.processes.filter((item) => !sameProcess(item, process)) : [...form.processes, process]);
  const toggleNorm = (norm: string) => update("normExclusions", { ...form.normExclusions, [norm]: form.normExclusions[norm] ?? { chapters: "", justification: "" } });
  const normChoices = [...normOptions, ...Object.keys(form.normExclusions).filter((code) => !normOptions.some((norm) => norm.code === code)).map((code) => ({ code, name: code }))];

  const validateStep = (currentStep: number): boolean => {
    if (currentStep === 1 && form.documents.some((item) => !item.value.trim())) {
      toast.error("Étape Documents : complétez ou supprimez les lignes vides.");
      return false;
    }
    if (currentStep === 2 && !form.objective.trim()) {
      toast.error("Étape Définition : l’objectif du document est requis.");
      return false;
    }
    if (currentStep === 2 && !form.definition.trim()) {
      toast.error("Étape Définition : la définition du domaine est requise.");
      return false;
    }
    if (currentStep === 3 && form.processes.some((process) => !process.name.trim() || !process.type.trim())) {
      toast.error("Étape Processus : chaque processus doit avoir un nom et une catégorie.");
      return false;
    }
    if (currentStep === 4 && form.products.some((item) => !item.value.trim())) {
      toast.error("Étape Produits et services : complétez ou supprimez les lignes vides.");
      return false;
    }
    if (currentStep === 5 && form.units.some((item) => !item.value.trim())) {
      toast.error("Étape Unités : complétez ou supprimez les lignes vides.");
      return false;
    }
    if (currentStep === 6 && form.locations.some((item) => !item.value.trim())) {
      toast.error("Étape Lieux : complétez ou supprimez les lignes vides.");
      return false;
    }
    const exclusions = form.normExclusions;
    if (currentStep === 7 && Object.values(exclusions).some((value) => value.chapters.trim() && !value.justification.trim())) {
      toast.error("Étape Exclusions : justifiez chaque exclusion par norme.");
      return false;
    }
    return true;
  };

  const saveForm = async () => {
    for (let currentStep = 1; currentStep <= 7; currentStep += 1) {
      if (!validateStep(currentStep)) {
        setStep(currentStep);
        return;
      }
    }

    try {
      const normExclusions = Object.fromEntries(Object.entries(form.normExclusions).filter(([, value]) => value.chapters.trim()).map(([key, value]) => [key, value.chapters.split(",").map((chapter) => chapter.trim()).filter(Boolean)]));
      const normJustifications = Object.fromEntries(Object.entries(form.normExclusions).filter(([, value]) => value.justification.trim()).map(([key, value]) => [key, value.justification.trim()]));
      const processPayload = form.processes.map((process) => ({ id: process.id ? Number(process.id) : undefined, name: process.name, type: process.type, abbreviation: process.abbreviation ?? "" }));
      await save.mutateAsync({
        id: existing?.id ? String(existing.id) : undefined,
        payload: {
          site_id: Number(siteId),
          version: form.version,
          is_current: true,
          document_objective: form.objective,
          scope_definition: form.definition,
          objective: form.objective,
          scope: form.definition,
          referenced_documents: lines(form.documents),
          processes: processPayload,
          included_processes: form.processes.map((process) => process.id).filter((id): id is string => Boolean(id)),
          products_services: lines(form.products),
          organizational_units: lines(form.units),
          locations: lines(form.locations),
          scope_exclusions: form.exclusions,
          exclusions: form.exclusions,
          exclusions_justification: form.exclusionsJustification,
          iso_exclusions: form.isoExclusions,
          iso_exclusions_justification: form.isoExclusionsJustification,
          norm_exclusions: normExclusions,
          norm_exclusions_justifications: normJustifications,
          applicable_norms: normOptions.map((norm) => norm.code),
        },
      });
      toast.success("Domaine d'application enregistré.");
      navigate({ to: "/app/$section", params: { section: "perimetre" } });
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Impossible d'enregistrer le domaine d'application.");
    }
  };

  const next = () => {
    if (validateStep(step)) setStep((current) => Math.min(8, current + 1));
  };
  const previous = () => setStep((current) => Math.max(1, current - 1));

  if (loadingScope) return <div className="mx-auto max-w-7xl p-8 text-sm text-muted-foreground">Chargement du domaine d'application…</div>;

  return (
    <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
      <Header>
        <button className={primary} onClick={saveForm} disabled={save.isPending}>
          <Save className="h-4 w-4" /> {save.isPending ? "Enregistrement…" : step === 8 ? "Enregistrer définitivement" : "Enregistrer"}
        </button>
      </Header>
      <Nav />
      <Stepper step={step} onStep={(target) => {
        if (target <= step || validateStep(step)) setStep(target);
      }} />
      <div className={`${card} p-5 md:p-8`}>
        {step === 1 && <section><StepIntro icon={FileText} title="Documents référencés" description="Listez les documents et référentiels utilisés pour définir le domaine d'application." /><EditableList items={form.documents} placeholder="Ex. Manuel QHSE, exigences clients…" onAdd={() => addList("documents")} onRemove={(index) => removeList("documents", index)} onChange={(index, value) => updateList("documents", index, value)} /></section>}
        {step === 2 && <section><StepIntro icon={Scale} title="Définition du périmètre" description="Formulez l'objectif du document et les limites du système de management." /><div className="space-y-5"><div><Label>Version</Label><input className={`${input} max-w-[160px]`} value={form.version} onChange={(event) => update("version", event.target.value)} /></div><div><Label>Objectif du document</Label><textarea className={`${input} min-h-28`} value={form.objective} onChange={(event) => update("objective", event.target.value)} placeholder="Finalité du document de domaine d'application…" /></div><div><Label>Définition du domaine d'application</Label><textarea className={`${input} min-h-36`} value={form.definition} onChange={(event) => update("definition", event.target.value)} placeholder="Activités, produits, services, limites organisationnelles et opérationnelles…" /></div></div></section>}
        {step === 3 && <section><StepIntro icon={GitBranch} title="Processus inclus" description="Sélectionnez les processus actifs qui font partie du système de management." />{loadingProcesses ? <p className={muted}>Chargement des processus…</p> : availableProcesses.length === 0 ? <div className="rounded-xl border border-dashed border-border p-8 text-center"><GitBranch className="mx-auto h-8 w-8 text-primary" /><p className="mt-3 font-semibold">Aucun processus disponible</p><p className={`mt-1 ${muted}`}>Créez d'abord un processus dans la cartographie.</p></div> : <div className="space-y-4">{availableProcesses.map((process) => { const selected = form.processes.some((item) => sameProcess(item, process)); return <button type="button" key={processKey(process)} onClick={() => toggleProcess(process)} className={`flex w-full items-start gap-3 rounded-xl border p-4 text-left transition ${selected ? "border-primary bg-primary-soft" : "border-border hover:border-primary/50"}`}><span className={`mt-0.5 grid h-5 w-5 place-items-center rounded-md border ${selected ? "border-primary bg-primary text-primary-foreground" : "border-border"}`}>{selected && <Check className="h-3.5 w-3.5" />}</span><span className="min-w-0 flex-1"><span className="font-display font-bold">{process.name}</span><span className="mt-1 block text-xs text-muted-foreground">{String((processResources.find((item) => String(item.id) === process.id)?.purpose ?? "Finalité non renseignée"))}</span></span><span className="rounded-full bg-background px-2 py-1 text-[10px] font-bold text-muted-foreground">{process.type}</span></button>; })}</div>}</section>}
        {step === 4 && <section><StepIntro icon={Layers3} title="Produits et services" description="Décrivez les produits et services couverts par le périmètre." /><EditableList items={form.products} placeholder="Produit ou service couvert" onAdd={() => addList("products")} onRemove={(index) => removeList("products", index)} onChange={(index, value) => updateList("products", index, value)} /></section>}
        {step === 5 && <section><StepIntro icon={Building2} title="Unités organisationnelles" description="Indiquez les directions, départements ou unités inclus dans le système." /><EditableList items={form.units} placeholder="Direction, service ou unité" onAdd={() => addList("units")} onRemove={(index) => removeList("units", index)} onChange={(index, value) => updateList("units", index, value)} /></section>}
        {step === 6 && <section><StepIntro icon={MapPin} title="Lieux et implantations" description="Répertoriez les lieux, sites et implantations concernés." /><EditableList items={form.locations} placeholder="Site, agence ou implantation" onAdd={() => addList("locations")} onRemove={(index) => removeList("locations", index)} onChange={(index, value) => updateList("locations", index, value)} /></section>}
        {step === 7 && <section><StepIntro icon={ShieldAlert} title="Exclusions et justifications" description="Documentez les exclusions générales et les exclusions par norme avec leur justification." /><div className="space-y-5"><div><Label>Exclusions générales</Label><textarea className={`${input} min-h-28`} value={form.exclusions} onChange={(event) => update("exclusions", event.target.value)} placeholder="Aucune exclusion non justifiée…" /></div><div><Label>Justification des exclusions générales</Label><textarea className={`${input} min-h-28`} value={form.exclusionsJustification} onChange={(event) => update("exclusionsJustification", event.target.value)} /></div><div className="grid gap-4 md:grid-cols-2"><div><Label>Exclusions ISO</Label><textarea className={`${input} min-h-24`} value={form.isoExclusions} onChange={(event) => update("isoExclusions", event.target.value)} placeholder="Ex. ISO 9001, article 8.3…" /></div><div><Label>Justification ISO</Label><textarea className={`${input} min-h-24`} value={form.isoExclusionsJustification} onChange={(event) => update("isoExclusionsJustification", event.target.value)} /></div></div><div><Label>Exclusions par norme du catalogue</Label>{normChoices.length === 0 ? <p className={muted}>Aucune norme disponible dans le catalogue du site.</p> : <div className="space-y-3">{normChoices.map((norm) => { const active = Boolean(form.normExclusions[norm.code]); const value = form.normExclusions[norm.code] ?? { chapters: "", justification: "" }; return <div key={norm.code} className={`${card} p-4`}><label className="flex items-center gap-3"><input type="checkbox" checked={active} onChange={() => toggleNorm(norm.code)} /><span className="font-bold">{norm.code}</span><span className="text-xs text-muted-foreground">{norm.name !== norm.code ? norm.name : ""}</span></label>{active && <div className="mt-4 grid gap-3 md:grid-cols-2"><textarea className={`${input} min-h-24`} value={value.chapters} onChange={(event) => update("normExclusions", { ...form.normExclusions, [norm.code]: { ...value, chapters: event.target.value } })} placeholder="Chapitres / exigences exclus (ex. 4.3, 8.3)…" /><textarea className={`${input} min-h-24`} value={value.justification} onChange={(event) => update("normExclusions", { ...form.normExclusions, [norm.code]: { ...value, justification: event.target.value } })} placeholder="Justification documentée…" /></div>}</div>; })}</div>}</div></div></section>}
        {step === 8 && <section><StepIntro icon={CheckCircle2} title="Récapitulatif" description="Relisez les informations avant de les enregistrer dans Laravel." /><div className="grid gap-4 md:grid-cols-2"><Summary label="Documents" value={`${lines(form.documents).length} élément(s)`} /><Summary label="Processus" value={`${form.processes.length} sélectionné(s)`} /><Summary label="Produits / services" value={`${lines(form.products).length} élément(s)`} /><Summary label="Unités / lieux" value={`${lines(form.units).length} / ${lines(form.locations).length}`} /><div className={`${card} p-4 md:col-span-2`}><p className="text-xs font-bold uppercase text-muted-foreground">Définition</p><p className="mt-2 whitespace-pre-line text-sm">{form.definition || "Non renseignée"}</p></div></div></section>}
        <div className="mt-8 flex items-center justify-between border-t border-border pt-5"><button className={secondary} disabled={step === 1} onClick={previous}><ArrowLeft className="h-4 w-4" /> Précédent</button>{step < 8 ? <button className={primary} onClick={next}>Suivant <ArrowRight className="h-4 w-4" /></button> : <button className={primary} onClick={saveForm} disabled={save.isPending}><Save className="h-4 w-4" /> Enregistrer définitivement</button>}</div>
      </div>
    </div>
  );
}

function StepIntro({ icon: Icon, title, description }: { icon: LucideIcon; title: string; description: string }) {
  return <div className="mb-6 flex items-start gap-3"><div className="rounded-xl bg-primary-soft p-3 text-primary"><Icon className="h-5 w-5" /></div><div><h2 className="font-display text-xl font-bold">{title}</h2><p className={`mt-1 ${muted}`}>{description}</p></div></div>;
}

function EditableList({ items, placeholder, onAdd, onRemove, onChange }: { items: ItemForm[]; placeholder: string; onAdd: () => void; onRemove: (index: number) => void; onChange: (index: number, value: string) => void }) {
  return <div className="space-y-3">{items.map((item, index) => <div key={index} className="flex gap-2"><span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-background text-xs font-bold text-muted-foreground">{index + 1}</span><input className={input} value={item.value} onChange={(event) => onChange(index, event.target.value)} placeholder={placeholder} /><button type="button" className="rounded-xl border border-border px-3 text-muted-foreground hover:text-destructive" onClick={() => onRemove(index)} aria-label="Supprimer"><X className="h-4 w-4" /></button></div>)}<button type="button" className={secondary} onClick={onAdd}><Plus className="h-4 w-4" /> Ajouter un élément</button></div>;
}

function Summary({ label, value }: { label: string; value: string }) {
  return <div className={`${card} p-4`}><p className="text-xs font-bold uppercase text-muted-foreground">{label}</p><p className="mt-2 font-display text-lg font-bold">{value}</p></div>;
}
