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
class AccountTranslation extends Model
{
    use LogsActivity;

    public $table = 'accounts_translations';

    public $fillable = [
        'account_id',
        'language_id',
        'translation_id',
        'name'
    ];

    protected static $logAttributes = [
        'name'
        ];

    /**
    * @return \Illuminate\Database\Eloquent\Relations\hasMany
    **/
    public function language()
    {
        return $this->hasOne(\App\Models\Language::class, 'id','language_id');
    }

}
