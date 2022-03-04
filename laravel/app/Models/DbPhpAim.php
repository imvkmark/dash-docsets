<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbPhpAim extends Model
{
    protected $connection = 'sqlite-php-aim';

    protected $table      = 'searchIndex';
}
