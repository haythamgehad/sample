<?php

namespace App\Models;


/**
 * Class Title
 * @package App\Models
 * @version March 10, 2020, 1:47 pm UTC
 */
class Title extends Model
{

    public $table = 'titles';


    public $fillable = [
        'id',
        'name',
        'language_id',
        'translation_id',
        'status'
    ];


}
