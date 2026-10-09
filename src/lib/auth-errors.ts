const MESSAGES: Record<string, string> = {
  "Invalid login credentials": "E-mail ou mot de passe incorrect.",
  "Identifiants incorrects": "E-mail ou mot de passe incorrect.",
  "Email not confirmed": "Veuillez confirmer votre adresse e-mail avant de vous connecter.",
  "User already registered": "Un compte existe déjà avec cette adresse e-mail.",
  "Cet email est déjà utilisé": "Un compte existe déjà avec cette adresse e-mail.",
  "Email rate limit exceeded": "Trop de tentatives. Réessayez dans quelques minutes.",
  "Password should be at least 6 characters": "Le mot de passe doit contenir au moins 6 caractères.",
  "Signup requires a valid password": "Le mot de passe est invalide.",
  "Anonymous sign-ins are disabled": "Connexion anonyme désactivée.",
  "Le code a expire. Veuillez relancer la connexion.": "Le code de vérification a expiré. Relancez la connexion.",
  "Code invalide ou expire.": "Le code de vérification est invalide ou expiré.",
};

export function authErrorMessage(error: unknown): string {
  const raw = error instanceof Error ? error.message : String(error ?? "");
  if (MESSAGES[raw]) return MESSAGES[raw];
  if (raw.includes("email_verified") || raw.includes("email n'est pas vérifiée")) {
    return "Veuillez confirmer votre adresse e-mail avant de vous connecter.";
  }
  if (raw.includes("mot de passe") || raw.includes("password")) return raw;
  if (raw.includes("serveur") || raw.includes("indisponible")) return raw;
  return raw || "Une erreur est survenue. Veuillez réessayer.";
}
