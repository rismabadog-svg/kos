<?php
/**
 * The base configuration for WordPress
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'i6642964_wp2' );

/** Database username */
define( 'DB_USER', 'database' );

/** Database password */
define( 'DB_PASSWORD', 'Gold@6464data' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'p%3NvE9xQ#mK$2LwR!tYz@8JfGcVbUaM' );
define( 'SECURE_AUTH_KEY',  'rT5yH&7uJkL$4pZxQwN@vCmF#bVgXzDf' );
define( 'LOGGED_IN_KEY',    'sW8mQ$3jF#6nXyL@zRkP!cVbGtYhUaMp' );
define( 'NONCE_KEY',        'tZ9nR@5pL#7qXyM$2wJkF!cVbGtYhUaN' );
define( 'AUTH_SALT',        'uX0mP$4jL#8nQyR@3wZkF!cVbGtYhUaO' );
define( 'SECURE_AUTH_SALT', 'vY1nQ@5kM#9pRzS$4xAlG!cVbGtYhUaP' );
define( 'LOGGED_IN_SALT',   'wZ2oR$6lN#0qSaT@5yBmH!cVbGtYhUaQ' );
define( 'NONCE_SALT',       'x#3pS@7mO$1rTbU&6zCnJ!cVbGtYhUaR' );

/**#@-*/

/**
 * WordPress database table prefix.
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 */
define( 'WP_DEBUG', true );

// Nonaktifkan tampilan error di layar (production)
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

// Tetap log error ke file untuk debugging
define( 'WP_DEBUG_LOG', true );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
