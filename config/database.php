<?php

use Illuminate\Support\Str;

return [

  /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

  'default' => env('DB_CONNECTION', 'sqlite'),

  /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

  'connections' => [

    'sqlite' => [
      'driver' => 'sqlite',
      'url' => env('DB_URL'),
      'database' => env('DB_DATABASE', database_path('database.sqlite')),
      'prefix' => '',
      'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
      'busy_timeout' => null,
      'journal_mode' => null,
      'synchronous' => null,
    ],

    //Conexion base de datos GESTION ADMIN
    'mysql-gestion-admin' => [
      'driver' => 'mysql',
      'url' => env('DB_URL'),
      'host' => env('DB_HOST', '127.0.0.1'),
      'port' => env('DB_PORT', '3306'),
      'database' => env('DB_DATABASE', 'laravel'),
      'username' => env('DB_USERNAME', 'root'),
      'password' => env('DB_PASSWORD', ''),
      'unix_socket' => env('DB_SOCKET', ''),
      'charset' => env('DB_CHARSET', 'utf8mb4'),
      'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
      'prefix' => '',
      'prefix_indexes' => true,
      'strict' => true,
      'engine' => null,
      'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
      ]) : [],
    ],

    //Conexion base de datos GESTION HUMANA
    'mysql-gestion-humana' => [
      'driver' => 'mysql',
      'url' => env('DB_URL'),
      'host' => env('DB_HOST_2', '127.0.0.1'),
      'port' => env('DB_PORT_2', '3306'),
      'database' => env('DB_DATABASE_2', 'laravel'),
      'username' => env('DB_USERNAME_2', 'root'),
      'password' => env('DB_PASSWORD_2', ''),
      'unix_socket' => env('DB_SOCKET', ''),
      'charset' => env('DB_CHARSET', 'utf8mb4'),
      'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
      'prefix' => '',
      'prefix_indexes' => true,
      'strict' => true,
      'engine' => null,
      'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
      ]) : [],
    ],


    //Conexion base de datos GESTION PASAJES
    'mysql-gestion-pasajes' => [
      'driver' => 'mysql',
      'url' => env('DB_URL'),
      'host' => env('DB_HOST_3', '127.0.0.1'),
      'port' => env('DB_PORT_3', '3306'),
      'database' => env('DB_DATABASE_3', 'laravel'),
      'username' => env('DB_USERNAME_3', 'root'),
      'password' => env('DB_PASSWORD_3', ''),
      'unix_socket' => env('DB_SOCKET', ''),
      'charset' => env('DB_CHARSET', 'utf8mb4'),
      'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
      'prefix' => '',
      'prefix_indexes' => true,
      'strict' => true,
      'engine' => null,
      'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
      ]) : [],
    ],

    //Conexion base de datos ORACLE logtrans
    'oracle' => [
      'driver' => 'oracle',
      'tns' => '',
      'host' => env('DB_HOST_4', '172.16.50.51'),
      'port' => env('DB_PORT_4', '1521'),
      'database' => env('DB_DATABASE_4', 'PRUEBAS'),
      'username' => env('DB_USERNAME_4', 'LOGTRANSPRO'),
      'password' => env('DB_PASSWORD_4'),
      'charset' => 'AL32UTF8',
      'prefix' => '',
      'prefix_schema' => '',
      'options' => [
        PDO::ATTR_AUTOCOMMIT => false,
      ]
    ],

    //CONEXION SQLSERVER GESTION PASAJES
    'sqlsrv' => [
      'driver' => 'sqlsrv',
      'url' => env('DB_URL_5'),
      'host' => env('DB_HOST_5', '172.16.48.56'),
      'port' => env('DB_PORT_5', '1433'),
      'database' => env('DB_DATABASE_5', 'WF_COPE_TEST'),
      'username' => env('DB_USERNAME_5', 'Gestion_Pasajes'),
      'password' => env('DB_PASSWORD_5'),
      'charset' => env('DB_CHARSET_5', 'utf8'),
      'prefix' => '',
      'prefix_indexes' => true,
      // 'encrypt' => env('DB_ENCRYPT', 'yes'),
      // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
    ],

    //CONEXION SQLSERVER GESTION PASAJES (SOLO LECTURA)
    'sqlsrv-lectura' => [
      'driver' => 'sqlsrv',
      'url' => env('DB_URL_7'),
      'host' => env('DB_HOST_7', '172.16.48.108'),
      'port' => env('DB_PORT_7', '1433'),
      'database' => env('DB_DATABASE_7', 'WF_COPE_TEST'),
      'username' => env('DB_USERNAME_7', 'Gestion_Pasajes'),
      'password' => env('DB_PASSWORD_7'),
      'charset' => env('DB_CHARSET_7', 'utf8'),
      'prefix' => '',
      'prefix_indexes' => true,
      // 'encrypt' => env('DB_ENCRYPT', 'yes'),
      // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
    ],

    //Conexion base de datos ORACLE logtrans
    'oracle-360' => [
      'driver' => 'oracle',
      'tns' => '',
      'host' => env('DB_HOST_6'),
      'port' => env('DB_PORT_6'),
      'database' => env('DB_DATABASE_6'),
      'username' => env('DB_USERNAME_6'),
      'password' => env('DB_PASSWORD_6'),
      'service_name' => env('DB_SERVICE_NAME_6'),
      'charset' => 'AL32UTF8',
      'prefix' => '',
      'prefix_schema' => '',
      'options' => [
        PDO::ATTR_AUTOCOMMIT => false,
      ]
    ],

    //ORACLE-LOGTRANS PRUEBAS  (esta conexion es exclusiva de pruebas)
    'oracle-pruebas' => [
      'driver' => 'oracle',
      'tns' => '',
      'host' => env('DB_HOST_8', '172.16.50.51'),
      'port' => env('DB_PORT_8', '1521'),
      'database' => env('DB_DATABASE_8', 'PRUEBAS'),
      'username' => env('DB_USERNAME_8'),
      'password' => env('DB_PASSWORD_8'),
      'charset' => 'AL32UTF8',
      'prefix' => '',
      'prefix_schema' => '',
      'options' => [
        PDO::ATTR_AUTOCOMMIT => false,
      ]
    ],

    // Conexion base de datos SARLAFT
    'mysql-sarlaft' => [
      'driver' => 'mysql',
      'url' => env('DB_URL_SARLAFT'),
      'host' => env('DB_SARLAFT_HOST', '172.16.48.99'),
      'port' => env('DB_SARLAFT_PORT', env('DB_PORT', '3306')),
      'database' => env('DB_SARLAFT_DATABASE', 'gestion_admin'),
      'username' => env('DB_SARLAFT_USERNAME', env('DB_USERNAME', 'root')),
      'password' => env('DB_SARLAFT_PASSWORD', env('DB_PASSWORD', '')),
      'unix_socket' => env('DB_SARLAFT_SOCKET', env('DB_SOCKET', '')),
      'charset' => env('DB_CHARSET', 'utf8mb4'),
      'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
      'prefix' => '',
      'prefix_indexes' => true,
      'strict' => true,
      'engine' => null,
      'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
      ]) : [],
    ],


    'mariadb' => [
      'driver' => 'mariadb',
      'url' => env('DB_URL'),
      'host' => env('DB_HOST', '127.0.0.1'),
      'port' => env('DB_PORT', '3306'),
      'database' => env('DB_DATABASE', 'laravel'),
      'username' => env('DB_USERNAME', 'root'),
      'password' => env('DB_PASSWORD', ''),
      'unix_socket' => env('DB_SOCKET', ''),
      'charset' => env('DB_CHARSET', 'utf8mb4'),
      'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
      'prefix' => '',
      'prefix_indexes' => true,
      'strict' => true,
      'engine' => null,
      'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
      ]) : [],
    ],

    'pgsql' => [
      'driver' => 'pgsql',
      'url' => env('DB_URL'),
      'host' => env('DB_HOST', '127.0.0.1'),
      'port' => env('DB_PORT', '5432'),
      'database' => env('DB_DATABASE', 'laravel'),
      'username' => env('DB_USERNAME', 'root'),
      'password' => env('DB_PASSWORD', ''),
      'charset' => env('DB_CHARSET', 'utf8'),
      'prefix' => '',
      'prefix_indexes' => true,
      'search_path' => 'public',
      'sslmode' => 'prefer',
    ]

  ],

  /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

  'migrations' => [
    'table' => 'migrations',
    'update_date_on_publish' => true,
  ],

  /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

  'redis' => [

    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
      'cluster' => env('REDIS_CLUSTER', 'redis'),
      'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
    ],

    'default' => [
      'url' => env('REDIS_URL'),
      'host' => env('REDIS_HOST', '127.0.0.1'),
      'username' => env('REDIS_USERNAME'),
      'password' => env('REDIS_PASSWORD'),
      'port' => env('REDIS_PORT', '6379'),
      'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
      'url' => env('REDIS_URL'),
      'host' => env('REDIS_HOST', '127.0.0.1'),
      'username' => env('REDIS_USERNAME'),
      'password' => env('REDIS_PASSWORD'),
      'port' => env('REDIS_PORT', '6379'),
      'database' => env('REDIS_CACHE_DB', '1'),
    ],

  ],

];
