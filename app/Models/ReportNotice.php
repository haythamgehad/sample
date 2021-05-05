<?php

namespace App\Models;

/**
 * Class ReportNotice
 * @package App\Models
 * @version March 10, 2020, 4:18 pm UTC
 *
 * @property \App\Models\User user
 * @property integer meeting_report_id
 * @property integer creator_id
 * @property string content
 * @property string status
 */
class ReportNotice extends Model
{

    public $table = 'reports_notices';
    

    const STATUS_PUBLISHED = 1 ;

    public $fillable = [
        'report_id',
        'reply_to_id',
        'creator_id',
        'content',
        'status'
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
        return $this->hasMany(ReportNotice::class, 'reply_to_id');
    }
}
