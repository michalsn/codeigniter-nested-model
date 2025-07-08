<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Model;
use CodeIgniter\Test\CIUnitTestCase;
use Michalsn\CodeIgniterNestedModel\Traits\HasRelations;
use ReflectionClass;
use Tests\Support\Models\UuidCommentModel;
use Tests\Support\Models\UuidUserModel;

/**
 * @internal
 */
final class TransformRelationIdsTest extends CIUnitTestCase
{
    public function testSpecificTransformationIsApplied(): void
    {
        $model = new UuidUserModel();

        $reflection = new ReflectionClass($model);
        $method     = $reflection->getMethod('transformRelationIds');

        // Test profile relation (has specific transform method)
        $result = $method->invoke($model, ['123e4567-e89b-12d3-a456-426614174000'], 'profile');

        // Should apply transformProfileRelationIds() - converts to binary
        $expected = [hex2bin('123e4567e89b12d3a456426614174000')];
        $this->assertSame($expected, $result);
    }

    public function testSpecificTransformationForPostsRelation(): void
    {
        $model = new UuidUserModel();

        $reflection = new ReflectionClass($model);
        $method     = $reflection->getMethod('transformRelationIds');

        // Test posts relation (has specific transform method)
        $result = $method->invoke($model, ['123e4567-e89b-12d3-a456-426614174000'], 'posts');

        // Should apply transformPostsRelationIds() - converts to uppercase
        $expected = ['123E4567-E89B-12D3-A456-426614174000'];
        $this->assertSame($expected, $result);
    }

    public function testNoTransformationWhenNoMethodExists(): void
    {
        // Create a model without any transform methods
        $model = new class () extends Model {
            use HasRelations;

            protected $table = 'test_table';
        };

        $reflection = new ReflectionClass($model);
        $method     = $reflection->getMethod('transformRelationIds');

        // Test that IDs are returned unchanged
        $result = $method->invoke($model, ['123', '456'], 'someRelation');

        $expected = ['123', '456'];
        $this->assertSame($expected, $result);
    }

    public function testTransformationWithMultipleIds(): void
    {
        $model = new UuidUserModel();

        $reflection = new ReflectionClass($model);
        $method     = $reflection->getMethod('transformRelationIds');

        // Test with multiple UUIDs
        $ids = [
            '123e4567-e89b-12d3-a456-426614174000',
            '987fcdeb-51a2-43d1-b789-123456789abc',
        ];

        $result = $method->invoke($model, $ids, 'profile');

        $expected = [
            hex2bin('123e4567e89b12d3a456426614174000'),
            hex2bin('987fcdeb51a243d1b789123456789abc'),
        ];

        $this->assertSame($expected, $result);
    }

    public function testTransformationWithEmptyArray(): void
    {
        $model = new UuidUserModel();

        $reflection = new ReflectionClass($model);
        $method     = $reflection->getMethod('transformRelationIds');

        // Test with empty array
        $result = $method->invoke($model, [], 'profile');

        $this->assertSame([], $result);
    }

    public function testNestedRelationTransformations(): void
    {
        $commentModel = new UuidCommentModel();

        $reflection = new ReflectionClass($commentModel);
        $method     = $reflection->getMethod('transformRelationIds');

        // Test user relation transformation
        $result   = $method->invoke($commentModel, ['123e4567-e89b-12d3-a456-426614174000'], 'user');
        $expected = [hex2bin('123e4567e89b12d3a456426614174000')];
        $this->assertSame($expected, $result);

        // Test post relation transformation
        $result   = $method->invoke($commentModel, ['987fcdeb-51a2-43d1-b789-123456789abc'], 'post');
        $expected = [hex2bin('987fcdeb51a243d1b789123456789abc')];
        $this->assertSame($expected, $result);
    }

    public function testTransformationPreservesArrayKeys(): void
    {
        $model = new UuidUserModel();

        $reflection = new ReflectionClass($model);
        $method     = $reflection->getMethod('transformRelationIds');

        // Test that array values are transformed, but structure is preserved
        $ids = [
            '123e4567-e89b-12d3-a456-426614174000',
            '987fcdeb-51a2-43d1-b789-123456789abc',
        ];

        $result = $method->invoke($model, $ids, 'profile');

        // Should have same number of elements
        $this->assertCount(2, $result);

        // Should be array with sequential keys
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
    }

    public function testActualRelationLoading(): void
    {
        $model      = new UuidUserModel();
        $reflection = new ReflectionClass($model);
        $method1    = $reflection->getMethod('transformProfileRelationIds');
        $method2    = $reflection->getMethod('transformPostsRelationIds');

        // Test actual transformation logic
        $profileIds = $method1->invoke($model, ['123e4567-e89b-12d3-a456-426614174000']);
        $this->assertSame([hex2bin('123e4567e89b12d3a456426614174000')], $profileIds);

        $postIds = $method2->invoke($model, ['test-uuid']);
        $this->assertSame(['TEST-UUID'], $postIds);
    }
}
