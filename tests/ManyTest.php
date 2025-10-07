<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\SeedTests;
use Tests\Support\Entities\Course;
use Tests\Support\Entities\Student;
use Tests\Support\Models\CourseModel;
use Tests\Support\Models\StudentModel;

/**
 * @internal
 */
final class ManyTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace;
    protected $seed = SeedTests::class;

    public function testFindManyCourses()
    {
        $student = model(StudentModel::class)->with('courses')->find(1);
        $this->assertInstanceOf(Student::class, $student);

        $isset = isset($student->courses);
        $this->assertTrue($isset);

        $this->assertCount(3, $student->courses);

        $courses = $student->courses;

        $this->assertInstanceOf(Course::class, $courses[0]);
        $this->assertSame('Baking for dummies', $courses[0]->name);
        $this->assertFalse($courses[0]->hasChanged());
    }

    public function testFindAllManyCourses()
    {
        $students = model(StudentModel::class)->with('courses')->findAll();
        $this->assertCount(3, $students);
        $this->assertInstanceOf(Student::class, $students[0]);

        $this->assertCount(1, $students[1]->courses);

        $courses = $students[1]->courses;

        $this->assertInstanceOf(Course::class, $courses[0]);
        $this->assertSame('PHP is not death', $courses[0]->name);
        $this->assertFalse($courses[0]->hasChanged());
    }

    public function testFindManyStudents()
    {
        $course = model(CourseModel::class)->with('students')->find(1);
        $this->assertInstanceOf(Course::class, $course);

        $isset = isset($course->students);
        $this->assertTrue($isset);

        $this->assertCount(1, $course->students);

        $students = $course->students;

        $this->assertInstanceOf(Student::class, $students[0]);
        $this->assertSame('Joe', $students[0]->firstname);
        $this->assertFalse($students[0]->hasChanged());
    }

    public function testFindAllManyStudents()
    {
        $courses = model(CourseModel::class)->with('students')->findAll();
        $this->assertCount(5, $courses);
        $this->assertInstanceOf(Course::class, $courses[1]);

        $this->assertCount(2, $courses[1]->students);

        $students = $courses[1]->students;

        $this->assertInstanceOf(Student::class, $students[1]);
        $this->assertSame('Elizabeth', $students[1]->firstname);
        $this->assertFalse($students[0]->hasChanged());
    }
}
