<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\SeedTests;
use Tests\Support\Entities\Post;
use Tests\Support\Entities\User;
use Tests\Support\Models\UserModel;

/**
 * @internal
 */
final class OfManyTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    protected $seed = SeedTests::class;

    public function testFindLatestOfMany()
    {
        $user = model(UserModel::class)->with('latestPost')->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->latestPost);
        $this->assertTrue($isset);

        $this->assertInstanceOf(Post::class, $user->latestPost);
        $this->assertSame('Title latest', $user->latestPost->title);
    }

    public function testFindOldestOfMany()
    {
        $user = model(UserModel::class)->with('oldestPost')->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->oldestPost);
        $this->assertTrue($isset);

        $this->assertInstanceOf(Post::class, $user->oldestPost);
        $this->assertSame('Title oldest', $user->oldestPost->title);
    }

    public function testFindOfMany()
    {
        $user = model(UserModel::class)->with('bestPost')->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->bestPost);
        $this->assertTrue($isset);

        $this->assertInstanceOf(Post::class, $user->bestPost);
        $this->assertSame('Title 3', $user->bestPost->title);
        $this->assertSame('5', $user->bestPost->rating);
    }

    public function testFindOfManyForManyResults()
    {
        $users = model(UserModel::class)->with('bestPost')->findAll();
        $user  = $users[0];
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->bestPost);
        $this->assertTrue($isset);

        $this->assertInstanceOf(Post::class, $user->bestPost);
        $this->assertSame('Title 3', $user->bestPost->title);
        $this->assertSame('5', $user->bestPost->rating);
        $this->assertSame('1', $user->bestPost->user_id);

        $user = $users[1];
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->bestPost);
        $this->assertTrue($isset);

        $this->assertInstanceOf(Post::class, $user->bestPost);
        $this->assertSame('Title 5', $user->bestPost->title);
        $this->assertSame('5', $user->bestPost->rating);
        $this->assertSame('2', $user->bestPost->user_id);
    }
}
