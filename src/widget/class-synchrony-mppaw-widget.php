<?php
/**
 * MPP Widget Class File.
 *
 * @package Synchrony\Payments\Widget
 */

namespace Synchrony\Payments\Widget;

defined( 'ABSPATH' ) || exit;

/**
 * Class Synchrony_Mppaw_Widget
 */
class Synchrony_Mppaw_Widget extends \WP_Widget {
	/**
	 * Define Init to add widget data.
	 */
	public function __construct() {
		parent::__construct(
		// Base ID of your widget.
			'mpp_widget',
			// Widget name will appear in UI.
			__( 'MPP Widget', 'mpp_widget' ),
			// Widget description.
			array( 'description' => __( 'MPP Anywhere Banner', 'mpp_widget' ) )
		);
	}

	/**
	 * Creating widget front-end.
	 *
	 * @param array $args This is widget data.
	 * @param array $instance This is form data.
	 *
	 * @return mixed
	 */
	public function widget( $args, $instance ) {

		// Before and after widget arguments are defined by themes.
		echo wp_kses_post( $args['before_widget'] );

		if ( ! empty( $instance['mpp_banner'] ) ) {
			$post = get_post( $instance['mpp_banner'] );
			if ( isset( $post->post_content ) ) {
				echo wp_kses_post( $post->post_content );
			}
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Widget Backend Form.
	 *
	 * @param array $instance This is mpp_banner data.
	 *
	 * @return mixed
	 */
	public function form( $instance ) {
		if ( isset( $instance['mpp_banner'] ) ) {
			$mpp_banner = $instance['mpp_banner'];
		} else {
			$mpp_banner = __( 'MPP Banner', 'mpp_widget' );
		}
		// Widget admin form.
		?>
		<p>
		<label for="<?php echo esc_html( $this->get_field_id( 'mpp_banner' ) ); ?>"><?php esc_html_e( 'MPP Banner:' ); ?></label>
		<select id="<?php echo esc_html( $this->get_field_id( 'mpp_banner' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'mpp_banner' ) ); ?>">
			<?php
				$args = array(
					'post_type'      => 'mpp-banner',
					'posts_per_page' => '-1',
				);
				$loop = new \WP_Query( $args );
				while ( $loop->have_posts() ) {
					$loop->the_post();
					?>
					<option value="<?php echo esc_html( get_the_ID() ); ?>" <?php echo selected( $mpp_banner, get_the_ID(), false ); ?>><?php the_title(); ?></option>
					<?php
				}
				?>
		</select>
		</p>
		<?php
	}

	/**
	 * Updating widget replacing old instances with new.
	 *
	 * @param array $new_instance This is new formdata.
	 * @param mixed $old_instance This is old formdata.
	 *
	 * @return mixed
	 */
	public function update( $new_instance, $old_instance ) {
		$instance               = array();
		$instance['mpp_banner'] = ( ! empty( $new_instance['mpp_banner'] ) ) ? wp_strip_all_tags( $new_instance['mpp_banner'] ) : '';
		return $instance;
	}
	// Class Mppaw_Widget ends here.
}
