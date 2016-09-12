<?php
return [	
		'db' => [
		
			/*
			 |--------------------------------------------------------------------------
			 | Default Database Connection Name
			 |--------------------------------------------------------------------------
			 |
			 | Here you may specify which of the database connections below you wish
			 | to use as your default connection for all database work. Of course
			 | you may use many connections at once using the Database library.
			 |
			 */
			
			'default' => 'sqlite',
			
			'debug' => false,
			'debug_mode' => 3, // 0 (printed on the screen), 1 (log the queries), 2 (printed on the screen + parameters filled in), 3 (log the queries + parameters filled in)
			
			
			/*
			 |--------------------------------------------------------------------------
			 | Database Connections
			 |--------------------------------------------------------------------------
			 |
			 | Here are each of the database connections setup for your application.
			 | Of course, examples of configuring each database platform that is
			 | supported by Redbean is shown below to make development simple.
			 |
			 |
			 | All database work in Redbean is done through the PHP PDO facilities
			 | so make sure you have the driver for your particular database of
			 | choice installed on your machine before you begin development.
			 |
			 */
	
			'connections' => [
					'sqlite' => [
							'driver'   => 'sqlite',
							'database' => _ROOT . '/storage/db/'.getenv('DB_NAME').'.s3db',
							'prefix'   => '',
					],
					
					'mysql' => [
							'driver'    => 'mysql',
							'host'      => 'localhost',
							'database'  => getenv('DB_NAME'),
							'username'  => getenv('DB_USER'),
							'password'  => getenv('DB_PASS'),
							'charset'   => 'utf8mb4',
							'collation' => 'utf8mb4_unicode_ci',
							'prefix'    => 'app_',
							
							'driver2'    => 'mysql',
							'host2'      => 'localhost',
							'database2'  => getenv('DB2_NAME'),
							'username2'  => getenv('DB2_USER'),
							'password2'  => getenv('DB2_PASS'),
							'charset2'   => 'utf8',
							'collation2' => 'utf8_unicode_ci',
							'prefix2'    => 'md_',
					],
					
					'pgsql' => [
							'driver'   => 'pgsql',
							'host'     => 'localhost',
							'database' => getenv('DB_NAME'),
							'username' => getenv('DB_USER'),
							'password' => getenv('DB_PASS'),
							'charset'  => 'utf8',
							'prefix'   => '',
							'schema'   => 'public',
					],
					
					'sqlsrv' => [
							'driver'   => 'sqlsrv',
							'host'     => 'localhost',
							'database' => getenv('DB_NAME'),
							'username' => getenv('DB_USER'),
							'password' => getenv('DB_PASS'),
							'prefix'   => '',
					],	
			],	
	
			
			/*
			 |--------------------------------------------------------------------------
			 | Redis Databases
			 |--------------------------------------------------------------------------
			 |
			 | Redis is an open source, fast, and advanced key-value store that also
			 | provides a richer set of commands than a typical key-value systems
			 | such as APC or Memcached. Laravel makes it easy to dig right in.
			 |
			 */
			
			'redis' => [
			
					'cluster' => true,
			
					'default' => [
							'host'     => 'localhost',
							'port'     => 6379,
							'database' => 0,
					],
			
			],
		],
];