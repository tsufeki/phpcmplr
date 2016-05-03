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
        $diagData->start = 17;
        $diagData->end = 17;
        $diagData->description = "Syntax error, unexpected '*'";
        $result->diagnostics = [$diagData];

        $this->assertEquals($result, $server->diagnostics($data));
    }
}
