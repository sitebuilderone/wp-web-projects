<?php
/**
 * Plugin Name: WP Web Projects
 * Description: Website portfolio projects, categories, features, filterable Bootstrap cards and image galleries.
 * Version: 1.0.3
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
		add_shortcode( 'wwp_gallery', array( __CLASS__, 'gallery' ) );
		add_shortcode( 'wwp_project_logo', array( __CLASS__, 'logo' ) );
		add_shortcode( 'wwp_project_link', array( __CLASS__, 'link' ) );
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
		wp_enqueue_media(); wp_enqueue_script( 'wwp-admin', plugins_url( 'assets/admin.js', __FILE__ ), array( 'jquery', 'media-views' ), '1.0.3', true );
		wp_enqueue_style( 'wwp-admin', plugins_url( 'assets/admin.css', __FILE__ ), array(), '1.0.3' );
	}
	public static function front_assets() {
		wp_enqueue_style( 'wwp-frontend', plugins_url( 'assets/frontend.css', __FILE__ ), array(), '1.0.3' );
		wp_register_script( 'wwp-frontend', plugins_url( 'assets/frontend.js', __FILE__ ), array(), '1.0.3', true );
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
	private static function lightbox() {
		?>
		<dialog class="wwp-lightbox" aria-label="<?php esc_attr_e( 'Project image gallery', 'wp-web-projects' ); ?>">
		<div class="wwp-lightbox-controls"><button type="button" class="btn btn-light" data-wwp-prev><?php esc_html_e( 'Previous image', 'wp-web-projects' ); ?></button><button type="button" class="btn btn-light" data-wwp-next><?php esc_html_e( 'Next image', 'wp-web-projects' ); ?></button><button type="button" class="btn btn-light" data-wwp-close autofocus><?php esc_html_e( 'Close', 'wp-web-projects' ); ?></button></div>
		<figure><img class="wwp-lightbox-image nolightbox" alt=""><figcaption aria-live="polite"><h3 class="h5" data-wwp-heading></h3><p class="wwp-description" data-wwp-description></p><span data-wwp-position></span></figcaption></figure></dialog>
		<?php
	}
	public static function portfolio( $atts ) {
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
		<div class="wwp-portfolio" data-wwp-portfolio data-wwp-gallery>
		<div class="row g-4"><aside class="col-lg-3 wwp-filters" hidden>
		<?php foreach ( $groups as $taxonomy => $label ) :
			$list = $terms[ $taxonomy ] ?? array(); uasort( $list, function( $a, $b ) { return strnatcasecmp( $a->name, $b->name ); } ); ?>
			<fieldset class="mb-4"><legend class="h5"><?php echo esc_html( $label ); ?></legend>
			<?php foreach ( $list as $term ) : ?>
			<label class="wwp-filter-option"><input type="checkbox" class="form-check-input" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" value="<?php echo esc_attr( $term->term_id ); ?>"> <span><?php echo esc_html( $term->name ); ?></span> <span class="wwp-filter-count" data-wwp-count></span></label>
			<?php endforeach; ?></fieldset>
		<?php endforeach; ?>
		<button type="button" class="btn btn-outline-secondary w-100" data-wwp-reset><?php esc_html_e( 'Reset filters', 'wp-web-projects' ); ?></button></aside>
		<div class="col-lg-9 wwp-results"><p role="status" aria-live="polite" data-wwp-status data-format="<?php esc_attr_e( '%d projects', 'wp-web-projects' ); ?>"></p>
		<div class="row g-4">
		<?php foreach ( $cards as $card ) : $post = $card['post']; ?>
		<div class="col-md-6 col-xl-4 wwp-project" data-wwp-terms="<?php echo esc_attr( wp_json_encode( $card['ids'] ) ); ?>">
		<article class="card h-100">
		<?php $image_url = get_the_post_thumbnail_url( $post, 'full' ); if ( $image_url ) : ?>
		<a class="nolightbox" href="<?php echo esc_url( $image_url ); ?>" data-wwp-image data-heading="<?php echo esc_attr( get_the_title( $post ) ); ?>" data-description="" aria-label="<?php echo esc_attr( sprintf( __( 'Preview image: %s', 'wp-web-projects' ), get_the_title( $post ) ) ); ?>"><?php echo get_the_post_thumbnail( $post, 'large', array( 'class' => 'card-img-top wwp-card-image nolightbox' ) ); ?></a>
		<?php endif; ?>
		<div class="card-body d-flex flex-column"><h2 class="h5 card-title"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h2><p class="card-text"><?php echo esc_html( self::card_excerpt( $post ) ); ?></p>
		<div class="wwp-card-actions mt-auto d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php esc_html_e( 'View project', 'wp-web-projects' ); ?></a><?php echo self::link( array( 'id' => $post->ID ) ); ?></div>
		</div></article></div>
		<?php endforeach; ?></div>
		<p data-wwp-empty <?php echo $posts ? 'hidden' : ''; ?>><?php esc_html_e( 'No projects match these filters.', 'wp-web-projects' ); ?></p>
		</div></div><?php self::lightbox(); ?></div>
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
