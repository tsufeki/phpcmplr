<?php

namespace Tests\PhpCmplr\Core;

use PhpCmplr\Core\Container;

/**
 * @covers \PhpCmplr\Core\Container
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testChaining()
    {
        $parent = new Container();
        $a = new \stdClass();
        $b = new \stdClass();
        $parent->set('a', $a);
        $parent->set('b', $b);

        $child = new Container($parent);
        $bb = new \stdClass();
        $c = new \stdClass();
        $child->set('b', $bb);
        $child->set('c', $c);

        $this->assertSame($a, $parent->get('a'));
        $this->assertSame($b, $parent->get('b'));
        $this->assertSame($a, $child->get('a'));
        $this->assertSame($bb, $child->get('b'));
        $this->assertSame($c, $child->get('c'));
    }
}
