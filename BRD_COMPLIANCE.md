# BRD Compliance Checklist – Case Management System (CMS)

This document maps the Business Requirements Document (BRD) to the current implementation. **Yes** = implemented; **Partial** = partly done; **No** = not implemented; **N/A** = optional or not applicable.

---

## 1. Introduction & Scope

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Allow authorized users to log in securely | **Yes** | Login (username + password), session-based auth, logout |
| Register and manage case records | **Yes** | Case list, create, edit, view; permission-based |
| Attach and manage case-related documents | **Yes** | Upload (PDF, Word, Excel, images), list, download per case |
| Track case handling by officers | **Yes** | Assigned officer on case; filters and reports by officer |
| Generate management reports | **Yes** | Reports screen with summary, by officer, by status, by category; PDF/Excel/Print |

---

## 2. Business Objectives

| Objective | Status |
|-----------|--------|
| Improve efficiency in case registration and tracking | **Yes** – digital cases, auto case number, filters, audit |
| Reduce manual paperwork | **Yes** – electronic forms, document uploads |
| Improve document management and retrieval | **Yes** – upload, list, download by case |
| Enable management reporting and performance monitoring | **Yes** – reports with date range, export, print |

---

## 3. Functional Requirements

### 3.1 User Authentication (Login)

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Log in using username and password | **Yes** | `LoginController`, username + password |
| Role-based access (e.g. Administrator, User) | **Yes** | Spatie Permission: Super Admin, Administrator, Officer, Viewer; permission-based (no role hardcoding) |
| Password reset | **Yes** | Forgot password (email link), reset form, `CanResetPassword` on User |
| Log user access activity for audit | **Yes** | `AuditService` logs login success/failure, logout |

### 3.2 Case Registration

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Authorized users can register new cases | **Yes** | `cases.create` permission; Register Case form |
| Automatically generate unique case number | **Yes** | `CaseManagementService::generateCaseNumber()` (e.g. CASE-YYYY-NNNNN) |
| Capture information (refer to database) | **Yes** | All workbook fields: Serial Number, Date Filed, Reference Number, Defendant, Nature of Claim, Officer Dealing, Entered By, Cause Number, Claimant, Title, Description, Status, Priority |
| Edit case details (based on role/permissions) | **Yes** | `cases.edit` permission; Case edit form and policy |

### 3.3 Document Attachment

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Upload documents related to a case | **Yes** | Documents tab on case view; PDF, Word, Excel, images; max 10MB |
| Support common file formats (PDF, Word, Excel, images) | **Yes** | Validation: pdf, doc, docx, xls, xlsx, jpg, jpeg, png, gif |
| View and download attached documents | **Yes** | List with file name, type, uploaded by, date; Download link |
| Document version history | **Yes** | **Implemented.** Each upload of the same file name gets an incremented `version`; Documents tab shows Version column and lists all versions (v1, v2, …) with download per version. |

### 3.4 Case Search and Retrieval

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Search by case number | **Yes** | Filter: case number (partial match) |
| Search by case title | **Yes** | Filter: title (partial match) |
| Search by officer name | **Yes** | Filter: Officer Dealing (assigned_to dropdown) |
| Display search results in a sortable list | **Yes** | **Implemented.** Case list has clickable column headers (Serial No, Title, Nature of Claim, Status, Officer Dealing, Entered By, Date Filed); sort direction toggles asc/desc. |
| Quick access to full case details from results | **Yes** | View button per row → case show (Overview, Documents, Notes, History) |

### 3.5 Case Handling and Updates

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Officers can update case status | **Yes** | Status on edit form; permission `cases.edit` |
| Officers can add case notes or comments | **Yes** | Notes tab: “Add note” form; timeline of notes |

### 3.6 Reporting

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Number of cases per officer | **Yes** | Report type “Cases per officer” |
| Total assigned cases per officer | **Yes** | Same report |
| Number of open/closed cases per officer | **Yes** | By officer + summary gives totals; status report gives open/in progress/closed |
| Total cases registered | **Yes** | Summary report |
| Total cases in selected date range | **Yes** | Date from/to on reports |
| Breakdown by case category | **Yes** | “Cases by nature of claim” report |
| Breakdown by case status | **Yes** | “Cases by status” report |
| Case status report (open, in-progress, closed) | **Yes** | Summary and by_status report |
| Average time to close cases | **Yes** | **Implemented.** Cases have `closed_at` set when status → closed; Summary report shows “Avg. days to close (cases closed in period)” when there are closed cases in the date range. |
| Cases handled within a specific period | **Yes** | Date range filter on all reports |
| Report filtering by date range | **Yes** | Date from / date to |
| Export reports (PDF, Excel) | **Yes** | Export PDF, Export Excel (CSV) |
| Print reports | **Yes** | Print button (window.print) |

---

## 4. Non-Functional Requirements

### 4.1 Security

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Role-based access control | **Yes** | Spatie Permission; middleware and policies |
| Secured user sessions | **Yes** | Laravel session, CSRF, auth middleware |
| Audit logs | **Yes** | `AuditLog` model; auth events + case create/update |

### 4.2 Usability

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| User-friendly interface | **Yes** | Bootstrap 5, sidebar, clear forms and tables |
| Form validation to prevent incorrect data entry | **Yes** | Form Requests and inline validation on forms |

---

## 5. User Roles (BRD: Administrator, User)

| BRD Role | Status | Implementation |
|----------|--------|----------------|
| Administrator: Manage users, assign roles, view all reports, configure system settings | **Yes** | Admin roles (Super Admin, Administrator) with `admin.users`, `admin.roles`, `admin.settings`, `reports.view` |
| User: Register cases, update case details, attach documents, change case status | **Yes** | Officer/Viewer and permissions: `cases.view`, `cases.create`, `cases.edit`; documents and notes on case |

*(The app uses four roles—Super Admin, Administrator, Officer, Viewer—which cover and extend the BRD’s Administrator and User.)*

---

## 6. Data Requirements

| Data | Status | Implementation |
|------|--------|----------------|
| User information | **Yes** | `users` table; Admin > Users CRUD |
| Case details | **Yes** | `cases` table (all workbook fields + status, priority, assignment) |
| Officer details | **Yes** | Officers are users; assigned_to on case |
| Document metadata | **Yes** | `case_documents` (file_path, original_name, mime_type, uploaded_by) |
| Audit logs | **Yes** | `audit_logs` table; auth and case actions |
| Reporting data | **Yes** | Derived from cases; reports and exports |

---

## 7. Success Criteria

| Criterion | Status |
|-----------|--------|
| All cases registered digitally | **Yes** |
| Case search time significantly reduced | **Yes** – filters and quick access to case details |
| Management can generate accurate reports instantly | **Yes** – reports with date range, export PDF/Excel, print |

---

## Summary: Gaps (optional or minor)

All BRD requirements are now implemented, including the previously optional/minor items (document version history, average time to close, sortable case list).
