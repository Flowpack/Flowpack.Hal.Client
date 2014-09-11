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
use TYPO3\Flow\Http\Uri;

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
	 * @var Uri
	 */
	protected $baseUri;

	/**
	 * @param Browser $browser
	 * @param Uri $baseUri
	 * @param array $properties
	 * @param array $links
	 * @param array $embedded
	 */
	public function __construct(Browser $browser, Uri $baseUri, array $properties, array $links = array(), array $embedded = array()) {
		$this->browser = $browser;
		$this->baseUri = $baseUri;
		$this->properties = $properties;
		$this->links = $links;
		$this->embedded = $embedded;
	}

	/**
	 *
	 *
	 * @return void
	 */
	public function initializeObject() {
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
	 * @param string|Uri $uri
	 * @param Browser $browser
	 * @param Uri $baseUri
	 * @return Resource
	 */
	static public function createFromUri($uri, Browser $browser, Uri $baseUri = NULL) {
		$response = $browser->request($uri);

		if (substr($response->getHeader('Content-Type'), 0, 20) !== 'application/hal+json') {
			throw new \RuntimeException('Invalid content type received: ' . $response->getHeader('Content-Type'), 1410345012);
		}

		$data = json_decode($response->getContent(), TRUE);

		if ($data === NULL) {
			throw new \RuntimeException('Invalid JSON format returned from ' . $uri, 1410259050);
		}

		if ($baseUri === NULL) {
			if (is_string($uri)) {
				$baseUri = new Uri($uri);
			} else {
				$baseUri = clone $uri;
			}
			$baseUri->setPath(NULL);
			$baseUri->setFragment(NULL);
			$baseUri->setQuery(NULL);
		}

		return new Resource($browser, $baseUri, $data);
	}

	/**
	 * Returns true if this resource has a property, embedded value or link with the given name.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function has($name) {
		return $this->hasProperty($name) || $this->hasEmbedded($name) || $this->hasLink($name);
	}

	/**
	 * Returns the property, embedded value or link with the given name.
	 *
	 * The existence is checked in this order: property, embedded, link.
	 *
	 * @param string $name
	 * @return Resource|ResourceCollection|NULL
	 */
	public function get($name) {
		if (array_key_exists($name, $this->properties)) {
			return $this->properties[$name];
		}

		if (array_key_exists($name, $this->embedded)) {
			return $this->getEmbedded($name);
		}

		if (array_key_exists($name, $this->links)) {
			return $this->getLinkValue($name);
		}

		return NULL;
	}

	/**
	 * Returns the value of the property with the given name or NULL
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getProperty($name) {
		if (!array_key_exists($name, $this->properties)) {
			return NULL;
		}

		return $this->properties[$name];
	}

	/**
	 * Returns an array of all properties this resource has.
	 *
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * Returns true if this resource has a property with the given name.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasProperty($name) {
		return isset($this->properties[$name]);
	}

	/**
	 * @param string $name
	 * @return Resource|ResourceCollection
	 */
	public function getEmbedded($name) {
		if (!is_object($this->embedded[$name])) {
			if (is_integer(key($this->embedded[$name])) || empty($this->embedded[$name])) {
				$this->embedded[$name] = new ResourceCollection($this->browser, $this->baseUri, $this->embedded[$name]);
			} else {
				$this->embedded[$name] = new Resource($this->browser, $this->baseUri, $this->embedded[$name]);
			}
		}

		return $this->embedded[$name];
	}

	/**
	 * @return array
	 * @todo should this return an array of resource instances?
	 */
	public function getEmbeddeds() {
		return $this->embedded;
	}

	/**
	 * Returns true if this resource has an embedded resource with the given name.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasEmbedded($name) {
		return isset($this->embedded[$name]);
	}

	/**
	 * Returns true if this resource has a link with the given name.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasLink($name) {
		return isset($this->links[$name]);
	}

	/**
	 * Returns an array of all links this resource carries.
	 *
	 * @return array
	 * @todo should this return an array of link instances?
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * @param string $name
	 * @return Link
	 * @todo support link collections
	 */
	public function getLink($name) {
		if (!array_key_exists($name, $this->links)) {
			return NULL;
		}

		if (!$this->links[$name] instanceof Link) {
			$this->links[$name] = new Link(array_merge(array('name' => $name), $this->links[$name]));
		}

		return $this->links[$name];
	}

	/**
	 * Returns a resource built from the link with the given name.
	 *
	 * @param string $name
	 * @param array $variables Required if the link is templated
	 * @return Resource|ResourceCollection
	 * @todo support link collections
	 */
	public function getLinkValue($name, array $variables = array()) {
		$uri = new Uri($this->getLink($name)->getHref($variables));

		if ($uri->getHost() === NULL) {
			$uri->setScheme($this->baseUri->getScheme());
			$uri->setHost($this->baseUri->getHost());
			$uri->setPath($this->baseUri->getPath() . $uri->getPath());
		}
		return self::createFromUri($uri, $this->browser);
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
	 * Returns the href of curie assoc given by link.
	 *
	 * @param string $linkName
	 * @return string
	 */
	public function getCurieHref($linkName) {
		$link = $this->getLink($linkName);

		if ($link->getPrefix() === NULL || $this->getCurie($link->getPrefix()) ===  NULL) {
			return NULL;
		}

		return $this->getCurie($link->getPrefix())->getHref(array('rel' => $link->getReference()));
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