<?php
/**
 * Plugin Name: WP Web Projects
 * Description: Website portfolio projects, categories, features, filterable Bootstrap cards and image galleries.
 * Version: 1.0.12
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: SiteBuilderOne
 * Text Domain: wp-web-projects
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class WWP_Plugin {
	const TYPE = 'wwp_project';
	const CATEGORY = 'wwp_project_category';
	const FEATURE = 'wwp_project_feature';

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'classic_editor' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_' . self::TYPE, array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		// Register styles early: LiveCanvas may render shortcodes after wp_head.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'front_assets' ) );
		add_shortcode( 'wwp_portfolio', array( __CLASS__, 'portfolio' ) );
		add_shortcode( 'wwp_portfolio_canvas', array( __CLASS__, 'portfolio_canvas' ) );
		add_shortcode( 'wwp_portfolio_carousel', array( __CLASS__, 'portfolio_carousel' ) );
		add_shortcode( 'wwp_gallery', array( __CLASS__, 'gallery' ) );
		add_shortcode( 'wwp_project_logo', array( __CLASS__, 'logo' ) );
		add_shortcode( 'wwp_project_link', array( __CLASS__, 'link' ) );
		add_filter( 'wp_robots', array( __CLASS__, 'noindex_robots' ), PHP_INT_MAX );
		add_action( 'template_redirect', array( __CLASS__, 'noindex_header' ) );
		add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'exclude_sitemap_types' ) );
		add_filter( 'wp_sitemaps_taxonomies', array( __CLASS__, 'exclude_sitemap_types' ) );
		foreach ( array( 'wpseo_sitemap_exclude_post_type', 'wpseo_sitemap_exclude_taxonomy', 'rank_math/sitemap/exclude_post_type', 'rank_math/sitemap/exclude_taxonomy' ) as $hook ) {
			add_filter( $hook, array( __CLASS__, 'exclude_seo_sitemap_type' ), 10, 2 );
		}
		// WP Sitemap Page reads these exclusions at render time; do not change saved settings.
		foreach ( array( 'wsp_exclude_cpt_' . self::TYPE, 'wsp_exclude_taxonomy_' . self::CATEGORY, 'wsp_exclude_taxonomy_' . self::FEATURE ) as $option ) {
			add_filter( 'pre_option_' . $option, array( __CLASS__, 'exclude_html_sitemap_type' ) );
		}
		add_filter( 'pre_do_shortcode_tag', array( __CLASS__, 'exclude_explicit_html_sitemap' ), 10, 3 );
	}

	public static function register() {
		register_post_type( self::TYPE, array(
			'labels' => array( 'name' => __( 'Website Projects', 'wp-web-projects' ), 'singular_name' => __( 'Website Project', 'wp-web-projects' ), 'add_new_item' => __( 'Add New Website Project', 'wp-web-projects' ), 'edit_item' => __( 'Edit Website Project', 'wp-web-projects' ) ),
			'public' => true, 'show_in_rest' => true, 'has_archive' => false,
			'rewrite' => array( 'slug' => 'website-portfolio', 'with_front' => false ),
			'menu_icon' => 'dashicons-portfolio',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ),
		) );
		foreach ( array( self::CATEGORY => array( 'Project Categories', 'Project Category', 'project-category' ), self::FEATURE => array( 'Project Features', 'Project Feature', 'project-feature' ) ) as $taxonomy => $labels ) {
			register_taxonomy( $taxonomy, self::TYPE, array(
				'labels' => array( 'name' => $labels[0], 'singular_name' => $labels[1] ),
				'public' => true, 'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true,
				'rewrite' => array( 'slug' => $labels[2], 'with_front' => false ),
			) );
		}
	}

	private static function is_project_url() {
		return is_singular( self::TYPE ) || is_tax( array( self::CATEGORY, self::FEATURE ) );
	}
	public static function noindex_robots( $robots ) {
		if ( self::is_project_url() ) {
			unset( $robots['index'] );
			$robots['noindex'] = true;
		}
		return $robots;
	}
	public static function noindex_header() {
		// Also works when an SEO plugin replaces WordPress's robots meta tag.
		if ( self::is_project_url() && ! headers_sent() ) { header( 'X-Robots-Tag: noindex', false ); }
	}
	public static function exclude_sitemap_types( $types ) {
		unset( $types[ self::TYPE ], $types[ self::CATEGORY ], $types[ self::FEATURE ] );
		return $types;
	}
	public static function exclude_seo_sitemap_type( $excluded, $type ) {
		return in_array( $type, array( self::TYPE, self::CATEGORY, self::FEATURE ), true ) ? true : $excluded;
	}
	public static function exclude_html_sitemap_type( $value ) { return '1'; }
	public static function exclude_explicit_html_sitemap( $result, $tag, $atts ) {
		return 'wp_sitemap_page' === $tag && is_array( $atts ) && in_array( $atts['only'] ?? '', array( self::TYPE, self::CATEGORY, self::FEATURE ), true ) ? '' : $result;
	}
	public static function classic_editor( $enabled, $type ) { return self::TYPE === $type ? false : $enabled; }
	public static function activate() { self::register(); flush_rewrite_rules(); }
	public static function deactivate() {
		unregister_taxonomy( self::CATEGORY ); unregister_taxonomy( self::FEATURE );
		unregister_post_type( self::TYPE ); flush_rewrite_rules();
	}
	public static function image_id( $value ) {
		$id = is_scalar( $value ) ? absint( $value ) : 0;
		return $id && wp_attachment_is_image( $id ) ? $id : 0;
	}
	public static function https_url( $value ) {
		$url = esc_url_raw( trim( (string) $value ), array( 'https' ) );
		return 'https' === wp_parse_url( $url, PHP_URL_SCHEME ) && wp_parse_url( $url, PHP_URL_HOST ) ? $url : '';
	}
	public static function clean_gallery( $rows ) {
		$clean = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$id = self::image_id( $row['id'] ?? 0 );
			if ( ! $id ) { continue; }
			$clean[] = array( 'id' => $id, 'heading' => sanitize_text_field( $row['heading'] ?? '' ), 'description' => sanitize_textarea_field( $row['description'] ?? '' ) );
		}
		return $clean;
	}
	public static function boxes() { add_meta_box( 'wwp-details', __( 'Project Details & Gallery', 'wp-web-projects' ), array( __CLASS__, 'details' ), self::TYPE, 'normal', 'high' ); }
	public static function details( $post ) {
		wp_nonce_field( 'wwp_save', 'wwp_nonce' );
		$url = get_post_meta( $post->ID, '_wwp_url', true );
		$logo = self::image_id( get_post_meta( $post->ID, '_wwp_logo', true ) );
		$rows = get_post_meta( $post->ID, '_wwp_gallery', true );
		?>
		<p><label for="wwp-url"><strong><?php esc_html_e( 'Website URL (HTTPS)', 'wp-web-projects' ); ?></strong></label></p>
		<input class="widefat" id="wwp-url" name="wwp_url" type="url" pattern="https://.+" placeholder="https://example.com/" value="<?php echo esc_attr( $url ); ?>">
		<p class="description"><?php esc_html_e( 'Optional. Enter a full HTTPS URL. Invalid URLs are not stored.', 'wp-web-projects' ); ?></p>
		<h3><?php esc_html_e( 'Logo', 'wp-web-projects' ); ?></h3>
		<div id="wwp-logo-preview"><?php if ( $logo ) { echo wp_get_attachment_image( $logo, 'thumbnail' ); } ?></div>
		<input id="wwp-logo" type="hidden" name="wwp_logo" value="<?php echo esc_attr( $logo ); ?>">
		<p><button type="button" class="button" id="wwp-select-logo"><?php esc_html_e( 'Choose logo', 'wp-web-projects' ); ?></button> <button type="button" class="button" id="wwp-remove-logo"><?php esc_html_e( 'Remove logo', 'wp-web-projects' ); ?></button></p>
		<h3><?php esc_html_e( 'Project gallery', 'wp-web-projects' ); ?></h3>
		<p><?php esc_html_e( 'Add images, then use Move up / Move down to set their order. Descriptions here apply only to this project.', 'wp-web-projects' ); ?></p>
		<div id="wwp-gallery-rows">
		<?php foreach ( self::clean_gallery( $rows ) as $index => $row ) { self::gallery_row( $index, $row ); } ?>
		</div>
		<button type="button" class="button" id="wwp-add-gallery"><?php esc_html_e( 'Add gallery images', 'wp-web-projects' ); ?></button>
		<template id="wwp-gallery-template"><?php self::gallery_row( '__INDEX__', array( 'id' => 0, 'heading' => '', 'description' => '' ) ); ?></template>
		<?php
	}
	private static function gallery_row( $index, $row ) {
		$name = 'wwp_gallery[' . $index . ']'; ?>
		<div class="wwp-gallery-row">
			<div class="wwp-gallery-preview"><?php if ( $row['id'] ) { echo wp_get_attachment_image( $row['id'], 'thumbnail' ); } ?></div>
			<input type="hidden" data-field="id" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $row['id'] ); ?>">
			<p><label><?php esc_html_e( 'Heading', 'wp-web-projects' ); ?><input class="widefat" data-field="heading" name="<?php echo esc_attr( $name ); ?>[heading]" value="<?php echo esc_attr( $row['heading'] ); ?>"></label></p>
			<p><label><?php esc_html_e( 'Description', 'wp-web-projects' ); ?><textarea class="widefat" rows="3" data-field="description" name="<?php echo esc_attr( $name ); ?>[description]" ><?php echo esc_textarea( $row['description'] ); ?></textarea></label></p>
			<p><button type="button" class="button wwp-up"><?php esc_html_e( 'Move up', 'wp-web-projects' ); ?></button> <button type="button" class="button wwp-down"><?php esc_html_e( 'Move down', 'wp-web-projects' ); ?></button> <button type="button" class="button wwp-remove"><?php esc_html_e( 'Remove image', 'wp-web-projects' ); ?></button></p>
		</div>
		<?php
	}
	public static function save( $id ) {
		if ( ! isset( $_POST['wwp_nonce'] ) || ! is_string( $_POST['wwp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wwp_nonce'] ) ), 'wwp_save' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) { return; }
		$url = isset( $_POST['wwp_url'] ) && is_string( $_POST['wwp_url'] ) ? wp_unslash( $_POST['wwp_url'] ) : '';
		update_post_meta( $id, '_wwp_url', self::https_url( $url ) );
		update_post_meta( $id, '_wwp_logo', self::image_id( isset( $_POST['wwp_logo'] ) && is_scalar( $_POST['wwp_logo'] ) ? $_POST['wwp_logo'] : 0 ) );
		update_post_meta( $id, '_wwp_gallery', self::clean_gallery( isset( $_POST['wwp_gallery'] ) ? wp_unslash( $_POST['wwp_gallery'] ) : array() ) );
	}
	public static function admin_assets() {
		$screen = get_current_screen();
		if ( ! $screen || self::TYPE !== $screen->post_type || 'post' !== $screen->base ) { return; }
		wp_enqueue_media(); wp_enqueue_script( 'wwp-admin', plugins_url( 'assets/admin.js', __FILE__ ), array( 'jquery', 'media-views' ), '1.0.12', true );
		wp_enqueue_style( 'wwp-admin', plugins_url( 'assets/admin.css', __FILE__ ), array(), '1.0.12' );
	}
	public static function front_assets() {
		wp_enqueue_style( 'wwp-frontend', plugins_url( 'assets/frontend.css', __FILE__ ), array(), '1.0.12' );
		wp_register_script( 'wwp-frontend', plugins_url( 'assets/frontend.js', __FILE__ ), array(), '1.0.12', true );
		$dependencies = array( 'wwp-frontend' );
		// Use the active theme's existing Canvas bundle; never fetch another copy.
		$swiper_path = get_theme_file_path( 'js/canvas/plugins.swiper.js' );
		$swiper_css = get_theme_file_path( 'css/canvas/swiper.css' );
		if ( is_readable( $swiper_path ) && is_readable( $swiper_css ) ) {
			wp_register_script( 'wwp-theme-swiper', get_theme_file_uri( 'js/canvas/plugins.swiper.js' ), array(), (string) filemtime( $swiper_path ), true );
			wp_register_style( 'wwp-theme-swiper', get_theme_file_uri( 'css/canvas/swiper.css' ), array(), (string) filemtime( $swiper_css ) );
			$dependencies[] = 'wwp-theme-swiper';
		}
		wp_register_script( 'wwp-carousel', plugins_url( 'assets/carousel.js', __FILE__ ), $dependencies, '1.0.12', true );
	}
	private static function project_id( $atts ) {
		$id = ! empty( $atts['id'] ) ? absint( $atts['id'] ) : get_the_ID();
		return self::TYPE === get_post_type( $id ) && ! post_password_required( $id ) && ( 'publish' === get_post_status( $id ) || current_user_can( 'read_post', $id ) ) ? $id : 0;
	}
	public static function logo( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'wwp_project_logo' );
		$id = self::project_id( $atts );
		return $id ? wp_get_attachment_image( self::image_id( get_post_meta( $id, '_wwp_logo', true ) ), 'medium', false, array( 'class' => 'wwp-project-logo img-fluid' ) ) : '';
	}
	public static function link( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0, 'label' => __( 'Visit website', 'wp-web-projects' ) ), $atts, 'wwp_project_link' );
		$id = self::project_id( $atts );
		$url = $id ? self::https_url( get_post_meta( $id, '_wwp_url', true ) ) : '';
		return $url ? '<a class="wwp-project-link btn btn-primary" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $atts['label'] ) . '</a>' : '';
	}
	public static function card_excerpt( $post ) {
		// Read raw project fields: theme excerpt filters may append HTML and use
		// the surrounding LiveCanvas page as their global post context.
		$manual = trim( $post->post_excerpt );
		$text = wp_strip_all_tags( strip_shortcodes( '' !== $manual ? $manual : $post->post_content ), true );
		return '' !== $manual ? $text : wp_trim_words( $text, 55, '…' );
	}
	private static function preview_title( $post, $image_url ) {
		$title = esc_html( get_the_title( $post ) );
		return $image_url ? '<a class="nolightbox" href="' . esc_url( $image_url ) . '" data-wwp-preview>' . $title . '</a>' : $title;
	}
	private static function lightbox() {
		?>
		<dialog class="wwp-lightbox" aria-label="<?php esc_attr_e( 'Project image gallery', 'wp-web-projects' ); ?>">
		<div class="wwp-lightbox-controls"><button type="button" class="btn btn-light" data-wwp-prev><?php esc_html_e( 'Previous image', 'wp-web-projects' ); ?></button><button type="button" class="btn btn-light" data-wwp-next><?php esc_html_e( 'Next image', 'wp-web-projects' ); ?></button><button type="button" class="btn btn-light" data-wwp-close autofocus><?php esc_html_e( 'Close', 'wp-web-projects' ); ?></button></div>
		<figure><img class="wwp-lightbox-image nolightbox" alt=""><figcaption aria-live="polite"><h3 class="h5" data-wwp-heading></h3><p class="wwp-description" data-wwp-description></p><p><a class="wwp-project-link btn btn-outline-secondary" data-wwp-website target="_blank" rel="noopener noreferrer" hidden><?php esc_html_e( 'Visit website', 'wp-web-projects' ); ?></a></p><span data-wwp-position></span></figcaption></figure></dialog>
		<?php
	}
	public static function portfolio( $atts ) {
		return self::render_portfolio( false );
	}
	public static function portfolio_canvas( $atts ) {
		return self::render_portfolio( true );
	}
	private static function render_portfolio( $canvas ) {
		wp_enqueue_script( 'wwp-frontend' );
		$posts = get_posts( array( 'post_type' => self::TYPE, 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) );
		$groups = array( self::CATEGORY => __( 'Categories', 'wp-web-projects' ), self::FEATURE => __( 'Features', 'wp-web-projects' ) );
		$terms = array(); $cards = array();
		foreach ( $posts as $post ) {
			$ids = array();
			foreach ( $groups as $taxonomy => $label ) {
				$assigned = get_the_terms( $post->ID, $taxonomy ); $ids[ $taxonomy ] = array();
				foreach ( is_array( $assigned ) ? $assigned : array() as $term ) {
					$ids[ $taxonomy ][] = $term->term_id;
					$terms[ $taxonomy ][ $term->term_id ] = $term;
				}
			}
			$cards[] = array( 'post' => $post, 'ids' => $ids );
		}
		ob_start(); ?>
		<div class="wwp-portfolio<?php echo $canvas ? ' wwp-portfolio-canvas' : ''; ?>" data-wwp-portfolio data-wwp-gallery>
		<div class="row g-4"><aside class="<?php echo $canvas ? 'col-12' : 'col-lg-3'; ?> wwp-filters" hidden>
		<?php foreach ( $groups as $taxonomy => $label ) :
			$list = $terms[ $taxonomy ] ?? array(); uasort( $list, function( $a, $b ) { return strnatcasecmp( $a->name, $b->name ); } ); ?>
			<fieldset class="mb-4"><legend class="h5"><?php echo esc_html( $label ); ?></legend>
			<?php if ( $canvas ) : ?><div class="wwp-filter-bar"><button type="button" class="wwp-filter-all" data-wwp-all="<?php echo esc_attr( $taxonomy ); ?>" aria-pressed="true"><?php esc_html_e( 'Show All', 'wp-web-projects' ); ?></button><?php endif; ?>
			<?php foreach ( $list as $term ) : ?>
			<label class="wwp-filter-option"><input type="checkbox" class="form-check-input" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" value="<?php echo esc_attr( $term->term_id ); ?>"> <span><?php echo esc_html( $term->name ); ?></span> <span class="wwp-filter-count" data-wwp-count></span></label>
			<?php endforeach; ?><?php if ( $canvas ) : ?></div><?php endif; ?></fieldset>
		<?php endforeach; ?>
		<button type="button" class="btn btn-outline-secondary w-100" data-wwp-reset><?php esc_html_e( 'Reset filters', 'wp-web-projects' ); ?></button></aside>
		<div class="<?php echo $canvas ? 'col-12' : 'col-lg-9'; ?> wwp-results"><p role="status" aria-live="polite" data-wwp-status data-format="<?php esc_attr_e( '%d projects', 'wp-web-projects' ); ?>"></p>
		<div class="row g-4">
		<?php foreach ( $cards as $card ) : $post = $card['post']; ?>
		<div class="col-md-6 <?php echo $canvas ? 'col-xl-3' : 'col-xl-4'; ?> wwp-project" data-wwp-terms="<?php echo esc_attr( wp_json_encode( $card['ids'] ) ); ?>">
		<article class="card h-100">
		<?php $image_url = get_the_post_thumbnail_url( $post, 'full' ); if ( $image_url ) : ?>
		<a class="nolightbox" href="<?php echo esc_url( $image_url ); ?>" data-wwp-image data-heading="<?php echo esc_attr( get_the_title( $post ) ); ?>" data-description="<?php echo esc_attr( self::card_excerpt( $post ) ); ?>" data-website="<?php echo esc_url( self::https_url( get_post_meta( $post->ID, '_wwp_url', true ) ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Preview image: %s', 'wp-web-projects' ), get_the_title( $post ) ) ); ?>"><?php echo get_the_post_thumbnail( $post, 'large', array( 'class' => 'card-img-top wwp-card-image nolightbox' ) ); ?></a>
		<?php endif; ?>
		<div class="card-body d-flex flex-column"><h2 class="h5 card-title"><?php echo self::preview_title( $post, $image_url ); ?></h2><p class="card-text"><?php echo esc_html( self::card_excerpt( $post ) ); ?></p>
		<div class="wwp-card-actions mt-auto d-flex flex-wrap gap-2"><?php echo self::link( array( 'id' => $post->ID ) ); ?></div>
		</div></article></div>
		<?php endforeach; ?></div>
		<p data-wwp-empty <?php echo $posts ? 'hidden' : ''; ?>><?php esc_html_e( 'No projects match these filters.', 'wp-web-projects' ); ?></p>
		</div></div><?php self::lightbox(); ?></div>
		<?php return ob_get_clean();
	}
	public static function portfolio_carousel( $atts ) {
		$atts = shortcode_atts( array( 'limit' => 12, 'columns' => 4 ), $atts, 'wwp_portfolio_carousel' );
		$limit = (int) $atts['limit'];
		$limit = -1 === $limit ? -1 : max( 1, min( 100, $limit ) );
		$columns = max( 2, min( 4, (int) $atts['columns'] ) );
		$posts = get_posts( array( 'post_type' => self::TYPE, 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => $limit, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true ) );
		if ( ! $posts ) { return ''; }
		wp_enqueue_script( 'wwp-carousel' );
		if ( wp_style_is( 'wwp-theme-swiper', 'registered' ) && ! wp_style_is( 'pico-one-c-canvas-swiper', 'enqueued' ) ) { wp_enqueue_style( 'wwp-theme-swiper' ); }
		$track_id = wp_unique_id( 'wwp-carousel-' );
		ob_start(); ?>
		<div class="wwp-carousel-container container"><div role="region" class="wwp-carousel wwp-gallery wwp-carousel-columns-<?php echo esc_attr( $columns ); ?>" data-wwp-carousel data-wwp-columns="<?php echo esc_attr( $columns ); ?>" data-wwp-gallery aria-roledescription="<?php esc_attr_e( 'carousel', 'wp-web-projects' ); ?>" aria-label="<?php esc_attr_e( 'Website portfolio', 'wp-web-projects' ); ?>">
		<div class="wwp-carousel-viewport" data-wwp-viewport tabindex="0" aria-label="<?php esc_attr_e( 'Projects: scroll to browse', 'wp-web-projects' ); ?>"><div class="wwp-carousel-track" id="<?php echo esc_attr( $track_id ); ?>" data-wwp-track>
		<?php foreach ( $posts as $post ) :
			$image_url = get_the_post_thumbnail_url( $post, 'full' ); ?>
		<article class="wwp-project wwp-carousel-card">
		<?php if ( $image_url ) : ?>
		<a class="wwp-carousel-image nolightbox" href="<?php echo esc_url( $image_url ); ?>" data-wwp-image data-heading="<?php echo esc_attr( get_the_title( $post ) ); ?>" data-description="<?php echo esc_attr( self::card_excerpt( $post ) ); ?>" data-website="<?php echo esc_url( self::https_url( get_post_meta( $post->ID, '_wwp_url', true ) ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Preview image: %s', 'wp-web-projects' ), get_the_title( $post ) ) ); ?>"><?php echo get_the_post_thumbnail( $post, 'large', array( 'class' => 'wwp-card-image nolightbox' ) ); ?></a>
		<?php endif; ?>
		<div class="wwp-carousel-body"><h3 class="h5 card-title"><?php echo self::preview_title( $post, $image_url ); ?></h3>
		<p class="wwp-carousel-excerpt"><?php echo esc_html( self::card_excerpt( $post ) ); ?></p></div>
		</article>
		<?php endforeach; ?></div></div>
		<div class="wwp-carousel-controls" data-wwp-carousel-controls hidden>
		<button type="button" class="wwp-carousel-arrow" data-wwp-carousel-prev aria-controls="<?php echo esc_attr( $track_id ); ?>" aria-label="<?php esc_attr_e( 'Previous projects', 'wp-web-projects' ); ?>"><span aria-hidden="true">&#8249;</span></button>
		<button type="button" class="wwp-carousel-arrow" data-wwp-carousel-next aria-controls="<?php echo esc_attr( $track_id ); ?>" aria-label="<?php esc_attr_e( 'Next projects', 'wp-web-projects' ); ?>"><span aria-hidden="true">&#8250;</span></button>
		</div><?php self::lightbox(); ?></div></div>
		<?php return ob_get_clean();
	}
	public static function gallery( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'wwp_gallery' );
		$id = self::project_id( $atts ); if ( ! $id ) { return ''; }
		$rows = self::clean_gallery( get_post_meta( $id, '_wwp_gallery', true ) );
		if ( ! $rows ) { return ''; } wp_enqueue_script( 'wwp-frontend' );
		ob_start(); ?>
		<div class="wwp-gallery row g-4" data-wwp-gallery>
		<?php foreach ( $rows as $row ) : $src = wp_get_attachment_image_url( $row['id'], 'full' ); ?>
		<figure class="col-md-6 col-lg-4"><a class="nolightbox" href="<?php echo esc_url( $src ); ?>" data-wwp-image data-heading="<?php echo esc_attr( $row['heading'] ); ?>" data-description="<?php echo esc_attr( $row['description'] ); ?>"><?php echo wp_get_attachment_image( $row['id'], 'large', false, array( 'class' => 'img-fluid rounded nolightbox' ) ); ?></a><figcaption><?php if ( $row['heading'] ) : ?><h3 class="h5 mt-2"><?php echo esc_html( $row['heading'] ); ?></h3><?php endif; ?><p class="wwp-description"><?php echo esc_html( $row['description'] ); ?></p></figcaption></figure>
		<?php endforeach; ?>
		<?php self::lightbox(); ?></div>
		<?php return ob_get_clean();
	}
}
WWP_Plugin::boot();
register_activation_hook( __FILE__, array( 'WWP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WWP_Plugin', 'deactivate' ) );
