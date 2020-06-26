<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Phalcon\Incubator\Events\Tests\Unit;

use Codeception\Test\Unit;
use Phalcon\Events\ManagerInterface;
use Phalcon\Incubator\Events\Manager;

class ManagerTest extends Unit
{
    public function testImplementation(): void
    {
        $class = $this->createMock(Manager::class);

        $this->assertInstanceOf(ManagerInterface::class, $class);
    }
}
