<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Class Todo
 * @package App\Models
 * @version March 10, 2020, 1:47 pm UTC
 *
 * @property integer action_id
 * @property string title
 * @property date due_date
 * @property string status
 */
class TaskLog extends Model
{    
    
    const UPDATE_STATUS = 1 ; //in  state
    const ADD_COMMENT = 2 ;
   
    public $table = 'tasks_log';

    public $fillable = [
        'id',
        'task_id',
        'assignee_id',
        'status',
        'task_status',
        'account_id',
        'language_id'
        
    ];

}
