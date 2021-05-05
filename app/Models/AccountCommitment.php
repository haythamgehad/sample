<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;


/**
 * Class AccountTranslation
 * @package App\Models
 * @version March 10, 2020, 12:25 am UTC
 *
 * @property \App\Models\Account account
 * @property \App\Models\Language language
 * @property integer account_id
 * @property integer language_code
 * @property string title
 * @property string name
 */
class AccountCommitment extends Model
{
    const OBJECT_TYPE_BOARDS = 'committees';
    const OBJECT_TYPE_COMMITTEES = 'committees';
    const OBJECT_TYPE_MEETING = 'meetings';

    public $table = 'accounts_commitments';

    public $fillable = [
        'id',
        'account_id',
        'regulation_id',
        'regulation_configuration_id',
        'object_type',
        'object_id',
        'value1',
        'value2',
        
    ];

}
