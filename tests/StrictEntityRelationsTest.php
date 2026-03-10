<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\SeedTests;
use Tests\Support\Entities\Post;
use Tests\Support\Entities\Profile;
use Tests\Support\Entities\StrictUser;
use Tests\Support\Models\StrictUserModel;

/**
 * @internal
 */
final class StrictEntityRelationsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    protected $seed = SeedTests::class;

    public function testEagerLoadsHasOneRelationOnStrictEntity(): void
    {
        $user = model(StrictUserModel::class)->with('profile')->find(1);

        $this->assertInstanceOf(StrictUser::class, $user);
        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertSame('United States', $user->profile->country);
    }

    public function testEagerLoadsHasManyRelationOnStrictEntity(): void
    {
        $user = model(StrictUserModel::class)->with('posts')->find(1);

        $this->assertInstanceOf(StrictUser::class, $user);
        $this->assertIsArray($user->posts);
        $this->assertInstanceOf(Post::class, $user->posts[0]);
        $this->assertSame('Title 1', $user->posts[0]->title);
    }

    public function testLazyLoadsHasOneRelationOnStrictEntity(): void
    {
        $user = model(StrictUserModel::class)->find(1);

        $this->assertInstanceOf(StrictUser::class, $user);
        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertSame('United States', $user->profile->country);
    }

    public function testLazyLoadsHasManyRelationOnStrictEntity(): void
    {
        $user = model(StrictUserModel::class)->find(1);

        $this->assertInstanceOf(StrictUser::class, $user);
        $this->assertIsArray($user->posts);
        $this->assertInstanceOf(Post::class, $user->posts[0]);
        $this->assertSame('Title 1', $user->posts[0]->title);
    }
}
