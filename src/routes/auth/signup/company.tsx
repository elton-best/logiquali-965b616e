import { createFileRoute, Link } from "@tanstack/react-router";
import {
  Building2,
  Check,
  Hash,
  Lock,
  Mail,
  MailCheck,
  MapPin,
  Phone,
  Upload,
  User,
} from "lucide-react";
import { useState } from "react";
import { supabase } from "@/integrations/supabase/client";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { LqInput } from "@/components/auth/LqInput";
import { LqButton } from "@/components/lq/LqButton";
import { authErrorMessage } from "@/lib/auth-errors";

export const Route = createFileRoute("/auth/signup/company")({
  head: () => ({
    meta: [
      { title: "Inscription entreprise — LOGIQUALI" },
      { name: "description", content: "Créez l'espace QHSE de votre entreprise en 3 étapes." },
      { property: "og:title", content: "Inscription entreprise — LOGIQUALI" },
      { property: "og:description", content: "Créez l'espace QHSE de votre entreprise en 3 étapes." },
    ],
  }),
  component: CompanySignupPage,
});

const STEPS = ["Entreprise", "Administrateur", "Identité"];

function FileDrop({ label }: { label: string }) {
  const [name, setName] = useState("");
  return (
    <label className="flex cursor-pointer items-center gap-4 rounded-2xl border-[1.5px] border-dashed border-input bg-card p-4 transition-colors hover:border-primary hover:bg-primary-soft/40">
      <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary">
        {name ? <Check className="h-5 w-5" /> : <Upload className="h-5 w-5" />}
      </span>
      <span className="min-w-0">
        <span className="block text-sm font-bold text-foreground">{label}</span>
        <span className="block truncate text-xs text-muted-foreground">
          {name || "PDF, JPG ou PNG — 5 Mo max"}
        </span>
      </span>
      <input
        type="file"
        accept=".pdf,.jpg,.jpeg,.png"
        className="hidden"
        onChange={(e) => setName(e.target.files?.[0]?.name ?? "")}
      />
    </label>
  );
}

function CompanySignupPage() {
  const [step, setStep] = useState(0);
  const [done, setDone] = useState(false);
  const [companyName, setCompanyName] = useState("");
  const [rccm, setRccm] = useState("");
  const [ifu, setIfu] = useState("");
  const [companyAddress, setCompanyAddress] = useState("");
  const [phone, setPhone] = useState("");
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  const next = async (e: React.FormEvent) => {
    e.preventDefault();
    if (step === 1 && password !== confirm) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }
    setError("");
    if (step < 2) {
      setStep(step + 1);
      return;
    }
    setBusy(true);
    const { error: authError } = await supabase.auth.signUp({
      email,
      password,
      options: {
        emailRedirectTo: `${window.location.origin}/auth/login`,
        data: {
          account_type: "company",
          first_name: firstName,
          last_name: lastName,
          phone,
          company_name: companyName,
          company_rccm: rccm,
          company_ifu: ifu,
          company_address: companyAddress,
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
      <AuthLayout title="Dossier envoyé" subtitle="Votre compte entreprise est en attente de validation.">
        <div className="rounded-3xl border border-border bg-card p-8 text-center">
          <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-soft text-primary">
            <MailCheck className="h-8 w-8" />
          </span>
          <h2 className="mt-5 font-display text-lg font-bold text-foreground">Vérifiez votre e-mail</h2>
          <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
            Un lien de vérification vous a été envoyé. Nos équipes examinent votre dossier sous
            24 à 48h ; vous serez notifié dès l'activation de votre espace.
          </p>
          <LqButton to="/" variant="ghost" className="mt-6">
            Retour à l'accueil
          </LqButton>
        </div>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout
      title="Inscription entreprise"
      subtitle="3 étapes pour créer l'espace QHSE de votre organisation."
    >
      {/* Stepper */}
      <ol className="mb-8 flex items-center">
        {STEPS.map((label, i) => (
          <li key={label} className="flex flex-1 items-center last:flex-none">
            <div className="flex items-center gap-2">
              <span
                className={`flex h-8 w-8 items-center justify-center rounded-full font-display text-xs font-extrabold transition-colors ${
                  i < step
                    ? "bg-primary text-primary-foreground"
                    : i === step
                      ? "bg-primary text-primary-foreground shadow-lg shadow-primary/30"
                      : "bg-secondary text-muted-foreground"
                }`}
              >
                {i < step ? <Check className="h-4 w-4" /> : i + 1}
              </span>
              <span
                className={`hidden text-xs font-bold sm:block ${
                  i <= step ? "text-foreground" : "text-muted-foreground"
                }`}
              >
                {label}
              </span>
            </div>
            {i < STEPS.length - 1 && (
              <span className={`mx-3 h-0.5 flex-1 rounded ${i < step ? "bg-primary" : "bg-border"}`} />
            )}
          </li>
        ))}
      </ol>

      <form onSubmit={next} className="space-y-4">
        {step === 0 && (
          <>
            <LqInput
              label="Raison sociale"
              icon={Building2}
              placeholder="Ma Société SARL"
              value={companyName}
              onChange={(e) => setCompanyName(e.target.value)}
              required
            />
            <div className="grid gap-4 sm:grid-cols-2">
              <LqInput
                label="N° RCCM"
                icon={Hash}
                placeholder="RB/COT/24 B 0000"
                value={rccm}
                onChange={(e) => setRccm(e.target.value)}
                required
              />
              <LqInput
                label="N° IFU"
                icon={Hash}
                placeholder="3202400000000"
                value={ifu}
                onChange={(e) => setIfu(e.target.value)}
                required
              />
            </div>
            <LqInput
              label="Adresse du siège"
              icon={MapPin}
              placeholder="Cotonou, Bénin"
              value={companyAddress}
              onChange={(e) => setCompanyAddress(e.target.value)}
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
          </>
        )}
        {step === 1 && (
          <>
            <div className="grid gap-4 sm:grid-cols-2">
              <LqInput
                label="Prénom"
                icon={User}
                placeholder="Jean"
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                required
              />
              <LqInput
                label="Nom"
                icon={User}
                placeholder="Dupont"
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
                required
              />
            </div>
            <LqInput
              label="E-mail professionnel"
              icon={Mail}
              type="email"
              placeholder="admin@entreprise.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
            <LqInput
              label="Mot de passe"
              icon={Lock}
              type="password"
              placeholder="8 caractères minimum"
              minLength={8}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
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
          </>
        )}
        {step === 2 && (
          <>
            <FileDrop label="Pièce d'identité de l'administrateur" />
            <FileDrop label="Extrait RCCM" />
            <FileDrop label="Attestation IFU" />
            {error && (
              <p className="rounded-xl bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive">
                {error}
              </p>
            )}
            <label className="flex items-start gap-3 pt-2 text-xs leading-relaxed text-muted-foreground">
              <input type="checkbox" required className="mt-0.5 h-4 w-4 accent-primary" />
              J'accepte les conditions générales d'utilisation et certifie l'exactitude des
              informations fournies.
            </label>
          </>
        )}

        <div className="flex gap-3 pt-3">
          {step > 0 && (
            <LqButton variant="ghost" onClick={() => setStep(step - 1)} className="flex-1">
              Retour
            </LqButton>
          )}
          <LqButton type="submit" className="flex-1" withArrow>
            {busy ? "Envoi…" : step < 2 ? "Continuer" : "Créer mon compte"}
          </LqButton>
        </div>
      </form>

      <p className="mt-8 text-center text-sm text-muted-foreground">
        Vous êtes un particulier ?{" "}
        <Link to="/auth/signup/individual" className="font-bold text-primary hover:underline">
          Inscription particulier
        </Link>
      </p>
    </AuthLayout>
  );
}
