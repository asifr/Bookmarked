<?php
/**
 * Bookmarked.in Schema API
 */

$schema['bm_bookmarks'] = array(
	'FIELDS'	=> array(
		'ID'	=> array(
			'datatype'		=> 'SERIAL',
			'allow_null'	=> false
		),
		'bookmark_author'	=> array(
			'datatype'		=> 'BIGINT(20) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'bookmark_date'	=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'bookmark_url'	=> array(
			'datatype'		=> 'LONGTEXT',
			'allow_null'	=> false
		),
		'bookmark_title'	=> array(
			'datatype'		=> 'TEXT',
			'allow_null'	=> false
		),
		'bookmark_status'	=> array(
			'datatype'		=> 'VARCHAR(20)',
			'allow_null'	=> false,
			'default'		=> 'toread'	// toread|private|read
		),
		'guid'	=> array(
			'datatype'		=> 'VARCHAR(255)',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'bookmark_tags'	=> array(
			'datatype'		=> 'TEXT',
			'allow_null'	=> true,
			'default'		=> ''
		),
		'bookmark_type'	=> array(
			'datatype'		=> 'VARCHAR(20)',
			'allow_null'	=> false,
			'default'		=> 'website'	// website|pdf
		)
	),
	'PRIMARY KEY'	=> array('ID')
);

$schema['bm_posts'] = array(
	'FIELDS'	=> array(
		'ID'	=> array(
			'datatype'		=> 'SERIAL',
			'allow_null'	=> false
		),
		'post_author'	=> array(
			'datatype'		=> 'BIGINT(20) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'post_date'	=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'post_content'	=> array(
			'datatype'		=> 'LONGTEXT',
			'allow_null'	=> false
		),
		'post_title'	=> array(
			'datatype'		=> 'TEXT',
			'allow_null'	=> false
		),
		'post_status'	=> array(
			'datatype'		=> 'VARCHAR(20)',
			'allow_null'	=> false,
			'default'		=> 'publish'	// publish|pending|draft|private|static|object|attachment|inherit|future|trash
		),
		'post_summary'	=> array(
			'datatype'		=> 'TEXT',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'post_modified'	=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'post_parent'	=> array(
			'datatype'		=> 'BIGINT(20) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'guid'	=> array(
			'datatype'		=> 'VARCHAR(255)',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'post_tags'	=> array(
			'datatype'		=> 'TEXT',
			'allow_null'	=> true,
			'default'		=> ''
		),
		'post_type'	=> array(
			'datatype'		=> 'VARCHAR(20)',
			'allow_null'	=> false,
			'default'		=> 'post'	// post|page|attachment
		),
		'post_mime_type'	=> array(
			'datatype'		=> 'VARCHAR(100)',
			'allow_null'	=> false,
			'default'		=> ''
		)
	),
	'PRIMARY KEY'	=> array('ID')
);

$schema['bm_users'] = array(
	'FIELDS'	=> array(
		'ID'	=> array(
			'datatype'		=> 'SERIAL',
			'allow_null'	=> false
		),
		'user_login'	=> array(
			'datatype'		=> 'VARCHAR(60)',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'user_pass'	=> array(
			'datatype'		=> 'VARCHAR(64)',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'user_nicename'	=> array(
			'datatype'		=> 'VARCHAR(50)',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'user_email'	=> array(
			'datatype'		=> 'VARCHAR(100)',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'user_url'	=> array(
			'datatype'		=> 'VARCHAR(100)',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'user_registered'	=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'user_activation_key'	=> array(
			'datatype'		=> 'VARCHAR(60)',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'user_session_hash'	=> array(
			'datatype'		=> 'VARCHAR(60)',
			'allow_null'	=> false,
			'default'		=> ''
		),
		'user_status'	=> array(
			'datatype'		=> 'INT(11)',
			'allow_null'	=> false,
			'default'		=> 0
		)
	),
	'PRIMARY KEY'	=> array('ID')
);

$schema['bm_postmeta'] = array(
	'FIELDS'		=> array(
		'meta_id'			=> array(
			'datatype'		=> 'SERIAL',
			'allow_null'	=> false
		),
		'post_id'	=> array(
			'datatype'		=> 'BIGINT(20) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'meta_key'	=> array(
			'datatype'		=> 'VARCHAR(255)',
			'allow_null'	=> false,
			'default'		=> NULL
		),
		'meta_value'	=> array(
			'datatype'		=> 'LONGTEXT',
			'allow_null'	=> true
		)
	),
	'PRIMARY KEY'	=> array('meta_id')
);

$schema['bm_usermeta'] = array(
	'FIELDS'		=> array(
		'umeta_id'			=> array(
			'datatype'		=> 'SERIAL',
			'allow_null'	=> false
		),
		'user_id'	=> array(
			'datatype'		=> 'BIGINT(20) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'meta_key'	=> array(
			'datatype'		=> 'VARCHAR(255)',
			'allow_null'	=> false,
			'default'		=> NULL
		),
		'meta_value'	=> array(
			'datatype'		=> 'LONGTEXT',
			'allow_null'	=> true
		)
	),
	'PRIMARY KEY'	=> array('umeta_id')
);

$schema['bm_bookmarkmeta'] = array(
	'FIELDS'		=> array(
		'umeta_id'			=> array(
			'datatype'		=> 'SERIAL',
			'allow_null'	=> false
		),
		'bookmark_id'	=> array(
			'datatype'		=> 'BIGINT(20) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> 0
		),
		'meta_key'	=> array(
			'datatype'		=> 'VARCHAR(255)',
			'allow_null'	=> false,
			'default'		=> NULL
		),
		'meta_value'	=> array(
			'datatype'		=> 'LONGTEXT',
			'allow_null'	=> true
		)
	),
	'PRIMARY KEY'	=> array('umeta_id')
);
?>