// Configuration of every QHSE section: entity kinds, fields, statuses, workflow buttons and relations.

export type FieldType = "text" | "textarea" | "select" | "date" | "number" | "relation" | "email";

export type Field = {
  key: string;
  label: string;
  type: FieldType;
  options?: string[];
  /** For relation fields: kinds that can be linked. */
  kinds?: string[];
  required?: boolean;
  /** Show as a column in the list. */
  column?: boolean;
};

export type StatusTone = "neutral" | "info" | "warn" | "success" | "danger";

/** A workflow button shown on a record. */
export type Transition = {
  label: string;
  /** Target status. Omit to only log the event (e.g. "Diffuser"). */
  to?: string;
  /** Statuses where the button is visible. Omit = always. */
  from?: string[];
  tone?: "primary" | "default" | "danger";
  /** Asks for a mandatory comment with this label. */
  reason?: string;
  /** Asks for a date stored in data[date.key]. */
  date?: { key: string; label: string };
  /** Asks for a number stored in data[number.key] (e.g. "Saisir une valeur"). */
  number?: { key: string; label: string };
  confirm?: boolean;
  /** Increments data.version (new revision). */
  bumpVersion?: boolean;
};

export type KindConfig = {
  kind: string;
  label: string; // plural
  singular: string;
  prefix: string;
  titleLabel: string;
  statuses: { value: string; tone: StatusTone }[];
  actions?: Transition[];
  fields: Field[];
  /** Field holding the person in charge (for "Mes tâches"). */
  owner?: string;
  /** Field holding a due date. */
  due?: string;
  /** Statuses considered finished. */
  done?: string[];
};

export type SectionConfig = {
  slug: string;
  title: string;
  description: string;
  kinds: [KindConfig, ...KindConfig[]];
};

export const NORM_CODES = ["ISO 9001", "ISO 14001", "ISO 45001", "ISO 50001", "ISO 22000", "ISO 27001"] as const;

const PROCESS_REL: Field = { key: "process_id", label: "Processus", type: "relation", kinds: ["process"], column: true };
const SITE_REL: Field = { key: "site_id", label: "Site", type: "relation", kinds: ["site"], column: true };
const PILOT: Field = { key: "pilot_id", label: "Responsable", type: "relation", kinds: ["collaborator"], column: true };
const DUE: Field = { key: "due_date", label: "Échéance", type: "date", column: true };
const NORM_F: Field = { key: "norm", label: "Norme", type: "select", options: [...NORM_CODES], column: true };
const DOC_REL: Field = { key: "document_id", label: "Document lié", type: "relation", kinds: ["document", "procedure"] };

// ---------- Shared workflows ----------
const VALIDATION_STATUSES: KindConfig["statuses"] = [
  { value: "Brouillon", tone: "neutral" },
  { value: "En vérification", tone: "info" },
  { value: "En approbation", tone: "warn" },
  { value: "Publié", tone: "success" },
  { value: "À corriger", tone: "danger" },
  { value: "Rejeté", tone: "danger" },
  { value: "Archivé", tone: "neutral" },
];
const VALIDATION_ACTIONS: Transition[] = [
  { label: "Soumettre à vérification", to: "En vérification", from: ["Brouillon", "À corriger"], tone: "primary" },
  { label: "Vérifier et transmettre à l'approbation", to: "En approbation", from: ["En vérification"], tone: "primary" },
  { label: "Approuver et publier", to: "Publié", from: ["En approbation"], tone: "primary", date: { key: "effective_date", label: "Date d'effet" } },
  { label: "Demander une correction", to: "À corriger", from: ["En vérification", "En approbation"], reason: "Commentaire de correction" },
  { label: "Rejeter", to: "Rejeté", from: ["En vérification", "En approbation"], tone: "danger", reason: "Motif du rejet", confirm: true },
  { label: "Nouvelle révision", to: "Brouillon", from: ["Publié"], bumpVersion: true },
  { label: "Archiver", to: "Archivé", from: ["Publié", "Rejeté", "Brouillon"], tone: "danger", confirm: true },
  { label: "Réactiver", to: "Brouillon", from: ["Archivé"] },
];
const VALIDATION_DONE = ["Publié", "Archivé", "Rejeté"];

const archive = (from: string[], back: string): Transition[] => [
  { label: "Archiver", to: "Archivé", from, tone: "danger", confirm: true },
  { label: "Réactiver", to: back, from: ["Archivé"] },
];

const validated = (k: Omit<KindConfig, "statuses" | "actions" | "done"> & { extra?: Transition[] }): KindConfig => {
  const { extra, ...rest } = k;
  return { ...rest, statuses: VALIDATION_STATUSES, actions: [...VALIDATION_ACTIONS, ...(extra ?? [])], done: VALIDATION_DONE };
};

// ---------- Kinds ----------
const K = {
  site: {
    kind: "site", label: "Sites", singular: "site", prefix: "SIT", titleLabel: "Nom du site",
    statuses: [{ value: "Actif", tone: "success" }, { value: "Suspendu", tone: "warn" }, { value: "Archivé", tone: "neutral" }],
    actions: [
      { label: "Suspendre", to: "Suspendu", from: ["Actif"], tone: "danger", reason: "Motif de la suspension", confirm: true },
      { label: "Réactiver", to: "Actif", from: ["Suspendu", "Archivé"], tone: "primary" },
      { label: "Archiver", to: "Archivé", from: ["Actif", "Suspendu"], tone: "danger", confirm: true },
    ],
    fields: [
      { key: "code", label: "Code", type: "text", column: true },
      { key: "type", label: "Type", type: "select", options: ["Siège", "Usine", "Agence", "Bureau", "Entrepôt"], column: true },
      { key: "city", label: "Ville", type: "text", column: true },
      { key: "address", label: "Adresse", type: "text", required: true },
      { key: "manager_id", label: "Responsable de site", type: "relation", kinds: ["collaborator"], column: true },
      { key: "norms", label: "Normes actives sur le site", type: "text" },
    ],
  },
  collaborator: {
    kind: "collaborator", label: "Personnel", singular: "collaborateur", prefix: "COL", titleLabel: "Nom complet",
    statuses: [{ value: "Invité", tone: "info" }, { value: "Actif", tone: "success" }, { value: "Désactivé", tone: "danger" }],
    actions: [
      { label: "Renvoyer l'invitation", from: ["Invité"] },
      { label: "Activer", to: "Actif", from: ["Invité"], tone: "primary" },
      { label: "Désactiver", to: "Désactivé", from: ["Actif", "Invité"], tone: "danger", reason: "Motif de la désactivation", confirm: true },
      { label: "Réactiver", to: "Actif", from: ["Désactivé"], tone: "primary" },
    ],
    fields: [
      { key: "email", label: "E-mail", type: "email", required: true, column: true },
      { key: "job_id", label: "Poste", type: "relation", kinds: ["job"], column: true },
      { key: "role", label: "Rôle", type: "select", options: ["Administrateur", "Responsable de site", "Collaborateur", "Auditeur"], required: true, column: true },
      SITE_REL,
      { key: "unit_id", label: "Service (organigramme)", type: "relation", kinds: ["orgunit"] },
      { key: "phone", label: "Téléphone", type: "text" },
    ],
  },
  context: {
    kind: "context", label: "Enjeux", singular: "enjeu", prefix: "CTX", titleLabel: "Enjeu",
    statuses: [{ value: "Brouillon", tone: "neutral" }, { value: "Validé", tone: "success" }, { value: "À revoir", tone: "warn" }, { value: "Archivé", tone: "neutral" }],
    actions: [
      { label: "Valider", to: "Validé", from: ["Brouillon", "À revoir"], tone: "primary" },
      { label: "À revoir", to: "À revoir", from: ["Validé"], reason: "Raison de la revue" },
      ...archive(["Validé", "Brouillon", "À revoir"], "Brouillon"),
    ],
    fields: [
      { key: "category", label: "Analyse", type: "select", options: ["Enjeu interne", "Enjeu externe", "SWOT — Force", "SWOT — Faiblesse", "SWOT — Opportunité", "SWOT — Menace", "PESTEL — Politique", "PESTEL — Économique", "PESTEL — Social", "PESTEL — Technologique", "PESTEL — Environnemental", "PESTEL — Légal"], required: true, column: true },
      { key: "impact", label: "Impact", type: "select", options: ["Faible", "Moyen", "Fort"], column: true },
      { key: "risk_id", label: "Risque lié", type: "relation", kinds: ["risk"] },
      { key: "description", label: "Description", type: "textarea" },
    ],
  },
  party: {
    kind: "party", label: "Parties intéressées", singular: "partie intéressée", prefix: "PI", titleLabel: "Partie intéressée",
    statuses: [{ value: "Active", tone: "success" }, { value: "À revoir", tone: "warn" }, { value: "Archivé", tone: "neutral" }],
    actions: [{ label: "Marquer à revoir", to: "À revoir", from: ["Active"] }, { label: "Valider", to: "Active", from: ["À revoir"], tone: "primary" }, ...archive(["Active", "À revoir"], "Active")],
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Client", "Salarié", "Fournisseur", "Actionnaire", "Autorité", "Riverain", "Partenaire"], required: true, column: true },
      { key: "expectations", label: "Besoins et attentes", type: "textarea" },
      { key: "requirements", label: "Exigences applicables", type: "textarea" },
      { key: "influence", label: "Influence", type: "select", options: ["Faible", "Moyenne", "Forte"], column: true },
      PILOT,
    ],
  },
  scope: validated({
    kind: "scope", label: "Domaine d'application", singular: "périmètre", prefix: "PER", titleLabel: "Intitulé du périmètre",
    fields: [
      NORM_F,
      { key: "sites", label: "Sites couverts", type: "text", column: true },
      { key: "activities", label: "Activités et produits couverts", type: "textarea" },
      { key: "limits", label: "Limites", type: "textarea" },
      { key: "exclusions", label: "Exclusions et justifications", type: "textarea" },
      { key: "version", label: "Version", type: "text", column: true },
    ],
  }),
  policy: validated({
    kind: "policy", label: "Politiques", singular: "politique", prefix: "POL", titleLabel: "Intitulé de la politique",
    owner: "pilot_id", due: "review_date",
    extra: [{ label: "Diffuser", from: ["Publié"], reason: "Destinataires / canal de diffusion" }],
    fields: [
      NORM_F,
      { key: "version", label: "Version", type: "text", column: true },
      PILOT,
      { key: "review_date", label: "Prochaine révision", type: "date", column: true },
      { key: "content", label: "Engagements de la direction", type: "textarea", required: true },
    ],
  }),
  orgunit: {
    kind: "orgunit", label: "Organigramme", singular: "service", prefix: "ORG", titleLabel: "Service / direction",
    statuses: [{ value: "Actif", tone: "success" }, { value: "Archivé", tone: "neutral" }],
    actions: archive(["Actif"], "Actif"),
    fields: [
      { key: "parent_id", label: "Rattaché à", type: "relation", kinds: ["orgunit"], column: true },
      { key: "manager_id", label: "Responsable", type: "relation", kinds: ["collaborator"], column: true },
      SITE_REL,
    ],
  },
  job: validated({
    kind: "job", label: "Fiches de poste", singular: "fiche de poste", prefix: "FDP", titleLabel: "Intitulé du poste",
    fields: [
      { key: "unit_id", label: "Service", type: "relation", kinds: ["orgunit"], column: true },
      { key: "reports_to", label: "Supérieur hiérarchique", type: "relation", kinds: ["job"] },
      { key: "missions", label: "Missions", type: "textarea", required: true },
      { key: "responsibilities", label: "Responsabilités", type: "textarea" },
      { key: "skills", label: "Compétences requises", type: "textarea" },
    ],
  }),
  responsibility: validated({
    kind: "responsibility", label: "Fiches de responsabilité", singular: "fiche de responsabilité", prefix: "RES", titleLabel: "Responsabilité",
    owner: "pilot_id",
    fields: [
      PILOT,
      { key: "scope_id", label: "Objet", type: "relation", kinds: ["process", "document", "objective", "policy"], column: true },
      NORM_F,
      { key: "authority", label: "Autorité / délégation", type: "textarea" },
    ],
  }),
  risk: {
    kind: "risk", label: "Risques & opportunités", singular: "risque", prefix: "RSK", titleLabel: "Intitulé",
    owner: "pilot_id", done: ["Clôturé"],
    statuses: [{ value: "Identifié", tone: "warn" }, { value: "Évalué", tone: "info" }, { value: "En traitement", tone: "info" }, { value: "Maîtrisé", tone: "success" }, { value: "Clôturé", tone: "neutral" }],
    actions: [
      { label: "Évaluer / Réévaluer", to: "Évalué", from: ["Identifié", "Maîtrisé", "Évalué"], tone: "primary", number: { key: "gravity", label: "Gravité (1-5) — la probabilité se modifie dans la fiche" } },
      { label: "Lancer le traitement", to: "En traitement", from: ["Évalué"], tone: "primary" },
      { label: "Marquer maîtrisé", to: "Maîtrisé", from: ["En traitement"], tone: "primary" },
      { label: "Clôturer", to: "Clôturé", from: ["Maîtrisé"], confirm: true },
      { label: "Réouvrir", to: "Identifié", from: ["Clôturé", "Maîtrisé"], reason: "Raison de la réouverture" },
    ],
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Risque", "Opportunité"], required: true, column: true },
      { key: "source", label: "Source", type: "text" },
      PROCESS_REL,
      { key: "probability", label: "Probabilité (1-5)", type: "number", column: true },
      { key: "gravity", label: "Impact (1-5)", type: "number", column: true },
      PILOT,
      { key: "treatment", label: "Mesures et traitement prévus", type: "textarea" },
    ],
  },
  objective: {
    kind: "objective", label: "Objectifs", singular: "objectif", prefix: "OBJ", titleLabel: "Objectif",
    owner: "pilot_id", due: "due_date", done: ["Atteint", "Clôturé"],
    statuses: [{ value: "En cours", tone: "info" }, { value: "Atteint", tone: "success" }, { value: "Non atteint", tone: "danger" }, { value: "Clôturé", tone: "neutral" }],
    actions: [
      { label: "Mettre à jour la progression", from: ["En cours", "Non atteint"], number: { key: "progress", label: "Avancement (%)" }, tone: "primary" },
      { label: "Marquer atteint", to: "Atteint", from: ["En cours", "Non atteint"], tone: "primary" },
      { label: "Marquer non atteint", to: "Non atteint", from: ["En cours"], reason: "Analyse de l'écart" },
      { label: "Clôturer", to: "Clôturé", from: ["Atteint", "Non atteint"], confirm: true },
      { label: "Réouvrir", to: "En cours", from: ["Clôturé", "Atteint"] },
    ],
    fields: [
      PROCESS_REL,
      { key: "target", label: "Cible", type: "text", required: true, column: true },
      { key: "current", label: "Valeur actuelle", type: "text" },
      { key: "indicator_id", label: "Indicateur de mesure", type: "relation", kinds: ["indicator"] },
      { key: "progress", label: "Avancement (%)", type: "number", column: true },
      DUE,
      PILOT,
    ],
  },
  action: {
    kind: "action", label: "Actions", singular: "action", prefix: "ACT", titleLabel: "Action à réaliser",
    owner: "pilot_id", due: "due_date", done: ["Terminée", "Vérifiée"],
    statuses: [{ value: "À faire", tone: "neutral" }, { value: "En cours", tone: "info" }, { value: "Bloquée", tone: "danger" }, { value: "Terminée", tone: "success" }, { value: "Vérifiée", tone: "success" }],
    actions: [
      { label: "Démarrer", to: "En cours", from: ["À faire"], tone: "primary" },
      { label: "Signaler un blocage", to: "Bloquée", from: ["À faire", "En cours"], tone: "danger", reason: "Raison du blocage" },
      { label: "Débloquer", to: "En cours", from: ["Bloquée"], tone: "primary" },
      { label: "Ajouter un jalon", from: ["À faire", "En cours"], reason: "Jalon (étape et date)" },
      { label: "Terminer", to: "Terminée", from: ["En cours"], tone: "primary", reason: "Preuve de réalisation" },
      { label: "Vérifier l'efficacité", to: "Vérifiée", from: ["Terminée"], tone: "primary" },
      { label: "Réouvrir", to: "En cours", from: ["Terminée", "Vérifiée"], reason: "Raison de la réouverture" },
    ],
    fields: [
      { key: "origin_id", label: "Origine", type: "relation", kinds: ["nc", "risk", "audit", "objective", "review", "complaint", "suggestion", "improvement", "process_review", "env_aspect", "hazard", "energy_use", "haccp", "infosec"], column: true },
      PROCESS_REL,
      PILOT,
      { key: "priority", label: "Priorité", type: "select", options: ["Basse", "Normale", "Haute", "Urgente"], column: true },
      DUE,
      { key: "depends_on", label: "Dépend de", type: "relation", kinds: ["action"] },
      { key: "description", label: "Description", type: "textarea" },
    ],
  },
  process: {
    kind: "process", label: "Processus", singular: "processus", prefix: "PRO", titleLabel: "Nom du processus",
    owner: "pilot_id",
    statuses: [{ value: "Brouillon", tone: "neutral" }, { value: "En vérification", tone: "info" }, { value: "Actif", tone: "success" }, { value: "À revoir", tone: "warn" }, { value: "Archivé", tone: "neutral" }],
    actions: [
      { label: "Soumettre", to: "En vérification", from: ["Brouillon", "À revoir"], tone: "primary" },
      { label: "Approuver", to: "Actif", from: ["En vérification"], tone: "primary" },
      { label: "Demander une correction", to: "À revoir", from: ["En vérification", "Actif"], reason: "Commentaire" },
      ...archive(["Actif", "Brouillon", "À revoir"], "Brouillon"),
    ],
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Management", "Réalisation", "Support"], required: true, column: true },
      PILOT,
      { key: "purpose", label: "Finalité", type: "textarea" },
      { key: "inputs", label: "Éléments d'entrée", type: "textarea" },
      { key: "outputs", label: "Éléments de sortie", type: "textarea" },
      { key: "resources", label: "Ressources", type: "textarea" },
      { key: "upstream_id", label: "Processus amont", type: "relation", kinds: ["process"] },
      { key: "downstream_id", label: "Processus aval", type: "relation", kinds: ["process"] },
    ],
  },
  document: validated({
    kind: "document", label: "Information documentée", singular: "document", prefix: "DOC", titleLabel: "Titre du document",
    owner: "pilot_id", due: "review_date",
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Manuel", "Politique", "Procédure", "Instruction", "Formulaire", "Enregistrement", "Document externe"], required: true, column: true },
      PROCESS_REL,
      SITE_REL,
      { key: "version", label: "Version", type: "text", column: true },
      { key: "review_date", label: "Prochaine révision", type: "date", column: true },
      PILOT,
      { key: "file_url", label: "Lien du fichier", type: "text" },
      { key: "summary", label: "Résumé", type: "textarea" },
    ],
  }),
  procedure: validated({
    kind: "procedure", label: "Procédures", singular: "procédure", prefix: "PRC", titleLabel: "Intitulé de la procédure",
    owner: "pilot_id", due: "review_date",
    fields: [
      PROCESS_REL,
      { key: "version", label: "Version", type: "text", column: true },
      PILOT,
      { key: "users", label: "Utilisateurs concernés", type: "text" },
      { key: "review_date", label: "Prochaine révision", type: "date", column: true },
      { key: "steps", label: "Consignes / étapes", type: "textarea", required: true },
    ],
  }),
  awareness: {
    kind: "awareness", label: "Sensibilisation", singular: "campagne", prefix: "SEN", titleLabel: "Thème de la campagne",
    owner: "pilot_id", due: "date", done: ["Réalisée", "Clôturée"],
    statuses: [{ value: "Planifiée", tone: "neutral" }, { value: "En cours", tone: "info" }, { value: "Réalisée", tone: "success" }, { value: "Clôturée", tone: "neutral" }],
    actions: [
      { label: "Lancer", to: "En cours", from: ["Planifiée"], tone: "primary" },
      { label: "Enregistrer une participation", from: ["En cours"], reason: "Nom du participant" },
      { label: "Saisir le taux de réalisation", from: ["En cours", "Réalisée"], number: { key: "rate", label: "Taux de réalisation (%)" } },
      { label: "Terminer", to: "Réalisée", from: ["En cours"], tone: "primary" },
      { label: "Clôturer", to: "Clôturée", from: ["Réalisée"] },
    ],
    fields: [
      { key: "audience", label: "Public", type: "text", column: true },
      { key: "date", label: "Date", type: "date", column: true },
      { key: "rate", label: "Taux de réalisation (%)", type: "number", column: true },
      PILOT,
      { key: "materials", label: "Supports", type: "textarea" },
    ],
  },
  communication: {
    kind: "communication", label: "Communication", singular: "communication", prefix: "COM", titleLabel: "Objet",
    owner: "pilot_id", due: "date", done: ["Diffusée"],
    statuses: [{ value: "Brouillon", tone: "neutral" }, { value: "Planifiée", tone: "info" }, { value: "Diffusée", tone: "success" }],
    actions: [
      { label: "Planifier", to: "Planifiée", from: ["Brouillon"], tone: "primary", date: { key: "date", label: "Date de diffusion" } },
      { label: "Marquer diffusée", to: "Diffusée", from: ["Planifiée", "Brouillon"], tone: "primary", reason: "Preuve de diffusion" },
    ],
    fields: [
      { key: "audience", label: "Public", type: "select", options: ["Interne", "Externe", "Clients", "Autorités", "Tout le personnel"], column: true },
      { key: "channel", label: "Canal", type: "select", options: ["E-mail", "Affichage", "Réunion", "Intranet", "Courrier", "Site web"], column: true },
      { key: "date", label: "Date", type: "date", column: true },
      { key: "frequency", label: "Fréquence", type: "select", options: ["Ponctuelle", "Hebdomadaire", "Mensuelle", "Trimestrielle", "Annuelle"] },
      PILOT,
    ],
  },
  opcontrol: {
    kind: "opcontrol", label: "Maîtrise opérationnelle", singular: "contrôle", prefix: "CTL", titleLabel: "Activité / contrôle",
    owner: "pilot_id", due: "date", done: ["Conforme"],
    statuses: [{ value: "Planifié", tone: "neutral" }, { value: "Conforme", tone: "success" }, { value: "Non conforme", tone: "danger" }],
    actions: [
      { label: "Évaluer : conforme", to: "Conforme", from: ["Planifié", "Non conforme"], tone: "primary", reason: "Résultat / preuve" },
      { label: "Évaluer : non conforme", to: "Non conforme", from: ["Planifié"], tone: "danger", reason: "Écart constaté" },
    ],
    fields: [PROCESS_REL, SITE_REL, { key: "criteria", label: "Critères", type: "textarea", required: true }, { key: "date", label: "Date prévue", type: "date", column: true }, PILOT],
  },
  requirement: {
    kind: "requirement", label: "Exigences produits & services", singular: "exigence", prefix: "EXI", titleLabel: "Exigence",
    statuses: [{ value: "À analyser", tone: "warn" }, { value: "Revue", tone: "info" }, { value: "Satisfaite", tone: "success" }, { value: "Archivé", tone: "neutral" }],
    actions: [
      { label: "Revoir", to: "Revue", from: ["À analyser"], tone: "primary", reason: "Analyse de la capacité à satisfaire" },
      { label: "Marquer satisfaite", to: "Satisfaite", from: ["Revue"], tone: "primary", reason: "Preuve" },
      ...archive(["Satisfaite"], "Revue"),
    ],
    fields: [
      { key: "origin", label: "Origine", type: "select", options: ["Client", "Réglementaire", "Interne"], required: true, column: true },
      { key: "client", label: "Client / source", type: "text", column: true },
      PROCESS_REL,
      { key: "description", label: "Description", type: "textarea" },
    ],
  },
  obligation: {
    kind: "obligation", label: "Obligations de conformité", singular: "obligation", prefix: "OBL", titleLabel: "Obligation",
    owner: "pilot_id", due: "due_date", done: ["Conforme"],
    statuses: [{ value: "À évaluer", tone: "warn" }, { value: "Conforme", tone: "success" }, { value: "Partiellement conforme", tone: "warn" }, { value: "Non conforme", tone: "danger" }],
    actions: [
      { label: "Évaluer : conforme", to: "Conforme", tone: "primary", reason: "Preuve de conformité" },
      { label: "Évaluer : partiel", to: "Partiellement conforme", reason: "Écart" },
      { label: "Évaluer : non conforme", to: "Non conforme", tone: "danger", reason: "Écart" },
    ],
    fields: [{ key: "source", label: "Texte / source", type: "text", required: true, column: true }, NORM_F, PILOT, DUE, { key: "evidence", label: "Preuve", type: "textarea" }],
  },
  design: {
    kind: "design", label: "Conception & développement", singular: "projet", prefix: "CDV", titleLabel: "Projet",
    owner: "pilot_id", due: "due_date", done: ["Validé"],
    statuses: [{ value: "Planification", tone: "neutral" }, { value: "Entrées", tone: "info" }, { value: "Revue", tone: "info" }, { value: "Vérification", tone: "warn" }, { value: "Validé", tone: "success" }],
    actions: [
      { label: "Étape suivante : entrées", to: "Entrées", from: ["Planification"], tone: "primary" },
      { label: "Revue de conception", to: "Revue", from: ["Entrées"], tone: "primary", reason: "Conclusions de la revue" },
      { label: "Vérification", to: "Vérification", from: ["Revue"], tone: "primary" },
      { label: "Valider", to: "Validé", from: ["Vérification"], tone: "primary" },
      { label: "Enregistrer une modification", reason: "Description de la modification" },
    ],
    fields: [PILOT, DUE, { key: "needs", label: "Besoins", type: "textarea" }, { key: "outputs", label: "Éléments de sortie", type: "textarea" }, { key: "risks", label: "Risques", type: "textarea" }],
  },
  operation: {
    kind: "operation", label: "Production & prestation", singular: "opération", prefix: "OPE", titleLabel: "Produit / service",
    due: "date", done: ["Libérée"],
    statuses: [{ value: "Planifiée", tone: "neutral" }, { value: "En cours", tone: "info" }, { value: "Contrôlée", tone: "info" }, { value: "Libérée", tone: "success" }, { value: "Bloquée", tone: "danger" }],
    actions: [
      { label: "Démarrer", to: "En cours", from: ["Planifiée"], tone: "primary" },
      { label: "Contrôler", to: "Contrôlée", from: ["En cours"], tone: "primary", reason: "Résultat du contrôle" },
      { label: "Libérer", to: "Libérée", from: ["Contrôlée"], tone: "primary", reason: "Personne autorisée / décision" },
      { label: "Bloquer", to: "Bloquée", from: ["En cours", "Contrôlée"], tone: "danger", reason: "Motif" },
      { label: "Débloquer", to: "En cours", from: ["Bloquée"] },
    ],
    fields: [PROCESS_REL, SITE_REL, { key: "client", label: "Client / commande", type: "text", column: true }, { key: "date", label: "Date prévue", type: "date", column: true }, { key: "equipment_id", label: "Équipement", type: "relation", kinds: ["equipment"] }, { key: "requirements", label: "Critères", type: "textarea" }],
  },
  release: {
    kind: "release", label: "Libération", singular: "libération", prefix: "LIB", titleLabel: "Lot / prestation",
    done: ["Libéré", "Rejeté"],
    statuses: [{ value: "En attente", tone: "warn" }, { value: "Libéré", tone: "success" }, { value: "Dérogation", tone: "info" }, { value: "Rejeté", tone: "danger" }],
    actions: [
      { label: "Libérer", to: "Libéré", from: ["En attente"], tone: "primary", reason: "Résultat et personne autorisée" },
      { label: "Accepter sous dérogation", to: "Dérogation", from: ["En attente"], reason: "Justification de la dérogation" },
      { label: "Rejeter", to: "Rejeté", from: ["En attente"], tone: "danger", reason: "Motif", confirm: true },
    ],
    fields: [{ key: "operation_id", label: "Opération", type: "relation", kinds: ["operation"], column: true }, { key: "criteria", label: "Critères de libération", type: "textarea" }, { key: "date", label: "Date", type: "date", column: true }],
  },
  nonconforming: {
    kind: "nonconforming", label: "Sorties non conformes", singular: "sortie non conforme", prefix: "SNC", titleLabel: "Défaut constaté",
    done: ["Clôturée"],
    statuses: [{ value: "Détectée", tone: "danger" }, { value: "Isolée", tone: "warn" }, { value: "Corrigée", tone: "info" }, { value: "Dérogation", tone: "info" }, { value: "Rebutée", tone: "neutral" }, { value: "Clôturée", tone: "success" }],
    actions: [
      { label: "Isoler", to: "Isolée", from: ["Détectée"], tone: "primary" },
      { label: "Corriger", to: "Corrigée", from: ["Isolée"], tone: "primary", reason: "Correction réalisée" },
      { label: "Accepter sous dérogation", to: "Dérogation", from: ["Isolée"], reason: "Justification" },
      { label: "Rejeter / rebuter", to: "Rebutée", from: ["Isolée"], tone: "danger", reason: "Motif", confirm: true },
      { label: "Clôturer", to: "Clôturée", from: ["Corrigée", "Dérogation", "Rebutée"] },
    ],
    fields: [{ key: "operation_id", label: "Opération", type: "relation", kinds: ["operation", "release"], column: true }, { key: "impact", label: "Impact", type: "select", options: ["Faible", "Moyen", "Fort"], column: true }, { key: "decision", label: "Décision", type: "textarea" }],
  },
  supplier: {
    kind: "supplier", label: "Prestataires", singular: "fournisseur", prefix: "FRN", titleLabel: "Raison sociale",
    statuses: [{ value: "En évaluation", tone: "info" }, { value: "Approuvé", tone: "success" }, { value: "Sous surveillance", tone: "warn" }, { value: "Refusé", tone: "danger" }, { value: "Archivé", tone: "neutral" }],
    actions: [
      { label: "Évaluer", from: ["En évaluation", "Approuvé", "Sous surveillance"], tone: "primary", number: { key: "score", label: "Note d'évaluation (/20)" } },
      { label: "Réévaluation périodique", from: ["Approuvé", "Sous surveillance"], date: { key: "next_evaluation", label: "Prochaine évaluation" }, reason: "Synthèse de la réévaluation" },
      { label: "Approuver", to: "Approuvé", from: ["En évaluation", "Sous surveillance"], tone: "primary" },
      { label: "Mettre sous surveillance", to: "Sous surveillance", from: ["Approuvé"], reason: "Motif" },
      { label: "Refuser", to: "Refusé", from: ["En évaluation", "Sous surveillance"], tone: "danger", reason: "Motif du refus", confirm: true },
      ...archive(["Refusé", "Approuvé"], "En évaluation"),
    ],
    fields: [
      { key: "category", label: "Prestation", type: "text", column: true },
      { key: "criticality", label: "Criticité", type: "select", options: ["Faible", "Moyenne", "Élevée"], column: true },
      { key: "contact", label: "Contact", type: "text" },
      { key: "email", label: "E-mail", type: "email" },
      { key: "contract_end", label: "Fin de contrat", type: "date" },
      { key: "score", label: "Note d'évaluation (/20)", type: "number", column: true },
    ],
  },
  nc: {
    kind: "nc", label: "Non-conformités", singular: "non-conformité", prefix: "NC", titleLabel: "Écart constaté",
    owner: "pilot_id", done: ["Clôturée"],
    statuses: [{ value: "Ouverte", tone: "danger" }, { value: "En analyse", tone: "warn" }, { value: "En traitement", tone: "info" }, { value: "Vérification", tone: "info" }, { value: "Clôturée", tone: "success" }],
    actions: [
      { label: "Analyser la cause", to: "En analyse", from: ["Ouverte"], tone: "primary", reason: "Cause immédiate et cause racine" },
      { label: "Lancer le traitement", to: "En traitement", from: ["En analyse"], tone: "primary" },
      { label: "Soumettre pour vérification", to: "Vérification", from: ["En traitement"], tone: "primary", reason: "Preuve du traitement" },
      { label: "Vérifier l'efficacité et clôturer", to: "Clôturée", from: ["Vérification"], tone: "primary", confirm: true },
      { label: "Réouvrir", to: "Ouverte", from: ["Clôturée", "Vérification"], reason: "Raison de la réouverture" },
    ],
    fields: [
      { key: "origin", label: "Origine", type: "select", options: ["Audit", "Client", "Interne", "Fournisseur", "Réglementaire"], column: true },
      { key: "severity", label: "Gravité", type: "select", options: ["Mineure", "Majeure", "Critique"], required: true, column: true },
      PROCESS_REL, SITE_REL, PILOT,
      { key: "source_id", label: "Lié à", type: "relation", kinds: ["audit", "complaint", "supplier", "operation", "nonconforming", "opcontrol"] },
      { key: "description", label: "Description", type: "textarea" },
      { key: "root_cause", label: "Analyse des causes", type: "textarea" },
      { key: "correction", label: "Correction immédiate", type: "textarea" },
    ],
  },
  complaint: {
    kind: "complaint", label: "Réclamations", singular: "réclamation", prefix: "REC", titleLabel: "Objet",
    done: ["Clôturée"],
    statuses: [{ value: "Reçue", tone: "warn" }, { value: "En traitement", tone: "info" }, { value: "Répondue", tone: "success" }, { value: "Clôturée", tone: "neutral" }],
    actions: [
      { label: "Prendre en charge", to: "En traitement", from: ["Reçue"], tone: "primary" },
      { label: "Répondre au client", to: "Répondue", from: ["En traitement"], tone: "primary", reason: "Réponse apportée" },
      { label: "Clôturer", to: "Clôturée", from: ["Répondue"] },
      { label: "Réouvrir", to: "En traitement", from: ["Clôturée"], reason: "Motif" },
    ],
    fields: [
      { key: "client", label: "Client", type: "text", required: true, column: true },
      { key: "channel", label: "Canal", type: "select", options: ["E-mail", "Téléphone", "Courrier", "Plateforme", "Visite"], column: true },
      { key: "date", label: "Date de réception", type: "date", column: true },
      { key: "description", label: "Description", type: "textarea" },
    ],
  },
  suggestion: {
    kind: "suggestion", label: "Suggestions", singular: "suggestion", prefix: "SUG", titleLabel: "Idée d'amélioration",
    done: ["Acceptée", "Refusée"],
    statuses: [{ value: "Proposée", tone: "info" }, { value: "Acceptée", tone: "success" }, { value: "Refusée", tone: "neutral" }],
    actions: [
      { label: "Accepter", to: "Acceptée", from: ["Proposée"], tone: "primary" },
      { label: "Refuser", to: "Refusée", from: ["Proposée"], tone: "danger", reason: "Motif du refus" },
    ],
    fields: [PROCESS_REL, { key: "author", label: "Auteur", type: "text", column: true }, { key: "gain", label: "Gain attendu", type: "text", column: true }, { key: "description", label: "Description", type: "textarea" }],
  },
  improvement: {
    kind: "improvement", label: "Projets d'amélioration", singular: "projet", prefix: "AMC", titleLabel: "Projet",
    owner: "pilot_id", due: "due_date", done: ["Terminé"],
    statuses: [{ value: "Lancé", tone: "info" }, { value: "En cours", tone: "info" }, { value: "Terminé", tone: "success" }],
    actions: [
      { label: "Démarrer", to: "En cours", from: ["Lancé"], tone: "primary" },
      { label: "Mettre à jour la progression", from: ["En cours"], number: { key: "progress", label: "Progression (%)" } },
      { label: "Terminer", to: "Terminé", from: ["En cours"], tone: "primary", reason: "Résultat obtenu" },
    ],
    fields: [{ key: "suggestion_id", label: "Suggestion d'origine", type: "relation", kinds: ["suggestion"], column: true }, PILOT, DUE, { key: "progress", label: "Progression (%)", type: "number", column: true }, { key: "gain", label: "Gains attendus", type: "textarea" }],
  },
  audit: {
    kind: "audit", label: "Audits internes", singular: "audit", prefix: "AUD", titleLabel: "Intitulé de l'audit",
    owner: "auditor_id", due: "date", done: ["Clôturé"],
    statuses: [{ value: "Planifié", tone: "neutral" }, { value: "En préparation", tone: "info" }, { value: "En cours", tone: "warn" }, { value: "Rapport", tone: "info" }, { value: "Clôturé", tone: "success" }],
    actions: [
      { label: "Préparer", to: "En préparation", from: ["Planifié"], tone: "primary", reason: "Plan d'audit / check-list" },
      { label: "Démarrer l'audit", to: "En cours", from: ["En préparation"], tone: "primary" },
      { label: "Saisir un constat", from: ["En cours"], reason: "Constat (conformité, observation ou écart)" },
      { label: "Rédiger le rapport", to: "Rapport", from: ["En cours"], tone: "primary" },
      { label: "Clôturer l'audit", to: "Clôturé", from: ["Rapport"], tone: "primary", confirm: true },
    ],
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Interne", "Fournisseur", "Certification", "Surveillance"], column: true },
      NORM_F,
      SITE_REL,
      PROCESS_REL,
      { key: "date", label: "Date", type: "date", required: true, column: true },
      { key: "auditor_id", label: "Auditeur", type: "relation", kinds: ["collaborator"] },
      { key: "scope", label: "Champ et critères", type: "textarea" },
      { key: "findings", label: "Constats", type: "textarea" },
    ],
  },
  auditor_eval: {
    kind: "auditor_eval", label: "Évaluation des auditeurs", singular: "évaluation", prefix: "EVA", titleLabel: "Évaluation",
    done: ["Évalué"],
    statuses: [{ value: "À évaluer", tone: "warn" }, { value: "Évalué", tone: "success" }],
    actions: [{ label: "Évaluer", to: "Évalué", from: ["À évaluer", "Évalué"], tone: "primary", number: { key: "score", label: "Note (/20)" } }],
    fields: [{ key: "auditor_id", label: "Auditeur", type: "relation", kinds: ["collaborator"], required: true, column: true }, { key: "audit_id", label: "Audit", type: "relation", kinds: ["audit"], column: true }, { key: "score", label: "Note (/20)", type: "number", column: true }, { key: "needs", label: "Besoins de formation", type: "textarea" }],
  },
  indicator: {
    kind: "indicator", label: "Indicateurs", singular: "indicateur", prefix: "KPI", titleLabel: "Nom de l'indicateur",
    owner: "pilot_id",
    statuses: [{ value: "Actif", tone: "success" }, { value: "Suspendu", tone: "neutral" }],
    actions: [
      { label: "Saisir une valeur", from: ["Actif"], tone: "primary", number: { key: "value", label: "Valeur de la période" } },
      { label: "Suspendre", to: "Suspendu", from: ["Actif"], confirm: true },
      { label: "Réactiver", to: "Actif", from: ["Suspendu"] },
    ],
    fields: [
      PROCESS_REL,
      { key: "method", label: "Méthode de calcul", type: "text" },
      { key: "unit", label: "Unité", type: "text" },
      { key: "target", label: "Cible", type: "number", required: true, column: true },
      { key: "threshold", label: "Seuil d'alerte", type: "number" },
      { key: "value", label: "Dernière valeur", type: "number", column: true },
      { key: "frequency", label: "Fréquence", type: "select", options: ["Mensuelle", "Trimestrielle", "Semestrielle", "Annuelle"], column: true },
      PILOT,
    ],
  },
  survey: {
    kind: "survey", label: "Évaluations PIP", singular: "enquête", prefix: "ENQ", titleLabel: "Enquête",
    owner: "pilot_id", due: "due_date", done: ["Rapport publié"],
    statuses: [{ value: "Brouillon", tone: "neutral" }, { value: "Diffusée", tone: "info" }, { value: "Clôturée", tone: "warn" }, { value: "Rapport publié", tone: "success" }],
    actions: [
      { label: "Diffuser", to: "Diffusée", from: ["Brouillon"], tone: "primary" },
      { label: "Relancer", from: ["Diffusée"], reason: "Destinataires relancés" },
      { label: "Saisir le score", from: ["Diffusée", "Clôturée"], number: { key: "score", label: "Score de satisfaction (%)" } },
      { label: "Clôturer la collecte", to: "Clôturée", from: ["Diffusée"] },
      { label: "Générer le rapport", to: "Rapport publié", from: ["Clôturée"], tone: "primary", reason: "Synthèse et actions" },
    ],
    fields: [
      { key: "audience", label: "Public", type: "select", options: ["Clients", "Personnel", "Fournisseurs", "Autres parties intéressées"], required: true, column: true },
      { key: "participation", label: "Taux de participation (%)", type: "number", column: true },
      { key: "score", label: "Score (%)", type: "number", column: true },
      DUE, PILOT,
    ],
  },
  process_review: {
    kind: "process_review", label: "Revue des processus", singular: "revue de processus", prefix: "RVP", titleLabel: "Intitulé",
    due: "date", done: ["Tenue"],
    statuses: [{ value: "Planifiée", tone: "neutral" }, { value: "Tenue", tone: "success" }],
    actions: [{ label: "Enregistrer la revue", to: "Tenue", from: ["Planifiée"], tone: "primary", reason: "Décisions" }],
    fields: [{ ...PROCESS_REL, required: true }, { key: "date", label: "Date", type: "date", column: true }, { key: "performance", label: "Performance et objectifs", type: "textarea" }, { key: "decisions", label: "Décisions", type: "textarea" }],
  },
  review: {
    kind: "review", label: "Revues de direction", singular: "revue", prefix: "RDD", titleLabel: "Intitulé",
    due: "date", done: ["Rapport publié"],
    statuses: [{ value: "Planifiée", tone: "neutral" }, { value: "Invitations envoyées", tone: "info" }, { value: "Tenue", tone: "info" }, { value: "Rapport publié", tone: "success" }],
    actions: [
      { label: "Inviter les participants", to: "Invitations envoyées", from: ["Planifiée"], tone: "primary" },
      { label: "Enregistrer la réunion", to: "Tenue", from: ["Invitations envoyées", "Planifiée"], tone: "primary", reason: "Décisions prises" },
      { label: "Publier le rapport", to: "Rapport publié", from: ["Tenue"], tone: "primary" },
    ],
    fields: [
      { key: "date", label: "Date", type: "date", required: true, column: true },
      { key: "participants", label: "Participants", type: "text", column: true },
      { key: "inputs", label: "Éléments d'entrée", type: "textarea" },
      { key: "decisions", label: "Décisions", type: "textarea" },
    ],
  },
  competence: {
    kind: "competence", label: "Compétences", singular: "compétence", prefix: "CMP", titleLabel: "Compétence",
    done: ["Acquise"],
    statuses: [{ value: "À acquérir", tone: "warn" }, { value: "En cours", tone: "info" }, { value: "Acquise", tone: "success" }],
    actions: [{ label: "Démarrer", to: "En cours", from: ["À acquérir"] }, { label: "Valider l'acquisition", to: "Acquise", from: ["En cours", "À acquérir"], tone: "primary", reason: "Preuve" }, { label: "Réévaluer", to: "Acquise", from: ["Acquise"], tone: "primary", reason: "Résultat de la réévaluation", date: { key: "next_review", label: "Prochaine réévaluation" } }, { label: "Remettre à acquérir", to: "À acquérir", from: ["Acquise"], reason: "Motif" }],
    due: "next_review",
    fields: [
      { key: "collaborator_id", label: "Collaborateur", type: "relation", kinds: ["collaborator"], column: true },
      { key: "next_review", label: "Prochaine réévaluation", type: "date" },
      { key: "job_id", label: "Poste", type: "relation", kinds: ["job"] },
      { key: "level", label: "Niveau", type: "select", options: ["Débutant", "Confirmé", "Expert"], column: true },
    ],
  },
  training: {
    kind: "training", label: "Formations", singular: "formation", prefix: "FOR", titleLabel: "Intitulé de la formation",
    owner: "collaborator_id", due: "date", done: ["Réalisée", "Évaluée"],
    statuses: [{ value: "Planifiée", tone: "neutral" }, { value: "Réalisée", tone: "success" }, { value: "Évaluée", tone: "success" }, { value: "Annulée", tone: "neutral" }],
    actions: [
      { label: "Marquer réalisée", to: "Réalisée", from: ["Planifiée"], tone: "primary", reason: "Attestation / preuve" },
      { label: "Évaluer l'efficacité", to: "Évaluée", from: ["Réalisée"], tone: "primary", reason: "Évaluation" },
      { label: "Annuler", to: "Annulée", from: ["Planifiée"], tone: "danger", confirm: true },
    ],
    fields: [
      { key: "collaborator_id", label: "Collaborateur", type: "relation", kinds: ["collaborator"], column: true },
      { key: "date", label: "Date", type: "date", column: true },
      { key: "provider", label: "Organisme", type: "text", column: true },
    ],
  },
  habilitation: {
    kind: "habilitation", label: "Habilitations", singular: "habilitation", prefix: "HAB", titleLabel: "Habilitation",
    owner: "collaborator_id", due: "expiry",
    statuses: [{ value: "Valide", tone: "success" }, { value: "À renouveler", tone: "warn" }, { value: "Expirée", tone: "danger" }],
    actions: [
      { label: "Renouveler", to: "Valide", tone: "primary", date: { key: "expiry", label: "Nouvelle date d'expiration" } },
      { label: "Marquer à renouveler", to: "À renouveler", from: ["Valide"] },
      { label: "Marquer expirée", to: "Expirée", from: ["Valide", "À renouveler"], tone: "danger", confirm: true },
    ],
    fields: [{ key: "collaborator_id", label: "Collaborateur", type: "relation", kinds: ["collaborator"], required: true, column: true }, { key: "expiry", label: "Date d'expiration", type: "date", required: true, column: true }],
  },
  equipment: {
    kind: "equipment", label: "Ressources & équipements", singular: "équipement", prefix: "EQP", titleLabel: "Équipement / ressource",
    owner: "pilot_id", due: "next_maintenance",
    statuses: [{ value: "En service", tone: "success" }, { value: "Maintenance", tone: "warn" }, { value: "Hors service", tone: "danger" }, { value: "Archivé", tone: "neutral" }],
    actions: [
      { label: "Planifier maintenance", tone: "primary", date: { key: "next_maintenance", label: "Date de maintenance" } },
      { label: "Envoyer en maintenance", to: "Maintenance", from: ["En service"] },
      { label: "Remettre en service", to: "En service", from: ["Maintenance", "Hors service"], tone: "primary", reason: "Rapport d'intervention" },
      { label: "Déclarer hors service", to: "Hors service", from: ["En service", "Maintenance"], tone: "danger", reason: "Motif" },
      ...archive(["Hors service"], "En service"),
    ],
    fields: [
      { key: "category", label: "Catégorie", type: "select", options: ["Machine", "Moyen de mesure", "Véhicule", "Local", "Informatique", "EPI"], column: true },
      { key: "serial", label: "N° de série", type: "text", column: true },
      SITE_REL, PILOT,
      { key: "next_maintenance", label: "Prochaine maintenance", type: "date", column: true },
      { key: "notes", label: "Notes", type: "textarea" },
    ],
  },
  certification: {
    kind: "certification", label: "Certifications", singular: "certification", prefix: "CRT", titleLabel: "Intitulé",
    due: "expiry",
    statuses: [{ value: "En préparation", tone: "info" }, { value: "Obtenue", tone: "success" }, { value: "Expirée", tone: "danger" }],
    actions: [
      { label: "Marquer obtenue", to: "Obtenue", from: ["En préparation"], tone: "primary", date: { key: "expiry", label: "Date d'expiration du certificat" } },
      { label: "Renouveler", to: "Obtenue", from: ["Expirée", "Obtenue"], date: { key: "expiry", label: "Nouvelle date d'expiration" } },
    ],
    fields: [{ ...NORM_F, required: true }, { key: "body", label: "Organisme certificateur", type: "text", column: true }, { key: "expiry", label: "Date d'expiration", type: "date", column: true }],
  },

  // ---------- Norm-specific kinds ----------
  env_aspect: {
    kind: "env_aspect", label: "Aspects environnementaux", singular: "aspect", prefix: "ENV", titleLabel: "Aspect",
    statuses: [{ value: "Identifié", tone: "warn" }, { value: "Significatif", tone: "danger" }, { value: "Maîtrisé", tone: "success" }],
    actions: [{ label: "Classer significatif", to: "Significatif", from: ["Identifié"], tone: "danger" }, { label: "Marquer maîtrisé", to: "Maîtrisé", tone: "primary", reason: "Mesures de maîtrise" }],
    fields: [PROCESS_REL, { key: "impact", label: "Impact environnemental", type: "text", column: true }, { key: "condition", label: "Condition", type: "select", options: ["Normale", "Anormale", "Urgence"], column: true }, { key: "score", label: "Cotation", type: "number", column: true }],
  },
  emergency: {
    kind: "emergency", label: "Situations d'urgence", singular: "situation d'urgence", prefix: "URG", titleLabel: "Situation",
    due: "next_drill",
    statuses: [{ value: "Identifiée", tone: "warn" }, { value: "Plan prêt", tone: "info" }, { value: "Exercice réalisé", tone: "success" }],
    actions: [{ label: "Valider le plan", to: "Plan prêt", from: ["Identifiée"], tone: "primary" }, { label: "Enregistrer un exercice", to: "Exercice réalisé", tone: "primary", reason: "Bilan de l'exercice" }],
    fields: [SITE_REL, { key: "next_drill", label: "Prochain exercice", type: "date", column: true }, { key: "plan", label: "Plan de réponse", type: "textarea" }],
  },
  hazard: {
    kind: "hazard", label: "Dangers & DUER", singular: "danger", prefix: "DGR", titleLabel: "Danger / situation dangereuse",
    owner: "pilot_id",
    statuses: [{ value: "Identifié", tone: "warn" }, { value: "Évalué", tone: "info" }, { value: "Maîtrisé", tone: "success" }],
    actions: [{ label: "Évaluer", to: "Évalué", tone: "primary", number: { key: "score", label: "Cotation du risque" } }, { label: "Marquer maîtrisé", to: "Maîtrisé", tone: "primary", reason: "Mesures de prévention" }],
    fields: [SITE_REL, { key: "unit", label: "Unité de travail", type: "text", column: true }, { key: "score", label: "Cotation", type: "number", column: true }, PILOT, { key: "measures", label: "Mesures de prévention", type: "textarea" }],
  },
  incident: {
    kind: "incident", label: "Accidents & incidents", singular: "accident / incident", prefix: "INC", titleLabel: "Événement",
    done: ["Clôturé"],
    statuses: [{ value: "Déclaré", tone: "danger" }, { value: "En analyse", tone: "warn" }, { value: "Clôturé", tone: "success" }],
    actions: [{ label: "Analyser", to: "En analyse", from: ["Déclaré"], tone: "primary", reason: "Analyse des causes" }, { label: "Clôturer", to: "Clôturé", from: ["En analyse"], confirm: true }],
    fields: [SITE_REL, { key: "date", label: "Date", type: "date", required: true, column: true }, { key: "type", label: "Type", type: "select", options: ["Accident avec arrêt", "Accident sans arrêt", "Presque-accident", "Incident environnemental"], column: true }, { key: "description", label: "Description", type: "textarea" }],
  },
  energy_use: {
    kind: "energy_use", label: "Usages énergétiques", singular: "usage énergétique", prefix: "ENR", titleLabel: "Usage",
    statuses: [{ value: "Suivi", tone: "info" }, { value: "Significatif (USE)", tone: "warn" }, { value: "Optimisé", tone: "success" }],
    actions: [{ label: "Saisir la consommation", tone: "primary", number: { key: "consumption", label: "Consommation de la période" } }, { label: "Classer USE", to: "Significatif (USE)", from: ["Suivi"] }, { label: "Marquer optimisé", to: "Optimisé", reason: "Gain obtenu" }],
    fields: [SITE_REL, { key: "source", label: "Source d'énergie", type: "select", options: ["Électricité", "Gasoil", "Gaz", "Solaire", "Autre"], column: true }, { key: "consumption", label: "Consommation", type: "number", column: true }, { key: "baseline", label: "Situation de référence", type: "number" }],
  },
  haccp: {
    kind: "haccp", label: "Plan HACCP / CCP", singular: "point de maîtrise", prefix: "CCP", titleLabel: "Étape / danger",
    statuses: [{ value: "PRP", tone: "neutral" }, { value: "PRPo", tone: "info" }, { value: "CCP", tone: "warn" }, { value: "Validé", tone: "success" }],
    actions: [{ label: "Valider la maîtrise", to: "Validé", tone: "primary", reason: "Validation (preuve)" }, { label: "Saisir une mesure", number: { key: "last_value", label: "Valeur mesurée" } }],
    fields: [PROCESS_REL, { key: "hazard", label: "Danger", type: "select", options: ["Biologique", "Chimique", "Physique", "Allergène"], column: true }, { key: "limit", label: "Limite critique", type: "text", column: true }, { key: "last_value", label: "Dernière mesure", type: "number" }, { key: "corrective", label: "Action corrective", type: "textarea" }],
  },
  infosec: {
    kind: "infosec", label: "Actifs & risques SI", singular: "actif", prefix: "SI", titleLabel: "Actif informationnel",
    owner: "pilot_id",
    statuses: [{ value: "Identifié", tone: "warn" }, { value: "Évalué", tone: "info" }, { value: "Traité", tone: "success" }],
    actions: [{ label: "Évaluer", to: "Évalué", tone: "primary", number: { key: "score", label: "Niveau de risque" } }, { label: "Marquer traité", to: "Traité", tone: "primary", reason: "Mesures (Annexe A)" }],
    fields: [{ key: "type", label: "Type", type: "select", options: ["Données", "Logiciel", "Matériel", "Service", "Personnel"], column: true }, { key: "classification", label: "Classification", type: "select", options: ["Public", "Interne", "Confidentiel", "Secret"], column: true }, { key: "score", label: "Niveau de risque", type: "number", column: true }, PILOT],
  },

  // ---------- Platform records ----------
  norm: {
    kind: "norm", label: "Normes", singular: "norme", prefix: "NRM", titleLabel: "Norme",
    statuses: [{ value: "Active", tone: "success" }, { value: "Expirée", tone: "danger" }, { value: "Désactivée", tone: "neutral" }],
    fields: [{ key: "activated_at", label: "Activée le", type: "date" }, { key: "expires_at", label: "Expire le", type: "date" }],
  },
  subscription_request: {
    kind: "subscription_request", label: "Demandes d'abonnement", singular: "demande", prefix: "ABO", titleLabel: "Demande",
    statuses: [{ value: "En attente", tone: "warn" }, { value: "Payée", tone: "success" }, { value: "Échouée", tone: "danger" }],
    fields: [{ key: "offer", label: "Offre", type: "text" }, { key: "amount", label: "Montant", type: "text" }],
  },
} satisfies Record<string, KindConfig>;

export const KINDS: Record<string, KindConfig> = K;

const S = (slug: string, title: string, description: string, ...kinds: [KindConfig, ...KindConfig[]]): SectionConfig => ({ slug, title, description, kinds });

export const SECTIONS: Record<string, SectionConfig> = {
  sites: S("sites", "Sites", "Établissements, usines et agences de l'entreprise.", K.site),
  // Contexte
  contexte: S("contexte", "Compréhension de l'organisme", "Enjeux internes et externes, SWOT et PESTEL.", K.context),
  "parties-interessees": S("parties-interessees", "Parties intéressées", "Besoins, attentes et exigences des parties intéressées.", K.party),
  perimetre: S("perimetre", "Domaine d'application", "Activités, sites, limites et exclusions du système.", K.scope),
  // Leadership
  politique: S("politique", "Politique QHSE", "Engagements de la direction, versions et diffusion.", K.policy),
  organigramme: S("organigramme", "Organigramme", "Services, rattachements et responsables.", K.orgunit),
  collaborateurs: S("collaborateurs", "Liste du personnel", "Collaborateurs, rôles, sites et statut.", K.collaborator),
  "fiches-poste": S("fiches-poste", "Fiches de poste", "Missions, responsabilités et compétences par poste.", K.job),
  responsabilites: S("responsabilites", "Fiches de responsabilité", "Responsabilités liées aux processus, documents et objectifs.", K.responsibility),
  // Planification
  risques: S("risques", "Risques & opportunités", "Identification, évaluation et traitement.", K.risk),
  objectifs: S("objectifs", "Objectifs qualité", "Cibles mesurables, indicateurs et progression.", K.objective),
  actions: S("actions", "Plans d'actions", "Actions issues des NC, risques, audits, objectifs et revues.", K.action),
  "aspects-environnementaux": S("aspects-environnementaux", "Aspects environnementaux", "ISO 14001 — aspects, impacts et significativité.", K.env_aspect),
  dangers: S("dangers", "Dangers & DUER", "ISO 45001 — évaluation des risques professionnels.", K.hazard),
  energie: S("energie", "Revue énergétique", "ISO 50001 — usages énergétiques significatifs et consommations.", K.energy_use),
  "securite-information": S("securite-information", "Actifs & risques SI", "ISO 27001 — actifs informationnels et traitement des risques.", K.infosec),
  // Support
  equipements: S("equipements", "Ressources", "Équipements, locaux, moyens de mesure et maintenance.", K.equipment),
  competences: S("competences", "Compétences", "Compétences, formations et habilitations.", K.competence, K.training, K.habilitation),
  sensibilisation: S("sensibilisation", "Sensibilisation", "Campagnes, participants et taux de réalisation.", K.awareness),
  communication: S("communication", "Communication", "Communications internes et externes.", K.communication),
  documents: S("documents", "Information documentée", "Bibliothèque, versions et circuit de validation.", K.document),
  // Réalisation
  processus: S("processus", "Processus", "Cartographie, pilotes et interactions des processus.", K.process),
  procedures: S("procedures", "Procédures", "Consignes de travail rattachées aux processus.", K.procedure),
  "maitrise-operationnelle": S("maitrise-operationnelle", "Planification & maîtrise opérationnelle", "Activités, critères et contrôles.", K.opcontrol),
  exigences: S("exigences", "Exigences produits & services", "Exigences clients, réglementaires et internes.", K.requirement),
  obligations: S("obligations", "Obligations de conformité", "Veille réglementaire et évaluation de conformité.", K.obligation),
  conception: S("conception", "Conception & développement", "Étapes, revues, vérifications et validations.", K.design),
  fournisseurs: S("fournisseurs", "Gestion des prestataires", "Qualification, évaluation et suivi des fournisseurs.", K.supplier),
  realisation: S("realisation", "Production & prestation", "Commandes, contrôles et libération.", K.operation),
  liberation: S("liberation", "Libération des produits & services", "Décisions de libération et dérogations.", K.release),
  "sorties-non-conformes": S("sorties-non-conformes", "Maîtrise des sorties non conformes", "Isolement, correction, dérogation et clôture.", K.nonconforming),
  urgences: S("urgences", "Situations d'urgence", "ISO 14001 / 45001 — préparation et exercices.", K.emergency),
  incidents: S("incidents", "Accidents & incidents", "ISO 45001 — déclaration et analyse des événements.", K.incident),
  haccp: S("haccp", "Plan HACCP", "ISO 22000 — PRP, PRPo et CCP.", K.haccp),
  // Évaluation
  indicateurs: S("indicateurs", "Indicateurs", "Définition, cibles, valeurs et tendances.", K.indicator),
  enquetes: S("enquetes", "Évaluations PIP", "Satisfaction des clients, du personnel et des parties intéressées.", K.survey),
  "revue-processus": S("revue-processus", "Revue des processus", "Performance, objectifs et décisions par processus.", K.process_review),
  audits: S("audits", "Audits internes", "Programme, préparation, réalisation et rapport.", K.audit),
  auditeurs: S("auditeurs", "Évaluation des auditeurs", "Qualification et performance des auditeurs.", K.auditor_eval),
  "revue-direction": S("revue-direction", "Revue de direction", "Invitations, entrées, décisions et rapport.", K.review),
  // Amélioration
  "non-conformites": S("non-conformites", "Non-conformités & actions correctives", "Déclaration, analyse, traitement et efficacité.", K.nc),
  reclamations: S("reclamations", "Réclamations", "Réclamations clients et réponses.", K.complaint),
  amelioration: S("amelioration", "Amélioration continue", "Suggestions et projets d'amélioration.", K.suggestion, K.improvement),
  // Other
  certifications: S("certifications", "Certifications", "Certifications obtenues et échéances.", K.certification),
};

/** Which section hosts a kind (for "open" links). */
export function sectionForKind(kind: string): string | undefined {
  return Object.values(SECTIONS).find((s) => s.kinds.some((k) => k.kind === kind))?.slug;
}

export function toneOf(kind: string, status: string): StatusTone {
  return KINDS[kind]?.statuses.find((s) => s.value === status)?.tone ?? "neutral";
}

export const TONE_CLASS: Record<StatusTone, string> = {
  neutral: "bg-secondary text-muted-foreground",
  info: "bg-primary-soft text-primary",
  warn: "bg-primary/15 text-primary-dark",
  success: "bg-primary text-primary-foreground",
  danger: "bg-destructive/10 text-destructive",
};

export function availableActions(kind: string, status: string): Transition[] {
  return (KINDS[kind]?.actions ?? []).filter((a) => !a.from || a.from.includes(status));
}

// ---------- Norms catalog ----------
export type NormInfo = { code: (typeof NORM_CODES)[number]; name: string; domain: string; requirements: string[]; kinds: string[] };

export const NORM_CATALOG: NormInfo[] = [
  { code: "ISO 9001", name: "Management de la qualité", domain: "Qualité", requirements: ["Contexte et parties intéressées", "Politique qualité", "Risques et objectifs", "Information documentée", "Maîtrise opérationnelle", "Audits et revue de direction", "Amélioration"], kinds: ["context", "party", "scope", "policy", "risk", "objective", "process", "document", "audit", "review", "nc"] },
  { code: "ISO 14001", name: "Management environnemental", domain: "Environnement", requirements: ["Aspects environnementaux", "Obligations de conformité", "Situations d'urgence", "Surveillance environnementale"], kinds: ["env_aspect", "obligation", "emergency"] },
  { code: "ISO 45001", name: "Santé et sécurité au travail", domain: "Sécurité", requirements: ["Identification des dangers (DUER)", "Accidents et incidents", "Situations d'urgence", "Consultation des travailleurs"], kinds: ["hazard", "incident", "emergency"] },
  { code: "ISO 50001", name: "Management de l'énergie", domain: "Énergie", requirements: ["Revue énergétique", "Usages énergétiques significatifs", "Situation de référence et IPÉ"], kinds: ["energy_use"] },
  { code: "ISO 22000", name: "Sécurité des denrées alimentaires", domain: "HACCP", requirements: ["Programmes prérequis (PRP)", "Analyse des dangers", "Plan HACCP / CCP", "Traçabilité"], kinds: ["haccp"] },
  { code: "ISO 27001", name: "Sécurité de l'information", domain: "Sécurité de l'information", requirements: ["Inventaire des actifs", "Appréciation des risques SI", "Déclaration d'applicabilité"], kinds: ["infosec"] },
];
