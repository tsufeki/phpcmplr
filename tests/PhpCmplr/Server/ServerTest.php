<?php

/*
 * phpcmplr
 * Copyright (C) 2016  tsufeki
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tests\PhpCmplr\Server;

use PhpCmplr\Server\Server;

class ServerUnprotected extends Server
{
    public static function getPropertyOr400($data, $property, $type = null, $default = null)
    {
        return parent::getPropertyOr400($data, $property, $type, $default);
    }
}

class ServerTest extends \PHPUnit_Framework_TestCase
{
    public function test_load_diagnostics()
    {
        $server = new Server(7373);

        $data = new \stdClass();
        $fileData = new \stdClass();
        $fileData->path = 'qaz.php';
        $fileData->contents = '<?php '."\n\n".'$a = 7 + *f("wsx");';
        $data->files = [$fileData];

        $this->assertEquals(new \stdClass(), $server->load($data));

        $data = new \stdClass();
        $data->path = 'qaz.php';

        $result = new \stdClass();
        $diagData = new \stdClass();
        $diagData->start = new \stdClass();
        $diagData->start->line = 3;
        $diagData->start->col = 10;
        $diagData->end = new \stdClass();
        $diagData->end->line = 3;
        $diagData->end->col = 10;
        $diagData->description = "Syntax error, unexpected '*'";
        $result->diagnostics = [$diagData];

        $this->assertEquals($result, $server->diagnostics($data));
    }

    public function test_getPropertyOr400_location()
    {
        $obj = new \stdClass();
        $loc = new \stdClass();
        $loc->line = 7;
        $loc->col = 12;
        $obj->loc = $loc;
        $this->assertSame([7, 12], ServerUnprotected::getPropertyOr400($obj, 'loc', 'location'));
    }
}
