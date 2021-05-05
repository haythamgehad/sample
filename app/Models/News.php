<?php

namespace App\Models;

/**
 * Class News
 * @package App\Models
 * @version March 10, 2020, 1:06 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection newsCatrogires
 * @property \Illuminate\Database\Eloquent\Collection newsTags
 * @property \App\Models\Account account
 * @property \App\Models\User creator
 * @property integer creator_id
 * @property integer account_id
 * @property string title
 * @property string brief
 * @property string content
 * @property string status
 */
class News extends Model
{

    public $table = 'news';
    
    const STATUS_PUBLISHED = 1 ;

    public $fillable = [
        'creator_id',
        'account_id',
        'title',
        'brief',
        'content',
        'status'
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function categories()
    {
        return $this->belongsToMany(Category::class,'news_categories','id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'news_tags');
    }


}
