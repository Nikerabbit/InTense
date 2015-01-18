<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$GLOBALS['wgExtensionCredits']['other'][] = array(
	'path' => __FILE__,
	'name' => 'InTense',
	'version' => '2015-01-03',
	'author' => 'Niklas Laxström',
	'description' => 'InTensify!',
);

$dir = __DIR__;

$GLOBALS['wgMessagesDirs']['InTense'] = "$dir/i18n";
$GLOBALS['wgExtensionMessagesFiles']['InTenseAlias'] = "$dir/InTense.alias.php";

$GLOBALS['wgAutoloadClasses'] += array(
	'ApiRepoManage' => "$dir/ApiRepoManage.php",
	'InTenseMessageGroup' => "$dir/InTenseMessageGroup.php",
	'ProcessMessageGroupJob' => "$dir/ProcessMessageGroupJob.php",
	'SpecialRepoStatus' => "$dir/SpecialRepoStatus.php",
);

require "$dir/Resources.php";

$GLOBALS['wgJobClasses']['ProcessMessageGroupJob'] = 'ProcessMessageGroupJob';
$GLOBALS['wgSpecialPages']['RepoStatus'] = 'SpecialRepoStatus';
$GLOBALS['wgAPIModules']['repomanage'] = 'ApiRepoManage';

$GLOBALS['wgHooks']['TranslatePostInitGroups'][] = function ( &$list, &$deps, &$autoload ) {
	$dbw = wfGetDB( DB_MASTER );
	$res = $dbw->select(
		array( 'page', 'text', 'revision' ),
		array( 'page_id', 'page_title', 'page_namespace', 'rev_id', 'old_text', 'old_flags' ),
		array(
			'page_latest = rev_id',
			'old_id = rev_text_id',
			'page_namespace' => NS_GROUP,
		),
		__METHOD__
	);

	foreach ( $res as $r ) {
		$text = Revision::getRevisionText( $r );
		$parsed = TranslateYaml::loadString( $text );
		$group = MessageGroupBase::factory( $parsed );
		$list[$group->getId()] = $group;
	}
};

$GLOBALS['wgHooks']['PageContentSaveComplete'][] = function (
	WikiPage $page,
	User $user,
	$content,
	$summary,
	$minor,
	$_,
	$_,
	$flags,
	$revision
) {
	if ( !$page->getTitle()->inNamespace( NS_GROUP ) ) {
		return true;
	}

	$manager = wfGetRepoManager();
	$configStorage = $manager->getConfigStorage();
	$parsed = TranslateYaml::loadString( $content->getNativeData() );

	$group = MessageGroupBase::factory( $parsed );
	if ( !$group instanceof InTenseMessageGroup ) {
		return true;
	}

	$config = $group->getRepository();
	if ( !is_array( $config ) ) {
		return true;
	}

	$config['__group'] = $group->getId();

	$id = $manager->createId( $config );
	if ( $configStorage->exists( $id ) ) {
		$manager->update( $id );
	} else {
		$manager->create( $config );
	}

	MessageGroups::clearCache();
};

$GLOBALS['wgHooks']['RepoLibraryRepoUpdated'][] = function ( $id, $new ) {
	$manager = wfGetRepoManager();
	$configStorage = $manager->getConfigStorage();
	$groupId = $configStorage->get( $id )['__group'];
	$group = MessageGroups::getGroup( $groupId );
	if ( $group ) {
		$job = ProcessMessageGroupJob::newJob( $groupId );
		JobQueueGroup::singleton()->push( $job );
	}
};

$GLOBALS['wgHooks']['EditFilterMergedContent'][] = function ( $context, $content, $status ) {
	if ( !$content instanceof TextContent ) {
		return true; // whatever.
	}

	$title = $context->getTitle();
	if ( !$title->inNamespace( NS_GROUP ) ) {
		return true;
	}

	$parser = new MessageGroupConfigurationParser();
	$parser->getHopefullyValidConfigurations(
		$content->getNativeData(),
		function ( $index, $config, $error ) use ( $status ) {
			$status->fatal( new RawMessage( "Document $index failed to validate: $error" ) );
		}
	);

	return true;
};
