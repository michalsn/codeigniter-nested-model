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

    public function testInsertValidationErrors()
    {
        $user = [
            'username'   => 'Test User',
            'company_id' => '1',
            'country_id' => '1',
            'profile'    => [
                'country' => 'United States of America and something more to violate the validation rule',
            ],
        ];

        $userModel = model(UserModel::class);
        $userModel->with('profile')->useTransactions()->insert($user);

        $this->assertArrayHasKey('country', $userModel->errors());
        $this->assertSame('The country field cannot exceed 20 characters in length.', $userModel->errors()['country']);

        $this->dontSeeInDatabase(
            'users',
            [
                'id'       => '3',
                'username' => 'Test User',
            ]
        );
    }

    public function testUpdateValidationErrors()
    {
        $user = [
            'username'   => 'Test User',
            'company_id' => '1',
            'country_id' => '1',
            'profile'    => [
                'country' => 'United States of America and something more to violate the validation rule',
            ],
        ];

        $userModel = model(UserModel::class);
        $userModel->with('profile')->useTransactions()->update(1, $user);

        $this->assertArrayHasKey('country', $userModel->errors());
        $this->assertSame('The country field cannot exceed 20 characters in length.', $userModel->errors()['country']);

        $this->dontSeeInDatabase(
            'users',
            [
                'id'       => '1',
                'username' => 'Test User',
            ]
        );
    }

    public function testDatabaseErrorsOnInsert()
    {
        $user = [
            'username'   => 'Test User 1',
            'company_id' => '1',
            'country_id' => '1',
            'profile'    => [
                'country' => 'United States of America and something more to violate the validation rule',
            ],
        ];

        $userModel = model(UserModel::class);
        $userModel->with('profile')->useTransactions()->insert($user);

        $this->assertArrayHasKey('database_error', $userModel->errors());
        $this->assertSame(
            "Duplicate entry 'Test User 1' for key 'users.username'",
            $userModel->errors()['database_error']
        );

        $this->dontSeeInDatabase(
            'users',
            [
                'id'       => '3',
                'username' => 'Test User 1',
            ]
        );
    }

    public function testDatabaseErrorsOnUpdate()
    {
        $user = [
            'username'   => 'Test User 3',
            'company_id' => '1',
            'country_id' => '11', // important
            'profile'    => [
                'user_id' => '1',
                'country' => 'United States of America and something more to violate the validation rule',
            ],
        ];

        $userModel = model(UserModel::class);
        $userModel->with('profile')->useTransactions()->update(1, $user);

        $this->assertArrayHasKey('database_error', $userModel->errors());
        $this->assertStringContainsString(
            'Cannot add or update a child row: a foreign key constraint fails',
            $userModel->errors()['database_error']
        );

        $this->dontSeeInDatabase(
            'users',
            [
                'id'       => '1',
                'username' => 'Test User 3',
            ]
        );
    }
}
