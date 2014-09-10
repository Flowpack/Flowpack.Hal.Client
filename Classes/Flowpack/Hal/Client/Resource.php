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
 * Resource
 */
class Resource implements \ArrayAccess {

	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var array
	 */
	protected $links = array();

	/**
	 * @var array
	 */
	protected $embedded = array();

	/**
	 * @var array
	 */
	protected $curies = array();

	/**
	 * @var Browser
	 */
	protected $browser;

	/**
	 * @param Browser $browser
	 * @param array $properties
	 * @param array $links
	 * @param array $embedded
	 */
	public function __construct(Browser $browser, array $properties, array $links = array(), array $embedded = array()) {
		$this->browser = $browser;
		$this->properties = $properties;
		$this->links = $links;
		$this->embedded = $embedded;
	}

	/**
	 *
	 *
	 * @return void
	 */
	protected function initializeObject() {
		$this->links = isset($this->properties['_links']) ? $this->properties['_links'] : $this->links;
		$this->embedded = isset($this->properties['_embedded']) ? $this->properties['_embedded'] : $this->embedded;

		unset($this->properties['_links']);
		unset($this->properties['_embedded']);

		if (array_key_exists('curies', $this->links)) {
			foreach ($this->links['curies'] as $curie) {
				$this->curies[$curie['name']] = new Curie($curie);
			}
		}
	}

	/**
	 *
	 *
	 * @param string $uri
	 * @param Browser $browser
	 * @return Resource
	 */
	static public function createFromUri($uri, Browser $browser) {
		$response = $browser->request($uri);

		if (substr($response->getHeader('Content-Type'), 0, 20) !== 'application/hal+json') {
			// disabled for now, server sends wrong header...
			// throw new \RuntimeException('Invalid content type received: ' . $response->getHeader('Content-Type'), 1410345012);
		}

		$data = json_decode($response->getContent(), TRUE);

		if ($data === NULL) {
			throw new \RuntimeException('Invalid JSON format returned from ' . $uri, 1410259050);
		}

		return new Resource($browser, $data);
	}

	/**
	 * @return array
	 */
	public function getEmbedded() {
		return $this->embedded;
	}

	/**
	 * @return array
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * @param string $name
	 * @return Link
	 */
	public function getLink($name) {
		if (!array_key_exists($name, $this->links)) {
			return NULL;
		}

		if (!$this->links[$name] instanceof Link) {
			$this->links[$name] = new Link(array_merge(array('name' => $name), $this->links[$name]), $this->browser);
		}

		return $this->links[$name];
	}

	/**
	 * Create a resource from link href.
	 *
	 * @param string $name
	 * @param array $variables Required if the link is templated
	 * @return Resource
	 */
	public function getLinkResource($name, array $variables = array()) {
		$link = $this->getLink($name);

		return self::createFromUri($link->getHref($variables), $this->browser);
	}

	/**
	 * @param string $name
	 * @return Curie
	 */
	public function getCurie($name) {
		if (!array_key_exists($name, $this->curies)) {
			return NULL;
		}

		return $this->curies[$name];
	}

	/**
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @param string $name
	 * @return Resource|ResourceCollection|NULL
	 */
	public function get($name) {
		if (array_key_exists($name, $this->properties)) {
			return $this->properties[$name];
		}

		if (!array_key_exists($name, $this->embedded)) {
			if (!$this->buildResourceValue($name)) {
				return NULL;
			}
		}

		return $this->getEmbeddedValue($name);
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function has($name) {
		return $this->hasProperty($name) || $this->hasLink($name) || $this->hasEmbedded($name);
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasLink($name) {
		return isset($this->links[$name]);
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasProperty($name) {
		return isset($this->properties[$name]);
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasEmbedded($name) {
		return isset($this->embedded[$name]);
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	protected function buildResourceValue($name) {
		$link = $this->getLink($name);

		if (!$link) {
			return FALSE;
		}

		$this->embedded[$name] = $this->getResource($link);

		return TRUE;
	}

	/**
	 * Returns the href of curie assoc given by link.
	 *
	 * @param Link $link
	 * @return string
	 */
	public function getCurieHref(Link $link) {
		if (NULL === $link->getNCName() || NULL === $this->getCurie($link->getNCName())) {
			return NULL;
		}

		return $this->getCurie($link->getNCName())->getHref(array('rel' => $link->getReference()));
	}

	/**
	 * @param string $name
	 * @return Resource|ResourceCollection
	 */
	protected function getEmbeddedValue($name) {
		if (!is_object($this->embedded[$name])) {
			if (is_integer(key($this->embedded[$name])) || empty($this->embedded[$name])) {
				$this->embedded[$name] = new ResourceCollection($this->browser, $this->embedded[$name]);
			} else {
				$this->embedded[$name] = new self($this->browser, $this->embedded[$name]);
			}
		}

		return $this->embedded[$name];
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetExists($offset) {
		return $this->has($offset);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $value) {
		throw new \RuntimeException('Operation not available');
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset) {
		throw new \RuntimeException('Operation not available');
	}
}