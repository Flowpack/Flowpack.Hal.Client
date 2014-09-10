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
	 * @var boolean
	 */
	protected $updateIterator = TRUE;

	/**
	 * @param Browser $browser
	 * @param array $collection
	 */
	public function __construct(Browser $browser, array $collection = array()) {
		$this->browser = $browser;
		$this->iterator = new \ArrayIterator($collection);
	}

	/**
	 * @param Browser $client
	 * @param \Iterator $collection
	 * @param boolean $updateIterator if the Iterator should be updated to wrap the data inside Resource instances
	 * @return ResourceCollection
	 */
	public static function createFromIterator(Browser $client, \Iterator $collection, $updateIterator = FALSE) {
		$col = new self($client);
		$col->iterator = $collection;
		$col->updateIterator = $updateIterator;

		return $col;
	}

	/**
	 * @param null|array $data
	 *
	 * @return Resource
	 */
	protected function createResource($data) {
		if (NULL === $data) {
			return NULL;
		}

		return new Resource($this->browser, $data);
	}

	/**
	 * {@inheritdoc}
	 */
	public function current() {
		$resource = $this->iterator->current();
		if (NULL === $resource) {
			return NULL;
		}

		if ($this->updateIterator && !$resource instanceof Resource) {
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
			throw new \RuntimeException('Operation not allowed');
		}

		return count($this->iterator);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetExists($offset) {
		if (!$this->iterator instanceof \ArrayAccess) {
			throw new \RuntimeException('Operation not allowed');
		}

		return isset($this->iterator[$offset]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetGet($offset) {
		if (!$this->iterator instanceof \ArrayAccess) {
			throw new \RuntimeException('Operation not allowed');
		}

		$resource = $this->iterator->offsetGet($offset);
		if (NULL === $resource) {
			return NULL;
		}

		if ($this->updateIterator && !$resource instanceof Resource) {
			$resource = $this->createResource($resource);
			$this->iterator->offsetSet($offset, $resource);
		}

		return $resource;
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $value) {
		throw new \RuntimeException('Operation not allowed');
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset) {
		throw new \RuntimeException('Operation not allowed');
	}
}