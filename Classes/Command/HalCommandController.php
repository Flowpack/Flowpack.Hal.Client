<?php
namespace Flowpack\Hal\Client\Command;

/*
 * This file is part of the Flowpack.Hal.Client package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\Hal\Client\HalResource;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;

/**
 * @Flow\Scope("singleton")
 */
class HalCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var Browser
     */
    protected $browser;

    /**
     * Allows to test a HAL URI.
     *
     * @param string $uri
     * @return void
     */
    public function testCommand($uri)
    {
        $engine = new CurlEngine();
        $this->browser->setRequestEngine($engine);

        $resource = HalResource::createFromUri($uri, $this->browser);

        $this->outputLine('Properties');
        foreach ($resource->getProperties() as $k => $v) {
            echo $k . ' - ' . (is_array($v) ? 'array' : $v) . PHP_EOL;
        }

        $this->outputLine('Links');
        foreach ($resource->getLinks() as $k => $v) {
            echo $k . ' - ' . (is_array($v) ? count($v) . ' items' : $v->getHref()) . PHP_EOL;
        }

        $this->outputLine('Embedded');
        foreach ($resource->getEmbeddeds() as $k => $v) {
            echo $k . ' - ' . (is_array($v) ? count($v) . ' items' : $v) . PHP_EOL;
        }

        $this->quit();
    }
}
