<?php

class ApiRepoManage extends ApiBase {
	public function execute() {
		$user = $this->getUser();
		if ( !$user->isAllowed( 'edit' ) ) {
			$this->dieUsage( 'Permission denied', 'permissiondenied' );
		}

		$manager = wfGetRepoManager();
		$params = $this->extractRequestParams();

		$output = array();
		$subaction = $params['subaction'];
		try {
			switch( $subaction ) {
			case 'delete':
			case 'update':
				$id = $params['repoid'];

				$manager->$subaction( $id );
				break;
			default:
				$this->dieUsage( 'Unknown subaction', 'invalidparam' );
			}

			$output['repoid'] = $id;
		} catch ( \RepoLibrary\Exception $e ) {
			$this->dieUsage( $e->getMessage(), 'exception' );
		}

		$output['result'] = 'ok';
		$this->getResult()->addValue( null, $this->getModuleName(), $output );
	}

	public function isWriteMode() {
		return true;
	}

	public static function getToken() {
		return 'csrf';
	}

	public function getAllowedParams() {
		return array(
			'subaction' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'repoid' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
		);
	}
}
