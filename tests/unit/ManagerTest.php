<?php

declare(strict_types = 1);

namespace Phalcon\Incubator\Events\Tests\Unit;

use Phalcon\Events\ManagerInterface;
use Phalcon\Incubator\Events\Manager;

/**
 * \Phalcon\Incubator\Events\Tests\Unit\ManagerTest
 * Tests for Phalcon\Incubator\Events\Manager component
 *
 * @copyright (c) 2011-2016 Phalcon Team
 * @author        Aziz Muzafarov <bemyslavedarlin@gmail.com>
 * @link          http://phalconphp.com/
 * @package       Phalcon\Incubator\Events\Tests\Unit
 * @group         Events
 *
 * The contents of this file are subject to the New BSD License that is
 * bundled with this package in the file docs/LICENSE.txt
 *
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world-wide-web, please send an email to license@phalconphp.com
 * so that we can send you a copy immediately.
 */
class ManagerTest extends UnitTester
{
    public function testImplementation(): void
    {
        $class = $this->createMock(Manager::class);

        $this->assertInstanceOf(ManagerInterface::class, $class);
    }
}
