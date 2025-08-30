<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to
     * use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     */
    public array $default = [
    'DSN'          => '',
    'hostname'     => 'localhost',
    'username'     => 'postgres',        // your PG username
    'password'     => 'root',            // your PG password
    'database'     => 'city_masterplan', // your DB name
    'DBDriver'     => 'Postgre',
    'DBPrefix'     => '',
    'pConnect'     => false,
    'DBDebug'      => true,             // disable to prevent connection errors
    'charset'      => 'utf8',
    'DBCollat'     => '',
    'swapPre'      => '',
    'encrypt'      => false,
    'compress'     => false,
    'strictOn'     => false,
    'failover'     => [],
    'port'         => 5432,
    'numberNative' => false,
];


    /**
     * This database connection is used when
     * running PHPUnit database tests.
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => '',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
    ];

    public function __construct()
    {
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }

        // Parse DATABASE_URL if available
        if (getenv('DATABASE_URL')) {
            $url = parse_url(getenv('DATABASE_URL'));
            $this->default['hostname'] = $url['host'] ?? 'localhost';
            $this->default['username'] = $url['user'] ?? 'postgres';
            $this->default['password'] = $url['pass'] ?? '';
            $this->default['database'] = ltrim($url['path'] ?? '', '/');
            $this->default['port'] = $url['port'] ?? 5432;
        } else {
            // Override with individual environment variables if available
            if (getenv('PGHOST')) {
                $this->default['hostname'] = getenv('PGHOST');
            }
            if (getenv('PGDATABASE')) {
                $this->default['database'] = getenv('PGDATABASE');
            }
            if (getenv('PGUSER')) {
                $this->default['username'] = getenv('PGUSER');
            }
            if (getenv('PGPASSWORD')) {
                $this->default['password'] = getenv('PGPASSWORD');
            }
            if (getenv('PGPORT')) {
                $this->default['port'] = (int) getenv('PGPORT');
            }
        }
    }
}
