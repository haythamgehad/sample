<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Class CommitteeAuthority
 * @package App\Models
 * @version March 10, 2020, 3:41 pm UTC
 *
 * @property integer member_id
 * @property integer action_id
 */
class CommitteeAuthority extends Model
{
    use LogsActivity;

    public $table = 'committees_authorities';
    
    const STATUS_DRAFT = 1 ;

    public $fillable = [
        
        'id',
        'committee_id',
        'member_ids',//all or comma seprated
        'position_ids',//all or comma seprated
        'committee_ids',//all or parent or sub or comma seprated
        'meeting_ids',//all or comma seprated
        'action_ids',//all or comma seprated
    ];

    protected static $logAttributes = [
        'committee_id',
        'member_ids',//all or comma seprated
        'position_ids',//all or comma seprated
        'committee_ids',//all or parent or sub or comma seprated
        'meeting_ids',//all or comma seprated
        'action_ids',//all or comma seprated
        ];

}
