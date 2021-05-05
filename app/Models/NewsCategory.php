<?php

namespace App\Models;

/**
 * Class NewsCategory
 * @package App\Models
 * @version March 10, 2020, 1:14 pm UTC
 *
 * @property \App\Models\News news
 * @property \App\Models\NewsCategories newsCategory
 * @property integer news_id
 * @property integer category_id
 */
class NewsCategory extends Model
{

    public $table = 'news_categories';
    

    public $fillable = [
        'news_id',
        'category_id'
    ];


}
