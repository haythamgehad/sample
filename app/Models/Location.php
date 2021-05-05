<?php

namespace App\Models;

/**
 * Class Location
 * @package App\Models
 * @version March 10, 2020, 1:52 am UTC
 *
 * @property \App\Models\Account account
 * @property \App\Models\User creator
 * @property integer creator_id
 * @property integer account_id
 * @property string status
 */
class Location extends Model
{

    public $table = 'locations';

    const STATUS_PUBLISHED = 1;

    public $fillable = [
        'id',
        'creator_id',
        'account_id',
        'language_id',
        'translation_id',
        'name',
        'description',
        'longitude',
        'latitude',
        'map_url',
        'status'
    ];

    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function translation()
    {
        return $this->hasOne(\App\Models\Location::class, 'translation_id')->where('language_id','<>', $this->getLangIdFromLocale())->latest();
    }

}
