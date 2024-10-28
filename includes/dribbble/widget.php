<?php
class AtticThemes_Dribbble_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'atsf_dribbble', // Base ID
			__('AtticThemes: Dribbble Feed', 'atsf'), // Name
			array( 'description' => __( 'With this widget you can list your latest Dribbble posts.', 'atsf' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', isset($instance['title']) ? $instance['title'] : '' );
		$value = 0;

		echo $args['before_widget'];
		//
		if ( !empty($title) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		/* Add markup below */
		if( class_exists('AtticThemes_Dribbble') && method_exists('AtticThemes_Dribbble', 'shortcode') ) {
			echo AtticThemes_Dribbble::shortcode( array(
					'count' => isset($instance['count']) ? $instance['count'] : 2
				)
			);
		}
		
		/* ---------------- */
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) { ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'atsf' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr(isset($instance[ 'title' ]) ? $instance[ 'title' ] : '' ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of recent posts to show:', 'atsf' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="number" value="<?php echo esc_attr(isset($instance[ 'count' ]) ? $instance[ 'count' ] : '2' ); ?>" size="2" max="20" min="1">
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = !empty($new_instance['title']) ? strip_tags($new_instance['title']) : '';
		$instance['user'] = !empty($new_instance['user']) ? $new_instance['user'] : '';
		$instance['count'] = !empty($new_instance['count']) ? $new_instance['count'] : 2;

		return $instance;
	}
}
?>