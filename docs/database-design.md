# FamilyJam Database Design

## ERD testuale

- `users` 1--n `households`
  - `households.created_by -> users.id`
- `users` 1--1 `user_preferences`
  - `user_preferences.user_id -> users.id`
  - `user_preferences.default_household_id -> households.id`
- `households` 1--1 `household_settings`
  - `household_settings.household_id -> households.id`
- `households` 1--n `household_memberships`
  - `household_memberships.household_id -> households.id`
  - `household_memberships.user_id -> users.id`
- `household_memberships` n--n `roles` tramite `membership_roles`
  - `membership_roles.membership_id -> household_memberships.id`
  - `membership_roles.role_id -> roles.id`
- `roles` n--n `permissions` tramite `role_permissions`
  - `role_permissions.role_id -> roles.id`
  - `role_permissions.permission_id -> permissions.id`
- `households` 1--n `invitations`
  - `invitations.household_id -> households.id`
  - `invitations.role_id -> roles.id`
- `households` 1--n `expense_categories`
  - categorie di sistema con `household_id = NULL`
- `households` 1--n `recurring_rules`
- `households` 1--n `expenses`
  - `expenses.recurring_rule_id -> recurring_rules.id`
  - `expenses.category_id -> expense_categories.id`
  - `expenses.receipt_attachment_id -> attachments.id`
- `expenses` 1--n `expense_payers`
- `expenses` 1--n `expense_splits`
- `households` 1--n `settlements`
  - `settlements.attachment_id -> attachments.id`
- `households` 1--n `chores`
  - `chores.recurring_rule_id -> recurring_rules.id`
- `chores` 1--n `chore_occurrences`
- `households` 1--n `shopping_lists`
- `shopping_lists` 1--n `shopping_items`
  - `shopping_items.converted_expense_id -> expenses.id`
- `households` 1--n `pinboard_posts`
- `pinboard_posts` 1--n `pinboard_comments`
- `attachments` puo essere collegata polimorficamente a qualunque entita tramite `entity_type` + `entity_id`
- `users` 1--n `notifications`
  - `notifications.household_id` opzionale
- `households` 1--n `audit_logs`
  - `audit_logs.actor_user_id` opzionale

## Tabelle e responsabilita

- `users`: account applicativi.
- `user_preferences`: preferenze utente persistenti e household preferita.
- `households`: anagrafica tenant/workspace.
- `household_settings`: impostazioni estese del tenant.
- `household_memberships`: legame utente-household.
- `invitations`: inviti email-based con lifecycle.
- `roles`: ruoli globali di sistema e ruoli custom household.
- `permissions`: catalogo permessi granulari.
- `role_permissions`: grant dei permessi ai ruoli.
- `membership_roles`: ruoli assegnati alle membership.
- `expense_categories`: categorie spesa globali o household-specific.
- `recurring_rules`: motore ricorrenze per expenses e chores.
- `expenses`: testata spesa condivisa.
- `expense_payers`: chi ha pagato e quanto.
- `expense_splits`: quanto deve ciascun membro.
- `settlements`: rimborsi manuali.
- `chores`: definizione faccenda.
- `chore_occurrences`: istanze schedulate/completate/skippate.
- `shopping_lists`: liste spesa.
- `shopping_items`: item di lista.
- `pinboard_posts`: post di bacheca.
- `pinboard_comments`: commenti ai post.
- `attachments`: file metadata e binding polimorfico.
- `notifications`: feed in-app utente.
- `audit_logs`: trail eventi sensibili.

## Note su vincoli e performance

- Charset e collation standardizzati su `utf8mb4` + `utf8mb4_unicode_ci`.
- Tutte le foreign key tenant-critical usano `CASCADE` dove l'entita figlia non puo sopravvivere al tenant.
- Soft delete usato sulle entita funzionali che possono essere archiviate o ripristinate.
- `roles` e `expense_categories` usano `scope_household_id` generated column per avere univocita reale per scope `system` o `household`.
- Indici principali:
  - dashboard finance: `expenses(household_id, expense_date)`, `settlements(household_id, settlement_date)`
  - dashboard chores: `chore_occurrences(household_id, status, due_at)`
  - shopping mobile: `shopping_items(shopping_list_id, is_purchased, position)`
  - notification center: `notifications(user_id, read_at, created_at)`
  - audit lookup: `audit_logs(household_id, created_at)` e `audit_logs(entity_type, entity_id, created_at)`
  - recurring engine: `recurring_rules(household_id, entity_type, is_active, next_run_at)`
- `attachments` resta senza FK polimorfica verso le entita applicative per evitare rigidita e cicli; l'integrita applicativa e demandata ai service layer.
- `membership_roles` sostituisce il vecchio legame singolo membership-role e consente combinazioni di ruoli senza cambiare RBAC di base.

## Ordine di esecuzione SQL raw

1. `database/mysql/001_users_households.sql`
2. `database/mysql/002_authorization.sql`
3. `database/mysql/003_attachments_finance.sql`
4. `database/mysql/004_chores_shopping_pinboard.sql`
5. `database/mysql/005_notifications_audit.sql`
6. `database/mysql/006_seed_base_data.sql`
