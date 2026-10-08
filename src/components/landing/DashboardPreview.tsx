import {
  Activity,
  AlertTriangle,
  ArrowUpRight,
  BarChart3,
  Bell,
  CalendarClock,
  CheckCircle2,
  ClipboardCheck,
  FileText,
  Gauge,
  GitBranch,
  ListChecks,
  ShieldAlert,
  Users,
} from "lucide-react";
import { Reveal } from "@/components/lq/Reveal";

const NAV = [
  [Gauge, "Vue d'ensemble"],
  [GitBranch, "Processus"],
  [FileText, "Documents"],
  [ClipboardCheck, "Audits"],
  [ListChecks, "Actions"],
] as const;

const KPI = [
  { icon: GitBranch, label: "Processus actifs", value: "—", note: "cartographie du site", color: "blue" },
  { icon: ListChecks, label: "Actions à traiter", value: "—", note: "priorités de l'équipe", color: "amber" },
  { icon: ShieldAlert, label: "NC ouvertes", value: "—", note: "suivi des écarts", color: "rose" },
  { icon: ClipboardCheck, label: "Audits à venir", value: "—", note: "prochaines échéances", color: "violet" },
] as const;

const tone: Record<string, { icon: string; badge: string }> = {
  blue: { icon: "bg-[#edf3ff] text-[#4068B5]", badge: "bg-[#edf3ff] text-[#4068B5]" },
  amber: { icon: "bg-[#fff4df] text-[#b77610]", badge: "bg-[#fff4df] text-[#b77610]" },
  rose: { icon: "bg-[#fff0f1] text-[#c84c5a]", badge: "bg-[#fff0f1] text-[#c84c5a]" },
  violet: { icon: "bg-[#f1ebff] text-[#8154c6]", badge: "bg-[#f1ebff] text-[#8154c6]" },
};

function KpiCard({ item }: { item: (typeof KPI)[number] }) {
  const Icon = item.icon;
  const colors = tone[item.color];
  return <div className="rounded-2xl border border-[#e6ebf2] bg-white p-3.5 shadow-[0_8px_24px_-18px_rgba(15,23,42,.3)] sm:p-4"><span className={`grid h-8 w-8 place-items-center rounded-xl ${colors.icon}`}><Icon className="h-4 w-4" /></span><p className="mt-3 text-[10px] font-extrabold uppercase tracking-[0.1em] text-[#8490a1]">{item.label}</p><p className="mt-1 font-display text-2xl font-extrabold tracking-tight text-[#142033]">{item.value}</p><p className="mt-1 text-[10px] text-[#8490a1]">{item.note}</p></div>;
}

function EmptyPanel({ icon: Icon, title, description }: { icon: typeof Activity; title: string; description: string }) {
  return <div className="rounded-2xl border border-dashed border-[#dfe7f1] bg-[#fbfdff] p-5"><div className="flex items-center gap-2"><Icon className="h-4 w-4 text-[#4068B5]" /><p className="text-xs font-extrabold text-[#142033]">{title}</p></div><p className="mt-4 text-[11px] leading-relaxed text-[#8490a1]">{description}</p></div>;
}

export function DashboardPreview() {
  return <section aria-label="Aperçu du tableau de bord LOGIQUALI" className="relative z-10 -mt-3 overflow-hidden pb-16 md:-mt-6 md:pb-24"><div className="pointer-events-none absolute left-1/2 top-10 h-80 w-[min(90vw,58rem)] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl" /><Reveal className="relative mx-auto max-w-6xl px-6"><div className="mx-auto max-w-5xl [perspective:1600px]"><div className="relative overflow-hidden rounded-[1.5rem] border border-[#dfe7f1] bg-[#f7faff] shadow-[0_38px_80px_-32px_rgba(44,78,126,.55),0_16px_32px_-18px_rgba(15,23,42,.2)] transition-transform duration-500 md:rounded-[2rem]" style={{ transform: "rotateX(5deg) rotateZ(-0.5deg)" }}>
    <div className="flex h-12 items-center justify-between border-b border-[#e4eaf2] bg-white/95 px-4 sm:h-14 sm:px-6"><div className="flex items-center gap-2.5"><span className="grid h-7 w-7 place-items-center rounded-lg bg-[#4068B5] text-[10px] font-extrabold text-white">LQ</span><span className="font-display text-xs font-extrabold tracking-tight text-[#182539]">LOGIQUALI</span></div><div className="hidden items-center gap-7 text-[10px] font-bold text-[#8390a2] sm:flex"><span className="text-[#4068B5]">Vue d'ensemble</span><span>Mon espace</span><span>Documents</span><span>Paramètres</span></div><div className="flex items-center gap-3"><Bell className="h-4 w-4 text-[#8490a1]" /><span className="h-7 w-7 rounded-full bg-[#dbe8ff]" /></div></div>
    <div className="grid grid-cols-[46px_1fr] sm:grid-cols-[140px_1fr]"><aside className="border-r border-[#e4eaf2] bg-white/70 p-2 sm:p-4"><p className="mb-3 hidden px-2 text-[9px] font-extrabold uppercase tracking-[0.15em] text-[#a1adbc] sm:block">Pilotage</p><div className="space-y-2">{NAV.map(([Icon, label], index) => <div key={label} className={`flex items-center justify-center rounded-xl p-2 sm:justify-start sm:gap-2.5 sm:px-2.5 ${index === 0 ? "bg-[#edf3ff] text-[#4068B5]" : "text-[#a1adbc]"}`}><Icon className="h-4 w-4 shrink-0" /><span className="hidden text-[9px] font-bold sm:block">{label}</span></div>)}</div></aside>
      <main className="min-w-0 p-4 sm:p-6 md:p-8"><div className="flex items-end justify-between gap-3"><div><p className="text-[9px] font-extrabold uppercase tracking-[0.16em] text-[#4068B5] sm:text-[10px]">Cockpit QHSE</p><h3 className="mt-1 font-display text-lg font-extrabold tracking-tight text-[#142033] sm:text-2xl">Vue d'ensemble</h3><p className="mt-1 text-[10px] text-[#8490a1] sm:text-xs">Pilotez votre système de management depuis un seul espace.</p></div><div className="hidden items-center gap-2 rounded-xl border border-[#e1e8f1] bg-white px-3 py-2 text-[10px] font-bold text-[#69788c] sm:flex"><span className="h-1.5 w-1.5 rounded-full bg-[#22a56f]" /> Site courant</div></div>
        <div className="mt-5 grid grid-cols-2 gap-2.5 sm:mt-7 sm:grid-cols-4 sm:gap-3">{KPI.map((item) => <KpiCard key={item.label} item={item} />)}</div>
        <div className="mt-3 grid gap-3 lg:grid-cols-[1.35fr_1fr]"><div className="rounded-2xl border border-[#e6ebf2] bg-white p-4 sm:p-5"><div className="flex items-center justify-between gap-3"><div><p className="text-[10px] font-bold uppercase tracking-[0.12em] text-[#8490a1]">Priorités</p><p className="mt-1 font-display text-sm font-extrabold text-[#142033] sm:text-base">À faire cette semaine</p></div><span className="rounded-lg bg-[#edf3ff] px-2 py-1 text-[9px] font-extrabold text-[#4068B5]">Mes actions</span></div><div className="mt-4 space-y-2.5"><div className="flex items-center gap-3 rounded-xl bg-[#fbfcfe] p-3"><span className="grid h-8 w-8 place-items-center rounded-lg bg-[#fff4df] text-[#b77610]"><AlertTriangle className="h-4 w-4" /></span><div className="min-w-0 flex-1"><p className="text-[11px] font-bold text-[#26344a]">Actions, audits et vérifications</p><p className="mt-0.5 text-[10px] text-[#8490a1]">Les échéances sont regroupées ici dès que votre site est connecté.</p></div><ArrowUpRight className="h-4 w-4 text-[#a1adbc]" /></div><div className="flex items-center gap-3 rounded-xl bg-[#fbfcfe] p-3"><span className="grid h-8 w-8 place-items-center rounded-lg bg-[#e9f9f0] text-[#16845d]"><CheckCircle2 className="h-4 w-4" /></span><div className="min-w-0 flex-1"><p className="text-[11px] font-bold text-[#26344a]">Suivi de la conformité</p><p className="mt-0.5 text-[10px] text-[#8490a1]">Visualisez les documents et preuves attendus par vos normes.</p></div><ArrowUpRight className="h-4 w-4 text-[#a1adbc]" /></div></div></div><div className="rounded-2xl border border-[#e6ebf2] bg-white p-4 sm:p-5"><div className="flex items-center justify-between"><div><p className="text-[10px] font-bold uppercase tracking-[0.12em] text-[#8490a1]">Activité</p><p className="mt-1 font-display text-sm font-extrabold text-[#142033] sm:text-base">Votre système, en un coup d'œil</p></div><Activity className="h-4 w-4 text-[#4068B5]" /></div><div className="mt-4 space-y-3"><EmptyPanel icon={GitBranch} title="Cartographie des processus" description="Retrouvez vos processus, leurs pilotes et leurs indicateurs au même endroit." /><EmptyPanel icon={CalendarClock} title="Échéances à venir" description="Les audits, actions et revues apparaissent automatiquement dans le cockpit." /></div></div></div>
        <div className="mt-3 grid gap-3 sm:grid-cols-3"><div className="rounded-2xl border border-[#e6ebf2] bg-white p-4"><div className="flex items-center gap-2 text-[#4068B5]"><BarChart3 className="h-4 w-4" /><p className="text-[10px] font-bold uppercase tracking-[0.1em]">Indicateurs</p></div><p className="mt-3 text-[11px] leading-relaxed text-[#8490a1]">Suivez vos résultats et préparez vos revues de direction.</p></div><div className="rounded-2xl border border-[#e6ebf2] bg-white p-4"><div className="flex items-center gap-2 text-[#16845d]"><Users className="h-4 w-4" /><p className="text-[10px] font-bold uppercase tracking-[0.1em]">Équipe</p></div><p className="mt-3 text-[11px] leading-relaxed text-[#8490a1]">Distribuez les responsabilités et impliquez les bons acteurs.</p></div><div className="rounded-2xl border border-[#e6ebf2] bg-white p-4"><div className="flex items-center gap-2 text-[#8154c6]"><ShieldAlert className="h-4 w-4" /><p className="text-[10px] font-bold uppercase tracking-[0.1em]">Risques & NC</p></div><p className="mt-3 text-[11px] leading-relaxed text-[#8490a1]">Priorisez les écarts et pilotez l'amélioration continue.</p></div></div>
      </main></div>
  </div></div></Reveal></section>;
}
