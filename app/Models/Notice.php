<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;



/**
 * Class Notice
 * @package App\Models
 */
class Notice extends Model
{

    use LogsActivity;

    public $table = 'notices';
    

    const STATUS_APPROVE = 1 ;

    const STATUS_DRAFT = 0 ;

    const STATUS_PUBLISHED = 0 ;

    public $fillable = [
        'id',
        'account_id',
        'creator_id',
        'meeting_id',
        'agenda_id',
        'content',
        'status'
    ];

    protected static $logAttributes = [
        'content',
        ];

    /**
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    **/
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
