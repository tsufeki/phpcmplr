<?php

namespace Tests\PhpCmplr\Symfony\Config;

/**
 * @covers \PhpCmplr\Symfony\Config\Config
 * @covers \PhpCmplr\Symfony\Config\Service
 */
use PhpCmplr\Symfony\Config\Config;
use PhpCmplr\Symfony\Config\Service;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function test_resolve()
    {
        $cfg = new Config();
        $cfg->addParameter('qaz', 'aaaa');
        $cfg->addParameter('wsx', 'bb');
        $cfg->addParameter('edc', 42);
        $cfg->addParameter('rfv', 'rr%wsx%');
        $cfg->addParameter('tgb', ['%wsx%']);
        $cfg->addParameter('yhn', ['k' => 1, 'm' => 'z%rfv%z%ujm%']);
        $cfg->addParameter('ujm', '%qaz%');
        $cfg->addService((new Service('abc'))->setClass('stdClass'));
        $cfg->addService((new Service('def'))->setClass('\A%qaz%'));
        $cfg->resolve();

        $this->assertSame('aaaa', $cfg->getParameter('qaz'));
        $this->assertSame('bb', $cfg->getParameter('wsx'));
        $this->assertSame(42, $cfg->getParameter('edc'));
        $this->assertSame('rrbb', $cfg->getParameter('rfv'));
        $this->assertSame(['bb'], $cfg->getParameter('tgb'));
        $this->assertSame(['k' => 1, 'm' => 'zrrbbzaaaa'], $cfg->getParameter('yhn'));
        $this->assertSame('aaaa', $cfg->getParameter('ujm'));
        $this->assertSame('\stdClass', $cfg->getService('abc')->getClass());
        $this->assertSame('\Aaaaa', $cfg->getService('def')->getClass());

        $this->assertNull($cfg->getParameter('non_existent'));
        $this->assertNull($cfg->getService('non_existent'));
    }

    public function test_resolve_infiniteLoop()
    {
        $cfg = new Config();
        $cfg->addParameter('qaz', '%wsx%');
        $cfg->addParameter('wsx', '%qaz%');
        $cfg->resolve();

        $this->assertSame($cfg->getParameter('qaz'), '');
        $this->assertSame($cfg->getParameter('wsx'), '');
    }

    public function test_resolve_arraySubstitution()
    {
        $cfg = new Config();
        $cfg->addParameter('qaz', []);
        $cfg->addParameter('wsx', '%qaz%');
        $cfg->resolve();

        $this->assertSame($cfg->getParameter('qaz'), []);
        $this->assertSame($cfg->getParameter('wsx'), '');
    }

    public function test_getPublicServices()
    {
        $cfg = new Config();
        $services = [
            (new Service('a'))->setPublic(true),
            (new Service('b'))->setPublic(false),
            (new Service('c'))->setPublic(true),
            (new Service('d'))->setPublic(false),
        ];
        foreach ($services as $service) {
            $cfg->addService($service);
        }

        $this->assertSame([$services[0], $services[2]], $cfg->getPublicServices());
    }
}
