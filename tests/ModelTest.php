<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Entity\Entity;
use CodeIgniter\Model;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\SeedTests;
use Tests\Support\Entities\Profile;
use Tests\Support\Entities\User;
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
