import { backendApi, type BackendProfile } from "@/integrations/backend/client";

/** Profile contract consumed by the existing TanStack routes and dashboards. */
export const getMyProfile = async (): Promise<BackendProfile> => backendApi.auth.me();

export type Profile = BackendProfile;

export async function updateMyProfile(input: Record<string, unknown>): Promise<BackendProfile> {
  return backendApi.profile.update(input);
}
