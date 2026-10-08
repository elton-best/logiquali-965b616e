import { createFileRoute, Link } from "@tanstack/react-router";
import { Lock, Mail } from "lucide-react";
import { useState } from "react";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { LqInput } from "@/components/auth/LqInput";
import { LqButton } from "@/components/lq/LqButton";

export const Route = createFileRoute("/auth/login")({
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
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!email.includes("@")) {
      setError("Adresse e-mail invalide.");
      return;
    }
    if (password.length < 6) {
      setError("Le mot de passe doit contenir au moins 6 caractères.");
      return;
    }
    setError("");
    // La connexion réelle sera branchée lors de l'activation du backend.
  };

  return (
    <AuthLayout
      title="Bon retour parmi nous"
      subtitle="Connectez-vous à votre cockpit QHSE. Un code de vérification vous sera envoyé par e-mail."
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
            <span className="cursor-pointer text-xs font-bold text-primary hover:underline">
              Mot de passe oublié ?
            </span>
          </div>
        </div>
        {error && (
          <p className="rounded-xl bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive">
            {error}
          </p>
        )}
        <LqButton type="submit" className="w-full" size="lg" withArrow>
          Se connecter
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
