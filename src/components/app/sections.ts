// Configuration of every QHSE section: entity kinds, fields, statuses and relations.

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

export type KindConfig = {
  kind: string;
  label: string; // plural
  singular: string;
  prefix: string;
  titleLabel: string;
  statuses: { value: string; tone: StatusTone }[];
  /** Linear workflow: "advance" button moves to next status. */
  workflow?: boolean;
  fields: Field[];
};

export type SectionConfig = {
  slug: string;
  title: string;
  description: string;
  kinds: [KindConfig, ...KindConfig[]];
};

const PROCESS_REL: Field = { key: "process_id", label: "Processus", type: "relation", kinds: ["process"], column: true };
const SITE_REL: Field = { key: "site_id", label: "Site", type: "relation", kinds: ["site"], column: true };
const PILOT: Field = { key: "pilot_id", label: "Responsable", type: "relation", kinds: ["collaborator"], column: true };
const NORMS = ["ISO 9001", "ISO 14001", "ISO 45001", "ISO 27001", "ISO 22000", "ISO 50001"];

const K = {
  site: {
    kind: "site", label: "Sites", singular: "site", prefix: "SIT", titleLabel: "Nom du site",
    statuses: [{ value: "Actif", tone: "success" }, { value: "Inactif", tone: "neutral" }],
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Siège", "Usine", "Agence", "Bureau", "Entrepôt"], column: true },
      { key: "city", label: "Ville", type: "text", column: true },
      { key: "address", label: "Adresse", type: "text" },
      { key: "manager_id", label: "Responsable de site", type: "relation", kinds: ["collaborator"], column: true },
    ],
  },
  collaborator: {
    kind: "collaborator", label: "Collaborateurs", singular: "collaborateur", prefix: "COL", titleLabel: "Nom complet",
    statuses: [{ value: "Invité", tone: "info" }, { value: "Actif", tone: "success" }, { value: "Suspendu", tone: "danger" }],
    fields: [
      { key: "email", label: "E-mail", type: "email", required: true, column: true },
      { key: "job", label: "Fonction", type: "text", column: true },
      { key: "role", label: "Rôle", type: "select", options: ["Administrateur", "Responsable de site", "Collaborateur", "Auditeur"], column: true },
      SITE_REL,
      { key: "phone", label: "Téléphone", type: "text" },
    ],
  },
  context: {
    kind: "context", label: "Enjeux", singular: "enjeu", prefix: "CTX", titleLabel: "Enjeu",
    statuses: [{ value: "Brouillon", tone: "neutral" }, { value: "Validé", tone: "success" }, { value: "À revoir", tone: "warn" }],
    fields: [
      { key: "category", label: "Analyse", type: "select", options: ["SWOT — Force", "SWOT — Faiblesse", "SWOT — Opportunité", "SWOT — Menace", "PESTEL — Politique", "PESTEL — Économique", "PESTEL — Social", "PESTEL — Technologique", "PESTEL — Environnemental", "PESTEL — Légal"], required: true, column: true },
      { key: "impact", label: "Impact", type: "select", options: ["Faible", "Moyen", "Fort"], column: true },
      { key: "description", label: "Description", type: "textarea" },
    ],
  },
  party: {
    kind: "party", label: "Parties intéressées", singular: "partie intéressée", prefix: "PI", titleLabel: "Partie intéressée",
    statuses: [{ value: "Active", tone: "success" }, { value: "À revoir", tone: "warn" }],
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Client", "Salarié", "Fournisseur", "Actionnaire", "Autorité", "Riverain", "Partenaire"], column: true },
      { key: "expectations", label: "Besoins et attentes", type: "textarea" },
      { key: "influence", label: "Influence", type: "select", options: ["Faible", "Moyenne", "Forte"], column: true },
    ],
  },
  scope: {
    kind: "scope", label: "Périmètre", singular: "périmètre", prefix: "PER", titleLabel: "Intitulé du périmètre",
    statuses: [{ value: "Brouillon", tone: "neutral" }, { value: "Approuvé", tone: "success" }],
    workflow: true,
    fields: [
      { key: "norm", label: "Norme", type: "select", options: NORMS, column: true },
      { key: "activities", label: "Activités couvertes", type: "textarea" },
      { key: "exclusions", label: "Exclusions justifiées", type: "textarea" },
    ],
  },
  risk: {
    kind: "risk", label: "Risques & opportunités", singular: "risque", prefix: "RSK", titleLabel: "Intitulé",
    statuses: [{ value: "Identifié", tone: "warn" }, { value: "En traitement", tone: "info" }, { value: "Maîtrisé", tone: "success" }],
    workflow: true,
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Risque", "Opportunité"], required: true, column: true },
      PROCESS_REL,
      { key: "probability", label: "Probabilité (1-5)", type: "number", column: true },
      { key: "gravity", label: "Gravité (1-5)", type: "number", column: true },
      { key: "treatment", label: "Traitement prévu", type: "textarea" },
    ],
  },
  objective: {
    kind: "objective", label: "Objectifs", singular: "objectif", prefix: "OBJ", titleLabel: "Objectif",
    statuses: [{ value: "En cours", tone: "info" }, { value: "Atteint", tone: "success" }, { value: "Non atteint", tone: "danger" }],
    fields: [
      PROCESS_REL,
      { key: "target", label: "Cible", type: "text", column: true },
      { key: "progress", label: "Avancement (%)", type: "number", column: true },
      { key: "due_date", label: "Échéance", type: "date", column: true },
      PILOT,
    ],
  },
  action: {
    kind: "action", label: "Actions", singular: "action", prefix: "ACT", titleLabel: "Action à réaliser",
    statuses: [{ value: "À faire", tone: "neutral" }, { value: "En cours", tone: "info" }, { value: "Terminée", tone: "success" }, { value: "Vérifiée", tone: "success" }],
    workflow: true,
    fields: [
      { key: "origin_id", label: "Origine", type: "relation", kinds: ["nc", "risk", "audit", "objective", "review", "complaint", "suggestion"], column: true },
      PROCESS_REL,
      PILOT,
      { key: "priority", label: "Priorité", type: "select", options: ["Basse", "Normale", "Haute", "Urgente"], column: true },
      { key: "due_date", label: "Échéance", type: "date", column: true },
      { key: "description", label: "Description", type: "textarea" },
    ],
  },
  process: {
    kind: "process", label: "Processus", singular: "processus", prefix: "PRO", titleLabel: "Nom du processus",
    statuses: [{ value: "Brouillon", tone: "neutral" }, { value: "Actif", tone: "success" }, { value: "À revoir", tone: "warn" }, { value: "Archivé", tone: "neutral" }],
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Management", "Réalisation", "Support"], required: true, column: true },
      PILOT,
      { key: "purpose", label: "Finalité", type: "textarea" },
      { key: "inputs", label: "Éléments d'entrée", type: "textarea" },
      { key: "outputs", label: "Éléments de sortie", type: "textarea" },
      { key: "upstream_id", label: "Processus amont", type: "relation", kinds: ["process"] },
    ],
  },
  document: {
    kind: "document", label: "Documents", singular: "document", prefix: "DOC", titleLabel: "Titre du document",
    statuses: [{ value: "Brouillon", tone: "neutral" }, { value: "En vérification", tone: "info" }, { value: "En approbation", tone: "warn" }, { value: "Publié", tone: "success" }, { value: "Archivé", tone: "neutral" }],
    workflow: true,
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Manuel", "Politique", "Procédure", "Instruction", "Formulaire", "Enregistrement"], required: true, column: true },
      PROCESS_REL,
      { key: "version", label: "Version", type: "text", column: true },
      { key: "review_date", label: "Prochaine révision", type: "date", column: true },
      PILOT,
      { key: "summary", label: "Résumé", type: "textarea" },
    ],
  },
  operation: {
    kind: "operation", label: "Opérations", singular: "opération", prefix: "OPE", titleLabel: "Produit / service",
    statuses: [{ value: "Planifiée", tone: "neutral" }, { value: "En cours", tone: "info" }, { value: "Libérée", tone: "success" }, { value: "Bloquée", tone: "danger" }],
    workflow: true,
    fields: [
      PROCESS_REL, SITE_REL,
      { key: "client", label: "Client", type: "text", column: true },
      { key: "date", label: "Date prévue", type: "date", column: true },
      { key: "requirements", label: "Exigences", type: "textarea" },
    ],
  },
  supplier: {
    kind: "supplier", label: "Fournisseurs", singular: "fournisseur", prefix: "FRN", titleLabel: "Raison sociale",
    statuses: [{ value: "En évaluation", tone: "info" }, { value: "Approuvé", tone: "success" }, { value: "Sous surveillance", tone: "warn" }, { value: "Refusé", tone: "danger" }],
    fields: [
      { key: "category", label: "Catégorie", type: "text", column: true },
      { key: "contact", label: "Contact", type: "text" },
      { key: "email", label: "E-mail", type: "email" },
      { key: "score", label: "Note d'évaluation (/20)", type: "number", column: true },
    ],
  },
  nc: {
    kind: "nc", label: "Non-conformités", singular: "non-conformité", prefix: "NC", titleLabel: "Écart constaté",
    statuses: [{ value: "Ouverte", tone: "danger" }, { value: "En analyse", tone: "warn" }, { value: "En traitement", tone: "info" }, { value: "Vérification", tone: "info" }, { value: "Clôturée", tone: "success" }],
    workflow: true,
    fields: [
      { key: "origin", label: "Origine", type: "select", options: ["Audit", "Client", "Interne", "Fournisseur", "Réglementaire"], column: true },
      { key: "severity", label: "Gravité", type: "select", options: ["Mineure", "Majeure", "Critique"], required: true, column: true },
      PROCESS_REL, SITE_REL,
      { key: "source_id", label: "Lié à", type: "relation", kinds: ["audit", "complaint", "supplier", "operation"] },
      { key: "description", label: "Description", type: "textarea" },
      { key: "root_cause", label: "Analyse des causes", type: "textarea" },
    ],
  },
  complaint: {
    kind: "complaint", label: "Réclamations", singular: "réclamation", prefix: "REC", titleLabel: "Objet",
    statuses: [{ value: "Reçue", tone: "warn" }, { value: "En traitement", tone: "info" }, { value: "Répondue", tone: "success" }, { value: "Clôturée", tone: "neutral" }],
    workflow: true,
    fields: [
      { key: "client", label: "Client", type: "text", column: true },
      { key: "channel", label: "Canal", type: "select", options: ["E-mail", "Téléphone", "Courrier", "Plateforme", "Visite"], column: true },
      { key: "date", label: "Date de réception", type: "date", column: true },
      { key: "description", label: "Description", type: "textarea" },
    ],
  },
  suggestion: {
    kind: "suggestion", label: "Suggestions", singular: "suggestion", prefix: "SUG", titleLabel: "Idée d'amélioration",
    statuses: [{ value: "Proposée", tone: "info" }, { value: "Acceptée", tone: "success" }, { value: "Refusée", tone: "neutral" }],
    fields: [PROCESS_REL, { key: "author", label: "Auteur", type: "text", column: true }, { key: "description", label: "Description", type: "textarea" }],
  },
  audit: {
    kind: "audit", label: "Audits", singular: "audit", prefix: "AUD", titleLabel: "Intitulé de l'audit",
    statuses: [{ value: "Planifié", tone: "neutral" }, { value: "En préparation", tone: "info" }, { value: "En cours", tone: "warn" }, { value: "Rapport", tone: "info" }, { value: "Clôturé", tone: "success" }],
    workflow: true,
    fields: [
      { key: "type", label: "Type", type: "select", options: ["Interne", "Fournisseur", "Certification", "Surveillance"], column: true },
      { key: "norm", label: "Norme", type: "select", options: NORMS, column: true },
      SITE_REL,
      { key: "date", label: "Date", type: "date", column: true },
      { key: "auditor_id", label: "Auditeur", type: "relation", kinds: ["collaborator"] },
      { key: "scope", label: "Champ de l'audit", type: "textarea" },
      { key: "findings", label: "Constats", type: "textarea" },
    ],
  },
  indicator: {
    kind: "indicator", label: "Indicateurs", singular: "indicateur", prefix: "KPI", titleLabel: "Nom de l'indicateur",
    statuses: [{ value: "Actif", tone: "success" }, { value: "Suspendu", tone: "neutral" }],
    fields: [
      PROCESS_REL,
      { key: "unit", label: "Unité", type: "text" },
      { key: "target", label: "Cible", type: "number", column: true },
      { key: "value", label: "Dernière valeur", type: "number", column: true },
      { key: "frequency", label: "Fréquence", type: "select", options: ["Mensuelle", "Trimestrielle", "Semestrielle", "Annuelle"], column: true },
      PILOT,
    ],
  },
  review: {
    kind: "review", label: "Revues de direction", singular: "revue", prefix: "RDD", titleLabel: "Intitulé",
    statuses: [{ value: "Planifiée", tone: "neutral" }, { value: "Tenue", tone: "info" }, { value: "Rapport publié", tone: "success" }],
    workflow: true,
    fields: [
      { key: "date", label: "Date", type: "date", column: true },
      { key: "participants", label: "Participants", type: "text", column: true },
      { key: "inputs", label: "Éléments d'entrée", type: "textarea" },
      { key: "decisions", label: "Décisions", type: "textarea" },
    ],
  },
  competence: {
    kind: "competence", label: "Compétences", singular: "compétence", prefix: "CMP", titleLabel: "Compétence",
    statuses: [{ value: "À acquérir", tone: "warn" }, { value: "En cours", tone: "info" }, { value: "Acquise", tone: "success" }],
    fields: [
      { key: "collaborator_id", label: "Collaborateur", type: "relation", kinds: ["collaborator"], column: true },
      { key: "level", label: "Niveau", type: "select", options: ["Débutant", "Confirmé", "Expert"], column: true },
    ],
  },
  training: {
    kind: "training", label: "Formations", singular: "formation", prefix: "FOR", titleLabel: "Intitulé de la formation",
    statuses: [{ value: "Planifiée", tone: "neutral" }, { value: "Réalisée", tone: "success" }, { value: "Évaluée", tone: "success" }],
    workflow: true,
    fields: [
      { key: "collaborator_id", label: "Collaborateur", type: "relation", kinds: ["collaborator"], column: true },
      { key: "date", label: "Date", type: "date", column: true },
      { key: "provider", label: "Organisme", type: "text", column: true },
    ],
  },
  equipment: {
    kind: "equipment", label: "Équipements", singular: "équipement", prefix: "EQP", titleLabel: "Équipement",
    statuses: [{ value: "En service", tone: "success" }, { value: "Maintenance", tone: "warn" }, { value: "Hors service", tone: "danger" }],
    fields: [
      { key: "serial", label: "N° de série", type: "text", column: true },
      SITE_REL,
      { key: "next_maintenance", label: "Prochaine maintenance", type: "date", column: true },
      { key: "notes", label: "Notes", type: "textarea" },
    ],
  },
  certification: {
    kind: "certification", label: "Certifications", singular: "certification", prefix: "CRT", titleLabel: "Intitulé",
    statuses: [{ value: "En préparation", tone: "info" }, { value: "Obtenue", tone: "success" }, { value: "Expirée", tone: "danger" }],
    fields: [
      { key: "norm", label: "Norme", type: "select", options: NORMS, required: true, column: true },
      { key: "body", label: "Organisme certificateur", type: "text", column: true },
      { key: "expiry", label: "Date d'expiration", type: "date", column: true },
    ],
  },
} satisfies Record<string, KindConfig>;

export const KINDS: Record<string, KindConfig> = K;

export const SECTIONS: Record<string, SectionConfig> = {
  sites: { slug: "sites", title: "Sites", description: "Établissements, usines et agences de l'entreprise.", kinds: [K.site] },
  collaborateurs: { slug: "collaborateurs", title: "Collaborateurs", description: "Utilisateurs, fonctions et rattachement aux sites.", kinds: [K.collaborator] },
  contexte: { slug: "contexte", title: "Contexte de l'organisation", description: "SWOT, PESTEL, parties intéressées et périmètre du système.", kinds: [K.context, K.party, K.scope] },
  risques: { slug: "risques", title: "Risques & objectifs", description: "Risques, opportunités et objectifs mesurables.", kinds: [K.risk, K.objective] },
  actions: { slug: "actions", title: "Plans d'actions", description: "Toutes les actions issues des NC, risques, audits, objectifs et revues.", kinds: [K.action] },
  processus: { slug: "processus", title: "Processus", description: "Cartographie, pilotes et interactions des processus.", kinds: [K.process] },
  documents: { slug: "documents", title: "Documents", description: "Bibliothèque documentaire et circuit de validation.", kinds: [K.document] },
  realisation: { slug: "realisation", title: "Réalisation", description: "Planification, réalisation et libération des produits et services.", kinds: [K.operation] },
  fournisseurs: { slug: "fournisseurs", title: "Fournisseurs", description: "Évaluation et suivi des fournisseurs et prestataires.", kinds: [K.supplier] },
  "non-conformites": { slug: "non-conformites", title: "Non-conformités", description: "Déclaration, analyse, traitement et clôture des écarts.", kinds: [K.nc] },
  reclamations: { slug: "reclamations", title: "Réclamations & suggestions", description: "Réclamations clients et idées d'amélioration.", kinds: [K.complaint, K.suggestion] },
  audits: { slug: "audits", title: "Audits", description: "Programme, préparation, réalisation et rapport d'audit.", kinds: [K.audit] },
  indicateurs: { slug: "indicateurs", title: "Indicateurs", description: "Mesure de la performance par processus.", kinds: [K.indicator] },
  "revue-direction": { slug: "revue-direction", title: "Revue de direction", description: "Préparation, décisions et rapport des revues.", kinds: [K.review] },
  competences: { slug: "competences", title: "Compétences & formations", description: "Compétences requises et plan de formation.", kinds: [K.competence, K.training] },
  equipements: { slug: "equipements", title: "Équipements", description: "Parc d'équipements et maintenance.", kinds: [K.equipment] },
  certifications: { slug: "certifications", title: "Certifications", description: "Certifications obtenues et échéances.", kinds: [K.certification] },
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
