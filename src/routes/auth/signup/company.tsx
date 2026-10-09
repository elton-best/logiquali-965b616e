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
import { backendApi } from "@/integrations/backend/client";
import { AuthLayout } from "@/components/auth/AuthLayout";
import { LqInput } from "@/components/auth/LqInput";
import { LqButton } from "@/components/lq/LqButton";
import { authErrorMessage } from "@/lib/auth-errors";

export const Route = createFileRoute("/auth/signup/company")({
  head: () => ({
    meta: [
      { title: "Inscription entreprise — LOGIQUALI" },
      { name: "description", content: "Créez l'espace QHSE de votre entreprise en 3 étapes." },
    ],
  }),
  component: CompanySignupPage,
});

const STEPS = ["Entreprise", "Administrateur", "Pièces justificatives"];
type DocumentKey = "id_document" | "rccm_document" | "ifu_document";

function FileDrop({
  label,
  file,
  onChange,
}: {
  label: string;
  file: File | null;
  onChange: (file: File | null) => void;
}) {
  return (
    <label className="flex cursor-pointer items-center gap-4 rounded-2xl border-[1.5px] border-dashed border-input bg-card p-4 transition-colors hover:border-primary hover:bg-primary-soft/40">
      <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary">
        {file ? <Check className="h-5 w-5" /> : <Upload className="h-5 w-5" />}
      </span>
      <span className="min-w-0">
        <span className="block text-sm font-bold text-foreground">{label} *</span>
        <span className="block truncate text-xs text-muted-foreground">
          {file?.name || "PDF, JPG ou PNG — 5 Mo max"}
        </span>
      </span>
      <input
        type="file"
        accept=".pdf,.jpg,.jpeg,.png"
        className="hidden"
        onChange={(e) => onChange(e.target.files?.[0] ?? null)}
        required={!file}
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
  const [field, setField] = useState("");
  const [companyAddress, setCompanyAddress] = useState("");
  const [city, setCity] = useState("Cotonou");
  const [phone, setPhone] = useState("");
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [files, setFiles] = useState<Record<DocumentKey, File | null>>({
    id_document: null,
    rccm_document: null,
    ifu_document: null,
  });
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  const next = async (e: React.FormEvent) => {
    e.preventDefault();
    if (step === 1 && password !== confirm) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }
    if (step < 2) {
      setError("");
      setStep(step + 1);
      return;
    }
    if (!files.id_document || !files.rccm_document || !files.ifu_document) {
      setError("Les trois pièces justificatives sont obligatoires pour le backend.");
      return;
    }
    setError("");
    setBusy(true);
    try {
      const username =
        `${firstName}.${lastName}`.toLowerCase().replace(/[^a-z0-9.]+/g, "") || email.split("@")[0];
      const payload = new FormData();
      const values: Record<string, string> = {
        enterprise_name: companyName,
        email,
        enterprise_email: email,
        registration_number: rccm,
        rccm_number: rccm,
        ifu,
        ifu_number: ifu,
        address: companyAddress,
        city,
        country: "Bénin",
        first_name: firstName,
        last_name: lastName,
        job_title: "Administrateur entreprise",
        username,
        password,
        password_confirmation: confirm,
        phone,
        field,
      };
      Object.entries(values).forEach(([key, value]) => payload.append(key, value));
      payload.append("id_type", "CNI");
      payload.append("id_document", files.id_document);
      payload.append("rccm_document", files.rccm_document);
      payload.append("ifu_document", files.ifu_document);
      await backendApi.auth.registerEnterprise(payload);
      setDone(true);
    } catch (authError) {
      setError(authErrorMessage(authError));
    } finally {
      setBusy(false);
    }
  };

  if (done) {
    return (
      <AuthLayout
        title="Dossier envoyé"
        subtitle="Votre compte entreprise est en attente de validation."
      >
        <div className="rounded-3xl border border-border bg-card p-8 text-center">
          <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-soft text-primary">
            <MailCheck className="h-8 w-8" />
          </span>
          <h2 className="mt-5 font-display text-lg font-bold text-foreground">
            Vérifiez votre e-mail
          </h2>
          <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
            Votre dossier a été transmis au backend LOGIQUALI. Vérifiez votre e-mail puis attendez
            la validation de l'entreprise sous 24 à 48 h.
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
      subtitle="Les informations et pièces sont envoyées au backend Laravel LOGIQUALI."
    >
      <ol className="mb-8 flex items-center">
        {STEPS.map((label, i) => (
          <li key={label} className="flex flex-1 items-center last:flex-none">
            <div className="flex items-center gap-2">
              <span
                className={`flex h-8 w-8 items-center justify-center rounded-full font-display text-xs font-extrabold ${i < step || i === step ? "bg-primary text-primary-foreground" : "bg-secondary text-muted-foreground"}`}
              >
                {i < step ? <Check className="h-4 w-4" /> : i + 1}
              </span>
              <span
                className={`hidden text-xs font-bold sm:block ${i <= step ? "text-foreground" : "text-muted-foreground"}`}
              >
                {label}
              </span>
            </div>
            {i < STEPS.length - 1 && (
              <span
                className={`mx-3 h-0.5 flex-1 rounded ${i < step ? "bg-primary" : "bg-border"}`}
              />
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
              label="Domaine d'activité"
              icon={Building2}
              placeholder="Conseil, industrie, services…"
              value={field}
              onChange={(e) => setField(e.target.value)}
              required
            />
            <LqInput
              label="Adresse du siège"
              icon={MapPin}
              placeholder="Cotonou, Bénin"
              value={companyAddress}
              onChange={(e) => setCompanyAddress(e.target.value)}
              required
            />
            <div className="grid gap-4 sm:grid-cols-2">
              <LqInput
                label="Ville"
                icon={MapPin}
                value={city}
                onChange={(e) => setCity(e.target.value)}
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
            </div>
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
              placeholder="8 caractères, une majuscule et un chiffre"
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
            <FileDrop
              label="Pièce d'identité de l'administrateur"
              file={files.id_document}
              onChange={(file) => setFiles((current) => ({ ...current, id_document: file }))}
            />
            <FileDrop
              label="Extrait RCCM"
              file={files.rccm_document}
              onChange={(file) => setFiles((current) => ({ ...current, rccm_document: file }))}
            />
            <FileDrop
              label="Attestation IFU"
              file={files.ifu_document}
              onChange={(file) => setFiles((current) => ({ ...current, ifu_document: file }))}
            />
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
            <LqButton
              type="button"
              variant="ghost"
              onClick={() => setStep(step - 1)}
              className="flex-1"
            >
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
