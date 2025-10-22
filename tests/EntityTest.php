<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Events\Events;
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
    protected $seed         = SeedTests::class;
    private int $queryCount = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryCount = 0;
        Events::on('DBQuery', function () {
            $this->queryCount++;
        });
    }

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

    public function testRelationQueriedOnlyOnce()
    {
        $model = model(UserModel::class);
        // Single user (without some relations)
        $data = [
            'username'   => 'Test Single User',
            'company_id' => '2',
            'country_id' => '1',
        ];

        $id = $model->insert($data);

        $user       = $model->find($id);
        $queryCount = $this->queryCount;
        $profile    = $user->profile;
        $this->assertNull($profile);

        $this->assertSame($queryCount + 1, $this->queryCount);

        $profile = $user->profile;
        $this->assertNull($profile);

        $this->assertSame($queryCount + 1, $this->queryCount);
    }
}
