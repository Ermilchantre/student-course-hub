<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Module;
use PDO;

class ModuleTest extends TestCase
{
    public function testCanInstantiateModule()
    {
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $this->createMock(PDO::class);
        $module = new Module($pdo);
        $this->assertInstanceOf(Module::class, $module);
    }
}
