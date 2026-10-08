import {
  Activity,
  ArrowUpRight,
  Bell,
  CheckCircle2,
  FileCheck2,
  Gauge,
  MoreHorizontal,
  ShieldCheck,
  Users,
} from "lucide-react";
import { Reveal } from "@/components/lq/Reveal";

const TREND = [38, 46, 43, 57, 53, 68, 64, 76, 72, 86, 82, 94];

function MiniSparkline({ values, color = "#4068B5" }: { values: number[]; color?: string }) {
  const width = 116;
  const height = 34;
  const min = Math.min(...values);
  const max = Math.max(...values);
  const points = values
    .map((value, index) => {
      const x = (index / (values.length - 1)) * width;
      const y = height - ((value - min) / Math.max(1, max - min)) * (height - 6) - 3;
      return `${x},${y}`;
    })
    .join(" ");

  return (
    <svg
      viewBox={`0 0 ${width} ${height}`}
      className="h-9 w-full"
      role="img"
      aria-label="Évolution positive"
    >
      <polyline
        points={points}
        fill="none"
        stroke={color}
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth="2.5"
      />
    </svg>
  );
}

function ComplianceChart() {
  const width = 620;
  const height = 190;
  const points = TREND.map((value, index) => {
    const x = 18 + (index / (TREND.length - 1)) * (width - 36);
    const y = height - 28 - ((value - 30) / 70) * (height - 55);
    return [x, y] as const;
  });
  const line = points.map(([x, y]) => `${x},${y}`).join(" ");
  const area = `18,${height - 28} ${line} ${width - 18},${height - 28}`;

  return (
    <svg
      viewBox={`0 0 ${width} ${height}`}
      className="h-full w-full"
      role="img"
      aria-label="Évolution du niveau de conformité"
    >
      {[0, 1, 2, 3].map((row) => {
        const y = 28 + row * 43;
        return (
          <line
            key={row}
            x1="18"
            x2={width - 18}
            y1={y}
            y2={y}
            stroke="#e7edf5"
            strokeDasharray="4 6"
          />
        );
      })}
      <polygon points={area} fill="url(#preview-area)" />
      <polyline
        points={line}
        fill="none"
        stroke="#4068B5"
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth="4"
      />
      {points
        .filter((_, index) => index === points.length - 1)
        .map(([x, y]) => (
          <g key={`${x}-${y}`}>
            <circle cx={x} cy={y} r="7" fill="#fff" stroke="#4068B5" strokeWidth="3" />
            <circle cx={x} cy={y} r="3" fill="#4068B5" />
          </g>
        ))}
      <defs>
        <linearGradient id="preview-area" x1="0" x2="0" y1="0" y2="1">
          <stop offset="0" stopColor="#4068B5" stopOpacity="0.22" />
          <stop offset="1" stopColor="#4068B5" stopOpacity="0" />
        </linearGradient>
      </defs>
    </svg>
  );
}

function Kpi({
  icon: Icon,
  label,
  value,
  trend,
  color,
}: {
  icon: typeof Gauge;
  label: string;
  value: string;
  trend: string;
  color: string;
}) {
  return (
    <div className="rounded-2xl border border-[#e6ebf2] bg-white p-3.5 shadow-[0_8px_24px_-18px_rgba(15,23,42,.3)] sm:p-4">
      <div className="flex items-start justify-between gap-2">
        <span className={`grid h-8 w-8 place-items-center rounded-xl ${color}`}>
          <Icon className="h-4 w-4" />
        </span>
        <span className="inline-flex items-center gap-0.5 text-[10px] font-extrabold text-[#16845d]">
          <ArrowUpRight className="h-3 w-3" /> {trend}
        </span>
      </div>
      <p className="mt-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#8490a1]">
        {label}
      </p>
      <p className="mt-0.5 font-display text-xl font-extrabold tracking-tight text-[#142033] sm:text-2xl">
        {value}
      </p>
    </div>
  );
}

export function DashboardPreview() {
  return (
    <section
      aria-label="Aperçu du tableau de bord LOGIQUALI"
      className="relative z-10 -mt-3 overflow-hidden pb-16 md:-mt-6 md:pb-24"
    >
      <div className="pointer-events-none absolute left-1/2 top-10 h-80 w-[min(90vw,58rem)] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl" />
      <Reveal className="relative mx-auto max-w-6xl px-6">
        <div className="mx-auto max-w-5xl [perspective:1600px]">
          <div
            className="relative overflow-hidden rounded-[1.5rem] border border-[#dfe7f1] bg-[#f7faff] shadow-[0_38px_80px_-32px_rgba(44,78,126,.55),0_16px_32px_-18px_rgba(15,23,42,.2)] transition-transform duration-500 md:rounded-[2rem]"
            style={{ transform: "rotateX(7deg) rotateZ(-0.7deg)" }}
          >
            <div className="flex h-11 items-center justify-between border-b border-[#e4eaf2] bg-white/90 px-4 sm:h-14 sm:px-6">
              <div className="flex items-center gap-2.5">
                <span className="grid h-6 w-6 place-items-center rounded-lg bg-[#4068B5] text-[10px] font-extrabold text-white sm:h-7 sm:w-7">
                  LQ
                </span>
                <span className="font-display text-[11px] font-extrabold tracking-tight text-[#182539] sm:text-xs">
                  LOGIQUALI
                </span>
              </div>
              <div className="hidden items-center gap-7 text-[10px] font-bold text-[#8390a2] sm:flex">
                <span className="text-[#4068B5]">Vue d'ensemble</span>
                <span>Documents</span>
                <span>Audits</span>
                <span>Actions</span>
              </div>
              <div className="flex items-center gap-2">
                <Bell className="h-3.5 w-3.5 text-[#8490a1]" />
                <span className="h-6 w-6 rounded-full bg-[#dbe8ff] sm:h-7 sm:w-7" />
              </div>
            </div>

            <div className="grid grid-cols-[42px_1fr] sm:grid-cols-[116px_1fr]">
              <aside className="border-r border-[#e4eaf2] bg-white/70 p-2 sm:p-4">
                <div className="space-y-2 sm:space-y-3">
                  {[
                    { icon: Gauge, active: true },
                    { icon: FileCheck2, active: false },
                    { icon: Activity, active: false },
                    { icon: Users, active: false },
                    { icon: ShieldCheck, active: false },
                  ].map(({ icon: Icon, active }, index) => (
                    <div
                      key={index}
                      className={`flex items-center justify-center rounded-xl p-2 sm:justify-start sm:gap-2.5 sm:px-2.5 ${active ? "bg-[#edf3ff] text-[#4068B5]" : "text-[#a1adbc]"}`}
                    >
                      <Icon className="h-4 w-4 shrink-0" />
                      <span className="hidden text-[9px] font-bold sm:block">
                        {["Synthèse", "Documents", "Audits", "Équipe", "Sécurité"][index]}
                      </span>
                    </div>
                  ))}
                </div>
              </aside>

              <main className="min-w-0 p-4 sm:p-6 md:p-8">
                <div className="flex items-end justify-between gap-3">
                  <div>
                    <p className="text-[9px] font-extrabold uppercase tracking-[0.16em] text-[#4068B5] sm:text-[10px]">
                      Cockpit QHSE
                    </p>
                    <h3 className="mt-1 font-display text-lg font-extrabold tracking-tight text-[#142033] sm:text-2xl">
                      Vue d'ensemble
                    </h3>
                    <p className="mt-1 text-[10px] text-[#8490a1] sm:text-xs">
                      Votre conformité, claire et pilotable.
                    </p>
                  </div>
                  <div className="hidden items-center gap-2 rounded-xl border border-[#e1e8f1] bg-white px-3 py-2 text-[10px] font-bold text-[#69788c] sm:flex">
                    <span className="h-1.5 w-1.5 rounded-full bg-[#22a56f]" /> Juil. — Août 2025
                  </div>
                </div>

                <div className="mt-5 grid grid-cols-2 gap-2.5 sm:mt-7 sm:grid-cols-4 sm:gap-3">
                  <Kpi
                    icon={Gauge}
                    label="Conformité globale"
                    value="84 %"
                    trend="+12 %"
                    color="bg-[#edf3ff] text-[#4068B5]"
                  />
                  <Kpi
                    icon={FileCheck2}
                    label="Documents actifs"
                    value="128"
                    trend="+8"
                    color="bg-[#e9f9f0] text-[#16845d]"
                  />
                  <Kpi
                    icon={Activity}
                    label="Actions ouvertes"
                    value="24"
                    trend="-6"
                    color="bg-[#fff4df] text-[#b77610]"
                  />
                  <Kpi
                    icon={ShieldCheck}
                    label="Audits à venir"
                    value="06"
                    trend="+2"
                    color="bg-[#f1ebff] text-[#8154c6]"
                  />
                </div>

                <div className="mt-3 grid gap-3 lg:grid-cols-[1.5fr_1fr]">
                  <div className="rounded-2xl border border-[#e6ebf2] bg-white p-4 sm:p-5">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-[10px] font-bold uppercase tracking-[0.12em] text-[#8490a1]">
                          Performance
                        </p>
                        <p className="mt-1 font-display text-sm font-extrabold text-[#142033] sm:text-base">
                          Niveau de conformité
                        </p>
                      </div>
                      <span className="rounded-lg bg-[#e9f9f0] px-2 py-1 text-[9px] font-extrabold text-[#16845d]">
                        + 12,4 %
                      </span>
                    </div>
                    <div className="mt-3 h-32 sm:h-40">
                      <ComplianceChart />
                    </div>
                    <div className="mt-1 flex justify-between text-[9px] font-semibold text-[#a0abba]">
                      <span>Sept.</span>
                      <span>Oct.</span>
                      <span>Nov.</span>
                      <span>Déc.</span>
                      <span>Jan.</span>
                      <span>Fév.</span>
                    </div>
                  </div>
                  <div className="rounded-2xl border border-[#e6ebf2] bg-white p-4 sm:p-5">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-[10px] font-bold uppercase tracking-[0.12em] text-[#8490a1]">
                          Priorités
                        </p>
                        <p className="mt-1 font-display text-sm font-extrabold text-[#142033]">
                          À traiter cette semaine
                        </p>
                      </div>
                      <MoreHorizontal className="h-4 w-4 text-[#a0abba]" />
                    </div>
                    <div className="mt-3 space-y-3">
                      {[
                        ["Revue documentaire ISO 9001", "Aujourd'hui", "bg-[#4068B5]"],
                        ["Clôturer la NC-2025-024", "Demain", "bg-[#e29b26]"],
                        ["Audit interne — site Cotonou", "12 août", "bg-[#22a56f]"],
                      ].map(([label, date, dot]) => (
                        <div key={label} className="flex items-center gap-2.5">
                          <span className={`h-2 w-2 shrink-0 rounded-full ${dot}`} />
                          <span className="min-w-0 flex-1 truncate text-[10px] font-bold text-[#334257]">
                            {label}
                          </span>
                          <span className="shrink-0 text-[9px] font-semibold text-[#9ba7b6]">
                            {date}
                          </span>
                        </div>
                      ))}
                    </div>
                    <div className="mt-5 border-t border-[#edf0f4] pt-4">
                      <div className="flex items-center gap-2 text-[10px] font-bold text-[#16845d]">
                        <CheckCircle2 className="h-3.5 w-3.5" /> 18 tâches terminées ce mois
                      </div>
                      <MiniSparkline values={TREND} color="#22a56f" />
                    </div>
                  </div>
                </div>
              </main>
            </div>
            <div className="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-[#f7faff]/85 to-transparent" />
          </div>
        </div>
        <p className="relative mt-8 text-center text-xs font-semibold tracking-wide text-muted-foreground">
          Un cockpit unique pour transformer vos données QHSE en décisions.
        </p>
      </Reveal>
    </section>
  );
}
