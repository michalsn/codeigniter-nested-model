<?php

declare(strict_types=1);

namespace Tests;

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
final class HasOneTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    protected $seed = SeedTests::class;

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

    public function testFindAllHasOne()
    {
        // Load normal model
        $users = model(UserModel::class)->findAll();
        $this->assertInstanceOf(User::class, $users[0]);

        $isset = isset($users[0]->profile);
        $this->assertFalse($isset);

        // Load model with relation
        $users = model(UserModel::class)->with('profile')->findAll();
        $this->assertInstanceOf(User::class, $users[0]);

        $isset = isset($users[0]->profile);
        $this->assertTrue($isset);

        $this->assertInstanceOf(Profile::class, $users[0]->profile);

        $this->assertSame('1', $users[0]->profile->user_id);
        $this->assertSame('United States', $users[0]->profile->country);
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
}
