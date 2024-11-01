<?php
/**
 * Uninstalling Synchrony\Payments Plugin deletes options and data in database.
 *
 * @package Synchrony\Payments\Uninstall.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$custom_post_id = get_option( 'syf_sync_page' );
if ( $custom_post_id ) {
	// Delete Synchrony Payment Page Permanently.
	wp_delete_post( $custom_post_id, true );
}
// Clear up our settings.
delete_option( 'woocommerce_synchrony-unifi-payments_settings' );
// Delete Synchrony Payment Page ID.
delete_option( 'syf_sync_page' );
$setting_options = array( 'synchrony_deployed_authentication_api_endpoint', 'synchrony_test_authentication_api_endpoint', 'synchrony_deployed_token_api_endpoint', 'synchrony_test_token_api_endpoint', 'synchrony_deployed_unifi_script_endpoint', 'synchrony_test_unifi_script_endpoint', 'synchrony_deployed_transactapi_api_endpoint', 'synchrony_test_transactapi_api_endpoint', 'synchrony_logger_api_endpoint', 'synchrony_test_logger_api_endpoint', 'synchrony_deployed_moduletracking_api_endpoint', 'synchrony_test_moduletracking_api_endpoint', 'synchrony_deployed_findstatus_api_endpoint', 'synchrony_test_findstatus_api_endpoint', 'synchrony_test_promo_tag_endpoint', 'synchrony_deployed_promo_tag_endpoint', 'synchrony_test_promo_tag_determination_endpoint', 'synchrony_deployed_promo_tag_determination_endpoint', 'synchrony_test_banner_mpp_endpoint', 'synchrony_deployed_banner_mpp_endpoint', 'synchrony_test_partner_activate_api_endpoint', 'synchrony_deployed_partner_activate_api_endpoint', 'synchrony_test_smb_domain_api_endpoint', 'synchrony_deployed_smb_domain_api_endpoint', 'synchrony_deployed_client_id_rotation_api_endpoint', 'synchrony_test_client_id_rotation_api_endpoint' );
// Clear up our Synchrony APIs.
foreach ( $setting_options as $setting_name ) {
	delete_option( $setting_name );
}
// Delete all mpp banner post from database.
$all_mpp_banner = get_posts(
	array(
		'post_type'   => 'mpp-banner',
		'numberposts' => -1,
	)
);
if ( $all_mpp_banner ) {
	foreach ( $all_mpp_banner as $banner ) {
		wp_delete_post( $banner->ID, true );
	}
}
global $wpdb;
$table_name = $wpdb->prefix . 'synchrony_partner_auth';
$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
delete_option( 'syf_db_version' );
// delete cache synchrony smb access token from wp options table.
delete_transient( 'synchrony_smb_access_token' );
delete_transient( 'synchrony_access_token' );
