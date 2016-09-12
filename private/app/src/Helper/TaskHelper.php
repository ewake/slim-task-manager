<?php
namespace App\Helper;

use RedBeanPHP\R;

final class TaskHelper extends \App\Helper
{
	public function checkRunning()
	{
		return R::findOne( 'task', ' running = 1 ' );
	}
	
	public function checkStart($task_id)
	{
		$tasks = R::findAll( 'task', ' ORDER BY task_id ASC ' );
	
		$max_done_id = 0;
	
		if( count($tasks) ) {
			foreach( $tasks as $task ) {
				if($task->running)
					return false;
				
				if($task->done)
					$max_done_id = $task->task_id;
			}
				
			if($task_id > 1 && $max_done_id != ($task_id - 1))
				return false;
		} else {
			if($task_id > 1)
				return false;
		}
	
		return true;
	}
	
	public function getLabel($title)
	{
		switch($title) {
			case 'start':
				return 'label label-info';
			  break;
			case 'complete':
			  return 'label label-success';
			  break;
			case 'warning':
			  return 'label label-warning';
			  break;
			case 'fail':
			  return 'label label-danger';
			  break;
			case 'reset':
			  return 'label label-default';
			  break;
			default:
			  return '';
		}
	}
	
	public function getLogBuffer($limit = 5000)
	{
		$buffer = '';
	
		$logs = R::findAll( 'log' );

		$tot_logs = count($logs);
		
		if( $tot_logs ) {
			$logs = R::find( 'log', ' ORDER BY id DESC LIMIT :limit ', ['limit' => $limit] );
			
			foreach( $logs as $log ) {
				if(strpos($log->title, '|')) {
					list($label, $title) = explode('|', $log->title);
					$title = ' '.$title;
					$label_class = $this->getLabel($label);
				} else {
					$label_class = $this->getLabel($log->title);
					if($label_class) {
						$label = $log->title;
						$title = '';
					} else {
						$label = '';
						$title = $log->title;
					}
				}
				
				$buffer .= sprintf(_('[%1$s] task'.($log->task_id ? ' #%2$d' : 's').': <span class="%3$s">%4$s</span>%5$s'), $log->idate, $log->task_id, $label_class, $label, $title).PHP_EOL;
				
				if($log->new) {
					$log->new = 0;
					R::store( $log );
				}
			}
			
			if($tot_logs > $limit)
				$buffer .= '...'.PHP_EOL;
		}
	
		return $buffer;
	}
	
	public function addLog($task_id, $title)
	{
		$logger = $this->container->get('logger');
	
		$log = R::dispense( 'log' );
			
		$log->idate = R::isoDateTime();
		$log->task_id = $task_id;
		$log->new = 1;
		$log->title = $title;
	
		$logger->addDebug('log', ['task_id' => $task_id, 'title' => $title]);
			
		return R::store( $log );
	}
	
	public static function truncate($string, $limit, $break=" ", $pad="...")
	{
		// return with no change if string is shorter than $limit
		if(strlen($string) <= $limit) return $string;
	
		$string = substr($string, 0, $limit);
		if(false !== ($breakpoint = strrpos($string, $break))) {
			$string = substr($string, 0, $breakpoint);
		}
	
		return $string . $pad;
	}
	
}
