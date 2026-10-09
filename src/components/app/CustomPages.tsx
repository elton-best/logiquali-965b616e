import { getRouteApi, Link, useRouter } from "@tanstack/react-router";
import { useQueryClient } from "@tanstack/react-query";
import { Building2, Check, Plus, CreditCard, Download, ExternalLink, Minus, RotateCcw, Search } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { updateMyProfile } from "@/lib/profile.functions";
import { historyOf, RECORDS_KEY, useRecords, useSaveRecord, useTransition, type QRecord } from "@/hooks/use-records";
import { useEnterpriseNorms, useNorm, fetchNormChapters, flattenNormSections, type NormSection } from "@/integrations/backend/norms";
import { useDocumentWorkflow, usePendingDocuments } from "@/integrations/backend/documents";
import { downloadCsv, useCurrentSite, useWorkspace, type Task } from "@/hooks/use-workspace";
import { KINDS, sectionForKind } from "./sections";
import { RecordActions, StatusBadge } from "./SectionView";
import { ListLoading } from "./LoadingState";

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
      <Header title="Mes tâches" desc="Tout ce qui attend une action de votre part : actions, validations, audits, indicateurs, formations et échéances.">
        <Link to="/app/$section" params={{ section: "actions" }} search={{ new: 1 }} className={primaryBtn}><Plus className="h-4 w-4" /> Créer une tâche</Link>
      </Header>
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
      {isLoading ? <ListLoading rows={5} /> : list.length === 0 ? (
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
  const workflowStatus = mode === "verification" ? "pending_verification" : "pending_approval";
  const { data: documents = [], isLoading, isError } = usePendingDocuments(workflowStatus);
  const workflow = useDocumentWorkflow();
  const [open, setOpen] = useState<string | null>(null);
  const [rejecting, setRejecting] = useState<string | null>(null);
  const [rejectionReason, setRejectionReason] = useState("");
  const busy = workflow.verify.isPending || workflow.approve.isPending || workflow.reject.isPending;

  const verify = async (id: string) => {
    try {
      await workflow.verify.mutateAsync(id);
      toast.success("Document vérifié et transmis à l'approbation");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "La vérification a échoué.");
    }
  };
  const approve = async (id: string) => {
    try {
      await workflow.approve.mutateAsync({ id });
      toast.success("Document approuvé");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "L'approbation a échoué.");
    }
  };
  const reject = async () => {
    if (!rejecting || !rejectionReason.trim()) return;
    try {
      await workflow.reject.mutateAsync({ id: rejecting, rejection_reason: rejectionReason.trim() });
      toast.success("Document rejeté avec un motif");
      setRejecting(null);
      setRejectionReason("");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Le rejet a échoué.");
    }
  };

  return (
    <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
      <Header
        title={mode === "verification" ? "Vérification" : "Approbation"}
        desc={mode === "verification" ? "File Laravel des documents soumis au contrôle. Vérifiez, rejetez avec un motif ou transmettez à l'approbation." : "File Laravel des documents vérifiés prêts à décision. Approuvez ou rejetez avec traçabilité."}
      />
      {isError && <p className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">Impossible de charger la file documentaire. Votre permission et la connexion au backend sont nécessaires.</p>}
      {isLoading ? <ListLoading rows={5} /> : documents.length === 0 ? (
        <div className="rounded-2xl border border-border bg-card p-10 text-center">
          <p className="font-display font-bold text-foreground">File vide</p>
          <p className="mt-1 text-sm text-muted-foreground">Aucun document en attente de {mode === "verification" ? "vérification" : "approbation"}.</p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-2xl border border-border bg-card">
          <div className="hidden overflow-x-auto md:block">
            <table className="w-full min-w-[980px] text-sm">
              <thead className="border-b border-border bg-background text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">
                <tr><th className="px-4 py-3">Code</th><th className="px-4 py-3">Titre</th><th className="px-4 py-3">Processus</th><th className="px-4 py-3">Source</th><th className="px-4 py-3">Auteur</th><th className="px-4 py-3">Statut</th><th className="px-4 py-3 text-right">Actions</th></tr>
              </thead>
              <tbody className="divide-y divide-border">
                {documents.map((document) => (
                  <tr key={document.id} className="align-top hover:bg-primary-soft/30">
                    <td className="whitespace-nowrap px-4 py-3 font-mono text-xs font-bold text-primary">{document.code}<span className="ml-1 text-muted-foreground">v{document.version}</span></td>
                    <td className="max-w-[230px] px-4 py-3 font-semibold text-foreground">{document.title}</td>
                    <td className="px-4 py-3 text-muted-foreground">{document.process_label}</td>
                    <td className="px-4 py-3 text-muted-foreground">{document.source_label}</td>
                    <td className="px-4 py-3 text-muted-foreground">{document.author_name}</td>
                    <td className="px-4 py-3"><span className="rounded-full bg-primary-soft px-2.5 py-1 text-xs font-bold text-primary">{mode === "verification" ? "À vérifier" : "À approuver"}</span></td>
                    <td className="px-4 py-3"><div className="flex justify-end gap-2">
                      <button disabled={busy} onClick={() => mode === "verification" ? void verify(document.id) : void approve(document.id)} className="h-8 rounded-xl bg-success px-3 text-xs font-semibold text-success-foreground disabled:opacity-50">{mode === "verification" ? "Valider" : "Approuver"}</button>
                      <button disabled={busy} onClick={() => { setRejecting(document.id); setRejectionReason(""); }} className="h-8 rounded-xl border border-destructive/30 px-3 text-xs font-semibold text-destructive disabled:opacity-50">Rejeter</button>
                      <button onClick={() => setOpen(open === document.id ? null : document.id)} className="h-8 rounded-xl border border-border px-3 text-xs font-semibold hover:border-primary">{open === document.id ? "Masquer" : "Détails"}</button>
                    </div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <ul className="divide-y divide-border md:hidden">
            {documents.map((document) => (
              <li key={document.id} className="p-4">
                <p className="font-mono text-xs font-bold text-primary">{document.code} · v{document.version}</p>
                <p className="mt-1 font-semibold text-foreground">{document.title}</p>
                <p className="mt-1 text-xs text-muted-foreground">{document.process_label} · {document.source_label} · {document.author_name}</p>
                <div className="mt-3 flex flex-wrap gap-2">
                  <button disabled={busy} onClick={() => mode === "verification" ? void verify(document.id) : void approve(document.id)} className="h-9 rounded-xl bg-success px-3 text-xs font-semibold text-success-foreground disabled:opacity-50">{mode === "verification" ? "Valider" : "Approuver"}</button>
                  <button disabled={busy} onClick={() => { setRejecting(document.id); setRejectionReason(""); }} className="h-9 rounded-xl border border-destructive/30 px-3 text-xs font-semibold text-destructive disabled:opacity-50">Rejeter</button>
                </div>
              </li>
            ))}
          </ul>
        </div>
      )}

      {open && (() => {
        const document = documents.find((item) => item.id === open);
        return document ? <div className="rounded-2xl border border-border bg-card p-5"><div className="flex items-start justify-between gap-3"><div><p className="font-mono text-xs font-bold text-primary">{document.code}</p><h2 className="mt-1 font-display text-lg font-bold text-foreground">{document.title}</h2></div><button onClick={() => setOpen(null)} className="text-sm font-semibold text-primary">Fermer</button></div><dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2"><div><dt className="text-muted-foreground">Auteur</dt><dd className="font-semibold">{document.author_name}</dd></div><div><dt className="text-muted-foreground">Approbateur</dt><dd className="font-semibold">{document.approver_name}</dd></div><div><dt className="text-muted-foreground">Processus</dt><dd className="font-semibold">{document.process_label}</dd></div><div><dt className="text-muted-foreground">Source</dt><dd className="font-semibold">{document.source_label}</dd></div></dl></div> : null;
      })()}

      {rejecting && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/40 p-4" role="dialog" aria-modal="true">
          <div className="w-full max-w-lg rounded-2xl border border-border bg-card p-5 shadow-xl">
            <h2 className="font-display text-lg font-bold text-foreground">Motif du rejet</h2>
            <p className="mt-1 text-sm text-muted-foreground">Le motif est enregistré dans l'historique du workflow Laravel.</p>
            <textarea value={rejectionReason} onChange={(event) => setRejectionReason(event.target.value)} className="mt-4 h-28 w-full rounded-xl border border-input bg-background p-3 text-sm outline-none focus:border-primary" placeholder="Expliquez la correction attendue…" autoFocus />
            <div className="mt-4 flex justify-end gap-2"><button onClick={() => setRejecting(null)} className="h-10 rounded-xl border border-border px-4 text-sm font-semibold">Annuler</button><button disabled={!rejectionReason.trim() || busy} onClick={() => void reject()} className="h-10 rounded-xl bg-destructive px-4 text-sm font-semibold text-destructive-foreground disabled:opacity-50">Confirmer le rejet</button></div>
          </div>
        </div>
      )}
    </div>
  );
}

// ---------- Bibliothèque des normes ----------
export function NormsPage() {
  const [search, setSearch] = useState("");
  const [sectionSearch, setSectionSearch] = useState("");
  const [selectedId, setSelectedId] = useState<string | number | null>(null);
  const [sections, setSections] = useState<NormSection[]>([]);
  const [expanded, setExpanded] = useState<Set<string>>(new Set());
  const [sectionsLoading, setSectionsLoading] = useState(false);
  const [sectionsError, setSectionsError] = useState<string | null>(null);
  const [currentSite] = useCurrentSite();
  const { data: catalog, isLoading, isError } = useEnterpriseNorms({
    site_id: currentSite || undefined,
    search: search.trim() || undefined,
  });
  const norms = catalog?.norms ?? [];
  const { data: selectedNorm, isLoading: detailLoading } = useNorm(selectedId);

  useEffect(() => {
    if (norms.length === 0) {
      setSelectedId(null);
      return;
    }
    if (selectedId === null || !norms.some((norm) => String(norm.id) === String(selectedId))) {
      setSelectedId(norms[0].id);
    }
  }, [norms, selectedId]);

  useEffect(() => {
    let cancelled = false;
    async function loadSections() {
      if (!selectedNorm) {
        setSections([]);
        return;
      }
      setSectionsError(null);
      const inline = selectedNorm.currentVersion?.sections
        ?? selectedNorm.current_version?.sections
        ?? selectedNorm.versions?.[0]?.sections
        ?? [];
      if (inline.length > 0) {
        setSections(inline);
        setExpanded(new Set(flattenNormSections(inline).slice(0, 8).map((section) => String(section.id))));
        return;
      }
      setSectionsLoading(true);
      try {
        const chapters = await fetchNormChapters(selectedNorm.id);
        if (!cancelled) {
          setSections(chapters);
          setExpanded(new Set(flattenNormSections(chapters).slice(0, 8).map((section) => String(section.id))));
        }
      } catch {
        if (!cancelled) {
          setSections([]);
          setSectionsError("La structure détaillée de cette norme n’est pas disponible.");
        }
      } finally {
        if (!cancelled) setSectionsLoading(false);
      }
    }
    void loadSections();
    return () => { cancelled = true; };
  }, [selectedNorm]);

  const filteredSections = useMemo(() => {
    const query = sectionSearch.trim().toLowerCase();
    if (!query) return sections;
    const filter = (items: NormSection[]): NormSection[] => items.flatMap((section) => {
      const text = `${section.number ?? ""} ${section.title ?? ""} ${section.content ?? ""}`.toLowerCase();
      const children = Array.isArray(section.children) ? filter(section.children) : [];
      return text.includes(query) || children.length > 0 ? [{ ...section, children }] : [];
    });
    return filter(sections);
  }, [sections, sectionSearch]);

  const toggleSection = (id: string | number) => {
    setExpanded((current) => {
      const next = new Set(current);
      const key = String(id);
      if (next.has(key)) next.delete(key); else next.add(key);
      return next;
    });
  };

  const exportCsv = () => {
    const rows = [
      ["Code", "Nom", "Domaine", "Statut"],
      ...norms.map((norm) => [norm.code, norm.name, norm.domain ?? "—", norm.status ?? "—"]),
    ];
    downloadCsv("bibliotheque-normes.csv", rows);
    toast.success("Export téléchargé");
  };

  const renderSections = (items: NormSection[], depth = 0): React.ReactNode => items.map((section) => {
    const key = String(section.id);
    const hasChildren = Array.isArray(section.children) && section.children.length > 0;
    const isOpen = expanded.has(key);
    return (
      <li key={key} className="border-l border-border pl-3" style={{ marginLeft: depth * 8 }}>
        <button onClick={() => toggleSection(key)} className="flex w-full items-start gap-2 py-2 text-left hover:text-primary">
          <span className="mt-0.5 w-4 shrink-0 text-xs text-muted-foreground">{hasChildren ? (isOpen ? "−" : "+") : "·"}</span>
          <span className="text-sm font-semibold text-foreground">{section.number ? `${section.number} ` : ""}{section.title ?? "Section"}</span>
        </button>
        {isOpen && (
          <div className="pb-2 pl-6 text-sm text-muted-foreground">
            {section.content && <p className="whitespace-pre-wrap">{section.content}</p>}
            {hasChildren && <ul>{renderSections(section.children!, depth + 1)}</ul>}
          </div>
        )}
      </li>
    );
  });

  return (
    <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
      <Header title="Bibliothèque des normes" desc="Consultez les référentiels accessibles au site courant et leur structure issue du backend Laravel.">
        <button onClick={exportCsv} disabled={norms.length === 0} className={`${ghostBtn} disabled:opacity-50`}><Download className="h-4 w-4" /> Exporter</button>
      </Header>

      {catalog?.trialPeriod && <p className="rounded-xl border border-primary/30 bg-primary-soft/60 p-4 text-sm text-foreground">Période d’essai active{catalog.trialDaysRemaining > 0 ? ` · ${catalog.trialDaysRemaining} jour(s) restant(s)` : ""}. Les normes accessibles sont celles calculées par le backend.</p>}
      {isError && <p className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">Impossible de charger la bibliothèque des normes depuis le backend Laravel.</p>}

      <div className="flex flex-wrap gap-3">
        <div className="relative min-w-[240px] flex-1">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-primary" />
          <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Rechercher une norme par code ou nom…" className="h-11 w-full rounded-xl border border-input bg-card pl-10 pr-4 text-sm outline-none focus:border-primary" />
        </div>
        <input value={sectionSearch} onChange={(event) => setSectionSearch(event.target.value)} placeholder="Rechercher dans les chapitres…" className="h-11 min-w-[240px] flex-1 rounded-xl border border-input bg-card px-4 text-sm outline-none focus:border-primary" />
      </div>

      {isLoading ? <ListLoading rows={6} /> : norms.length === 0 ? (
        <div className="rounded-2xl border border-border bg-card p-10 text-center">
          <p className="font-display font-bold text-foreground">Aucune norme accessible</p>
          <p className="mt-1 text-sm text-muted-foreground">Le backend ne retourne aucune norme liée à votre entreprise ou à votre site courant. Vérifiez l’offre et les abonnements.</p>
        </div>
      ) : (
        <div className="grid gap-6 lg:grid-cols-[minmax(260px,360px)_1fr]">
          <section className="rounded-2xl border border-border bg-card p-4">
            <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Normes accessibles ({norms.length})</p>
            <ul className="mt-3 space-y-2">
              {norms.map((norm) => (
                <li key={String(norm.id)}>
                  <button onClick={() => setSelectedId(norm.id)} className={`w-full rounded-xl border p-4 text-left transition-colors ${String(selectedId) === String(norm.id) ? "border-primary bg-primary-soft/60" : "border-border hover:border-primary/50"}`}>
                    <div className="flex items-start justify-between gap-2"><span className="font-display font-extrabold text-foreground">{norm.code}</span><span className="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-bold text-muted-foreground">{norm.status ?? "Accessible"}</span></div>
                    <p className="mt-1 text-sm text-muted-foreground">{norm.name}</p>
                    {norm.domain && <p className="mt-1 text-xs text-muted-foreground">{norm.domain}</p>}
                  </button>
                </li>
              ))}
            </ul>
          </section>

          <section className="min-h-[420px] rounded-2xl border border-border bg-card p-5">
            {detailLoading || sectionsLoading ? <ListLoading rows={5} /> : selectedNorm ? (
              <>
                <div className="flex flex-wrap items-start justify-between gap-3 border-b border-border pb-4">
                  <div><p className="font-display text-xl font-extrabold text-foreground">{selectedNorm.code}</p><h2 className="mt-1 text-lg font-bold text-foreground">{selectedNorm.name}</h2><p className="mt-1 text-sm text-muted-foreground">{selectedNorm.domain ?? "Référentiel QHSE"}</p></div>
                  <div className="text-right text-xs text-muted-foreground"><p>{selectedNorm.status ?? "Accessible"}</p><p className="mt-1">Version {selectedNorm.currentVersion?.version_code ?? selectedNorm.current_version?.version_code ?? selectedNorm.versions?.[0]?.version_code ?? "—"}</p></div>
                </div>
                {sectionsError && <p className="mt-4 rounded-xl bg-warning/15 p-3 text-sm text-warning-foreground">{sectionsError}</p>}
                {!sectionsError && filteredSections.length === 0 && <p className="mt-5 text-sm text-muted-foreground">Aucun chapitre disponible pour cette norme.</p>}
                {filteredSections.length > 0 && <ul className="mt-4 space-y-1">{renderSections(filteredSections)}</ul>}
              </>
            ) : <p className="text-sm text-muted-foreground">Sélectionnez une norme.</p>}
          </section>
        </div>
      )}
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
    try {
      await updateMyProfile(form);
      toast.success("Paramètres de l'organisation enregistrés");
    } catch {
      toast.error("Enregistrement impossible. Vos saisies sont conservées.");
    }
    setBusy(false);
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
