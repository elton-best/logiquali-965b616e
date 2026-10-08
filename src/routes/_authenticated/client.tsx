import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Bell, CheckCircle2, Clock, HelpCircle, LayoutDashboard, LogOut, Menu, MessageSquareWarning, Plus, Smile, Star, User, X } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { supabase } from "@/integrations/supabase/client";
import type { Json } from "@/integrations/supabase/types";
import { getMyProfile } from "@/lib/profile.functions";
import logoAsset from "@/assets/bestqhse-logo.png.asset.json";

type Section = "dashboard" | "plaintes" | "notifications" | "satisfaction" | "profil" | "aide";
const SECTIONS: { id: Section; label: string; icon: typeof User }[] = [
  { id: "dashboard", label: "Dashboard", icon: LayoutDashboard },
  { id: "plaintes", label: "Mes plaintes", icon: MessageSquareWarning },
  { id: "notifications", label: "Notifications", icon: Bell },
  { id: "satisfaction", label: "Satisfaction client", icon: Smile },
  { id: "profil", label: "Mon profil", icon: User },
  { id: "aide", label: "Aide & support", icon: HelpCircle },
];

export const Route = createFileRoute("/_authenticated/client")({
  validateSearch: (s: Record<string, unknown>): { s?: Section } =>
    SECTIONS.some((x) => x.id === s["s"]) ? { s: s["s"] as Section } : {},
  head: () => ({
    meta: [
      { title: "Espace client — LOGIQUALI" },
      { name: "description", content: "Déposez et suivez vos plaintes, répondez aux enquêtes de satisfaction." },
    ],
  }),
  loader: () => getMyProfile(),
  component: ClientSpace,
  errorComponent: () => <p className="p-8 text-sm text-muted-foreground">Impossible de charger votre espace.</p>,
});

type Rec = { id: string; kind: string; reference: string; title: string; status: string; data: Record<string, unknown>; created_at: string; updated_at: string };
const KEY = ["client-records"];
const STATUS_TONE: Record<string, string> = {
  "En attente": "bg-warning/15 text-warning-foreground",
  "En cours": "bg-info/15 text-info",
  "Résolue": "bg-success/15 text-success",
  "Rejetée": "bg-destructive/10 text-destructive",
};

function useClientRecords() {
  return useQuery({
    queryKey: KEY,
    queryFn: async () => {
      const { data, error } = await supabase.from("qhse_records").select("id, kind, reference, title, status, data, created_at, updated_at")
        .in("kind", ["client_complaint", "client_survey", "client_ticket"]).order("created_at", { ascending: false });
      if (error) throw error;
      return (data ?? []) as unknown as Rec[];
    },
  });
}

function useCreate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (v: { kind: string; prefix: string; title: string; status: string; data: Record<string, unknown>; count: number }) => {
      const { data: u } = await supabase.auth.getUser();
      const { error } = await supabase.from("qhse_records").insert({
        company_id: u.user!.id, kind: v.kind, reference: `${v.prefix}-${String(v.count + 1).padStart(3, "0")}`,
        title: v.title, status: v.status, data: v.data as Json,
      });
      if (error) throw error;
    },
    onSuccess: () => { qc.invalidateQueries({ queryKey: KEY }); toast.success("Enregistré"); },
    onError: () => toast.error("L'enregistrement a échoué, réessayez."),
  });
}

function useUpdate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (v: { id: string; status?: string; data: Record<string, unknown> }) => {
      const { error } = await supabase.from("qhse_records").update({ ...(v.status ? { status: v.status } : {}), data: v.data as Json }).eq("id", v.id);
      if (error) throw error;
    },
    onSuccess: () => { qc.invalidateQueries({ queryKey: KEY }); toast.success("Mis à jour"); },
    onError: () => toast.error("Mise à jour impossible."),
  });
}

const fmt = (d: string) => new Date(d).toLocaleDateString("fr-FR");
const card = "rounded-2xl border border-border bg-card p-5";
const inputCls = "h-11 w-full rounded-xl border border-input bg-background px-3.5 text-sm outline-none focus:border-primary";

function ClientSpace() {
  const profile = Route.useLoaderData();
  const { s = "dashboard" } = Route.useSearch();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [mobile, setMobile] = useState(false);
  const { data: recs = [] } = useClientRecords();
  const complaints = recs.filter((r) => r.kind === "client_complaint");
  const unread = complaints.filter((c) => c.data["unread"]).length;
  const name = [profile.first_name, profile.last_name].filter(Boolean).join(" ") || "Client";
  const initials = name.split(/\s+/).map((p) => p[0]).join("").slice(0, 2).toUpperCase();

  const signOut = async () => {
    await qc.cancelQueries(); qc.clear(); await supabase.auth.signOut();
    navigate({ to: "/auth/login", replace: true });
  };

  const nav = (
    <div className="flex h-full flex-col bg-card">
      <div className="flex items-center gap-3 border-b border-border p-4">
        <span className="flex h-10 w-10 items-center justify-center rounded-xl border border-border"><img src={logoAsset.url} alt="LOGIQUALI" className="h-6 w-auto" /></span>
        <div className="flex-1"><p className="font-display font-extrabold text-foreground">LOGIQUALI</p><p className="text-[11px] font-semibold text-muted-foreground">Espace Client</p></div>
        <button className="lg:hidden" onClick={() => setMobile(false)} aria-label="Fermer"><X className="h-5 w-5" /></button>
      </div>
      <nav className="flex-1 space-y-1 p-3">
        {SECTIONS.map((x) => (
          <Link key={x.id} to="/client" search={{ s: x.id }} onClick={() => setMobile(false)}
            className={`flex h-11 items-center gap-3 rounded-xl px-3.5 text-sm font-semibold ${s === x.id ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:bg-secondary hover:text-foreground"}`}>
            <x.icon className="h-[18px] w-[18px]" /><span className="flex-1">{x.label}</span>
            {x.id === "plaintes" && unread > 0 && <span className="rounded-full bg-destructive px-1.5 text-[10px] font-bold text-destructive-foreground">{unread}</span>}
          </Link>
        ))}
      </nav>
      <div className="flex items-center gap-3 border-t border-border p-3">
        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">{initials}</span>
        <div className="min-w-0 flex-1"><p className="truncate text-sm font-bold text-foreground">{name}</p><p className="truncate text-xs text-muted-foreground">{profile.email}</p></div>
        <button onClick={signOut} aria-label="Se déconnecter" className="flex h-9 w-9 items-center justify-center rounded-lg border border-border text-muted-foreground hover:text-destructive"><LogOut className="h-4 w-4" /></button>
      </div>
    </div>
  );

  return (
    <div className="flex h-dvh w-full overflow-hidden bg-background">
      <aside className="hidden w-[260px] shrink-0 border-r border-border lg:block">{nav}</aside>
      {mobile && <div className="fixed inset-0 z-50 lg:hidden"><div className="absolute inset-0 bg-foreground/40" onClick={() => setMobile(false)} /><aside className="absolute inset-y-0 left-0 w-[270px]">{nav}</aside></div>}
      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex h-16 items-center gap-3 border-b border-border bg-card px-4 md:px-6">
          <button onClick={() => setMobile(true)} className="flex h-10 w-10 items-center justify-center rounded-xl border border-border lg:hidden" aria-label="Ouvrir le menu"><Menu className="h-5 w-5" /></button>
          <h1 className="font-display text-lg font-bold text-foreground">{SECTIONS.find((x) => x.id === s)?.label}</h1>
          <Link to="/client" search={{ s: "notifications" }} className="relative ml-auto flex h-10 w-10 items-center justify-center rounded-xl border border-border text-muted-foreground hover:text-primary" aria-label="Notifications">
            <Bell className="h-[18px] w-[18px]" />
            {unread > 0 && <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-destructive-foreground">{unread}</span>}
          </Link>
        </header>
        <main className="flex-1 overflow-y-auto p-4 md:p-8">
          <div className="mx-auto max-w-5xl">
            {s === "dashboard" && <Dashboard name={name} complaints={complaints} />}
            {s === "plaintes" && <Complaints complaints={complaints} />}
            {s === "notifications" && <Notifications complaints={complaints} />}
            {s === "satisfaction" && <Satisfaction surveys={recs.filter((r) => r.kind === "client_survey")} />}
            {s === "profil" && <Profile profile={profile} />}
            {s === "aide" && <Help tickets={recs.filter((r) => r.kind === "client_ticket")} />}
          </div>
        </main>
      </div>
    </div>
  );
}

function Badge({ status }: { status: string }) {
  return <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${STATUS_TONE[status] ?? "bg-secondary text-muted-foreground"}`}>{status}</span>;
}

function Dashboard({ name, complaints }: { name: string; complaints: Rec[] }) {
  const n = (st: string) => complaints.filter((c) => c.status === st).length;
  const rate = complaints.length ? Math.round((n("Résolue") / complaints.length) * 100) : 0;
  const stats = [
    { label: "Total plaintes", v: complaints.length, cls: "text-primary" },
    { label: "En attente", v: n("En attente"), cls: "text-warning-foreground" },
    { label: "En cours", v: n("En cours"), cls: "text-info" },
    { label: "Résolues", v: n("Résolue"), cls: "text-success" },
    { label: "Taux de résolution", v: `${rate} %`, cls: "text-success" },
  ];
  return (
    <div className="space-y-6">
      <div className={card}>
        <p className="text-xs font-bold uppercase tracking-widest text-primary">{new Date().toLocaleDateString("fr-FR", { weekday: "long", day: "numeric", month: "long", year: "numeric" })}</p>
        <h2 className="mt-1 font-display text-2xl font-bold text-foreground">Bonjour {name}</h2>
        <div className="mt-4 flex flex-wrap gap-2">
          <Link to="/client" search={{ s: "plaintes" }} className="inline-flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground"><Plus className="h-4 w-4" /> Déposer une plainte</Link>
          <Link to="/client" search={{ s: "satisfaction" }} className="inline-flex h-10 items-center gap-2 rounded-xl bg-success px-4 text-sm font-semibold text-success-foreground"><Smile className="h-4 w-4" /> Répondre à une enquête</Link>
          <Link to="/client" search={{ s: "aide" }} className="inline-flex h-10 items-center gap-2 rounded-xl border border-border px-4 text-sm font-semibold"><HelpCircle className="h-4 w-4" /> Contacter le support</Link>
        </div>
      </div>
      <div className="grid grid-cols-2 gap-3 md:grid-cols-5">
        {stats.map((x) => <div key={x.label} className={card}><p className={`font-display text-3xl font-bold ${x.cls}`}>{x.v}</p><p className="mt-1 text-xs font-semibold text-muted-foreground">{x.label}</p></div>)}
      </div>
      <div className={card}>
        <h3 className="font-display font-bold text-foreground">Plaintes récentes</h3>
        <ul className="mt-3 divide-y divide-border">
          {complaints.slice(0, 5).map((c) => (
            <li key={c.id} className="flex items-center justify-between gap-3 py-3">
              <div className="min-w-0"><p className="truncate text-sm font-semibold text-foreground">{c.reference} · {c.title}</p><p className="text-xs text-muted-foreground">Déposée le {fmt(c.created_at)}</p></div>
              <Badge status={c.status} />
            </li>
          ))}
          {complaints.length === 0 && <li className="py-3 text-sm text-muted-foreground">Aucune plainte pour l'instant.</li>}
        </ul>
      </div>
    </div>
  );
}

function Complaints({ complaints }: { complaints: Rec[] }) {
  const create = useCreate();
  const update = useUpdate();
  const [open, setOpen] = useState<Rec | null>(null);
  const [form, setForm] = useState(false);
  const [filter, setFilter] = useState("");
  const [f, setF] = useState({ title: "", company: "", category: "Qualité produit", description: "" });
  const list = complaints.filter((c) => !filter || c.status === filter);

  if (open) {
    const c = complaints.find((x) => x.id === open.id) ?? open;
    const hist = Array.isArray(c.data["history"]) ? (c.data["history"] as { at: string; text: string }[]) : [];
    return (
      <div className={card}>
        <button onClick={() => setOpen(null)} className="mb-4 text-sm font-semibold text-primary hover:underline">← Retour à mes plaintes</button>
        <p className="font-mono text-xs font-bold text-primary">{c.reference}</p>
        <h2 className="mt-1 font-display text-2xl font-bold text-foreground">{c.title}</h2>
        <div className="mt-2"><Badge status={c.status} /></div>
        <dl className="mt-5 grid gap-3 text-sm sm:grid-cols-2">
          <div><dt className="font-semibold text-muted-foreground">Entreprise concernée</dt><dd>{String(c.data["company"] ?? "—")}</dd></div>
          <div><dt className="font-semibold text-muted-foreground">Catégorie</dt><dd>{String(c.data["category"] ?? "—")}</dd></div>
          <div className="sm:col-span-2"><dt className="font-semibold text-muted-foreground">Description</dt><dd className="whitespace-pre-wrap">{String(c.data["description"] ?? "")}</dd></div>
        </dl>
        <h3 className="mt-6 font-display font-bold">Suivi du traitement</h3>
        <ul className="mt-2 space-y-2 border-l-2 border-primary/20 pl-4 text-sm">
          {hist.map((h, i) => <li key={i}><span className="text-xs text-muted-foreground">{fmt(h.at)}</span> — {h.text}</li>)}
        </ul>
        <div className="mt-6 flex flex-wrap gap-2">
          {c.status === "Résolue" && !c.data["rating"] && [1, 2, 3, 4, 5].map((n) => (
            <button key={n} onClick={() => update.mutate({ id: c.id, data: { ...c.data, rating: n } })} className="inline-flex h-10 items-center gap-1 rounded-xl bg-warning px-3 text-sm font-semibold text-warning-foreground"><Star className="h-4 w-4" />{n}</button>
          ))}
          {c.data["rating"] ? <p className="text-sm font-semibold text-success">Votre note : {String(c.data["rating"])}/5 — merci !</p> : null}
          {c.status === "En attente" && (
            <button onClick={() => { if (confirm("Retirer cette plainte ?")) update.mutate({ id: c.id, status: "Rejetée", data: { ...c.data, history: [...hist, { at: new Date().toISOString(), text: "Plainte retirée par le client" }] } }); }}
              className="inline-flex h-10 items-center rounded-xl bg-destructive/10 px-4 text-sm font-semibold text-destructive">Retirer la plainte</button>
          )}
        </div>
      </div>
    );
  }

  if (form) {
    return (
      <form className={`${card} space-y-4`} onSubmit={async (e) => {
        e.preventDefault();
        if (!f.title.trim() || !f.description.trim()) { toast.error("Objet et description obligatoires."); return; }
        await create.mutateAsync({ kind: "client_complaint", prefix: "PLT", title: f.title, status: "En attente", count: complaints.length,
          data: { company: f.company, category: f.category, description: f.description, history: [{ at: new Date().toISOString(), text: "Plainte déposée" }] } });
        setForm(false); setF({ title: "", company: "", category: "Qualité produit", description: "" });
      }}>
        <button type="button" onClick={() => setForm(false)} className="text-sm font-semibold text-primary hover:underline">← Retour</button>
        <h2 className="font-display text-xl font-bold">Déposer une plainte</h2>
        <input className={inputCls} placeholder="Objet *" value={f.title} onChange={(e) => setF({ ...f, title: e.target.value })} />
        <input className={inputCls} placeholder="Entreprise concernée" value={f.company} onChange={(e) => setF({ ...f, company: e.target.value })} />
        <select className={inputCls} value={f.category} onChange={(e) => setF({ ...f, category: e.target.value })}>
          {["Qualité produit", "Délai de livraison", "Service client", "Facturation", "Sécurité", "Autre"].map((x) => <option key={x}>{x}</option>)}
        </select>
        <textarea className={`${inputCls} h-32 py-3`} placeholder="Description *" value={f.description} onChange={(e) => setF({ ...f, description: e.target.value })} />
        <button disabled={create.isPending} className="inline-flex h-11 items-center rounded-xl bg-primary px-5 text-sm font-semibold text-primary-foreground">Envoyer la plainte</button>
      </form>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap gap-2">
        <select aria-label="Filtrer par statut" value={filter} onChange={(e) => setFilter(e.target.value)} className={`${inputCls} sm:w-60`}>
          <option value="">Tous les statuts ({complaints.length})</option>
          {Object.keys(STATUS_TONE).map((st) => <option key={st} value={st}>{st} ({complaints.filter((c) => c.status === st).length})</option>)}
        </select>
        <button onClick={() => setForm(true)} className="ml-auto inline-flex h-11 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground"><Plus className="h-4 w-4" /> Nouvelle plainte</button>
      </div>
      <ul className="space-y-2">
        {list.map((c) => (
          <li key={c.id}>
            <button onClick={() => { setOpen(c); if (c.data["unread"]) update.mutate({ id: c.id, data: { ...c.data, unread: false } }); }} className={`${card} flex w-full items-center justify-between gap-3 text-left hover:border-primary`}>
              <div className="min-w-0">
                <p className="text-xs font-bold text-primary">{c.reference} · {String(c.data["category"] ?? "")}{c.data["unread"] ? " · Nouveau" : ""}</p>
                <p className="truncate font-semibold text-foreground">{c.title}</p>
                <p className="text-xs text-muted-foreground">{String(c.data["company"] ?? "")} · {fmt(c.created_at)}</p>
              </div>
              <Badge status={c.status} />
            </button>
          </li>
        ))}
        {list.length === 0 && <li className="text-sm text-muted-foreground">Aucune plainte.</li>}
      </ul>
    </div>
  );
}

function Notifications({ complaints }: { complaints: Rec[] }) {
  const items = complaints.flatMap((c) => (Array.isArray(c.data["history"]) ? (c.data["history"] as { at: string; text: string }[]) : []).map((h) => ({ c, ...h })))
    .sort((a, b) => b.at.localeCompare(a.at));
  return (
    <ul className="space-y-2">
      {items.map((n, i) => (
        <li key={i} className={`${card} flex gap-3`}>
          {n.text.includes("résolue") ? <CheckCircle2 className="h-5 w-5 shrink-0 text-success" /> : <Clock className="h-5 w-5 shrink-0 text-info" />}
          <div><p className="text-sm font-semibold text-foreground">{n.c.reference} · {n.text}</p><p className="text-xs text-muted-foreground">{fmt(n.at)} · {n.c.title}</p></div>
        </li>
      ))}
      {items.length === 0 && <li className="text-sm text-muted-foreground">Aucune notification.</li>}
    </ul>
  );
}

function Satisfaction({ surveys }: { surveys: Rec[] }) {
  const update = useUpdate();
  const [scores, setScores] = useState<Record<string, number>>({});
  return (
    <ul className="space-y-3">
      {surveys.map((s) => (
        <li key={s.id} className={card}>
          <div className="flex items-center justify-between gap-2"><p className="font-semibold text-foreground">{s.title}</p><Badge status={s.status === "Répondue" ? "Résolue" : "En attente"} /></div>
          <p className="text-xs text-muted-foreground">{String(s.data["company"] ?? "")} · reçue le {fmt(s.created_at)}</p>
          {s.status === "Répondue" ? <p className="mt-2 text-sm font-semibold text-success">Votre note : {String(s.data["score"])}/5</p> : (
            <div className="mt-3 flex flex-wrap items-center gap-2">
              {[1, 2, 3, 4, 5].map((n) => (
                <button key={n} onClick={() => setScores({ ...scores, [s.id]: n })} className={`inline-flex h-10 w-10 items-center justify-center rounded-xl text-sm font-bold ${scores[s.id] === n ? "bg-warning text-warning-foreground" : "border border-border"}`}>{n}</button>
              ))}
              <button disabled={!scores[s.id]} onClick={() => update.mutate({ id: s.id, status: "Répondue", data: { ...s.data, score: scores[s.id] } })} className="inline-flex h-10 items-center rounded-xl bg-success px-4 text-sm font-semibold text-success-foreground disabled:opacity-50">Envoyer</button>
            </div>
          )}
        </li>
      ))}
      {surveys.length === 0 && <li className="text-sm text-muted-foreground">Aucune enquête reçue.</li>}
    </ul>
  );
}

function Profile({ profile }: { profile: Awaited<ReturnType<typeof getMyProfile>> }) {
  const [f, setF] = useState({ first_name: profile.first_name, last_name: profile.last_name, phone: profile.phone ?? "" });
  const [busy, setBusy] = useState(false);
  return (
    <form className={`${card} max-w-xl space-y-4`} onSubmit={async (e) => {
      e.preventDefault(); setBusy(true);
      const { error } = await supabase.from("profiles").update(f).eq("id", profile.id);
      setBusy(false);
      if (error) toast.error("Mise à jour impossible."); else toast.success("Profil mis à jour");
    }}>
      <p className="text-sm text-muted-foreground">E-mail : <b className="text-foreground">{profile.email}</b></p>
      <input className={inputCls} placeholder="Prénom" value={f.first_name} onChange={(e) => setF({ ...f, first_name: e.target.value })} />
      <input className={inputCls} placeholder="Nom" value={f.last_name} onChange={(e) => setF({ ...f, last_name: e.target.value })} />
      <input className={inputCls} placeholder="Téléphone" value={f.phone} onChange={(e) => setF({ ...f, phone: e.target.value })} />
      <button disabled={busy} className="inline-flex h-11 items-center rounded-xl bg-primary px-5 text-sm font-semibold text-primary-foreground">Enregistrer</button>
    </form>
  );
}

function Help({ tickets }: { tickets: Rec[] }) {
  const create = useCreate();
  const [msg, setMsg] = useState("");
  const faq = [
    ["Comment déposer une plainte ?", "Menu « Mes plaintes » puis « Nouvelle plainte »."],
    ["Quel est le délai de traitement ?", "L'entreprise concernée répond en général sous 5 jours ouvrés."],
    ["Puis-je retirer une plainte ?", "Oui, tant qu'elle est « En attente », depuis sa fiche."],
  ];
  return (
    <div className="space-y-6">
      <div className={card}>
        <h3 className="font-display font-bold">Questions fréquentes</h3>
        {faq.map(([q, a]) => <details key={q} className="mt-3"><summary className="cursor-pointer text-sm font-semibold">{q}</summary><p className="mt-1 text-sm text-muted-foreground">{a}</p></details>)}
      </div>
      <form className={`${card} space-y-3`} onSubmit={async (e) => {
        e.preventDefault(); if (!msg.trim()) return;
        await create.mutateAsync({ kind: "client_ticket", prefix: "SUP", title: msg.slice(0, 80), status: "En attente", data: { message: msg }, count: tickets.length });
        setMsg("");
      }}>
        <h3 className="font-display font-bold">Contacter le support</h3>
        <textarea className={`${inputCls} h-28 py-3`} placeholder="Votre message" value={msg} onChange={(e) => setMsg(e.target.value)} />
        <button disabled={create.isPending} className="inline-flex h-11 items-center rounded-xl bg-info px-5 text-sm font-semibold text-info-foreground">Envoyer</button>
        {tickets.map((t) => <p key={t.id} className="text-xs text-muted-foreground">{t.reference} · {fmt(t.created_at)} · {t.status}</p>)}
      </form>
    </div>
  );
}
