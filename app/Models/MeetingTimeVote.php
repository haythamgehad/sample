<?php

namespace App\Models;

/**
 * Class MeetingTimeVote
 * @package App\Models
 * @version March 14, 2020, 2:32 pm UTC
 *
 * @property integer time_id
 * @property integer creator_id
 */
class MeetingTimeVote extends Model
{

    public $table = 'meetings_times_votes';
    



    public $fillable = [
        'time_id',
        'creator_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
