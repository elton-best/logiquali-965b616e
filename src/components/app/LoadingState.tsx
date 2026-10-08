export function ListLoading({ rows = 5 }: { rows?: number }) {
  return (
    <div className="space-y-3" aria-busy="true" aria-label="Chargement des données">
      {Array.from({ length: rows }, (_, index) => (
        <div
          key={index}
          className="flex items-center gap-3 rounded-2xl border border-border bg-card p-4 shadow-sm"
        >
          <div className="h-10 w-10 shrink-0 animate-pulse rounded-xl bg-primary/10" />
          <div className="min-w-0 flex-1 space-y-2">
            <div className={`h-3 animate-pulse rounded-full bg-secondary ${index % 2 ? "w-3/5" : "w-4/5"}`} />
            <div className="h-2.5 w-2/5 animate-pulse rounded-full bg-secondary" />
          </div>
          <div className="hidden h-6 w-20 animate-pulse rounded-full bg-primary/10 sm:block" />
        </div>
      ))}
    </div>
  );
}
