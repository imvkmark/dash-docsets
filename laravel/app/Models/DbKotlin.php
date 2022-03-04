<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbKotlin extends Model
{
    protected $connection = 'sqlite-kotlin';

    protected $table      = 'searchIndex';
}
