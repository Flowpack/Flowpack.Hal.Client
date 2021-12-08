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
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * HalResource
 */
class HalResource implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var array
     */
    protected $links = [];

    /**
     * @var array
     */
    protected $embedded = [];

    /**
     * @var array
     */
    protected $curies = [];

    /**
     * @var Browser
     */
    protected $browser;

    /**
     * @var UriInterface
     */
    protected $baseUri;

    /**
     * Construct a HalResource instance. Usually the static factory methods should be used to acquire usable
     * instances of HalResource.
     *
     * @param Browser $browser
     * @param UriInterface $baseUri
     * @param array $properties
     * @param array $links
     * @param array $embedded
     */
    public function __construct(Browser $browser, UriInterface $baseUri, array $properties, array $links = [], array $embedded = [])
    {
        $this->browser = $browser;
        $this->baseUri = $baseUri;
        $this->properties = $properties;
        $this->links = $links;
        $this->embedded = $embedded;

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
     * Create and return the HalResource found at the given $uri.
     *
     * @param string|UriInterface $uri URI to fetch the resource data from
     * @param Browser $browser The browser to use for fetching the resource data
     * @param UriInterface $baseUri Base uri to use, if omitted it is built from the $uri
     * @return HalResource
     */
    static public function createFromUri($uri, Browser $browser, UriInterface $baseUri = null)
    {
        if (is_string($uri)) {
            $requestUri = new Uri($uri);
        } else {
            $requestUri = $uri;
        }

        if ($baseUri === null) {
            if (is_string($uri)) {
                $baseUri = new Uri($uri);
            } else {
                $baseUri = clone $uri;
            }
            $baseUri = $baseUri->withPath('')->withQuery('')->withFragment('');
        }

        return self::createFromRequest(new ServerRequest('get', $requestUri), $browser, $baseUri);
    }

    /**
     * Create and return a HalResource with the data the request returns.
     *
     * @param ServerRequestInterface $request The request to use for fetching the resource data
     * @param Browser $browser The browser to use for fetching the resource data
     * @param UriInterface $baseUri Base uri to use
     * @return HalResource
     */
    static public function createFromRequest(ServerRequestInterface $request, Browser $browser, UriInterface $baseUri)
    {
        $response = $browser->sendRequest($request);

        if (substr($response->getHeaderLine('Content-Type'), 0, 20) !== 'application/hal+json') {
            throw new \RuntimeException('Invalid content type received: ' . $response->getHeaderLine('Content-Type'), 1410345012);
        }

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Response with unexpected status code returned for ' . $request->getUri() . ': ' . $response->getStatusCode(), 1418142491);
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if ($data === null) {
            $message = 'Invalid JSON format returned from ' . $request->getUri() . ', JSON error code: ' . json_last_error();
            if (function_exists(json_last_error_msg())) {
                $message .= ', JSON error message: ' . json_last_error_msg();
            }
            throw new \RuntimeException($message, 1410259050);
        }

        return new HalResource($browser, $baseUri, $data);
    }

    /**
     * Returns true if this resource has a property, embedded value or link with the given name.
     *
     * @param string $name
     * @return boolean
     */
    public function has($name)
    {
        return $this->hasProperty($name) || $this->hasEmbedded($name) || $this->hasLink($name);
    }

    /**
     * Returns the property, embedded value or link with the given name.
     * The existence is checked in this order: property, embedded, link.
     *
     * @param string $name
     * @return HalResource|HalResourceCollection|NULL
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        if (array_key_exists($name, $this->embedded)) {
            return $this->getEmbedded($name);
        }

        if (array_key_exists($name, $this->links)) {
            return $this->getLinkValue($name);
        }

        return null;
    }

    /**
     * Returns the value of the property with the given name or NULL
     *
     * @param string $name
     * @return mixed
     */
    public function getProperty($name)
    {
        if (!array_key_exists($name, $this->properties)) {
            return null;
        }

        return $this->properties[$name];
    }

    /**
     * Returns an array of all properties this resource has.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Returns true if this resource has a property with the given name.
     *
     * @param string $name
     * @return boolean
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * @param string $name
     * @return HalResource|HalResourceCollection
     */
    public function getEmbedded($name)
    {
        if (!is_object($this->embedded[$name])) {
            if (is_integer(key($this->embedded[$name])) || empty($this->embedded[$name])) {
                $this->embedded[$name] = new HalResourceCollection($this->browser, $this->baseUri, $this->embedded[$name]);
            } else {
                $this->embedded[$name] = new HalResource($this->browser, $this->baseUri, $this->embedded[$name]);
            }
        }

        return $this->embedded[$name];
    }

    /**
     * @return array
     * @todo should this return an array of HalResource instances?
     */
    public function getEmbeddeds()
    {
        return $this->embedded;
    }

    /**
     * Returns true if this resource has an embedded resource with the given name.
     *
     * @param string $name
     * @return boolean
     */
    public function hasEmbedded($name)
    {
        return isset($this->embedded[$name]);
    }

    /**
     * Returns true if this resource has a link with the given name.
     *
     * @param string $name
     * @return boolean
     */
    public function hasLink($name)
    {
        return isset($this->links[$name]);
    }

    /**
     * Returns an array of all links this resource carries.
     *
     * @return array
     * @todo should this return an array of link instances?
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param string $name
     * @return Link
     * @todo support link collections
     */
    public function getLink($name)
    {
        if (!array_key_exists($name, $this->links)) {
            return null;
        }

        if (!$this->links[$name] instanceof Link) {
            $this->links[$name] = new Link(array_merge(['name' => $name], $this->links[$name]));
        }

        return $this->links[$name];
    }

    /**
     * Returns a HalResource built from the link with the given name.
     *
     * @param string $name
     * @param array $variables Required if the link is templated
     * @return HalResource|HalResourceCollection
     * @todo support link collections
     */
    public function getLinkValue($name, array $variables = [])
    {
        $uri = new Uri($this->getLink($name)->getHref($variables));

        if ($uri->getHost() === null || $uri->getHost() === '') {
            $uri = $uri
                ->withScheme($this->baseUri->getScheme())
                ->withHost($this->baseUri->getHost())
                ->withPath($this->baseUri->getPath() . $uri->getPath());
        }

        return self::createFromUri($uri, $this->browser);
    }

    /**
     * @param string $name
     * @return Curie
     */
    public function getCurie($name)
    {
        if (!array_key_exists($name, $this->curies)) {
            return null;
        }

        return $this->curies[$name];
    }

    /**
     * Returns the href of curie assoc given by link.
     *
     * @param string $linkName
     * @return string
     */
    public function getCurieHref($linkName)
    {
        $link = $this->getLink($linkName);

        if ($link->getPrefix() === null || $this->getCurie($link->getPrefix()) === null) {
            return null;
        }

        return $this->getCurie($link->getPrefix())->getHref(['rel' => $link->getReference()]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Operation not available');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Operation not available');
    }

}
