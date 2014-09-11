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
 * ResourceCollection
 */
class ResourceCollection implements \Iterator, \Countable, \ArrayAccess {

	/**
	 * @var \ArrayIterator
	 */
	protected $iterator;

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
	 * @param array $collection
	 */
	public function __construct(Browser $browser, Uri $baseUri, array $collection = array()) {
		$this->baseUri = $baseUri;

		$this->browser = $browser;
		$this->iterator = new \ArrayIterator($collection);
	}

	/**
	 * {@inheritdoc}
	 */
	public function current() {
		$resource = $this->iterator->current();
		if ($resource === NULL) {
			return NULL;
		}

		if (!$resource instanceof Resource) {
			$resource = $this->createResource($resource);
			$this->iterator->offsetSet($this->iterator->key(), $resource);
		}

		return $resource;
	}

	/**
	 * {@inheritdoc}
	 */
	public function next() {
		$this->iterator->next();
	}

	/**
	 * {@inheritdoc}
	 */
	public function key() {
		return $this->iterator->key();
	}

	/**
	 * {@inheritdoc}
	 */
	public function valid() {
		return $this->iterator->valid();
	}

	/**
	 * {@inheritdoc}
	 */
	public function rewind() {
		$this->iterator->rewind();
	}

	/**
	 * {@inheritdoc}
	 */
	public function count() {
		if (!$this->iterator instanceof \Countable) {
			throw new \RuntimeException('Operation not allowed', 1410358638);
		}

		return count($this->iterator);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetExists($offset) {
		if (!$this->iterator instanceof \ArrayAccess) {
			throw new \RuntimeException('Operation not allowed', 1410358626);
		}

		return isset($this->iterator[$offset]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetGet($offset) {
		if (!$this->iterator instanceof \ArrayAccess) {
			throw new \RuntimeException('Operation not allowed', 1410358622);
		}

		$resource = $this->iterator->offsetGet($offset);
		if ($resource === NULL) {
			return NULL;
		}

		if (!$resource instanceof Resource) {
			$resource = $this->createResource($resource);
			$this->iterator->offsetSet($offset, $resource);
		}

		return $resource;
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $value) {
		throw new \RuntimeException('Operation not allowed', 1410358614);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset) {
		throw new \RuntimeException('Operation not allowed', 1410358617);
	}

	/**
	 * @param array $data
	 * @return Resource
	 */
	protected function createResource(array $data) {
		return new Resource($this->browser, $this->baseUri, $data);
	}

}