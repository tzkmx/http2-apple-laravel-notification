<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Corp extends Model
{
    protected $table = 'mc.corp';
    protected $primaryKey = 'id_empresa';

    use SoftDeletes;

    public function users()
    {
        return $this->hasMany(
          User::class,
          'corpid',
          'id_empresa'
        );
    }
}
