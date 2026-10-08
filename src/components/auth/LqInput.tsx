import { useState, type InputHTMLAttributes } from "react";
import { Eye, EyeOff, type LucideIcon } from "lucide-react";

type LqInputProps = InputHTMLAttributes<HTMLInputElement> & {
  label: string;
  icon: LucideIcon;
  error?: string;
};

export function LqInput({ label, icon: Icon, error, type, id, ...props }: LqInputProps) {
  const [show, setShow] = useState(false);
  const isPassword = type === "password";
  const inputId = id ?? label.toLowerCase().replace(/\s+/g, "-");

  return (
    <div>
      <label htmlFor={inputId} className="mb-1.5 block text-xs font-bold text-muted-foreground">
        {label}
      </label>
      <div className="group relative">
        <Icon className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-primary" />
        <input
          id={inputId}
          type={isPassword ? (show ? "text" : "password") : type}
          className={`h-[52px] w-full rounded-2xl border-[1.5px] bg-card pl-12 pr-12 text-sm font-medium text-foreground outline-none transition-all placeholder:text-muted-foreground/70 focus:shadow-[0_0_0_4px] focus:shadow-primary/15 ${
            error ? "border-destructive focus:border-destructive" : "border-input focus:border-primary"
          }`}
          {...props}
        />
        {isPassword && (
          <button
            type="button"
            onClick={() => setShow(!show)}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
            aria-label={show ? "Masquer le mot de passe" : "Afficher le mot de passe"}
          >
            {show ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
          </button>
        )}
      </div>
      {error && <p className="mt-1.5 text-xs font-semibold text-destructive">{error}</p>}
    </div>
  );
}
