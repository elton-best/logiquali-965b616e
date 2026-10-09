import { createFileRoute, Link } from "@tanstack/react-router";
import { KeyRound, Lock, ShieldAlert } from "lucide-react";
import { useState } from "react";
import { backendApi } from "@/integrations/backend/client";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { LqInput } from "@/components/auth/LqInput";
import { LqButton } from "@/components/lq/LqButton";
import { authErrorMessage } from "@/lib/auth-errors";

export const Route = createFileRoute("/auth/reset-password")({
  validateSearch: (search: Record<string, unknown>): { token?: string; email?: string } => ({
    token: typeof search.token === "string" ? search.token : undefined,
    email: typeof search.email === "string" ? search.email : undefined,
  }),
  head: () => ({
    meta: [
      { title: "Nouveau mot de passe — LOGIQUALI" },
      { name: "description", content: "Définissez un nouveau mot de passe pour votre compte." },
    ],
  }),
  component: ResetPasswordPage,
});

function ResetPasswordPage() {
  const { token, email } = Route.useSearch();
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(false);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token || !email) {
      setError("Le lien de réinitialisation est incomplet ou expiré.");
      return;
    }
    if (password.length < 8) {
      setError("Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.");
      return;
    }
    if (password !== confirm) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }
    setError("");
    setBusy(true);
    try {
      await backendApi.auth.resetPassword({
        token,
        email,
        password,
        password_confirmation: confirm,
      });
      setDone(true);
    } catch (authError) {
      setError(authErrorMessage(authError));
    } finally {
      setBusy(false);
    }
  };

  if (!token || !email) {
    return (
      <AuthLayout title="Lien expiré" subtitle="Ce lien de réinitialisation n'est plus valable.">
        <div className="rounded-3xl border border-border bg-card p-8 text-center">
          <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-destructive/10 text-destructive">
            <ShieldAlert className="h-8 w-8" />
          </span>
          <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
            Demandez un nouveau lien pour continuer.
          </p>
          <LqButton to="/auth/forgot-password" className="mt-6" withArrow>
            Demander un nouveau lien
          </LqButton>
        </div>
      </AuthLayout>
    );
  }

  if (done) {
    return (
      <AuthLayout title="Mot de passe mis à jour" subtitle="Votre nouveau mot de passe est actif.">
        <div className="rounded-3xl border border-border bg-card p-8 text-center">
          <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-soft text-primary">
            <KeyRound className="h-8 w-8" />
          </span>
          <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
            Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
          </p>
          <LqButton to="/auth/login" className="mt-6" withArrow>
            Se connecter
          </LqButton>
        </div>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout
      title="Nouveau mot de passe"
      subtitle="Choisissez un mot de passe d'au moins 8 caractères."
    >
      <form onSubmit={submit} className="space-y-5">
        <LqInput
          label="Nouveau mot de passe"
          icon={Lock}
          type="password"
          placeholder="8 caractères minimum"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          minLength={8}
          required
        />
        <LqInput
          label="Confirmer le mot de passe"
          icon={Lock}
          type="password"
          placeholder="••••••••"
          value={confirm}
          onChange={(e) => setConfirm(e.target.value)}
          error={error}
          required
        />
        {error && (
          <p className="rounded-xl bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive">
            {error}
          </p>
        )}
        <LqButton type="submit" className="w-full" size="lg" withArrow>
          {busy ? "Enregistrement…" : "Enregistrer le mot de passe"}
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
