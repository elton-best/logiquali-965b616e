import { createServerFn } from "@tanstack/react-start";
import { requireSupabaseAuth } from "@/integrations/supabase/auth-middleware";

export const getMyProfile = createServerFn({ method: "GET" })
  .middleware([requireSupabaseAuth])
  .handler(async ({ context }) => {
    const { data, error } = await context.supabase
      .from("profiles")
      .select(
        "id, email, first_name, last_name, phone, account_type, status, company_name, company_rccm, company_ifu, company_address, created_at"
      )
      .eq("id", context.userId)
      .single();
    if (error) throw new Error("Profil introuvable");
    return data;
  });

export type Profile = Awaited<ReturnType<typeof getMyProfile>>;
