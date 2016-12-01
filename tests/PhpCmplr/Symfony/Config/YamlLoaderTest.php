<?php

namespace Tests\PhpCmplr\Symfony\Config;

use PhpCmplr\Symfony\Config\Config;
use PhpCmplr\Symfony\Config\YamlLoader;

/**
 * @covers \PhpCmplr\Symfony\Config\YamlLoader
 */
class YamlLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $yaml = <<<'EOF'
parameters:
    qaz: aa
    wsx: 12
    edc: false
    rfv: []
    tgb: "%rfv%"

services:
    qwe:
        class: stdClass
        public: false
    asd:
        alias: qwe
    zxc: "@rty"
EOF;

        $cfg = new Config();
        $loader = new YamlLoader();
        $this->assertTrue($loader->load($yaml, $cfg));

        $this->assertSame('aa', $cfg->getParameter('qaz'));
        $this->assertSame(12, $cfg->getParameter('wsx'));
        $this->assertSame(false, $cfg->getParameter('edc'));
        $this->assertSame([], $cfg->getParameter('rfv'));
        $this->assertSame('%rfv%', $cfg->getParameter('tgb'));

        $srv = $cfg->getService('qwe');
        $this->assertSame('\\stdClass', $srv->getClass());
        $this->assertNull($srv->getAlias());
        $this->assertFalse($srv->isPublic());

        $srv = $cfg->getService('asd');
        $this->assertNull($srv->getClass());
        $this->assertSame('qwe', $srv->getAlias());
        $this->assertTrue($srv->isPublic());

        $srv = $cfg->getService('zxc');
        $this->assertNull($srv->getClass());
        $this->assertSame('rty', $srv->getAlias());
        $this->assertTrue($srv->isPublic());
    }
}
