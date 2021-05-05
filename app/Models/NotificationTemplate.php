<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class NotificationTemplate
 * @package App\Models
 * @version March 10, 2020, 4:30 pm UTC
 *
 * @property \App\Models\User creator
 * @property \App\Models\NotificationTypes notificationTypes
 * @property integer resource_type_id
 * @property integer notification_type_id
 * @property integer creator_id
 * @property integer account_id
 * @property string name
 * @property string content
 * @property string status
 */
class NotificationTemplate extends Model
{

    use SoftDeletes;
    
    public $table = 'notifications_templates';
    
    const STATUS_PUBLISHED = 1 ;

    public $fillable = [
        'id',
        'type_id',
        'is_default',
        'creator_id',
        'account_id',
        
        'language_id',
        'translation_id',

        'is_sms',
        'is_email',
        'is_push',

        'title',
        'content',
        'status'
    ];

    public function notificationType()
    {
       return $this->hasOne(NotificationType::class, 'translation_id', 'type_id')->where('language_id',$this->getLangIdFromLocale())->latest();

    } 


    
}
