<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Corp extends Model
{
    const CREATED_AT = 'fec_alta';
    const UPDATED_AT = 'fec_modif';
    const DELETED_AT = 'fec_baja';

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
