import { createFileRoute, Link } from "@tanstack/react-router";
import { Mail, MailCheck } from "lucide-react";
import { useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { LqInput } from "@/components/auth/LqInput";
import { LqButton } from "@/components/lq/LqButton";
import { authErrorMessage } from "@/lib/auth-errors";

export const Route = createFileRoute("/auth/forgot-password")({
  head: () => ({
    meta: [
      { title: "Mot de passe oublié — LOGIQUALI" },
      { name: "description", content: "Recevez un lien pour réinitialiser votre mot de passe." },
      { property: "og:title", content: "Mot de passe oublié — LOGIQUALI" },
      {
        property: "og:description",
        content: "Recevez un lien pour réinitialiser votre mot de passe.",
      },
    ],
  }),
  component: ForgotPasswordPage,
});

function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [error, setError] = useState("");
  const [sent, setSent] = useState(false);
  const [busy, setBusy] = useState(false);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email.includes("@")) {
      setError("Adresse e-mail invalide.");
      return;
    }
    setError("");
    setBusy(true);
    const { error: authError } = await supabase.auth.resetPasswordForEmail(email, {
      redirectTo: `${window.location.origin}/auth/reset-password`,
    });
    setBusy(false);
    if (authError) {
      setError(authErrorMessage(authError));
      return;
    }
    setSent(true);
  };

  if (sent) {
    return (
      <AuthLayout
        title="E-mail envoyé"
        subtitle="Suivez le lien reçu pour choisir un nouveau mot de passe."
      >
        <div className="rounded-3xl border border-border bg-card p-8 text-center">
          <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-soft text-primary">
            <MailCheck className="h-8 w-8" />
          </span>
          <h2 className="mt-5 font-display text-lg font-bold text-foreground">
            Vérifiez votre boîte de réception
          </h2>
          <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
            Un lien de réinitialisation vous a été envoyé à {email}. Il est valable une heure.
          </p>
          <LqButton to="/auth/login" variant="ghost" className="mt-6">
            Retour à la connexion
          </LqButton>
        </div>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout
      title="Mot de passe oublié"
      subtitle="Entrez votre e-mail : nous vous enverrons un lien de réinitialisation."
    >
      <form onSubmit={submit} className="space-y-5">
        <LqInput
          label="Adresse e-mail"
          icon={Mail}
          type="email"
          placeholder="vous@entreprise.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        {error && (
          <p className="rounded-xl bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive">
            {error}
          </p>
        )}
        <LqButton type="submit" className="w-full" size="lg" withArrow>
          {busy ? "Envoi…" : "Envoyer le lien"}
        </LqButton>
      </form>
      <p className="mt-8 text-center text-sm text-muted-foreground">
        <Link to="/auth/login" className="font-bold text-primary hover:underline">
          Retour à la connexion
        </Link>
      </p>
    </AuthLayout>
  );
}
