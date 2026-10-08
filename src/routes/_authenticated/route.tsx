import { createFileRoute, Outlet, redirect } from "@tanstack/react-router";
import { BackendApiError, backendApi } from "@/integrations/backend/client";

export const Route = createFileRoute("/_authenticated")({
  // Auth is backed by the Laravel Sanctum token and is intentionally resolved
  // in the browser. This avoids trying to read a browser token during SSR.
  ssr: false,
  beforeLoad: async () => {
    try {
      const profile = await backendApi.auth.me();
      return { user: profile.raw, profile };
    } catch (error) {
      if (!(error instanceof BackendApiError) || error.status === 401 || error.status === 0) {
        backendApi.auth.clearToken();
      }
      throw redirect({ to: "/auth/login" });
    }
  },
  component: () => <Outlet />,
});
