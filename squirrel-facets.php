<?php
/**
 * Plugin Name: Squirrel Facets
 * Plugin URI: https://github.com/squirrel/squirrel-facets
 * Version: 1.0.0
 * Requires at least: 6.3
 * Requires PHP: 8.3
 * License: MIT
 * Text Domain: squirrel-facets
 *
 * This file is only used if installed directly as a plugin and not when used as a composer dependency
 *
 * @package Squirrel\Facets
 */

namespace Squirrel\Facets;

$autoload = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>';
			echo esc_html( 'Squirrel Facets: run composer install in wp-content/mu-plugins/squirrel-facets (or ddev squirrel-setup).' );
			echo '</p></div>';
		}
	);

	return;
}

require_once $autoload;

add_action( 'init', __NAMESPACE__ . '\\Facets::instance' );
