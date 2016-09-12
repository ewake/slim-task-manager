<?php
return [
		'mail' => [
				'return_path' => 'root@mailgun.org',
					
				'transport' => 'smtp', // smtp, mail, sendmail
		
				'smtp_host' => 'smtp.mailgun.org',
				'smtp_port' => '587',
				'smtp_encryption' => 'tls', // ssl, tls
				'smtp_username' => getenv('SMTP_USERNAME'),
				'smtp_password' => getenv('SMTP_PASSWORD'),
		],
];