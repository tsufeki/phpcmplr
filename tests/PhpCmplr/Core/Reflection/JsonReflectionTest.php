<?php

namespace Tests\PhpCmplr\Core\Reflection;

use PhpCmplr\Core\Container;
use PhpCmplr\Core\Reflection\JsonReflection;
use PhpCmplr\Util\FileIOInterface;
use PhpCmplr\Util\FileIO;

/**
 * @covers \PhpCmplr\Core\Reflection\JsonReflection
 */
class JsonReflectionTest extends \PHPUnit_Framework_TestCase
{
    private function prepare($dataString)
    {
        $container = new Container();
        $io = $this->createMock(FileIOInterface::class);
        $io
            ->method('read')
            ->willReturn($dataString);
        $container->set('io', $io);
        return new JsonReflection($container, '/qaz.json');
    }

    public function test_getFunctions()
    {
        $jr = $this->prepare('{
            "functions": [
                {
                    "kind": "function",
                    "name": "ff",
                    "return_type": "int",
                    "return_by_ref": false,
                    "params": [
                        {
                            "name": "$a",
                            "type": "string",
                            "optional": true,
                            "by_ref": false,
                            "variadic": false
                        }
                    ]
                },
                {
                    "kind": "alias",
                    "name": "gg",
                    "aliased_name": "ff"
                }
            ],
            "classes": [],
            "interfaces": [],
            "constants": []
        }');

        $fun = $jr->getFunctions();
        $this->assertCount(2, $fun);

        $this->assertSame('\\ff', $fun[0]->getName());
        $this->assertSame('int', $fun[0]->getDocReturnType()->toString());
        $this->assertFalse($fun[0]->getReturnByRef());
        $this->assertCount(1, $fun[0]->getParams());
        $param = $fun[0]->getParams()[0];
        $this->assertSame('$a', $param->getName());
        $this->assertSame('string', $param->getDocType()->toString());
        $this->assertTrue($param->isOptional());
        $this->assertFalse($param->isByRef());
        $this->assertFalse($param->isVariadic());

        $this->assertSame('\\gg', $fun[1]->getName());
        $this->assertSame('int', $fun[1]->getDocReturnType()->toString());
        $this->assertFalse($fun[1]->getReturnByRef());
        $this->assertCount(1, $fun[1]->getParams());
        $param = $fun[1]->getParams()[0];
        $this->assertSame('$a', $param->getName());
        $this->assertSame('string', $param->getDocType()->toString());
        $this->assertTrue($param->isOptional());
        $this->assertFalse($param->isByRef());
        $this->assertFalse($param->isVariadic());
    }

    public function test_stdlib()
    {
        $container = new Container();
        $container->set('io', new FileIO());
        $jr = new JsonReflection($container, __DIR__ . '/../../../../data/stdlib.json');
        $this->assertGreaterThan(0, count($jr->getFunctions()));
    }
}
