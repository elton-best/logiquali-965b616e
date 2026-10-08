import { createFileRoute, Link, notFound } from "@tanstack/react-router";
import { ArrowLeft, Construction } from "lucide-react";
import { findNavItem } from "@/components/app/nav";

export const Route = createFileRoute("/_authenticated/app/$section")({
  loader: ({ params }) => {
    const item = findNavItem(params.section);
    if (!item) throw notFound();
    return { slug: item.slug, label: item.label };
  },
  component: SectionPage,
  notFoundComponent: () => (
    <div className="p-8 text-sm text-muted-foreground">Cette rubrique n'existe pas.</div>
  ),
  errorComponent: () => (
    <div className="p-8 text-sm text-muted-foreground">Impossible de charger cette rubrique.</div>
  ),
});

function SectionPage() {
  const { slug, label } = Route.useLoaderData();
  const Icon = findNavItem(slug)?.icon ?? Construction;
  return (
    <div className="mx-auto max-w-5xl p-4 md:p-8">
      <Link to="/app" className="inline-flex items-center gap-2 text-sm font-semibold text-muted-foreground hover:text-primary">
        <ArrowLeft className="h-4 w-4" /> Tableau de bord
      </Link>
      <h1 className="mt-3 font-display text-2xl font-extrabold text-foreground md:text-3xl">{label}</h1>
      <div className="mt-8 flex flex-col items-center rounded-3xl border border-dashed border-input bg-card px-6 py-16 text-center">
        <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-soft text-primary">
          <Icon className="h-7 w-7" />
        </span>
        <p className="mt-4 font-display text-lg font-bold text-foreground">Rubrique en préparation</p>
        <p className="mt-1 max-w-md text-sm text-muted-foreground">
          Le module « {label} » sera disponible prochainement dans votre espace.
        </p>
      </div>
    </div>
  );
}
