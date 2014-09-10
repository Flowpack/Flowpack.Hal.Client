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
class AbstractLink {

	/**
	 * @var string
	 * @see http://www.w3.org/TR/1999/REC-xml-names-19990114/#NT-NCName
	 */
	protected $name;

	/**
	 * @var null|string
	 */
	protected $href;

	/**
	 * @var boolean
	 */
	protected $templated = FALSE;

	/**
	 * Constructor.
	 *
	 * @param array $data
	 */
	public function __construct(array $data) {
		if (!isset($data['href'])) {
			throw new \RuntimeException('Property href must be set.', 1410345835);
		}
		$this->href = $data['href'];
		$this->name = isset($data['name']) ? $data['name'] : NULL;
		$this->templated = isset($data['templated']) ? (boolean)$data['templated'] : FALSE;
	}

	/**
	 * @return NULL|string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the href.
	 *
	 * @param array $variables Required if the link is templated
	 * @return NULL|string
	 * @throws \RuntimeException When call with property "href" empty and sets variables
	 */
	public function getHref(array $variables = array()) {
		if (!empty($variables)) {
			return $this->prepareUrl($variables);
		}

		return $this->href;
	}

	/**
	 * @return boolean
	 */
	public function isTemplated() {
		return $this->templated;
	}

	/**
	 * Prepare the url with variables.
	 *
	 * @param array $variables Required if the link is templated
	 * @return string
	 * @throws \RuntimeException When call with property "href" empty
	 */
	private function prepareUrl(array $variables = array()) {
		if (!$this->templated) {
			return $this->href;
		}

		return str_replace('{rel}', $variables['rel'], $this->href);
	}
}

