<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbPhpOrigin extends Model
{
    protected $connection = 'sqlite-php-ori';

    protected $table      = 'searchIndex';
}
