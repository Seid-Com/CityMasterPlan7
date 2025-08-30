<?php

namespace Config;

use CodeIgniter\Events\Events;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create events by simply defining a closure or callable method
 * and registering it with the Events service:
 *
 *      Events::on('eventName', $callable);
 *
 * The $callable can be any valid PHP callable, including closures,
 * that has a signature similar to what you are looking for.
 *
 * Note that custom Events MUST be defined prior to calling the
 * Events::trigger() command, or they will not be found.
 */

/*
 * --------------------------------------------------------------------
 * Pre-system Events
 * --------------------------------------------------------------------
 */

Events::on('pre_system', static function (): void {
    /*
     * The pre_system event is triggered very early in the execution
     * of the program, just before the system folder is loaded.
     * It has no CI super object to work with, and nothing has been
     * instantiated yet so only core resources are available.
     */
});

/*
 * --------------------------------------------------------------------
 * Post Controller Constructor Events
 * --------------------------------------------------------------------
 */

Events::on('post_controller_constructor', static function (): void {
    /*
     * The post_controller_constructor event is triggered immediately
     * after your controller is instantiated, but prior to any method
     * calls happening.
     */
});

/*
 * --------------------------------------------------------------------
 * Database Events
 * --------------------------------------------------------------------
 */

Events::on('DBQuery', static function (\CodeIgniter\Database\Query $query): void {
    // if (ENVIRONMENT === 'development')
    // {
    //     var_dump($query->getQuery());
    // }
});

/*
 * --------------------------------------------------------------------
 * Framework Events
 * --------------------------------------------------------------------
 * 
 * Custom application events can be added here.
 */