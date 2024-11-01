<?php
/**
 * MPP Block File
 *
 * @package Synchrony\Payments\Blocks
 */

namespace Synchrony\Payments\Blocks;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Widget_Helper;
use Synchrony\Payments\Helper\Synchrony_Setting_Config_Helper;

/**
 * Class Synchrony_Block_Mpp_Widget
 */
class Synchrony_Block_Mpp_Widget {
	/**
	 * Init the Widget by setting up action and filter hooks.
	 *
	 * @return void
	 */
	public function init() {
		$setting_config_helper = new Synchrony_Setting_Config_Helper();
		$widget_helper         = new Synchrony_Widget_Helper();
		$is_plugin_active      = $setting_config_helper->synchrony_plugin_active();
		$enable_mppbanner      = $widget_helper->enable_mppbanner();
		if ( $is_plugin_active && $enable_mppbanner ) {
			add_action( 'enqueue_block_editor_assets', array( $this, 'load_mpp_banner_block' ) );
		}
	}
	/**
	 * Load MPP Banner On Block Area.
	 *
	 * @return void
	 */
	public function load_mpp_banner_block() {

		wp_enqueue_script(
			'mpp-widget-block',
			plugin_dir_url( __FILE__ ) . 'mpp-widget-block.js',
			array( 'react', 'wp-blocks', 'wp-element', 'wp-editor', 'wp-block-editor', 'wp-polyfill' ),
			'1.0.0',
			true
		);

		$mpp_banners  = array();
		$content_list = array();
		$mppargs      = array(
			'post_type'      => 'mpp-banner',
			'posts_per_page' => '-1',
		);

		$mpploop = get_posts( $mppargs );
		foreach ( $mpploop as $post ) {
			$mpp_banners[]             = array(
				'label' => esc_html( $post->post_title ),
				'value' => $post->ID,
			);
			$content_list[ $post->ID ] = wp_kses_post( $post->post_content );
		}
		wp_localize_script(
			'mpp-widget-block',
			'mppData',
			array(
				'mpp_banners'  => $mpp_banners,
				'content_list' => $content_list,
			)
		);
	}
}
