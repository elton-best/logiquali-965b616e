create table if not exists public.qhse_records (
  id uuid primary key default gen_random_uuid(),
  company_id uuid not null default auth.uid(),
  kind text not null,
  reference text not null default '',
  title text not null,
  status text not null default '',
  data jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists qhse_records_company_kind_idx on public.qhse_records (company_id, kind);

grant select, insert, update, delete on public.qhse_records to authenticated;
grant all on public.qhse_records to service_role;

alter table public.qhse_records enable row level security;

drop policy if exists "Company reads own records" on public.qhse_records;
create policy "Company reads own records" on public.qhse_records
  for select to authenticated using (company_id = auth.uid());
drop policy if exists "Company inserts own records" on public.qhse_records;
create policy "Company inserts own records" on public.qhse_records
  for insert to authenticated with check (company_id = auth.uid());
drop policy if exists "Company updates own records" on public.qhse_records;
create policy "Company updates own records" on public.qhse_records
  for update to authenticated using (company_id = auth.uid()) with check (company_id = auth.uid());
drop policy if exists "Company deletes own records" on public.qhse_records;
create policy "Company deletes own records" on public.qhse_records
  for delete to authenticated using (company_id = auth.uid());

drop trigger if exists qhse_records_updated_at on public.qhse_records;
create trigger qhse_records_updated_at
before update on public.qhse_records
for each row execute function public.set_updated_at();

-- Users may edit their profile details but never their own status or account type.
create or replace function public.protect_profile_fields()
returns trigger language plpgsql security definer set search_path = public as $$
begin
  if (new.status is distinct from old.status or new.account_type is distinct from old.account_type)
     and auth.uid() is not null
     and not public.has_role(auth.uid(), 'admin') then
    new.status := old.status;
    new.account_type := old.account_type;
  end if;
  return new;
end;
$$;

drop trigger if exists profiles_protect_fields on public.profiles;
create trigger profiles_protect_fields
before update on public.profiles
for each row execute function public.protect_profile_fields();