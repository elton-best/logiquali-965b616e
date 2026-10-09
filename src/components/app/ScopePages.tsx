import { Link } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { useEffect, useState, type ReactNode } from "react";
import { toast } from "sonner";
import {
  ArrowLeft,
  ArrowRight,
  Building2,
  Check,
  CheckCircle2,
  Download,
  Eye,
  FileText,
  GitBranch,
  Layers3,
  MapPin,
  Plus,
  Save,
  Scale,
  Send,
  ShieldAlert,
  Trash2,
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
  ["contexte", "Compréhension de l'organisme"],
  ["parties-interessees", "Parties intéressées"],
  ["perimetre", "Domaine d'application"],
  ["processus", "Système de management"],
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

type NormChapter = { id?: string | number; code: string; title: string; subchapters?: NormChapter[] };
type NormOption = { id?: string | number; code: string; name: string; chapters?: NormChapter[] };
type DocumentTypeOption = { id: string; name: string; abbreviation: string };

const emptyItem = (): ItemForm => ({ value: "" });
const initialForm = (): ScopeForm => ({
  version: "1.0",
  objective: `Ce document vise à définir clairement les limites du Système de management de la qualité (SMQ) de NOM DE LA STRUCTURE.\n\nIl s'applique à toute la documentation et aux activités au sein du SMQ de NOM DE LA STRUCTURE.\n\nLes utilisateurs de ce document sont les membres de la direction et l'équipe de mise en œuvre du projet de SMQ.`,
  definition: `Le domaine d'application du système de management de la qualité définit les limites physiques et organisationnelles auxquelles le SMQ s'applique.\n\nNOM DE LA STRUCTURE considère son contexte, les besoins et les attentes des parties intéressées, ainsi que l'étendue du contrôle et de l'influence qui peuvent s'exercer sur ses activités, ses produits et ses services.\n\nLe domaine d'application est une déclaration factuelle et représentative des opérations de NOM DE LA STRUCTURE dans les limites du SMQ.`,
  documents: [],
  processes: [],
  products: [],
  units: [],
  locations: [],
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
    return result;
  }
  if (typeof value === "string") {
    try {
      return toLines(JSON.parse(value));
    } catch {
      const result = value.split("\n").map((item) => item.trim()).filter(Boolean).map((item) => ({ value: item }));
      return result;
    }
  }
  return [];
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
    abbreviation: String(resource.abbreviation ?? resource.code ?? "").trim().toUpperCase() || undefined,
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
      const norms = Array.isArray(values)
        ? values.map((item) => {
            const value = flattenResource(item);
            return {
              id: value.id == null ? undefined : String(value.id),
              code: String(value.code ?? value.name ?? ""),
              name: String(value.name ?? value.code ?? ""),
            };
          }).filter((item) => item.code)
        : [];

      return Promise.all(norms.map(async (norm) => {
        if (!norm.id) return norm;
        try {
          const chaptersPayload = await backendApi.request<unknown>(`norms/${norm.id}/chapters?site_id=${encodeURIComponent(siteId)}`);
          const chaptersRoot = chaptersPayload && typeof chaptersPayload === "object" ? chaptersPayload as Record<string, unknown> : {};
          const chapters = Array.isArray(chaptersRoot.data) ? chaptersRoot.data : [];
          return {
            ...norm,
            chapters: chapters.map((chapter) => {
              const item = flattenResource(chapter);
              const subchapters = Array.isArray(item.subchapters)
                ? item.subchapters.map((subchapter) => {
                    const child = flattenResource(subchapter);
                    return {
                      id: child.id == null ? undefined : String(child.id),
                      code: String(child.code ?? child.number ?? ""),
                      title: String(child.title ?? child.name ?? ""),
                    };
                  })
                : [];
              return {
                id: item.id == null ? undefined : String(item.id),
                code: String(item.code ?? item.number ?? ""),
                title: String(item.title ?? item.name ?? ""),
                subchapters,
              };
            }),
          };
        } catch {
          return norm;
        }
      }));
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
  const { data: scopes = [], isLoading: loadingScope } = useScope(siteId);
  const { data: processResources = [], isLoading: loadingProcesses } = useProcesses(siteId);
  const { data: normOptions = [] } = useNorms(siteId);
  const { save } = useScopeMutations(siteId);
  const [step, setStep] = useState(1);
  const [form, setForm] = useState<ScopeForm>(initialForm);
  const [manualProcess, setManualProcess] = useState<ScopeProcess>({ name: "", type: "realization", abbreviation: "" });
  const [savedScopeId, setSavedScopeId] = useState<string>();
  const [documentTypes, setDocumentTypes] = useState<DocumentTypeOption[]>([]);
  const [documentTypeId, setDocumentTypeId] = useState("");
  const [generationProcess, setGenerationProcess] = useState("");
  const [generationAction, setGenerationAction] = useState<"draft" | "preview" | "download" | "verify">("draft");
  const [generationOpen, setGenerationOpen] = useState(false);
  const [generationBusy, setGenerationBusy] = useState(false);
  const [documentId, setDocumentId] = useState<string>();
  const [documentCode, setDocumentCode] = useState("");
  const [verificationOpen, setVerificationOpen] = useState(false);
  const [verificationBusy, setVerificationBusy] = useState(false);
  const [previewUrl, setPreviewUrl] = useState<string>();
  const [previewOpen, setPreviewOpen] = useState(false);
  const availableProcesses = processResources.map(processFromResource).filter((process): process is ScopeProcess => Boolean(process));
  const existing = scopes[0] as Record<string, unknown> | undefined;
  const currentScopeId = savedScopeId ?? (existing?.id == null ? undefined : String(existing.id));

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

  useEffect(() => {
    if (existing?.id != null) setSavedScopeId(String(existing.id));
  }, [existing?.id]);

  useEffect(() => {
    let cancelled = false;
    if (!siteId) return undefined;
    backendApi.request<unknown>(`document-type-catalogs?site_id=${encodeURIComponent(siteId)}&is_active=true`)
      .then((payload) => {
        const values = Array.isArray(payload)
          ? payload
          : payload && typeof payload === "object" && Array.isArray((payload as Record<string, unknown>).data)
            ? (payload as Record<string, unknown>).data as unknown[]
            : [];
        if (cancelled) return;
        const options = values.map((item) => {
          const value = flattenResource(item);
          return {
            id: String(value.id ?? ""),
            name: String(value.name ?? value.title ?? ""),
            abbreviation: String(value.abbreviation ?? ""),
          };
        }).filter((item) => item.id && item.name);
        setDocumentTypes(options);
        setDocumentTypeId((current) => current || options[0]?.id || "");
      })
      .catch(() => {
        if (!cancelled) setDocumentTypes([]);
      });
    return () => { cancelled = true; };
  }, [siteId]);

  useEffect(() => () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
  }, [previewUrl]);

  if (!siteId) return <div className="mx-auto max-w-7xl p-4 md:p-8"><SiteRequired /></div>;

  const update = <K extends keyof ScopeForm>(key: K, value: ScopeForm[K]) => setForm((current) => ({ ...current, [key]: value }));
  const updateList = (key: "documents" | "products" | "units" | "locations", index: number, value: string) => setForm((current) => ({ ...current, [key]: current[key].map((item, itemIndex) => itemIndex === index ? { value } : item) }));
  const addList = (key: "documents" | "products" | "units" | "locations") => setForm((current) => ({ ...current, [key]: [...current[key], emptyItem()] }));
  const removeList = (key: "documents" | "products" | "units" | "locations", index: number) => setForm((current) => ({ ...current, [key]: current[key].filter((_, itemIndex) => itemIndex !== index) }));
  const toggleProcess = (process: ScopeProcess) => update("processes", form.processes.some((item) => sameProcess(item, process)) ? form.processes.filter((item) => !sameProcess(item, process)) : [...form.processes, process]);
  const updateProcess = (index: number, patch: Partial<ScopeProcess>) => update("processes", form.processes.map((process, itemIndex) => itemIndex === index ? { ...process, ...patch } : process));
  const removeProcess = (index: number) => update("processes", form.processes.filter((_, itemIndex) => itemIndex !== index));
  const addManualProcess = () => {
    const name = manualProcess.name.trim();
    if (!name) {
      toast.error("Le nom du processus est requis.");
      return;
    }
    if (!manualProcess.abbreviation?.trim()) {
      toast.error("L'abréviation du processus est requise.");
      return;
    }
    if (form.processes.some((process) => process.name.trim().toLowerCase() === name.toLowerCase())) {
      toast.error("Ce processus est déjà sélectionné.");
      return;
    }
    update("processes", [...form.processes, { ...manualProcess, name, abbreviation: manualProcess.abbreviation.trim().toUpperCase() }]);
    setManualProcess({ name: "", type: "realization", abbreviation: "" });
  };
  const toggleNorm = (norm: string) => {
    const next = { ...form.normExclusions };
    if (next[norm]) delete next[norm];
    else next[norm] = { chapters: "", justification: "" };
    update("normExclusions", next);
  };
  const toggleNormChapter = (norm: string, chapter: string) => {
    const current = form.normExclusions[norm] ?? { chapters: "", justification: "" };
    const chapters = current.chapters.split(",").map((item) => item.trim()).filter(Boolean);
    const next = chapters.includes(chapter) ? chapters.filter((item) => item !== chapter) : [...chapters, chapter];
    update("normExclusions", { ...form.normExclusions, [norm]: { ...current, chapters: next.join(", ") } });
  };
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
    if (currentStep === 3 && form.processes.some((process) => !process.name.trim() || !process.type.trim() || !process.abbreviation?.trim())) {
      toast.error("Étape Processus : chaque processus doit avoir un nom, une catégorie et une abréviation.");
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
      const savedPayload = await save.mutateAsync({
        id: currentScopeId,
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
      const savedRoot = savedPayload && typeof savedPayload === "object" ? savedPayload as Record<string, unknown> : {};
      const savedData = savedRoot.data && typeof savedRoot.data === "object" ? savedRoot.data as Record<string, unknown> : savedRoot;
      const savedResource = flattenResource(savedData);
      if (savedResource.id != null) setSavedScopeId(String(savedResource.id));
      toast.success("Domaine d'application enregistré.");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Impossible d'enregistrer le domaine d'application.");
    }
  };

  const generationProcesses = [...form.processes, ...availableProcesses].filter((process, index, values) => values.findIndex((candidate) => sameProcess(candidate, process)) === index);

  const extractDocumentInfo = (payload: unknown): { id?: string; code: string } => {
    const root = payload && typeof payload === "object" ? payload as Record<string, unknown> : {};
    const data = root.data && typeof root.data === "object" ? root.data as Record<string, unknown> : root;
    const attributes = data.attributes && typeof data.attributes === "object" ? data.attributes as Record<string, unknown> : data;
    return {
      id: data.id == null && attributes.id == null ? undefined : String(data.id ?? attributes.id),
      code: String(attributes.code ?? ""),
    };
  };

  const downloadBlob = (blob: Blob, filename: string) => {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    URL.revokeObjectURL(url);
  };

  const openGeneration = (action: "draft" | "preview" | "download" | "verify") => {
    if (!currentScopeId) {
      toast.error("Veuillez d'abord enregistrer le domaine d'application.");
      return;
    }
    if (!documentTypes.length) {
      toast.error("Aucun type documentaire actif n'est disponible pour ce site.");
      return;
    }
    setGenerationAction(action);
    setDocumentTypeId((current) => current || documentTypes[0].id);
    setGenerationProcess((current) => current || generationProcesses[0]?.id || generationProcesses[0]?.name || "");
    setGenerationOpen(true);
  };

  const performExistingDocumentAction = async (action: "preview" | "download" | "verify") => {
    if (!documentId) return;
    setGenerationBusy(true);
    try {
      if (action === "verify") {
        setVerificationOpen(true);
      } else {
        const blob = await backendApi.blob(`documents/${documentId}/${action === "preview" ? "preview" : "download"}`);
        if (action === "preview") {
          if (previewUrl) URL.revokeObjectURL(previewUrl);
          setPreviewUrl(URL.createObjectURL(blob));
          setPreviewOpen(true);
        } else {
          downloadBlob(blob, `Domaine_Application_${siteId}_${new Date().toISOString().slice(0, 10)}.pdf`);
          toast.success("Téléchargement prêt.");
        }
      }
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Impossible d'accéder au document.");
    } finally {
      setGenerationBusy(false);
    }
  };

  const generateDocument = async () => {
    if (!currentScopeId || !documentTypeId || !generationProcess) return;
    const selectedProcess = generationProcesses.find((process) => (process.id && process.id === generationProcess) || (!process.id && process.name === generationProcess));
    setGenerationBusy(true);
    try {
      const payload: Record<string, unknown> = { document_type_catalog_id: Number(documentTypeId) };
      if (selectedProcess?.id) {
        payload.process_id = Number(selectedProcess.id);
      } else if (selectedProcess) {
        payload.process_name = selectedProcess.name;
        payload.process_type = selectedProcess.type;
        payload.process_abbreviation = selectedProcess.abbreviation ?? "";
      }
      const response = await backendApi.request<unknown>(`application-scopes/${currentScopeId}/generate-draft`, {
        method: "POST",
        body: JSON.stringify(payload),
      });
      const info = extractDocumentInfo(response);
      if (!info.id || !info.code) throw new Error("Le brouillon n'a pas retourné de code documentaire.");
      setDocumentId(info.id);
      setDocumentCode(info.code);
      setGenerationOpen(false);
      toast.success("Brouillon documentaire généré.");
      if (generationAction === "preview" || generationAction === "download") {
        const blob = await backendApi.blob(`documents/${info.id}/${generationAction === "preview" ? "preview" : "download"}`);
        if (generationAction === "preview") {
          if (previewUrl) URL.revokeObjectURL(previewUrl);
          setPreviewUrl(URL.createObjectURL(blob));
          setPreviewOpen(true);
        } else {
          downloadBlob(blob, `Domaine_Application_${siteId}_${new Date().toISOString().slice(0, 10)}.pdf`);
          toast.success("Téléchargement prêt.");
        }
      } else if (generationAction === "verify") {
        setVerificationOpen(true);
      }
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Impossible de générer le brouillon.");
    } finally {
      setGenerationBusy(false);
    }
  };

  const requestDocumentAction = (action: "preview" | "download" | "verify") => {
    if (documentId) {
      void performExistingDocumentAction(action);
    } else {
      openGeneration(action);
    }
  };

  const submitForVerification = async () => {
    if (!documentId || !documentCode) return;
    setVerificationBusy(true);
    try {
      await backendApi.request(`documents/${documentId}/confirm-code`, {
        method: "POST",
        body: JSON.stringify({ needs_verification: true, confirmed_code: documentCode }),
      });
      setVerificationOpen(false);
      setDocumentId(undefined);
      setDocumentCode("");
      toast.success("Document envoyé pour vérification.");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Échec de la soumission pour vérification.");
    } finally {
      setVerificationBusy(false);
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
        <button className={secondary} onClick={() => openGeneration("draft")} disabled={generationBusy || save.isPending}>
          <FileText className="h-4 w-4" /> Générer le brouillon
        </button>
        <button className={secondary} onClick={() => requestDocumentAction("preview")} disabled={generationBusy || save.isPending}>
          <Eye className="h-4 w-4" /> Prévisualiser
        </button>
        <button className={secondary} onClick={() => requestDocumentAction("download")} disabled={generationBusy || save.isPending}>
          <Download className="h-4 w-4" /> Télécharger
        </button>
        <button className={secondary} onClick={() => requestDocumentAction("verify")} disabled={generationBusy || save.isPending}>
          <Send className="h-4 w-4" /> Vérifier
        </button>
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
        {step === 3 && <section><StepIntro icon={GitBranch} title="Processus inclus" description="Sélectionnez, complétez ou ajoutez les processus qui font partie du système de management." />
          <div className="space-y-4">
            {form.processes.length > 0 && <div className="space-y-3"><p className="text-xs font-bold uppercase tracking-wide text-muted-foreground">Processus sélectionnés</p>{form.processes.map((process, index) => <div key={`${processKey(process)}-${index}`} className={`${card} p-4`}><div className="flex items-start gap-3"><div className="min-w-0 flex-1"><p className="font-display font-bold">{process.name}</p><p className={`mt-1 ${muted}`}>{process.id ? "Processus Laravel" : "Processus à synchroniser lors de la génération"}</p></div><button type="button" className="rounded-xl p-2 text-muted-foreground hover:bg-destructive/10 hover:text-destructive" onClick={() => removeProcess(index)} aria-label="Supprimer le processus"><Trash2 className="h-4 w-4" /></button></div><div className="mt-3 grid gap-3 md:grid-cols-2"><div><Label>Catégorie</Label><select className={input} value={process.type} onChange={(event) => updateProcess(index, { type: event.target.value })}><option value="management">Management</option><option value="realization">Réalisation</option><option value="support">Support</option></select></div><div><Label>Abréviation</Label><input className={input} value={process.abbreviation ?? ""} onChange={(event) => updateProcess(index, { abbreviation: event.target.value.toUpperCase() })} placeholder="Ex. SMQ" /></div></div></div>)}</div>}
            <div className="rounded-xl border border-dashed border-border p-4"><p className="font-display font-bold">Ajouter un processus manuel</p><p className={`mt-1 ${muted}`}>Le backend Laravel le créera ou le retrouvera par son nom pendant la génération documentaire.</p><div className="mt-3 grid gap-3 md:grid-cols-[1fr_180px_120px_auto]"><input className={input} value={manualProcess.name} onChange={(event) => setManualProcess((current) => ({ ...current, name: event.target.value }))} placeholder="Nom du processus" /><select className={input} value={manualProcess.type} onChange={(event) => setManualProcess((current) => ({ ...current, type: event.target.value }))}><option value="management">Management</option><option value="realization">Réalisation</option><option value="support">Support</option></select><input className={input} value={manualProcess.abbreviation ?? ""} onChange={(event) => setManualProcess((current) => ({ ...current, abbreviation: event.target.value.toUpperCase() }))} placeholder="Abrév." /><button type="button" className={secondary} onClick={addManualProcess}><Plus className="h-4 w-4" /> Ajouter</button></div></div>
            {loadingProcesses ? <p className={muted}>Chargement des processus…</p> : <div className="space-y-3"><p className="text-xs font-bold uppercase tracking-wide text-muted-foreground">Processus Laravel disponibles</p>{availableProcesses.length === 0 ? <p className={muted}>Aucun processus Laravel disponible. Ajoutez-en un manuellement.</p> : availableProcesses.map((process) => { const selected = form.processes.some((item) => sameProcess(item, process)); return <button type="button" key={processKey(process)} onClick={() => toggleProcess(process)} className={`flex w-full items-start gap-3 rounded-xl border p-4 text-left transition ${selected ? "border-primary bg-primary-soft" : "border-border hover:border-primary/50"}`}><span className={`mt-0.5 grid h-5 w-5 place-items-center rounded-md border ${selected ? "border-primary bg-primary text-primary-foreground" : "border-border"}`}>{selected && <Check className="h-3.5 w-3.5" />}</span><span className="min-w-0 flex-1"><span className="font-display font-bold">{process.name}</span><span className="mt-1 block text-xs text-muted-foreground">{String((processResources.find((item) => String(item.id) === process.id)?.purpose ?? "Finalité non renseignée"))}</span></span><span className="rounded-full bg-background px-2 py-1 text-[10px] font-bold text-muted-foreground">{process.type}</span></button>; })}</div>}
          </div></section>}
        {step === 4 && <section><StepIntro icon={Layers3} title="Produits et services" description="Décrivez les produits et services couverts par le périmètre." /><EditableList items={form.products} placeholder="Produit ou service couvert" onAdd={() => addList("products")} onRemove={(index) => removeList("products", index)} onChange={(index, value) => updateList("products", index, value)} /></section>}
        {step === 5 && <section><StepIntro icon={Building2} title="Unités organisationnelles" description="Indiquez les directions, départements ou unités inclus dans le système." /><EditableList items={form.units} placeholder="Direction, service ou unité" onAdd={() => addList("units")} onRemove={(index) => removeList("units", index)} onChange={(index, value) => updateList("units", index, value)} /></section>}
        {step === 6 && <section><StepIntro icon={MapPin} title="Lieux et implantations" description="Répertoriez les lieux, sites et implantations concernés." /><EditableList items={form.locations} placeholder="Site, agence ou implantation" onAdd={() => addList("locations")} onRemove={(index) => removeList("locations", index)} onChange={(index, value) => updateList("locations", index, value)} /></section>}
        {step === 7 && <section><StepIntro icon={ShieldAlert} title="Exclusions et justifications" description="Documentez les exclusions générales et les exclusions par norme avec leur justification." /><div className="space-y-5"><div><Label>Exclusions générales</Label><textarea className={`${input} min-h-28`} value={form.exclusions} onChange={(event) => update("exclusions", event.target.value)} placeholder="Aucune exclusion non justifiée…" /></div><div><Label>Justification des exclusions générales</Label><textarea className={`${input} min-h-28`} value={form.exclusionsJustification} onChange={(event) => update("exclusionsJustification", event.target.value)} /></div><div className="grid gap-4 md:grid-cols-2"><div><Label>Exclusions ISO</Label><textarea className={`${input} min-h-24`} value={form.isoExclusions} onChange={(event) => update("isoExclusions", event.target.value)} placeholder="Ex. ISO 9001, article 8.3…" /></div><div><Label>Justification ISO</Label><textarea className={`${input} min-h-24`} value={form.isoExclusionsJustification} onChange={(event) => update("isoExclusionsJustification", event.target.value)} /></div></div><div><Label>Exclusions par norme du catalogue</Label>{normChoices.length === 0 ? <p className={muted}>Aucune norme disponible dans le catalogue du site.</p> : <div className="space-y-3">{normChoices.map((norm) => { const active = Boolean(form.normExclusions[norm.code]); const value = form.normExclusions[norm.code] ?? { chapters: "", justification: "" }; const subscribedNorm = normOptions.find((option) => option.code === norm.code); const selectedChapters = value.chapters.split(",").map((item) => item.trim()).filter(Boolean); return <div key={norm.code} className={`${card} p-4`}><label className="flex items-center gap-3"><input type="checkbox" checked={active} onChange={() => toggleNorm(norm.code)} /><span className="font-bold">{norm.code}</span><span className="text-xs text-muted-foreground">{norm.name !== norm.code ? norm.name : ""}</span></label>{active && <div className="mt-4 space-y-4"><div><Label>Chapitres et sous-chapitres exclus</Label>{subscribedNorm?.chapters?.length ? <div className="space-y-2 rounded-xl bg-background p-3">{subscribedNorm.chapters.map((chapter) => <div key={String(chapter.id ?? chapter.code)}><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={selectedChapters.includes(chapter.code)} onChange={() => toggleNormChapter(norm.code, chapter.code)} /><span className="font-semibold">{chapter.code}</span><span>{chapter.title}</span></label>{chapter.subchapters?.map((subchapter) => <label key={String(subchapter.id ?? subchapter.code)} className="ml-6 mt-1 flex items-center gap-2 text-xs text-muted-foreground"><input type="checkbox" checked={selectedChapters.includes(subchapter.code)} onChange={() => toggleNormChapter(norm.code, subchapter.code)} /><span>{subchapter.code}</span><span>{subchapter.title}</span></label>)}</div>)}</div> : <textarea className={`${input} min-h-24`} value={value.chapters} onChange={(event) => update("normExclusions", { ...form.normExclusions, [norm.code]: { ...value, chapters: event.target.value } })} placeholder="Ex. 4.3, 8.3…" />}</div><div><Label>Justification documentée</Label><textarea className={`${input} min-h-24`} value={value.justification} onChange={(event) => update("normExclusions", { ...form.normExclusions, [norm.code]: { ...value, justification: event.target.value } })} placeholder="Justification de l'exclusion…" /></div></div>}</div>; })}</div>}</div></div></section>}
        {step === 8 && <section><StepIntro icon={CheckCircle2} title="Récapitulatif" description="Relisez les informations avant de les enregistrer dans Laravel." /><div className="grid gap-4 md:grid-cols-2"><Summary label="Documents" value={`${lines(form.documents).length} élément(s)`} /><Summary label="Processus" value={`${form.processes.length} sélectionné(s)`} /><Summary label="Produits / services" value={`${lines(form.products).length} élément(s)`} /><Summary label="Unités / lieux" value={`${lines(form.units).length} / ${lines(form.locations).length}`} /><div className={`${card} p-4 md:col-span-2`}><p className="text-xs font-bold uppercase text-muted-foreground">Définition</p><p className="mt-2 whitespace-pre-line text-sm">{form.definition || "Non renseignée"}</p></div></div></section>}
        <div className="mt-8 flex items-center justify-between border-t border-border pt-5"><button className={secondary} disabled={step === 1} onClick={previous}><ArrowLeft className="h-4 w-4" /> Précédent</button>{step < 8 ? <button className={primary} onClick={next}>Suivant <ArrowRight className="h-4 w-4" /></button> : <button className={primary} onClick={saveForm} disabled={save.isPending}><Save className="h-4 w-4" /> Enregistrer définitivement</button>}</div>
      </div>

      {generationOpen && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="scope-generation-title">
          <div className={`${card} w-full max-w-xl p-6 shadow-2xl`}>
            <div className="flex items-start justify-between gap-4">
              <div><p className="text-xs font-bold uppercase tracking-wide text-primary">Document généré</p><h2 id="scope-generation-title" className="mt-1 font-display text-xl font-bold">Paramètres du brouillon</h2></div>
              <button type="button" className="rounded-xl p-2 text-muted-foreground hover:bg-background" onClick={() => setGenerationOpen(false)} aria-label="Fermer"><X className="h-5 w-5" /></button>
            </div>
            <p className={`mt-3 ${muted}`}>Sélectionnez le type documentaire et le processus lié avant de générer le document Laravel.</p>
            <div className="mt-5 space-y-4">
              <div><Label>Type documentaire</Label><select className={input} value={documentTypeId} onChange={(event) => setDocumentTypeId(event.target.value)}><option value="">Sélectionner un type</option>{documentTypes.map((type) => <option key={type.id} value={type.id}>{type.name} ({type.abbreviation})</option>)}</select></div>
              <div><Label>Processus lié</Label><select className={input} value={generationProcess} onChange={(event) => setGenerationProcess(event.target.value)}><option value="">Sélectionner un processus</option>{generationProcesses.map((process) => <option key={processKey(process)} value={process.id ?? process.name}>{process.name}{process.abbreviation ? ` (${process.abbreviation})` : ""}</option>)}</select></div>
            </div>
            <div className="mt-6 flex justify-end gap-2"><button type="button" className={secondary} onClick={() => setGenerationOpen(false)}>Annuler</button><button type="button" className={primary} onClick={() => void generateDocument()} disabled={generationBusy || !documentTypeId || !generationProcess}>{generationBusy ? "Génération…" : "Générer"}</button></div>
          </div>
        </div>
      )}

      {previewOpen && previewUrl && (
        <div className="fixed inset-0 z-50 flex flex-col bg-black/70 p-4" role="dialog" aria-modal="true" aria-label="Prévisualisation PDF">
          <div className="mb-3 flex justify-end"><button type="button" className="rounded-xl bg-card p-2 text-foreground" onClick={() => { setPreviewOpen(false); URL.revokeObjectURL(previewUrl); setPreviewUrl(undefined); }} aria-label="Fermer"><X className="h-5 w-5" /></button></div>
          <iframe title="Prévisualisation du domaine d'application" src={previewUrl} className="mx-auto h-full w-full max-w-5xl rounded-2xl bg-white" />
        </div>
      )}

      {verificationOpen && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="scope-verification-title">
          <div className={`${card} w-full max-w-lg p-6 shadow-2xl`}>
            <h2 id="scope-verification-title" className="font-display text-xl font-bold">Soumettre pour vérification</h2>
            <p className={`mt-3 ${muted}`}>Le code du document généré est <strong className="text-foreground">{documentCode}</strong>. Confirmez-le pour lancer le workflow Laravel.</p>
            <div className="mt-6 flex justify-end gap-2"><button type="button" className={secondary} onClick={() => setVerificationOpen(false)}>Annuler</button><button type="button" className={primary} onClick={() => void submitForVerification()} disabled={verificationBusy}>{verificationBusy ? "Envoi…" : "Envoyer pour vérification"}</button></div>
          </div>
        </div>
      )}
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
