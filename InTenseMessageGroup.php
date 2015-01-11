<?php

class InTenseMessageGroup extends FileBasedMessageGroup implements MetaYamlSchemaExtender {
	protected function parseNamespace() {
		return NS_Z;
	}

	public function getSourceFilePath( $code ) {
		global $wgRepoLibraryRepositoryLocation;

		$manager = wfGetRepoManager();
		$config = $this->getRepository();
		$config['__group'] = $this->getId();

		$id = $manager->createId( $config );
		$x = parent::getSourceFilePath( $code );

		return "$wgRepoLibraryRepositoryLocation/$id/$x";
	}

	public function getTranslatableLanguages() {
		$codes = $this->getFromConf( 'FILES', 'codeMap' ) ?: array();
		$codes[$this->getSourceLanguage()]= $this->getSourceLanguage();
		return $codes;
	}

	public function getMangler() {
		if ( !isset( $this->mangler ) ) {
			$id = $this->getId();
			$this->mangler = new StringMatcher( "$id--", array( '*' ) );
		}

		return $this->mangler;
	}

	public function getRepository() {
		return isset( $this->conf['REPOSITORY'] ) ? $this->conf['REPOSITORY'] : null;
	}

	public static function getExtraSchema() {
		$parent = parent::getExtraSchema();

		$schema = array(
			'root' => array(
				'_type' => 'array',
				'_children' => array(
					'BASIC' => array(
						'_type' => 'array',
						'_children' => array(
							'license' => array(
								'_type' => 'text',
							),
						)
					),
					'REPOSITORY' => array(
						'_type' => 'array',
						'_required' => true,
						'_children' => array(
							'branch' => array(
								'_type' => 'text',
							),
							'source' => array(
								'_type' => 'text',
								'_not_emtpty' => true,
							),
							'type' => array(
								'_type' => 'enum',
								'_values' => array( 'git', 'svn' ),
							),
						),
					),
				),
			),
		);

		return array_replace_recursive( $parent, $schema );
	}
}
