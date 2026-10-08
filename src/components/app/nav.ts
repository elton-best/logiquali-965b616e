import {
  Award,
  BarChart3,
  Briefcase,
  Building2,
  ClipboardCheck,
  CreditCard,
  FileText,
  Gauge,
  GitBranch,
  GraduationCap,
  Landmark,
  LayoutDashboard,
  MapPin,
  MessageSquareWarning,
  Presentation,
  ShieldAlert,
  ShieldCheck,
  Target,
  Truck,
  Users,
  Wrench,
  Factory,
  type LucideIcon,
} from "lucide-react";

export type NavItem = { slug: string; label: string; icon: LucideIcon; badge?: string };
export type NavGroup = { id: string; label: string; icon: LucideIcon; items: NavItem[] };

export const NAV_GROUPS: NavGroup[] = [
  {
    id: "organisation",
    label: "Organisation",
    icon: Building2,
    items: [
      { slug: "entreprise", label: "Fiche entreprise", icon: Briefcase },
      { slug: "sites", label: "Sites", icon: MapPin },
      { slug: "collaborateurs", label: "Collaborateurs", icon: Users },
      { slug: "roles", label: "Rôles & permissions", icon: ShieldCheck },
    ],
  },
  {
    id: "systeme",
    label: "Système de management",
    icon: Landmark,
    items: [
      { slug: "contexte", label: "Contexte", icon: Landmark },
      { slug: "risques", label: "Risques & objectifs", icon: Target },
      { slug: "processus", label: "Processus", icon: GitBranch },
      { slug: "documents", label: "Documents", icon: FileText, badge: "3" },
    ],
  },
  {
    id: "operations",
    label: "Opérations",
    icon: Factory,
    items: [
      { slug: "realisation", label: "Réalisation", icon: Factory },
      { slug: "fournisseurs", label: "Fournisseurs", icon: Truck },
      { slug: "non-conformites", label: "Non-conformités", icon: ShieldAlert, badge: "2" },
      { slug: "reclamations", label: "Réclamations", icon: MessageSquareWarning },
    ],
  },
  {
    id: "evaluation",
    label: "Évaluation",
    icon: ClipboardCheck,
    items: [
      { slug: "audits", label: "Audits", icon: ClipboardCheck },
      { slug: "indicateurs", label: "Indicateurs", icon: BarChart3 },
      { slug: "revue-direction", label: "Revue de direction", icon: Presentation },
    ],
  },
  {
    id: "support",
    label: "Support",
    icon: Wrench,
    items: [
      { slug: "competences", label: "Compétences", icon: GraduationCap },
      { slug: "equipements", label: "Équipements", icon: Wrench },
    ],
  },
];

export const BOTTOM_ITEMS: NavItem[] = [
  { slug: "abonnement", label: "Abonnement", icon: CreditCard },
  { slug: "certifications", label: "Certifications", icon: Award },
];

export const DASHBOARD_ICON = LayoutDashboard;
export const GAUGE_ICON = Gauge;

export function findNavItem(slug: string): NavItem | undefined {
  for (const g of NAV_GROUPS) {
    const it = g.items.find((i) => i.slug === slug);
    if (it) return it;
  }
  return BOTTOM_ITEMS.find((i) => i.slug === slug);
}
