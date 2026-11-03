# WP Web Projects

This repository contains a standalone WordPress portfolio plugin for SiteBuilderOne.

## Product decisions
- Public projects use `/website-portfolio/{project-slug}/`.
- The portfolio landing page is a normal WordPress page with `[wwp_portfolio]`; do not register a competing post type archive.
- Use the Classic Editor for projects only. Keep standard title, editor, excerpt and featured image fields.
- Categories describe industries/client types; features describe capabilities. Both support multiple terms per project.
- Use the theme's Bootstrap CSS. Do not load Bootstrap, external libraries or a second CSS framework.
- Individual layouts belong to the theme/LiveCanvas. Provide reusable shortcodes for project data and the gallery.
- Portfolio thumbnails and titles open featured images in a lightbox; do not render View project buttons or links to individual project pages. Keep external website actions and plain excerpts without theme excerpt filters.
- Gallery headings/descriptions are project-specific. Alt text belongs to the attachment.
- Individual projects and project category/feature archives are noindex and excluded from XML/HTML sitemaps. Keep the normal portfolio landing Page eligible and respect site visibility and other plugins' exclusions.
- Do not migrate old content, create redirects, activate plugins or change site content without a separate request.

## Implementation
- Prefix PHP symbols, metadata, asset handles, shortcodes and CSS with `wwp`.
- Check capabilities and nonces before metadata changes; skip autosaves and revisions.
- Sanitize on write, escape on output, and validate media as image attachments.
- Preserve content on deactivation/uninstall. Flush rewrites only on activation/deactivation.
- Keep front-end scripts dependency-free and scoped per shortcode instance.
- Keep filters accessible, progressively enhanced, and independent from lightbox behavior.
- Update README.md when shortcode attributes or stored metadata change.

## Checks
Run `php -l wp-web-projects.php`, `node --check assets/admin.js`, `node --check assets/frontend.js`, and the available tests under `tests/`.
