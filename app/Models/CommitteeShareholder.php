<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;


/**
 * Class CommitteeShareholder
 * @package App\Models
 * @version March 10, 2020, 12:49 pm UTC
 *
 * @property \App\Models\User member
 * @property \App\Models\Committee committee
 * @property integer committee_id
 * @property integer member_id
 
 */
class CommitteeShareholder extends Model
{
    use LogsActivity;

    public $table = 'committees_shareholders';
    
    const STATUS_PUBLISHED = 1;

    public $fillable = [
        
        'committee_id',
        'member_id',
        'shares_count',
        'shares_percentage',
        'shares_value',
    ];


  

    
}
