<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpAim extends Model
{
    protected $connection = 'sqlite-php-aim';

    protected $table      = 'searchIndex';
}
