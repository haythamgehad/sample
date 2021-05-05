<?php

namespace App\Models;


/**
 * Class Capability
 * @package App\Models
 * @version March 10, 2020, 1:47 pm UTC
 */
class Capability extends Model
{

    public $table = 'capabilities';


    public $fillable = [
        'id',
        'name',
        'code',
        'language_id',
        'translation_id',
        'status'
    ];


}
