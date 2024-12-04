# Relations

- [One-to-one](#one-to-one)
- [One-to-many](#one-to-many)
- [One-to-many inverse](#one-to-many-inverse)
- [One-of-many](#one-of-many)

## One-to-one

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

## One-to-many

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

## One-to-many inverse

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
        return $this->belongTo(UserModel::class);
    }
}
```

### Usage

```php
model(PostModel::class)->with('user')->find(1);
```

## One-of-many

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
