import { Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { Menu, X } from "lucide-react";
import logoAsset from "@/assets/bestqhse-logo.png.asset.json";
import { LqButton } from "@/components/lq/LqButton";

const LINKS = [
  { id: "normes", label: "Normes" },
  { id: "processus", label: "Processus" },
  { id: "modules", label: "Modules" },
  { id: "fonctionnalites", label: "Fonctionnalités" },
  { id: "tarifs", label: "Tarifs" },
  { id: "faq", label: "FAQ" },
];

export function Navbar() {
  const [active, setActive] = useState("");
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const sections = LINKS.map((l) => document.getElementById(l.id)).filter(Boolean);
    const obs = new IntersectionObserver(
      (entries) => {
        for (const e of entries) {
          if (e.isIntersecting) setActive(e.target.id);
        }
      },
      { rootMargin: "-40% 0px -55% 0px" }
    );
    sections.forEach((s) => s && obs.observe(s));
    return () => obs.disconnect();
  }, []);

  return (
    <header className="fixed inset-x-0 top-4 z-50 px-4">
      <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 rounded-2xl border border-border/70 bg-card/85 py-2.5 pl-4 pr-2.5 shadow-lg shadow-primary/5 backdrop-blur-xl">
        <Link to="/" className="flex shrink-0 items-center gap-2.5">
          <span className="flex h-9 items-center rounded-lg bg-card px-1.5">
            <img src={logoAsset.url} alt="LOGIQUALI — BestQHSE" className="h-6 w-auto" />
          </span>
          <span className="hidden font-display text-lg font-bold tracking-tight text-foreground sm:block">
            LOGIQUALI
          </span>
        </Link>

        <nav className="hidden items-center gap-1 rounded-full bg-secondary/70 p-1 lg:flex">
          {LINKS.map((l) => (
            <a
              key={l.id}
              href={`#${l.id}`}
              className={`relative rounded-full px-3.5 py-1.5 text-[13px] font-semibold transition-colors ${
                active === l.id
                  ? "bg-primary text-primary-foreground shadow-md shadow-primary/30"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              {l.label}
            </a>
          ))}
        </nav>

        <div className="hidden items-center gap-2 lg:flex">
          <Link
            to="/auth/login"
            className="rounded-xl px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:text-primary"
          >
            Connexion
          </Link>
          <LqButton to="/auth/signup" size="sm" withArrow>
            Essai gratuit
          </LqButton>
        </div>

        <button
          className="grid h-10 w-10 place-items-center rounded-xl text-foreground lg:hidden"
          onClick={() => setOpen(!open)}
          aria-label="Menu"
        >
          {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
        </button>
      </div>

      {open && (
        <div className="mx-auto mt-2 max-w-6xl rounded-2xl border border-border bg-card p-4 shadow-xl lg:hidden">
          <nav className="flex flex-col gap-1">
            {LINKS.map((l) => (
              <a
                key={l.id}
                href={`#${l.id}`}
                onClick={() => setOpen(false)}
                className="rounded-xl px-4 py-2.5 text-sm font-semibold text-foreground hover:bg-secondary"
              >
                {l.label}
              </a>
            ))}
            <div className="mt-3 flex gap-2">
              <LqButton to="/auth/login" variant="ghost" size="sm" className="flex-1">
                Connexion
              </LqButton>
              <LqButton to="/auth/signup" size="sm" className="flex-1">
                Essai gratuit
              </LqButton>
            </div>
          </nav>
        </div>
      )}
    </header>
  );
}
