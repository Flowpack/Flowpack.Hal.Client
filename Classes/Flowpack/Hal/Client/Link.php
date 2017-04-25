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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\Browser;

/**
 * Link
 */
class Link extends AbstractLink {

	/**
	 * @var string
	 * @see http://www.w3.org/TR/curie/#s_syntax
	 */
	protected $prefix;

	/**
	 * @var string
	 */
	protected $reference;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $deprecation;

	/**
	 * @var string
	 */
	protected $profile;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $hreflang;

	/**
	 * Constructor.
	 *
	 * @param array $data
	 * @param Browser $browser
	 */
	public function __construct(array $data) {
		parent::__construct($data);

		if ($this->name !== NULL && strpos($this->name, ':') !== FALSE) {
			list($this->prefix, $this->reference) = explode(':', $this->name, 2);
		}

		$this->type = isset($data['type']) ? $data['type'] : NULL;
		$this->deprecation = isset($data['deprecation']) ? $data['deprecation'] : NULL;
		$this->profile = isset($data['profile']) ? $data['profile'] : NULL;
		$this->title = isset($data['title']) ? $data['title'] : NULL;
		$this->hreflang = isset($data['hreflang']) ? $data['hreflang'] : NULL;
	}

	/**
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @return string
	 */
	public function getReference() {
		return $this->reference;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getDeprecation() {
		return $this->deprecation;
	}

	/**
	 * @return string
	 */
	public function getProfile() {
		return $this->profile;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getHreflang() {
		return $this->hreflang;
	}
}

