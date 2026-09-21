# GN-POMS frontend redesign

## What changed

- `frontend/src/components/Layout.jsx`, `Icon.jsx`, `Charts.jsx`, `UI.jsx`, and `design.css`: navy sidebar, compact header, responsive navigation, accessible icons, shared form/table/card styles, Recharts visualizations, search, profile menu and language selector. Reference screenshots were not embedded or cropped. The login landscape is CSS artwork. Shared branding now uses a locally stored Sri Lankan emblem SVG from Wikimedia Commons with an HTML wordmark; see `frontend/public/branding/README.md` for attribution.
- `frontend/src/pages/Public.jsx`: split login layout, password visibility, remember-me and existing Laravel session login/registration.
- `Dashboard.jsx`, `Reports.jsx`: real scoped statistics, charts, report summaries and Excel/PDF exports. No invented growth percentages or sample officer records.
- `Records.jsx`, `Officers.jsx`: filtered officer directory, Excel/PDF exports, four-step add/edit form, dependent rows, profile tabs and historical service events. Existing user, signatory and service-history editors remain available. User editing includes account status and password changes; last login comes from recorded login audits.
- `Documents.jsx`: scoped filters, inline preview, download, authorized deletion and drag/drop upload with PDF/JPEG/PNG validation (10 MB maximum). Bulk export exports document metadata to Excel.
- `GenerateLetter.jsx`, `Letters.jsx`: generated-letter directory and two-column letter preparation, actual server PDF preview, finalization and downloads. Existing batch creation/import/generation/editing workflows remain at `/letters/batches`.
- `Administration.jsx`: read-only audit browsing with previous/new values and settings overview. Existing signatory and batch configuration links are retained.
- `lib/i18n.jsx`, `lib/permissions.js`, `App.jsx`: central translation foundation, role-aware navigation, dedicated routes and existing 404 behavior.

## Laravel changes

- `WorkspaceController.php`: scoped officer details, generated-letter listing, analytics and administrator-only audit listing.
- `DirectoryController.php`: rich filters, exports, extended officer/document validation, inline document responses, last-login lookup and service-change history. Editing service fields records a **profile correction** with the original/new values and edit date; it does not invent an appointment or promotion date.
- `TabularExportService.php`: real XLSX/PDF downloads. XLSX cells are explicitly text to prevent spreadsheet formula injection. Directory exports are limited to 5,000 filtered records.
- `BatchController.php`: scoped finalized-letter viewing for officers, stronger letter ownership checks and PDF download disposition. Existing generation and finalization services remain in use.
- `UserRole.php`/`User.php`: read-only `ministry_head` role. Existing `main_admin` is displayed as Super Admin; existing district and divisional roles retain their access boundaries.
- Migration `2026_09_21_000001_extend_officer_directory.php`: additive designation, contact email, mobile, dependent JSON and issuing-authority fields. Applied to the local database. No existing tables or records were replaced.

## Remaining integrations and deliberate limits

- TOTP/MFA, automated password-reset email and notification delivery are not implemented. Login uses the existing email identity; separate usernames are not stored.
- Navigation translations exist for English/Sinhala/Tamil. Full-page translations still require translated content and connecting remaining copy to the central catalog.
- Settings show real configuration and existing editing routes. CRUD APIs for location directories, centrally managed designations/grades/document types and global settings are not implemented.
- Officer drafts are not supported by the backend; the wizard does not claim to save incomplete drafts. Profile image upload is not implemented; avatars use initials.
- No official seal/signature is fabricated. Letter wording, PDF fonts, seals and signatures use the existing backend template configuration. Letter reference generation retains the existing NIC suffix. Transfer-target and advanced template fields remain available through batch settings.
- The unsaved letter panel is explicitly a layout guide. Save Draft produces the actual approved-template PDF; finalization archives it and may update service records.
- Export PDF uses the existing PDF package and default export font. Sinhala/Tamil export typography still needs visual verification and an approved Unicode font configuration.
- Reference-level visual verification remains outstanding because the in-app browser connector was unavailable. Responsive rules and DOM workflows were checked, but this is not a claim of pixel-exact matching or completed browser-console QA.

## Running and validation

Frontend: `cd frontend` then `npm run dev` (http://127.0.0.1:5173).

Backend: `cd backend`, `php artisan migrate`, then `php artisan serve --host=127.0.0.1`. PHP must have the application's required extensions enabled, including GD and ZIP for PDF/spreadsheet workflows. `frontend/.env` can configure `BACKEND_URL`; session cookies and CSRF remain on the same-origin `/api` proxy.

Checks: `npm run build`, `npm test`, and `php -d extension=zip -d extension=gd artisan test`. The latter flags are for this machine's PHP configuration; omit them if already enabled in php.ini.

Regression coverage includes session login/logout, registration, permissions, regional data/export scope, ministry read-only access, document upload rejection, profile tabs, wizard value retention, dependent persistence, service-history preservation and existing batch/PDF workflows.
