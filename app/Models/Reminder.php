<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;


/**
 * Class Reminder
 * @package App\Models
 * @version March 10, 2020, 3:23 pm UTC
 */
class Reminder extends Model
{
    use LogsActivity;

    public $table = 'reminders';
    
   const STATUS_PUBLISHED = 1 ;

    public $fillable = [
        'creator_id',
        'account_id',
        'meeting_id',
        'action_id',
        'todo_id',
        'task_id',
        'send_at',
        'sent_at',
        'users',
        'title',
        'content',
        'status'
    ];

    protected static $logAttributes = [
        'title',
        'content',
        ];

    
}
