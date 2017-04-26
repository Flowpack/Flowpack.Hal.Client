<?php
namespace Flowpack\Hal\Client\Tests\Unit;

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
use Flowpack\Hal\Client\HalResourceCollection;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;

/**
 * ResourceTest
 */
class ResourceTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @var Resource
     */
    protected $resource;

    protected function setUp()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/Fixtures/Orders.json'), true);
        $this->resource = new HalResource($this->createMock(Browser::class), new Uri('http://localhost'), $data);
    }

    /**
     * @test
     * @return void
     */
    public function getPropertiesReturnsExpectedValues()
    {
        $expectedProperties = [
            'currentlyProcessing' => 14,
            'shippedToday' => 20
        ];

        $this->assertEquals($expectedProperties, $this->resource->getProperties());
    }

    /**
     * @test
     * @return void
     */
    public function getReturnsExpectedValueOnExistingProperty()
    {
        $expectedProperties = [
            'currentlyProcessing' => 14,
            'shippedToday' => 20
        ];

        foreach ($expectedProperties as $name => $expectedValue) {
            $this->assertEquals($expectedValue, $this->resource->get($name));
        }
    }

    /**
     * @test
     * @return void
     */
    public function getReturnsExpectedValueOnExistingEmbedded()
    {
        $this->assertEquals('John Doe', $this->resource->get('customer')->getProperty('name'));
    }

    /**
     * @test
     * @return void
     */
    public function getWouldReturnExpectedValueOnExistingLink()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/Fixtures/Orders.json'), true);

        $resource = $this->getMockBuilder(HalResource::class)
            ->setMethods(['getLinkValue'])
            ->setConstructorArgs([$this->createMock(Browser::class), new Uri('http://localhost'), $data])
            ->getMock();
        $resource->expects($this->atLeastOnce())->method('getLinkValue')->with('next');

        $resource->get('next');
    }

    /**
     * @test
     * @return void
     */
    public function hasReturnsTrueForExistingProperty()
    {
        $this->assertTrue($this->resource->has('shippedToday'));
        $this->assertTrue($this->resource->hasProperty('shippedToday'));
    }

    /**
     * @test
     * @return void
     */
    public function hasReturnsFalseForNotExistingProperty()
    {
        $this->assertFalse($this->resource->has('notHere'));
        $this->assertFalse($this->resource->hasProperty('notHere'));
    }

    /**
     * @test
     * @return void
     */
    public function hasReturnsTrueForExistingEmbeddedResource()
    {
        $this->assertTrue($this->resource->has('orders'));
        $this->assertTrue($this->resource->hasEmbedded('orders'));
    }

    /**
     * @test
     * @return void
     */
    public function hasReturnsFalseForNotExistingEmbeddedResource()
    {
        $this->assertFalse($this->resource->has('noOrders'));
        $this->assertFalse($this->resource->hasEmbedded('noOrders'));
    }

    /**
     * @test
     * @return void
     */
    public function getEmbeddedReturnsEmbeddedResource()
    {
        $embeddedResource = $this->resource->getEmbedded('customer');
        $this->assertInstanceOf(HalResource::class, $embeddedResource);

        $this->assertEquals('John Doe', $embeddedResource->get('name'));
    }

    /**
     * @test
     * @return void
     */
    public function getEmbeddedReturnsEmbeddedResourceCollection()
    {
        $embeddedResources = $this->resource->getEmbedded('orders');
        $this->assertInstanceOf(HalResourceCollection::class, $embeddedResources);
        $this->assertCount(2, $embeddedResources);

        foreach ($embeddedResources as $resource) {
            $this->assertInstanceOf(HalResource::class, $resource);
            $this->assertEquals('USD', $resource->get('currency'));
        }
    }

    /**
     * @test
     * @return void
     */
    public function hasLinkReturnsTrueForExistingLink()
    {
        $this->assertTrue($this->resource->hasLink('self'));
    }

    /**
     * @test
     * @return void
     */
    public function hasLinkReturnsFalseForNotExistingLink()
    {
        $this->assertFalse($this->resource->hasLink('nothing'));
    }

    /**
     * @test
     * @return void
     */
    public function linkCountIsReadAsExpected()
    {
        $this->assertEquals(5, count($this->resource->getLinks()));
    }

    /**
     * @test
     * @return void
     */
    public function getLinkReturnsExpectedLinkInstance()
    {
        $link = $this->resource->getLink('find');
        $this->assertEquals('find', $link->getName());
        $this->assertEquals('/orders?id=456', $link->getHref(['id' => 456]));
    }

    /**
     * @test
     * @return void
     */
    public function getLinkReturnsNullForNotExistingLink()
    {
        $this->assertNull($this->resource->getLink('foobar'));
    }

    /**
     * @test
     * @return void
     */
    public function getLinkValueWouldReturnExpectedValueOnExistingLink()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/Fixtures/Orders.json'), true);
        $mockBrowser = $this->createMock(\Neos\Flow\Http\Client\Browser::class);
        $mockBrowser->expects($this->any())->method('sendRequest')->will($this->returnCallback(function (Request $request) {
            $statusCode = 200;
            $headers = ['Content-Type' => 'application/hal+json'];
            switch ($request->getUri()) {
                case 'http://localhost/orders?page=2':
                    $content = file_get_contents(__DIR__ . '/Fixtures/Orders2.json');
                break;
            }

            $mockResponse = $this->createMock(\Neos\Flow\Http\Response::class);
            $mockResponse->expects($this->any())->method('getStatusCode')->will($this->returnValue($statusCode));
            $mockResponse->expects($this->any())->method('getHeader')->will($this->returnCallback(function ($name) use ($headers) {
                return $headers[$name];
            }));
            $mockResponse->expects($this->any())->method('getContent')->will($this->returnValue($content));

            return $mockResponse;
        }));

        $resource = new HalResource($mockBrowser, new Uri('http://localhost'), $data);

        $linkValue = $resource->getLinkValue('next');

        $this->assertInstanceOf(HalResource::class, $linkValue);
        $this->assertEquals(14, $linkValue->get('currentlyProcessing'));
    }

    /**
     * @test
     * @return void
     */
    public function getCurieReturnsExpectedCurieInstance()
    {
        $curie = $this->resource->getCurie('acme');
        $this->assertEquals('acme', $curie->getName());
        $this->assertEquals('http://docs.acme.com/relations/{rel}', $curie->getHref());
        $this->assertTrue($curie->isTemplated());
    }

    /**
     * @test
     * @return void
     */
    public function getCurieReturnsNullForNotExistingCurie()
    {
        $this->assertNull($this->resource->getCurie('quux'));
    }

    /**
     * @test
     * @return void
     */
    public function getCurieHrefReturnsExpectedValue()
    {
        $this->assertEquals('http://docs.acme.com/relations/widgets', $this->resource->getCurieHref('acme:widgets'));
    }

    /**
     * @test
     * @return void
     */
    public function embeddedsCountIsReadAsExpected()
    {
        $this->assertEquals(2, count($this->resource->getEmbeddeds()));
    }

    /**
     * @test
     * @return void
     */
    public function arrayAccessWorksAsExpected()
    {
        $this->assertTrue(isset($this->resource['shippedToday']));
        $this->assertFalse(isset($this->resource['notHere']));

        $this->assertEquals(20, $this->resource['shippedToday']);
        $this->assertNull($this->resource['notHere']);
    }
}
