<?php
/**
 * Tables
 *
 * @package Framework\Tables
 */

/**
 * Utility Class for Creating Tables
 */
abstract class APP_Table {

	protected function table( $items, $attributes = array(), $args = array() ) {

		$args = wp_parse_args( $args, array(
			'wrapper_html' => 'table',
			'header_wrapper' => 'thead',
			'body_wrapper' => 'tbody',
			'footer_wrapper' => 'tfoot',
		) );

		extract( $args );

		$table_body = '';

		$table_body .= html( $header_wrapper, array(), $this->header( $items ) );
		$table_body .= html( $body_wrapper, array(), $this->rows( $items ) );
		$table_body .= html( $footer_wrapper, array(), $this->footer( $items ) );

		return html( $wrapper_html, $attributes, $table_body );

	}

	protected function header( $data ) {}

	protected function footer( $data ) {}

	protected function rows( array $items ) {

		$table_body = '';
		foreach ( $items as $item ) {
			$table_body .= $this->row( $item );
		}

		return $table_body;

	}

	abstract protected function row( $item );

	protected function cells( $cells, $type = 'td' ) {

		$output = '';
		foreach ( $cells as $value ) {
			$output .= html( $type, array(), $value );
		}
		return $output;

	}

}
