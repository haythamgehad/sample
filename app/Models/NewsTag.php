<?php

namespace App\Models;

/**
 * Class NewsTag
 * @package App\Models
 * @version March 10, 2020, 1:12 pm UTC
 *
 * @property \App\Models\News news
 * @property \App\Models\Tag NewsTag
 * @property integer news_id
 * @property integer tag_id
 */
class NewsTag extends Model
{

    public $table = 'news_tags';
    

    public $fillable = [
        'news_id',
        'tag_id'
    ];

   
}
