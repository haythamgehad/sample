<?php

namespace App\Models;


/**
 * Class ActionVoting
 * @package App\Models
 * @version March 10, 2020, 2:54 pm UTC
 */
class ActionVoting extends Model
{

    const STATUS_ACCEPT = 1 ;

    const STATUS_REJECT = 2 ;

    const STATUS_REQUEST_MEETING = 3 ;

    const MINUMUM_REQUEST_MEETING_BEFORE_END_VOTING = 4 ;

    const STATUS_REFRAIN= 5 ;

    public $table = 'actions_votings';
    

    public $fillable = [
        'action_id',
        'creator_id',
        'status',
        'shares',
        'confirmed',
        'action_voting_element_id',
        'confirmation_code'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'creator_id');
    }
    
}
