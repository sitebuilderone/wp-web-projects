<?php
// Isolated contract tests; no database or changes to a WordPress installation.
define( 'ABSPATH', __DIR__ );
$GLOBALS['stored'] = array();
$GLOBALS['allowed'] = true;
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function add_shortcode( ...$args ) {}
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
check( true === $GLOBALS['types'][WWP_Plugin::TYPE]['public'], 'Native sitemap eligibility' );
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
function wp_enqueue_script( $handle ) {}
function get_posts( $args ) { return array( $GLOBALS['fixture_project'] ); }
function get_the_terms( $id, $taxonomy ) { return false; }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function get_the_title( $post ) { return 'Fixture project'; }
function get_permalink( $post ) { return 'https://example.test/website-portfolio/project/'; }
function get_the_post_thumbnail_url( $post, $size ) { return 'https://example.test/featured-full.jpg'; }
function get_the_post_thumbnail( $post, $size, $attrs ) { return '<img class="' . esc_attr( $attrs['class'] ) . '" src="https://example.test/featured-large.jpg" alt="Featured">'; }
$GLOBALS['fixture_project'] = (object) array( 'ID' => 42, 'post_excerpt' => '<b>Custom excerpt</b>', 'post_content' => 'Fallback description' );
$GLOBALS['stored'][42]['_wwp_url'] = 'https://client.example.test/';
$outer_post = (object) array( 'ID' => 999 );
$GLOBALS['post'] = $outer_post;
$html = WWP_Plugin::portfolio( array() );
check( false !== strpos( $html, '<p class="card-text">Custom excerpt</p>' ), 'Render clean manual excerpt' );
check( false !== strpos( $html, 'href="https://example.test/featured-full.jpg" data-wwp-image' ), 'Thumbnail opens full image' );
check( false !== strpos( $html, 'href="https://example.test/website-portfolio/project/">View project</a>' ), 'Explicit project button destination' );
check( false !== strpos( $html, 'href="https://client.example.test/" target="_blank"' ), 'External website button destination' );
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
