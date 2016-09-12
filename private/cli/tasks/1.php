<?php
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use RedBeanPHP\R;

//-------------------------------
$TaskHelper->addLog($task->task_id, _('cancellazione files wordpress')); 

//http://unix.stackexchange.com/questions/167823/find-exec-rm-vs-delete
//http://stackoverflow.com/questions/1881582/whats-the-difference-between-escapeshellarg-and-escapeshellcmd
$process = new Process('find '._PUBLIC.'/* -maxdepth 0 -type f -exec rm -f {} \;');
$process->run();

if (!$process->isSuccessful()) {
	throw new ProcessFailedException($process);
}


//-------------------------------
$TaskHelper->addLog($task->task_id, _('cancellazione cartelle wordpress'));

//http://unix.stackexchange.com/questions/167823/find-exec-rm-vs-delete
$process = new Process('find '._PUBLIC.'/* -maxdepth 0 -path '._PUBLIC.'/stats -prune -o -type d -exec rm -Rf {} \;');
$process->run();

if (!$process->isSuccessful()) {
	throw new ProcessFailedException($process);
}


//-------------------------------
$TaskHelper->addLog($task->task_id, _('importazione database wordpress base'));
		
$process = new Process('mysql --one-database '.escapeshellarg(getenv('DB_NAME')).' < '.escapeshellarg(_ROOT.'/storage/wp_db/'.getenv('DB_NAME').'_MASTER.sql').' -u'.escapeshellarg(getenv('DB_USER')).' -p'.escapeshellarg(getenv('DB_PASS')));
$process->run();

if (!$process->isSuccessful()) {
	throw new ProcessFailedException($process);
}


//-------------------------------
$TaskHelper->addLog($task->task_id, _('installazione sorgenti wordpress base'));
		
$process = new Process('cp -a '._ROOT.'/storage/wp_src/* '._PUBLIC.'/');
$process->run();

if (!$process->isSuccessful()) {
	throw new ProcessFailedException($process);
}


//-------------------------------
//FIXME online non copia l'htaccess
$TaskHelper->addLog($task->task_id, _('copia .htaccess wordpress base'));

$process = new Process('cp -a '._ROOT.'/storage/wp_src/.htaccess '._PUBLIC.'/');
$process->run();

if (!$process->isSuccessful()) {
	throw new ProcessFailedException($process);
}


//-------------------------------
//$TaskHelper->addLog($task->task_id, _('creazione cartella wordpress uploads'));

//$process = new Process('mkdir -p '._PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m'));
//$process->run();
//
//if (!$process->isSuccessful()) {
//	throw new ProcessFailedException($process);
//}

//$fs = new Filesystem();
//$fs->mkdir(_PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m'), 0777); // throw Exception già presente nel metodo


//-------------------------------
//$TaskHelper->addLog($task->task_id, _('cambio owner e group cartella wordpress uploads'));

//FIXME fail
//$fs->chown(_PUBLIC.'/wp-content/uploads', $app->settings['public_unix_owner'], true); // true = recursively
//$fs->chgrp(_PUBLIC.'/wp-content/uploads', $app->settings['public_unix_group'], true); // true = recursively

//$process = new Process('chown -Rf '.$app->settings['public_unix_owner'].':'.$app->settings['public_unix_group'].' '._PUBLIC.'/wp-content/uploads');
//$process->run();
//
//if (!$process->isSuccessful()) {
//	throw new ProcessFailedException($process);
//}


//-------------------------------
if(in_array($app->settings['mode'], ['production'])) {
	$TaskHelper->addLog($task->task_id, _('cancellazione wp-config-*.php di sviluppo'));
	
	$process = new Process('rm -f '.escapeshellarg(_PUBLIC.'/wp-config-*.php'));
	$process->run();
	
	if (!$process->isSuccessful()) {
		throw new ProcessFailedException($process);
	}
}

//-------------------------------
R::exec( 'UPDATE task SET done = 0' );
