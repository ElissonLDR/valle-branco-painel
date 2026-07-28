<?php
/**
 * Orquestra o plugin.
 *
 * @package ValleBrancoPainel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_Painel_Plugin
 */
class VB_Painel_Plugin {

	/**
	 * Liga módulos.
	 */
	public function run() {
		if ( ! is_admin() ) {
			return;
		}

		$admin = new VB_Painel_Admin();
		$admin->hooks();
	}
}
