<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;


/**
 * Class AccountConfiguration
 * @package App\Models
 * @version March 10, 2020, 3:41 pm UTC
 *
 * @property integer account_id
 * @property integer committee_id
 * @property integer creator_id
 * @property string key
 * @property string value
 * @property string status
 */
class AccountConfiguration extends Model
{
    use LogsActivity;

    public $table = 'accounts_configurations';
    
    const STATUS_DRAFT = 1 ;

    const ALLOW_DELEGATE_ATTENDEE_REGULATION_ID = 1 ;

    public $fillable = [
        'account_id',
        'creator_id',
        'committee_id',
        'association_code',
        'regulation_configuration_id',
        'value1',
        'value2',
        'status'
    ];

    protected static $logAttributes = [
        'committee_id',
        'regulation_configuration_id',
        'value1',
        'value2',
        'status'
        ];

   

    public function regulationconfiguration() {
        return $this->hasMany(RegulationConfiguration::class,'id','regulation_configuration_id');
    }

}
