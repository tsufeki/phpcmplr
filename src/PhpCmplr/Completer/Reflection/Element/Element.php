<?php

namespace PhpCmplr\Completer\Reflection\Element;

use PhpCmplr\Completer\SourceFile\Location;

abstract class Element
{
    /**
     * @var string Fully qualified name.
     */
    private $name;

    /**
     * @var Location
     */
    private $location = null;

    /**
     * Clone.
     */
    public function __clone()
    {
        if ($this->location) {
            $this->location = clone $this->location;
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     *
     * @return $this
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;

        return $this;
    }
}
