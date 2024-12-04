<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Model;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\SeedTests;
use Tests\Support\Entities\Post;
use Tests\Support\Entities\Profile;
use Tests\Support\Entities\User;
use Tests\Support\Models\PostModel;
use Tests\Support\Models\UserModel;

/**
 * @internal
 */
final class ModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    protected $seed = SeedTests::class;

    public function testModelInstance(): void
    {
        $model = model(UserModel::class);
        $this->assertInstanceOf(UserModel::class, $model);
    }

    public function testFindHasOne()
    {
        // Load normal model
        $user = model(UserModel::class)->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->profile);
        $this->assertFalse($isset);

        // Load model with relation
        $user = model(UserModel::class)->with('profile')->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->profile);
        $this->assertTrue($isset);

        $this->assertInstanceOf(Profile::class, $user->profile);

        $this->assertSame('1', $user->profile->user_id);
        $this->assertSame('United States', $user->profile->country);
    }

    public function testFindHasOneAsArray()
    {
        $user = model(UserModel::class)->with('profile', static function (Model $model) {
            $model->asArray();
        })->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->profile);
        $this->assertTrue($isset);

        $this->assertIsArray($user->profile);

        $this->assertSame('1', $user->profile['user_id']);
        $this->assertSame('United States', $user->profile['country']);
    }

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

    public function testFindBelongTo()
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

    public function testUpdateHasOne()
    {
        // Load model with relation
        $user = model(UserModel::class)->with('profile')->find(1);
        $this->assertInstanceOf(User::class, $user);

        /** @var Entity $user->profile */
        $user->profile->country = 'Canada';
        $user->username         = 'Jack';

        // Save without relation
        model(UserModel::class)->save($user);

        $this->dontSeeInDatabase(
            'profiles',
            [
                'user_id' => '1',
                'country' => 'Canada',
            ]
        );

        $this->seeInDatabase(
            'users',
            [
                'id'       => '1',
                'username' => 'Jack',
            ]
        );

        // Save with relation
        $user->username = 'Bill';

        model(UserModel::class)->with('profile')->save($user);

        $this->seeInDatabase(
            'profiles',
            [
                'user_id' => '1',
                'country' => 'Canada',
            ]
        );

        $this->seeInDatabase(
            'users',
            [
                'id'       => '1',
                'username' => 'Bill',
            ]
        );
    }

    public function testUpdateHasMany()
    {
        // Load model with relation
        $user = model(UserModel::class)->with('posts')->find(1);
        $this->assertInstanceOf(User::class, $user);

        $posts = $user->posts;

        $posts[0]->title = 'Title 11';
        $posts[1]->title = 'Title 22';

        $user->username = 'Jack';
        $user->posts    = $posts;

        // Save without relation
        model(UserModel::class)->save($user);

        $this->dontSeeInDatabase(
            'posts',
            [
                'id'    => '1',
                'title' => 'Title 11',
            ]
        );

        $this->dontSeeInDatabase(
            'posts',
            [
                'id'    => '2',
                'title' => 'Title 22',
            ]
        );

        $this->seeInDatabase(
            'users',
            [
                'id'       => '1',
                'username' => 'Jack',
            ]
        );

        // Save with relation
        $user->username = 'Bill';

        model(UserModel::class)->with('posts')->save($user);

        $this->seeInDatabase(
            'posts',
            [
                'id'    => '1',
                'title' => 'Title 11',
            ]
        );

        $this->seeInDatabase(
            'posts',
            [
                'id'    => '2',
                'title' => 'Title 22',
            ]
        );

        $this->seeInDatabase(
            'users',
            [
                'id'       => '1',
                'username' => 'Bill',
            ]
        );
    }
}
