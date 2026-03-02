# Implementation Plan: Enterprise CMS Platform

Analysis of `prompt.txt`, the Business Requirements Document, and the **CASE MANAGEMENT SYSTEM.xlsm** workbook. Case data fields below are aligned with the workbook column set.

---

## 1. High-Level Summary

| Area | Scope |
|------|--------|
| **Stack** | Laravel 12, PostgreSQL, Blade, Bootstrap 5 |
| **Architecture** | Core platform + pluggable modules (CaseManagement = Module 1) |
| **Auth** | Spatie Laravel Permission (RBAC), 4 roles, permission-based (no role hardcoding in views) |
| **UI** | Fixed sidebar, top navbar, Bootstrap 5, reusable Blade components |
| **Screens** | Login, Dashboard, Case List/Register/View/Edit, Reports, Admin (Users, Roles, Settings) |
| **Case fields (workbook)** | Serial Number, Date Filed, Reference Number, Defendant, Nature of Claim, Officer Dealing, Entered By, Cause Number, Claimant, Documents (see §5.0) |

**Base platform design:** The system is a **base platform** that can easily accommodate future modules (e.g. Complaints, HR, Asset Management, Licensing, Finance, Workflow Automation) **without architectural changes**. New modules are added by implementing `ModuleInterface`, registering in `ModulesServiceProvider`, and (optionally) adding a view namespace—no changes to Core, registry, or layout are required. Placeholder entries for these future modules appear in the sidebar until each is implemented.

---

## 2. Phased Implementation Plan

### Phase 1: Project Bootstrap & Core Structure

**1.1 Laravel 12 + PostgreSQL setup**
- Create Laravel 12 project (`composer create-project laravel/laravel .`).
- Configure `.env` for PostgreSQL (DB_CONNECTION=pgsql, host, database, username, password).
- Verify DB connection and run default migrations.

**1.2 Directory structure**
- Create `app/Core/` with subfolders: `Auth`, `RBAC`, `Audit`, `Settings`, `Dashboard`.
- Create `app/Modules/` and `app/Modules/CaseManagement/` with: `Controllers`, `Models`, `Services`, `Requests`, `Policies`, `Migrations`, `Views`, `Routes`.
- Add PSR-4 namespaces in `composer.json` for `App\Core\*` and `App\Modules\CaseManagement\*`.
- Create base `Module` interface/abstract and a `ModuleServiceProvider` pattern so each module registers routes, permissions, and menu items.

**1.3 Module registration**
- Create `app/Core/Support/ModuleRegistry.php` (or similar) to discover and boot modules.
- Register modules in `AppServiceProvider` or a dedicated `ModulesServiceProvider`.
- Each module exposes: `routes()`, `permissions()`, `menuItems()`.

---

### Phase 2: Authentication & RBAC (Spatie)

**2.1 Install and configure Spatie Laravel Permission**
- `composer require spatie/laravel-permission`.
- Publish config and migrations; run migrations.
- Use PostgreSQL-compatible schema (Spatie supports it).

**2.2 Core Auth scaffolding**
- Use Laravel Breeze or Fortify, or build minimal custom: login, logout, password reset (using Laravel’s built-in reset).
- Ensure login uses `username` (or email) + password as per mockup; add `username` to users table if needed.
- Implement “Remember me”, “Forgot password” link, and throttle/audit for failed attempts.

**2.3 Roles and permissions**
- Create migrations/seeders for:
  - **Roles:** Super Admin, Administrator, Officer, Viewer.
  - **Permissions:** e.g. `dashboard.view`, `cases.view`, `cases.create`, `cases.edit`, `cases.assign`, `reports.view`, `reports.export`, `admin.users`, `admin.roles`, `admin.settings`, etc.
- Assign permissions to roles in seeders (no hardcoded role checks in code; use `@can` / `Gate` / policies).
- Create middleware (or use Spatie’s) to enforce permissions on routes.
- Ensure “Admin only” menu items are driven by permissions (e.g. `admin.*`), not role names.

**2.4 Audit for auth events**
- Create `app/Core/Audit/` service and model (e.g. `AuditLog`) for login success/failure, logout, password reset.
- Log user_id, action, ip, user_agent, timestamp; use a dedicated table and inject the service where needed.

---

### Phase 3: Base UI Framework (Bootstrap 5 + Blade)

**3.1 Front-end stack**
- Add Bootstrap 5 (CDN or NPM); ensure no conflict with Laravel’s default Vite setup (use Vite and import Bootstrap JS/CSS, or Blade-only with CDN).
- Optional: Alpine.js or minimal JS for dropdowns/collapse if not using Bootstrap’s JS bundle.

**3.2 Global layout**
- Create `resources/views/layouts/app.blade.php`:
  - Fixed sidebar (collapsible), top navbar (system name, notifications, user avatar + dropdown: Profile, Change password, Logout).
  - Content area: page title, breadcrumbs, action buttons (right-aligned).
- Use a single layout for all authenticated pages; login uses a separate layout (e.g. `layouts/guest.blade.php`).

**3.3 Reusable Blade components**
- **Tables:** `<x-table>` or partial with sortable headers, pagination, slot for rows.
- **Forms:** `<x-input>`, `<x-select>`, `<x-textarea>` with validation error display.
- **Alerts:** `<x-alert type="success|danger|warning">`.
- **Modals:** `<x-modal id="...">` with slot.
- **Badges:** `<x-badge status="open|closed|...">` for case status and similar.

**3.4 Menu and breadcrumbs**
- Sidebar menu items come from Core (Dashboard) + registered modules (e.g. Case Management submenu: Register Case, Case List, Reports; Admin: Users, Roles & Permissions, System Settings).
- Filter menu by current user’s permissions.
- Breadcrumbs: build from route name or a small breadcrumb registry per page.

---

### Phase 4: Core Features (Dashboard, Settings, Audit)

**4.1 Dashboard (Core)**
- Route: `/dashboard` (or `/`). Controller in `App\Core\Dashboard\DashboardController`.
- KPI cards: Total Cases, Open Cases, In Progress, Closed (data from Case Management module via a contract or module API).
- Middle section: Bar chart (Cases by Status), Pie chart (Cases by Category) — use Chart.js or similar, data from Case module.
- Bottom: Recent Activity table (from Core Audit or Case audit) — Date, User, Action, Case Reference.
- Dashboard should not depend on Case Management directly in core; use events or a “dashboard metrics” contract that Case module implements.

**4.2 Settings (Core)**
- `app/Core/Settings/`: key-value or table for system settings (e.g. site name, date format).
- Admin UI under “System Settings” for viewing/editing (permission-gated).
- Cache settings and clear cache on update.

**4.3 Audit (Core)**
- Extend audit logging beyond auth: generic `AuditService::log($action, $model, $old, $new, $userId)`.
- Use in Case Management for case create/update/status change/assignment; optionally in other modules.
- Store in `audit_logs` table with polymorphic or JSON payload for flexibility.

---

### Phase 5: Case Management Module (Module 1)

**5.0 Case data model (workbook-aligned)**

Case registration and list fields are taken from the **CASE MANAGEMENT SYSTEM.xlsm** workbook. Map as follows:

| Workbook column       | DB / model usage | Notes |
|-----------------------|------------------|--------|
| **Serial Number**     | `case_number`    | Auto-generated unique identifier (e.g. CASE-YYYY-NNNN); display as “Case No” / “Serial Number”. |
| **Date Filed**        | `date_filed`     | Date the case was filed; filterable. |
| **Reference Number**  | `reference_number` | External reference (e.g. from another system); optional. |
| **Defendant**         | `defendant`      | Defendant name(s); text. |
| **Nature of Claim**   | `nature_of_claim` or `category_id` | Either free text or FK to categories; support filter by category. |
| **Officer Dealing**   | `assigned_to` (FK users) | Officer handling the case; “Assigned Officer” in UI. |
| **Entered By**        | `created_by` (FK users) | User who registered the case; audit. |
| **Cause Number**      | `cause_number`   | Legal/cause number; optional text. |
| **Claimant**          | `claimant`       | Claimant name(s); text. |
| **Documents**         | `case_documents` table | One-to-many; list in Case View “Documents” tab. |
| **UserName**          | —                | Context-dependent: in list view show current user or “Entered By”; no separate column needed if “Entered By” is shown. |

Additional fields from prompt (title, description, status, priority) remain: use **title** as case title, **description** for full narrative, **status** (e.g. Open / In Progress / Closed), **priority** (e.g. Low / Medium / High). Form layout: left column = case details (including workbook fields); right column = assignment & status.

**5.1 Database (PostgreSQL)**
- **Cases:** id (UUID), case_number (unique, auto-generated, maps to **Serial Number**), date_filed, reference_number, defendant, nature_of_claim (string or category_id FK), claimant, cause_number, title, description (textarea), status, priority, assigned_to (FK users, **Officer Dealing**), created_by (FK users, **Entered By**), updated_by, timestamps, soft deletes.
- **Categories:** id, name, slug, timestamps (optional if nature_of_claim is text-only; use for “Nature of Claim” dropdown if desired).
- **Case documents:** id, case_id (FK), file path, original name, mime type, uploaded_by, timestamps (**Documents**).
- **Case notes:** id, case_id, user_id, body, timestamps.
- **Case status history / audit:** core `audit_logs` or module `case_audits` (user, action, timestamp, payload).
- Indexes: status, assigned_to, created_at, date_filed; unique on case_number.

**5.2 Case Management service layer**
- `CaseManagementService`: create case (generate Serial Number / case_number), update (all workbook fields: date_filed, reference_number, defendant, nature_of_claim, claimant, cause_number, title, description, assigned_to, status, priority), change status, assign officer (Officer Dealing), add note. Set `created_by` (Entered By) on create.
- `CaseDocumentService`: store files securely (`storage/app/case-documents`), record in `case_documents`, link to case (Documents).
- Form Requests: validate all workbook-derived fields (StoreCaseRequest, UpdateCaseRequest).
- Policies: `CasePolicy` (view, create, update, assign) based on permissions.

**5.3 Routes and controllers**
- Register module routes in `app/Modules/CaseManagement/Routes/web.php` (or similar) and include from module provider.
- **Case list:** index with filters (Serial Number / case number, status, Officer Dealing / assigned officer, Date Filed range); paginated sortable table. **Columns:** Serial Number, Title (or Reference Number), Nature of Claim / Category, Status (badge), Officer Dealing, Entered By, Date Filed, Actions (View, Edit). Buttons: Search, Reset.
- **Register/Edit case:** form two columns. **Left (case details):** Serial Number (read-only, auto), Date Filed, Reference Number, Defendant, Nature of Claim, Claimant, Cause Number, Title, Description. **Right (assignment & status):** Officer Dealing (assigned officer), Priority, Status. Actions: Save Draft, Save & Assign, Cancel.
- **Case view:** header (Serial Number, Title, status badge, Edit, Change Status); tabs: **Overview** (all workbook fields + metadata: Officer Dealing, Entered By, Date Filed, created/updated dates), **Documents**, **Notes**, **History** (audit timeline).
- **Reports:** report type + date range; generate; summary, table, charts; export PDF, Excel, Print.

**5.4 Case number generation**
- Unique, human-readable (e.g. CASE-YYYY-NNNN). Implement in service with DB transaction and lock to avoid duplicates.

**5.5 Documents and notes**
- Documents: upload via form; store with hashed or UUID filenames; list with View/Download; restrict by policy.
- Notes: textarea + “Add Note”; list as timeline with user and date.
- History tab: read-only list from audit log for this case.

---

### Phase 6: Reporting Engine

**6.1 Generic reporting service**
- `App\Core\Reporting\ReportService` or in-module: accept report type, filters (e.g. date range, date_filed range), grouping.
- Return structured data (metrics + rows) so different reports can share the same engine.
- Case reports: total cases, open vs closed, cases per officer (Officer Dealing), cases by Nature of Claim / category, resolution time (if closed_at exists); optionally by Defendant, Claimant, or Entered By for management reporting.

**6.2 Export**
- PDF: Laravel DomPDF or Snappy (wkhtmltopdf); template for report layout.
- Excel: Laravel Excel (Maatwebsite); export same dataset.
- Print: CSS print stylesheet; “Print” button triggers `window.print()`.

---

### Phase 7: Administration (Users, Roles, System Settings)

**7.1 Users (Core or Admin module)**
- CRUD for users; assign roles (and optionally direct permissions); use Spatie’s role assignment.
- List with filters; create/edit form; password set/reset.
- Permission-gated (e.g. `admin.users`).

**7.2 Roles & Permissions**
- List roles and their permissions; edit role-permission matrix (Spatie provides APIs).
- Permission-gated (e.g. `admin.roles`).

**7.3 System Settings**
- Already in Phase 4.2; link from sidebar “System Settings”.

---

### Phase 8: Security, Validation & Polish

**8.1 Security**
- CSRF: Laravel default; ensure all forms have `@csrf`.
- Form Request validation on every mutation.
- Policy checks on all case and admin actions; middleware for permission-based routes.
- File upload: validate mime/size; store outside public; serve via controller with auth + policy check.

**8.2 Seeders**
- Roles and permissions seeder.
- Sample users (each role).
- Sample categories (if using category_id for Nature of Claim) and sample cases with all workbook fields (Serial Number, Date Filed, Reference Number, Defendant, Nature of Claim, Officer Dealing, Entered By, Cause Number, Claimant, title, description, status, priority).
- Call seeders from `DatabaseSeeder`.

**8.3 Quality**
- No hardcoded roles in views; only permissions.
- Comments on complex logic; clear naming.
- Use contracts/interfaces where it improves testability and module decoupling.

---

## 3. Suggested Order of Implementation (Checklist)

1. Laravel 12 + PostgreSQL + folder structure + module registration.
2. Spatie permission + roles/permissions seeders + auth (login, logout, password reset).
3. Core audit (auth events first, then generic log).
4. Base layout (sidebar, navbar, content), login page, and reusable components.
5. Dashboard (stub KPIs first, then wire to Case module).
6. Case Management: migrations → models → policies → services → Case list, Register/Edit, View (tabs), documents, notes, history.
7. Reporting: generic engine → case reports → export PDF/Excel/Print.
8. Administration: Users, Roles & Permissions, System Settings.
9. Seeders (roles, users, categories, cases).
10. Security pass (policies, validation, file access) and UI polish (alerts, validation feedback, loading states).

---

## 4. File and Namespace Map (Summary)

| Purpose | Location |
|--------|----------|
| Module registration | `app/Core/Support/ModuleRegistry.php`, `app/Providers/ModulesServiceProvider.php` |
| Auth / Login | `app/Http/Controllers/Auth/*`, `app/Core/Auth/` (if custom) |
| RBAC | Spatie + `app/Core/RBAC/` (optional helpers), seeders |
| Audit | `app/Core/Audit/AuditService.php`, `AuditLog` model, migrations |
| Settings | `app/Core/Settings/SettingsService.php`, model or key-value table |
| Dashboard | `app/Core/Dashboard/DashboardController.php`, views |
| Layout & components | `resources/views/layouts/*`, `resources/views/components/*` |
| Case Management | `app/Modules/CaseManagement/*` (Controllers, Models, Services, Policies, Requests, Migrations, Views, Routes) |
| Reporting | `app/Core/Reporting/` or `app/Modules/CaseManagement/Services/Reporting/` |
| Admin | `app/Core/` or `app/Modules/Administration/` for Users, Roles, Settings |

---

## 5. Risks and Mitigations

| Risk | Mitigation |
|------|------------|
| Module coupling | Define interfaces (e.g. `DashboardMetricsProvider`) so Core does not depend on Case module directly. |
| Permission sprawl | Group permissions (e.g. `cases.*`, `admin.*`); document in seeders. |
| Case number collisions | Use DB sequence or `SELECT FOR UPDATE` when generating. |
| File storage security | Store in `storage/app`; serve via controller with policy check; do not expose direct URLs. |

---

This plan follows the prompt’s structure: base platform first, then Case Management as Module 1, with RBAC, audit, Bootstrap 5 UI, and reporting as specified. Implementation can proceed phase by phase with the checklist above.
