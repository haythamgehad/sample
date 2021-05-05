<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;



/**
 * Class CommitteeMemberLog
 * @package App\Models
 * @version March 10, 2020, 12:49 pm UTC
 *
 * @property \App\Models\User member
 * @property \App\Models\Committee committee
 * @property integer committee_id
 * @property integer member_id
 
 */
class CommitteeMemberLog extends Model
{
    use LogsActivity;

    public $table = 'committees_members_logs';
    
    const STATUS_PUBLISHED = 1;

    const STATUS_FINISH = 0 ;

    public $fillable = [
        'committee_id',
        'member_id',
        'status',
        'comment'
    ];
    


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function member()
    {
        return $this->hasOne(User::class, 'id', 'member_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function committee()
    {
        return $this->hasOne(Committee::class, 'id', 'committee_id');
    }

    
}
