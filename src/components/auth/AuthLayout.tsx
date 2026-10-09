import { Link } from "@tanstack/react-router";
import { ArrowLeft, BadgeCheck, ShieldCheck, Sparkles } from "lucide-react";
import type { ReactNode } from "react";
import logoAsset from "@/assets/bestqhse-logo.png.asset.json";

export function AuthLayout({
  children,
  title,
  subtitle,
}: {
  children: ReactNode;
  title: string;
  subtitle: string;
}) {
  return (
    <div className="flex min-h-dvh bg-background lg:h-dvh lg:overflow-hidden">
      {/* Panneau marque */}
      <aside className="relative hidden w-[44%] flex-col justify-between overflow-hidden bg-primary p-12 lg:flex">
        <div className="bg-grid-soft absolute inset-0 opacity-25" />
        <div className="absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-primary-foreground/10 blur-3xl" />
        <div className="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-primary-dark/60 blur-3xl" />

        <div className="relative">
          <Link to="/" className="inline-flex items-center gap-3">
            <span className="flex h-11 items-center rounded-xl bg-card px-2.5 shadow-lg">
              <img src={logoAsset.url} alt="LOGIQUALI — BestQHSE" className="h-7 w-auto" />
            </span>
            <span className="font-display text-xl font-bold text-primary-foreground">LOGIQUALI</span>
          </Link>
        </div>

        <div className="relative space-y-8">
          <h2 className="font-display text-3xl font-extrabold leading-tight text-primary-foreground xl:text-4xl">
            Structurez votre conformité et accélérez vos décisions.
          </h2>
          <ul className="space-y-4">
            {[
              { icon: BadgeCheck, text: "6 normes ISO couvertes, cumulables par abonnement" },
              { icon: Sparkles, text: "7 modules et 35+ rubriques métier prêtes à l'emploi" },
              { icon: ShieldCheck, text: "Double authentification et traçabilité complète" },
            ].map((item) => (
              <li key={item.text} className="flex items-center gap-3 text-sm font-semibold text-primary-foreground/90">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-foreground/15">
                  <item.icon className="h-4.5 w-4.5" />
                </span>
                {item.text}
              </li>
            ))}
          </ul>
        </div>

        <p className="relative text-xs text-primary-foreground/60">
          BestQHSE, par Best Experts Group.
        </p>
      </aside>

      {/* Panneau formulaire */}
      <main className="relative flex flex-1 flex-col overflow-y-auto">
        <div className="flex items-center justify-between p-6 lg:px-12">
          <Link
            to="/"
            className="inline-flex items-center gap-2 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
          >
            <ArrowLeft className="h-4 w-4" />
            Retour au site
          </Link>
          <Link to="/" className="lg:hidden">
            <span className="flex h-9 items-center rounded-lg bg-card px-2 shadow-sm border border-border">
              <img src={logoAsset.url} alt="LOGIQUALI" className="h-5 w-auto" />
            </span>
          </Link>
        </div>
        <div className="flex flex-1 items-center justify-center px-6 pb-12 lg:px-12">
          <div className="w-full max-w-md">
            <h1 className="font-display text-2xl font-extrabold text-foreground md:text-3xl">{title}</h1>
            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{subtitle}</p>
            <div className="mt-8">{children}</div>
          </div>
        </div>
      </main>
    </div>
  );
}
