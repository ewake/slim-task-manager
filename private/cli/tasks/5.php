<?php
use RedBeanPHP\R;

//-------------------------------
// http://codex.wordpress.org/Integrating_WordPress_with_Your_Website
// http://wordpress.stackexchange.com/a/5352
// https://dannyvankooten.com/wordpress-plugin-structure-dont-load-unnecessary-code/

//ob_start();
define('DOING_AJAX', true); // For AJAX requests, is_admin() will always return true
define('WP_USE_THEMES', false);
//define( 'SHORTINIT', true );
//require _PUBLIC . '/wp-blog-header.php';
require _PUBLIC . '/wp-load.php';

$conn = $settings['db']['connections']['mysql'];

$db2 = new wpdb($conn['username2'], $conn['password2'], $conn['database2'], $conn['host2']);
$db2->show_errors();

//$db2->show_errors = true;
//$db2->suppress_errors = false;

// php reuses the existing connection when it is only the database that differs in the connection parameters.
// Either use different user accounts for each connection, or just use selectdb on the same connection before the query.
$db2->select($conn['database2']);

//FIXME
date_default_timezone_set($app->settings['timezone']);


//-------------------------------
$TaskHelper->addLog($task->task_id, _('svuotamento tabella match categorie appuntamenti md-pro <-> categorie appuntamenti wordpress'));

R::wipe( 'catevent' ); // no uppercase, _, etc. (ReadBeanPHP4 Conventions) 


//-------------------------------
$rows = $db2->get_results("SELECT * FROM ".$conn['prefix2']."postcalendar_categories ORDER BY pc_catname ASC");

$tot = count($rows);

$TaskHelper->addLog($task->task_id, sprintf(_('selezionate %1$d categorie appuntamenti da importare'), $tot));

foreach ($rows as $row) {
	$TaskHelper->addLog($task->task_id, sprintf(_('importazione categoria appuntamenti %1$s'), $row->pc_catname));
	
	//https://wordpress.org/support/topic/wp_insert_category-working-but-not-working
	$return = wp_insert_term($row->pc_catname, 'cat_event', [
			'description'=>$row->pc_catdesc,
			'slug'=>sanitize_title($row->pc_catname),
			'parent'=>''
	]);
	
	if( is_wp_error( $return ) ) {
		throw new Exception($return->get_error_message());
	} else {
		$catevent = R::dispense( 'catevent' );
		
		$catevent->old_id = $row->pc_catid;
		$catevent->new_id = $return['term_id'];
		
		$bean_id = R::store( $catevent );
	}
}