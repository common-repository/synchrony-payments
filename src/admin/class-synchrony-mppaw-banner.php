<?php
/**
 * MPP Banner Admin
 *
 * @package Synchrony\Payments\Admin
 */

namespace Synchrony\Payments\Admin;

defined( 'ABSPATH' ) || exit;

use Synchrony\Payments\Helper\Synchrony_Config_Helper;
use Synchrony\Payments\Gateway\Synchrony_Client;

/**
 * Class Synchrony_Mppaw_Banner
 */
class Synchrony_Mppaw_Banner {
	/**
	 * Mpp Banner constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'create_custom_post_type' ) );
		add_action( 'manage_mpp-banner_posts_custom_column', array( $this, 'mppb_banner_list_style' ), 10, 2 );
		add_action( 'admin_head', array( $this, 'mppb_banner_style' ) );
		add_filter( 'post_row_actions', array( $this, 'remove_row_actions_post' ), 10, 2 );
		add_filter( 'manage_mpp-banner_posts_columns', array( $this, 'change_banner_position' ) );
	}

	/**
	 * Create_custom_post_type.
	 */
	public function create_custom_post_type() {
		/*
		* The $labels describes how the post type appears.
		*/
		$labels = array(
			'name'          => 'MPP Banners',
			'singular_name' => 'MPP Banner',
			'add_new_item'  => __( 'Add New MPP Banner' ),
			'edit_item'     => __( 'Edit MPP Banner' ),
			'new_item'      => __( 'New MPP Banner' ),
			'view_item'     => __( 'View MPP Banner' ),
			'search_items'  => __( 'Search MPP Banners' ),
		);

		/*
		* The $supports parameter describes what the post type supports.
		*/
		$supports = array(
			'title',
			'editor',
		);

		/*
		* The $args parameter holds important parameters for the custom post type.
		*/
		$args = array(
			'labels'              => $labels,
			'description'         => 'Post type post mpp banner', // Description.
			'supports'            => $supports,
			'taxonomies'          => array(), // Allowed taxonomies.
			'hierarchical'        => false, // Allows hierarchical categorization, if set to false, the Custom Post Type will behave like Post, else it will behave like Page.
			'public'              => true,  // Makes the post type public.
			'show_ui'             => true,  // Displays an interface for this post type.
			'show_in_menu'        => false,  // Displays in the Admin Menu (the left panel).
			'show_in_nav_menus'   => false,  // Displays in Appearance -> Menus.
			'show_in_admin_bar'   => true,  // Displays in the black admin bar.
			'menu_position'       => 5,     // The position number in the left menu.
			'menu_icon'           => true,  // The URL for the icon used for this post type.
			'can_export'          => true,  // Allows content export using Tools -> Export.
			'has_archive'         => true,  // Enables post type archive (by month, date, or year).
			'exclude_from_search' => false, // Excludes posts of this type in the front-end search result page if set to true, include them if set to false.
			'publicly_queryable'  => true,  // Allows queries to be performed on the front-end part if set to true.
			'capability_type'     => 'post', // Allows read, edit, delete like “Post”.
			'visibility'          => true,
		);

		register_post_type( 'mpp-banner', $args ); // Create a post type with the slug is ‘product’ and arguments in $args.
	}
	/**
	 * Change Banner Image position.
	 *
	 * @param mixed $columns This is columns.
	 *
	 * @return mixed
	 */
	public function change_banner_position( $columns ) {
		return array_merge( array_slice( $columns, 0, 2 ), array( 'post_content' => __( 'Banner Image', 'textdomain' ) ), array_slice( $columns, 2, null ) );
	}
	/**
	 * Add Class on MPP Banner content list.
	 *
	 * @param mixed $column_key This is key.
	 * @param mixed $post_id This is post id.
	 *
	 * @return mixed
	 */
	public function mppb_banner_list_style( $column_key, $post_id ) {
		if ( 'post_content' === $column_key ) {
			$post = get_post( $post_id );
			if ( $post ) {
				echo '<div class="mpp-banner-sec">' . wp_kses_post( $post->post_content ) . '</div>';
			}
		}
	}
	/**
	 * Add Banner Style.
	 *
	 * @return mixed
	 */
	public function mppb_banner_style() {
		?>
			<style>.mpp-banner-sec img { width: auto; height: 50px; }</style>
		<?php
		$current_screen = get_current_screen();
		// Hides the "Move to Trash" link on the post edit page.
		if ( 'post' === $current_screen->base && 'mpp-banner' === $current_screen->post_type ) {
			?>
			<style>#delete-action { display: none; } #visibility{ display: none; } #post-status-select, .edit-post-status{ display: none; }</style>
			<?php
		}
		if ( 'edit' === $current_screen->base && 'edit-mpp-banner' === $current_screen->id ) {
			?>
			<style>.bulkactions{ display: none; }</style>
			<?php
		}
	}
	/**
	 * Remove trash option.
	 *
	 * @param mixed $actions This is action list.
	 * @param mixed $post This is post data.
	 *
	 * @return array
	 */
	public function remove_row_actions_post( $actions, $post ) {
		if ( 'mpp-banner' === $post->post_type ) {
			unset( $actions['trash'] );
		}
		return $actions;
	}
}
