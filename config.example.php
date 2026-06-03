<?php
/**
 * Copy this file to config.php and set your values before deploying.
 * config.php is gitignored — never commit live credentials.
 *
 * On shared hosting you can also place config.php one level above public_html:
 *   /home/youruser/nufinds_config.php
 */

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'nufinds_user');
define('DB_PASS', 'change_me_to_a_strong_password');
define('DB_NAME', 'nufindsdb');

/** Use 'production' on a live server; 'development' for local XAMPP. */
define('NUFINDS_ENV', 'development');
