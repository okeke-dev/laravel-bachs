<?php

namespace OkekeDev\Bachs\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use OkekeDev\Bachs\Concerns\Billsable;

class BillableUser extends Model
{
    use Billsable;

    protected $table = 'users';

    protected $guarded = [];
}
