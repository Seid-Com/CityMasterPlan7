<?php

/*
 |--------------------------------------------------------------------------
 | ERROR DISPLAY
 |--------------------------------------------------------------------------
 | Don't show ANY in production environments. Instead, let the system catch
 | it and display a generic error message.
 */
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

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
defined('CI_DEBUG') || define('CI_DEBUG', false);