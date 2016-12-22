<?php
/**
 * Class to perform App upgrade procedures on version change
 *
 * @package Framework\Upgrader
 */
abstract class APP_Upgrade_Processor {

	protected $new_version, $old_version;

	public function __construct( $option_key = '' ) {
		if ( $option_key ) {
			add_action( "appthemes_upgrade_$option_key", array( $this, 'init' ), 10, 2 );
		}
	}

	/**
	 * Fires on the App version change and starts upgrade process
	 *
	 * @param string $new_version New App version
	 * @param string $old_version Previuosly installed App version
	 */
	public function init( $new_version, $old_version ) {
		$this->new_version = $new_version;
		$this->old_version = $old_version;
		$this->upgrade();
	}

	/**
	 * Decides which upgrade method to execute depending on current version
	 */
	abstract protected function upgrade();

}