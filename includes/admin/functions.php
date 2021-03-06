<?php
/**
 * Common AnsPress admin functions
 *
 * @link https://anspress.io
 * @package AnsPress
 * @author Rahul Aryan <support@anspress.io>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check if AnsPress admin assets need to be loaded.
 *
 * @return boolean
 * @since  3.0.0
 */
function ap_load_admin_assets() {
	$page = get_current_screen();

	

	$load = 'question' === $page->post_type || 'answer' === $page->post_type || strpos( $page->base, 'anspress' ) !== false || 'nav-menus' === $page->base || 'admin_page_ap_select_question' === $page->base || 'admin_page_anspress_update' === $page->base;

	/**
	 * Filter ap_load_admin_assets to load admin assets in custom page.
	 *
	 * @param boolean $load Pass a boolean value if need to load assets.
	 * @return boolean
	 */
	return apply_filters( 'ap_load_admin_assets', $load );
}

/**
 * Update user role.
 *
 * @param  string $role_slug Role slug.
 * @param  array  $caps      Allowed caps array.
 * @return boolean
 */
function ap_update_caps_for_role( $role_slug, $caps = array() ) {
	$role_slug = sanitize_text_field( $role_slug );
	$role      = get_role( $role_slug );

	if ( ! $role || ! is_array( $caps ) ) {
		return false;
	}

	$ap_roles = new AP_Roles();
	$all_caps = $ap_roles->base_caps + $ap_roles->mod_caps;

	foreach ( (array) $all_caps as $cap => $val ) {
		if ( isset( $caps[ $cap ] ) ) {
			$role->add_cap( $cap );
		} else {
			$role->remove_cap( $cap );
		}
	}

	return true;
}

function ap_is_admin_update( $post_type ) {
	if ( is_admin() && isset( $_REQUEST['original_post_status'] ) && $_REQUEST['original_post_status'] == 'publish' && isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == $post_type ) {
		return true;
	}
	return false;
}

function ap_is_admin_publish( $post_type ) {
	if ( is_admin() && isset( $_REQUEST['original_post_status'] ) && $_REQUEST['original_post_status'] == 'auto-draft' && isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == $post_type ) {
		return true;
	}
	return false;
}

function ap_get_untrash_post_link( $post ) {
	$post_type_object = get_post_type_object( $post->post_type );
	$action = 'untrash';
	$link = add_query_arg( 'action', $action, admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) );
	return wp_nonce_url( $link, "$action-post_{$post->ID}" );
}