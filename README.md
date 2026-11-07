# WP Web Projects

A standalone WordPress plugin for a website portfolio, designed for a Bootstrap-based LiveCanvas site. No ACF or SEO plugin is required. The carousel reuses the active theme’s Swiper bundle when available, with native scrolling as a fallback. Requires WordPress 6.2+, PHP 7.4+, and theme-provided Bootstrap CSS (Bootstrap 5 recommended).

## Setup

1. Install this folder under `wp-content/plugins/wp-web-projects` and activate **WP Web Projects** in WordPress.
2. Add projects under **Website Projects**. Use the title for the name, the Classic Editor for the description, and the standard Excerpt and Featured Image panels. Set the HTTPS website URL and logo in **Project Details & Gallery**.
3. Add images to the gallery and enter optional headings and descriptions. Move up/down sets the saved order. Image alt text is managed in the Media Library. Removing an image from a project does not delete the attachment.
4. Create **Project Categories** for client types/industries, and **Project Features** for capabilities such as E-commerce, Lead Generation and Online Booking. Multiple terms can be assigned from each group. No terms or demo projects are created automatically.
5. Create a normal WordPress page with the slug `website-portfolio`. Add `[wwp_portfolio]` using LiveCanvas's shortcode rendering. Design the rest of the page in LiveCanvas.
6. Build your individual project layout in your theme/LiveCanvas using the `wwp_project` post type. The plugin does not replace your theme's single or taxonomy templates.

The plugin uses the theme's Bootstrap CSS and does not enqueue Bootstrap. It adds small scoped styles, dependency-free filtering/lightbox scripts, and a carousel controller that can reuse theme-provided Swiper. Styles load site-wide so shortcodes rendered late by LiveCanvas still display correctly; front-end JavaScript loads only when a portfolio, carousel or gallery shortcode renders.

## Shortcodes

| Shortcode | Purpose | Attributes |
| --- | --- | --- |
| `[wwp_portfolio]` | Filterable grid with featured image, title and excerpt | None |
| `[wwp_portfolio_canvas]` | Canvas-style grid with a compact Categories/Features filter toolbar | None |
| `[wwp_portfolio_carousel]` | Responsive horizontal project carousel with the existing image popup | `limit="12"`, `columns="4"` (optional) |
| `[wwp_gallery]` | Ordered gallery with lightbox, image headings and descriptions | `id="123"` (optional) |
| `[wwp_project_logo]` | Project logo | `id="123"` (optional) |
| `[wwp_project_link]` | External HTTPS website button, opens a new tab | `id="123"`, `label="Visit website"` (optional) |

Project shortcodes use the current post by default, including inside a project loop. Use `id` when embedding project data on another page. Password-protected project data is hidden until its password has been entered. Unpublished project data is restricted to users allowed to read that project. Use native WordPress/theme fields for the project title, content, excerpt, featured image and taxonomies.

### Home page carousel

Add this shortcode inside your home page or LiveCanvas section:

```text
[wwp_portfolio_carousel]
```

The carousel follows the Canvas portfolio presentation: borderless 4:3 featured images, project titles and plain excerpts beneath. Titles open the same image popup as the thumbnails (or display as plain text when no featured image is set). Cards have no category labels or action buttons, and the shortcode renders no section heading (older `heading` attributes are ignored). Clicking an image opens the same Previous/Next/Close popup used by the portfolio grid. Project image popups include the plain project excerpt (or a 55-word description summary when empty) and a **Visit website** button when a valid HTTPS URL is saved. Gallery image popups retain their project-specific headings and descriptions. Popup navigation includes all images in that carousel, including cards currently outside the scrolling view. Each carousel has its own controls and popup.

- `limit`: maximum projects, default `12`; use `-1` for all. Other values are clamped to 1–100. Projects use native Order, then title, and exclude unpublished/password-protected projects.
- `columns`: maximum cards visible at once, `2`, `3` or `4` (default `4`). Sizing follows the containing section: below 600px it shows one card, from 600px two, and from 1000px the configured count. Swiper uses container-based breakpoints; the native fallback uses CSS container queries (or one card in older browsers).
- Place the shortcode directly inside a theme/LiveCanvas section. It includes a theme Bootstrap `.container` wrapper, so a full-width section keeps the carousel aligned with your site’s normal content width. Within a narrower container or column it stays within that parent width. The carousel uses div wrappers rather than a nested section, avoiding theme section layout rules.
- Swipe or scroll horizontally, use the keyboard-focusable scroll area, or use the labelled Previous/Next project buttons. No automatic rotation. Buttons hide when everything fits; scrolling and image links work without JavaScript. Reduced-motion preferences are respected.
- The shortcode reuses the active theme’s `js/canvas/plugins.swiper.js` and `css/canvas/swiper.css` files (verified with the pico-one-c theme’s Swiper 12.1.3). Only carousel instances enqueue the Swiper script; an already-enqueued theme stylesheet is reused. No CDN or bundled duplicate is added. If these theme assets are absent, it uses a global `window.Swiper` when available or falls back to native scrolling. Each instance has isolated controls, no automatic rotation or loop clones, and keyboard/focus support. This theme-Swiper integration is an explicit exception to the original dependency-free carousel requirement. This shortcode does not apply the portfolio page's outer-width override to your home page section. Adding the shortcode to the page remains a manual step; no page content is changed by the plugin.

### Portfolio behavior

Use `[wwp_portfolio_canvas]` for the Canvas-style alternative: a compact toolbar with labelled Categories and Features groups, a **Show All** button for each group, and four columns on wide screens, two on medium screens and one on small screens. Both groups and Reset sit on one row when space allows, with smaller labels and buttons; the toolbar wraps on narrow screens. Cards use a borderless presentation with 4:3 thumbnails; theme Canvas/Bootstrap colours are inherited without loading Canvas scripts, Bootstrap or another library. Both portfolio shortcodes animate filtered cards with a fade/shrink and smooth repositioning over 650ms, using native browser animation without loading another library. Reduced-motion preferences use instant filtering. Rapid filter changes and resizing clear ongoing animations. All projects remain available; the number of project rows is not limited. Filtering, ordering, counts, excerpts and the image lightbox work as below. The original `[wwp_portfolio]` layout is unchanged. Replace the shortcode in your chosen page or LiveCanvas section to opt in; the plugin does not edit pages automatically.

On Picostrap pages, the outer content column containing the portfolio uses the equivalent of `col-12 py-2`: full width, no left offset, and `.5rem` vertical padding. This scoped CSS override does not change the parent theme files or other pages.

- All published projects without a password are displayed. JavaScript enhances this into instant filtering without reloads. No portfolio shortcode links to individual project pages.
- Select any category within Categories, and any feature within Features. The two groups are combined with AND: a project must match both groups when both are selected.
- Counts reflect matching projects in the other filter group, rather than unrelated site-wide term counts. Zero-match unselected choices are disabled.
- Reset clears both groups. A live result count and empty state are provided.
- Clicking a featured image or project title opens the full image in the lightbox. **View project** is removed from every layout. Without a featured image, the title is plain text. Cards have no **Visit website** button; the popup shows a **Visit Website** text link below the description when a saved HTTPS URL is available. It opens in a new tab. Lightbox navigation follows currently visible projects; title triggers do not duplicate images in the popup sequence.
- Cards display the saved excerpt as plain text, or a 55-word description summary if the excerpt is empty. Theme excerpt filters are bypassed so Picostrap cannot inject a duplicate Read More button or a link to the surrounding portfolio page.
- Ordering uses the native **Order** field under Attributes (lower numbers first), then title alphabetically. No pagination: suited to a modest agency portfolio; revisit server-side filtering/pagination for hundreds of projects.
- Desktop layout: filters at left and three cards per grid row on wide screens, two on medium screens, one on small screens. Filters stack above cards on mobile.
- The portfolio popup starts on the featured image and follows it with the project gallery in saved order. **Previous Image** and **Next Image** appear directly below the image, above its heading when there are additional images. Each gallery slide shows its own heading and description. Arrow keys navigate images when focus is on the image controls and navigate projects elsewhere in the portfolio popup.
- Portfolio and gallery image links opt out of Picostrap’s automatic GLightbox using its `nolightbox` class, so only the plugin dialog opens. Other theme images retain their existing lightbox behavior.
- With JavaScript disabled all projects remain visible. Gallery links open the full image. The native dialog lightbox has **Previous Website** and **Next Website** controls, an accessible **X** close button, arrow keys, Escape, focus containment and focus return; older browsers fall back to image links.

### Featured image sizing

Portfolio grid and carousel thumbnails render at **4:3**, crop to fill, and align to the top of the image. Create consistent featured images at **1600 × 1200 px** for this layout. Other possible presentation ratios include **16:9** (for example 1920 × 1080 px) for a wider screenshot, or **3:2** (1800 × 1200 px) as a middle ground; changing the displayed ratio currently requires a scoped CSS change rather than a shortcode attribute. The popup displays the original uploaded image proportionally, without cropping, constrained by the available screen width and 65% of the viewport height. Very tall full-page screenshots therefore appear narrower in the popup; use a selected section of the site for featured images and the project gallery for additional screenshots.

## Permalinks and XML sitemaps

- Single projects: `/website-portfolio/project-name/`.
- Portfolio landing page: `/website-portfolio/` (create this page yourself).
- Categories: `/project-category/term/`; features: `/project-feature/term/`.
- No competing project archive is registered. Rewrite rules are flushed on activation/deactivation only. If needed after deployment, save **Settings → Permalinks** once.
- Individual project URLs and project category/feature archives remain accessible, but receive `noindex` in WordPress robots metadata and an `X-Robots-Tag: noindex` response header. The normal portfolio landing Page is unaffected; existing site-wide search visibility settings still apply.
- Projects and both taxonomies are excluded from native WordPress XML sitemaps, Yoast and Rank Math sitemaps, and WP Sitemap Page’s HTML listings (including explicit `only` shortcodes). Exclusions apply at runtime without changing saved plugin settings. No separate sitemap is generated. Other sitemap plugins may need their own exclusions.
- After deploying, clear page/sitemap caches so fresh markup, headers and sitemap lists are served. Previously indexed URLs disappear only after search engines recrawl them; this change does not instantly remove search results. Do not block these URLs in robots.txt, since crawlers need to see the noindex directive.
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
node --check assets/carousel.js
php tests/smoke.php
node tests/frontend.cjs
```

The automated smoke tests use lightweight WordPress/DOM fixtures; also check media selection, ordering, saving, theme rendering, live URLs and sitemap inclusion on the actual WordPress site before production use.
