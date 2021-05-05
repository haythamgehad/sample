<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;


/**
 * Class Reply
 * @package App\Models
 * @version March 10, 2020, 3:23 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection medias
 * @property \App\Models\User creator
 * @property integer comment_id
 * @property integer parent_id
 * @property string content
 * @property string status
 * @property integer creator_id
 */
class Reply extends Model
{
    use LogsActivity;

    public $table = 'replies';
    
   const STATUS_PUBLISHED = 1 ;

    public $fillable = [
        'account_id',
        'comment_id',
        'parent_id',
        'content',
        'status',
        'creator_id'
    ];

    protected static $logAttributes = [
        'content',
        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function medias()
    {
        return $this->hasMany(ReplyMedia::class, 'reply_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function creator()
    {
        return $this->hasOne(User::class, 'id','creator_id');
    }
}
