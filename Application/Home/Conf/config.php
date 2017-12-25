<?php
return array(
	/* 模板相关配置 */
	'TMPL_PARSE_STRING' => array(
		'__STATIC__'     => __ROOT__ . '/Public/static',
		'__new_boss__'     => __ROOT__ . '/Public/new_boss',
		'__IMG__'        => __ROOT__ . '/Public/' . MODULE_NAME . '/img',
		'__CSS__'        => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
		'__JS__'         => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
		'__MODULE__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/module',
	),

	// 'LOAD_EXT_CONFIG' => array('OPTION'=>'option'),
	// 'LIST_ROWS'=>10,
);