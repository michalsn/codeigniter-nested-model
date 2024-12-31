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

    protected function initialize()
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

The data for the relation will be available under the relation name, in this case `$user->profile`. Data format will respect the `$returnType` set in the `ProfileModel` class.

!!! note

    You can still use your model as usual. If you omit the `with()` part, your model will work like a normal model.

### Deep relations

We can also call deep relations. This will query all posts for user and then all the comments for posts:

```php
model(UserModel::class)->with('posts')->with('posts.comments')->find(1);
```

The `'posts.comments'` relation mean that we will be looking for `comments` relation in the `PostModel` class.

We can go even further and get only comments that were created by user we're looking for:

```php
model(UserModel::class)->with('posts')->with('posts.comments', static function (Model $model) {
    $model->where('comments.user_id', 1);
})->find(1);
```

This will again query all posts for user but then will get only comments for posts that were created by user with ID `1`.

### Writing with relation

For simple relations like `hasOne` or `hasMany`, you can also save the entire object that contains relations inside.

So this is also possible:

```php
$userModel = model(UserModel::class);
$user      = $userModel->with('profile')->find(1);

$user->profile->favorite_pet = 'Cat';

$userModel->with('profile')->save($user);
```

!!! note

    If you use validation in the model, you should use a `useTransactions()` method to make sure that if something goes wrong, all changes are rolled back.

    ```php
    $userModel->with('profile')->useTransactions()->save($user);
    ```

    In general, if you are saving an object with a relation, you should always use this method.

## Lazy loading

Here we also have to specify our relations, just like in eager loading. The only difference is that we are required to use an `Entity` for our `$returnType`. That's because the entity will be responsible for triggering the relation request.

```php
class UserModel extends Model
{
    use HasRelations;

    // ...

    protected $returnType = User::class;

    // ...

    protected function initialize()
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
