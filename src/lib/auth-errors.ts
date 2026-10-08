const MESSAGES: Record<string, string> = {
  "Invalid login credentials": "E-mail ou mot de passe incorrect.",
  "Email not confirmed": "Veuillez confirmer votre adresse e-mail avant de vous connecter.",
  "User already registered": "Un compte existe déjà avec cette adresse e-mail.",
  "Email rate limit exceeded": "Trop de tentatives. Réessayez dans quelques minutes.",
  "Password should be at least 6 characters":
    "Le mot de passe doit contenir au moins 6 caractères.",
  "Signup requires a valid password": "Le mot de passe est invalide.",
  "Anonymous sign-ins are disabled": "Connexion anonyme désactivée.",
};

export function authErrorMessage(error: unknown): string {
  const raw = error instanceof Error ? error.message : String(error ?? "");
  return MESSAGES[raw] ?? "Une erreur est survenue. Veuillez réessayer.";
}
