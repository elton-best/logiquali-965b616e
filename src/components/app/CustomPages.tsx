import { getRouteApi, Link, useRouter } from "@tanstack/react-router";
import { Check, Minus, CreditCard, Building2 } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { supabase } from "@/integrations/supabase/client";
import { useRecords } from "@/hooks/use-records";

const appRoute = getRouteApi("/_authenticated/app");

function Header({ title, desc }: { title: string; desc: string }) {
  return (
    <div>
      <Link to="/app" className="text-xs font-bold uppercase tracking-[0.16em] text-primary">Tableau de bord</Link>
      <h1 className="mt-1 font-display text-2xl font-extrabold text-foreground md:text-3xl">{title}</h1>
      <p className="mt-1 text-sm text-muted-foreground">{desc}</p>
    </div>
  );
}

export function CompanyPage() {
  const profile = appRoute.useLoaderData();
  const router = useRouter();
  const { data: records = [] } = useRecords();
  const [form, setForm] = useState({
    company_name: profile.company_name ?? "",
    company_rccm: profile.company_rccm ?? "",
    company_ifu: profile.company_ifu ?? "",
    company_address: profile.company_address ?? "",
    phone: profile.phone ?? "",
    first_name: profile.first_name ?? "",
    last_name: profile.last_name ?? "",
  });
  const [busy, setBusy] = useState(false);

  const save = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    const { error } = await supabase.from("profiles").update(form).eq("id", profile.id);
    setBusy(false);
    if (error) { toast.error("Enregistrement impossible."); return; }
    toast.success("Fiche entreprise mise à jour");
    router.invalidate();
  };

  const fields: [keyof typeof form, string][] = [
    ["company_name", "Raison sociale"], ["company_rccm", "N° RCCM"], ["company_ifu", "N° IFU"],
    ["company_address", "Adresse du siège"], ["phone", "Téléphone"], ["first_name", "Prénom de l'administrateur"], ["last_name", "Nom de l'administrateur"],
  ];
  const stats = [
    ["Sites", records.filter((r) => r.kind === "site").length],
    ["Collaborateurs", records.filter((r) => r.kind === "collaborator").length],
    ["Processus", records.filter((r) => r.kind === "process").length],
    ["Certifications", records.filter((r) => r.kind === "certification" && r.status === "Obtenue").length],
  ] as const;

  return (
    <div className="mx-auto max-w-5xl space-y-6 p-4 md:p-8">
      <Header title="Fiche entreprise" desc="Informations légales et administrateur de l'entreprise." />
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
              <input value={form[k]} onChange={(e) => setForm({ ...form, [k]: e.target.value })} className="h-11 w-full rounded-xl border border-input bg-background px-3.5 text-sm outline-none focus:border-primary" />
            </label>
          ))}
        </div>
        <button disabled={busy} className="mt-6 h-11 rounded-xl bg-primary px-6 text-sm font-semibold text-primary-foreground hover:bg-primary-dark disabled:opacity-60">
          {busy ? "Enregistrement…" : "Enregistrer"}
        </button>
      </form>
    </div>
  );
}

const PERMS = ["Documents", "Processus", "Non-conformités", "Actions", "Audits", "Indicateurs", "Sites & utilisateurs", "Abonnement"];
const ROLES: { name: string; desc: string; rights: ("full" | "read" | "none")[] }[] = [
  { name: "Administrateur", desc: "Gère l'entreprise, les sites, les utilisateurs et les droits.", rights: ["full", "full", "full", "full", "full", "full", "full", "full"] },
  { name: "Responsable de site", desc: "Pilote les activités de son ou ses sites.", rights: ["full", "full", "full", "full", "full", "full", "read", "none"] },
  { name: "Collaborateur", desc: "Consulte et contribue selon ses affectations.", rights: ["read", "read", "full", "full", "read", "read", "none", "none"] },
  { name: "Auditeur", desc: "Accès en lecture et saisie des constats d'audit.", rights: ["read", "read", "read", "read", "full", "read", "none", "none"] },
];

export function RolesPage() {
  const { data: records = [] } = useRecords();
  return (
    <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
      <Header title="Rôles & permissions" desc="Matrice des droits par rôle. Les collaborateurs héritent des droits de leur rôle." />
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
                  <p className="text-xs text-muted-foreground">{records.filter((c) => c.kind === "collaborator" && c.data["role"] === r.name).length} collaborateur(s)</p>
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
      <div className="grid gap-3 md:grid-cols-2">
        {ROLES.map((r) => (
          <div key={r.name} className="rounded-2xl border border-border bg-card p-5">
            <p className="font-display font-bold text-foreground">{r.name}</p>
            <p className="mt-1 text-sm text-muted-foreground">{r.desc}</p>
          </div>
        ))}
      </div>
      <Link to="/app/$section" params={{ section: "collaborateurs" }} className="inline-flex h-11 items-center rounded-xl border border-primary px-5 text-sm font-semibold text-primary hover:bg-primary-soft">
        Gérer les collaborateurs
      </Link>
    </div>
  );
}

export function SubscriptionPage() {
  const { data: records = [] } = useRecords();
  const norms = Array.from(new Set(records.filter((r) => r.kind === "certification").map((r) => String(r.data["norm"] || "")).filter(Boolean)));
  const users = records.filter((r) => r.kind === "collaborator").length;
  const sites = records.filter((r) => r.kind === "site").length;
  return (
    <div className="mx-auto max-w-5xl space-y-6 p-4 md:p-8">
      <Header title="Abonnement" desc="Votre offre, vos normes et votre consommation." />
      <div className="rounded-3xl border-[1.5px] border-primary bg-card p-6 shadow-xl shadow-primary/10">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex items-center gap-3">
            <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-primary-foreground"><CreditCard className="h-6 w-6" /></span>
            <div>
              <p className="text-xs font-bold uppercase tracking-wider text-primary">Offre actuelle</p>
              <p className="font-display text-xl font-extrabold text-foreground">Essai gratuit — Pack Intégré</p>
            </div>
          </div>
          <span className="rounded-full bg-primary-soft px-3 py-1 text-xs font-bold text-primary">Actif</span>
        </div>
        <div className="mt-6 grid gap-4 sm:grid-cols-3">
          {[["Utilisateurs", users, 10], ["Sites", sites, 3], ["Normes", norms.length, 3]].map(([l, v, max]) => (
            <div key={String(l)}>
              <div className="flex justify-between text-sm"><span className="font-semibold text-foreground">{l}</span><span className="text-muted-foreground">{v} / {max}</span></div>
              <div className="mt-1.5 h-2 rounded-full bg-secondary"><div className="h-2 rounded-full bg-primary" style={{ width: `${Math.min(100, (Number(v) / Number(max)) * 100)}%` }} /></div>
            </div>
          ))}
        </div>
      </div>
      <div className="grid gap-4 md:grid-cols-3">
        {[["ISO 9001", "49 000 FCFA / mois"], ["Pack Intégré", "89 000 FCFA / mois"], ["Enterprise", "Sur devis"]].map(([n, p]) => (
          <div key={n} className="rounded-2xl border border-border bg-card p-5">
            <p className="font-display font-bold text-foreground">{n}</p>
            <p className="mt-1 text-sm text-muted-foreground">{p}</p>
            <button onClick={() => toast.info("Notre équipe vous contactera pour finaliser le changement d'offre.")} className="mt-4 h-10 w-full rounded-xl border border-border text-sm font-semibold hover:border-primary hover:text-primary">Choisir</button>
          </div>
        ))}
      </div>
    </div>
  );
}
