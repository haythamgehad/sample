<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;


/**
 * Class Comment
 * @package App\Models
 * @version March 10, 2020, 3:18 pm UTC
 *
 * @property \App\Models\User user
 * @property \Illuminate\Database\Eloquent\Collection replies
 * @property \Illuminate\Database\Eloquent\Collection commentMedias
 * @property integer creator_id
 * @property integer account_id
 * @property string content
 * @property string status
 */
class Comment extends Model
{
    use LogsActivity;

    public $table = 'comments';

    const STATUS_PUBLISHED = 1 ;
    public $fillable = [
        'creator_id',
        'account_id',

        'committee_id',
        'meeting_id',
        'type',
        'agenda_id',
        'action_id',

        'task_id',
        'mentions',
        'sharewith',

        'content',
        'status'
    ];

    protected static $logAttributes = [
        'content',
        ];

    

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'creator_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function replies()
    {
        return $this->hasMany(Reply::class, 'comment_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function medias()
    {
        return $this->hasMany(CommentMedia::class, 'comment_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function agenda()
    {
        return $this->hasOne(Agenda::class, 'id', 'agenda_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function action()
    {
        return $this->hasOne(Action::class, 'id', 'action_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function meeting()
    {
        return $this->hasOne(Meeting::class, 'id', 'meeting_id');
    }
}
