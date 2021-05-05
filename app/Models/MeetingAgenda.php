<?php

namespace App\Models;


/**
 * Class MeetingAgenda
 * @package App\Models
 * @version March 10, 2020, 2:54 pm UTC
 */
class MeetingAgenda extends Model
{
    public $table = 'meeting_agendas';


    public $fillable = [
        'meeting_id',
        'agenda_id',
        'original'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'creator_id');
    }
    
}
