<?php
/**
 * Plugin Name: Kate & Rate Careers
 * Description: Editable job listings with individual role pages. Add [kate_rate_careers] to a Careers page.
 * Version: 0.2.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: Kate & Rate website project
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'KRC_VERSION', '0.2.0' );

function krc_register_roles() {
	register_post_type( 'krc_role', array(
		'labels' => array( 'name' => 'Career roles', 'singular_name' => 'Career role' ),
		'public' => true, 'publicly_queryable' => true, 'exclude_from_search' => false,
		'show_ui' => false, 'show_in_rest' => false, 'has_archive' => false,
		'rewrite' => array( 'slug' => 'careers/role', 'with_front' => false ),
		'supports' => array( 'title', 'editor', 'excerpt' ),
		'capability_type' => 'page', 'map_meta_cap' => true,
	) );
}
add_action( 'init', 'krc_register_roles' );

register_activation_hook( __FILE__, function () { krc_register_roles(); flush_rewrite_rules(); } );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

function krc_defaults() {
	return array(
		'heading' => 'Build meaningful work with us.',
		'intro' => 'Explore open opportunities at Kate & Rate International Limited. Each listing explains the role and how to apply.',
		'email' => 'Kate.rate_international@yahoo.com',
		'page_id' => 0,
		'link_label' => 'View all careers',
	);
}
function krc_settings() {
	$saved = get_option( 'krc_settings', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), krc_defaults() );
}
function krc_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	return array(
		'heading' => isset( $input['heading'] ) ? sanitize_text_field( $input['heading'] ) : '',
		'intro' => isset( $input['intro'] ) ? sanitize_textarea_field( $input['intro'] ) : '',
		'email' => isset( $input['email'] ) ? sanitize_email( $input['email'] ) : '',
		'page_id' => isset( $input['page_id'] ) ? absint( $input['page_id'] ) : 0,
		'link_label' => isset( $input['link_label'] ) ? sanitize_text_field( $input['link_label'] ) : '',
	);
}
add_action( 'admin_init', function () { register_setting( 'krc_settings', 'krc_settings', array( 'type' => 'array', 'sanitize_callback' => 'krc_sanitize_settings' ) ); } );

function krc_careers_url() {
	$page_id = absint( krc_settings()['page_id'] );
	if ( $page_id && 'page' === get_post_type( $page_id ) && 'publish' === get_post_status( $page_id ) ) { return get_permalink( $page_id ); }
	$page = get_page_by_path( 'careers' );
	return $page && 'publish' === $page->post_status ? get_permalink( $page ) : home_url( '/careers/' );
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Careers', 'Careers', 'edit_pages', 'krc-roles', 'krc_admin_list', 'dashicons-businessperson', 26 );
	add_submenu_page( 'krc-roles', 'All roles', 'All roles', 'edit_pages', 'krc-roles', 'krc_admin_list' );
	add_submenu_page( 'krc-roles', 'Add role', 'Add role', 'edit_pages', 'krc-edit', 'krc_admin_edit' );
	add_submenu_page( 'krc-roles', 'Page settings', 'Page settings', 'manage_options', 'krc-settings', 'krc_settings_page' );
} );

function krc_is_open( $role ) {
	if ( ! $role || 'publish' !== $role->post_status ) { return false; }
	$date = (string) get_post_meta( $role->ID, '_krc_closing_date', true );
	return '' === $date || $date >= current_time( 'Y-m-d' );
}

function krc_admin_list() {
	if ( ! current_user_can( 'edit_pages' ) ) { wp_die( 'Permission denied.' ); }
	$roles = get_posts( array( 'post_type' => 'krc_role', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
	?>
	<div class="wrap"><h1 class="wp-heading-inline">Careers</h1> <a href="<?php echo esc_url( admin_url( 'admin.php?page=krc-edit' ) ); ?>" class="page-title-action">Add role</a><p>Create a vacancy, publish it and add its individual page to a menu if needed. A role disappears from the listing after its closing date; close it early using the Close link. Applications are sent directly to the email or external URL you set for the role.</p>
	<table class="widefat striped"><thead><tr><th>Role</th><th>Location</th><th>Status</th><th>Closing date</th><th>Actions</th></tr></thead><tbody>
	<?php foreach ( $roles as $role ) : ?>
	<tr><td><strong><?php echo esc_html( get_the_title( $role ) ); ?></strong></td><td><?php echo esc_html( get_post_meta( $role->ID, '_krc_location', true ) ); ?></td><td><?php echo krc_is_open( $role ) ? 'Open' : ( 'publish' === $role->post_status ? 'Expired' : 'Closed / draft' ); ?></td><td><?php echo esc_html( get_post_meta( $role->ID, '_krc_closing_date', true ) ?: 'Until filled' ); ?></td><td><a href="<?php echo esc_url( admin_url( 'admin.php?page=krc-edit&role_id=' . $role->ID ) ); ?>">Edit</a><?php if ( krc_is_open( $role ) ) : ?> · <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=krc_close&role_id=' . $role->ID ), 'krc_close_' . $role->ID ) ); ?>">Close</a><?php endif; ?><?php if ( 'publish' === $role->post_status ) : ?> · <a href="<?php echo esc_url( get_permalink( $role ) ); ?>" target="_blank" rel="noopener">View</a><?php endif; ?></td></tr>
	<?php endforeach; ?><?php if ( ! $roles ) : ?><tr><td colspan="5">No roles added yet. Use Add role to create your first listing.</td></tr><?php endif; ?></tbody></table>
	</div>
	<?php
}

function krc_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s = krc_settings();
	?>
	<div class="wrap"><h1>Careers page settings</h1><p>Place <code>[kate_rate_careers]</code> on the full Careers page. To add a standalone button on any other page, use <code>[kate_rate_careers_link]</code>.</p><form action="options.php" method="post"><?php settings_fields( 'krc_settings' ); ?><table class="form-table" role="presentation"><tr><th scope="row"><label for="krc-heading">Page heading</label></th><td><input class="large-text" id="krc-heading" name="krc_settings[heading]" value="<?php echo esc_attr( $s['heading'] ); ?>"></td></tr><tr><th scope="row"><label for="krc-intro">Page introduction</label></th><td><textarea class="large-text" rows="3" id="krc-intro" name="krc_settings[intro]"><?php echo esc_textarea( $s['intro'] ); ?></textarea></td></tr><tr><th scope="row"><label for="krc-email">Default application email</label></th><td><input class="regular-text" type="email" id="krc-email" name="krc_settings[email]" value="<?php echo esc_attr( $s['email'] ); ?>"><p class="description">Used when a role has no application URL or dedicated email. Confirm it receives applications before publishing a vacancy.</p></td></tr><tr><th scope="row"><label for="krc-page">Published Careers page</label></th><td><?php wp_dropdown_pages( array( 'name' => 'krc_settings[page_id]', 'id' => 'krc-page', 'selected' => absint( $s['page_id'] ), 'show_option_none' => 'Use /careers/ page', 'option_none_value' => '0', 'post_status' => 'publish' ) ); ?><p class="description">Used by the View all careers shortcode. If none is selected, the published /careers/ page is used.</p></td></tr><tr><th scope="row"><label for="krc-link-label">Button text</label></th><td><input class="regular-text" id="krc-link-label" name="krc_settings[link_label]" value="<?php echo esc_attr( $s['link_label'] ); ?>"><p class="description">Default text for <code>[kate_rate_careers_link]</code>.</p></td></tr></table><?php submit_button(); ?></form></div>
	<?php
}

function krc_admin_edit() {
	if ( ! current_user_can( 'edit_pages' ) ) { wp_die( 'Permission denied.' ); }
	$id = isset( $_GET['role_id'] ) ? absint( $_GET['role_id'] ) : 0;
	$role = $id ? get_post( $id ) : null;
	if ( $id && ( ! $role || 'krc_role' !== $role->post_type || ! current_user_can( 'edit_post', $id ) ) ) { wp_die( 'Role unavailable.' ); }
	$get = static function ( $key ) use ( $id ) { return $id ? (string) get_post_meta( $id, '_krc_' . $key, true ) : ''; };
	$notice = isset( $_GET['krc_notice'] ) ? sanitize_key( wp_unslash( $_GET['krc_notice'] ) ) : '';
	?>
	<div class="wrap krc-admin"><h1><?php echo $id ? 'Edit role' : 'Add role'; ?></h1><?php if ( 'saved' === $notice ) : ?><div class="notice notice-success"><p>Role saved.</p></div><?php endif; ?><p>The details below create the job card and its individual role page. Save an incomplete listing as a draft. Only published roles whose closing date has not passed appear on the Careers page.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="krc_save"><input type="hidden" name="role_id" value="<?php echo esc_attr( $id ); ?>"><?php wp_nonce_field( 'krc_save', 'krc_nonce' ); ?>
	<div class="krc-admin-grid"><div class="krc-admin-box"><h2>Role details</h2>
	<label>Job title *<input required name="krc_title" type="text" maxlength="160" value="<?php echo esc_attr( $role ? $role->post_title : '' ); ?>"></label>
	<label>Card summary *<textarea required name="krc_summary" rows="3" maxlength="500" placeholder="A short, clear description of the opportunity."><?php echo esc_textarea( $role ? $role->post_excerpt : '' ); ?></textarea></label>
	<div class="krc-admin-row"><label>Department<input name="krc_department" type="text" maxlength="100" value="<?php echo esc_attr( $get( 'department' ) ); ?>" placeholder="e.g. Project delivery"></label><label>Location *<input required name="krc_location" type="text" maxlength="120" value="<?php echo esc_attr( $get( 'location' ) ); ?>" placeholder="e.g. Akure, Ondo State"></label></div>
	<div class="krc-admin-row"><label>Employment type <select name="krc_type"><?php foreach ( array( 'Full-time', 'Part-time', 'Contract', 'Internship', 'Temporary' ) as $type ) : ?><option <?php selected( $get( 'type' ) ?: 'Full-time', $type ); ?>><?php echo esc_html( $type ); ?></option><?php endforeach; ?></select></label><label>Work arrangement <select name="krc_arrangement"><?php foreach ( array( 'On-site', 'Hybrid', 'Remote' ) as $arrangement ) : ?><option <?php selected( $get( 'arrangement' ) ?: 'On-site', $arrangement ); ?>><?php echo esc_html( $arrangement ); ?></option><?php endforeach; ?></select></label></div>
	<label>Role overview *<textarea required name="krc_overview" rows="6" placeholder="Explain what this role does and the context of the work."><?php echo esc_textarea( $role ? $role->post_content : '' ); ?></textarea></label>
	<label>Key responsibilities *<textarea required name="krc_responsibilities" rows="6" placeholder="One item per line."><?php echo esc_textarea( $get( 'responsibilities' ) ); ?></textarea></label>
	<label>Requirements *<textarea required name="krc_requirements" rows="6" placeholder="One item per line. Keep requirements relevant to the role."><?php echo esc_textarea( $get( 'requirements' ) ); ?></textarea></label>
	<label>Benefits or other information (optional)<textarea name="krc_benefits" rows="4" placeholder="Only include terms the company has approved."><?php echo esc_textarea( $get( 'benefits' ) ); ?></textarea></label>
	</div><aside class="krc-admin-box"><h2>Applications and publishing</h2><label>Closing date (optional)<input name="krc_closing_date" type="date" value="<?php echo esc_attr( $get( 'closing_date' ) ); ?>"></label><p>Leave empty for “Open until filled.” A past date hides the role from the Careers listing and disables its Apply button.</p>
	<label>How applicants apply <select name="krc_apply_method"><option value="email" <?php selected( $role ? krc_application_method( $role ) : 'email', 'email' ); ?>>Open their email app</option><option value="external" <?php selected( $role ? krc_application_method( $role ) : 'email', 'external' ); ?>>External application website</option><option value="website_form" <?php selected( $role ? krc_application_method( $role ) : 'email', 'website_form' ); ?>>Apply here with a form and CV</option></select></label>
	<label>Application email (for email-app or website-form applications)<input name="krc_apply_email" type="email" value="<?php echo esc_attr( $get( 'apply_email' ) ); ?>"></label><label>External application URL (only for external applications)<input name="krc_apply_url" type="url" value="<?php echo esc_attr( $get( 'apply_url' ) ); ?>" placeholder="https://..."></label><p>The chosen method is used for this role. A blank role email uses the default application email in Page settings. Website-form applications arrive by email with the CV attached; the plugin does not store CVs in the Media Library.</p>
	<label>How to apply (optional)<textarea name="krc_instructions" rows="4" placeholder="e.g. Include the role title in the subject line."><?php echo esc_textarea( $get( 'instructions' ) ); ?></textarea></label>
	<p><button type="submit" class="button" name="krc_status" value="draft">Save draft / close role</button> <?php if ( current_user_can( 'publish_pages' ) ) : ?><button type="submit" class="button button-primary" name="krc_status" value="publish">Publish role</button><?php endif; ?></p>
	<?php if ( $id && 'publish' === $role->post_status ) : ?><p><a href="<?php echo esc_url( get_permalink( $role ) ); ?>" target="_blank" rel="noopener">View role page ↗</a></p><?php endif; ?></aside></div></form></div>
	<style>.krc-admin-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(260px,.8fr);gap:1.2rem;max-width:1120px}.krc-admin-box{background:#fff;border:1px solid #cfc7da;padding:1.3rem}.krc-admin label{display:block;margin:1rem 0;font-weight:650}.krc-admin input:not([type=checkbox]),.krc-admin textarea,.krc-admin select{box-sizing:border-box;display:block;width:100%;max-width:100%;margin-top:.4rem;padding:.55rem;font-weight:400}.krc-admin-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.krc-admin-box p{line-height:1.5;color:#5b5265}@media(max-width:800px){.krc-admin-grid,.krc-admin-row{grid-template-columns:1fr}}</style>
	<?php
}

function krc_field( $key ) {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
}

add_action( 'admin_post_krc_save', function () {
	if ( ! current_user_can( 'edit_pages' ) || ! isset( $_POST['krc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( krc_field( 'krc_nonce' ) ), 'krc_save' ) ) { wp_die( 'Permission check failed.' ); }
	$id = absint( krc_field( 'role_id' ) );
	if ( $id && ( 'krc_role' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) ) { wp_die( 'Role unavailable.' ); }
	$title = sanitize_text_field( krc_field( 'krc_title' ) );
	$summary = sanitize_textarea_field( krc_field( 'krc_summary' ) );
	$overview = sanitize_textarea_field( krc_field( 'krc_overview' ) );
	$location = sanitize_text_field( krc_field( 'krc_location' ) );
	$responsibilities = sanitize_textarea_field( krc_field( 'krc_responsibilities' ) );
	$requirements = sanitize_textarea_field( krc_field( 'krc_requirements' ) );
	$publish = 'publish' === sanitize_key( krc_field( 'krc_status' ) ) && current_user_can( 'publish_pages' );
	if ( '' === $title ) { wp_die( 'Enter a job title.' ); }
	if ( $publish && ( '' === $summary || '' === $overview || '' === $location || '' === $responsibilities || '' === $requirements ) ) { wp_die( 'Complete the summary, overview, location, responsibilities and requirements before publishing.' ); }
	$date = trim( krc_field( 'krc_closing_date' ) );
	if ( '' !== $date ) {
		$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		if ( ! $parsed || $parsed->format( 'Y-m-d' ) !== $date ) { wp_die( 'Enter a valid closing date.' ); }
	}
	$email = sanitize_email( krc_field( 'krc_apply_email' ) );
	$url = esc_url_raw( krc_field( 'krc_apply_url' ), array( 'https', 'http' ) );
	$method = sanitize_key( krc_field( 'krc_apply_method' ) );
	if ( ! in_array( $method, array( 'email', 'external', 'website_form' ), true ) ) { $method = $id ? krc_application_method( get_post( $id ) ) : 'email'; }
	if ( '' !== trim( krc_field( 'krc_apply_url' ) ) && ! filter_var( $url, FILTER_VALIDATE_URL ) ) { wp_die( 'Enter a valid external application URL.' ); }
	if ( '' !== trim( krc_field( 'krc_apply_email' ) ) && ! is_email( $email ) ) { wp_die( 'Enter a valid application email address.' ); }
	if ( $publish && 'external' === $method && ! $url ) { wp_die( 'Enter an external application URL before publishing.' ); }
	if ( $publish && 'external' !== $method && ! is_email( $email ) && ! is_email( krc_settings()['email'] ) ) { wp_die( 'Add an application email before publishing.' ); }
	$data = array( 'post_type' => 'krc_role', 'post_status' => $publish ? 'publish' : 'draft', 'post_title' => $title, 'post_excerpt' => $summary, 'post_content' => $overview );
	if ( $id ) { $data['ID'] = $id; }
	$saved = $id ? wp_update_post( $data, true ) : wp_insert_post( $data, true );
	if ( is_wp_error( $saved ) || ! $saved ) { wp_die( 'The role could not be saved.' ); }
	$meta = array(
		'department' => sanitize_text_field( krc_field( 'krc_department' ) ), 'location' => $location,
		'type' => sanitize_text_field( krc_field( 'krc_type' ) ), 'arrangement' => sanitize_text_field( krc_field( 'krc_arrangement' ) ),
		'responsibilities' => $responsibilities, 'requirements' => $requirements,
		'benefits' => sanitize_textarea_field( krc_field( 'krc_benefits' ) ), 'closing_date' => $date,
		'apply_email' => $email, 'apply_url' => $url, 'apply_method' => $method,
		'instructions' => sanitize_textarea_field( krc_field( 'krc_instructions' ) ),
	);
	foreach ( $meta as $key => $value ) { update_post_meta( $saved, '_krc_' . $key, $value ); }
	wp_safe_redirect( admin_url( 'admin.php?page=krc-edit&role_id=' . $saved . '&krc_notice=saved' ) ); exit;
} );

add_action( 'admin_post_krc_close', function () {
	$id = isset( $_GET['role_id'] ) ? absint( $_GET['role_id'] ) : 0;
	if ( ! $id || 'krc_role' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) { wp_die( 'Role unavailable.' ); }
	check_admin_referer( 'krc_close_' . $id );
	wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
	wp_safe_redirect( admin_url( 'admin.php?page=krc-roles' ) ); exit;
} );

function krc_application_method( $role ) {
	$method = (string) get_post_meta( $role->ID, '_krc_apply_method', true );
	if ( in_array( $method, array( 'email', 'external', 'website_form' ), true ) ) { return $method; }
	return get_post_meta( $role->ID, '_krc_apply_url', true ) ? 'external' : 'email';
}

function krc_application_email( $role ) {
	$email = (string) get_post_meta( $role->ID, '_krc_apply_email', true );
	return is_email( $email ) ? $email : krc_settings()['email'];
}

function krc_apply_link( $role ) {
	$method = krc_application_method( $role );
	if ( 'website_form' === $method ) { return ''; }
	$url = (string) get_post_meta( $role->ID, '_krc_apply_url', true );
	if ( 'external' === $method ) { return $url && in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'https', 'http' ), true ) ? $url : ''; }
	$email = krc_application_email( $role );
	return is_email( $email ) ? 'mailto:' . $email . '?subject=' . rawurlencode( 'Application: ' . get_the_title( $role ) ) : '';
}

function krc_render_list() {
	$s = krc_settings();
	$roles = get_posts( array( 'post_type' => 'krc_role', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
	$roles = array_filter( $roles, 'krc_is_open' );
	ob_start();
	?>
	<div class="krc-careers"><section class="krc-hero"><div class="krc-shell"><p class="krc-eyebrow">Careers at Kate &amp; Rate</p><h1><?php echo esc_html( $s['heading'] ); ?></h1><p><?php echo esc_html( $s['intro'] ); ?></p><a href="#open-roles" class="krc-button krc-light">Explore open roles ↓</a></div></section>
	<section class="krc-listing" id="open-roles"><div class="krc-shell"><p class="krc-eyebrow">Opportunities</p><h2>Open roles</h2><?php if ( $roles ) : ?><div class="krc-grid"><?php foreach ( $roles as $role ) : ?><article class="krc-card"><div class="krc-tags"><span><?php echo esc_html( get_post_meta( $role->ID, '_krc_type', true ) ?: 'Full-time' ); ?></span><span><?php echo esc_html( get_post_meta( $role->ID, '_krc_arrangement', true ) ?: 'On-site' ); ?></span></div><h3><?php echo esc_html( get_the_title( $role ) ); ?></h3><p class="krc-location"><?php echo esc_html( get_post_meta( $role->ID, '_krc_location', true ) ); ?></p><p><?php echo esc_html( $role->post_excerpt ); ?></p><a href="<?php echo esc_url( get_permalink( $role ) ); ?>" class="krc-card-link" aria-label="View <?php echo esc_attr( get_the_title( $role ) ); ?> details">View role and apply ↗</a></article><?php endforeach; ?></div><?php else : ?><div class="krc-empty"><h3>No open positions right now.</h3><p>New opportunities will appear here when the company publishes them. Please check back later.</p></div><?php endif; ?></div></section>
	</div>
	<?php return ob_get_clean();
}
add_shortcode( 'kate_rate_careers', 'krc_render_list' );

function krc_render_all_careers_link( $atts = array() ) {
	$atts = shortcode_atts( array( 'label' => '' ), is_array( $atts ) ? $atts : array(), 'kate_rate_careers_link' );
	$label = sanitize_text_field( $atts['label'] );
	if ( '' === $label ) { $label = krc_settings()['link_label']; }
	if ( '' === $label ) { $label = 'View all careers'; }
	return '<a class="krc-careers-cta" href="' . esc_url( krc_careers_url() ) . '">' . esc_html( $label ) . ' <span aria-hidden="true">↗</span></a>';
}
add_shortcode( 'kate_rate_careers_link', 'krc_render_all_careers_link' );

function krc_render_lines( $text ) {
	$lines = preg_split( '/\r\n|\r|\n/', trim( (string) $text ) );
	$out = '';
	foreach ( $lines as $line ) { if ( '' !== trim( $line ) ) { $out .= '<li>' . esc_html( trim( $line ) ) . '</li>'; } }
	return $out ? '<ul>' . $out . '</ul>' : '';
}

function krc_application_form( $role ) {
	$status = isset( $_GET['krc_application'] ) && is_string( $_GET['krc_application'] ) ? sanitize_key( wp_unslash( $_GET['krc_application'] ) ) : '';
	$messages = array(
		'sent' => 'Your application has been submitted. Thank you for your interest.',
		'invalid' => 'Please check your details and try again.',
		'file' => 'Upload one PDF, DOC or DOCX CV of no more than 5 MB.',
		'failed' => 'Your application could not be sent. Please try again later.',
		'rate' => 'Please wait before submitting another application.',
	);
	ob_start();
	?>
	<div id="apply">
		<?php if ( isset( $messages[ $status ] ) ) : ?><p class="krc-form-notice<?php echo 'sent' === $status ? ' is-success' : ' is-error'; ?>" role="status"><?php echo esc_html( $messages[ $status ] ); ?></p><?php endif; ?>
		<?php if ( 'sent' !== $status ) : ?>
		<form class="krc-application-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="krc_apply_form"><input type="hidden" name="role_id" value="<?php echo esc_attr( $role->ID ); ?>">
			<?php wp_nonce_field( 'krc_apply_' . $role->ID, 'krc_apply_nonce', false ); ?>
			<label>Full name *<input type="text" name="krc_applicant_name" maxlength="120" autocomplete="name" required></label>
			<label>Email address *<input type="email" name="krc_applicant_email" maxlength="190" autocomplete="email" required></label>
			<label>Phone number (optional)<input type="tel" name="krc_applicant_phone" maxlength="40" autocomplete="tel"></label>
			<label>Brief introduction (optional)<textarea name="krc_applicant_message" rows="4" maxlength="2000" placeholder="Tell us why this role interests you."></textarea></label>
			<label>Upload your CV * <span class="krc-file-hint">PDF, DOC or DOCX, up to 5 MB</span><input type="file" name="krc_cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required></label>
			<div class="krc-honeypot" aria-hidden="true"><label>Website<input type="text" name="krc_website" tabindex="-1" autocomplete="off"></label></div>
			<label class="krc-privacy"><input type="checkbox" name="krc_privacy" value="1" required> I understand that my application and CV will be emailed to Kate &amp; Rate for recruitment. I have read the <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a>.</label>
			<button type="submit" class="krc-button">Submit application ↗</button>
		</form>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function krc_render_single( $role ) {
	$open = krc_is_open( $role );
	$method = krc_application_method( $role );
	$apply = $open ? krc_apply_link( $role ) : '';
	$external = 0 === strpos( $apply, 'http' );
	$close = (string) get_post_meta( $role->ID, '_krc_closing_date', true );
	ob_start();
	?>
	<main class="krc-careers krc-detail"><section class="krc-hero"><div class="krc-shell"><a class="krc-back" href="<?php echo esc_url( krc_careers_url() ); ?>">← All careers</a><p class="krc-eyebrow">Career opportunity</p><h1><?php echo esc_html( get_the_title( $role ) ); ?></h1><p><?php echo esc_html( $role->post_excerpt ); ?></p><div class="krc-tags"><span><?php echo esc_html( get_post_meta( $role->ID, '_krc_location', true ) ); ?></span><span><?php echo esc_html( get_post_meta( $role->ID, '_krc_type', true ) ?: 'Full-time' ); ?></span><span><?php echo esc_html( get_post_meta( $role->ID, '_krc_arrangement', true ) ?: 'On-site' ); ?></span></div></div></section>
	<section class="krc-listing"><div class="krc-shell krc-detail-grid"><div><section><h2>About the role</h2><?php echo wpautop( esc_html( $role->post_content ) ); ?></section><section><h2>What you'll do</h2><?php echo krc_render_lines( get_post_meta( $role->ID, '_krc_responsibilities', true ) ); ?></section><section><h2>What we're looking for</h2><?php echo krc_render_lines( get_post_meta( $role->ID, '_krc_requirements', true ) ); ?></section><?php if ( get_post_meta( $role->ID, '_krc_benefits', true ) ) : ?><section><h2>Additional information</h2><?php echo wpautop( esc_html( get_post_meta( $role->ID, '_krc_benefits', true ) ) ); ?></section><?php endif; ?></div>
	<aside class="krc-apply"><h2><?php echo $open ? 'Interested in this role?' : 'Applications closed'; ?></h2>
	<?php if ( $close ) : ?><p><strong>Closing date:</strong> <?php echo esc_html( wp_date( 'j F Y', strtotime( $close . ' 12:00:00' ) ) ); ?></p><?php else : ?><p>Open until filled.</p><?php endif; ?>
	<?php if ( $open ) : ?>
		<p>Review the requirements, then apply using the <?php echo 'website_form' === $method ? 'form below.' : 'link below.'; ?></p>
		<?php if ( get_post_meta( $role->ID, '_krc_instructions', true ) ) : ?><p><?php echo nl2br( esc_html( get_post_meta( $role->ID, '_krc_instructions', true ) ) ); ?></p><?php endif; ?>
		<?php if ( 'website_form' === $method && is_email( krc_application_email( $role ) ) ) : ?><?php echo krc_application_form( $role ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer. ?>
		<?php elseif ( $apply ) : ?><a class="krc-button" href="<?php echo esc_url( $apply ); ?>"<?php echo $external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>Apply for this role ↗</a><p class="krc-privacy-note">Before sending an application, read our <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a><?php echo $external ? ' and the external application site’s privacy notice' : ''; ?>.</p><?php endif; ?>
	<?php else : ?><p>This position is no longer accepting applications. See the Careers page for current opportunities.</p><a class="krc-button" href="<?php echo esc_url( krc_careers_url() ); ?>">See open roles</a><?php endif; ?>
	</aside></div></section></main>
	<?php return ob_get_clean();
}

function krc_application_result( $role, $result ) {
	wp_safe_redirect( add_query_arg( 'krc_application', $result, get_permalink( $role ) ) . '#apply' );
	exit;
}

function krc_valid_cv( $file ) {
	if ( ! is_array( $file ) || ! isset( $file['name'], $file['tmp_name'], $file['size'], $file['error'] ) || ! is_string( $file['name'] ) || ! is_string( $file['tmp_name'] ) || ! is_numeric( $file['size'] ) || UPLOAD_ERR_OK !== $file['error'] ) { return false; }
	if ( $file['size'] < 1 || $file['size'] > 5 * 1024 * 1024 || ! is_uploaded_file( $file['tmp_name'] ) ) { return false; }
	$name = sanitize_file_name( $file['name'] );
	$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'pdf', 'doc', 'docx' ), true ) ) { return false; }
	$handle = fopen( $file['tmp_name'], 'rb' );
	if ( ! $handle ) { return false; }
	$signature = fread( $handle, 8 );
	fclose( $handle );
	if ( 'pdf' === $ext ) { return 0 === strpos( $signature, '%PDF-' ); }
	if ( 'doc' === $ext ) { return 0 === strpos( bin2hex( $signature ), 'd0cf11e0a1b11ae1' ); }
	if ( 0 !== strpos( $signature, 'PK' ) || ! class_exists( 'ZipArchive' ) ) { return false; }
	$archive = new ZipArchive();
	$read_only = defined( 'ZipArchive::RDONLY' ) ? ZipArchive::RDONLY : 0;
	if ( true !== $archive->open( $file['tmp_name'], $read_only ) ) { return false; }
	$valid = false !== $archive->locateName( '[Content_Types].xml' ) && false !== $archive->locateName( 'word/document.xml' ) && false === $archive->locateName( 'word/vbaProject.bin' );
	$archive->close();
	return $valid;
}

function krc_submit_application() {
	$id = absint( krc_field( 'role_id' ) );
	$role = $id ? get_post( $id ) : null;
	if ( ! $role || 'krc_role' !== $role->post_type || ! krc_is_open( $role ) || 'website_form' !== krc_application_method( $role ) ) { wp_die( 'This role is not accepting website applications.', 'Application closed', array( 'response' => 403 ) ); }
	$nonce = sanitize_text_field( krc_field( 'krc_apply_nonce' ) );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'krc_apply_' . $id ) || krc_field( 'krc_website' ) || '1' !== krc_field( 'krc_privacy' ) ) { krc_application_result( $role, 'invalid' ); }
	$name = sanitize_text_field( krc_field( 'krc_applicant_name' ) );
	$email = sanitize_email( krc_field( 'krc_applicant_email' ) );
	$phone = sanitize_text_field( krc_field( 'krc_applicant_phone' ) );
	$message = sanitize_textarea_field( krc_field( 'krc_applicant_message' ) );
	$recipient = krc_application_email( $role );
	if ( '' === $name || strlen( $name ) > 240 || ! is_email( $email ) || strlen( $email ) > 190 || strlen( $phone ) > 40 || strlen( $message ) > 4000 || ! is_email( $recipient ) ) { krc_application_result( $role, 'invalid' ); }
	$file = isset( $_FILES['krc_cv'] ) ? $_FILES['krc_cv'] : null;
	if ( ! krc_valid_cv( $file ) ) { krc_application_result( $role, 'file' ); }
	$key = 'krc_app_' . hash_hmac( 'sha256', strtolower( $email ) . '|' . $id, wp_salt( 'auth' ) );
	if ( get_transient( $key ) ) { krc_application_result( $role, 'rate' ); }
	$subject = 'Job application: ' . get_the_title( $role );
	$body = "New application for: " . get_the_title( $role ) . "\n\nName: " . $name . "\nEmail: " . $email . "\nPhone: " . ( $phone ?: 'Not provided' ) . "\n\nIntroduction:\n" . ( $message ?: 'Not provided' ) . "\n\nCV: " . sanitize_file_name( $file['name'] ) . " (attached)\n";
	$filename = sanitize_file_name( $file['name'] );
	$sent = wp_mail( $recipient, $subject, $body, array( 'Reply-To: ' . $email ), array( $filename => $file['tmp_name'] ) );
	if ( ! $sent ) { krc_application_result( $role, 'failed' ); }
	set_transient( $key, 1, MINUTE_IN_SECONDS );
	krc_application_result( $role, 'sent' );
}
add_action( 'admin_post_nopriv_krc_apply_form', 'krc_submit_application' );
add_action( 'admin_post_krc_apply_form', 'krc_submit_application' );

add_filter( 'template_include', function ( $template ) {
	return is_singular( 'krc_role' ) ? __DIR__ . '/templates/single-role.php' : $template;
} );
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'krc-careers', plugin_dir_url( __FILE__ ) . 'assets/careers.css', array(), KRC_VERSION );
	wp_enqueue_script( 'krc-careers', plugin_dir_url( __FILE__ ) . 'assets/careers.js', array(), KRC_VERSION, true );
} );
