# CodeIgniter Nested Model

Dead simple nested model relations for CodeIgniter 4 framework. Relations are eager loaded. Each relation is one additional database query.

### Example

```php
// app/Models/UserModel.php
<?php

namespace App\Models;

use CodeIgniter\Model;
use Michalsn\CodeIgniterNestedModel\NestedModelTrait;

class UserModel extends Model
{
    use NestedModelTrait;
    
    ... 
    
    protected $relations = [
        // one avatar - relation type, model, foreign key, local key
        'avatar' => ['hasOne', AvatarModel::class]//, 'user_id', 'id'],
        // many social links - relation type, model, foreign key, local key
        'links'  => ['hasMany', LinkModel::class]//, 'user_id', 'id'],
    ];
}
```
```php
// app/Config/Routes.php
<?php

...

$routes->get('nested', static function () {

    // get all users with avatar and links
    d(model(UserModel::class)->with('avatar')->with('links')->findAll());

    // get user with id = 2 and all links
    d(model(UserModel::class)->with('links')->find(2));

    // get user with id = 2 and links with type 'test'
    d(model(UserModel::class)->with('links', static function () {
        return model(LinkModel::class)->where('type', 'test');
    })->find(2));

});

...
```
