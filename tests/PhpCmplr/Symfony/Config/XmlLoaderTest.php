<?php

namespace Tests\PhpCmplr\Symfony\Config;

use PhpCmplr\Symfony\Config\Config;
use PhpCmplr\Symfony\Config\XmlLoader;

/**
 * @covers \PhpCmplr\Symfony\Config\XmlLoader
 */
class XmlLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $xml = <<<'EOF'
<?xml version="1.0" ?>

<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <parameters>
        <parameter key="qaz">aa</parameter>
        <parameter key="wsx">12</parameter>
        <parameter key="edc">false</parameter>
        <parameter key="rfv" type="collection" />
        <parameter key="tgb">%rfv%</parameter>
        <parameter key="yhn" type="string">111</parameter>
        <parameter key="ujm" type="collection">
            <parameter>bbb</parameter>
        </parameter>
    </parameters>

    <services>
        <service id="qwe" class="stdClass" public="false" />
        <service id="asd" alias="qwe"></service>
        <service id="zxc" alias="rty" />
    </services>
</container>
EOF;

        $cfg = new Config();
        $loader = new XmlLoader();
        $this->assertTrue($loader->load($xml, $cfg));

        $this->assertSame('aa', $cfg->getParameter('qaz'));
        $this->assertSame(12, $cfg->getParameter('wsx'));
        $this->assertSame(false, $cfg->getParameter('edc'));
        $this->assertSame([], $cfg->getParameter('rfv'));
        $this->assertSame('%rfv%', $cfg->getParameter('tgb'));
        $this->assertSame('111', $cfg->getParameter('yhn'));
        $this->assertSame(['bbb'], $cfg->getParameter('ujm'));

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
