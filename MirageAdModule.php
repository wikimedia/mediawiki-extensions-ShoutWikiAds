<?php

use MediaWiki\Skins\Mirage\RightRailModules\RightRailModule;

class MirageAdModule extends RightRailModule {
	/** @var string */
	private $ad;

	public function __construct( $skin, string $ad ) {
		parent::__construct( $skin, 'mirage-right-rail-ad' );

		$this->ad = $ad;
	}

	/**
	 * @inheritDoc
	 */
	protected function getBodyContent() : string {
		return $this->ad;
	}

	/**
	 * @inheritDoc
	 */
	public function getAdditionalModuleClasses() : array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getHeader() : ?string {
		return null;
	}
}
