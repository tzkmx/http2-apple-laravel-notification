<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'mc.devicesdet';
    protected $primaryKey = 'id_devicedet';

    public function user()
    {
        return $this->belongsTo(
          User::class,
          'id_user',
          'id_user'
        );
    }
}
