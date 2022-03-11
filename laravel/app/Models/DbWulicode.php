<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbWulicode extends Model
{
    protected $connection = 'sqlite-wulicode';

    protected $table      = 'searchIndex';
}
