import { Link } from "@tanstack/react-router";
import logoAsset from "@/assets/bestqhse-logo.png.asset.json";

export function Footer() {
  return (
    <footer className="border-t border-border bg-card">
      <div className="mx-auto grid max-w-6xl gap-10 px-6 py-14 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
          <div className="flex items-center gap-2.5">
            <span className="flex h-9 items-center rounded-lg bg-card px-1.5">
              <img src={logoAsset.url} alt="LOGIQUALI" className="h-6 w-auto" />
            </span>
            <span className="font-display text-lg font-bold text-foreground">LOGIQUALI</span>
          </div>
          <p className="mt-4 max-w-xs text-sm leading-relaxed text-muted-foreground">
            La plateforme QHSE multi-normes. Structurez votre conformité et accélérez vos
            décisions.
          </p>
          <p className="mt-4 text-xs text-muted-foreground">
            BestQHSE, par Best Experts Group.
          </p>
        </div>
        <div>
          <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Produit</h4>
          <ul className="mt-4 space-y-2.5 text-sm font-medium text-foreground">
            <li><a href="#normes" className="hover:text-primary">Normes ISO</a></li>
            <li><a href="#modules" className="hover:text-primary">Modules</a></li>
            <li><a href="#fonctionnalites" className="hover:text-primary">Fonctionnalités</a></li>
            <li><a href="#tarifs" className="hover:text-primary">Tarifs</a></li>
          </ul>
        </div>
        <div>
          <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Compte</h4>
          <ul className="mt-4 space-y-2.5 text-sm font-medium text-foreground">
            <li><Link to="/auth/login" className="hover:text-primary">Connexion</Link></li>
            <li><Link to="/auth/signup" className="hover:text-primary">Créer un compte</Link></li>
            <li><Link to="/auth/signup/company" className="hover:text-primary">Espace entreprise</Link></li>
            <li><Link to="/auth/signup/individual" className="hover:text-primary">Espace particulier</Link></li>
          </ul>
        </div>
        <div>
          <h4 className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Légal</h4>
          <ul className="mt-4 space-y-2.5 text-sm font-medium text-foreground">
            <li><span className="cursor-pointer hover:text-primary">Conditions générales</span></li>
            <li><span className="cursor-pointer hover:text-primary">Confidentialité</span></li>
            <li><span className="cursor-pointer hover:text-primary">Mentions légales</span></li>
          </ul>
        </div>
      </div>
      <div className="border-t border-border py-5 text-center text-xs text-muted-foreground">
        © {new Date().getFullYear()} LOGIQUALI — Best Experts Group. Tous droits réservés.
      </div>
    </footer>
  );
}
