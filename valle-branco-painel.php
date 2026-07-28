<?php
/**
 * Plugin Name:       Valle Branco — Painel
 * Plugin URI:        https://vallebranco.com.br
 * Description:       Dashboard simplificado do painel com atalhos para as principais configurações do site.
 * Version:           1.0.10
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Valle Branco
 * Author URI:        https://vallebranco.com.br
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       valle-branco-painel
 *
 * @package ValleBrancoPainel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VB_PAINEL_VERSION', '1.0.10' );
define( 'VB_PAINEL_FILE', __FILE__ );
define( 'VB_PAINEL_PATH', plugin_dir_path( __FILE__ ) );
define( 'VB_PAINEL_URL', plugin_dir_url( __FILE__ ) );
define( 'VB_PAINEL_BASENAME', plugin_basename( __FILE__ ) );

require_once VB_PAINEL_PATH . 'includes/class-vb-painel-admin.php';
require_once VB_PAINEL_PATH . 'includes/class-vb-painel-plugin.php';

/**
 * Boot.
 */
function vb_painel_run() {
	$plugin = new VB_Painel_Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', 'vb_painel_run' );
