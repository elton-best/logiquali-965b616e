import { createFileRoute, notFound } from "@tanstack/react-router";
import { SECTIONS } from "@/components/app/sections";
import { SectionView } from "@/components/app/SectionView";
import {
  CompanyPage, JournalPage, NormsPage, PreferencesPage, QueuePage, RolesPage, SubscriptionPage, TasksPage,
} from "@/components/app/CustomPages";
import { InboxPage, MyActionsPage } from "@/components/app/Extras";
import { DuerpView, GuideSection, ManagementConsolidation, ObjectivesGrid, ProcessReviewView, RisksView } from "@/components/app/GuidePages";
import { ApplicationScopePage, ContextOrganisationPage, ManagementSystemPage, StakeholdersPage } from "@/components/app/OrganisationContextPages";
import { CollaboratorsPage, JobDescriptionsPage, OrgChartPage, PolicyPage, ResponsibilitiesPage } from "@/components/app/LeadershipPages";

const GUIDE: Record<string, { id: string; label: string; render: () => React.ReactNode }[]> = {
  risques: [{ id: "ro", label: "Risques & opportunités (grille, tableau, actions)", render: () => <RisksView /> }],
  dangers: [{ id: "duerp", label: "DUERP par unité de travail", render: () => <DuerpView /> }],
  objectifs: [{ id: "grid", label: "Grille mensuelle & taux d'atteinte", render: () => <ObjectivesGrid /> }],
  "revue-processus": [{ id: "rvp", label: "Revue en 9 sections", render: () => <ProcessReviewView /> }],
  "revue-direction": [{ id: "cons", label: "Consolidation des revues de processus", render: () => <ManagementConsolidation /> }],
};

const CUSTOM = ["entreprise", "roles", "abonnement", "taches", "verification", "approbation", "normes", "preferences", "journal", "mes-actions", "boite-reception"];

type Search = { open?: string | undefined; new?: number | undefined; origin?: string | undefined; kind?: string | undefined };

export const Route = createFileRoute("/_authenticated/app/$section")({
  validateSearch: (s: Record<string, unknown>): Search => ({
    open: typeof s["open"] === "string" ? (s["open"] as string) : undefined,
    new: s["new"] ? 1 : undefined,
    origin: typeof s["origin"] === "string" ? (s["origin"] as string) : undefined,
    kind: typeof s["kind"] === "string" ? (s["kind"] as string) : undefined,
  }),
  loader: ({ params }) => {
    if (!SECTIONS[params.section] && !CUSTOM.includes(params.section)) throw notFound();
    return { slug: params.section };
  },
  component: SectionPage,
  notFoundComponent: () => <div className="p-8 text-sm text-muted-foreground">Cette rubrique n'existe pas.</div>,
  errorComponent: () => <div className="p-8 text-sm text-muted-foreground">Impossible de charger cette rubrique.</div>,
});

function SectionPage() {
  const { slug } = Route.useLoaderData();
  const search = Route.useSearch();
  switch (slug) {
    case "entreprise": return <CompanyPage />;
    case "roles": return <RolesPage />;
    case "abonnement": return <SubscriptionPage />;
    case "taches": return <TasksPage />;
    case "verification": return <QueuePage mode="verification" />;
    case "approbation": return <QueuePage mode="approbation" />;
    case "normes": return <NormsPage />;
    case "preferences": return <PreferencesPage />;
    case "journal": return <JournalPage />;
    case "mes-actions": return <MyActionsPage />;
    case "boite-reception": return <InboxPage />;
    case "contexte": return <ContextOrganisationPage />;
    case "parties-interessees": return <StakeholdersPage />;
    case "perimetre": return <ApplicationScopePage />;
    case "processus": return search.open || search.new ? <SectionView key="processus-editor" section={SECTIONS.processus!} openId={search.open} createNew={!!search.new} newKind={search.kind} originId={search.origin} /> : <ManagementSystemPage />;
    case "politique": return <PolicyPage />;
    case "organigramme": return <OrgChartPage />;
    case "collaborateurs": return <CollaboratorsPage />;
    case "fiches-poste": return <JobDescriptionsPage />;
    case "responsabilites": return <ResponsibilitiesPage />;
  }
  const section = SECTIONS[slug]!;
  if (GUIDE[slug] && !search.open && !search.new) return <GuideSection key={slug} slug={slug} views={GUIDE[slug]!} />;
  return (
    <SectionView
      key={slug}
      section={section}
      openId={search.open}
      createNew={!!search.new}
      newKind={search.kind}
      originId={search.origin}
    />
  );
}
