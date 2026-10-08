import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useQueryClient } from "@tanstack/react-query";
import {
  ArrowLeft,
  Building2,
  Clock3,
  LogOut,
  ShieldCheck,
  Sparkles,
  XCircle,
} from "lucide-react";
import logoAsset from "@/assets/bestqhse-logo.png.asset.json";
import { LqButton } from "@/components/lq/LqButton";
import { getMyProfile, type Profile } from "@/lib/profile.functions";

export const Route = createFileRoute("/_authenticated/app")({
  loader: () => getMyProfile(),
  component: SpaceHome,
  errorComponent: SpaceError,
  notFoundComponent: SpaceError,
});

function SpaceError() {
  return (
    <div className="flex min-h-dvh items-center justify-center bg-background px-4">
      <div className="max-w-md text-center">
        <h1 className="font-display text-xl font-bold text-foreground">
          Impossible de charger votre espace
        </h1>
        <p className="mt-2 text-sm text-muted-foreground">
          Veuillez réessayer ou revenir à l'accueil.
        </p>
        <LqButton to="/" variant="ghost" className="mt-6">
          Retour à l'accueil
        </LqButton>
      </div>
    </div>
  );
}

function StatusBanner({ profile }: { profile: Profile }) {
  if (profile.account_type === "company" && profile.status === "pending") {
    return (
      <div className="flex items-start gap-4 rounded-3xl border-[1.5px] border-primary/30 bg-primary-soft/60 p-6">
        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
          <Clock3 className="h-5 w-5" />
        </span>
        <div>
          <p className="font-display text-sm font-bold text-foreground">
            Dossier en cours de validation
          </p>
          <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
            Nos équipes examinent votre dossier (RCCM, IFU, pièce d'identité) sous 24 à 48h.
            Vous recevrez un e-mail dès l'activation complète de votre espace.
          </p>
        </div>
      </div>
    );
  }
  if (profile.status === "rejected") {
    return (
      <div className="flex items-start gap-4 rounded-3xl border-[1.5px] border-destructive/30 bg-destructive/5 p-6">
        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-destructive text-destructive-foreground">
          <XCircle className="h-5 w-5" />
        </span>
        <div>
          <p className="font-display text-sm font-bold text-foreground">Dossier refusé</p>
          <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
            Votre dossier n'a pas pu être validé. Contactez notre support pour en connaître
            les raisons.
          </p>
        </div>
      </div>
    );
  }
  return (
    <div className="flex items-start gap-4 rounded-3xl border-[1.5px] border-primary/30 bg-primary-soft/60 p-6">
      <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
        <ShieldCheck className="h-5 w-5" />
      </span>
      <div>
        <p className="font-display text-sm font-bold text-foreground">
          Espace {profile.account_type === "company" ? "entreprise" : "particulier"} actif
        </p>
        <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
          Votre compte est vérifié. Les modules QHSE (documents, audits, non-conformités)
          seront disponibles prochainement.
        </p>
      </div>
    </div>
  );
}

function SpaceHome() {
  const profile = Route.useLoaderData();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const signOut = async () => {
    await queryClient.cancelQueries();
    queryClient.clear();
    await supabaseSignOut();
    navigate({ to: "/auth/login", replace: true });
  };

  const fullName = [profile.first_name, profile.last_name].filter(Boolean).join(" ");

  return (
    <div className="min-h-dvh bg-background">
      <header className="border-b border-border bg-card">
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3">
          <Link to="/" className="flex items-center gap-2.5">
            <span className="flex h-9 items-center rounded-lg bg-card px-1.5">
              <img src={logoAsset.url} alt="LOGIQUALI — BestQHSE" className="h-6 w-auto" />
            </span>
            <span className="font-display text-lg font-bold tracking-tight text-foreground">
              LOGIQUALI
            </span>
          </Link>
          <button
            onClick={signOut}
            className="inline-flex items-center gap-2 rounded-xl border border-input bg-background px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-accent"
          >
            <LogOut className="h-4 w-4" />
            Se déconnecter
          </button>
        </div>
      </header>

      <main className="mx-auto max-w-5xl px-4 py-12">
        <Link
          to="/"
          className="inline-flex items-center gap-2 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
        >
          <ArrowLeft className="h-4 w-4" />
          Retour au site
        </Link>

        <h1 className="mt-4 font-display text-3xl font-extrabold tracking-tight text-foreground">
          {fullName ? `Bonjour ${fullName}` : "Bonjour"}
        </h1>
        <p className="mt-2 text-sm text-muted-foreground">
          {profile.email}
          {profile.company_name ? ` · ${profile.company_name}` : ""}
        </p>

        <div className="mt-8">
          <StatusBanner profile={profile} />
        </div>

        <div className="mt-6 grid gap-4 sm:grid-cols-2">
          <div className="rounded-3xl border border-border bg-card p-6">
            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-soft text-primary">
              {profile.account_type === "company" ? (
                <Building2 className="h-5 w-5" />
              ) : (
                <Sparkles className="h-5 w-5" />
              )}
            </span>
            <p className="mt-4 font-display text-sm font-bold text-foreground">
              {profile.account_type === "company" ? "Compte entreprise" : "Compte particulier"}
            </p>
            <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
              {profile.account_type === "company"
                ? `RCCM : ${profile.company_rccm || "—"} · IFU : ${profile.company_ifu || "—"}`
                : "Déposez des réclamations et répondez aux enquêtes des entreprises certifiées."}
            </p>
          </div>
          <div className="rounded-3xl border border-border bg-card p-6">
            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-soft text-primary">
              <ShieldCheck className="h-5 w-5" />
            </span>
            <p className="mt-4 font-display text-sm font-bold text-foreground">
              Vos modules QHSE
            </p>
            <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
              Documents, audits, non-conformités, enquêtes… les modules de votre offre
              arrivent dans votre espace.
            </p>
          </div>
        </div>
      </main>
    </div>
  );
}

// Imported last to keep the helper below the component tree; simple alias.
import { supabase as supabaseClient } from "@/integrations/supabase/client";
function supabaseSignOut() {
  return supabaseClient.auth.signOut();
}
