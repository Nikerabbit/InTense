<?php

$resourcePaths = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'InTense'
);

$GLOBALS['wgResourceModules']['ext.intense.special.repostatus'] = array(
	'scripts' => 'resources/ext.intense.special.repostatus.js',
) + $resourcePaths;
