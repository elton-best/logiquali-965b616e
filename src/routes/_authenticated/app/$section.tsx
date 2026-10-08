import { createFileRoute, notFound } from "@tanstack/react-router";
import { SECTIONS } from "@/components/app/sections";
import { SectionView } from "@/components/app/SectionView";
import { CompanyPage, RolesPage, SubscriptionPage } from "@/components/app/CustomPages";

const CUSTOM = ["entreprise", "roles", "abonnement"];

type Search = { open?: string | undefined; new?: number | undefined; origin?: string | undefined };

export const Route = createFileRoute("/_authenticated/app/$section")({
  validateSearch: (s: Record<string, unknown>): Search => ({
    open: typeof s["open"] === "string" ? (s["open"] as string) : undefined,
    new: s["new"] ? 1 : undefined,
    origin: typeof s["origin"] === "string" ? (s["origin"] as string) : undefined,
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
  if (slug === "entreprise") return <CompanyPage />;
  if (slug === "roles") return <RolesPage />;
  if (slug === "abonnement") return <SubscriptionPage />;
  const section = SECTIONS[slug]!;
  const originKey = slug === "non-conformites" ? "source_id" : "origin_id";
  return (
    <SectionView
      key={slug}
      section={section}
      openId={search.open}
      createNew={!!search.new}
      prefill={search.origin ? { [originKey]: search.origin, ...(slug === "non-conformites" ? { origin: "Audit" } : {}) } : undefined}
    />
  );
}
