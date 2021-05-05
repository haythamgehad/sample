<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;


/**
 * Class Setting
 * @package App\Models
 * @version March 10, 2020, 1:47 pm UTC
 */
class Setting extends Model
{

    use LogsActivity;

    public $table = 'settings';

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = json_encode($value,true);
    }
    public function getContentAttribute($value)
    {
     return   $this->attributes['content'] = json_decode($value ,true);   
    }

    public $fillable = [
        'id',
        'account_id',
        'key',
        'value',
        'status',
        'content',
        'type'
    ];


}
