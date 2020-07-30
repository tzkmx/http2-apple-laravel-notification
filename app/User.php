<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    const CREATED_AT = 'fec_alta';
    const UPDATED_AT = 'fec_modif';
    const DELETED_AT = 'fec_baja';

    protected $table = 'mc.users';
    protected $primaryKey = 'id_user';

    public function corp()
    {
        return $this->belongsTo(
          Corp::class,
          'corpid',
          'id_empresa'
        );
    }

    public function devices()
    {
        return $this->hasMany(
          Device::class,
            'id_user',
          'id_user'
        )
          ->where('sta_baja', '!=', 'S');
    }

    use Notifiable;
    use SoftDeletes;

    public function routeNotificationForApn($notification)
    {
        return $this->devices()
          ->where('tip_device', 'ios')
          ->get()->pluck('uid_device')->all();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
