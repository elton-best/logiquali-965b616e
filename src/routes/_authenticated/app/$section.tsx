import { createFileRoute, notFound } from "@tanstack/react-router";
import { SECTIONS } from "@/components/app/sections";
import { SectionView } from "@/components/app/SectionView";
import {
  CompanyPage, JournalPage, NormsPage, PreferencesPage, QueuePage, RolesPage, SubscriptionPage, TasksPage,
} from "@/components/app/CustomPages";

const CUSTOM = ["entreprise", "roles", "abonnement", "taches", "verification", "approbation", "normes", "preferences", "journal"];

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
  }
  const section = SECTIONS[slug]!;
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
