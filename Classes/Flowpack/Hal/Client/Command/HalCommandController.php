<?php
namespace Flowpack\Hal\Client\Command;

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
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Http\Client\Browser;

/**
 * @Flow\Scope("singleton")
 */
class HalCommandController extends CommandController {

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
	public function testCommand($uri) {
		$engine = new \TYPO3\Flow\Http\Client\CurlEngine();
		$this->browser->setRequestEngine($engine);

		$resource = \Flowpack\Hal\Client\Resource::createFromUri($uri, $this->browser);

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
