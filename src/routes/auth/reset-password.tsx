import { createFileRoute, Link } from "@tanstack/react-router";
import { KeyRound, Lock, ShieldAlert } from "lucide-react";
import { useEffect, useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { LqInput } from "@/components/auth/LqInput";
import { LqButton } from "@/components/lq/LqButton";
import { authErrorMessage } from "@/lib/auth-errors";

export const Route = createFileRoute("/auth/reset-password")({
  head: () => ({
    meta: [
      { title: "Nouveau mot de passe — LOGIQUALI" },
      { name: "description", content: "Définissez un nouveau mot de passe pour votre compte." },
      { property: "og:title", content: "Nouveau mot de passe — LOGIQUALI" },
      {
        property: "og:description",
        content: "Définissez un nouveau mot de passe pour votre compte.",
      },
    ],
  }),
  component: ResetPasswordPage,
});

function ResetPasswordPage() {
  // "checking" -> "form" (recovery session detected) | "invalid" | "done"
  const [status, setStatus] = useState<"checking" | "form" | "invalid" | "done">("checking");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let alive = true;
    supabase.auth.getUser().then(({ data }) => {
      if (alive) setStatus(data.user ? "form" : "invalid");
    });
    return () => {
      alive = false;
    };
  }, []);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (password.length < 8) {
      setError("Le mot de passe doit contenir au moins 8 caractères.");
      return;
    }
    if (password !== confirm) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }
    setError("");
    setBusy(true);
    // Recovery session: current password is not required here.
    const { error: authError } = await supabase.auth.updateUser({ password });
    setBusy(false);
    if (authError) {
      setError(authErrorMessage(authError));
      return;
    }
    setStatus("done");
  };

  if (status === "checking") {
    return (
      <AuthLayout title="Vérification du lien" subtitle="Un instant…">
        <p className="text-sm text-muted-foreground">Nous vérifions votre lien de réinitialisation.</p>
      </AuthLayout>
    );
  }

  if (status === "invalid") {
    return (
      <AuthLayout
        title="Lien expiré"
        subtitle="Ce lien de réinitialisation n'est plus valable."
      >
        <div className="rounded-3xl border border-border bg-card p-8 text-center">
          <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-destructive/10 text-destructive">
            <ShieldAlert className="h-8 w-8" />
          </span>
          <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
            Les liens de réinitialisation ne sont valables qu'une heure. Demandez un nouveau
            lien pour continuer.
          </p>
          <LqButton to="/auth/forgot-password" className="mt-6" withArrow>
            Demander un nouveau lien
          </LqButton>
        </div>
      </AuthLayout>
    );
  }

  if (status === "done") {
    return (
      <AuthLayout
        title="Mot de passe mis à jour"
        subtitle="Votre nouveau mot de passe est actif."
      >
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
        <LqButton type="submit" className="w-full" size="lg" withArrow>
          {busy ? "Enregistrement…" : "Enregistrer le mot de passe"}
        </LqButton>
      </form>
    </AuthLayout>
  );
}
