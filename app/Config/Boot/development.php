<?php

/*
 |--------------------------------------------------------------------------
 | ERROR DISPLAY
 |--------------------------------------------------------------------------
 | In development, we want to show as many errors as possible to help
 | make sure they don't make it to production. And save us hours of
 | painful debugging.
 */
error_reporting(-1);
ini_set('display_errors', '1');

/*
 |--------------------------------------------------------------------------
 | DEBUG BACKTRACES
 |--------------------------------------------------------------------------
 | If true, this constant will tell the error screens to display debug
 | backtraces along with the other error information. If you would
 | prefer to not see this, set this value to false.
 */
defined('SHOW_DEBUG_BACKTRACE') || define('SHOW_DEBUG_BACKTRACE', true);

/*
 |--------------------------------------------------------------------------
 | DEBUG MODE
 |--------------------------------------------------------------------------
 | Debug mode is an experimental flag that can allow for more verbose output
 | during the development process. It can be set in several ways:
 | - Setting CI_DEBUG in your .env file
 | - Setting the environment variable CI_DEBUG
 | - As a special case for now, setting the environment variable CODEIGNITER_SCREAM_DEPRECATIONS
 |
 | If you set CODEIGNITER_SCREAM_DEPRECATIONS it will only affect the deprecated usage warnings.
 */
defined('CI_DEBUG') || define('CI_DEBUG', true);