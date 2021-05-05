<?php

namespace App\Models;


/**
 * Class TaskMedia
 * @package App\Models
 * @version March 10, 2020, 3:12 pm UTC
 *
 * @property \App\Models\Media media
 * @property integer task_id
 * @property integer media_id
 */
class TaskMedia extends Model
{

    public $table = 'tasks_medias';
    

    public $fillable = [
        'task_id',
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
