<?php

namespace App\Models;

/**
 * Class Annotation
 * @package App\Models
 * @version March 10, 2020, 2:12 pm UTC
 *
 */
class Annotation extends Model
{

    public $table = 'annotations';
    



    public $fillable = [
        'id',
        'creator_id',
        'meeting_id',
        'collection_id',
        'report_id',
        'media_id',
        'content',
        'share_with',
        'status',
        'annotation_id',
        'page',
        'is_agenda'
    ];

    
}
