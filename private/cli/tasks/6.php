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
$TaskHelper->addLog($task->task_id, _('svuotamento tabella match appuntamenti md-pro <-> appuntamenti wordpress'));

R::wipe( 'event' ); // no uppercase, _, etc. (ReadBeanPHP4 Conventions)


//-------------------------------
$config = HTMLPurifier_Config::createDefault();
$config->set('AutoFormat.Linkify', true); // Cannot enable Linkify injector because a.href is not allowed
$config->set('AutoFormat.RemoveEmpty', true);
//$config->set('AutoFormat.RemoveSpansWithoutAttributes', true); // Cannot enable RemoveSpansWithoutAttributes injector because span is not allowed
$config->set('Core.Language', 'it');
$config->set('HTML.Allowed', 'a[href|target|title], abbr, acronym, b, blockquote, br, caption, cite, code, dd, del, dfn, dl, dt, em, h3, h4, h5, h6, hr, i, ins, kbd, li, ol, p[align], pre, s, strike, strong, sub, sup, table[width|cellpadding], tbody, td, tfoot, th, thead, tr, tt, u, ul, var');
$purifier_content = new HTMLPurifier($config);

$rows = $db2->get_results("SELECT * FROM ".$conn['prefix2']."postcalendar_events ORDER BY pc_eid ASC");

$r = 1;
$a = 0;

$tot = count($rows);

$TaskHelper->addLog($task->task_id, sprintf(_('selezionati %1$d appuntamenti da importare'), $tot));

foreach ($rows as $row) {
	//if($row->pc_eid < 28500) continue;
	
	$app->logger->addInfo('events_row', ['pc_eid' => $row->pc_eid, 'pc_title' => $row->pc_title]);
	
	if(is_int($r/100))
		$TaskHelper->addLog($task->task_id, sprintf(_('importati %1$d appuntamenti di %2$d'), $r, $tot));
	
	$post_content = $row->pc_hometext;
	
	if(strncasecmp(':text:', $post_content, 6) === 0) {
		$post_content = substr($post_content, 6);
	}

	$post_content = nl2br($post_content);
	
	$post_content = $purifier_content->purify($post_content);
	
	$post = [
			//'ID'             => [ <post id> ] // Are you updating an existing post?
			'post_content'   => $post_content, //[ <string> ] // The full text of the post.
			//'post_name'      => [ <string> ] // The name (slug) for your post
			'post_title'     => $row->pc_title, // The title of your post.
			'post_status'    => 'publish', //[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
			'post_type'      => 'event', //[ 'post' | 'page' | 'link' | 'nav_menu_item' | custom post type ] // Default 'post'.
			'post_author'    => 1, // The user ID number of the author. Default is the current user ID.
			//'ping_status'    => [ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
			//'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
			//'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
			//'to_ping'        => // Space or carriage return-separated list of URLs to ping. Default empty string.
			//'pinged'         => // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
			//'post_password'  => [ <string> ] // Password for post, if any. Default empty string.
			//'guid'           => // Skip this and let Wordpress handle it, usually.
			//'post_content_filtered' => // Skip this and let Wordpress handle it, usually.
			//'post_excerpt'   => [ <string> ] // For all your post excerpt needs.
			'post_date'      => $row->pc_time, //[ Y-m-d H:i:s ] // The time post was made.
			'post_date_gmt'  => $row->pc_time, //[ Y-m-d H:i:s ] // The time post was made, in GMT.
			//'comment_status' => [ 'closed' | 'open' ] // Default is the option 'default_comment_status', or 'closed'.
			//'post_category'  => [ array(<category id>, ...) ], // Default empty.
			//'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
			//'tax_input'      => [ array( <taxonomy> => <array | string>, <taxonomy_other> => <array | string> ) ] // For custom taxonomies. Default empty.
			//'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.
	];
	
	//http://stackoverflow.com/a/17540562
	/*$catevent = R::findOne( 'catevent', ' old_id = :old_id ', ['old_id' => $row->pc_catid] );
	if(isset($catevent->id)) {
		if($catevent->old_id > 0) {
			$term = get_term( $catevent->new_id, 'cat_event' );
			
			$post['tax_input'] = ['cat_event' => $term->name];
		}
	}*/
	
	$posttag = R::findOne( 'posttag', ' old_id = :old_id ', ['old_id' => $row->pc_topic] );
	if(isset($posttag->id)) {
		if($posttag->old_id > 0)
			$post['tags_input'] = [$posttag->name];
	}
	
	$app->logger->addInfo('events_post', $post);
	
	$return = wp_insert_post($post, true);
	
	if( is_wp_error( $return ) ) {
		throw new Exception($return->get_error_message());
	} else {
		//http://wordpress.stackexchange.com/questions/18236/attaching-taxonomy-data-to-post-with-wp-insert-post
		$catevent = R::findOne( 'catevent', ' old_id = :old_id ', ['old_id' => $row->pc_catid] );
		if(isset($catevent->id)) {
			if($catevent->old_id > 0) {	
				$term = get_term( $catevent->new_id, 'cat_event' );
				
				$term_taxonomy_ids = wp_set_object_terms( $return, $term->slug, 'cat_event' );
				
				if ( is_wp_error( $term_taxonomy_ids ) ) {
					throw new Exception($return->get_error_message());
				}
			}
		}

		$event = R::dispense( 'event' );
		
		$event->old_id = $row->pc_eid;
		$event->new_id = $return;
		
		$bean_id = R::store( $event );
	}
	
	$r++;
}