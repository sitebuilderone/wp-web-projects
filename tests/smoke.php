<?php
// Isolated contract tests; no database or changes to a WordPress installation.
define( 'ABSPATH', __DIR__ );
$GLOBALS['stored'] = array();
$GLOBALS['allowed'] = true;
function add_action( $hook, $callback, ...$args ) { $GLOBALS['actions'][$hook] = $callback; }
function add_filter( $hook, $callback, ...$args ) { $GLOBALS['filters'][$hook] = $callback; }
function add_shortcode( $tag, $callback ) { $GLOBALS['shortcodes'][$tag] = $callback; }
function register_activation_hook( ...$args ) {}
function register_deactivation_hook( ...$args ) {}
function __( $text, $domain = '' ) { return $text; }
function register_post_type( $name, $args ) { $GLOBALS['types'][$name] = $args; }
function register_taxonomy( $name, $type, $args ) { $GLOBALS['taxonomies'][$name] = $args; }
function absint( $value ) { return abs( (int) $value ); }
function wp_attachment_is_image( $id ) { return in_array( $id, array( 10, 20 ), true ); }
function esc_url_raw( $url, $protocols ) { return preg_match( '~^https://[^\s<>]+$~', $url ) ? $url : ''; }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
function sanitize_text_field( $value ) { return is_scalar( $value ) ? trim( strip_tags( $value ) ) : ''; }
function sanitize_textarea_field( $value ) { return sanitize_text_field( $value ); }
function wp_unslash( $value ) { return $value; }
function wp_verify_nonce( $value, $action ) { return 'valid' === $value; }
function current_user_can( ...$args ) { return $GLOBALS['allowed']; }
function wp_is_post_revision( $id ) { return false; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['stored'][$id][$key] = $value; }
function get_post_meta( $id, $key, $single ) { return $GLOBALS['stored'][$id][$key] ?? ''; }
function shortcode_atts( $defaults, $args, $tag ) { return array_merge( $defaults, (array) $args ); }
function get_the_ID() { return 42; }
function get_post_type( $id ) { return 42 === $id ? WWP_Plugin::TYPE : 'post'; }
function post_password_required( $id ) { return $GLOBALS['password_required'] ?? false; }
function get_post_status( $id ) { return $GLOBALS['status'] ?? 'publish'; }
function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
require dirname( __DIR__ ) . '/wp-web-projects.php';
function check( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
WWP_Plugin::register();
check( 'website-portfolio' === $GLOBALS['types'][WWP_Plugin::TYPE]['rewrite']['slug'], 'Permalink contract' );
check( false === $GLOBALS['types'][WWP_Plugin::TYPE]['has_archive'], 'No archive/page conflict' );
check( true === $GLOBALS['types'][WWP_Plugin::TYPE]['public'], 'Keep projects available for portfolio display' );
check( 2 === count( $GLOBALS['taxonomies'] ), 'Two taxonomies' );
check( false === WWP_Plugin::classic_editor( true, WWP_Plugin::TYPE ), 'Classic project editor' );
check( true === WWP_Plugin::classic_editor( true, 'post' ), 'Other editors unchanged' );
foreach ( array( 'http://example.com', 'javascript:alert(1)', '//example.com', 'https:///bad' ) as $url ) {
	check( '' === WWP_Plugin::https_url( $url ), 'Reject non-HTTPS or malformed URL' );
}
check( 'https://example.com/path?q=1' === WWP_Plugin::https_url( ' https://example.com/path?q=1 ' ), 'Valid URL preserved' );
$_POST = array( 'wwp_nonce' => 'bad', 'wwp_url' => 'https://example.com' );
WWP_Plugin::save( 42 ); check( empty( $GLOBALS['stored'] ), 'Reject invalid nonce' );
$_POST['wwp_nonce'] = 'valid'; $GLOBALS['allowed'] = false;
WWP_Plugin::save( 42 ); check( empty( $GLOBALS['stored'] ), 'Reject unauthorized saves' );
$GLOBALS['allowed'] = true;
$_POST['wwp_logo'] = 30;
$_POST['wwp_gallery'] = array( array( 'id' => 20, 'heading' => '<b>Second</b>', 'description' => "Line 1\nLine 2" ), array( 'id' => 30 ), array( 'id' => 10, 'heading' => 'First' ), 'invalid' );
WWP_Plugin::save( 42 );
$meta = $GLOBALS['stored'][42];
check( 0 === $meta['_wwp_logo'], 'Reject non-image logo' );
check( array( 20, 10 ) === array_column( $meta['_wwp_gallery'], 'id' ), 'Gallery order and image validation' );
check( 'Second' === $meta['_wwp_gallery'][0]['heading'], 'Strip markup' );
check( "Line 1\nLine 2" === $meta['_wwp_gallery'][0]['description'], 'Keep description line breaks' );
check( false !== strpos( WWP_Plugin::link( array( 'label' => '<script>' ) ), '&lt;script&gt;' ), 'Escape link label' );
$GLOBALS['status'] = 'draft'; $GLOBALS['allowed'] = false;
check( '' === WWP_Plugin::link( array() ), 'Hide unpublished project data' );
$GLOBALS['status'] = 'publish'; $GLOBALS['allowed'] = true;
$GLOBALS['password_required'] = true;
check( '' === WWP_Plugin::link( array() ), 'Hide password-protected project data' );
$GLOBALS['password_required'] = false;
check( 0 === WWP_Plugin::image_id( array( 10 ) ), 'Reject malformed attachment IDs' );
$_POST = array( 'wwp_nonce' => 'valid' ); WWP_Plugin::save( 42 );
check( array() === $GLOBALS['stored'][42]['_wwp_gallery'], 'Allow clearing gallery' );
echo "PASS: registration, permissions, sanitization, gallery ordering and shortcode visibility\n";

// Render a real shortcode output against a theme-like fixture. A filtered
// excerpt must never run here: Picostrap appends markup with the outer post URL.
function wp_strip_all_tags( $text, $breaks = false ) { return trim( strip_tags( $text ) ); }
function strip_shortcodes( $text ) { return preg_replace( '/\[[^\]]+\]/', '', $text ); }
function wp_trim_words( $text, $length, $more ) { $words = preg_split( '/\s+/', trim( $text ) ); return implode( ' ', array_slice( $words, 0, $length ) ) . ( count( $words ) > $length ? $more : '' ); }
function get_the_excerpt( $post ) { throw new RuntimeException( 'Theme-filtered excerpts must not be used for cards' ); }
function wp_enqueue_script( $handle ) { $GLOBALS['enqueued_scripts'][] = $handle; }
function wp_style_is( $handle, $status ) { return false; }
function get_posts( $args ) { $GLOBALS['last_query'] = $args; return $GLOBALS['fixture_empty'] ?? false ? array() : array( $GLOBALS['fixture_project'] ); }
function get_the_terms( $id, $taxonomy ) { return $GLOBALS['fixture_terms'][$taxonomy] ?? false; }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function get_the_title( $post ) { return 'Fixture project'; }
function get_permalink( $post ) { return 'https://example.test/website-portfolio/project/'; }
function get_the_post_thumbnail_url( $post, $size ) { return empty( $GLOBALS['no_featured_image'] ) ? 'https://example.test/featured-full.jpg' : false; }
function get_the_post_thumbnail( $post, $size, $attrs ) { return '<img class="' . esc_attr( $attrs['class'] ) . '" src="https://example.test/featured-large.jpg" alt="Featured">'; }
$GLOBALS['fixture_project'] = (object) array( 'ID' => 42, 'post_excerpt' => '<b>Custom excerpt</b>', 'post_content' => 'Fallback description' );
$GLOBALS['stored'][42]['_wwp_url'] = 'https://client.example.test/';
$outer_post = (object) array( 'ID' => 999 );
$GLOBALS['post'] = $outer_post;
$html = WWP_Plugin::portfolio( array() );
check( false !== strpos( $html, '<p class="card-text">Custom excerpt</p>' ), 'Render clean manual excerpt' );
check( false !== strpos( $html, 'href="https://example.test/featured-full.jpg" data-wwp-image' ), 'Thumbnail opens full image' );
check( false === strpos( $html, 'https://example.test/website-portfolio/project/' ), 'No project detail links in grid' );
check( false === strpos( $html, '>View project</a>' ), 'No View project button in grid' );
check( false !== strpos( $html, 'data-wwp-preview>Fixture project</a>' ), 'Project titles trigger image preview' );
check( false === strpos( $html, 'class="wwp-card-actions' ), 'Portfolio cards omit website buttons' );
check( false !== strpos( $html, 'data-website="https://client.example.test/"' ), 'Portfolio popup retains website URL' );
check( false !== strpos( $html, '<dialog class="wwp-lightbox"' ), 'Portfolio includes lightbox' );
check( $GLOBALS['post'] === $outer_post, 'Preserve surrounding LiveCanvas post context' );
$GLOBALS['fixture_project']->post_excerpt = '';
$GLOBALS['fixture_project']->post_content = '[wwp_gallery]<p>' . str_repeat( 'word ', 60 ) . '</p>';
$html = WWP_Plugin::portfolio( array() );
check( false !== strpos( $html, '<p class="card-text">' . implode( ' ', array_fill( 0, 55, 'word' ) ) . '…</p>' ), 'Generate plain description fallback without shortcodes' );
echo "PASS: card excerpts, image links, real button URLs, lightbox markup and LiveCanvas context\n";

// Picostrap opts linked images into GLightbox unless the link/image has
// nolightbox. Exercise both shortcode outputs against that exact contract.
function wp_get_attachment_image_url( $id, $size ) { return 'https://example.test/gallery-full.jpg'; }
function wp_get_attachment_image( $id, $size, $icon = false, $attrs = array() ) { return '<img class="' . esc_attr( $attrs['class'] ?? '' ) . '" src="https://example.test/gallery.jpg" alt="Gallery">'; }
function check_theme_lightbox_exclusion( $html ) {
	$dom = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$dom->loadHTML( '<div id="container-content-page">' . $html . '</div>' );
	libxml_clear_errors(); libxml_use_internal_errors( $previous );
	$xpath = new DOMXPath( $dom );
	$opted_out_link = 'contains(concat(" ", normalize-space(@class), " "), " nolightbox ")';
	$selector = '//div[@id="container-content-page"]//a[not(' . $opted_out_link . ')]//img[not(' . $opted_out_link . ')]';
	check( 0 === $xpath->query( $selector )->length, 'Plugin images must not trigger Picostrap automatic GLightbox' );
	check( $xpath->query( '//a[@data-wwp-image]' )->length > 0, 'Keep plugin lightbox image links' );
}
check_theme_lightbox_exclusion( WWP_Plugin::portfolio( array() ) );
$GLOBALS['stored'][42]['_wwp_gallery'] = array( array( 'id' => 10, 'heading' => 'Gallery image', 'description' => 'Details' ) );
check_theme_lightbox_exclusion( WWP_Plugin::gallery( array( 'id' => 42 ) ) );
echo "PASS: portfolio and gallery opt out of Picostrap duplicate lightbox\n";

$GLOBALS['fixture_terms'] = array(
	WWP_Plugin::CATEGORY => array( (object) array( 'term_id' => 1, 'name' => 'Industry & client' ) ),
	WWP_Plugin::FEATURE => array( (object) array( 'term_id' => 2, 'name' => 'Online booking' ) ),
);
check( is_callable( $GLOBALS['shortcodes']['wwp_portfolio_canvas'] ), 'Register Canvas shortcode' );
$html = WWP_Plugin::portfolio_canvas( array() );
check( 2 === substr_count( $html, 'class="wwp-filter-bar"' ), 'Two horizontal filter groups' );
check( false !== strpos( $html, 'data-wwp-all="' . WWP_Plugin::CATEGORY . '"' ), 'Category Show All' );
check( false !== strpos( $html, 'data-wwp-all="' . WWP_Plugin::FEATURE . '"' ), 'Feature Show All' );
check( false !== strpos( $html, 'Industry &amp; client' ), 'Escape filter term labels' );
check( false !== strpos( $html, 'col-xl-3 wwp-project' ), 'Four-column Canvas grid' );
check( false === strpos( $html, '>View project</a>' ), 'Canvas omits project detail action' );
check_theme_lightbox_exclusion( $html );
$original = WWP_Plugin::portfolio( array() );
check( false !== strpos( $original, 'col-lg-3 wwp-filters' ) && false !== strpos( $original, 'col-xl-4 wwp-project' ), 'Original sidebar and three-column grid preserved' );
check( false === strpos( $original, 'data-wwp-all' ), 'Canvas controls do not affect original shortcode' );
echo "PASS: Canvas shortcode, two filter bars, escaped terms, four columns and original layout preservation\n";

function wp_unique_id( $prefix = '' ) { static $id = 0; return $prefix . ++$id; }
check( is_callable( $GLOBALS['shortcodes']['wwp_portfolio_carousel'] ), 'Register carousel shortcode' );
$html = WWP_Plugin::portfolio_carousel( array( 'heading' => '<Featured & projects>', 'columns' => '9', 'limit' => '8' ) );
check( 8 === $GLOBALS['last_query']['posts_per_page'], 'Carousel project limit' );
check( 'publish' === $GLOBALS['last_query']['post_status'] && false === $GLOBALS['last_query']['has_password'], 'Carousel excludes private and password protected projects' );
check( array( 'menu_order' => 'ASC', 'title' => 'ASC' ) === $GLOBALS['last_query']['orderby'], 'Carousel uses project ordering' );
check( false !== strpos( $html, 'wwp-carousel-columns-4' ), 'Clamp column count' );
check( in_array( 'wwp-carousel', $GLOBALS['enqueued_scripts'], true ), 'Load carousel controller' );
check( false !== strpos( $html, 'data-wwp-columns="4"' ), 'Pass column count to Swiper' );
$carousel_dom = new DOMDocument();
$previous_errors = libxml_use_internal_errors( true );
$carousel_dom->loadHTML( '<section id="home-section">' . $html . '</section>' );
libxml_clear_errors(); libxml_use_internal_errors( $previous_errors );
$carousel_xpath = new DOMXPath( $carousel_dom );
check( 1 === $carousel_xpath->query( '//section[@id="home-section"]/div[@class="wwp-carousel-container container"]/div[@data-wwp-carousel]' )->length, 'Direct section shortcode includes theme content container' );
check( 0 === $carousel_xpath->query( '//section[@id="home-section"]//section' )->length, 'Avoid nested theme section layout rules' );

check( false !== strpos( $html, 'data-wwp-viewport tabindex="0"' ), 'Focusable Swiper viewport' );
check( false === strpos( $html, 'wwp-carousel-heading' ) && false === strpos( $html, 'Featured &amp; projects' ), 'Ignore legacy heading attribute' );
check( false === strpos( $html, 'Industry &amp; client' ), 'Carousel omits categories' );
check( false === strpos( $html, 'class="wwp-card-actions' ), 'Carousel cards omit website and project buttons' );
check( false === strpos( $html, '>View project</a>' ), 'Carousel omits View project action' );
check( false !== strpos( $html, 'data-website="https://client.example.test/"' ), 'Carousel popup receives saved website URL' );
check( false !== strpos( $html, 'data-wwp-website target="_blank" rel="noopener noreferrer" hidden' ), 'Popup has optional safe website action' );
check( false !== strpos( $html, '<dialog class="wwp-lightbox"' ), 'Reuse existing popup' );
check( false === strpos( $html, 'data-wwp-portfolio' ), 'Keep carousel independent of grid filtering' );
check_theme_lightbox_exclusion( $html );
$GLOBALS['fixture_project']->post_excerpt = '<b>Summary & details</b>';
$excerpt_html = WWP_Plugin::portfolio_carousel( array() );
check( false !== strpos( $excerpt_html, 'data-description="Summary &amp; details"' ), 'Popup uses escaped plain project excerpt' );
check( false !== strpos( $excerpt_html, '<p class="wwp-carousel-excerpt">Summary &amp; details</p>' ), 'Cards replace categories with escaped plain excerpts' );
$GLOBALS['stored'][42]['_wwp_url'] = 'javascript:alert(1)';
$no_url_html = WWP_Plugin::portfolio_carousel( array() );
check( false !== strpos( $no_url_html, 'data-website=""' ), 'Invalid URL omitted from popup' );
check( false === strpos( $no_url_html, 'class="wwp-card-actions' ), 'No empty carousel card actions without URL' );
$GLOBALS['stored'][42]['_wwp_url'] = 'https://client.example.test/';
$second = WWP_Plugin::portfolio_carousel( array( 'limit' => '-1', 'columns' => '2' ) );
check( -1 === $GLOBALS['last_query']['posts_per_page'], 'Support all projects' );
preg_match( '/id="(wwp-carousel-[^"]+)"/', $html, $first_id );
preg_match( '/id="(wwp-carousel-[^"]+)"/', $second, $second_id );
check( $first_id[1] !== $second_id[1], 'Unique track ID per instance' );
check( false !== strpos( $html, 'aria-controls="' . $first_id[1] . '"' ), 'Controls address their own track' );
check( $GLOBALS['post'] === $outer_post, 'Carousel preserves LiveCanvas context' );
$GLOBALS['fixture_empty'] = true;
check( '' === WWP_Plugin::portfolio_carousel( array() ), 'Empty carousel omits controls and dialog' );
echo "PASS: carousel registration, public query, limits, escaping, popup, actions and instance IDs\n";

// Asset ordering must work when another shortcode already enqueued the lightbox.
function get_theme_file_path( $file ) { return empty( $GLOBALS['no_theme_swiper'] ) ? dirname( __DIR__, 3 ) . '/themes/pico-one-c/' . $file : '/wwp-missing/' . $file; }
function get_theme_file_uri( $file ) { return 'https://example.test/theme/' . $file; }
function plugins_url( $file, $plugin ) { return 'https://example.test/plugin/' . $file; }
function wp_enqueue_style( ...$args ) {}
function wp_register_script( $handle, $src, $deps, $version, $footer ) { $GLOBALS['scripts'][$handle] = array( 'src' => $src, 'deps' => $deps ); }
function wp_register_style( ...$args ) {}
WWP_Plugin::front_assets();
if ( is_readable( get_theme_file_path( 'js/canvas/plugins.swiper.js' ) ) ) {
    check( array( 'wwp-frontend', 'wwp-theme-swiper' ) === $GLOBALS['scripts']['wwp-carousel']['deps'], 'Theme Swiper loads before carousel controller' );
    check( 'https://example.test/theme/js/canvas/plugins.swiper.js' === $GLOBALS['scripts']['wwp-theme-swiper']['src'], 'Reuse theme asset rather than CDN' );
}
$GLOBALS['no_theme_swiper'] = true;
WWP_Plugin::front_assets();
check( array( 'wwp-frontend' ) === $GLOBALS['scripts']['wwp-carousel']['deps'], 'Missing theme keeps independent fallback' );
echo "PASS: theme Swiper reuse, controller asset ordering and missing-theme fallback\n";

function is_singular( $type ) { return ( $GLOBALS['request_type'] ?? '' ) === $type; }
function is_tax( $taxonomies ) { return in_array( $GLOBALS['request_taxonomy'] ?? '', (array) $taxonomies, true ); }
foreach ( array( WWP_Plugin::TYPE, WWP_Plugin::CATEGORY, WWP_Plugin::FEATURE ) as $type ) {
    $GLOBALS['request_type'] = WWP_Plugin::TYPE === $type ? $type : '';
    $GLOBALS['request_taxonomy'] = WWP_Plugin::TYPE !== $type ? $type : '';
    $robots = WWP_Plugin::noindex_robots( array( 'index' => true, 'nofollow' => true ) );
    check( true === $robots['noindex'] && ! isset( $robots['index'] ), 'Noindex project URLs and archives' );
    check( true === $robots['nofollow'], 'Preserve existing robots restrictions' );
    check( true === WWP_Plugin::exclude_seo_sitemap_type( false, $type ), 'SEO sitemap excludes project types' );
    check( '' === WWP_Plugin::exclude_explicit_html_sitemap( false, 'wp_sitemap_page', array( 'only' => $type ) ), 'Exclude explicit HTML sitemap type' );
}
$GLOBALS['request_type'] = 'page'; $GLOBALS['request_taxonomy'] = '';
check( array( 'index' => true ) === WWP_Plugin::noindex_robots( array( 'index' => true ) ), 'Landing Page robots unchanged' );
check( false === WWP_Plugin::exclude_seo_sitemap_type( false, 'page' ), 'Keep pages in SEO sitemap' );
check( true === WWP_Plugin::exclude_seo_sitemap_type( true, 'post' ), 'Preserve other SEO exclusions' );
$types = array( 'page' => 'landing', 'post' => 'blog', WWP_Plugin::TYPE => 'project', WWP_Plugin::CATEGORY => 'category', WWP_Plugin::FEATURE => 'feature' );
check( array( 'page' => 'landing', 'post' => 'blog' ) === WWP_Plugin::exclude_sitemap_types( $types ), 'Remove only project sitemap entries' );
check( '1' === WWP_Plugin::exclude_html_sitemap_type( false ), 'HTML sitemap exclusion options enabled at runtime' );
check( false === WWP_Plugin::exclude_explicit_html_sitemap( false, 'wp_sitemap_page', array( 'only' => 'page' ) ), 'Normal HTML sitemap pages preserved' );
check( false === WWP_Plugin::exclude_explicit_html_sitemap( false, 'other', array( 'only' => WWP_Plugin::TYPE ) ), 'Other shortcodes unaffected' );
check( is_callable( $GLOBALS['actions']['template_redirect'] ), 'Register noindex HTTP header' );
foreach ( array( 'wp_robots', 'wp_sitemaps_post_types', 'wp_sitemaps_taxonomies', 'pre_option_wsp_exclude_cpt_wwp_project', 'pre_option_wsp_exclude_taxonomy_wwp_project_category', 'pre_option_wsp_exclude_taxonomy_wwp_project_feature' ) as $hook ) {
    check( isset( $GLOBALS['filters'][$hook] ), 'Register indexing and sitemap integration: ' . $hook );
}
$GLOBALS['fixture_empty'] = false;
foreach ( array( 'portfolio', 'portfolio_canvas', 'portfolio_carousel' ) as $method ) {
    $html = WWP_Plugin::$method( array() );
    check( false === strpos( $html, 'https://example.test/website-portfolio/project/' ), 'No detail URL in ' . $method );
    check( false === strpos( $html, '>View project</a>' ), 'No detail button in ' . $method );
    check( 1 === substr_count( $html, ' data-wwp-image ' ) && 1 === substr_count( $html, ' data-wwp-preview>' ), 'Title trigger does not duplicate gallery images in ' . $method );
    $GLOBALS['no_featured_image'] = true;
    $html = WWP_Plugin::$method( array() );
    check( false === strpos( $html, 'data-wwp-preview' ) && false === strpos( $html, 'https://example.test/website-portfolio/project/' ), 'Image-less title is plain text in ' . $method );
    $GLOBALS['no_featured_image'] = false;
}
echo "PASS: popup-only cards, image-less titles, noindex scope, native/SEO/HTML sitemap exclusions and landing Page preservation\n";
