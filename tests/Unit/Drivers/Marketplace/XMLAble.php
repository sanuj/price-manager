<?php

namespace Tests\Unit\Drivers\Marketplace;

class XMLAble
{
    protected $contents;

    /**
     * XMLAble constructor.
     *
     * @param $contents
     */
    public function __construct($contents)
    {
        $this->contents = $contents;
    }

    public function toXML()
    {
        return $this->contents;
    }
}