<?php
/**
 * Tooltips
 *
 * @package Framework\Tooltips
 */

class APP_ToolTips {

	protected $screens;

	protected $selector;

	public function __construct( $pagehook, $args = array() ) {

		$defaults = array(
			'selector'	=> '.at-tip',
		);
		$args = wp_parse_args( $args, $defaults );

		// the screens where the tooltips should be enqueued
		$this->screens = array_flip( (array) $pagehook );

		$this->selector = $args['selector'];

		add_action( 'admin_enqueue_scripts', array( $this, '_enqueue_css_js' ) );
		add_action( 'admin_print_styles', array( $this, '_print_css' ) );
		add_action( 'admin_print_footer_scripts', array( $this, '_print_js' ) );
	}

	function _enqueue_css_js() {
		global $page_hook, $current_screen;

		if ( ! isset( $this->screens[ $page_hook ] ) && ! isset( $current_screen ) ) {
			return;
		}

		// maybe use WP Pointers as tooltips
		if ( self::supports_wp_pointer() ) {
			wp_enqueue_style('wp-pointer');
			wp_enqueue_script('wp-pointer');

			// custom styles for the  WP Pointer - must be added after the main CSS file
			$custom_styles = ""
					. ".app-wp-pointer .wp-pointer-content{ background-color: #444; color: #fff; padding: 0; }"
					. ".app-wp-pointer .wp-pointer-content a{ color: #0492CA } "
					. ".at-tip, .app-wp-pointer .wp-pointer-arrow-inner{ cursor: help; }"
					. ".wp-pointer-left .wp-pointer-arrow-inner { border-right-color: #444; }"
					. ".wp-pointer-right .wp-pointer-arrow-inner { border-left-color: #444; }";

			wp_add_inline_style( 'wp-pointer', $custom_styles );
		}
	}

	function _print_css() {
		global $page_hook, $current_screen;

		if ( ! isset( $this->screens[ $page_hook ] ) && ! isset( $current_screen ) ) {
			return;
		}
?>
		<style type="text/css">
			.wrap h3 { margin-bottom: 0; }
			.wrap .form-table + h3 { margin-top: 2em; }
			.form-table td label { display: block; }
			td.tip { width: 16px; }
			.tip-content { display: none; }
			.tip-show { border: 1px solid #ccc; }
			.at-tip { cursor: pointer; float: right; margin: 0 -24px 0 0; }
			.at-tip:before { content: "\f223"; color: #CFCFCF; }
			.at-tip-hover:before { color: inherit; }
		</style>
<?php
	}

	function _print_js() {
		global $page_hook, $current_screen;

		if ( ! isset( $this->screens[ $page_hook ] ) && ! isset( $current_screen ) ) {
			return;
		}
?>
		<script type="text/javascript">
			jQuery( function($) {

				var hover_class = 'wp-ui-text-highlight at-tip-hover';

				// check that we can use WP Pointer
				if ( <?php echo ( self::supports_wp_pointer() ? '1' : '0' ) ; ?> ) {

					$("<?php echo $this->selector; ?>").on( 'mouseenter mouseleave', function(ev) {

						// dimisss any opened pointer
						dismiss();

						$(this).addClass( hover_class );

						var content = '<p>' + $(this).attr('data-tooltip') + '</p>';

						$(this).pointer({
							pointerClass: 'app-wp-pointer wp-pointer',
							content: content,
							buttons: function( event, t ) {
							},
							position: {
								edge: ( $(window).width() < 782 ? 'right' : 'left' ),
  								align: 'center'
							},
							show: function( e, t ) {

								t.pointer.addClass('mouse-hover-content');

								t.pointer.bind( 'mouseleave', function (e) {
									t.pointer.removeClass('mouse-hover-content')
								});

								// bind a delayed 'mouseleave' event to make sure the tooltip is not hidden when user is moving cursor inside the baloon
								$(this).bind( 'mouseleave', function(event) {

									setTimeout(function () {

										var target = $(event.relatedTarget).closest('.wp-pointer');

										// hide on mouseleave only if user is not hovering the tooltip content
										if ( target.hasClass('mouse-hover-content') ) {
											return;
										}

										// otherwise, dismiss the tooltip
										t.pointer.trigger('mouseleave.pointer');
									}, 1);

								});

								// dismiss pointer on mouseleave
								t.pointer.bind( 'mouseleave.pointer', function(e) {
									e.preventDefault();
									t.element.pointer('close');
									dismiss();
								});

								t.pointer.show();
								t.opened();
							},
						}).pointer('open');

					});

					// dismiss tips on ESC key
					$(document).keyup( function(e) {
					  if (e.keyCode == 27) {
						  dismiss();
					  }
					});

					function dismiss() {
						$('.wp-pointer').hide();
						$('.at-tip').removeClass( hover_class );
					}

				} else {

					// default to older static tips on mobile or older WP versions

					$("<?php echo $this->selector; ?>").on( 'mouseenter mouseleave', function( ev ) {
						$(this).addClass( hover_class );
						$(this).attr( 'title', $(this).attr('data-tooltip') );

						$(this).bind( 'mouseleave', function(event) {
							var tip_opened = $(this).closest('tr').next('.tip-show');

							var icon_tip = $(this);

							setTimeout( function () {

								// hide on mouseleave only if user is not hovering the tooltip content
								if ( ! tip_opened.length ) {
									icon_tip.removeClass( hover_class );
								}

							}, 1);
						});

					});

					$("<?php echo $this->selector; ?>").on( 'click', function( ev ) {
						var $row = $(this).closest('tr');

						var $show = $row.next('.tip-show');

						$(this).addClass( hover_class );

						var icon_tip = $(this);

						if ( $show.length ) {
							$show.bind( 'remove', function() {
								icon_tip.removeClass( hover_class );
							});
							$show.remove();
						} else {
							$show = $('<tr class="tip-show">').html(
								$('<td colspan="3">').html( $row.find('.tip-content').html() )
							);

							$row.after( $show );
						}
					});
				}

			});
		</script>
<?php
	}

	/*
	 * Checks if we can use WP Pointers as tooltips.
	 */
	public static function supports_wp_pointer() {
		global $wp_version;

		return ! wp_is_mobile() && $wp_version >= 3.3;
	}

}
