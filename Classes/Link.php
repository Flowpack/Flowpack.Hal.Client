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
class Link extends AbstractLink
{
    /**
     * @var string
     * @see http://www.w3.org/TR/curie/#s_syntax
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $reference;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $deprecation;

    /**
     * @var string
     */
    protected $profile;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $hreflang;

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        if ($this->name !== null && strpos($this->name, ':') !== false) {
            list($this->prefix, $this->reference) = explode(':', $this->name, 2);
        }

        $this->type = isset($data['type']) ? $data['type'] : null;
        $this->deprecation = isset($data['deprecation']) ? $data['deprecation'] : null;
        $this->profile = isset($data['profile']) ? $data['profile'] : null;
        $this->title = isset($data['title']) ? $data['title'] : null;
        $this->hreflang = isset($data['hreflang']) ? $data['hreflang'] : null;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDeprecation()
    {
        return $this->deprecation;
    }

    /**
     * @return string
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getHreflang()
    {
        return $this->hreflang;
    }
}

