<?php
namespace Flowpack\Hal\Client;

/*
 * This file is part of the Flowpack.Hal.Client package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Uri;

/**
 * HalResourceCollection
 */
class HalResourceCollection implements \Iterator, \Countable, \ArrayAccess
{
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
    public function __construct(Browser $browser, Uri $baseUri, array $collection = [])
    {
        $this->baseUri = $baseUri;

        $this->browser = $browser;
        $this->iterator = new \ArrayIterator($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $resource = $this->iterator->current();
        if ($resource === null) {
            return null;
        }

        if (!$resource instanceof HalResource) {
            $resource = $this->createResource($resource);
            $this->iterator->offsetSet($this->iterator->key(), $resource);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (!$this->iterator instanceof \Countable) {
            throw new \RuntimeException('Operation not allowed', 1410358638);
        }

        return count($this->iterator);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if (!$this->iterator instanceof \ArrayAccess) {
            throw new \RuntimeException('Operation not allowed', 1410358626);
        }

        return isset($this->iterator[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!$this->iterator instanceof \ArrayAccess) {
            throw new \RuntimeException('Operation not allowed', 1410358622);
        }

        $resource = $this->iterator->offsetGet($offset);
        if ($resource === null) {
            return null;
        }

        if (!$resource instanceof HalResource) {
            $resource = $this->createResource($resource);
            $this->iterator->offsetSet($offset, $resource);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Operation not allowed', 1410358614);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Operation not allowed', 1410358617);
    }

    /**
     * @param array $data
     * @return HalResource
     */
    protected function createResource(array $data)
    {
        return new HalResource($this->browser, $this->baseUri, $data);
    }

}
