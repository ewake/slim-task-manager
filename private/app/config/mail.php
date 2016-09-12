<?php
return [
		'mail' => [
				'return_path' => 'root@host.domain.tld',
				
				'debug_emails_to' => [
						'info@domain.tld' => 'superadmin',
				],
				
				'transport' => 'sendmail', // smtp, mail, sendmail
		
				'smtp_host' => '',
				'smtp_port' => '',
				'smtp_encryption' => '', // ssl, tls
				'smtp_username' => '',
				'smtp_password' => '',
		
				'sendmail_path' => '/usr/sbin/sendmail -bs',
		],
];
