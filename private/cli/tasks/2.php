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
$TaskHelper->addLog($task->task_id, _('svuotamento tabella match categorie md-pro <-> categorie wordpress'));

R::wipe( 'category' ); // no uppercase, _, etc. (ReadBeanPHP4 Conventions) 


//-------------------------------
$rows = $db2->get_results("SELECT * FROM ".$conn['prefix2']."stories_cat ORDER BY pn_title ASC");

$tot = count($rows);

$TaskHelper->addLog($task->task_id, sprintf(_('selezionate %1$d categorie da importare'), $tot));

foreach ($rows as $row) {
	$TaskHelper->addLog($task->task_id, sprintf(_('importazione categoria %1$s'), $row->pn_title));
	
	//$return = wp_insert_category([
	//		'cat_name' => $row->pn_title, 
	//		'category_description' => '', 
	//		'category_nicename' => sanitize_title($row->pn_title), 
	//		'category_parent' => ''
	//], true);
	
	//https://wordpress.org/support/topic/wp_insert_category-working-but-not-working
	$return = wp_insert_term($row->pn_title, 'category', [
			'description'=>'',
			'slug'=>sanitize_title($row->pn_title),
			'parent'=>''
	]);
	
	if( is_wp_error( $return ) ) {
		throw new Exception($return->get_error_message());
	} else {
		$category = R::dispense( 'category' );
		
		$category->old_id = $row->pn_catid;
		$category->new_id = $return['term_id'];
		
		$bean_id = R::store( $category );
	}
}