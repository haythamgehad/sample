<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;



/**
 * Class Recommendation
 * @package App\Models
 */
class Recommendation extends Model
{

    use LogsActivity;

    public $table = 'recommendations';
    

    const STATUS_PUBLISHED = 1 ;

    const STATUS_DRAFT = 0 ;

    public $fillable = [
        'id',
        'account_id',
        'creator_id',
        'assignee_id',
        'meeting_id',
        'agenda_id',
        'show_to',
        'due_date',
        'content',
        'status'
    ];

    protected static $logAttributes = [
        'content',
        ];

    

}
