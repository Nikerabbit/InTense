<?php

class ProcessMessageGroupJob extends Job {
	public static function newJob( $groupId ) {
		$params = array(
			'groupId' => $groupId,
		);

		return new self( Title::newMainPage(), $params );
	}

	function __construct( $title, $params = array(), $id = 0 ) {
		parent::__construct( __CLASS__, $title, $params, $id );
		$this->removeDuplicates = true;
	}

	function run() {
		$comparator = new ExternalMessageSourceStateComparator();

		$id = $this->params['groupId'];
		$group = MessageGroups::getGroup( $id );
		if ( !$group ) {
			return true;
		}

		$changes = $comparator->processGroup( $group, $comparator::ALL_LANGUAGES );
		if ( !count( $changes ) ) {
			return true;
		}

		$jobs = array();
		$reindex = false;

		foreach ( $changes as $code => $subchanges ) {
			foreach ( $subchanges as $type => $messages ) {
				foreach ( $messages as $index => $params ) {
					if ( $code === $group->getSourceLanguage() ) {
						$reindex = $reindex || $type === 'deletion' || $type === 'addition';
						$fuzzy = $type === 'change';
					} else {
						$fuzzy = false;
					}

					if ( $type === 'deletion' ) {
						continue;
					}

					$key = $params['key'];
					$title = Title::makeTitleSafe( $group->getNamespace(), "$key/$code" );
					if ( !$title ) {
						var_dump( $group->getNamespace(), "$key/$code" );
						continue;
					}

					$jobs[] = MessageUpdateJob::newJob( $title, $params['content'], $fuzzy );
				}
			}

			$cache = new MessageGroupCache( $id, $code );
			$cache->create();
		}

		if ( $reindex ) {
			array_unshift( $jobs, MessageIndexRebuildJob::newJob() );
		}

		JobQueueGroup::singleton()->push( $jobs );

		return true;
	}
}
