<?php
// Routes

$app->map(['GET'], '/', 'App\Action\HomeAction:dispatch')->setName('home');

//$app->map(['GET'], '/{lang:[\w]{2}}/{page:[\w]+}', 'App\Action\PageAction:dispatch')->setName('page');

$app->map(['GET', 'POST'], '/{lang:[\w]{2}}/task/{id:[\d]+}', 'App\Action\TaskAction:dispatch')->setName('task');

$app->group('/api/{lang:[\w]{2}}', function () {
	
	$this->get('/task-see', 'App\Action\ApiAction:task_sse')->setName('api-task-sse');
	
	$this->get('/task-reset/{id:[\d]+}', 'App\Action\ApiAction:task_reset')->setName('api-task-reset');
	
	$this->get('/wipe-log/{id:[\d]+}', 'App\Action\ApiAction:wipe_log')->setName('api-wipe-log');
	
	$this->get('/db-nuke/{id:[\d]+}', 'App\Action\ApiAction:db_nuke')->setName('api-db-nuke');
	
});
