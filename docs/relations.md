# Relations

- [One to one](#one-to-one)
- [One to many](#one-to-many)
- [One to many inverse](#one-to-many-inverse)
- [One of many](#one-of-many)
- [Has one through](#has-one-through)
- [Has many through](#has-many-through)
- [Many to many](#many-to-many)

## One to one

A one-to-one relationship where one model is associated with exactly one instance of another model.

### Example

A User model has one Profile. Each user can have only one profile.

```php
class UserModel extends Model
{
    use HasRelations;

    // ...

    public function initialize()
    {
        $this->initRelations();
    }

    public function profile(): Relation
    {
        return $this->hasOne(ProfileModel::class);
    }
}
```

### Usage

```php
model(UserModel::class)->with('profile')->find(1);
```

## One to many

A one-to-many relationship where one model is associated with multiple instances of another model.

### Example

A User has many Posts. Each user can have multiple posts.

```php
class UserModel extends Model
{
    use HasRelations;

    // ...

    public function initialize()
    {
        $this->initRelations();
    }

    public function posts(): Relation
    {
        return $this->hasMany(PostModel::class);
    }
}
```

### Usage

```php
model(UserModel::class)->with('posts')->find(1);
```

## One to many inverse

A one-to-many inverse relationship where a model belongs to another model.

### Example

A Post belongs to a User. Each profile is associated with one specific user.

```php
class PostModel extends Model
{
    use HasRelations;

    // ...

    public function initialize()
    {
        $this->initRelations();
    }

    public function user(): Relation
    {
        return $this->belongsTo(UserModel::class);
    }
}
```

### Usage

```php
model(PostModel::class)->with('user')->find(1);
```

## One of many

A specialized type of one-to-one relationship where a parent model has multiple related records, but only one of them is considered active or relevant at any given time, based on a specific condition (e.g., the most recent, the highest priority, or the one meeting a custom criterion).

### Example

This type of relation is especially useful for scenarios where a model has many records, but you only need to retrieve one representative record from the set.

```php
class UserModel extends Model
{
    use HasRelations;

    // ...

    public function latestPost(): Relation
    {
        return $this->hasOne(PostModel::class)->latestOfMany();
    }

    public function oldestPost(): Relation
    {
        return $this->hasOne(PostModel::class)->oldestOfMany();
    }

    public function bestPost(): Relation
    {
        return $this->hasOne(PostModel::class)->ofMany('rating', OrderTypes::DESC);
    }
}
```

#### latestOfMany()

If the model uses timestamps, then we will order the result by `createdField`, otherwise by `primaryKey`.

#### oldestOfMany()

If the model uses timestamps, then we will order the result by `createdField`, otherwise by `primaryKey`.

#### ofMany()

The result will be ordered according to the specified field and order.

### Usage

```php
model(UserModel::class)->with('lastPost')->find(1);

model(UserModel::class)->with('firstPost')->find(1);

model(UserModel::class)->with('bestPost')->find(1);
```

## Has one through

A one-to-one relationship that is linked through an intermediate model. This means the parent model is related to a single record in the final model through another model acting as a bridge.

### Example

Consider an application where:

- A **User** belongs to a **Company**.
- Each **Company** has an **Address**.

You want to retrieve a User's Address without needing to manually query through the Company.

```php
class UserModel extends Model
{
    use HasRelations;

    // ...

    public function initialize()
    {
        $this->initRelations();
    }

    public function address(): Relation
    {
        return $this->hasOneThrough(
            AddressModel::class,
            CompanyModel::class,
            'address_id', // foreignKey - AddressModel
            'id', // foreignKey - CompanyModel
            'id', // primaryKey - AddressModel
            'company_id', // primaryKey - CompanyModel
        );
    }
}
```

In this case we have to specify custom `$foreignKey` and `$primaryKey` for `CompanyModel` which will have the value of `id` and `company_id` respectively.

!!! note
    We can assign custom keys for all relations.

### Usage

```php
model(UserModel::class)->with('address')->find(1);
```

## Has many through

A one-to-many relationship that is linked through an intermediate model. This means the parent model is related to multiple records in the final model through another model acting as a bridge.

### Example

Consider an application where::

- A *Country* has many *Users*.
- A *User* has many *Posts*.

You want to fetch all Posts for a Country, even though the Posts table does not directly reference the Country.

```php
class CountryModel extends Model
{
    use HasRelations;

    // ...

    public function initialize()
    {
        $this->initRelations();
    }

    public function posts(): Relation
    {
        return $this->hasManyThrough(PostModel::class, UserModel::class);
    }
}
```

### Usage

```php
model(UserModel::class)->with('posts')->find(1);
```

## Many to many

A `belongsToMany` relationship is used for many-to-many associations between two models. This relationship involves an intermediate pivot table that links records in one table to records in another.

### Example

Consider an application where:

- *Students* can enroll in multiple *Courses*.
- A *Course* can have multiple *Students*.

Since both Students and Courses can be related to each other in many ways, we use a pivot table to manage this association.

```php
class StudentModel extends Model
{
    use HasRelations;

    // ...

    public function initialize()
    {
        $this->initRelations();
    }

    public function courses(): Relation
    {
        return $this->belongsToMany(CourseModel::class);
    }
}
```
```php
class CourseModel extends Model
{
    use HasRelations;

    // ...

    public function initialize()
    {
        $this->initRelations();
    }

    public function students(): Relation
    {
        return $this->belongsToMany(StudentModel::class);
    }
}
```

### Usage

```php
model(StudentModel::class)->with('courses')->find(1);

model(CourseModel::class)->with('students')->find(1);
```
