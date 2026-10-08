import { createFileRoute, Link } from "@tanstack/react-router";
import { Building2, Lock, Mail, MailCheck, MapPin, Phone, User } from "lucide-react";
import { useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { LqInput } from "@/components/auth/LqInput";
import { LqButton } from "@/components/lq/LqButton";
import { authErrorMessage } from "@/lib/auth-errors";
import { passwordStrength } from "@/lib/password-strength";

export const Route = createFileRoute("/auth/signup/individual")({
  head: () => ({
    meta: [
      { title: "Inscription particulier — LOGIQUALI" },
      { name: "description", content: "Créez votre compte particulier LOGIQUALI en quelques secondes." },
      { property: "og:title", content: "Inscription particulier — LOGIQUALI" },
      { property: "og:description", content: "Créez votre compte particulier LOGIQUALI." },
    ],
  }),
  component: IndividualSignupPage,
});

const STRENGTH_LABELS = ["Très faible", "Faible", "Moyen", "Bon", "Excellent"];

function IndividualSignupPage() {
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [company, setCompany] = useState("");
  const [address, setAddress] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(false);
  const score = passwordStrength(password);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (score < 2) return;
    setError("");
    setBusy(true);
    const { error: authError } = await supabase.auth.signUp({
      email,
      password,
      options: {
        emailRedirectTo: `${window.location.origin}/auth/login`,
        data: {
          account_type: "individual",
          first_name: firstName,
          last_name: lastName,
          phone,
          company_name: company || null,
          company_address: address || null,
        },
      },
    });
    setBusy(false);
    if (authError) {
      setError(authErrorMessage(authError));
      return;
    }
    setDone(true);
  };

  if (done) {
    return (
      <AuthLayout title="Compte créé" subtitle="Plus qu'une étape avant d'accéder à votre espace.">
        <div className="rounded-3xl border border-border bg-card p-8 text-center">
          <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-soft text-primary">
            <MailCheck className="h-8 w-8" />
          </span>
          <h2 className="mt-5 font-display text-lg font-bold text-foreground">Vérifiez votre e-mail</h2>
          <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
            Cliquez sur le lien reçu pour activer votre compte immédiatement.
          </p>
          <LqButton to="/auth/login" className="mt-6" withArrow>
            Aller à la connexion
          </LqButton>
        </div>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout
      title="Inscription particulier"
      subtitle="Activation instantanée après vérification de votre e-mail."
    >
      <form onSubmit={submit} className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <LqInput
            label="Prénom"
            icon={User}
            placeholder="Awa"
            value={firstName}
            onChange={(e) => setFirstName(e.target.value)}
            required
          />
          <LqInput
            label="Nom"
            icon={User}
            placeholder="Koné"
            value={lastName}
            onChange={(e) => setLastName(e.target.value)}
            required
          />
        </div>
        <LqInput
          label="E-mail"
          icon={Mail}
          type="email"
          placeholder="vous@email.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        <LqInput
          label="Téléphone"
          icon={Phone}
          type="tel"
          placeholder="+229 00 00 00 00"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
          required
        />
        <div className="grid gap-4 sm:grid-cols-2">
          <LqInput
            label="Entreprise (optionnel)"
            icon={Building2}
            placeholder="—"
            value={company}
            onChange={(e) => setCompany(e.target.value)}
          />
          <LqInput
            label="Adresse (optionnel)"
            icon={MapPin}
            placeholder="—"
            value={address}
            onChange={(e) => setAddress(e.target.value)}
          />
        </div>
        <div>
          <LqInput
            label="Mot de passe"
            icon={Lock}
            type="password"
            placeholder="8 caractères minimum"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            minLength={8}
            required
          />
          {password && (
            <div className="mt-2.5">
              <div className="flex gap-1.5">
                {[0, 1, 2, 3].map((i) => (
                  <span
                    key={i}
                    className={`h-1.5 flex-1 rounded-full transition-colors ${
                      i < score ? (score <= 1 ? "bg-destructive" : "bg-primary") : "bg-border"
                    }`}
                  />
                ))}
              </div>
              <p className="mt-1.5 text-xs font-semibold text-muted-foreground">
                Force : {STRENGTH_LABELS[score]}
              </p>
            </div>
          )}
        </div>
        {error && (
          <p className="rounded-xl bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive">
            {error}
          </p>
        )}
        <label className="flex items-start gap-3 pt-1 text-xs leading-relaxed text-muted-foreground">
          <input type="checkbox" required className="mt-0.5 h-4 w-4 accent-primary" />
          J'accepte les conditions générales d'utilisation et la politique de confidentialité.
        </label>
        <LqButton type="submit" className="w-full" size="lg" withArrow>
          {busy ? "Création…" : "Créer mon compte"}
        </LqButton>
      </form>
      <p className="mt-8 text-center text-sm text-muted-foreground">
        Déjà inscrit ?{" "}
        <Link to="/auth/login" className="font-bold text-primary hover:underline">
          Se connecter
        </Link>
      </p>
    </AuthLayout>
  );
}
