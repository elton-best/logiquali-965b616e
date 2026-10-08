import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { Lock, Mail } from "lucide-react";
import { useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { LqInput } from "@/components/auth/LqInput";
import { LqButton } from "@/components/lq/LqButton";
import { authErrorMessage } from "@/lib/auth-errors";

export const Route = createFileRoute("/auth/login")({
  validateSearch: (search: Record<string, unknown>) => ({
    redirect: typeof search.redirect === "string" ? search.redirect : undefined,
  }),
  head: () => ({
    meta: [
      { title: "Connexion — LOGIQUALI" },
      { name: "description", content: "Connectez-vous à votre espace LOGIQUALI." },
      { property: "og:title", content: "Connexion — LOGIQUALI" },
      { property: "og:description", content: "Connectez-vous à votre espace LOGIQUALI." },
    ],
  }),
  component: LoginPage,
});

function LoginPage() {
  const navigate = useNavigate();
  const search = Route.useSearch();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email.includes("@")) {
      setError("Adresse e-mail invalide.");
      return;
    }
    setError("");
    setBusy(true);
    const { error: authError } = await supabase.auth.signInWithPassword({ email, password });
    setBusy(false);
    if (authError) {
      setError(authErrorMessage(authError));
      return;
    }
    const target =
      typeof search.redirect === "string" &&
      search.redirect.startsWith("/") &&
      !search.redirect.startsWith("//")
        ? search.redirect
        : "/app";
    navigate({ to: target, replace: true });
  };

  return (
    <AuthLayout
      title="Bon retour parmi nous"
      subtitle="Connectez-vous à votre cockpit QHSE."
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
        <div>
          <LqInput
            label="Mot de passe"
            icon={Lock}
            type="password"
            placeholder="••••••••"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
          <div className="mt-2 text-right">
            <Link
              to="/auth/forgot-password"
              className="text-xs font-bold text-primary hover:underline"
            >
              Mot de passe oublié ?
            </Link>
          </div>
        </div>
        {error && (
          <p className="rounded-xl bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive">
            {error}
          </p>
        )}
        <LqButton type="submit" className="w-full" size="lg" withArrow>
          {busy ? "Connexion…" : "Se connecter"}
        </LqButton>
      </form>
      <p className="mt-8 text-center text-sm text-muted-foreground">
        Pas encore de compte ?{" "}
        <Link to="/auth/signup" className="font-bold text-primary hover:underline">
          Créer un compte
        </Link>
      </p>
    </AuthLayout>
  );
}
