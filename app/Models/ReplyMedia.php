<?php

namespace App\Models;

/**
 * Class ReplyMedia
 * @package App\Models
 * @version March 10, 2020, 3:12 pm UTC
 *
 * @property \App\Models\Media media
 * @property integer reply_id
 * @property integer media_id
 */
class ReplyMedia extends Model
{

    public $table = 'replies_medias';
    



    public $fillable = [
        'reply_id',
        'media_id'
    ];

    

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function media()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }
}
