<?php

namespace App\Models;

/**
 * Class MeetingTime
 * @package App\Models
 * @version March 14, 2020, 2:29 pm UTC
 *
 * @property integer meeting_id
 * @property string start_at
 * @property string end_at
 * @property integer duration
 * @property integer votes_count
 */
class MeetingTime extends Model
{

    public $table = 'meetings_times';
    
   const STATUS_DREAFT = 1 ;

    public $fillable = [
        'meeting_id',
        'start_at',
        'end_at',
        'duration',
        'votes_count',
        'total_vote_counts',
        'status'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function votes()
    {
        return $this->hasMany(MeetingTimeVote::class, 'time_id');
    }
}
