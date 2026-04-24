#!/usr/bin/bash

## use this file to install a wordpress unit testing environment

if [ -f "$1" ]; then
	source $1
fi

if ! type wp > /dev/null ; then
	echo "wp command not found in path"
	exit 1
fi

if ! type mysql > /dev/null; then
	echo "mysql command not found in path"
	exit 1
fi

if ! [ "$DB_USER" ]; then
	DB_USER=root
fi

if ! [ "$DB_PASS" ]; then
	DB_PASS=''
fi

if ! [ "$DB_NAME" ]; then
	DB_NAME='squirrel_facets_tests'
fi

if ! [ "$DB_HOST" ]; then
	DB_HOST='localhost'
fi

if ! [ "$WP_PATH" ]; then
	WP_PATH="/tmp/wordpress-tests"
fi

if ! [ "$WP_TITLE" ]; then
	WP_TITLE="Squirrel Facets Tests"
fi

if ! [ "$WP_USER" ]; then
	WP_USER="squirrel_facets_tests"
fi

if ! [ "$WP_EMAIL" ]; then
	WP_EMAIL="user@example.com"
fi

if ! [ "$WP_URL" ]; then
	WP_URL="http://localhost"
fi

MU_PLUGIN_PATH="$WP_PATH/wp-content/mu-plugins"
MU_PLUGIN_SOURCE=$(pwd)
MU_PLUGIN_TARGET="$MU_PLUGIN_PATH/squirrel-facets"
MU_LOADER_PATH="$MU_PLUGIN_PATH/squirrel-facets.php"

if [ ! -f "$WP_PATH/wp-load.php" ]; then
	wp core download --path="$WP_PATH" --force
fi

mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME" --user="$DB_USER" --password="$DB_PASS"

wp db reset --path="$WP_PATH" --yes

if [ ! -f "$WP_PATH/wp-config.php" ]; then
	wp core config --dbname="$DB_NAME" --dbuser="$DB_USER" --dbhost="$DB_HOST" --dbpass="$DB_PASS" --path="$WP_PATH"
fi

wp core install --url="$WP_URL" --title="$WP_TITLE" --admin_user="$WP_USER" --admin_email="$WP_EMAIL" --path="$WP_PATH"

if [ ! -f "$WP_PATH/wp-content/plugins/wordpress-importer/wordpress-importer.php" ]; then
	wp plugin install wordpress-importer --path="$WP_PATH" --quiet
fi

wp plugin activate wordpress-importer --path="$WP_PATH" --quiet
wp import tests/wptest.xml --authors=create --path="$WP_PATH"

if [ ! -d "$MU_PLUGIN_PATH" ]; then
	mkdir "$MU_PLUGIN_PATH"
fi

if [ ! -f "$MU_LOADER_PATH" ]; then
	echo "<?php" > "$MU_LOADER_PATH"
	echo "require_once 'squirrel-facets/squirrel-facets.php';" >> "$MU_LOADER_PATH"
fi

if [ ! -L "$MU_PLUGIN_TARGET" ]; then
	ln -s "$MU_PLUGIN_SOURCE" "$MU_PLUGIN_TARGET";
fi
