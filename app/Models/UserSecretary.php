<?php

namespace App\Models;

/**
 * Class UserSecretary
 * @package App\Models
 * @version March 10, 2020, 2:24 am UTC
 *
 * @property \App\Models\User user
 * @property \App\Models\User user
 * @property integer user_id
 * @property integer secretary_id
 * @property integer secratry_type_id
 */
class UserSecretary extends Model
{

    public $table = 'users_secretaries';
    
    const STATUS_PUBLISHED = 1;

    public $fillable = [
        'user_id',
        'secretary_id',
        'secratry_type_id'
    ];

    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function secretary()
    {
        return $this->hasMany(User::class, 'secretary_id');
    }

}
