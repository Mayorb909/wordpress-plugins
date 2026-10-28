<?php
/** Role detail template using the active site's header and footer. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
$role = get_queried_object();
if ( $role instanceof WP_Post && 'krc_role' === $role->post_type && 'publish' === $role->post_status ) {
	echo krc_render_single( $role ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped in renderer.
}
get_footer();
