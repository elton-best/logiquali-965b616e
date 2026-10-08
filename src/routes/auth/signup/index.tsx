import { createFileRoute, Link } from "@tanstack/react-router";
import { Building2, Clock3, User, Zap } from "lucide-react";
import { AuthLayout } from "@/components/auth/AuthLayout";

export const Route = createFileRoute("/auth/signup/")({
  head: () => ({
    meta: [
      { title: "Créer un compte — LOGIQUALI" },
      { name: "description", content: "Choisissez votre profil : entreprise ou particulier." },
      { property: "og:title", content: "Créer un compte — LOGIQUALI" },
      { property: "og:description", content: "Choisissez votre profil : entreprise ou particulier." },
    ],
  }),
  component: SignupChoicePage,
});

function SignupChoicePage() {
  return (
    <AuthLayout
      title="Créer votre compte"
      subtitle="Choisissez le profil qui correspond à votre situation."
    >
      <div className="space-y-4">
        <Link
          to="/auth/signup/company"
          className="group block rounded-3xl border-[1.5px] border-border bg-card p-6 transition-all hover:-translate-y-0.5 hover:border-primary hover:shadow-xl hover:shadow-primary/10"
        >
          <div className="flex items-start justify-between">
            <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg shadow-primary/30">
              <Building2 className="h-6 w-6" />
            </span>
            <span className="inline-flex items-center gap-1.5 rounded-full bg-primary-soft px-3 py-1 text-[11px] font-bold text-primary">
              <Clock3 className="h-3 w-3" />
              Activation 24-48h
            </span>
          </div>
          <h2 className="mt-5 font-display text-lg font-bold text-foreground">Entreprise</h2>
          <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
            Cockpit QHSE complet : documents, audits, non-conformités, multi-sites. Vérification
            KYC de votre dossier sous 24 à 48h.
          </p>
        </Link>

        <Link
          to="/auth/signup/individual"
          className="group block rounded-3xl border-[1.5px] border-border bg-card p-6 transition-all hover:-translate-y-0.5 hover:border-primary hover:shadow-xl hover:shadow-primary/10"
        >
          <div className="flex items-start justify-between">
            <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-soft text-primary">
              <User className="h-6 w-6" />
            </span>
            <span className="inline-flex items-center gap-1.5 rounded-full bg-primary-soft px-3 py-1 text-[11px] font-bold text-primary">
              <Zap className="h-3 w-3" />
              Activation instantanée
            </span>
          </div>
          <h2 className="mt-5 font-display text-lg font-bold text-foreground">Particulier</h2>
          <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">
            Déposez des réclamations et répondez aux enquêtes de satisfaction des entreprises
            certifiées. Compte actif immédiatement.
          </p>
        </Link>
      </div>

      <p className="mt-8 text-center text-sm text-muted-foreground">
        Déjà inscrit ?{" "}
        <Link to="/auth/login" className="font-bold text-primary hover:underline">
          Se connecter
        </Link>
      </p>
    </AuthLayout>
  );
}
