# Basic usage

- [Eager loading](#eager-loading)
- [Lazy loading](#lazy-loading)

## Eager loading

First, we have to define our relations. In the below example we will have `UserModel`, which have one-to-one relation with `ProfileModel`.

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

To eagerly load the data, we have to use `with()` method and specify relation we want to use as a first parameter.

```php
model(UserModel::class)->with('profile')->findAll();
```

This will perform two queries. One for all users, and one to fetch all the profiles for these users.

## Lazy loading

Here we also have to specify our relations, just like in eager loading. The only difference is that we are required to use an `Entity` for our `$returnType`. That's because the entity will be responsible for triggering the relation request.

```php
class UserModel extends Model
{
    use HasRelations;

    // ...

    protected $returnType = User::class;

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

The entity class we use have to use `hasLazyRelations` trait.

```php
class User extends Entity
{
    use HasLazyRelations;

    // ...
}
```

With lazy loading, data is fetched on demand when we access given relation property.

```php
$users = model(UserModel::class)->findAll();
foreach ($users as $user) {
    var_dump($user->profile);
}
```

This will perform `n+1` queries. First one to get all the users and then one for each profile we want to access.
