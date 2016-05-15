<?php

namespace PhpCmplr\Completer\Reflection;

trait MemberTrait
{
    /**
     * @var int
     */
    private $accessibility = ClassLike::M_PUBLIC;

    /**
     * @var bool
     */
    private $static = false;

    /**
     * @return int
     */
    public function getAccessibility()
    {
        return $this->accessibility;
    }

    /**
     * @param int $accessibility
     *
     * @return $this
     */
    public function setAccessibility($accessibility)
    {
        $this->accessibility = $accessibility;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->accessibility === ClassLike::M_PRIVATE;
    }

    /**
     * @return bool
     */
    public function isProtected()
    {
        return $this->accessibility === ClassLike::M_PROTECTED;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->accessibility === ClassLike::M_PUBLIC;
    }

    /**
     * @return bool
     */
    public function getStatic()
    {
        return $this->static;
    }

    /**
     * @param bool $static
     *
     * @return $this
     */
    public function setStatic($static)
    {
        $this->static = $static;

        return $this;
    }
}
