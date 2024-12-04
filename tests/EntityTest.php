<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\SeedTests;
use Tests\Support\Entities\Post;
use Tests\Support\Entities\Profile;
use Tests\Support\Entities\User;
use Tests\Support\Models\UserModel;

/**
 * @internal
 */
final class EntityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    protected $seed = SeedTests::class;

    public function testHasOne()
    {
        $user = model(UserModel::class)->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->profile);
        $this->assertFalse($isset);

        $this->assertSame('1', $user->profile->user_id);
        $this->assertSame('United States', $user->profile->country);

        $this->assertInstanceOf(Profile::class, $user->profile);
    }

    public function testHasMany()
    {
        $user = model(UserModel::class)->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->posts);
        $this->assertFalse($isset);

        $posts = $user->posts;
        $this->assertIsArray($posts);

        $this->assertInstanceOf(Post::class, $posts[0]);

        $this->assertSame('1', $posts[0]->user_id);
        $this->assertSame('Title 1', $posts[0]->title);
    }

    public function testRelationNotExists()
    {
        $user = model(UserModel::class)->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->notExists);
        $this->assertFalse($isset);

        $notExists = $user->notExists;
        $this->assertNull($notExists);
    }
}
