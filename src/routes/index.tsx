import { createFileRoute } from "@tanstack/react-router";
import {
  Award,
  ArrowRight,
  BadgeCheck,
  BarChart3,
  Building2,
  ClipboardCheck,
  ClipboardList,
  FileCheck2,
  FileText,
  FolderKanban,
  Gauge,
  Landmark,
  Lightbulb,
  Search,
  Settings2,
  ShieldCheck,
  Target,
  Users,
  ChevronDown,
  Check,
  Sparkles,
} from "lucide-react";
import { useMemo, useState } from "react";
import { Navbar } from "@/components/landing/Navbar";
import { Footer } from "@/components/landing/Footer";
import { LqButton } from "@/components/lq/LqButton";
import { Reveal } from "@/components/lq/Reveal";
import { DashboardPreview } from "@/components/landing/DashboardPreview";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "LOGIQUALI — Structurez votre conformité et accélérez vos décisions" },
      {
        name: "description",
        content:
          "La plateforme QHSE multi-normes : 6 normes ISO, 7 modules, 35+ rubriques métier, multi-sites. Gestion documentaire, audits, non-conformités, DUERP et reporting.",
      },
      { property: "og:title", content: "LOGIQUALI — La plateforme QHSE multi-normes" },
      {
        property: "og:description",
        content:
          "6 normes ISO, 7 modules, 35+ rubriques métier. Centralisez le pilotage de votre conformité.",
      },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary_large_image" },
    ],
  }),
  component: LandingPage,
});

const STANDARDS = [
  { code: "ISO 9001", label: "Qualité", desc: "Système de management de la qualité" },
  { code: "ISO 14001", label: "Environnement", desc: "Management environnemental" },
  { code: "ISO 45001", label: "Santé & Sécurité", desc: "Santé et sécurité au travail" },
  { code: "ISO 27001", label: "Sécurité de l'information", desc: "Système de management de la sécurité de l'information" },
  { code: "ISO 22000", label: "Sécurité alimentaire", desc: "Management de la sécurité des denrées alimentaires" },
  { code: "ISO 50001", label: "Énergie", desc: "Système de management de l'énergie" },
];

const PROCESS_STEPS = [
  { n: "01", duration: "1 semaine", title: "Cadrer", desc: "Définissez votre contexte, vos parties intéressées et le périmètre de votre système de management.", icon: Target },
  { n: "02", duration: "2 semaines", title: "Centraliser", desc: "Rassemblez documents, enregistrements et preuves dans un référentiel unique et versionné.", icon: FolderKanban },
  { n: "03", duration: "1–2 jours", title: "Piloter", desc: "Suivez audits, non-conformités, actions et indicateurs depuis votre cockpit QHSE.", icon: Gauge },
  { n: "04", duration: "1–2 jours", title: "Arbitrer", desc: "Décidez sur des données fiables : revues de direction, tableaux de bord et exports prêts à l'emploi.", icon: BarChart3 },
];

const MODULES = [
  { n: "4", title: "Contexte", desc: "Parties intéressées, enjeux, périmètre et processus de l'organisme.", icon: Landmark },
  { n: "5", title: "Leadership", desc: "Politique QHSE, rôles, responsabilités et engagement de la direction.", icon: Users },
  { n: "6", title: "Planification", desc: "Risques, opportunités, objectifs et plans d'actions associés.", icon: ClipboardList },
  { n: "7", title: "Support", desc: "Compétences, communication, gestion documentaire et ressources.", icon: Settings2 },
  { n: "8", title: "Réalisation", desc: "Maîtrise opérationnelle, équipements, DUERP et situations d'urgence.", icon: ShieldCheck },
  { n: "9", title: "Évaluation", desc: "Audits, indicateurs, suivi des performances et revue de direction.", icon: ClipboardCheck },
  { n: "10", title: "Amélioration", desc: "Non-conformités, actions correctives et amélioration continue.", icon: Lightbulb },
];

const FEATURES = [
  { title: "Gestion documentaire", desc: "Workflow vérification → approbation, versions, diffusion contrôlée et traçabilité complète.", icon: FileText },
  { title: "Audits & inspections", desc: "Planifiez vos audits, collectez les constats et suivez les plans d'action jusqu'à clôture.", icon: Search },
  { title: "Non-conformités", desc: "Déclaration, analyse des causes, actions correctives et vérification d'efficacité.", icon: FileCheck2 },
  { title: "Certifications", desc: "Préparez et maintenez vos certifications ISO avec un référentiel toujours prêt pour l'audit.", icon: Award },
  { title: "Collaboration", desc: "Rôles et permissions granulaires, multi-sites et multi-entreprises, notifications ciblées.", icon: Users },
  { title: "Reporting & exports", desc: "Tableaux de bord temps réel et exports PDF, DOCX et Excel pour vos revues de direction.", icon: BarChart3 },
];

const PLATFORM_BLOCKS = [
  {
    title: "Un référentiel documentaire vivant",
    desc: "Chaque document suit son cycle de vie : rédaction, vérification, approbation, diffusion. Les anciennes versions sont archivées, les lectures sont tracées, et chacun accède toujours à la bonne version.",
    points: ["Workflow vérification → approbation", "Versions et archivage automatique", "Accusés de lecture"],
    icon: FileText,
  },
  {
    title: "Un cockpit de pilotage en temps réel",
    desc: "Indicateurs, audits en cours, NC ouvertes, avancement des plans d'action : votre tableau de bord consolide tout, par site et par processus, pour des revues de direction sans préparation.",
    points: ["Tableaux de bord par processus", "Alertes et échéances", "Exports PDF / DOCX / Excel"],
    icon: Gauge,
  },
  {
    title: "Multi-sites et multi-entreprises",
    desc: "Gérez un groupe entier depuis une seule plateforme : chaque site garde son autonomie, la direction garde la vision consolidée. Idéal pour les cabinets et les groupes multisites.",
    points: ["Vision consolidée groupe", "Autonomie par site", "Consolidation des indicateurs"],
    icon: Building2,
  },
  {
    title: "Sécurité et traçabilité natives",
    desc: "Rôles granulaires, journal d'activité complet, double authentification par e-mail : vos données de conformité sont sensibles, elles sont protégées comme telles.",
    points: ["Permissions granulaires", "Journal d'audit complet", "MFA obligatoire"],
    icon: ShieldCheck,
  },
];

const PLANS = [
  {
    name: "ISO 9001",
    price: "49 000",
    unit: "FCFA / mois",
    desc: "Pour démarrer avec le référentiel qualité.",
    features: ["1 norme au choix", "7 modules inclus", "3 utilisateurs", "1 site", "Exports PDF & Excel", "Support par e-mail"],
    popular: false,
  },
  {
    name: "Pack Intégré",
    price: "89 000",
    unit: "FCFA / mois",
    desc: "Le système de management intégré complet.",
    features: ["Jusqu'à 3 normes cumulées", "7 modules inclus", "10 utilisateurs", "Multi-sites (3 sites)", "Workflow documentaire avancé", "Support prioritaire"],
    popular: true,
  },
  {
    name: "Enterprise",
    price: "Sur devis",
    unit: "",
    desc: "Pour les groupes et cabinets multi-entreprises.",
    features: ["6 normes illimitées", "Utilisateurs illimités", "Multi-entreprises", "Accompagnement dédié", "API & intégrations", "SLA garanti"],
    popular: false,
  },
];

const FAQ_ITEMS = [
  { q: "Qu'est-ce que LOGIQUALI ?", a: "LOGIQUALI (BestQHSE, par Best Experts Group) est une plateforme QHSE multi-normes qui centralise le pilotage de votre conformité ISO. Elle couvre 6 normes (ISO 9001, 14001, 45001, 27001, 22000, 50001) à travers 7 modules calqués sur les points 4 à 10 des normes." },
  { q: "Puis-je cumuler plusieurs normes ISO ?", a: "Oui. Les normes sont cumulables par abonnement. Le Pack Intégré permet de gérer jusqu'à 3 normes dans un système de management intégré, et l'offre Enterprise couvre les 6 normes sans limite." },
  { q: "Comment se passe l'inscription d'une entreprise ?", a: "L'inscription se fait en 3 étapes : informations de l'entreprise (RCCM/IFU), compte administrateur, puis pièces justificatives. Votre dossier est vérifié par nos équipes sous 24 à 48h, puis votre espace est activé." },
  { q: "Quelle différence entre compte entreprise et particulier ?", a: "Le compte entreprise donne accès au cockpit QHSE complet (documents, audits, NC, multi-sites). Le compte particulier, activé instantanément, permet de déposer des réclamations et de répondre aux enquêtes de satisfaction." },
  { q: "Mes données sont-elles sécurisées ?", a: "Oui. La plateforme impose une double authentification par e-mail, des rôles et permissions granulaires, et conserve un journal d'activité complet pour une traçabilité totale." },
  { q: "Puis-je exporter mes données ?", a: "Absolument. Tous vos documents, registres et tableaux de bord sont exportables en PDF, DOCX et Excel, à tout moment, sans restriction." },
];

function LandingPage() {
  return (
    <div className="min-h-screen bg-background">
      <Navbar />
      <Hero />
      <DashboardPreview />
      <Standards />
      <Process />
      <Modules />
      <Features />
      <PlatformDetail />
      <Pricing />
      <Faq />
      <FinalCta />
      <Footer />
    </div>
  );
}

function Hero() {
  const stats = [
    { value: "6", label: "normes ISO couvertes" },
    { value: "7", label: "modules de management" },
    { value: "35+", label: "rubriques métier" },
    { value: "∞", label: "multi-sites & entreprises" },
  ];
  return (
    <section className="relative overflow-hidden pt-36 pb-20 md:pt-44 md:pb-28">
      <div className="bg-grid-soft absolute inset-0 [mask-image:radial-gradient(ellipse_70%_60%_at_50%_35%,black,transparent)]" />
      <div className="absolute -top-32 left-1/2 h-96 w-[42rem] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl" />
      <div className="relative mx-auto max-w-5xl px-6 text-center">
        <Reveal>
          <span className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary-soft px-4 py-1.5 text-xs font-bold text-primary">
            <Sparkles className="h-3.5 w-3.5" />
            La plateforme QHSE multi-normes
          </span>
        </Reveal>
        <Reveal delay={100}>
          <h1 className="mt-6 text-4xl font-extrabold leading-[1.08] text-foreground sm:text-5xl md:text-6xl">
            Structurez votre conformité et{" "}
            <span className="bg-gradient-to-r from-primary to-primary-dark bg-clip-text text-transparent">
              accélérez vos décisions
            </span>
          </h1>
        </Reveal>
        <Reveal delay={200}>
          <p className="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-muted-foreground md:text-lg">
            LOGIQUALI centralise le pilotage de votre conformité ISO : documents, audits,
            non-conformités, DUERP et reporting, dans un seul cockpit QHSE.
          </p>
        </Reveal>
        <Reveal delay={300}>
          <div className="mt-9 flex flex-wrap items-center justify-center gap-3">
            <LqButton to="/auth/signup" size="lg" withArrow>
              Démarrer l'essai gratuit
            </LqButton>
            <LqButton href="#modules" variant="ghost" size="lg">
              Découvrir les modules
            </LqButton>
          </div>
        </Reveal>
        <Reveal delay={400}>
          <dl className="mx-auto mt-16 grid max-w-3xl grid-cols-2 gap-4 md:grid-cols-4">
            {stats.map((s) => (
              <div
                key={s.label}
                className="rounded-2xl border border-border bg-card/80 px-4 py-5 shadow-sm backdrop-blur"
              >
                <dt className="order-2 mt-1 block text-xs font-semibold text-muted-foreground">
                  {s.label}
                </dt>
                <dd className="font-display text-3xl font-extrabold text-primary">{s.value}</dd>
              </div>
            ))}
          </dl>
        </Reveal>
      </div>
    </section>
  );
}

function SectionHeader({ kicker, title, desc }: { kicker: string; title: string; desc: string }) {
  return (
    <Reveal className="mx-auto max-w-2xl text-center">
      <span className="text-xs font-bold uppercase tracking-[0.18em] text-primary">{kicker}</span>
      <h2 className="mt-3 text-3xl font-extrabold text-foreground md:text-4xl">{title}</h2>
      <p className="mt-4 text-muted-foreground">{desc}</p>
    </Reveal>
  );
}

function Standards() {
  return (
    <section id="normes" className="scroll-mt-28 bg-card py-20 md:py-28">
      <div className="mx-auto max-w-6xl px-6">
        <SectionHeader
          kicker="Normes prises en charge"
          title="6 référentiels ISO, une seule plateforme"
          desc="Cumulez les normes selon vos besoins. Chaque référentiel est pré-configuré avec ses exigences, ses rubriques et ses indicateurs."
        />
        <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {STANDARDS.map((s, i) => (
            <Reveal key={s.code} delay={i * 80}>
              <div className="group h-full rounded-2xl border border-border bg-background p-6 transition-all hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl hover:shadow-primary/10">
                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-soft text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                  <BadgeCheck className="h-6 w-6" />
                </div>
                <h3 className="mt-5 font-display text-xl font-bold text-foreground">{s.code}</h3>
                <p className="text-sm font-bold text-primary">{s.label}</p>
                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{s.desc}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

function Process() {
  return (
    <section id="processus" className="relative scroll-mt-28 overflow-hidden bg-[#fbfcf8] py-20 md:py-28">
      <svg
        className="absolute inset-x-0 top-0 h-10 w-full text-card"
        viewBox="0 0 1440 40"
        preserveAspectRatio="none"
        aria-hidden
      >
        <path d="M0,0 C360,40 1080,40 1440,0 L1440,0 L0,0 Z" fill="currentColor" />
      </svg>
      <div className="mx-auto max-w-6xl px-6">
        <SectionHeader
          kicker="Comment ça marche"
          title="De la mise en place à la décision, en 4 étapes"
          desc="Une méthode éprouvée pour passer d'un système dispersé à un pilotage continu de la conformité."
        />

        <div className="relative mx-auto mt-14 max-w-6xl md:mt-16">
          <div className="hidden h-px bg-primary/20 md:block" aria-hidden="true" />
          <div className="grid gap-5 md:grid-cols-4 md:gap-4">
            {PROCESS_STEPS.map((step, i) => {
              const Icon = step.icon;
              const highlighted = i === 0 || i === 3;
              return (
                <Reveal key={step.n} delay={i * 120} className="relative">
                  {i < PROCESS_STEPS.length - 1 && <ArrowRight className="absolute -right-4 top-12 z-10 hidden h-7 w-7 text-primary/45 md:block" aria-hidden="true" />}
                  <article className={`group relative flex min-h-[285px] flex-col rounded-[1.5rem] border p-6 shadow-[0_18px_40px_-30px_rgba(15,23,42,.38)] transition-all duration-300 hover:-translate-y-1 hover:shadow-xl ${highlighted ? "border-success/30 bg-success/10" : "border-border/70 bg-white"}`}>
                    <div className="flex items-start justify-between gap-3">
                      <span className={`grid h-12 w-12 place-items-center rounded-2xl ${highlighted ? "bg-[#087653] text-white" : "bg-secondary text-foreground"}`}><Icon className="h-5 w-5" /></span>
                      <span className={`rounded-full px-2.5 py-1 text-[10px] font-extrabold ${highlighted ? "bg-success/20 text-[#087653]" : "bg-background text-muted-foreground"}`}>Étape {step.n}</span>
                    </div>
                    <div className="mt-6"><h3 className="font-display text-2xl font-extrabold text-foreground">{step.title}</h3><p className={`mt-1 text-xs font-bold ${highlighted ? "text-[#087653]" : "text-muted-foreground"}`}>{step.duration}</p></div>
                    <p className="mt-5 text-sm leading-relaxed text-muted-foreground">{step.desc}</p>
                    <div className="mt-auto flex items-center gap-2 pt-5 text-xs font-bold text-primary"><span className="grid h-5 w-5 place-items-center rounded-full bg-primary text-primary-foreground">{i + 1}</span> {i === PROCESS_STEPS.length - 1 ? "Décider avec confiance" : "Étape suivante"}</div>
                  </article>
                </Reveal>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
}

function Modules() {
  return (
    <section id="modules" className="scroll-mt-28 bg-card py-20 md:py-28">
      <div className="mx-auto max-w-6xl px-6">
        <SectionHeader
          kicker="Les 7 modules"
          title="Calqués sur les points 4 à 10 des normes"
          desc="Chaque module couvre un pilier du système de management, avec ses rubriques métier prêtes à l'emploi."
        />
        <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {MODULES.map((m, i) => (
            <Reveal key={m.n} delay={i * 60} className={i === 6 ? "sm:col-span-2 lg:col-span-1" : ""}>
              <div className="group h-full rounded-2xl border border-border bg-background p-6 transition-all hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl hover:shadow-primary/10">
                <div className="flex items-center justify-between">
                  <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-soft text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                    <m.icon className="h-5 w-5" />
                  </div>
                  <span className="font-display text-4xl font-extrabold text-primary/15 transition-colors group-hover:text-primary/30">
                    {m.n}
                  </span>
                </div>
                <h3 className="mt-4 font-display text-lg font-bold text-foreground">{m.title}</h3>
                <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">{m.desc}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

function Features() {
  return (
    <section id="fonctionnalites" className="scroll-mt-28 py-20 md:py-28">
      <div className="mx-auto max-w-6xl px-6">
        <SectionHeader
          kicker="Fonctionnalités clés"
          title="Tout le quotidien QHSE, sans tableurs"
          desc="Des outils pensés par des auditeurs pour les équipes qualité, sécurité et environnement."
        />
        <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {FEATURES.map((f, i) => (
            <Reveal key={f.title} delay={i * 70}>
              <div className="h-full rounded-2xl border border-border bg-card p-6 shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/10">
                <f.icon className="h-6 w-6 text-primary" />
                <h3 className="mt-4 font-display text-lg font-bold text-foreground">{f.title}</h3>
                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{f.desc}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

function PlatformDetail() {
  return (
    <section className="bg-card py-20 md:py-28">
      <div className="mx-auto max-w-6xl space-y-20 px-6 md:space-y-28">
        {PLATFORM_BLOCKS.map((b, i) => (
          <Reveal key={b.title}>
            <div
              className={`grid items-center gap-10 md:grid-cols-2 ${
                i % 2 === 1 ? "md:[&>*:first-child]:order-2" : ""
              }`}
            >
              <div className="relative">
                <div className="absolute -inset-4 rounded-3xl bg-gradient-to-br from-primary/15 to-transparent blur-xl" />
                <div className="relative flex aspect-[4/3] items-center justify-center rounded-3xl border border-border bg-gradient-to-br from-primary-soft to-primary-softer">
                  <div className="flex h-24 w-24 items-center justify-center rounded-3xl bg-primary text-primary-foreground shadow-2xl shadow-primary/40">
                    <b.icon className="h-11 w-11" />
                  </div>
                </div>
              </div>
              <div>
                <span className="text-xs font-bold uppercase tracking-[0.18em] text-primary">
                  La plateforme en détail
                </span>
                <h3 className="mt-3 font-display text-2xl font-extrabold text-foreground md:text-3xl">
                  {b.title}
                </h3>
                <p className="mt-4 leading-relaxed text-muted-foreground">{b.desc}</p>
                <ul className="mt-6 space-y-3">
                  {b.points.map((p) => (
                    <li key={p} className="flex items-center gap-3 text-sm font-semibold text-foreground">
                      <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary-soft text-primary">
                        <Check className="h-3.5 w-3.5" />
                      </span>
                      {p}
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </Reveal>
        ))}
      </div>
    </section>
  );
}

function Pricing() {
  return (
    <section id="tarifs" className="scroll-mt-28 py-20 md:py-28">
      <div className="mx-auto max-w-6xl px-6">
        <SectionHeader
          kicker="Tarifs"
          title="Une offre adaptée à votre maturité"
          desc="Commencez avec une norme, évoluez vers un système intégré. Sans engagement."
        />
        <div className="mt-16 grid items-stretch gap-6 lg:grid-cols-3">
          {PLANS.map((p, i) => (
            <Reveal key={p.name} delay={i * 100} className="h-full">
              <div
                className={`relative flex h-full flex-col rounded-3xl border p-8 ${
                  p.popular
                    ? "border-primary bg-card shadow-2xl shadow-primary/20 lg:-translate-y-4 lg:scale-[1.02]"
                    : "border-border bg-card shadow-sm"
                }`}
              >
                {p.popular && (
                  <span className="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-primary px-4 py-1 text-xs font-bold text-primary-foreground shadow-lg shadow-primary/30">
                    Le plus populaire
                  </span>
                )}
                <h3 className="font-display text-xl font-bold text-foreground">{p.name}</h3>
                <p className="mt-1 text-sm text-muted-foreground">{p.desc}</p>
                <div className="mt-6 flex items-baseline gap-2">
                  <span className="font-display text-4xl font-extrabold text-foreground">{p.price}</span>
                  {p.unit && <span className="text-sm font-semibold text-muted-foreground">{p.unit}</span>}
                </div>
                <ul className="mt-7 flex-1 space-y-3">
                  {p.features.map((f) => (
                    <li key={f} className="flex items-start gap-3 text-sm text-foreground">
                      <Check className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                      {f}
                    </li>
                  ))}
                </ul>
                <div className="mt-8">
                  <LqButton
                    to="/auth/signup"
                    variant={p.popular ? "primary" : "ghost"}
                    className="w-full"
                    withArrow={p.popular}
                  >
                    {p.price === "Sur devis" ? "Nous contacter" : "Commencer"}
                  </LqButton>
                </div>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

function Faq() {
  const [query, setQuery] = useState("");
  const [openIdx, setOpenIdx] = useState<number | null>(0);
  const filtered = useMemo(
    () =>
      FAQ_ITEMS.filter(
        (f) =>
          f.q.toLowerCase().includes(query.toLowerCase()) ||
          f.a.toLowerCase().includes(query.toLowerCase())
      ),
    [query]
  );

  return (
    <section id="faq" className="scroll-mt-28 bg-card py-20 md:py-28">
      <div className="mx-auto max-w-3xl px-6">
        <SectionHeader
          kicker="FAQ"
          title="Questions fréquentes"
          desc="Tout ce qu'il faut savoir avant de structurer votre conformité."
        />
        <Reveal delay={100}>
          <div className="relative mt-10">
            <Search className="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-primary" />
            <input
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Rechercher une question…"
              className="h-[52px] w-full rounded-full border-[1.5px] border-input bg-card pl-13 pr-12 text-sm font-medium text-foreground shadow-sm outline-none transition-all placeholder:text-muted-foreground focus:border-primary focus:shadow-[0_0_0_4px] focus:shadow-primary/15"
              style={{ paddingLeft: "3.25rem" }}
            />
            {query && (
              <button
                onClick={() => setQuery("")}
                className="absolute right-4 top-1/2 -translate-y-1/2 rounded-full p-1 text-muted-foreground hover:text-foreground"
                aria-label="Effacer"
              >
                ×
              </button>
            )}
          </div>
        </Reveal>
        <div className="mt-8 space-y-3">
          {filtered.length === 0 && (
            <p className="py-8 text-center text-sm text-muted-foreground">
              Aucune question ne correspond à votre recherche.
            </p>
          )}
          {filtered.map((f) => {
            const idx = FAQ_ITEMS.indexOf(f);
            const open = openIdx === idx;
            return (
              <Reveal key={f.q}>
                <div
                  className={`overflow-hidden rounded-2xl border transition-colors ${
                    open ? "border-primary/40 bg-primary-soft/50" : "border-border bg-background"
                  }`}
                >
                  <button
                    onClick={() => setOpenIdx(open ? null : idx)}
                    className="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                  >
                    <span className="font-display text-[15px] font-bold text-foreground">{f.q}</span>
                    <ChevronDown
                      className={`h-5 w-5 shrink-0 text-primary transition-transform duration-300 ${
                        open ? "rotate-180" : ""
                      }`}
                    />
                  </button>
                  <div
                    className="grid transition-all duration-300 ease-out"
                    style={{ gridTemplateRows: open ? "1fr" : "0fr" }}
                  >
                    <div className="overflow-hidden">
                      <p className="px-6 pb-5 text-sm leading-relaxed text-muted-foreground">{f.a}</p>
                    </div>
                  </div>
                </div>
              </Reveal>
            );
          })}
        </div>
      </div>
    </section>
  );
}

function FinalCta() {
  return (
    <section className="relative overflow-hidden bg-primary py-20 md:py-28">
      <div className="bg-grid-soft absolute inset-0 opacity-30" />
      <div className="absolute -bottom-24 left-1/2 h-72 w-[36rem] -translate-x-1/2 rounded-full bg-primary-foreground/10 blur-3xl" />
      <div className="relative mx-auto max-w-3xl px-6 text-center">
        <Reveal>
          <h2 className="font-display text-3xl font-extrabold text-primary-foreground md:text-4xl">
            Prêt à structurer votre conformité ?
          </h2>
          <p className="mx-auto mt-4 max-w-xl text-primary-foreground/85">
            Créez votre compte en quelques minutes. Entreprise : activation sous 24-48h après
            vérification. Particulier : accès immédiat.
          </p>
          <div className="mt-9 flex flex-wrap items-center justify-center gap-3">
            <LqButton to="/auth/signup" variant="white" size="lg" withArrow>
              Créer mon compte
            </LqButton>
            <LqButton
              to="/auth/login"
              size="lg"
              className="border border-primary-foreground/30 bg-transparent text-primary-foreground shadow-none hover:bg-primary-foreground/10"
            >
              J'ai déjà un compte
            </LqButton>
          </div>
        </Reveal>
      </div>
    </section>
  );
}
