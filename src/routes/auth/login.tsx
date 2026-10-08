import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { KeyRound, Lock, Mail, RefreshCw, ShieldCheck } from "lucide-react";
import { useState } from "react";
import { backendApi } from "@/integrations/backend/client";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { LqInput } from "@/components/auth/LqInput";
import { LqButton } from "@/components/lq/LqButton";
import { authErrorMessage } from "@/lib/auth-errors";

const DEMO_EMAIL = import.meta.env["VITE_DEMO_EMAIL"] || "demo.entreprise@logiquali.test";
const DEMO_PASSWORD = import.meta.env["VITE_DEMO_PASSWORD"] || "DemoLogiQuali2026!";

export const Route = createFileRoute("/auth/login")({
  validateSearch: (search: Record<string, unknown>): { redirect?: string } => {
    const value = search["redirect"];
    return typeof value === "string" && value.startsWith("/") && !value.startsWith("//")
      ? { redirect: value }
      : {};
  },
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
  const [code, setCode] = useState("");
  const [mfaToken, setMfaToken] = useState<string | null>(null);
  const [mfaHint, setMfaHint] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  const finish = () => navigate({ to: search.redirect ?? "/app", replace: true });

  const authenticate = async (loginEmail: string, loginPassword: string, autoVerifyMfa = false) => {
    setError("");
    setBusy(true);
    try {
      const result = await backendApi.auth.login(loginEmail, loginPassword);
      if (result.mfa_required) {
        if (autoVerifyMfa && result.mfa_code) {
          await backendApi.auth.verifyMfa(result.mfa_token, result.mfa_code);
          finish();
          return;
        }
        setMfaToken(result.mfa_token);
        if (result.mfa_code) setMfaHint(`Code de démonstration : ${result.mfa_code}`);
        return;
      }
      finish();
    } catch (authError) {
      setError(authErrorMessage(authError));
    } finally {
      setBusy(false);
    }
  };

  const submitCredentials = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email.includes("@")) {
      setError("Adresse e-mail invalide.");
      return;
    }
    await authenticate(email, password);
  };

  const accessDemo = async () => {
    await authenticate(DEMO_EMAIL, DEMO_PASSWORD, true);
  };

  const submitMfa = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!mfaToken || !/^\d{6}$/.test(code)) {
      setError("Saisissez le code de vérification à 6 chiffres.");
      return;
    }
    setError("");
    setBusy(true);
    try {
      await backendApi.auth.verifyMfa(mfaToken, code);
      finish();
    } catch (authError) {
      setError(authErrorMessage(authError));
    } finally {
      setBusy(false);
    }
  };

  const resendMfa = async () => {
    if (!mfaToken) return;
    setError("");
    setBusy(true);
    try {
      const result = await backendApi.auth.resendMfa(mfaToken);
      setMfaToken(result.mfa_token);
      setCode("");
      if (result.mfa_code) setMfaHint(`Code de démonstration : ${result.mfa_code}`);
    } catch (authError) {
      setError(authErrorMessage(authError));
    } finally {
      setBusy(false);
    }
  };

  if (mfaToken) {
    return (
      <AuthLayout
        title="Vérification en deux étapes"
        subtitle="Un code de sécurité a été envoyé à votre adresse e-mail."
      >
        <form onSubmit={submitMfa} className="space-y-5">
          <div className="rounded-2xl border border-primary/20 bg-primary-soft/60 p-4 text-sm text-foreground">
            <div className="flex items-start gap-3">
              <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
              <p>Entrez le code à 6 chiffres pour finaliser votre connexion.</p>
            </div>
          </div>
          {mfaHint && (
            <p className="rounded-xl bg-warning/15 px-4 py-3 text-xs font-semibold text-warning-foreground">
              {mfaHint}
            </p>
          )}
          <LqInput
            label="Code de vérification"
            icon={KeyRound}
            inputMode="numeric"
            maxLength={6}
            placeholder="000000"
            value={code}
            onChange={(e) => setCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
            required
          />
          {error && (
            <p className="rounded-xl bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive">
              {error}
            </p>
          )}
          <LqButton type="submit" className="w-full" size="lg" withArrow>
            {busy ? "Vérification…" : "Valider le code"}
          </LqButton>
          <button
            type="button"
            onClick={resendMfa}
            disabled={busy}
            className="mx-auto flex items-center gap-2 text-xs font-bold text-primary hover:underline disabled:opacity-50"
          >
            <RefreshCw className="h-3.5 w-3.5" /> Renvoyer le code
          </button>
          <button
            type="button"
            onClick={() => {
              setMfaToken(null);
              setCode("");
              setError("");
            }}
            className="block w-full text-center text-xs font-semibold text-muted-foreground hover:text-foreground"
          >
            Modifier mes identifiants
          </button>
        </form>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout title="Bon retour parmi nous" subtitle="Connectez-vous à votre cockpit QHSE.">
      <form onSubmit={submitCredentials} className="space-y-5">
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
        {import.meta.env.DEV && (
          <button
            type="button"
            onClick={accessDemo}
            disabled={busy}
            className="w-full rounded-xl border border-dashed border-primary/50 px-4 py-3 text-xs font-bold text-primary hover:bg-primary-soft disabled:opacity-50"
          >
            {busy ? "Ouverture du compte démo…" : "Accéder au compte démo entreprise"}
          </button>
        )}
      </form>
      <p className="mt-8 text-center text-sm text-muted-foreground">
        Pas encore de compte?{" "}
        <Link to="/auth/signup" className="font-bold text-primary hover:underline">
          Créer un compte
        </Link>
      </p>
    </AuthLayout>
  );
}
