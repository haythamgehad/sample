<?php

namespace App\Models;

/**
 * Class MeetingMedia
 * @package App\Models
 * @version March 10, 2020, 2:12 pm UTC
 *
 */
class MeetingMedia extends Model
{

    public $table = 'meetings_medias';
    



    public $fillable = [
        'meeting_id',
        'media_id',
        'status'
    ];


     /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function media()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }

    
}
