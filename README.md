# WP Web Projects

A standalone WordPress plugin for a website portfolio, designed for a Bootstrap-based LiveCanvas site. No ACF, JavaScript library or SEO plugin is required. Requires WordPress 6.2+, PHP 7.4+, and theme-provided Bootstrap CSS (Bootstrap 5 recommended).

## Setup

1. Install this folder under `wp-content/plugins/wp-web-projects` and activate **WP Web Projects** in WordPress.
2. Add projects under **Website Projects**. Use the title for the name, the Classic Editor for the description, and the standard Excerpt and Featured Image panels. Set the HTTPS website URL and logo in **Project Details & Gallery**.
3. Add images to the gallery and enter optional headings and descriptions. Move up/down sets the saved order. Image alt text is managed in the Media Library. Removing an image from a project does not delete the attachment.
4. Create **Project Categories** for client types/industries, and **Project Features** for capabilities such as E-commerce, Lead Generation and Online Booking. Multiple terms can be assigned from each group. No terms or demo projects are created automatically.
5. Create a normal WordPress page with the slug `website-portfolio`. Add `[wwp_portfolio]` using LiveCanvas's shortcode rendering. Design the rest of the page in LiveCanvas.
6. Build your individual project layout in your theme/LiveCanvas using the `wwp_project` post type. The plugin does not replace your theme's single or taxonomy templates.

The plugin uses the theme's Bootstrap CSS and does not enqueue Bootstrap. It adds small scoped styles and dependency-free filtering/lightbox scripts. Styles load site-wide so shortcodes rendered late by LiveCanvas still display correctly; front-end JavaScript loads only when a portfolio or gallery shortcode renders.

## Shortcodes

| Shortcode | Purpose | Attributes |
| --- | --- | --- |
| `[wwp_portfolio]` | Filterable grid with featured image, title and excerpt | None |
| `[wwp_portfolio_canvas]` | Canvas-style grid with a compact Categories/Features filter toolbar | None |
| `[wwp_gallery]` | Ordered gallery with lightbox, image headings and descriptions | `id="123"` (optional) |
| `[wwp_project_logo]` | Project logo | `id="123"` (optional) |
| `[wwp_project_link]` | External HTTPS website button, opens a new tab | `id="123"`, `label="Visit website"` (optional) |

Project shortcodes use the current post by default, including inside a project loop. Use `id` when embedding project data on another page. Password-protected project data is hidden until its password has been entered. Unpublished project data is restricted to users allowed to read that project. Use native WordPress/theme fields for the project title, content, excerpt, featured image and taxonomies.

### Portfolio behavior

Use `[wwp_portfolio_canvas]` for the Canvas-style alternative: a compact toolbar with labelled Categories and Features groups, a **Show All** button for each group, and four columns on wide screens, two on medium screens and one on small screens. Both groups and Reset sit on one row when space allows, with smaller labels and buttons; the toolbar wraps on narrow screens. Cards use a borderless presentation with 4:3 thumbnails; theme Canvas/Bootstrap colours are inherited without loading Canvas scripts, Bootstrap or another library. All projects remain available; the number of project rows is not limited. Filtering, ordering, counts, excerpts, project/website buttons and the image lightbox work as below. The original `[wwp_portfolio]` layout is unchanged. Replace the shortcode in your chosen page or LiveCanvas section to opt in; the plugin does not edit pages automatically.

On Picostrap pages, the outer content column containing the portfolio uses the equivalent of `col-12 py-2`: full width, no left offset, and `.5rem` vertical padding. This scoped CSS override does not change the parent theme files or other pages.

- All published projects without a password are rendered as crawlable links. JavaScript enhances this into instant filtering without reloads.
- Select any category within Categories, and any feature within Features. The two groups are combined with AND: a project must match both groups when both are selected.
- Counts reflect matching projects in the other filter group, rather than unrelated site-wide term counts. Zero-match unselected choices are disabled.
- Reset clears both groups. A live result count and empty state are provided.
- Clicking a featured image opens the full image in the lightbox. The project title and **View project** button open the individual project; **Visit website** opens the saved HTTPS URL in a new tab when provided. Lightbox navigation follows currently visible projects.
- Cards display the saved excerpt as plain text, or a 55-word description summary if the excerpt is empty. Theme excerpt filters are bypassed so Picostrap cannot inject a duplicate Read More button or a link to the surrounding portfolio page.
- Ordering uses the native **Order** field under Attributes (lower numbers first), then title alphabetically. No pagination: suited to a modest agency portfolio; revisit server-side filtering/pagination for hundreds of projects.
- Desktop layout: filters at left and three cards per grid row on wide screens, two on medium screens, one on small screens. Filters stack above cards on mobile.
- Portfolio and gallery image links opt out of Picostrap’s automatic GLightbox using its `nolightbox` class, so only the plugin dialog opens. Other theme images retain their existing lightbox behavior.
- With JavaScript disabled all projects remain visible. Gallery links open the full image. The native dialog lightbox supports Previous/Next, arrow keys, Escape, focus containment and focus return; older browsers fall back to image links.

## Permalinks and XML sitemaps

- Single projects: `/website-portfolio/project-name/`.
- Portfolio landing page: `/website-portfolio/` (create this page yourself).
- Categories: `/project-category/term/`; features: `/project-feature/term/`.
- No competing project archive is registered. Rewrite rules are flushed on activation/deactivation only. If needed after deployment, save **Settings → Permalinks** once.
- Projects and both taxonomies are public, making published projects and populated public terms eligible for native WordPress XML sitemaps at `/wp-sitemap.xml`. The portfolio page participates as a normal Page. A separate `/sitemap.xml` file is not generated.
- Native sitemaps must be enabled and **Discourage search engines** must be unchecked on the production site. SEO plugins and site filters can override sitemap inclusion. Sitemap eligibility does not guarantee search indexing.
- Existing content, old URLs and redirects are not migrated. If old project URLs are retired later, configure explicit 301 redirects to their corresponding new pages.

## Stored data and lifecycle

Post type: `wwp_project`. Taxonomies: `wwp_project_category`, `wwp_project_feature`.

| Meta key | Value |
| --- | --- |
| `_wwp_url` | Validated HTTPS URL or empty string |
| `_wwp_logo` | Image attachment ID or 0 |
| `_wwp_gallery` | Ordered array of `{id, heading, description}`; heading/description are plain text |

The plugin checks save permissions and nonces, ignores autosaves/revisions, sanitizes metadata and escapes rendered output. Description line breaks are preserved. Post content uses the standard WordPress editor behavior. Metadata is not exposed through a custom REST endpoint. Deactivation/deletion preserves projects, terms and media. Re-enable the plugin to access them again.

## Verification

```sh
php -l wp-web-projects.php
node --check assets/admin.js
node --check assets/frontend.js
php tests/smoke.php
node tests/frontend.cjs
```

The automated smoke tests use lightweight WordPress/DOM fixtures; also check media selection, ordering, saving, theme rendering, live URLs and sitemap inclusion on the actual WordPress site before production use.
