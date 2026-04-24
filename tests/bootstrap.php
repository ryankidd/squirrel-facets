<?php
$_ENV = array_merge(
	$_ENV,
	[
		'WP_PATH' => '/tmp/wordpress-tests',
	]
);

if ( file_exists( realpath( __DIR__ . '/../.env' ) ) ) {
	$_ENV = array_merge( $_ENV, parse_ini_file( realpath( __DIR__ . '/../.env' ) ) );
}

require_once $_ENV['WP_PATH'] . '/wp-load.php';
