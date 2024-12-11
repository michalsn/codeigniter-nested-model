<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\SeedTests;
use Tests\Support\Entities\Address;
use Tests\Support\Entities\Country;
use Tests\Support\Entities\Post;
use Tests\Support\Entities\User;
use Tests\Support\Models\CountryModel;
use Tests\Support\Models\UserModel;

/**
 * @internal
 */
final class ThroughTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    protected $seed = SeedTests::class;

    public function testFindHasOneThrough()
    {
        $user = model(UserModel::class)->with('address')->find(1);
        $this->assertInstanceOf(User::class, $user);

        $isset = isset($user->address);
        $this->assertTrue($isset);

        $this->assertInstanceOf(Address::class, $user->address);

        $this->assertSame('1943 Ashcraft Court', $user->address->street);
        $this->assertSame('San Diego', $user->address->city);
        $this->assertSame('United States', $user->address->country);
    }

    public function testFindAllHasOneThrough()
    {
        $users = model(UserModel::class)->with('address')->findAll();
        $this->assertCount(2, $users);
        $this->assertInstanceOf(User::class, $users[0]);

        $isset = isset($users[0]->address);
        $this->assertTrue($isset);

        $this->assertInstanceOf(Address::class, $users[0]->address);

        $this->assertSame('1943 Ashcraft Court', $users[0]->address->street);
        $this->assertSame('San Diego', $users[0]->address->city);
        $this->assertSame('United States', $users[0]->address->country);
    }

    public function testFindHasManyThrough()
    {
        $country = model(CountryModel::class)->with('posts')->find(1);
        $this->assertInstanceOf(Country::class, $country);

        $isset = isset($country->posts);
        $this->assertTrue($isset);

        $this->assertCount(5, $country->posts);

        $this->assertInstanceOf(Post::class, $country->posts[0]);
    }

    public function testFindAllHasManyThrough()
    {
        $countries = model(CountryModel::class)->with('posts')->findAll();
        $this->assertInstanceOf(Country::class, $countries[0]);

        $this->assertCount(2, $countries);

        $isset = isset($countries[0]->posts);
        $this->assertTrue($isset);

        $this->assertCount(5, $countries[0]->posts);
        $this->assertInstanceOf(Post::class, $countries[0]->posts[0]);

        $this->assertCount(3, $countries[1]->posts);
        $this->assertInstanceOf(Post::class, $countries[0]->posts[1]);
    }
}
