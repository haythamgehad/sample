<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;



/**
 * Class Account
 * @package App\Models
 * @version March 10, 2020, 12:08 am UTC
 *
 * @property \App\Models\Language defaultLanguage
 * @property \App\Models\User user
 * @property \App\Models\Media media
 * @property integer creator_id
 * @property integer language_id
 * @property integer logo_id
 * @property string color
 * @property string slug
 * @property string url
 */
class Account extends Model
{

    use SoftDeletes;
    
    use LogsActivity;

    public $table = 'accounts';

   const STATUS_PUBLISHED = 1;

   const STATUS_BLOCKED = 0;

   const REGULATION_ID = 1;
   

    public $fillable = [
        'id',
        'creator_id',
        'language_id',
        'logo_id',

        'logo_url',
        'text_color',
        'bg_color',
        'name_en',
        'name_ar',
        'webex_access_token',
        'webex_access_refresh_token',
        'slug',
        'url',
        'regulation_id',
        'type_id',
        'has_associations',
        'status',
        'general_settings'
    ];

  
    protected static $logAttributes = [
        'logo_id',
        'type_id',

        'logo_url',
        'text_color',
        'bg_color',
        'name_en',
        'name_ar',
        
        'slug',
        'url',
        ];

  
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function creator()
    {
        return $this->belongsTo(User::class,'creator_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function logo()
    {
        return $this->hasOne(Media::class, 'id','logo_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    public function translation()
    {
        return $this->hasOne(AccountTranslation::class,'account_id')->where('language_id',$this->getLangIdFromLocale())->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     **/
    public function translations()
    {
        return $this->hasOne(AccountTranslation::class,'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function boards()
    {
        return $this->hasMany(Committee::class, 'account_id')->where('parent_id',null);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function committees()
    {
        return $this->hasMany(Committee::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function news()
    {
        return $this->hasMany(News::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function settings()
    {
        return $this->hasMany(Setting::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function actions()
    {
        return $this->hasMany( Action::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function type()
    {
       // return $this->belongsTo(AccountType::class ,'type_id','id');
        //return $this->hasOne(AccountType::class ,'type_id','id');
        return $this->hasOne(AccountType::class, 'translation_id', 'type_id')->where('language_id',$this->getLangIdFromLocale())->latest();

    }

    public function getGeneralSettingsAttribute($value)
    {
        return $this->attributes['general_settings'] = !is_array($value) ? json_decode($value ,true) : $value;
    }

    public function setGeneralSettingsAttribute($value)
    {
        return $this->attributes['general_settings'] =json_encode($value ,true);
    }
}
