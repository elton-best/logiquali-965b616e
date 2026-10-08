import { useEffect, useRef, useState } from "react";

/** RT-05 — horizontal scrollbar shown both above and below a wide table. */
export function DoubleScroll({ children, className = "" }: { children: React.ReactNode; className?: string }) {
  const top = useRef<HTMLDivElement>(null);
  const body = useRef<HTMLDivElement>(null);
  const [w, setW] = useState(0);
  useEffect(() => {
    const el = body.current;
    if (!el) return;
    const ro = new ResizeObserver(() => setW(el.scrollWidth));
    ro.observe(el);
    if (el.firstElementChild) ro.observe(el.firstElementChild);
    return () => ro.disconnect();
  }, []);
  const sync = (from: HTMLDivElement | null, to: HTMLDivElement | null) => { if (from && to && to.scrollLeft !== from.scrollLeft) to.scrollLeft = from.scrollLeft; };
  return (
    <div className={className}>
      <div ref={top} onScroll={() => sync(top.current, body.current)} className="overflow-x-auto" aria-hidden>
        <div style={{ width: w, height: 1 }} />
      </div>
      <div ref={body} onScroll={() => sync(body.current, top.current)} className="overflow-x-auto">{children}</div>
    </div>
  );
}
