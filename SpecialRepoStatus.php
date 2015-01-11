<?php

class SpecialRepoStatus extends SpecialPage {
	public function __construct() {
		parent::__construct( 'RepoStatus' );
	}

	public function execute( $par ) {
		$this->checkPermissions();

		$request = $this->getRequest();
		$user = $this->getUser();
		$out = $this->getOutput();
		$language = $this->getLanguage();
		$this->setHeaders();

		$manager = wfGetRepoManager();
		$statusStorage = $manager->getStatusStorage();
		$configStorage = $manager->getConfigStorage();

		$out->addHtml( '<table class=wikitable>' );
		foreach ( $statusStorage->getList() as $id ) {
			$status = $statusStorage->get( $id );
			$config = $configStorage->get( $id );

			$out->addHtml( '<tr><th>' . htmlspecialchars( $config['source'] ) . '</th></tr>' );
			unset( $config['source'] );

			$out->addHtml(
				'<tr><td><table class=wikitable>' .
				$this->uglify( array_keys( $config ) ) .
				$this->uglify( array_values( $config ) ) .
				'</td></tr></table>'
			);

			$timestamp = new MWTimestamp( $status['timestamp'] );
			$timestamp = $timestamp->getHumanTimestamp( new MWTimestamp(), $user, $language );

			$size = isset( $status['size'] ) ? $status['size'] : -1;
			$size = $language->formatSize( $size );

			$out->addHtml(
				'<tr><td><table class=wikitable>' .
				'<tr><td>status</td><td>timestamp</td><td>size</td></tr>' .
				"<tr><td>{$status['code']}</td><td>$timestamp</td><td>$size</td></tr>" .
				'<tr><td colspan=3>' . TranslateUtils::convertWhiteSpaceToHTML( $status['output'] ) . '</td></tr>' .
				'</table></td></tr>'
			);
		}

		$out->addHtml( '</table>' );
	}

	public function uglify( $items ) {
		$cells = array_map( function ( $v ) { return Html::element( 'td', null, $v ); }, $items );
		return Html::rawElement( 'tr', null, implode( '', $cells ) );
	}
}
