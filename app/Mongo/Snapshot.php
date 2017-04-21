<?php

namespace App\Mongo;

use Jenssegers\Mongodb\Eloquent\Model;

class Snapshot extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'snapshots';

    protected $guarded = [];
}
