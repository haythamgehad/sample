<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
/**
 * Class CommitteeTranslation
 * @package App\Models
 * @version March 10, 2020, 12:49 pm UTC
 *
 * @property \App\Models\User member
 * @property \App\Models\Committee committee
 * @property \App\Models\Role role
 * @property \App\Models\Membership membership
 * @property integer committee_id
 * @property integer member_id
 *  * @property integer position_id
 
 */
class CommitteeTranslation extends Model
{

    use LogsActivity;

    public $table = 'committees_translations';
    
    const STATUS_PUBLISHED = 1;

    public $fillable = [
        
        'committee_id',
        'language_id',
        'name',
        'goal'

    ];

 protected static $logAttributes = [
    'name',
    'goal'
    ];

    /**
    * @return \Illuminate\Database\Eloquent\Relations\hasMany
    **/
    public function language()
    {
        return $this->hasOne(\App\Models\Language::class,'id', 'language_id');
    }
}
