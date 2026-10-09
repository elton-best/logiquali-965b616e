import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Download, Eye, FileText, Send, X } from "lucide-react";
import { backendApi } from "@/integrations/backend/client";
import { flattenResource, useProcesses } from "@/integrations/backend/context";

const card = "rounded-2xl border border-border bg-card";
const input = "w-full rounded-xl border border-input bg-background px-3.5 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15";
const button = "inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50";
const primary = `${button} bg-primary text-primary-foreground hover:bg-primary-dark`;
const secondary = `${button} border border-border bg-card hover:border-primary hover:text-primary`;
const muted = "text-sm text-muted-foreground";

export type DocumentActionsProps = {
  siteId: string;
  title: string;
  generatePath: string;
  filename: string;
  payload?: Record<string, unknown>;
};

type DocumentType = { id: string; name: string; abbreviation: string };
type Process = { id?: string; name: string; type?: string; abbreviation?: string };

type Action = "draft" | "preview" | "download" | "verify";

function documentInfo(payload: unknown) {
  const root = payload && typeof payload === "object" ? payload as Record<string, unknown> : {};
  const data = root.data && typeof root.data === "object" ? root.data as Record<string, unknown> : root;
  const attributes = data.attributes && typeof data.attributes === "object" ? data.attributes as Record<string, unknown> : data;
  return { id: data.id == null && attributes.id == null ? undefined : String(data.id ?? attributes.id), code: String(attributes.code ?? "") };
}

function processType(value: unknown) {
  const type = String(value ?? "").toLowerCase();
  if (["management", "pilotage", "direction"].includes(type)) return "management";
  if (["support", "soutien"].includes(type)) return "support";
  return "realization";
}

export function DocumentActions({ siteId, title, generatePath, filename, payload = {} }: DocumentActionsProps) {
  const { data: resources = [] } = useProcesses(siteId);
  const [types, setTypes] = useState<DocumentType[]>([]);
  const [typeId, setTypeId] = useState("");
  const [processId, setProcessId] = useState("");
  const [action, setAction] = useState<Action>("draft");
  const [configOpen, setConfigOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [documentId, setDocumentId] = useState<string>();
  const [documentCode, setDocumentCode] = useState("");
  const [previewUrl, setPreviewUrl] = useState<string>();
  const [previewOpen, setPreviewOpen] = useState(false);
  const [verifyOpen, setVerifyOpen] = useState(false);

  const processes: Process[] = resources.map((resource) => {
    const item = flattenResource(resource);
    return { id: item.id == null ? undefined : String(item.id), name: String(item.title ?? item.name ?? ""), type: processType(item.category ?? item.type), abbreviation: String(item.abbreviation ?? item.code ?? "") };
  }).filter((item) => item.name);

  useEffect(() => {
    let cancelled = false;
    backendApi.request<unknown>(`document-type-catalogs?site_id=${encodeURIComponent(siteId)}&is_active=true`)
      .then((response) => {
        const root = response && typeof response === "object" ? response as Record<string, unknown> : {};
        const values = Array.isArray(response) ? response : Array.isArray(root.data) ? root.data : [];
        const next = values.map((item) => {
          const value = flattenResource(item);
          return { id: String(value.id ?? ""), name: String(value.name ?? value.title ?? ""), abbreviation: String(value.abbreviation ?? "") };
        }).filter((item) => item.id && item.name);
        if (cancelled) return;
        setTypes(next);
        setTypeId((current) => current || next[0]?.id || "");
      })
      .catch(() => { if (!cancelled) setTypes([]); });
    return () => { cancelled = true; };
  }, [siteId]);

  useEffect(() => () => { if (previewUrl) URL.revokeObjectURL(previewUrl); }, [previewUrl]);

  const downloadBlob = (blob: Blob) => {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    URL.revokeObjectURL(url);
  };

  const openConfig = (nextAction: Action) => {
    if (!types.length) {
      toast.error("Aucun type documentaire actif n'est disponible pour ce site.");
      return;
    }
    setAction(nextAction);
    setTypeId((current) => current || types[0].id);
    setProcessId((current) => current || processes[0]?.id || "");
    setConfigOpen(true);
  };

  const handleDocument = async (nextAction: Exclude<Action, "draft">, id = documentId) => {
    if (!id) return;
    setBusy(true);
    try {
      if (nextAction === "verify") {
        setVerifyOpen(true);
      } else {
        const blob = await backendApi.blob(`documents/${id}/${nextAction === "preview" ? "preview" : "download"}`);
        if (nextAction === "preview") {
          if (previewUrl) URL.revokeObjectURL(previewUrl);
          setPreviewUrl(URL.createObjectURL(blob));
          setPreviewOpen(true);
        } else {
          downloadBlob(blob);
          toast.success("Téléchargement prêt.");
        }
      }
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Impossible d'accéder au document.");
    } finally {
      setBusy(false);
    }
  };

  const generate = async () => {
    if (!typeId || !processId) return;
    const process = processes.find((item) => item.id === processId);
    setBusy(true);
    try {
      const response = await backendApi.request<unknown>(generatePath, {
        method: "POST",
        body: JSON.stringify({
          site_id: Number(siteId),
          document_type_catalog_id: Number(typeId),
          ...(process?.id ? { process_id: Number(process.id) } : { process_name: process?.name, process_type: process?.type, process_abbreviation: process?.abbreviation }),
          ...payload,
        }),
      });
      const info = documentInfo(response);
      if (!info.id || !info.code) throw new Error("Le brouillon n'a pas retourné de code documentaire.");
      setDocumentId(info.id);
      setDocumentCode(info.code);
      setConfigOpen(false);
      toast.success("Brouillon documentaire généré.");
      if (action === "preview" || action === "download") await handleDocument(action, info.id);
      if (action === "verify") setVerifyOpen(true);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Impossible de générer le brouillon.");
    } finally {
      setBusy(false);
    }
  };

  const request = (nextAction: Exclude<Action, "draft">) => documentId ? void handleDocument(nextAction) : openConfig(nextAction);

  const submit = async () => {
    if (!documentId || !documentCode) return;
    setBusy(true);
    try {
      await backendApi.request(`documents/${documentId}/confirm-code`, { method: "POST", body: JSON.stringify({ needs_verification: true, confirmed_code: documentCode }) });
      setVerifyOpen(false);
      setDocumentId(undefined);
      setDocumentCode("");
      toast.success("Document envoyé pour vérification.");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Échec de la soumission pour vérification.");
    } finally {
      setBusy(false);
    }
  };

  return <>
    <button className={secondary} onClick={() => openConfig("draft")} disabled={busy}><FileText className="h-4 w-4" /> Générer</button>
    <button className={secondary} onClick={() => request("preview")} disabled={busy}><Eye className="h-4 w-4" /> Prévisualiser</button>
    <button className={secondary} onClick={() => request("download")} disabled={busy}><Download className="h-4 w-4" /> Télécharger</button>
    <button className={secondary} onClick={() => request("verify")} disabled={busy}><Send className="h-4 w-4" /> Vérifier</button>

    {configOpen && <div className="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="document-action-title"><div className={`${card} w-full max-w-xl p-6 shadow-2xl`}><div className="flex items-start justify-between gap-4"><div><p className="text-xs font-bold uppercase tracking-wide text-primary">{title}</p><h2 id="document-action-title" className="mt-1 font-display text-xl font-bold">Paramètres du document</h2></div><button type="button" className="rounded-xl p-2 text-muted-foreground hover:bg-background" onClick={() => setConfigOpen(false)} aria-label="Fermer"><X className="h-5 w-5" /></button></div><p className={`mt-3 ${muted}`}>Choisissez le type documentaire et le processus liés à la génération Laravel.</p><div className="mt-5 space-y-4"><div><label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">Type documentaire</label><select className={input} value={typeId} onChange={(event) => setTypeId(event.target.value)}><option value="">Sélectionner un type</option>{types.map((item) => <option key={item.id} value={item.id}>{item.name} ({item.abbreviation})</option>)}</select></div><div><label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">Processus lié</label><select className={input} value={processId} onChange={(event) => setProcessId(event.target.value)}><option value="">Sélectionner un processus</option>{processes.map((item) => <option key={item.id ?? item.name} value={item.id ?? item.name}>{item.name}{item.abbreviation ? ` (${item.abbreviation})` : ""}</option>)}</select></div></div><div className="mt-6 flex justify-end gap-2"><button type="button" className={secondary} onClick={() => setConfigOpen(false)}>Annuler</button><button type="button" className={primary} onClick={() => void generate()} disabled={busy || !typeId || !processId}>{busy ? "Génération…" : "Générer"}</button></div></div></div>}

    {previewOpen && previewUrl && <div className="fixed inset-0 z-50 flex flex-col bg-black/70 p-4" role="dialog" aria-modal="true" aria-label="Prévisualisation PDF"><div className="mb-3 flex justify-end"><button type="button" className="rounded-xl bg-card p-2" onClick={() => { setPreviewOpen(false); URL.revokeObjectURL(previewUrl); setPreviewUrl(undefined); }} aria-label="Fermer"><X className="h-5 w-5" /></button></div><iframe title={`Prévisualisation ${title}`} src={previewUrl} className="mx-auto h-full w-full max-w-5xl rounded-2xl bg-white" /></div>}
    {verifyOpen && <div className="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="document-verify-title"><div className={`${card} w-full max-w-lg p-6 shadow-2xl`}><h2 id="document-verify-title" className="font-display text-xl font-bold">Soumettre pour vérification</h2><p className={`mt-3 ${muted}`}>Le code généré est <strong className="text-foreground">{documentCode}</strong>. Confirmez-le pour lancer le workflow.</p><div className="mt-6 flex justify-end gap-2"><button type="button" className={secondary} onClick={() => setVerifyOpen(false)}>Annuler</button><button type="button" className={primary} onClick={() => void submit()} disabled={busy}>{busy ? "Envoi…" : "Envoyer pour vérification"}</button></div></div></div>}
  </>;
}
