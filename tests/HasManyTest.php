<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Model;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\SeedTests;
use Tests\Support\Entities\Post;
use Tests\Support\Entities\User;
use Tests\Support\Models\PostModel;
use Tests\Support\Models\UserModel;

/**
 * @internal
 */
final class HasManyTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    protected $seed = SeedTests::class;

    public function testFindHasMany()
    {
        // Load normal model
        $user = model(UserModel::class)->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->posts);
        $this->assertFalse($isset);

        // Load model with relation
        $user = model(UserModel::class)->with('posts')->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->posts);
        $this->assertTrue($isset);

        $posts = $user->posts;
        $this->assertIsArray($posts);

        $this->assertInstanceOf(Post::class, $posts[0]);

        $this->assertSame('1', $posts[0]->user_id);
        $this->assertSame('Title 1', $posts[0]->title);
    }

    public function testFindAllHasMany()
    {
        // Load normal model
        $users = model(UserModel::class)->findAll();
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertCount(2, $users);

        $isset = isset($users[0]->posts);
        $this->assertFalse($isset);

        // Load model with relation
        $users = model(UserModel::class)->with('posts')->findAll();
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertCount(2, $users);

        $isset = isset($users[0]->posts);
        $this->assertTrue($isset);

        $posts = $users[0]->posts;
        $this->assertIsArray($posts);

        $this->assertCount(5, $posts);

        $this->assertInstanceOf(Post::class, $posts[0]);

        $this->assertSame('1', $posts[0]->user_id);
        $this->assertSame('Title 1', $posts[0]->title);
    }

    public function testFindHasManyWithCondition()
    {
        // Load model with relation
        $user = model(UserModel::class)->with('posts', static function (Model $model) {
            $model->where('rating >', 3);
        })->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->posts);
        $this->assertTrue($isset);

        $posts = $user->posts;
        $this->assertIsArray($posts);

        $this->assertInstanceOf(Post::class, $posts[0]);

        $this->assertSame('1', $posts[0]->user_id);
        $this->assertSame('Title 1', $posts[0]->title);
    }

    public function testFindHasManyAsArray()
    {
        // Load model with relation
        $user = model(UserModel::class)->with('posts', static function (Model $model) {
            $model->asArray();
        })->find(1);

        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->posts);
        $this->assertTrue($isset);

        $this->assertIsArray($user->posts);

        $this->assertIsArray($user->posts[0]);

        $this->assertSame('1', $user->posts[0]['user_id']);
        $this->assertSame('Title 1', $user->posts[0]['title']);
    }

    public function testFindBelongsTo()
    {
        // Load normal model
        $post = model(PostModel::class)->find(1);
        $this->assertInstanceOf(Post::class, $post);

        $isset = isset($post->user);
        $this->assertFalse($isset);

        // Load model with relation
        $post = model(PostModel::class)->with('user')->find(1);
        $this->assertInstanceOf(Post::class, $post);

        $isset = isset($post->user);
        $this->assertTrue($isset);

        $this->assertInstanceOf(User::class, $post->user);

        $this->assertSame('1', $post->user->id);
        $this->assertSame('Test User 1', $post->user->username);
    }
}
