<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpOrigin extends Model
{
    protected $connection = 'sqlite-php-ori';

    protected $table      = 'searchIndex';
}
