<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testDefaultValues()
    {
        $user = new User();
        $this->assertNull($user->getId());
        $this->assertNull($user->getUsername());
        $this->assertEquals(['ROLE_ADMIN'], $user->getRoles());
        $this->assertNull($user->getPassword());
    }

    public function testSettersAndGetters()
    {
        $user = new User();
        $user->setUsername('admin')
            ->setPassword('secret')
            ->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $this->assertEquals('admin', $user->getUsername());
        $this->assertEquals('admin', $user->getUserIdentifier());
        $this->assertEquals(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        $this->assertEquals('secret', $user->getPassword());
    }

    public function testEraseCredentials()
    {
        $user = new User();
        $user->eraseCredentials();
        $this->assertTrue(true); // Just ensure no exception
    }
}
