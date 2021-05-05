<?php

namespace App\Models;

/**
 * Class NewsMedia
 * @package App\Models
 * @version March 10, 2020, 1:10 pm UTC
 *
 * @property \App\Models\Media media
 * @property \App\Models\News news
 * @property integer news_id
 * @property integer media_id
 */
class NewsMedia extends Model
{

    public $table = 'news_medias';


    public $fillable = [
        'news_id',
        'media_id'
    ];

   

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function media()
    {
        return $this->hasOne(Media::class, 'id','media_id');
    }

}
