import { Link, useNavigate } from "@tanstack/react-router";
import { AlertTriangle, ChevronDown, ChevronRight, FileDown, HelpCircle, Lock, Plus, X } from "lucide-react";
import { useMemo, useState } from "react";
import { toast } from "sonner";
import { isDone, isOverdue, useRecords, useSaveRecord, type QRecord } from "@/hooks/use-records";
import { DoubleScroll } from "./DoubleScroll";
import { ExportPreview } from "./Extras";
import { RecordActions, SectionView, StatusBadge } from "./SectionView";
import { SECTIONS } from "./sections";

const sel = "h-11 rounded-xl border border-input bg-card px-4 text-sm font-semibold outline-none focus:border-primary";
const ghost = "inline-flex h-10 items-center gap-2 rounded-xl border border-border bg-card px-4 text-sm font-semibold hover:border-primary hover:text-primary";
const primary = "inline-flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground hover:bg-primary-dark disabled:opacity-50";
const card = "rounded-2xl border border-border bg-card p-5";
const num = (v: unknown) => (v === null || v === undefined || v === "" ? 0 : Number(v));

function useOpen(slug: string) {
  const navigate = useNavigate();
  return {
    open: (r: QRecord) => navigate({ to: "/app/$section", params: { section: slug }, search: { open: r.id } }),
    create: (kind?: string, origin?: string) => navigate({ to: "/app/$section", params: { section: slug }, search: { new: 1, kind, origin } }),
  };
}

function Modal({ title, onClose, children }: { title: string; onClose: () => void; children: React.ReactNode }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/40 p-4" onClick={onClose}>
      <div className="max-h-[85vh] w-full max-w-2xl overflow-auto rounded-2xl bg-card p-6 shadow-xl" onClick={(e) => e.stopPropagation()}>
        <div className="mb-4 flex items-center justify-between"><h2 className="font-display text-lg font-bold">{title}</h2><button onClick={onClose} aria-label="Fermer"><X className="h-5 w-5" /></button></div>
        {children}
      </div>
    </div>
  );
}

/** Wrapper: a specialised view plus the standard record management view. */
export function GuideSection({ slug, views }: { slug: string; views: { id: string; label: string; render: () => React.ReactNode }[] }) {
  const [v, setV] = useState(views[0]!.id);
  const section = SECTIONS[slug]!;
  const all = [...views, { id: "fiches", label: "Gestion des fiches (créer, modifier, valider)", render: () => <SectionView section={section} /> }];
  const cur = all.find((x) => x.id === v) ?? all[0]!;
  return (
    <div>
      <div className="mx-auto max-w-7xl px-4 pt-4 md:px-8 md:pt-8">
        <select aria-label="Vue" value={v} onChange={(e) => setV(e.target.value)} className={`${sel} w-full sm:w-96`}>
          {all.map((x) => <option key={x.id} value={x.id}>Vue : {x.label}</option>)}
        </select>
      </div>
      {cur.id === "fiches" ? cur.render() : <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-8">{cur.render()}</div>}
    </div>
  );
}

function Head({ title, desc, children }: { title: string; desc: string; children?: React.ReactNode }) {
  return (
    <div className="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 className="font-display text-2xl font-extrabold text-foreground md:text-3xl">{title}</h1>
        <p className="mt-1 text-sm text-muted-foreground">{desc}</p>
      </div>
      {children && <div className="flex flex-wrap gap-2">{children}</div>}
    </div>
  );
}

function Chip({ active, onClick, label, count, tone = "" }: { active: boolean; onClick: () => void; label: string; count: number; tone?: string }) {
  return (
    <button onClick={onClick} className={`shrink-0 rounded-xl border px-4 py-2 text-left ${active ? "border-primary bg-primary-soft" : "border-border bg-card"}`}>
      <p className={`font-display text-xl font-extrabold ${tone}`}>{count}</p>
      <p className="whitespace-nowrap text-xs font-semibold text-muted-foreground">{label}</p>
    </button>
  );
}

// =====================================================================
// 6.1 Risques & opportunités — 3 vues, 2 sous-onglets, statuts cliquables, guide
// =====================================================================
const LEVELS = [
  { id: "Critique", min: 15, cls: "bg-destructive text-destructive-foreground" },
  { id: "Élevé", min: 8, cls: "bg-warning text-warning-foreground" },
  { id: "Modéré", min: 4, cls: "bg-info text-info-foreground" },
  { id: "Faible", min: 0, cls: "bg-success text-success-foreground" },
];
export const levelOf = (score: number) => LEVELS.find((l) => score >= l.min)!;

export function RisksView() {
  const { data: records = [] } = useRecords();
  const { open, create } = useOpen("risques");
  const [type, setType] = useState<"Risque" | "Opportunité">("Risque");
  const [view, setView] = useState<"grid" | "list" | "actions">("grid");
  const [level, setLevel] = useState("");
  const [status, setStatus] = useState("");
  const [help, setHelp] = useState(false);
  const byId = useMemo(() => new Map(records.map((r) => [r.id, r])), [records]);
  const all = records.filter((r) => r.kind === "risk" && (r.data["type"] ?? "Risque") === type && r.status !== "Archivé");
  const score = (r: QRecord) => num(r.data["probability"]) * num(r.data["gravity"]);
  const list = all.filter((r) => (!level || levelOf(score(r)).id === level) && (!status || r.status === status)).sort((a, b) => score(b) - score(a));
  const actionsOf = (r: QRecord) => records.filter((a) => a.kind === "action" && a.data["origin_id"] === r.id);
  const statuses = [...new Set(all.map((r) => r.status))];
  return (
    <>
      <Head title="Risques & opportunités" desc="Criticité = probabilité × impact. Cliquez sur un niveau ou un statut pour filtrer.">
        <button onClick={() => setHelp(true)} className={ghost}><HelpCircle className="h-4 w-4" /> Guide de la matrice</button>
        <button onClick={() => create("risk")} className={primary}><Plus className="h-4 w-4" /> Ajouter</button>
      </Head>
      <div className="flex flex-wrap gap-2">
        <select aria-label="Sous-onglet" value={type} onChange={(e) => { setType(e.target.value as "Risque"); setLevel(""); setStatus(""); }} className={sel}>
          <option value="Risque">Risques ({records.filter((r) => r.kind === "risk" && (r.data["type"] ?? "Risque") === "Risque").length})</option>
          <option value="Opportunité">Opportunités ({records.filter((r) => r.kind === "risk" && r.data["type"] === "Opportunité").length})</option>
        </select>
        <select aria-label="Mode de vue" value={view} onChange={(e) => setView(e.target.value as "grid")} className={sel}>
          <option value="grid">Vue grille</option><option value="list">Vue tableau</option><option value="actions">Vue plans d'action</option>
        </select>
      </div>
      <div className="flex gap-2 overflow-x-auto pb-1">
        <Chip active={!level && !status} onClick={() => { setLevel(""); setStatus(""); }} label="Total" count={all.length} />
        {LEVELS.map((l) => <Chip key={l.id} active={level === l.id} onClick={() => setLevel(level === l.id ? "" : l.id)} label={l.id} count={all.filter((r) => levelOf(score(r)).id === l.id).length} />)}
        {statuses.map((s) => <Chip key={s} active={status === s} onClick={() => setStatus(status === s ? "" : s)} label={s} count={all.filter((r) => r.status === s).length} />)}
      </div>
      {list.length === 0 && <p className={`${card} text-center text-sm text-muted-foreground`}>Aucun élément.</p>}
      {view === "grid" && (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {list.map((r) => {
            const lv = levelOf(score(r));
            return (
              <button key={r.id} onClick={() => open(r)} className={`${card} text-left hover:border-primary`}>
                <div className="flex items-center justify-between gap-2">
                  <span className="font-mono text-xs font-bold text-primary">{r.reference}</span>
                  <span onClick={(e) => { e.stopPropagation(); setLevel(lv.id); }} className={`rounded-full px-2.5 py-0.5 text-[11px] font-bold ${lv.cls}`}>{lv.id} · {score(r)}</span>
                </div>
                <p className="mt-2 font-semibold text-foreground">{r.title}</p>
                <p className="mt-1 text-xs text-muted-foreground">{String(byId.get(String(r.data["process_id"]))?.title ?? "Sans processus")} · {actionsOf(r).length} action(s)</p>
                <div className="mt-3" onClick={(e) => { e.stopPropagation(); setStatus(r.status); }}><StatusBadge kind="risk" status={r.status} /></div>
              </button>
            );
          })}
        </div>
      )}
      {view === "list" && list.length > 0 && (
        <DoubleScroll className="rounded-2xl border border-border bg-card">
          <table className="w-full min-w-[900px] text-sm">
            <thead className="bg-background text-left text-xs font-bold uppercase text-muted-foreground">
              <tr><th className="sticky left-0 bg-background px-3 py-3">Code</th><th className="sticky left-[90px] bg-background px-3 py-3">Libellé</th><th className="px-3 py-3">Processus</th><th className="px-3 py-3">P</th><th className="px-3 py-3">I</th><th className="px-3 py-3">Criticité</th><th className="px-3 py-3">Traitement</th><th className="px-3 py-3">Statut</th><th className="sticky right-0 bg-background px-3 py-3">Actions</th></tr>
            </thead>
            <tbody className="divide-y divide-border">
              {list.map((r) => { const lv = levelOf(score(r)); return (
                <tr key={r.id}>
                  <td className="sticky left-0 w-[90px] bg-card px-3 py-2 font-mono text-xs font-bold text-primary">{r.reference}</td>
                  <td className="sticky left-[90px] bg-card px-3 py-2 font-semibold">{r.title}</td>
                  <td className="px-3 py-2 text-muted-foreground">{String(byId.get(String(r.data["process_id"]))?.title ?? "—")}</td>
                  <td className="px-3 py-2">{num(r.data["probability"])}</td><td className="px-3 py-2">{num(r.data["gravity"])}</td>
                  <td className="px-3 py-2"><button onClick={() => setLevel(lv.id)} className={`rounded-full px-2.5 py-0.5 text-[11px] font-bold ${lv.cls}`}>{lv.id} · {score(r)}</button></td>
                  <td className="max-w-[240px] truncate px-3 py-2 text-muted-foreground">{String(r.data["treatment"] ?? "—")}</td>
                  <td className="px-3 py-2"><button onClick={() => setStatus(r.status)}><StatusBadge kind="risk" status={r.status} /></button></td>
                  <td className="sticky right-0 bg-card px-3 py-2"><button onClick={() => open(r)} className="text-xs font-bold text-primary">Ouvrir</button></td>
                </tr>
              ); })}
            </tbody>
          </table>
        </DoubleScroll>
      )}
      {view === "actions" && (
        <div className="space-y-3">
          {list.map((r) => (
            <div key={r.id} className={card}>
              <div className="flex flex-wrap items-center justify-between gap-2">
                <button onClick={() => open(r)} className="text-left"><span className="font-mono text-xs font-bold text-primary">{r.reference}</span> <span className="font-semibold">{r.title}</span></button>
                <Link to="/app/$section" params={{ section: "actions" }} search={{ new: 1, origin: r.id }} className={ghost}><Plus className="h-4 w-4" /> Action</Link>
              </div>
              <ul className="mt-3 space-y-1 text-sm">
                {actionsOf(r).map((a) => (
                  <li key={a.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-background px-3 py-2">
                    <span>{a.reference} · {a.title} {isOverdue(a) && <span className="ml-1 rounded-full bg-destructive/10 px-2 text-[11px] font-bold text-destructive">En retard</span>}</span>
                    <span className="flex items-center gap-2 text-xs text-muted-foreground">Échéance {a.data["due_date"] ? new Date(String(a.data["due_date"])).toLocaleDateString("fr-FR") : "—"} <StatusBadge kind="action" status={a.status} /></span>
                  </li>
                ))}
                {actionsOf(r).length === 0 && <li className="text-xs text-muted-foreground">Aucune action associée.</li>}
              </ul>
            </div>
          ))}
        </div>
      )}
      {help && (
        <Modal title="Guide d'utilisation de la matrice des risques" onClose={() => setHelp(false)}>
          <p className="text-sm text-muted-foreground">Notez la <b>probabilité</b> (1 = rare … 5 = quasi certain) et l'<b>impact</b> (1 = négligeable … 5 = catastrophique). La criticité est le produit des deux.</p>
          <div className="mt-4 grid grid-cols-6 gap-1 text-center text-xs font-bold">
            <div />{[1, 2, 3, 4, 5].map((i) => <div key={i}>I{i}</div>)}
            {[5, 4, 3, 2, 1].map((p) => [<div key={`p${p}`}>P{p}</div>, ...[1, 2, 3, 4, 5].map((i) => <div key={`${p}-${i}`} className={`rounded py-2 ${levelOf(p * i).cls}`}>{p * i}</div>)])}
          </div>
          <ul className="mt-4 space-y-1 text-sm">
            <li><b>15–25 Critique</b> : action immédiate, validation de la direction.</li>
            <li><b>8–14 Élevé</b> : plan d'action sous 1 mois.</li>
            <li><b>4–7 Modéré</b> : action planifiée, surveillance.</li>
            <li><b>1–3 Faible</b> : risque accepté, revue annuelle.</li>
          </ul>
        </Modal>
      )}
    </>
  );
}

// =====================================================================
// 6.1.1 DUERP — UT, arborescence, échelles dynamiques, workflow, accidents
// =====================================================================
type Scale = { label: string; coef: number }[];
const DEFAULT_G: Scale = [{ label: "Faible", coef: 1 }, { label: "Moyenne", coef: 2 }, { label: "Grave", coef: 3 }, { label: "Très grave", coef: 4 }];
const DEFAULT_F: Scale = [{ label: "Rare", coef: 1 }, { label: "Occasionnelle", coef: 2 }, { label: "Fréquente", coef: 3 }, { label: "Permanente", coef: 4 }];

export function DuerpView() {
  const { data: records = [] } = useRecords();
  const save = useSaveRecord();
  const { open, create } = useOpen("dangers");
  const [collapsed, setCollapsed] = useState<Set<string>>(new Set());
  const [scalesOpen, setScalesOpen] = useState(false);
  const versions = records.filter((r) => r.kind === "duerp").sort((a, b) => b.created_at.localeCompare(a.created_at));
  const cur = versions[0];
  const G: Scale = (cur?.data["gravity_scale"] as Scale) ?? DEFAULT_G;
  const F: Scale = (cur?.data["frequency_scale"] as Scale) ?? DEFAULT_F;
  const uts = records.filter((r) => r.kind === "work_unit" && r.status !== "Archivé");
  const hazards = records.filter((r) => r.kind === "hazard");
  const incidents = records.filter((r) => r.kind === "incident");
  const coef = (s: Scale, v: unknown) => s[Math.max(0, Math.min(s.length - 1, num(v) - 1))]?.coef ?? 0;
  const score = (h: QRecord) => (h.data["gravity"] && h.data["frequency"] ? coef(G, h.data["gravity"]) * coef(F, h.data["frequency"]) : num(h.data["score"]));
  const max = (G.at(-1)?.coef ?? 4) * (F.at(-1)?.coef ?? 4);
  const tone = (s: number) => (s >= max * 0.6 ? "bg-destructive text-destructive-foreground" : s >= max * 0.3 ? "bg-warning text-warning-foreground" : "bg-success text-success-foreground");
  const toggle = (id: string) => setCollapsed((c) => { const n = new Set(c); n.has(id) ? n.delete(id) : n.add(id); return n; });
  const orphan = hazards.filter((h) => !uts.some((u) => u.id === h.data["ut_id"]));

  const createVersion = () => save.mutate({ kind: "duerp", title: `DUERP ${new Date().getFullYear()}`, status: "Brouillon", data: { year: String(new Date().getFullYear()), gravity_scale: DEFAULT_G, frequency_scale: DEFAULT_F } });

  return (
    <>
      <Head title="DUERP — Document unique" desc="Unités de travail ➔ familles de risques ➔ risques identifiés, avec cotation Gravité × Fréquence.">
        <button onClick={() => setScalesOpen(true)} className={ghost} disabled={!cur}>Échelles G / F</button>
        <button onClick={() => create("work_unit")} className={ghost}><Plus className="h-4 w-4" /> Unité de travail</button>
        <button onClick={() => create("hazard")} className={primary}><Plus className="h-4 w-4" /> Risque</button>
      </Head>
      <div className={`${card} flex flex-wrap items-center justify-between gap-3`}>
        {cur ? (
          <>
            <div><p className="text-xs font-bold text-primary">{cur.reference}</p><p className="font-semibold">{cur.title}</p><p className="text-xs text-muted-foreground">Circuit : Brouillon ➔ Vérification RQ ➔ Approbation CEO</p></div>
            <div className="flex flex-wrap items-center gap-2"><StatusBadge kind="duerp" status={cur.status} /><RecordActions record={cur} compact /></div>
          </>
        ) : (
          <><p className="text-sm text-muted-foreground">Aucune version du DUERP. Créez-la pour lancer le circuit de validation.</p><button onClick={createVersion} className={primary}>Créer le DUERP</button></>
        )}
      </div>
      <div className="grid gap-3 sm:grid-cols-4">
        <div className={card}><p className="text-xs text-muted-foreground">Unités de travail</p><p className="font-display text-2xl font-extrabold">{uts.length}</p></div>
        <div className={card}><p className="text-xs text-muted-foreground">Risques identifiés</p><p className="font-display text-2xl font-extrabold">{hazards.length}</p></div>
        <div className={card}><p className="text-xs text-muted-foreground">Risques prioritaires</p><p className="font-display text-2xl font-extrabold text-destructive">{hazards.filter((h) => score(h) >= max * 0.6).length}</p></div>
        <Link to="/app/$section" params={{ section: "incidents" }} className={`${card} hover:border-primary`}><p className="text-xs text-muted-foreground">Accidents SST ouverts</p><p className="font-display text-2xl font-extrabold">{incidents.filter((i) => i.status !== "Clôturé").length}</p><p className="text-xs font-bold text-primary">Traiter les accidents ➔</p></Link>
      </div>
      <div className="space-y-3">
        {[...uts.map((u) => ({ id: u.id, title: `${u.reference} · ${u.title}`, sub: String(u.data["basis"] ?? ""), rec: u as QRecord | undefined, items: hazards.filter((h) => h.data["ut_id"] === u.id) })),
          ...(orphan.length ? [{ id: "none", title: "Sans unité de travail", sub: "À rattacher", rec: undefined, items: orphan }] : [])].map((u) => {
          const families = [...new Set(u.items.map((h) => String(h.data["family"] || "Non classé")))];
          return (
            <div key={u.id} className={card}>
              <button onClick={() => toggle(u.id)} className="flex w-full items-center gap-2 text-left">
                {collapsed.has(u.id) ? <ChevronRight className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                <span className="font-display font-bold">{u.title}</span>
                <span className="text-xs text-muted-foreground">{u.sub} · {u.items.length} risque(s)</span>
              </button>
              {!collapsed.has(u.id) && (
                <div className="mt-3 space-y-3 pl-6">
                  {families.map((f) => (
                    <div key={f}>
                      <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">{f}</p>
                      <ul className="mt-1 space-y-1">
                        {u.items.filter((h) => String(h.data["family"] || "Non classé") === f).map((h) => {
                          const prev = records.filter((a) => a.kind === "action" && a.data["origin_id"] === h.id);
                          return (
                            <li key={h.id} className="rounded-lg bg-background p-3">
                              <div className="flex flex-wrap items-center justify-between gap-2">
                                <button onClick={() => open(h)} className="text-left text-sm"><span className="font-mono text-xs font-bold text-primary">{h.reference}</span> <span className="font-semibold">{h.title}</span></button>
                                <div className="flex items-center gap-2 text-xs">
                                  <span className="text-muted-foreground">G : {G[num(h.data["gravity"]) - 1]?.label ?? "—"} · F : {F[num(h.data["frequency"]) - 1]?.label ?? "—"}</span>
                                  <span className={`rounded-full px-2.5 py-0.5 font-bold ${tone(score(h))}`}>{score(h)}</span>
                                  <StatusBadge kind="hazard" status={h.status} />
                                </div>
                              </div>
                              <div className="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                <span className="text-muted-foreground">Prévention :</span>
                                {prev.map((a) => <span key={a.id} className={`rounded-full px-2 py-0.5 font-semibold ${isOverdue(a) ? "bg-destructive/10 text-destructive" : isDone(a) ? "bg-success/15 text-success" : "bg-info/15 text-info"}`}>{a.reference} {a.title}</span>)}
                                <Link to="/app/$section" params={{ section: "actions" }} search={{ new: 1, origin: h.id }} className="font-bold text-primary">+ Action de prévention</Link>
                              </div>
                            </li>
                          );
                        })}
                      </ul>
                    </div>
                  ))}
                  {u.items.length === 0 && <p className="text-xs text-muted-foreground">Aucun risque dans cette unité.</p>}
                </div>
              )}
            </div>
          );
        })}
        {uts.length === 0 && <p className={`${card} text-sm text-muted-foreground`}>Commencez par définir vos unités de travail (par processus, site ou atelier).</p>}
      </div>
      {scalesOpen && cur && <ScalesEditor cur={cur} G={G} F={F} onClose={() => setScalesOpen(false)} />}
    </>
  );
}

function ScalesEditor({ cur, G, F, onClose }: { cur: QRecord; G: Scale; F: Scale; onClose: () => void }) {
  const save = useSaveRecord();
  const [g, setG] = useState(G);
  const [f, setF] = useState(F);
  const edit = (s: Scale, set: (x: Scale) => void, title: string) => (
    <div>
      <p className="mb-2 text-sm font-bold">{title}</p>
      {s.map((l, i) => (
        <div key={i} className="mb-1 flex gap-2">
          <span className="w-6 pt-2 text-xs text-muted-foreground">{i + 1}</span>
          <input value={l.label} onChange={(e) => set(s.map((x, j) => (j === i ? { ...x, label: e.target.value } : x)))} className="h-9 flex-1 rounded-lg border border-input px-2 text-sm" />
          <input type="number" value={l.coef} onChange={(e) => set(s.map((x, j) => (j === i ? { ...x, coef: Number(e.target.value) } : x)))} className="h-9 w-20 rounded-lg border border-input px-2 text-sm" />
          <button onClick={() => s.length > 2 && set(s.filter((_, j) => j !== i))} aria-label="Retirer"><X className="h-4 w-4" /></button>
        </div>
      ))}
      <button onClick={() => set([...s, { label: "Nouveau niveau", coef: (s.at(-1)?.coef ?? 0) + 1 }])} className="text-xs font-bold text-primary">+ Niveau</button>
    </div>
  );
  return (
    <Modal title="Échelles de gravité et de fréquence" onClose={onClose}>
      <div className="grid gap-6 sm:grid-cols-2">{edit(g, setG, "Gravité (libellé · coefficient)")}{edit(f, setF, "Fréquence (libellé · coefficient)")}</div>
      <p className="mt-3 text-xs text-muted-foreground">Dans chaque fiche risque, saisissez le numéro de niveau (1, 2, 3…) pour la gravité et la fréquence.</p>
      <button onClick={async () => { await save.mutateAsync({ id: cur.id, kind: cur.kind, title: cur.title, status: cur.status, data: { ...cur.data, gravity_scale: g, frequency_scale: f }, previous: cur }); onClose(); }} className={`${primary} mt-4`}>Enregistrer les échelles</button>
    </Modal>
  );
}

// =====================================================================
// 6.2 Objectifs — grille mensuelle, plafond 100 %, N/A, verrouillage, récapitulatifs
// =====================================================================
const MONTHS = ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc"];
type Months = Record<string, number | "NA">;

/** Average of filled, applicable months up to (and including) the current month. */
export function currentRate(months: Months, currentMonth: number): number | null {
  const vals: number[] = [];
  for (let m = 1; m <= currentMonth; m++) { const v = months[String(m)]; if (typeof v === "number") vals.push(v); }
  return vals.length ? Math.round((vals.reduce((a, b) => a + b, 0) / vals.length) * 10) / 10 : null;
}
/** A month is locked once its last calendar day has passed. */
export const isMonthLocked = (year: number, month: number, today = new Date()) => new Date(year, month, 1) <= new Date(today.getFullYear(), today.getMonth(), today.getDate());

export function ObjectivesGrid() {
  const { data: records = [] } = useRecords();
  const save = useSaveRecord();
  const today = new Date();
  const year = today.getFullYear();
  const cm = today.getMonth() + 1;
  const byId = useMemo(() => new Map(records.map((r) => [r.id, r])), [records]);
  const objs = records.filter((r) => r.kind === "objective" && r.status !== "Archivé");
  const monthsOf = (o: QRecord): Months => ((o.data["months"] as Record<string, Months> | undefined)?.[year] ?? {});
  const setMonth = (o: QRecord, m: number, v: number | "NA" | null) => {
    const all = { ...((o.data["months"] as Record<string, Months>) ?? {}) };
    const cur = { ...(all[year] ?? {}) };
    if (v === null) delete cur[String(m)]; else cur[String(m)] = v;
    all[year] = cur;
    const rate = currentRate(cur, cm);
    save.mutate({ id: o.id, kind: o.kind, title: o.title, status: o.status, data: { ...o.data, months: all, progress: rate ?? o.data["progress"] ?? null }, previous: o, silent: true });
  };
  const rate = (o: QRecord) => currentRate(monthsOf(o), cm);
  const recap = (key: (o: QRecord) => string[]) => {
    const groups = new Map<string, number[]>();
    for (const o of objs) { const r = rate(o); if (r === null) continue; for (const k of key(o)) groups.set(k, [...(groups.get(k) ?? []), r]); }
    return [...groups.entries()].map(([k, v]) => ({ k, n: v.length, avg: Math.round((v.reduce((a, b) => a + b, 0) / v.length) * 10) / 10 }));
  };
  const normsOf = (o: QRecord) => (Array.isArray(o.data["norms"]) ? (o.data["norms"] as string[]) : [String(o.data["norm"] || "ISO 9001")]);
  const recaps = [
    { t: "Par processus", rows: recap((o) => [String(byId.get(String(o.data["process_id"]))?.title ?? "Sans processus")]) },
    { t: "Par axe stratégique", rows: recap((o) => [String(o.data["axis"] || "Sans axe")]) },
    { t: "Par norme", rows: recap(normsOf) },
    { t: "Global du système", rows: recap(() => ["Système de management"]) },
  ];
  return (
    <>
      <Head title={`Objectifs — taux d'atteinte ${year}`} desc="Saisie mensuelle en % (maximum 100). « N/A » exclut le mois du calcul. Les mois écoulés sont verrouillés." />
      <DoubleScroll className="rounded-2xl border border-border bg-card">
        <table className="w-full min-w-[1300px] text-sm">
          <thead className="bg-background text-xs font-bold uppercase text-muted-foreground">
            <tr>
              <th className="sticky left-0 z-10 w-[220px] bg-background px-3 py-3 text-left">Objectif</th>
              <th className="sticky left-[220px] z-10 w-[150px] bg-background px-3 py-3 text-left">Processus</th>
              <th className="sticky left-[370px] z-10 w-[90px] bg-background px-3 py-3">Taux actuel</th>
              {MONTHS.map((m, i) => <th key={m} className={`px-1 py-3 ${i + 1 === cm ? "text-primary" : ""}`}>{m}</th>)}
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {objs.map((o) => {
              const ms = monthsOf(o);
              const r = rate(o);
              return (
                <tr key={o.id}>
                  <td className="sticky left-0 z-10 w-[220px] bg-card px-3 py-2"><p className="font-mono text-[11px] font-bold text-primary">{o.reference}</p><p className="font-semibold">{o.title}</p><p className="text-[11px] text-muted-foreground">{String(o.data["axis"] ?? "")}</p></td>
                  <td className="sticky left-[220px] z-10 w-[150px] bg-card px-3 py-2 text-xs text-muted-foreground">{String(byId.get(String(o.data["process_id"]))?.title ?? "—")}</td>
                  <td className="sticky left-[370px] z-10 w-[90px] bg-card px-3 py-2 text-center"><span className={`rounded-full px-2 py-0.5 text-xs font-bold ${r === null ? "bg-muted text-muted-foreground" : r >= 80 ? "bg-success/15 text-success" : r >= 50 ? "bg-warning/15 text-warning-foreground" : "bg-destructive/10 text-destructive"}`}>{r === null ? "—" : `${r} %`}</span></td>
                  {MONTHS.map((_, i) => {
                    const m = i + 1;
                    const v = ms[String(m)];
                    const locked = isMonthLocked(year, m, today);
                    if (v === "NA") return (
                      <td key={m} className="bg-muted px-1 py-2 text-center"><button disabled={locked} onClick={() => setMonth(o, m, null)} className="text-[11px] font-bold text-muted-foreground disabled:cursor-not-allowed">N/A</button></td>
                    );
                    return (
                      <td key={m} className={`px-1 py-2 text-center ${m > cm ? "opacity-60" : ""}`}>
                        {locked ? (
                          <span className="inline-flex items-center gap-1 text-xs font-semibold" title="Mois clôturé — lecture seule">{typeof v === "number" ? `${v}` : "—"}<Lock className="h-3 w-3 text-muted-foreground" /></span>
                        ) : (
                          <div className="flex flex-col items-center gap-0.5">
                            <input type="number" min={0} max={100} defaultValue={typeof v === "number" ? v : ""} key={`${o.id}-${m}-${String(v)}`}
                              onBlur={(e) => {
                                const t = e.target.value;
                                if (t === "") { if (v !== undefined) setMonth(o, m, null); return; }
                                const n = Number(t);
                                if (n > 100) { toast.error("Valeur maximale : 100 %."); e.target.value = typeof v === "number" ? String(v) : ""; return; }
                                if (n < 0) { toast.error("La valeur ne peut pas être négative."); return; }
                                if (n !== v) setMonth(o, m, n);
                              }}
                              className="h-8 w-14 rounded-md border border-input px-1 text-center text-xs outline-none focus:border-primary" aria-label={`${o.title} ${MONTHS[i]}`} />
                            <button onClick={() => setMonth(o, m, "NA")} className="text-[10px] font-bold text-muted-foreground hover:text-primary">N/A</button>
                          </div>
                        )}
                      </td>
                    );
                  })}
                </tr>
              );
            })}
          </tbody>
        </table>
      </DoubleScroll>
      {objs.length === 0 && <p className="text-sm text-muted-foreground">Aucun objectif. Créez-en depuis « Gestion des fiches ».</p>}
      <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        {recaps.map((g) => (
          <div key={g.t} className={card}>
            <p className="font-display font-bold">Récapitulatif {g.t.toLowerCase()}</p>
            <ul className="mt-2 space-y-2 text-sm">
              {g.rows.map((r) => (
                <li key={r.k}>
                  <div className="flex justify-between"><span className="truncate">{r.k}</span><span className="font-bold">{r.avg} %</span></div>
                  <div className="mt-1 h-1.5 rounded-full bg-muted"><div className="h-1.5 rounded-full bg-primary" style={{ width: `${Math.min(100, r.avg)}%` }} /></div>
                </li>
              ))}
              {g.rows.length === 0 && <li className="text-xs text-muted-foreground">Pas encore de saisie.</li>}
            </ul>
          </div>
        ))}
      </div>
    </>
  );
}

// =====================================================================
// RT-12 Bloc d'action standard
// =====================================================================
export type ActionBlock = { summary?: string; decision?: string; owner?: string; due?: string; status?: string };
export function StandardActionBlock({ value, onChange, collaborators, disabled }: { value: ActionBlock; onChange: (v: ActionBlock) => void; collaborators: QRecord[]; disabled?: boolean }) {
  const i = "h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:border-primary disabled:opacity-60";
  const set = (k: keyof ActionBlock, v: string) => onChange({ ...value, [k]: v });
  return (
    <div className="grid gap-2 sm:grid-cols-2">
      <textarea disabled={disabled} className={`${i} h-20 py-2 sm:col-span-2`} placeholder="Synthèse / observations" value={value.summary ?? ""} onChange={(e) => set("summary", e.target.value)} />
      <input disabled={disabled} className={`${i} sm:col-span-2`} placeholder="Décision / action" value={value.decision ?? ""} onChange={(e) => set("decision", e.target.value)} />
      <select disabled={disabled} className={i} value={value.owner ?? ""} onChange={(e) => set("owner", e.target.value)}>
        <option value="">Responsable…</option>
        {collaborators.map((c) => <option key={c.id} value={c.id}>{c.title}</option>)}
      </select>
      <input disabled={disabled} type="date" className={i} value={value.due ?? ""} onChange={(e) => set("due", e.target.value)} />
      <select disabled={disabled} className={i} value={value.status ?? "À planifier"} onChange={(e) => set("status", e.target.value)}>
        {["À planifier", "En cours", "Réalisé", "En retard"].map((s) => <option key={s}>{s}</option>)}
      </select>
    </div>
  );
}

// =====================================================================
// 9.2 Revue de processus — 9 sections, verrouillage de clôture, rapport ; 9.3 consolidation
// =====================================================================
const Sec = ({ n, t, children }: { n: number; t: string; children: React.ReactNode }) => (
    <section className={card}><h3 className="font-display font-bold"><span className="mr-2 rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground">{n}</span>{t}</h3><div className="mt-3">{children}</div></section>
  );
const Count = ({ label, v, to, tone }: { label: string; v: number; to: string; tone: string }) => (
    <Link to="/app/$section" params={{ section: to }} className="rounded-xl border border-border p-3 hover:border-primary"><p className={`font-display text-xl font-extrabold ${tone}`}>{v}</p><p className="text-xs text-muted-foreground">{label}</p></Link>
  );

export function ProcessReviewView() {
  const { data: records = [] } = useRecords();
  const save = useSaveRecord();
  const { create } = useOpen("revue-processus");
  const reviews = records.filter((r) => r.kind === "process_review").sort((a, b) => b.created_at.localeCompare(a.created_at));
  const [id, setId] = useState<string>("");
  const [draft, setDraft] = useState<Record<string, unknown> | null>(null);
  const [preview, setPreview] = useState(false);
  const rv = reviews.find((r) => r.id === id) ?? reviews[0];
  const byId = useMemo(() => new Map(records.map((r) => [r.id, r])), [records]);
  const collaborators = records.filter((r) => r.kind === "collaborator");
  if (!rv) return (
    <div className={card}><p className="text-sm text-muted-foreground">Aucune revue de processus. Planifiez-en une.</p><button onClick={() => create("process_review")} className={`${primary} mt-3`}><Plus className="h-4 w-4" /> Planifier une revue</button></div>
  );
  const d: Record<string, unknown> = draft && draft["_id"] === rv.id ? draft : { ...rv.data, _id: rv.id };
  const setD = (k: string, v: unknown) => setDraft({ ...d, [k]: v });
  const pid = String(rv.data["process_id"] ?? "");
  const proc = byId.get(pid);
  const ofP = (k: string) => records.filter((r) => r.kind === k && r.data["process_id"] === pid);
  const ncs = ofP("nc"), objs = ofP("objective"), acts = ofP("action"), risks = ofP("risk");
  const complaints = records.filter((r) => r.kind === "complaint");
  const context = records.filter((r) => r.kind === "context" && (!r.data["process_id"] || r.data["process_id"] === pid));
  const parties = records.filter((r) => r.kind === "party" && (!r.data["process_id"] || r.data["process_id"] === pid));
  const sequences = String(proc?.data["sequences"] ?? "").split("\n").map((s) => s.trim()).filter(Boolean);
  const seqObs = (d["seq_obs"] as string[] | undefined) ?? [];
  const pending = acts.filter((a) => !isDone(a));
  const blocking = pending.filter((a) => isOverdue(a) || !a.data["due_date"]);
  const closed = rv.status === "Tenue";
  const block = (k: string) => (d[k] as ActionBlock | undefined) ?? {};
  const persist = async (status = rv.status) => {
    const { _id, ...rest } = d; void _id;
    await save.mutateAsync({ id: rv.id, kind: rv.kind, title: rv.title, status, data: rest, previous: rv });
    setDraft(null);
  };
  const reportRows = (): string[][] => [
    ["Section", "Contenu"],
    ["Processus", proc ? `${proc.reference} ${proc.title}` : "—"],
    ["1. Enjeux", context.map((c) => c.title).join(" ; ")],
    ["2. Synthèse PIP", parties.map((p) => p.title).join(" ; ")],
    ["3. NC & réclamations", `${ncs.filter((n) => n.status !== "Clôturée").length} NC en cours, ${ncs.filter((n) => n.status === "Clôturée").length} clôturées, ${complaints.length} réclamations`],
    ["4. Objectifs & activités", `${objs.length} objectifs ; actions réalisées ${acts.length - pending.length}/${acts.length}`],
    ["5. Séquences", sequences.map((s, i) => `${s} : ${seqObs[i] ?? ""}`).join(" | ")],
    ["6. Risques & opportunités", `${risks.length} risques ; nouveaux : ${String(d["new_risks"] ?? "")}`],
    ["7. Besoins & ressources", `${block("s7").summary ?? ""} — ${block("s7").decision ?? ""}`],
    ["8. Modifications du système", `${String(d["changes"] ?? "")} — ${block("s8").decision ?? ""}`],
    ["9. Difficultés & suggestions", String(d["difficulties"] ?? "")],
  ];
  const ta = "h-24 w-full rounded-lg border border-input bg-background p-3 text-sm outline-none focus:border-primary disabled:opacity-60";
  return (
    <>
      <Head title="Revue de processus" desc="Les 9 sections obligatoires. La clôture est bloquée tant que des actions non réalisées n'ont pas été replanifiées.">
        <select aria-label="Revue" value={rv.id} onChange={(e) => { setId(e.target.value); setDraft(null); }} className={sel}>
          {reviews.map((r) => <option key={r.id} value={r.id}>{r.reference} · {r.title}</option>)}
        </select>
        <button onClick={() => create("process_review")} className={ghost}><Plus className="h-4 w-4" /> Nouvelle</button>
      </Head>
      <div className={`${card} flex flex-wrap items-center justify-between gap-3`}>
        <div><p className="font-semibold">{proc ? `${proc.reference} · ${proc.title}` : "Processus non défini"}</p><p className="text-xs text-muted-foreground">Date : {rv.data["date"] ? new Date(String(rv.data["date"])).toLocaleDateString("fr-FR") : "—"}</p></div>
        <StatusBadge kind="process_review" status={rv.status} />
      </div>
      <Sec n={1} t="Enjeux">{context.length ? <ul className="list-disc pl-5 text-sm">{context.map((c) => <li key={c.id}>{c.title}</li>)}</ul> : <p className="text-sm text-muted-foreground">Aucun enjeu applicable.</p>}</Sec>
      <Sec n={2} t="Synthèse des parties intéressées">{parties.length ? <ul className="list-disc pl-5 text-sm">{parties.map((p) => <li key={p.id}>{p.title} {p.data["expectations"] ? `— ${String(p.data["expectations"])}` : ""}</li>)}</ul> : <p className="text-sm text-muted-foreground">Aucune partie intéressée.</p>}</Sec>
      <Sec n={3} t="Non-conformités & réclamations">
        <div className="grid grid-cols-3 gap-2">
          <Count label="NC en cours" v={ncs.filter((n) => n.status !== "Clôturée").length} to="non-conformites" tone="text-destructive" />
          <Count label="NC clôturées" v={ncs.filter((n) => n.status === "Clôturée").length} to="non-conformites" tone="text-success" />
          <Count label="Réclamations clients" v={complaints.length} to="reclamations" tone="text-warning-foreground" />
        </div>
      </Sec>
      <Sec n={4} t="Objectifs & activités">
        <ul className="space-y-1 text-sm">{objs.map((o) => <li key={o.id} className="flex justify-between"><span>{o.title}</span><span className="font-bold">{o.data["progress"] != null ? `${String(o.data["progress"])} %` : "—"}</span></li>)}</ul>
        <p className="mt-2 text-sm">Activités réalisées : <b className="text-success">{acts.length - pending.length}</b> · non réalisées : <b className="text-destructive">{pending.length}</b></p>
      </Sec>
      <Sec n={5} t="Activités menées (séquences du processus)">
        {sequences.length === 0 ? <p className="text-sm text-muted-foreground">Renseignez les séquences opérationnelles dans la fiche du processus.</p> : (
          <ol className="space-y-2">{sequences.map((s, i) => (
            <li key={i}><p className="text-sm font-semibold">{i + 1}. {s}</p>
              <input disabled={closed} value={seqObs[i] ?? ""} onChange={(e) => { const n = [...seqObs]; n[i] = e.target.value; setD("seq_obs", n); }} placeholder="Observation" className="mt-1 h-9 w-full rounded-lg border border-input bg-background px-3 text-sm disabled:opacity-60" /></li>
          ))}</ol>
        )}
      </Sec>
      <Sec n={6} t="Risques & opportunités">
        <ul className="list-disc pl-5 text-sm">{risks.map((r) => <li key={r.id}>{r.reference} {r.title} — {r.status}</li>)}</ul>
        <textarea disabled={closed} className={`${ta} mt-2`} placeholder="Risques survenus / nouveaux risques déclarés" value={String(d["new_risks"] ?? "")} onChange={(e) => setD("new_risks", e.target.value)} />
      </Sec>
      <Sec n={7} t="Besoins & ressources"><StandardActionBlock disabled={closed} value={block("s7")} onChange={(v) => setD("s7", v)} collaborators={collaborators} /></Sec>
      <Sec n={8} t="Modifications à apporter au système">
        <textarea disabled={closed} className={ta} placeholder="Besoins d'évolution du système" value={String(d["changes"] ?? "")} onChange={(e) => setD("changes", e.target.value)} />
        <div className="mt-2"><StandardActionBlock disabled={closed} value={block("s8")} onChange={(v) => setD("s8", v)} collaborators={collaborators} /></div>
      </Sec>
      <Sec n={9} t="Difficultés rencontrées & suggestions">
        <textarea disabled={closed} className={ta} placeholder="Difficultés et propositions d'actions" value={String(d["difficulties"] ?? "")} onChange={(e) => setD("difficulties", e.target.value)} />
        <label className="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" disabled={closed} checked={!!d["rq_visa"]} onChange={(e) => setD("rq_visa", e.target.checked)} className="h-4 w-4 accent-primary" /> Soumettre les propositions au visa du RQ</label>
      </Sec>
      {blocking.length > 0 && !closed && (
        <div className="rounded-2xl border border-destructive/30 bg-destructive/5 p-4">
          <p className="flex items-center gap-2 text-sm font-bold text-destructive"><AlertTriangle className="h-4 w-4" /> {blocking.length} action(s) non réalisée(s) à replanifier avant la clôture</p>
          <ul className="mt-2 space-y-1 text-sm">{blocking.map((a) => <li key={a.id}><Link to="/app/$section" params={{ section: "actions" }} search={{ open: a.id }} className="font-semibold text-primary hover:underline">{a.reference} · {a.title}</Link> — utilisez « Replanifier »</li>)}</ul>
        </div>
      )}
      <div className="flex flex-wrap gap-2">
        <button disabled={closed || !draft} onClick={() => persist()} className={ghost}>Enregistrer</button>
        <button onClick={() => setPreview(true)} className={ghost}><FileDown className="h-4 w-4" /> Rapport d'activité</button>
        <button disabled={closed || blocking.length > 0} onClick={() => persist("Tenue")} className={primary} title={blocking.length ? "Replanifiez d'abord les actions non réalisées" : ""}>Clôturer la revue</button>
      </div>
      <ExportPreview open={preview} onClose={() => setPreview(false)} filename={`rapport-${rv.reference}.csv`} rows={preview ? reportRows() : []} />
    </>
  );
}

export function ManagementConsolidation() {
  const { data: records = [] } = useRecords();
  const byId = useMemo(() => new Map(records.map((r) => [r.id, r])), [records]);
  const reviews = records.filter((r) => r.kind === "process_review");
  return (
    <>
      <Head title="Consolidation des revues de processus" desc="Synthèse de toutes les revues de processus pour préparer la revue de direction." />
      <DoubleScroll className="rounded-2xl border border-border bg-card">
        <table className="w-full min-w-[900px] text-sm">
          <thead className="bg-background text-left text-xs font-bold uppercase text-muted-foreground"><tr><th className="sticky left-0 bg-background px-3 py-3">Revue</th><th className="px-3 py-3">Processus</th><th className="px-3 py-3">Statut</th><th className="px-3 py-3">Besoins & ressources</th><th className="px-3 py-3">Modifications</th><th className="px-3 py-3">Difficultés</th></tr></thead>
          <tbody className="divide-y divide-border">
            {reviews.map((r) => (
              <tr key={r.id}>
                <td className="sticky left-0 bg-card px-3 py-2 font-mono text-xs font-bold text-primary">{r.reference}</td>
                <td className="px-3 py-2">{String(byId.get(String(r.data["process_id"]))?.title ?? "—")}</td>
                <td className="px-3 py-2"><StatusBadge kind="process_review" status={r.status} /></td>
                <td className="px-3 py-2 text-muted-foreground">{(r.data["s7"] as ActionBlock | undefined)?.decision ?? "—"}</td>
                <td className="px-3 py-2 text-muted-foreground">{String(r.data["changes"] ?? "—")}</td>
                <td className="px-3 py-2 text-muted-foreground">{String(r.data["difficulties"] ?? "—")}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </DoubleScroll>
    </>
  );
}
