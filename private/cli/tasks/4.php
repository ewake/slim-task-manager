<?php
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
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
$TaskHelper->addLog($task->task_id, _('svuotamento tabella match news md-pro <-> news wordpress'));

R::wipe( 'news' ); // no uppercase, _, etc. (ReadBeanPHP4 Conventions)


//-------------------------------
$fs = new Filesystem();

$config = HTMLPurifier_Config::createDefault();
$config->set('AutoFormat.Linkify', true); // Cannot enable Linkify injector because a.href is not allowed
$config->set('AutoFormat.RemoveEmpty', true);
//$config->set('AutoFormat.RemoveSpansWithoutAttributes', true); // Cannot enable RemoveSpansWithoutAttributes injector because span is not allowed
$config->set('Core.Language', 'it');
$config->set('HTML.Allowed', 'a[href|target|title], abbr, acronym, b, blockquote, br, caption, cite, code, dd, del, dfn, dl, dt, em, h3, h4, h5, h6, hr, i, img[src|alt], ins, kbd, li, ol, p[align], pre, s, strike, strong, sub, sup, table[width|cellpadding], tbody, td, tfoot, th, thead, tr, tt, u, ul, var');
$purifier_content = new HTMLPurifier($config);

$config = HTMLPurifier_Config::createDefault();
$config->set('AutoFormat.Linkify', true); // Cannot enable Linkify injector because a.href is not allowed
$config->set('AutoFormat.RemoveEmpty', true);
//$config->set('AutoFormat.RemoveSpansWithoutAttributes', true); // Cannot enable RemoveSpansWithoutAttributes injector because span is not allowed
$config->set('Core.Language', 'it');
$config->set('HTML.Allowed', 'a[href|target|title], b, br, em, i, img[src|alt], li, ol, strong, sub, sup, u, ul');
$purifier_pre_excerpt = new HTMLPurifier($config);

$config = HTMLPurifier_Config::createDefault();
$config->set('AutoFormat.Linkify', true); // Cannot enable Linkify injector because a.href is not allowed
$config->set('AutoFormat.RemoveEmpty', true);
//$config->set('AutoFormat.RemoveSpansWithoutAttributes', true); // Cannot enable RemoveSpansWithoutAttributes injector because span is not allowed
$config->set('Core.Language', 'it');
$config->set('HTML.Allowed', 'a[href|target|title], b, br, em, i, li, ol, strong, sub, sup, u, ul');
$purifier_excerpt = new HTMLPurifier($config);

$rows = $db2->get_results("SELECT * FROM ".$conn['prefix2']."stories ORDER BY pn_sid ASC");

$r = 1;
$i = 0;
$a = 0;
$ei = 0;
$ea = 0;

$tot = count($rows);

$TaskHelper->addLog($task->task_id, sprintf(_('selezionate %1$d news da importare'), $tot));

foreach ($rows as $row) {
	//if($row->pn_sid < 3200) continue;
	
	$app->logger->addInfo('stories_row', ['pn_sid' => $row->pn_sid, 'pn_title' => $row->pn_title]);
	
	if(is_int($r/50))
		$TaskHelper->addLog($task->task_id, sprintf(_('importate %1$d news di %2$d'), $r, $tot));
	
	$featured_image = false;
	
	$file_pathname_arr = [];
	
	$post_content = $row->pn_bodytext;
	if(!empty(trim($row->pn_notes))) 
		$post_content .= '<br><br><hr>'.$row->pn_notes;
	
	$post_content = $purifier_content->purify($post_content);
	
	if(!empty($post_content)) {
		//http://stackoverflow.com/a/10131137
		//http://stackoverflow.com/a/14107553
		$doc = new DOMDocument();
		$doc->loadHTML($post_content);
		$xpath = new DOMXPath($doc);
		$img_nodelist = $xpath->query('//img[@src]');
		$a_nodelist = $xpath->query('//a[@href]');
		
		if($img_nodelist->length) {
			$i = $i + $img_nodelist->length;
			
			if(is_int($i/50))
				$TaskHelper->addLog($task->task_id, sprintf(_('trovate %1$d immagini su %2$d news'), $i, $r));
			
			for($n=0; $n < $img_nodelist->length; $n++) {
				$filename = false;
				
				$src = $img_nodelist->item($n)->getAttribute('src');
				
				$prefix = ($img_nodelist->length > 1) ? '-'.($n + 1) : '';
				
				if(stristr($src, 'http://') !== false || stristr($src, 'https://') !== false) { //http://stackoverflow.com/a/4192039
					$file_content = @file_get_contents($src);
					
					if($file_content) {
						$info = new SplFileInfo($src);
							
						$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						
						while($fs->exists(_PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename)) {
							$prefix = '-'.(intval($prefix) + 1);
							$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						}
						
						$file_pathname = _PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename;
							
						$app->logger->addInfo('ext_img_copy', ['pn_sid' => $row->pn_sid, 'old_path' => $src, 'new_path' => $file_pathname]);
							
						file_put_contents($file_pathname, $file_content);
					} else {
						$app->logger->addWarning('ext_img_not_found', ['pn_sid' => $row->pn_sid, 'pn_title' => $TaskHelper::truncate($row->pn_title, 80), 'src' => $src]);
							
						$TaskHelper->addLog($task->task_id, sprintf('warning|non esiste immagine esterna news %1$s', $TaskHelper::truncate($row->pn_title, 80)));
					}
				} else {	
					$old_file_pathname = (substr($src, 0, 1) == '/') ? $app->settings['public2_path'].$src : $app->settings['public2_path'].'/'.$src;
					
					$old_file_pathname = str_replace(['\\', '%5C'], '', $old_file_pathname);
					$old_file_pathname = stripslashes($old_file_pathname);
					$old_file_pathname = rawurldecode($old_file_pathname);
					
					if($fs->exists($old_file_pathname) && !is_dir($old_file_pathname)) {
						$info = new SplFileInfo($old_file_pathname);
							
						$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						
						while($fs->exists(_PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename)) {
							$prefix = '-'.(intval($prefix) + 1);
							$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						}
						
						$file_pathname = _PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename;
							
						$app->logger->addInfo('img_copy', ['pn_sid' => $row->pn_sid, 'old_path' => $old_file_pathname, 'new_path' => $file_pathname, 'src' => $src]);
							
						$fs->copy($old_file_pathname, $file_pathname);
					} else {
						$app->logger->addWarning('img_not_found', ['pn_sid' => $row->pn_sid, 'pn_title' => $TaskHelper::truncate($row->pn_title, 80), 'src' => $src]);
						
						$TaskHelper->addLog($task->task_id, sprintf('warning|non esiste immagine interna news %1$s', $TaskHelper::truncate($row->pn_title, 80)));
					}
				}
	
				if($filename) {
					$post_content = str_ireplace(
							$src, 
							$app->settings['dev_url'].'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename, 
							$post_content
					);
					
					$post_content = str_ireplace(
							'<img',
							'<img class="size-medium alignleft"',
							$post_content
					);
					
					$file_pathname_arr[] = $file_pathname;
				}
			}
		}
		
		if($a_nodelist->length) {
			$a = $a + $a_nodelist->length;
			
			if(is_int($a/50))
				$TaskHelper->addLog($task->task_id, sprintf(_('trovati %1$d link su %2$d news'), $a, $r));
			
			for($n=0; $n < $a_nodelist->length; $n++) {
				$filename = false;
				$local_file = false;
					
				$href = $a_nodelist->item($n)->getAttribute('href');
					
				$prefix = ($a_nodelist->length > 1) ? '-'.($n + 1) : '';
				
				//http://stackoverflow.com/a/4192039
				//https://josephscott.org/archives/2010/03/php-tip-spaces-are-not-empty/
				if(empty($href) || stristr($href, '.php') !== false || stristr($href, '.htm') !== false)
					$local_file = false;
				elseif(strcasecmp($app->settings['base_url'], $href) === 0 || strcasecmp(str_replace('www.', '', $app->settings['base_url']), $href) === 0 || strcasecmp(str_replace('http://', '', $app->settings['base_url']), $href) === 0)
					$local_file = false;
				elseif(stristr($href, $app->settings['base_url']) !== false || stristr($href, str_replace('www.', '', $app->settings['base_url'])) !== false)
					$local_file = true;
				elseif(stristr($href, 'http://') !== false || stristr($href, 'https://') !== false || strncasecmp('www.', $href, 4) === 0)
					$local_file = false;
				elseif(stristr($href, '/') !== false)
					$local_file = true;
				
				if($local_file) {
					$basename_href = str_replace([$app->settings['base_url'], str_replace('www.', '', $app->settings['base_url'])], '', $href);
					
					$old_file_pathname = (substr($basename_href, 0, 1) == '/') ? $app->settings['public2_path'].$basename_href : $app->settings['public2_path'].'/'.$basename_href;
				
					$old_file_pathname = str_replace(['\\', '%5C'], '', $old_file_pathname);
					$old_file_pathname = stripslashes($old_file_pathname);
					$old_file_pathname = rawurldecode($old_file_pathname);
				
					if($fs->exists($old_file_pathname) && !is_dir($old_file_pathname)) {
						$info = new SplFileInfo($old_file_pathname);
				
						$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
	
						while($fs->exists(_PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename)) {
							$prefix = '-'.(intval($prefix) + 1);
							$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						}
							
						$file_pathname = _PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename;
				
						$app->logger->addInfo('file_copy', ['pn_sid' => $row->pn_sid, 'old_path' => $old_file_pathname, 'new_path' => $file_pathname, 'href' => $href]);
				
						$fs->copy($old_file_pathname, $file_pathname);
					} else {
						$app->logger->addWarning('file_not_found', ['pn_sid' => $row->pn_sid, 'pn_title' => $TaskHelper::truncate($row->pn_title, 80), 'href' => $href]);
							
						$TaskHelper->addLog($task->task_id, sprintf('warning|non esiste file news %1$s', $TaskHelper::truncate($row->pn_title, 80)));
					}
				}
				
				if($filename) {
					$post_content = str_ireplace(
							$href,
							$app->settings['dev_url'].'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename,
							$post_content
					);
				
					$file_pathname_arr[] = $file_pathname;
				}
			}
		}
	} else {
		$app->logger->addWarning('post_content_empty', ['pn_sid' => $row->pn_sid, 'pn_title' => $TaskHelper::truncate($row->pn_title, 80)]);
			
		$TaskHelper->addLog($task->task_id, sprintf('warning|non esiste testo news %1$s', $TaskHelper::truncate($row->pn_title, 80)));
	}
	
	$post_pre_excerpt = $purifier_pre_excerpt->purify($row->pn_hometext);
	$post_excerpt = $purifier_excerpt->purify($row->pn_hometext);
	
	if(!empty($post_pre_excerpt)) {
		$doc = new DOMDocument();
		$doc->loadHTML($post_pre_excerpt);
		$xpath = new DOMXPath($doc);
		$img_nodelist = $xpath->query('//img[@src]');
		$a_nodelist = $xpath->query('//a[@href]');
		
		if($img_nodelist->length) {
			$ei = $ei + $img_nodelist->length;
			
			if(is_int($ei/50))
				$TaskHelper->addLog($task->task_id, sprintf(_('trovate %1$d immagini excerpt su %2$d news'), $ei, $r));
			
			for($n=0; $n < $img_nodelist->length; $n++) {
				$filename = false;
				
				$src = $img_nodelist->item($n)->getAttribute('src');
				
				$prefix = ($img_nodelist->length > 1) ? '-'.($n + 1) : '';
				
				if(stristr($src, 'http://') !== false || stristr($src, 'https://') !== false) { //http://stackoverflow.com/a/4192039
					$file_content = @file_get_contents($src);
					
					if($file_content) {
						$info = new SplFileInfo($src);
							
						$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						
						while($fs->exists(_PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename)) {
							$prefix = '-'.(intval($prefix) + 1);
							$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						}
						
						$file_pathname = _PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename;
							
						$app->logger->addInfo('ext_img_excerpt_copy', ['pn_sid' => $row->pn_sid, 'old_path' => $src, 'new_path' => $file_pathname]);
							
						file_put_contents($file_pathname, $file_content);
					} else {
						$app->logger->addWarning('ext_img_excerpt_not_found', ['pn_sid' => $row->pn_sid, 'pn_title' => $TaskHelper::truncate($row->pn_title, 80), 'src' => $src]);
							
						$TaskHelper->addLog($task->task_id, sprintf('warning|non esiste immagine excerpt esterna news %1$s', $TaskHelper::truncate($row->pn_title, 80)));
					}
				} else {	
					$old_file_pathname = (substr($src, 0, 1) == '/') ? $app->settings['public2_path'].$src : $app->settings['public2_path'].'/'.$src;
					
					$old_file_pathname = str_replace(['\\', '%5C'], '', $old_file_pathname);
					$old_file_pathname = stripslashes($old_file_pathname);
					$old_file_pathname = rawurldecode($old_file_pathname);
					
					if($fs->exists($old_file_pathname) && !is_dir($old_file_pathname)) {
						$info = new SplFileInfo($old_file_pathname);
							
						$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						
						while($fs->exists(_PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename)) {
							$prefix = '-'.(intval($prefix) + 1);
							$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						}
						
						$file_pathname = _PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename;
							
						$app->logger->addInfo('img_excerpt_copy', ['pn_sid' => $row->pn_sid, 'old_path' => $old_file_pathname, 'new_path' => $file_pathname, 'src' => $src]);
							
						$fs->copy($old_file_pathname, $file_pathname);
					} else {
						$app->logger->addWarning('img_excerpt_not_found', ['pn_sid' => $row->pn_sid, 'pn_title' => $TaskHelper::truncate($row->pn_title, 80), 'src' => $src]);
						
						$TaskHelper->addLog($task->task_id, sprintf('warning|non esiste immagine excerpt interna news %1$s', $TaskHelper::truncate($row->pn_title, 80)));
					}
				}
	
				if($filename) {
					$featured_image = false;
					
					$file_pathname_arr[] = $file_pathname;
				}
			}
		}
		
		if($a_nodelist->length) {
			$ea = $ea + $a_nodelist->length;
			
			if(is_int($ea/50))
				$TaskHelper->addLog($task->task_id, sprintf(_('trovati %1$d link excerpt su %2$d news'), $ea, $r));
			
			for($n=0; $n < $a_nodelist->length; $n++) {
				$filename = false;
				$local_file = false;
					
				$href = $a_nodelist->item($n)->getAttribute('href');
					
				$prefix = ($a_nodelist->length > 1) ? '-'.($n + 1) : '';
				
				//http://stackoverflow.com/a/4192039
				//https://josephscott.org/archives/2010/03/php-tip-spaces-are-not-empty/
				if(empty($href) || stristr($href, '.php') !== false || stristr($href, '.htm') !== false || stristr($href, '.html') !== false)
					$local_file = false;
				elseif(strcasecmp($app->settings['base_url'], $href) === 0 || strcasecmp(str_replace('www.', '', $app->settings['base_url']), $href) === 0 || strcasecmp(str_replace('http://', '', $app->settings['base_url']), $href) === 0)
					$local_file = false;
				elseif(stristr($href, $app->settings['base_url']) !== false || stristr($href, str_replace('www.', '', $app->settings['base_url'])) !== false)
					$local_file = true;
				elseif(stristr($href, 'http://') !== false || stristr($href, 'https://') !== false || strncasecmp('www.', $href, 4) === 0)
					$local_file = false;
				elseif(stristr($href, '/') !== false)
					$local_file = true;
				
				if($local_file) {
					$basename_href = str_replace([$app->settings['base_url'], str_replace('www.', '', $app->settings['base_url'])], '', $href);
					
					$old_file_pathname = (substr($basename_href, 0, 1) == '/') ? $app->settings['public2_path'].$basename_href : $app->settings['public2_path'].'/'.$basename_href;
				
					$old_file_pathname = str_replace(['\\', '%5C'], '', $old_file_pathname);
					$old_file_pathname = stripslashes($old_file_pathname);
					$old_file_pathname = rawurldecode($old_file_pathname);
				
					if($fs->exists($old_file_pathname) && !is_dir($old_file_pathname)) {
						$info = new SplFileInfo($old_file_pathname);
				
						$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
	
						while($fs->exists(_PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename)) {
							$prefix = '-'.(intval($prefix) + 1);
							$filename = $row->pn_sid.$prefix.'-'.sanitize_title($row->pn_title).'.'.mb_strtolower($info->getExtension(), 'utf-8');
						}
							
						$file_pathname = _PUBLIC.'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename;
				
						$app->logger->addInfo('file_excerpt_copy', ['pn_sid' => $row->pn_sid, 'old_path' => $old_file_pathname, 'new_path' => $file_pathname, 'href' => $href]);
				
						$fs->copy($old_file_pathname, $file_pathname);
					} else {
						$app->logger->addWarning('file_excerpt_not_found', ['pn_sid' => $row->pn_sid, 'pn_title' => $TaskHelper::truncate($row->pn_title, 80), 'href' => $href]);
							
						$TaskHelper->addLog($task->task_id, sprintf('warning|non esiste file excerpt news %1$s', $TaskHelper::truncate($row->pn_title, 80)));
					}
				}
				
				if($filename) {
					$post_excerpt = str_ireplace(
							$href,
							$app->settings['dev_url'].'/wp-content/uploads/'.date('Y').'/'.date('m').'/'.$filename,
							$post_excerpt
					);
				
					$file_pathname_arr[] = $file_pathname;
				}
			}
		}
	}
	
	$post = [
			//'ID'             => [ <post id> ] // Are you updating an existing post?
			'post_content'   => $post_content, //[ <string> ] // The full text of the post.
			//'post_name'      => [ <string> ] // The name (slug) for your post
			'post_title'     => $row->pn_title, // The title of your post.
			'post_status'    => 'publish', //[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
			'post_type'      => 'post', //[ 'post' | 'page' | 'link' | 'nav_menu_item' | custom post type ] // Default 'post'.
			'post_author'    => 1, // The user ID number of the author. Default is the current user ID.
			//'ping_status'    => [ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
			//'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
			//'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
			//'to_ping'        => // Space or carriage return-separated list of URLs to ping. Default empty string.
			//'pinged'         => // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
			//'post_password'  => [ <string> ] // Password for post, if any. Default empty string.
			//'guid'           => // Skip this and let Wordpress handle it, usually.
			//'post_content_filtered' => // Skip this and let Wordpress handle it, usually.
			'post_excerpt'   => $post_excerpt, //[ <string> ] // For all your post excerpt needs.
			'post_date'      => $row->pn_time, //[ Y-m-d H:i:s ] // The time post was made.
			'post_date_gmt'  => $row->pn_time, //[ Y-m-d H:i:s ] // The time post was made, in GMT.
			//'comment_status' => [ 'closed' | 'open' ] // Default is the option 'default_comment_status', or 'closed'.
			//'post_category'  => [ array(<category id>, ...) ], // Default empty.
			//'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
			//'tax_input'      => [ array( <taxonomy> => <array | string>, <taxonomy_other> => <array | string> ) ] // For custom taxonomies. Default empty.
			//'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.
	];
	
	$category = R::findOne( 'category', ' old_id = :old_id ', ['old_id' => $row->pn_catid] );
	if(isset($category->id)) {
		if($category->old_id > 0)
			$post['post_category'] = [$category->new_id];
	}
	
	$posttag = R::findOne( 'posttag', ' old_id = :old_id ', ['old_id' => $row->pn_topic] );
	if(isset($posttag->id)) {
		if($posttag->old_id > 0)
			$post['tags_input'] = [$posttag->name];
	}
	
	$return = wp_insert_post($post, true);
	
	if( is_wp_error( $return ) ) {
		throw new Exception($return->get_error_message());
	} else {
		$news = R::dispense( 'news' );
		
		$news->old_id = $row->pn_sid;
		$news->new_id = $return;
		
		$bean_id = R::store( $news );
	}
	
	if($file_pathname_arr) {
		foreach($file_pathname_arr as $file_pathname) {
			// Check the type of file. We'll use this as the 'post_mime_type'.
			$filetype = wp_check_filetype( basename( $file_pathname ), null );
				
			// Prepare an array of post data for the attachment.
			$attachment = array(
					'guid'           => $file_pathname,
					'post_mime_type' => $filetype['type'],
					'post_title'     => sanitize_file_name($row->pn_title),
					'post_content'   => '',
					'post_status'    => 'inherit'
			);
				
			// Insert the attachment.
			$attach_id = wp_insert_attachment( $attachment, $file_pathname, $return);
				
			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file_pathname );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			
			if(!$featured_image && stristr($filetype['type'], 'image') !== false) { //http://stackoverflow.com/a/4192039
				$app->logger->addInfo('set_post_thumbnail', ['pn_sid' => $row->pn_sid, 'return' => $return, 'attach_id' => $attach_id, 'filetype' => $filetype['type']]);
					
				if(!set_post_thumbnail( $return, $attach_id )) {
					throw new Exception(sprintf(_('set_post_thumbnail error: [pn_sid => %1$d, return => %1$s, attach_id => %1$s, filetype => %1$s', $row->pn_sid, $return, $attach_id, $filetype['type'])));
				} else {
					$featured_image = true;
				}
			}
		}
	}
	
	$r++;
}