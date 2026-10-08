import { Link } from "@tanstack/react-router";
import { ArrowRight } from "lucide-react";
import { useMemo, type ReactNode } from "react";

type LqButtonProps = {
  children: ReactNode;
  to?: string;
  href?: string;
  type?: "button" | "submit";
  variant?: "primary" | "ghost" | "white";
  size?: "sm" | "md" | "lg";
  className?: string;
  withArrow?: boolean;
  onClick?: () => void;
};

const SIZES = {
  sm: "h-10 px-5 text-sm",
  md: "h-12 px-7 text-[15px]",
  lg: "h-14 px-9 text-base",
};

function Particles() {
  const particles = useMemo(
    () =>
      Array.from({ length: 10 }, (_, i) => ({
        left: `${(i * 37 + 11) % 94}%`,
        top: `${(i * 53 + 17) % 80 + 10}%`,
        size: 2 + ((i * 7) % 3),
        delay: `${(i * 0.31) % 2.4}s`,
        duration: `${2.6 + ((i * 13) % 14) / 10}s`,
      })),
    []
  );
  return (
    <span aria-hidden className="pointer-events-none absolute inset-0">
      {particles.map((p, i) => (
        <span
          key={i}
          className="lq-particle"
          style={{
            left: p.left,
            top: p.top,
            width: p.size,
            height: p.size,
            animationDelay: p.delay,
            animationDuration: p.duration,
          }}
        />
      ))}
    </span>
  );
}

export function LqButton({
  children,
  to,
  href,
  type = "button",
  variant = "primary",
  size = "md",
  className = "",
  withArrow = false,
  onClick,
}: LqButtonProps) {
  const base =
    variant === "primary"
      ? "btn-lq"
      : variant === "ghost"
        ? "btn-lq-ghost"
        : "rounded-xl bg-card text-primary font-semibold shadow-lg shadow-primary/10 transition-transform active:scale-95 hover:shadow-xl";
  const cls = `group inline-flex items-center justify-center gap-2 ${base} ${SIZES[size]} ${className}`;
  const inner = (
    <>
      {variant === "primary" && <Particles />}
      <span className="relative z-10">{children}</span>
      {withArrow && (
        <ArrowRight className="relative z-10 h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
      )}
    </>
  );

  if (to) {
    return (
      <Link to={to} className={cls} onClick={onClick}>
        {inner}
      </Link>
    );
  }
  if (href) {
    return (
      <a href={href} className={cls} onClick={onClick}>
        {inner}
      </a>
    );
  }
  return (
    <button type={type} className={cls} onClick={onClick}>
      {inner}
    </button>
  );
}
