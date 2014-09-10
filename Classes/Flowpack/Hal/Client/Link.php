<?php
namespace Flowpack\Hal\Client;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Flowpack.Hal.Client".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Client\Browser;

/**
 * Link
 */
class Link extends AbstractLink {

	/**
	 * Prefix curie if the name is a curie.
	 * Relation to curie name.
	 *
	 * @var NULL|string
	 * @see http://www.w3.org/TR/curie/#s_syntax
	 */
	protected $ncName;

	/**
	 * @var NULL|string
	 */
	protected $reference;

	/**
	 * @var NULL|string
	 */
	protected $title;

	/**
	 * Constructor.
	 *
	 * @param array $data
	 * @param Browser $browser
	 */
	public function __construct(array $data) {
		parent::__construct($data);

		$this->title = isset($data['title']) ? $data['title'] : NULL;

		if ($this->name !== NULL && strpos($this->name, ':') !== FALSE) {
			list($this->ncName, $this->reference) = explode(':', $this->name, 2);
		}
	}

	/**
	 * @return NULL|string
	 */
	public function getNCName() {
		return $this->ncName;
	}

	/**
	 * @return NULL|string
	 */
	public function getReference() {
		return $this->reference;
	}

	/**
	 * @return NULL|string
	 */
	public function getTitle() {
		return $this->title;
	}
}

