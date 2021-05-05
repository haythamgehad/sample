<?php

namespace App\Models;


/**
 * Class ActionMedia
 * @package App\Models
 * @version March 10, 2020, 2:54 pm UTC
 *
 * @property \App\Models\Media media
 * @property integer action_id
 * @property integer media_id
 */
class ActionMedia extends Model
{

    public $table = 'actions_medias';
    

    public $fillable = [
        'action_id',
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
