<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'i~JaD7eQ_{eKNEZ4G~T:r`JreQb((<dCGFa Q}G8fyIv0(V&r0vCA3]Po9s%fGK~' );
define( 'SECURE_AUTH_KEY',   'NjmC8)5QmYf;r%gwCn5hlFS1!|,4z4@1R57l+rODp+T[ym#vM2L9W~mNjrWI3uv*' );
define( 'LOGGED_IN_KEY',     '8dh)}I9)nRG.XWmppD*1}2R:,DE@+&?yte2z)q1ENu~16N#1q$^6;Jp9b/@sqS[X' );
define( 'NONCE_KEY',         '^x:K` me2jgi*}g1n_jOFTvRaaoTS[=DNr9.jov5):BiedWAx~1}:sV1k^ |0A2{' );
define( 'AUTH_SALT',         'o-;&6oc<kU)!/*`oGg,yW7D4VsFz7uGObTT22{o#<]/DkBfuOvcd9/;i>Eov[2l(' );
define( 'SECURE_AUTH_SALT',  'FgCQK R3g0x1 rzM5)!TXK/mja&WNrEK_/!IvWU lo&O,.9,&0$A95kjgsZ%f0iy' );
define( 'LOGGED_IN_SALT',    'e FW6jnEC6JR4n!28P->*)D?Lqx%A]|b:5a+0JHi|_oVwUK0OuG~vwHy{D(p_!fa' );
define( 'NONCE_SALT',        '*a .Sc~OA%[xsq{:e RYF?=] N4*$RNQ14!ZU>G6d=*&#H.-$hp [l(,*ZeXF/}x' );
define( 'WP_CACHE_KEY_SALT', '-@yC$BzO}E>hgJUpRG4!(BBkl_^1F,ous|z{^fx G/c(E1,tV(@nJz$45eD%8nuT' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
