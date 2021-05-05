<?php

namespace App\Models;

/**
 * Class Notification
 * @package App\Models
 * @version March 10, 2020, 4:36 pm UTC
 *
 * @property \App\Models\notificationTypes notificationType
 * @property \App\Models\NotificationTemplates notificationTemplates
 * @property integer notification_type_id
 * @property integer notification_template_id
 * @property integer from_id
 * @property integer to_id
 * @property string from_email
 * @property string to_email
 * @property integer to_mobile
 * @property string content
 * @property string status
 * @property integer account_id
 */
class Notification extends Model
{

    public $table = 'notifications';
    
    const STATUS_DRAFT = 1 ;

    public $fillable = [
        'type_id',
        'template_id',
        'from_id',
        'to_id',
        'from_email',
        'to_email',
        'to_mobile',
        'content',
        'status',
        'account_id',
        'title',
        'extra_data',
        'readed'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public function getExtraDataAttribute($value)
    {
        return $this->attributes['content'] = json_decode($value ,true);   
    }

}
