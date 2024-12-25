<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Michalsn\CodeIgniterNestedModel\Exceptions\NestedModelException;
use Tests\Support\Database\Seeds\SeedTests;
use Tests\Support\Models\UserModel;

/**
 * @internal
 */
final class ExceptionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    protected $seed = SeedTests::class;

    public function testRelationNotDefined(): void
    {
        $this->expectException(NestedModelException::class);
        $this->expectExceptionMessage('Relation "notExist" is not defined.');

        model(UserModel::class)->with('notExist')->find(1);
    }

    public function testMissingReturnType(): void
    {
        $this->expectException(NestedModelException::class);
        $this->expectExceptionMessage('Method "missingReturnType()" is missing a required return type declaration.');

        model(UserModel::class)->with('missingReturnType')->find(1);
    }

    public function testIncorrectReturnType(): void
    {
        $this->expectException(NestedModelException::class);
        $this->expectExceptionMessage('Method "incorrectReturnType()" returned an incorrect type.');

        model(UserModel::class)->with('incorrectReturnType')->find(1);
    }

    public function testParentRelationNotDeclared(): void
    {
        $this->expectException(NestedModelException::class);
        $this->expectExceptionMessage('Parent relation "posts" has not been declared yet.');

        model(UserModel::class)->with('posts.something')->find(1);
    }

    public function testMethodNotSupported(): void
    {
        $this->expectException(NestedModelException::class);
        $this->expectExceptionMessage('This method is not supported for the "hasMany" relation.');

        model(UserModel::class)->posts()->latestOfMany();
    }

    public function testNotValidWriteRelation(): void
    {
        $this->expectException(NestedModelException::class);
        $this->expectExceptionMessage('This type of relation does not support write.');

        model(UserModel::class)->with('address')->insert(['what' => 'ever']);
    }
}
