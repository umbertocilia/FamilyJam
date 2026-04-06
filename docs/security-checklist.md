# Security Checklist

## Implemented Controls

- CSRF protection is enabled globally with session-backed tokens in `app/Config/Security.php`.
- Secure headers are applied globally through the `secureheaders` filter in `app/Config/Filters.php`.
- Login throttling is enforced by `RateLimitLoginFilter` and `LoginThrottleService`.
- Authenticated requests are guarded by `AuthenticatedSessionFilter`.
- Household-scoped pages are guarded by `CurrentHouseholdFilter` and route-level `permission:*` filters.
- RBAC is resolved per household through `HouseholdAuthorizationService`.
- Sensitive mutations are executed in services, not in views or route closures.
- Critical write flows use database transactions: household provisioning, invitation accept/revoke, expense create/update/delete, settlement create, role assignment, settings update.
- Output rendering uses escaped view helpers as the default pattern.
- Uploaded files are stored outside `public/`, renamed server-side, MIME-checked, extension-checked, size-limited, and checksum-tracked.
- Session identifiers are regenerated on login/logout and authenticated sessions now include a request fingerprint.
- Authenticated sessions are invalidated if the stored user no longer resolves as active.
- Global HTTPS enforcement is enabled automatically in production through `App.forceGlobalSecureRequests`.

## Sensitive Actions Audited

- `expense.created`
- `expense.updated`
- `expense.deleted`
- `settlement.created`
- `invitation.created`
- `invitation.accepted`
- `invitation.revoked`
- `role.created`
- `role.updated`
- `membership.roles_updated`
- `household.settings_updated`
- auth lifecycle events already present: `auth.registered`, `auth.login`, `auth.login_failed`, `auth.logout`, `auth.email_verified`

## Snapshot Policy

- `before_json` and `after_json` are stored when a mutation changes an existing record or assignment state.
- Invitation snapshots are sanitized to exclude `token_hash`.
- Role assignment audit stores before/after role ids and role codes.
- Household settings audit stores both household-level fields and `household_settings` fields.

## Upload Constraints

- Allowed MIME types: `image/jpeg`, `image/png`, `image/webp`, `application/pdf`
- Max file size: `8 MB`
- Client extension must match the detected MIME mapping.
- Empty, already-moved, or malformed uploads are rejected.
- Original filenames are sanitized before persistence.

## Household Isolation Expectations

- Main business entities are queried through `household_id`-scoped models or service guards.
- Route access alone is not considered sufficient: services re-resolve membership against the current household identifier.
- Cross-tenant access attempts should return `null`, throw a domain/security exception, or redirect to login depending on the layer.

## Current Limits

- Settlement deletion is not implemented in the current product flow, so there is no `settlement.deleted` path yet.
- CSP is not forced on globally in this revision because some reporting/chart fragments still rely on inline style attributes.
- Virus scanning is not integrated; uploads are restricted by MIME, extension, path isolation, and size only.
- `Cookie.secure` and global secure-request enforcement are enabled automatically in production, but reverse-proxy headers still need correct deployment configuration.
