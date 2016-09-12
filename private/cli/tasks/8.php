<?php
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
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

// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
require_once  _PUBLIC . '/wp-admin/includes/image.php';

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
$buffer = '';

$args = array(
		'posts_per_page'   => -1,
		'offset'           => 0,
		'category'         => '',
		'category_name'    => '',
		'orderby'          => 'ID',
		'order'            => 'ASC',
		'include'          => '',
		'exclude'          => '',
		'meta_key'         => '',
		'meta_value'       => '',
		'post_type'        => 'event',
		'post_mime_type'   => '',
		'post_parent'      => '',
		'author'	   => '',
		'post_status'      => 'publish',
		'suppress_filters' => true
);
$posts = get_posts( $args );

$r = 1;

$tot = count($posts);

foreach ( $posts as $post ) {
	if(is_int($r/1000))
		$TaskHelper->addLog($task->task_id, sprintf(_('mappati %1$d appuntamenti di %2$d'), $r, $tot));
	
	setup_postdata($post);

	$event = R::findOne( 'event', ' new_id = :new_id ', ['new_id' => $post->ID] );
	
	$permalink = basename(get_permalink());
	$permalink = str_replace('/', '', $permalink);
	
	$buffer .= $event->old_id.' '.$permalink.PHP_EOL;
	
	$r++;
}
wp_reset_postdata();


//-------------------------------
$TaskHelper->addLog($task->task_id, _('generazione mappa appuntamenti .txt'));

$map_txt_file = dirname(_ROOT).'/rewritemap/'.$app->settings['app_sub_name'].'_event.txt';

file_put_contents($map_txt_file, $buffer);


//-------------------------------
$TaskHelper->addLog($task->task_id, _('conversione mappa appuntamenti httxt2dbm'));

$map_db_file = dirname(_ROOT).'/rewritemap/'.$app->settings['app_sub_name'].'_event.map';

$process = new Process('httxt2dbm -i '.$map_txt_file.' -o '.$map_db_file);
$process->run();

if (!$process->isSuccessful()) {
	throw new ProcessFailedException($process);
}
