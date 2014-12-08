<?php
namespace Flowpack\Hal\Client\Tests\Unit;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Flowpack.Hal.Client".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Flowpack\Hal\Client\Resource;

/**
 * ResourceTest
 */
class ResourceTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \Flowpack\Hal\Client\Resource
	 */
	protected $resource;

	protected function setUp() {
		$data = json_decode(file_get_contents(__DIR__ . '/Fixtures/Orders.json'), TRUE);
		$this->resource = new Resource($this->getMock('TYPO3\Flow\Http\Client\Browser'), new \TYPO3\Flow\Http\Uri('http://localhost'), $data);
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getPropertiesReturnsExpectedValues() {
		$expectedProperties = array(
			'currentlyProcessing' => 14,
			'shippedToday' => 20
		);

		$this->assertEquals($expectedProperties, $this->resource->getProperties());
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getReturnsExpectedValueOnExistingProperty() {
		$expectedProperties = array(
			'currentlyProcessing' => 14,
			'shippedToday' => 20
		);

		foreach ($expectedProperties as $name => $expectedValue) {
			$this->assertEquals($expectedValue, $this->resource->get($name));
		}
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getReturnsExpectedValueOnExistingEmbedded() {
		$this->assertEquals('John Doe', $this->resource->get('customer')->getProperty('name'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getWouldReturnExpectedValueOnExistingLink() {
		$data = json_decode(file_get_contents(__DIR__ . '/Fixtures/Orders.json'), TRUE);

		$resource = $this->getMock('Flowpack\Hal\Client\Resource', array('getLinkValue'), array($this->getMock('TYPO3\Flow\Http\Client\Browser'), new \TYPO3\Flow\Http\Uri('http://localhost'), $data));
		$resource->expects($this->atLeastOnce())->method('getLinkValue')->with('next');

		$resource->get('next');
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function hasReturnsTrueForExistingProperty() {
		$this->assertTrue($this->resource->has('shippedToday'));
		$this->assertTrue($this->resource->hasProperty('shippedToday'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function hasReturnsFalseForNotExistingProperty() {
		$this->assertFalse($this->resource->has('notHere'));
		$this->assertFalse($this->resource->hasProperty('notHere'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function hasReturnsTrueForExistingEmbeddedResource() {
		$this->assertTrue($this->resource->has('orders'));
		$this->assertTrue($this->resource->hasEmbedded('orders'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function hasReturnsFalseForNotExistingEmbeddedResource() {
		$this->assertFalse($this->resource->has('noOrders'));
		$this->assertFalse($this->resource->hasEmbedded('noOrders'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getEmbeddedReturnsEmbeddedResource() {
		$embeddedResource = $this->resource->getEmbedded('customer');
		$this->assertInstanceOf('Flowpack\Hal\Client\Resource', $embeddedResource);

		$this->assertEquals('John Doe', $embeddedResource->get('name'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getEmbeddedReturnsEmbeddedResourceCollection() {
		$embeddedResources = $this->resource->getEmbedded('orders');
		$this->assertInstanceOf('Flowpack\Hal\Client\ResourceCollection', $embeddedResources);
		$this->assertCount(2, $embeddedResources);

		foreach ($embeddedResources as $resource) {
			$this->assertInstanceOf('Flowpack\Hal\Client\Resource', $resource);
			$this->assertEquals('USD', $resource->get('currency'));
		}
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function hasLinkReturnsTrueForExistingLink() {
		$this->assertTrue($this->resource->hasLink('self'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function hasLinkReturnsFalseForNotExistingLink() {
		$this->assertFalse($this->resource->hasLink('nothing'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function linkCountIsReadAsExpected() {
		$this->assertEquals(5, count($this->resource->getLinks()));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getLinkReturnsExpectedLinkInstance() {
		$link = $this->resource->getLink('find');
		$this->assertEquals('find', $link->getName());
		$this->assertEquals('/orders?id=456', $link->getHref(array('id' => 456)));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getLinkReturnsNullForNotExistingLink() {
		$this->assertNull($this->resource->getLink('foobar'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getLinkValueWouldReturnExpectedValueOnExistingLink() {
		$data = json_decode(file_get_contents(__DIR__ . '/Fixtures/Orders.json'), TRUE);
		$mockBrowser = $this->getMock('TYPO3\Flow\Http\Client\Browser');
		$mockBrowser->expects($this->any())->method('sendRequest')->will($this->returnCallback(function(\TYPO3\Flow\Http\Request $request) {
			$statusCode = 200;
			$headers = array('Content-Type' => 'application/hal+json');
			switch ($request->getUri()) {
				case 'http://localhost/orders?page=2':
					$content = file_get_contents(__DIR__ . '/Fixtures/Orders2.json');
					break;
			}

			$mockResponse = $this->getMock('\TYPO3\Flow\Http\Response');
			$mockResponse->expects($this->any())->method('getStatusCode')->will($this->returnValue($statusCode));
			$mockResponse->expects($this->any())->method('getHeader')->will($this->returnCallback(function($name) use ($headers) {
				return $headers[$name];
			}));
			$mockResponse->expects($this->any())->method('getContent')->will($this->returnValue($content));

			return $mockResponse;
		}));


		$resource = new \Flowpack\Hal\Client\Resource($mockBrowser, new \TYPO3\Flow\Http\Uri('http://localhost'), $data);

		$linkValue = $resource->getLinkValue('next');

		$this->assertInstanceOf('Flowpack\Hal\Client\Resource', $linkValue);
		$this->assertEquals(14, $linkValue->get('currentlyProcessing'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getCurieReturnsExpectedCurieInstance() {
		$curie = $this->resource->getCurie('acme');
		$this->assertEquals('acme', $curie->getName());
		$this->assertEquals('http://docs.acme.com/relations/{rel}', $curie->getHref());
		$this->assertTrue($curie->isTemplated());
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getCurieReturnsNullForNotExistingCurie() {
		$this->assertNull($this->resource->getCurie('quux'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function getCurieHrefReturnsExpectedValue() {
		$this->assertEquals('http://docs.acme.com/relations/widgets', $this->resource->getCurieHref('acme:widgets'));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function embeddedsCountIsReadAsExpected() {
		$this->assertEquals(2, count($this->resource->getEmbeddeds()));
	}

	/**
	 *
	 * @test
	 * @return void
	 */
	public function arrayAccessWorksAsExpected() {
		$this->assertTrue(isset($this->resource['shippedToday']));
		$this->assertFalse(isset($this->resource['notHere']));

		$this->assertEquals(20, $this->resource['shippedToday']);
		$this->assertNull($this->resource['notHere']);
	}
}