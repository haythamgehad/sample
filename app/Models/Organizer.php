<?php

namespace App\Models;


/**
 * Class Organizer
 * @package App\Models
 * @version March 10, 2020, 1:47 pm UTC
 */
class Organizer extends Model
{

    public $table = 'organizers';


    public $fillable = [
        'id',
        'member_id',
        'meeting_id',
        'status',
        'capabilities',
        'expires_at'
    ];

 /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function user()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    

}
