<?php

namespace App\Models;



/**
 * Class CommentMedia
 * @package App\Models
 * @version March 10, 2020, 3:12 pm UTC
 *
 * @property \App\Models\Media media
 * @property integer comment_id
 * @property integer media_id
 */
class CommentMedia extends Model
{

    public $table = 'comments_medias';
    



    public $fillable = [
        'comment_id',
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
