ALTER TABLE public.qhse_records ADD COLUMN IF NOT EXISTS target_company_id uuid;
CREATE INDEX IF NOT EXISTS qhse_records_target_idx ON public.qhse_records(target_company_id);
DROP POLICY IF EXISTS "Target company reads complaints" ON public.qhse_records;
CREATE POLICY "Target company reads complaints" ON public.qhse_records FOR SELECT TO authenticated USING (target_company_id = auth.uid());
DROP POLICY IF EXISTS "Target company updates complaints" ON public.qhse_records;
CREATE POLICY "Target company updates complaints" ON public.qhse_records FOR UPDATE TO authenticated USING (target_company_id = auth.uid()) WITH CHECK (target_company_id = auth.uid());
CREATE OR REPLACE FUNCTION public.list_companies()
RETURNS TABLE(id uuid, name text) LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
  SELECT p.id, COALESCE(NULLIF(p.company_name,''), p.email) FROM public.profiles p
  WHERE p.account_type = 'company' AND p.status = 'active' ORDER BY 2
$$;
REVOKE ALL ON FUNCTION public.list_companies() FROM public, anon;
GRANT EXECUTE ON FUNCTION public.list_companies() TO authenticated;