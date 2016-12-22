<?php
/**
 * Form Progress
 *
 * @package Components\Checkouts\Progress-Form
 */

/**
 * Form Progress class
 */
class APP_Form_Progress{

	/**
	 * The checkout type (ID)
	 * @var string
	 */
	protected $checkout_type;

	/**
	 * The checkout current step
	 * @var string
	 */
	protected $current_step;

	/**
	 * The checkout CSS classes
	 * @var string
	 */
	protected $css;

	/**
	 * The checkout walker function that outputs the step tree
	 * @var string
	 */
	protected $walker;

	/**
	 * Setup the form progress step tree
	 * @param array $params Additional parameters:
	 *	- $walker string				The walker function that outputs the progress tree
	 *	- $css['done','todo'] string	The progress CSS classes for 'done' and 'todo' steps
	 */
	public function __construct( $params = array() ) {

		$current_step = _appthemes_fp_get_step_from_query();
		if ( ! $current_step )
			return;

		// look for an active checkout
		if ( ! $checkout_type = $this->get_active_checkout() )
			return;

		// check if the current checkout was registered
		if ( ! APP_Form_Progress_Checkout_Registry::is_registered( $checkout_type ) )
			return;

		$defaults = array(
			'walker' => array( $this, '_display' ),
			'css' => array(
				'done'	=> 'done',
				'todo'	=> 'todo'
			),
		);
		$params = wp_parse_args( $params, $defaults ) ;

		$this->checkout_type = $checkout_type;
		$this->current_step = $current_step;

		$this->walker = $params['walker'];
		$this->css = $params['css'];
	}

	/**
	 * Retrieves an active checkout (dynamic checkout or other)
	 * @param $part (optionl)	The checkout part to retrieve: 'type' (default), 'steps'
	 * @return string|bool		The active checkout name, if any, or False otherwise
	 */
	protected function get_active_checkout( $part = 'type' ) {

		// if theme supports app-checkout
		if ( function_exists( 'appthemes_get_checkout' ) ) {

			$checkout = appthemes_get_checkout();
			if ( ! $checkout )
				return false;

			$curr_checkout_type = $checkout->get_checkout_type();
			$steps = $checkout->get_steps();
			$steps = wp_list_pluck( $steps, 'id' );

			$registered_steps = APP_Form_Progress_Checkout_Registry::steps( $curr_checkout_type );
			if ( empty( $registered_steps )  ) {
				// if the checkout is valid and no steps are registered use the dynamic checkout step tree as the steps defaults
				APP_Form_Progress_Checkout_Registry::register_steps( $curr_checkout_type, $steps );
			}

		} else {

			if ( ! empty( $_REQUEST['app-checkout-type'] ) ) {
				$curr_checkout_type = wp_strip_all_tags( $_REQUEST['app-checkout-type'] );
				$steps = APP_Form_Progress_Checkout_Registry::steps( $curr_checkout_type );
			}

		}

		// no steps found for the current checkout
		if ( empty( $steps ) || empty( $curr_checkout_type )  )
			return false;

		$checkout = array(
			'type'	=> $curr_checkout_type,
			'steps' => $steps
		);

		return $checkout[ $part ];
	}

	/**
	 * Prepares the step tree for output
	 * @param array $steps The steps list
	 * @return array The step list associative array with title and CSS classes
	 */
	protected function format_display( $steps ) {

		$steps_display = array();

		$curr_num = count( $steps );
		$num = 1;
		foreach( $steps as $key => $step ):

			if ( isset( $step['current'] ) )
				$curr_num = $num;

			if ( $num <= $curr_num )
				$class = $this->css['done'];
			else
				$class = $this->css['todo'];

			$steps_display[] = array(
				'title' => $step['title'],
				'class' => $class
			);
			$num++;
		endforeach;

		return $steps_display;
	}

	/**
	 * Retrieves the step tree ready for output
	 * @return array The step tree list with title and CSS classes
	 */
	protected function generate_step_tree() {

		$steps_list = APP_Form_Progress_Checkout_Registry::steps( $this->checkout_type );

		$active_steps = $this->get_active_checkout( $part = 'steps' );

		foreach( $steps_list as $key => $step ) {

			// Checkout only - keep only the steps registered by the Checkout module
			if ( ! isset( $active_steps[ $key ] ) )
				continue;

			if ( isset( $step['map_to'] ) ) {
				$s_key = $step['map_to'];
				$mapped_step = $steps_list[ $step['map_to'] ];
			} else {
				$s_key = $key;
				$mapped_step = $step;
			}

			$steps[ $s_key ] = $mapped_step;

			if ( $key == $this->current_step )
				$current_s_key = $s_key;
		}

		if ( ! empty( $current_s_key ) ) {
			$steps[ $current_s_key ]['current'] = true;
		}

		return $this->format_display( $steps );
	}

	/**
	 * Calls the step walker function
	 * filters: appthemes_form_progress_steps
	 */
	public function display() {
		if ( ! $this->checkout_type )
			return;

		$step_tree = apply_filters( 'appthemes_form_progress_steps', $this->generate_step_tree(), $this );

		call_user_func( $this->walker, $step_tree );
	}

	/**
	 * Outputs the step tree
	 * @param array The steps list
	 */
	protected function _display( $steps ) {
?>
		<div class="progtrckr-wrapper">
			<ol class="progtrckr" data-progtrckr-steps="<?php echo esc_attr( count( $steps ) ); ?>">
<?php
			$num = 1;
			foreach( $steps as $step ):

				$clear_spaces_before = '';
				if ( $num > 1 ) {
					$clear_spaces_before = '-->';
				}

				$clear_spaces_after = '';
				if ( $num >= 1 && $num < count( $steps ) ) {
					$clear_spaces_after = '<!--';
				}
?>
				<?php echo $clear_spaces_before; ?><li class="progtrckr-<?php echo esc_attr( $step['class'] ); ?>"><?php echo $step['title']; ?></li><?php echo $clear_spaces_after; ?>
<?php
				$num++;
			endforeach;
?>
			</ol>
		</div>
<?php
	}

}

function _appthemes_fp_get_step_from_query(){

	if ( ! empty( $_GET['step'] ) )
		$step = $_GET['step'];
	elseif( class_exists('APP_Current_Checkout') && $checkout = APP_Current_Checkout::get_checkout() )
		$step = $checkout->get_next_step();
	else
		$step = '';

	return apply_filters( 'appthemes_form_progress_current_step', $step );
}