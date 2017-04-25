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

/**
 * Link
 */
class AbstractLink
{

    /**
     * @var string
     */
    protected $href;

    /**
     * @var boolean
     */
    protected $templated = false;

    /**
     * @var string
     */
    protected $name;

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (!isset($data['href'])) {
            throw new \RuntimeException('Property href must be set.', 1410345835);
        }

        $this->href = $data['href'];
        $this->templated = isset($data['templated']) ? (boolean)$data['templated'] : false;
        $this->name = isset($data['name']) ? $data['name'] : null;
    }

    /**
     * @return NULL|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the href.
     *
     * @param array $variables Required if the link is templated
     * @return NULL|string
     * @throws \RuntimeException When call with property "href" empty and sets variables
     */
    public function getHref(array $variables = [])
    {
        if (!empty($variables)) {
            return $this->prepareUrl($variables);
        }

        return $this->href;
    }

    /**
     * @return boolean
     */
    public function isTemplated()
    {
        return $this->templated;
    }

    /**
     * Prepare the url with variables.
     *
     * @param array $variables Required if the link is templated
     * @return string
     * @todo properly support URI Templates
     */
    private function prepareUrl(array $variables = [])
    {
        if (!$this->templated) {
            return $this->href;
        }

        return \Neos\Flow\Http\UriTemplate::expand($this->href, $variables);
    }
}

