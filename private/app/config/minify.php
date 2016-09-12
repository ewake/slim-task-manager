<?php
return [
		'minify' => [
				'status' => false,
				
				'options' => [
						'xhtml' => true,
						'jsCleanComments' => true,
						'jsMinifier' => ['JSMin\\JSMin', 'minify'],
						'cssMinifier' => ['Minify_CSSmin', 'minify'],
				],
				
				//TODO
				'cache_type' => null, // apc, file, memcache, wincache, xcache, zendplatform or null			
				'cache_path' => _ROOT . '/tmp/cache',
		],
];