import { useEffect, useState } from "react";
import { backendApi } from "@/integrations/backend/client";

/** Lightweight session flag for UI affordances (navbar, CTAs). */
export function useSignedIn() {
  const [signedIn, setSignedIn] = useState(() => Boolean(backendApi.auth.hasToken()));

  useEffect(() => {
    let alive = true;
    const check = async () => {
      if (!backendApi.auth.hasToken()) {
        if (alive) setSignedIn(false);
        return;
      }
      try {
        await backendApi.auth.me();
        if (alive) setSignedIn(true);
      } catch {
        if (alive) setSignedIn(false);
      }
    };
    check();
    window.addEventListener("lq-auth-changed", check);
    return () => {
      alive = false;
      window.removeEventListener("lq-auth-changed", check);
    };
  }, []);

  return signedIn;
}
