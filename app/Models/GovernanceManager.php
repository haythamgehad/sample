<?php

namespace App\Models;


/**
 * Class GovernanceManager
 * @package App\Models
 * @version March 10, 2020, 2:54 pm UTC
 *
 */
class GovernanceManager extends Model
{

    public $table = 'governance_managers';
    

    public $fillable = [
        'committee_id',
        'user_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function committee()
    {
        return $this->hasOne(Committee::class, 'id', 'committee_id');
    }

    /**
    * @return \Illuminate\Database\Eloquent\Relations\HasOne
    **/
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

}
