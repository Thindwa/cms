#!/usr/bin/env python3
from __future__ import annotations

import os
import zipfile
from datetime import datetime, timezone
from pathlib import Path
from xml.sax.saxutils import escape


OUTPUT = Path("output/doc/website_handover_documentation_opc-site.docx")


CONTENT = [
    ("title", "Website Handover Documentation"),
    ("normal", "Repository: opc-site"),
    ("heading1", "Current Status"),
    (
        "normal",
        "Website development is complete. The website is currently deployed on the demo server and is waiting to be hosted live on production.",
    ),
    ("heading1", "Website Architecture"),
    ("heading2", "1. Website Summary"),
    ("normal", "This project is a Laravel 12 website application with:"),
    ("bullet", "a public-facing website rendered with Blade views"),
    ("bullet", "a Filament v3 admin panel for content and user administration"),
    ("bullet", "a block-based CMS for structured page content"),
    ("bullet", "security hardening around headers, sanitization, uploads, and activity logging"),
    ("normal", "At a high level:"),
    ("bullet", "visitors consume content on the frontend"),
    ("bullet", "editors and admins manage content in `/admin`"),
    ("bullet", "content is stored in MySQL/SQLite tables depending on deployment configuration"),
    ("bullet", "complex page layouts are stored as JSON block data in the database"),
    ("heading2", "2. Technology Stack"),
    ("normal", "Core platform:"),
    ("bullet", "PHP 8.2+"),
    ("bullet", "Laravel 12"),
    ("bullet", "Filament 3"),
    ("bullet", "Blade templates"),
    ("bullet", "Vite for asset bundling"),
    ("normal", "Main packages in active use:"),
    ("bullet", "`bezhansalleh/filament-shield`: role and permission control"),
    ("bullet", "`jeffgreco13/filament-breezy`: user profile management"),
    ("bullet", "`outerweb/filament-settings`: settings management"),
    ("bullet", "`outerweb/filament-image-library`: image management"),
    ("bullet", "`skyraptor/filament-blocks-builder`: block-based page builder"),
    ("bullet", "`z3d0x/filament-logger`: activity logging integration"),
    ("bullet", "`hasnayeen/themes`: admin theming"),
    ("bullet", "`swisnl/filament-backgrounds`: auth screen visuals"),
    ("bullet", "`jenssegers/agent`: device/browser detection for activity logs"),
    ("heading2", "3. Main Application Areas"),
    ("heading3", "3.1 Public Website"),
    ("normal", "Primary route file:"),
    ("code", "`routes/web.php`"),
    ("normal", "Main controller:"),
    ("code", "`app/Http/Controllers/FrontendController.php`"),
    ("normal", "Frontend layout:"),
    ("code", "`resources/views/layouts/frontend.blade.php`"),
    (
        "normal",
        "The frontend is mostly content-driven. Many pages load a `Page` record by `slug`, then render block content from the `content` JSON field.",
    ),
    ("normal", "Examples:"),
    ("bullet", "home page loads the `home` slug"),
    ("bullet", "about page loads `opc-hqs-sections-page`"),
    ("bullet", "profile page loads `his-excellency-profile`"),
    ("bullet", "history page loads `history-page`"),
    ("normal", "Some pages are record-driven instead of page-builder-driven:"),
    ("bullet", "`news`"),
    ("bullet", "`events`"),
    ("bullet", "`documents`"),
    ("bullet", "`videos`"),
    ("bullet", "`management`"),
    ("bullet", "`ministers`"),
    ("bullet", "`deputy ministers`"),
    ("bullet", "`departments`"),
    ("heading3", "3.2 Admin Panel"),
    ("normal", "Admin entrypoint:"),
    ("code", "`/admin`"),
    ("normal", "Panel provider:"),
    ("code", "`app/Providers/Filament/AdminPanelProvider.php`"),
    ("normal", "The admin panel is responsible for:"),
    ("bullet", "CRUD for all major content types"),
    ("bullet", "dashboard widgets and activity visibility"),
    ("bullet", "role-based access control"),
    ("bullet", "site settings and SEO settings"),
    ("bullet", "user management"),
    ("normal", "The admin panel discovers:"),
    ("bullet", "resources from `app/Filament/Resources`"),
    ("bullet", "pages from `app/Filament/Pages`"),
    ("bullet", "widgets from `app/Filament/Widgets`"),
    ("heading3", "3.3 Block-Based CMS"),
    ("normal", "Block definitions live in:"),
    ("code", "`app/Filament/Blocks`"),
    ("normal", "Block views live in:"),
    ("code", "`resources/views/blocks`"),
    ("normal", "Used by:"),
    ("bullet", "`PageResource`"),
    ("bullet", "`DepartmentResource`"),
    (
        "normal",
        "This is the key customization layer of the system. Instead of hardcoding every page layout, editors assemble pages from reusable blocks such as:",
    ),
    ("bullet", "heading"),
    ("bullet", "paragraph"),
    ("bullet", "columns"),
    ("bullet", "image"),
    ("bullet", "button"),
    ("bullet", "quote"),
    ("bullet", "tabs"),
    ("bullet", "accordion"),
    ("bullet", "slider"),
    ("bullet", "map"),
    ("bullet", "table"),
    ("bullet", "service grid"),
    ("bullet", "vision"),
    ("heading2", "4. Request and Rendering Flow"),
    ("normal", "Typical frontend flow:"),
    ("bullet", "Request enters Laravel through `public/index.php`"),
    ("bullet", "Global middleware is configured in `bootstrap/app.php`"),
    ("bullet", "Security headers are appended globally"),
    ("bullet", "Web middleware includes input sanitization"),
    ("bullet", "Route in `routes/web.php` maps to `FrontendController`"),
    ("bullet", "Controller loads data from Eloquent models"),
    ("bullet", "Blade view renders frontend template"),
    ("bullet", "SEO values are injected through `resources/views/layouts/frontend.blade.php`"),
    ("normal", "For block-driven pages:"),
    ("bullet", "Controller loads `Page` or `Department`"),
    ("bullet", "`content` JSON is read and transformed where needed"),
    ("bullet", "Blade view loops through blocks or uses helper rendering"),
    ("bullet", "block partials in `resources/views/blocks` output HTML"),
    ("heading2", "5. Data Model"),
    ("normal", "Main business tables and models:"),
    ("bullet", "`pages` -> `App\\Models\\Page`: stores custom pages with `title`, `slug`, `content`; `content` is JSON"),
    ("bullet", "`departments` -> `App\\Models\\Department`: stores department landing pages with `title`, `slug`, `icon`, `banner_image`, `content`; `content` is JSON"),
    ("bullet", "`news` -> `App\\Models\\News`: stores news articles and auto-generates unique slugs"),
    ("bullet", "`events` -> `App\\Models\\Event`: stores events/upcoming activities, auto-generates unique slugs, date fields cast to dates"),
    ("bullet", "`documents` -> `App\\Models\\Document`: stores document groups by category; `files` is JSON"),
    ("bullet", "`videos` -> `App\\Models\\Video`: stores YouTube URLs and exposes a computed embed URL"),
    ("bullet", "`management` -> `App\\Models\\Management`: stores management staff records and uses `position_type` for layout grouping"),
    ("bullet", "`ministers` -> `App\\Models\\Minister`: stores cabinet minister records and uses `position_type` for grouping"),
    ("bullet", "`dministers` -> `App\\Models\\Dminister`: stores deputy minister records"),
    ("bullet", "`activity_log` -> `App\\Models\\ActivityLog`: stores activity and audit data including IP, device, browser, OS, and request metadata"),
    ("bullet", "`users` -> `App\\Models\\User`: admin/authenticated users, role-enabled with Spatie permissions"),
    ("normal", "Supporting tables:"),
    ("bullet", "permissions and roles"),
    ("bullet", "settings"),
    ("bullet", "image library tables"),
    ("bullet", "security logs table"),
    ("heading2", "6. Admin Resources"),
    ("normal", "The main Filament resources are:"),
    ("bullet", "`PageResource`"),
    ("bullet", "`DepartmentResource`"),
    ("bullet", "`NewsResource`"),
    ("bullet", "`EventResource`"),
    ("bullet", "`DocumentResource`"),
    ("bullet", "`VideoResource`"),
    ("bullet", "`ManagementResource`"),
    ("bullet", "`MinisterResource`"),
    ("bullet", "`DministerResource`"),
    ("bullet", "`UserResource`"),
    ("bullet", "`ActivityLogResource`"),
    ("normal", "Resource groupings reflect editorial ownership:"),
    ("bullet", "content management: pages, news, events, documents, videos"),
    ("bullet", "organization: departments, ministers, deputy ministers, management"),
    ("bullet", "administration: users, activity logs, settings"),
    ("heading2", "7. Settings and SEO"),
    ("normal", "Settings page:"),
    ("code", "`app/Filament/Pages/Settings.php`"),
    ("normal", "Settings are used for:"),
    ("bullet", "brand name"),
    ("bullet", "default SEO title"),
    ("bullet", "default SEO description"),
    ("bullet", "SEO keywords"),
    ("bullet", "default social sharing image"),
    ("bullet", "cabinet ministers page header content"),
    ("bullet", "deputy ministers page header content"),
    ("normal", "Helpers:"),
    ("bullet", "`app/Helpers/SeoHelper.php`"),
    ("bullet", "`app/Helpers/SettingsHelper.php`"),
    ("normal", "SEO meta tags are applied in:"),
    ("code", "`resources/views/layouts/frontend.blade.php`"),
    ("heading2", "8. Security Architecture"),
    ("normal", "Security middleware:"),
    ("bullet", "`app/Http/Middleware/SecurityHeadersMiddleware.php`"),
    ("bullet", "`app/Http/Middleware/InputSanitizationMiddleware.php`"),
    ("bullet", "`app/Http/Middleware/RateLimitMiddleware.php`"),
    ("normal", "Security-related services/helpers:"),
    ("bullet", "`app/Helpers/HtmlSanitizer.php`"),
    ("bullet", "`app/Services/SecureFileUploadService.php`"),
    ("bullet", "`app/Services/SecurityLoggingService.php`"),
    ("bullet", "`app/Services/ActivityLoggingService.php`"),
    ("normal", "Current security approach includes:"),
    ("bullet", "CSP and browser security headers"),
    ("bullet", "CSRF protection"),
    ("bullet", "input cleanup on web requests"),
    ("bullet", "upload validation by MIME type, size, and content signature"),
    ("bullet", "sanitization of rich HTML output"),
    ("bullet", "structured activity logging"),
    ("bullet", "role/permission-based access control through Filament Shield"),
    ("normal", "Reference docs already in repo:"),
    ("bullet", "`SECURITY_AUDIT.md`"),
    ("bullet", "`XSS_TEST_PAYLOADS.md`"),
    ("heading2", "9. Notable Custom Behaviors"),
    ("normal", "Home page block merging"),
    ("normal", "The home action in `FrontendController` merges:"),
    ("bullet", "one `ImageTextBlock`"),
    ("bullet", "one `HomeAccordionBlock`"),
    ("normal", "This is a custom presentation rule and should be preserved if the home page content model is changed."),
    ("normal", "Slug generation"),
    ("normal", "`News` and `Event` auto-generate unique slugs during save. Editors do not need to manage slug collisions manually."),
    ("normal", "Header content driven by settings"),
    ("normal", "Cabinet Ministers and Deputy Ministers pages use settings-based header content instead of hardcoded template text."),
    ("normal", "Hardcoded Google Maps script include"),
    ("normal", "The frontend layout includes a Google Maps API script directly in the layout. If maps stop working or the key changes, check:"),
    ("bullet", "`resources/views/layouts/frontend.blade.php`"),
    ("bullet", "`public/frontendassets/plugins/google-map/map.js`"),
    ("heading2", "10. Files to Read First When Taking Over"),
    ("normal", "Start with these files:"),
    ("bullet", "`routes/web.php`"),
    ("bullet", "`app/Http/Controllers/FrontendController.php`"),
    ("bullet", "`app/Providers/Filament/AdminPanelProvider.php`"),
    ("bullet", "`app/Filament/Resources/PageResource.php`"),
    ("bullet", "`app/Filament/Resources/DepartmentResource.php`"),
    ("bullet", "`app/Filament/Pages/Settings.php`"),
    ("bullet", "`resources/views/layouts/frontend.blade.php`"),
    ("bullet", "`app/Helpers/HtmlSanitizer.php`"),
    ("bullet", "`SECURITY_AUDIT.md`"),
    ("heading2", "11. Architecture Risks and Caveats"),
    ("normal", "Known items a takeover developer should verify:"),
    ("bullet", "admin input sanitization is intentionally skipped by middleware, so Filament field validation and view sanitization remain important"),
    ("bullet", "the frontend layout contains a visible Google Maps API key reference"),
    ("bullet", "the repository README still describes the original starter kit, not the customized OPC system"),
    ("bullet", "automated tests exist only as starter examples, so regression risk is higher for future refactors"),
    ("bullet", "page rendering depends on exact `slug` values for several routes"),
    ("heading1", "Operations and Takeover Notes"),
    ("heading2", "1. Purpose of This Document"),
    ("normal", "This document is the practical handover guide for the next developer or administrator maintaining this repository."),
    ("normal", "It explains:"),
    ("bullet", "how to run the website"),
    ("bullet", "how content is managed"),
    ("bullet", "where major customizations live"),
    ("bullet", "what should be checked first during takeover"),
    ("heading2", "2. Local Setup"),
    ("normal", "Minimum requirements:"),
    ("bullet", "PHP 8.2+"),
    ("bullet", "Composer"),
    ("bullet", "Node.js and npm"),
    ("bullet", "a database supported by Laravel, typically SQLite or MySQL"),
    ("normal", "Basic setup flow:"),
    ("bullet", "Copy `.env` from `.env.example` if needed."),
    ("bullet", "Configure database credentials."),
    ("bullet", "Run `composer install`."),
    ("bullet", "Run `npm install`."),
    ("bullet", "Run `php artisan key:generate`."),
    ("bullet", "Run `php artisan migrate`."),
    ("bullet", "Run `php artisan storage:link`."),
    ("bullet", "Run `php artisan make:filament-user`."),
    ("bullet", "Run `php artisan shield:super-admin --user=1 --panel=admin`."),
    ("bullet", "Run `php artisan shield:generate --all --ignore-existing-policies --panel=admin`."),
    ("normal", "Useful development command:"),
    ("code", "composer run dev"),
    ("normal", "This starts:"),
    ("bullet", "Laravel dev server"),
    ("bullet", "queue listener"),
    ("bullet", "log tailing"),
    ("bullet", "Vite dev process"),
    ("heading2", "3. Content Management Workflow"),
    ("normal", "Pages"),
    ("normal", "Managed in:"),
    ("bullet", "Admin -> Pages"),
    ("normal", "Pages use:"),
    ("bullet", "`title`"),
    ("bullet", "`slug`"),
    ("bullet", "`content` block builder"),
    ("normal", "Important rule:"),
    ("normal", "several frontend routes depend on exact slug values; if a slug is changed in admin without updating controller logic, the related page will fail with `404`"),
    ("normal", "Examples of slugs used directly by code:"),
    ("bullet", "`home`"),
    ("bullet", "`opc-hqs-sections-page`"),
    ("bullet", "`his-excellency-profile`"),
    ("bullet", "`executive-page`"),
    ("bullet", "`service-charter`"),
    ("bullet", "`history-page`"),
    ("bullet", "`history-of-chief-secretaries`"),
    ("normal", "Departments"),
    ("normal", "Managed in:"),
    ("bullet", "Admin -> Departments"),
    ("normal", "Department pages are dynamic and route through:"),
    ("code", "`/departments/{slug}`"),
    ("normal", "Each department can contain:"),
    ("bullet", "icon reference"),
    ("bullet", "banner image"),
    ("bullet", "block-based content"),
    ("normal", "News"),
    ("normal", "Managed in:"),
    ("bullet", "Admin -> News"),
    ("normal", "Behavior:"),
    ("bullet", "newest items appear first"),
    ("bullet", "listing page paginates"),
    ("bullet", "single articles use generated slugs"),
    ("bullet", "SEO description is derived from article content"),
    ("normal", "Events"),
    ("normal", "Managed in:"),
    ("bullet", "Admin -> Events"),
    ("normal", "Behavior:"),
    ("bullet", "ordered by `start_date`"),
    ("bullet", "used for the upcoming/events frontend views"),
    ("normal", "Documents"),
    ("normal", "Managed in:"),
    ("bullet", "Admin -> Documents"),
    ("normal", "Behavior:"),
    ("bullet", "files are stored as JSON arrays"),
    ("bullet", "current model is grouped more by category than by a single document record pattern"),
    ("normal", "Videos"),
    ("normal", "Managed in:"),
    ("bullet", "Admin -> Videos"),
    ("normal", "Behavior:"),
    ("bullet", "stores YouTube URLs"),
    ("bullet", "embed URL is derived in the model"),
    ("normal", "Ministers, Deputy Ministers, Management"),
    ("normal", "Managed in:"),
    ("bullet", "Admin -> Ministers"),
    ("bullet", "Admin -> Deputy Ministers"),
    ("bullet", "Admin -> Management"),
    ("normal", "Behavior:"),
    ("bullet", "page layout depends on `position_type`"),
    ("bullet", "ministers page header text is settings-driven"),
    ("bullet", "deputy ministers page header text is settings-driven"),
    ("heading2", "4. Settings Management"),
    ("normal", "Managed in:"),
    ("bullet", "Admin -> Settings"),
    ("normal", "Tabs currently available:"),
    ("bullet", "General"),
    ("bullet", "Seo"),
    ("bullet", "Page Headers"),
    ("normal", "Use settings for:"),
    ("bullet", "organization brand name"),
    ("bullet", "default meta title, description, keywords, image"),
    ("bullet", "ministers page header copy and logo"),
    ("bullet", "deputy ministers page header copy and logo"),
    ("normal", "If these pages look wrong after data import or migration, verify settings values before changing templates."),
    ("heading2", "5. Permissions and Access Control"),
    ("normal", "Access control is handled by:"),
    ("bullet", "Filament Shield"),
    ("bullet", "Spatie roles/permissions"),
    ("normal", "Expected admin setup after deployment:"),
    ("bullet", "create the initial Filament user"),
    ("bullet", "assign super admin"),
    ("bullet", "generate permissions"),
    ("bullet", "review access for editors vs administrators"),
    ("normal", "Policy classes exist in:"),
    ("code", "`app/Policies`"),
    ("heading2", "6. Logging and Auditability"),
    ("normal", "The website includes both:"),
    ("bullet", "application activity logging"),
    ("bullet", "security event logging"),
    ("normal", "Relevant pieces:"),
    ("bullet", "`app/Models/ActivityLog.php`"),
    ("bullet", "`app/Services/ActivityLoggingService.php`"),
    ("bullet", "`app/Services/SecurityLoggingService.php`"),
    ("bullet", "`config/logging.php`"),
    ("bullet", "`database/migrations/2025_09_04_071522_create_security_logs_table.php`"),
    ("bullet", "`database/migrations/2025_09_04_193506_add_device_tracking_to_activity_log_table.php`"),
    ("normal", "Use this area first when investigating:"),
    ("bullet", "unexpected admin changes"),
    ("bullet", "suspicious login activity"),
    ("bullet", "file upload concerns"),
    ("heading2", "7. Frontend Structure"),
    ("normal", "Primary frontend layout:"),
    ("code", "`resources/views/layouts/frontend.blade.php`"),
    ("normal", "Shared partials:"),
    ("bullet", "`resources/views/partials/frontnav.blade.php`"),
    ("bullet", "`resources/views/partials/frontfooter.blade.php`"),
    ("bullet", "`resources/views/partials/frontslider.blade.php`"),
    ("normal", "Frontend pages live in:"),
    ("bullet", "`resources/views/frontend`"),
    ("normal", "Static assets live mostly in:"),
    ("bullet", "`public/frontendassets`"),
    ("bullet", "`resources/css`"),
    ("bullet", "`resources/js`"),
    ("bullet", "`public/css`"),
    ("normal", "There is a mixture of source and built/static asset organization. Before a frontend refactor, confirm which files are still actively used in production."),
    ("heading2", "8. Deployment Notes"),
    ("normal", "Before production release, verify:"),
    ("bullet", "`APP_ENV=production`"),
    ("bullet", "`APP_DEBUG=false`"),
    ("bullet", "`SESSION_SECURE_COOKIE=true`"),
    ("bullet", "storage link exists"),
    ("bullet", "writable permissions for `storage/` and `bootstrap/cache/`"),
    ("bullet", "correct database credentials"),
    ("bullet", "queue worker configuration if queued jobs are used"),
    ("normal", "Also verify:"),
    ("bullet", "SEO image paths resolve correctly from storage"),
    ("bullet", "admin uploads write to the intended disk"),
    ("bullet", "Google Maps key is still valid if the map feature is required"),
    ("heading2", "9. Gaps and Follow-Up Items"),
    ("normal", "Important takeover observations:"),
    ("bullet", "the project README is still the starter-kit README and should be replaced with project-specific onboarding"),
    ("bullet", "test coverage is minimal; current test files are still example scaffolding"),
    ("bullet", "several features rely on content conventions rather than enforced validation"),
    ("bullet", "route-to-slug coupling should be documented and ideally reduced over time"),
    ("bullet", "security hardening exists, but production config still needs environment-level review"),
    ("heading2", "10. Recommended First Actions for the New Maintainer"),
    ("normal", "Technical"),
    ("bullet", "Review `.env` and deployment secrets."),
    ("bullet", "Confirm database schema matches all migrations."),
    ("bullet", "Log in to `/admin` and verify all resources load."),
    ("bullet", "Review settings values and public content pages."),
    ("bullet", "Replace the root `README.md` with project-specific documentation."),
    ("bullet", "Add feature tests for key public routes and admin CRUD flows."),
    ("normal", "Editorial"),
    ("bullet", "Verify all required `Page` slugs exist."),
    ("bullet", "Confirm ministers and deputy ministers header settings are populated."),
    ("bullet", "Check uploaded files and images render correctly from `storage`."),
    ("bullet", "Review news, events, and documents listing pages for stale content structures."),
    ("heading2", "11. Handover Summary"),
    ("normal", "This website is best understood as:"),
    ("bullet", "a Laravel application"),
    ("bullet", "with a Filament-powered admin CMS"),
    ("bullet", "using JSON block content for flexible page composition"),
    ("bullet", "extended with security, SEO, and audit logging enhancements"),
    ("normal", "The most important takeover principle is to preserve the relationship between:"),
    ("bullet", "route definitions"),
    ("bullet", "expected page slugs"),
    ("bullet", "block structure"),
    ("bullet", "admin-managed settings"),
    ("normal", "Most future changes will be safe if that relationship is respected."),
]


def paragraph(text: str, style: str | None = None) -> str:
    ppr = f"<w:pPr><w:pStyle w:val=\"{style}\"/></w:pPr>" if style else ""
    return (
        "<w:p>"
        f"{ppr}"
        "<w:r><w:rPr><w:lang w:val=\"en-US\"/></w:rPr>"
        f"<w:t xml:space=\"preserve\">{escape(text)}</w:t>"
        "</w:r></w:p>"
    )


def build_document_xml() -> str:
    blocks = []
    for kind, text in CONTENT:
        if kind == "title":
            blocks.append(paragraph(text, "Title"))
        elif kind == "heading1":
            blocks.append(paragraph(text, "Heading1"))
        elif kind == "heading2":
            blocks.append(paragraph(text, "Heading2"))
        elif kind == "heading3":
            blocks.append(paragraph(text, "Heading3"))
        elif kind == "bullet":
            blocks.append(paragraph(f"- {text}", "Normal"))
        elif kind == "code":
            blocks.append(paragraph(text, "Code"))
        else:
            blocks.append(paragraph(text, "Normal"))
    body = "".join(blocks)
    return (
        "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>"
        "<w:document xmlns:wpc=\"http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas\" "
        "xmlns:mc=\"http://schemas.openxmlformats.org/markup-compatibility/2006\" "
        "xmlns:o=\"urn:schemas-microsoft-com:office:office\" "
        "xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\" "
        "xmlns:m=\"http://schemas.openxmlformats.org/officeDocument/2006/math\" "
        "xmlns:v=\"urn:schemas-microsoft-com:vml\" "
        "xmlns:wp14=\"http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing\" "
        "xmlns:wp=\"http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing\" "
        "xmlns:w10=\"urn:schemas-microsoft-com:office:word\" "
        "xmlns:w=\"http://schemas.openxmlformats.org/wordprocessingml/2006/main\" "
        "xmlns:w14=\"http://schemas.microsoft.com/office/word/2010/wordml\" "
        "xmlns:wpg=\"http://schemas.microsoft.com/office/word/2010/wordprocessingGroup\" "
        "xmlns:wpi=\"http://schemas.microsoft.com/office/word/2010/wordprocessingInk\" "
        "xmlns:wne=\"http://schemas.microsoft.com/office/2006/wordml\" "
        "xmlns:wps=\"http://schemas.microsoft.com/office/word/2010/wordprocessingShape\" "
        "mc:Ignorable=\"w14 wp14\">"
        "<w:body>"
        f"{body}"
        "<w:sectPr>"
        "<w:pgSz w:w=\"12240\" w:h=\"15840\"/>"
        "<w:pgMar w:top=\"1440\" w:right=\"1440\" w:bottom=\"1440\" w:left=\"1440\" w:header=\"708\" w:footer=\"708\" w:gutter=\"0\"/>"
        "</w:sectPr>"
        "</w:body></w:document>"
    )


STYLES_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/>
        <w:sz w:val="22"/>
        <w:szCs w:val="22"/>
      </w:rPr>
    </w:rPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:after="120" w:line="276" w:lineRule="auto"/>
    </w:pPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Title">
    <w:name w:val="Title"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:before="120" w:after="240"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="36"/>
      <w:szCs w:val="36"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:before="240" w:after="120"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="28"/>
      <w:szCs w:val="28"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:before="180" w:after="80"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="24"/>
      <w:szCs w:val="24"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading3">
    <w:name w:val="heading 3"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:before="160" w:after="60"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="22"/>
      <w:szCs w:val="22"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Code">
    <w:name w:val="Code"/>
    <w:basedOn w:val="Normal"/>
    <w:qFormat/>
    <w:pPr>
      <w:spacing w:after="80"/>
    </w:pPr>
    <w:rPr>
      <w:rFonts w:ascii="Courier New" w:hAnsi="Courier New"/>
      <w:sz w:val="20"/>
      <w:szCs w:val="20"/>
    </w:rPr>
  </w:style>
</w:styles>
"""


CONTENT_TYPES_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
"""


RELS_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
"""


DOC_RELS_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
"""


def core_xml(now: datetime) -> str:
    iso = now.strftime("%Y-%m-%dT%H:%M:%SZ")
    return f"""<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>Website Handover Documentation</dc:title>
  <dc:subject>opc-site handover</dc:subject>
  <dc:creator>OpenAI Codex</dc:creator>
  <cp:lastModifiedBy>OpenAI Codex</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{iso}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{iso}</dcterms:modified>
</cp:coreProperties>
"""


APP_XML = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft Office Word</Application>
</Properties>
"""


def main() -> None:
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    now = datetime.now(timezone.utc)

    with zipfile.ZipFile(OUTPUT, "w", compression=zipfile.ZIP_DEFLATED) as docx:
        docx.writestr("[Content_Types].xml", CONTENT_TYPES_XML)
        docx.writestr("_rels/.rels", RELS_XML)
        docx.writestr("docProps/core.xml", core_xml(now))
        docx.writestr("docProps/app.xml", APP_XML)
        docx.writestr("word/document.xml", build_document_xml())
        docx.writestr("word/styles.xml", STYLES_XML)
        docx.writestr("word/_rels/document.xml.rels", DOC_RELS_XML)

    print(os.fspath(OUTPUT.resolve()))


if __name__ == "__main__":
    main()
