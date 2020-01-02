<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements UserContract
{
    use Authenticatable;
}
