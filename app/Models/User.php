<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Lumen\Auth\Authorizable;

/**
 * Class User
 *
 * @property int $id
 * @property string|null $email
 * @property string|null $mobile
 * @property string|null $password
 * @property array|null $picture_id
 * @property int $status
 * @property int $language_id
 * @property int $creator_id
 * @property int $account_id
 * @property int $manager_id
 * @property int $nationality_id
 * @property string $identification_number
 * @property string $residence_number
 * @property string $passport_number
 * @property int $shares_number
 * @property int $shares_value
 * @property int $currency_id
 * @property int $ratio_value
 * @property int $role_id
 * @property Carbon|null $birth_date
 * @property string|null $activation_email_code
 * @property string|null $activation_mobile_code
 * @property string|null $forgot_code
 * @property Carbon|null $forgot_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection|UserToken[] $userTokens
 * @property-read Collection|UserNotification[] $userNotifications
 * @property-read Language $language
 * @property-read Role $role
 * @property-read Collection|UserTask[] $userTasks
 * @property-read Collection|UserTask[] $userAssignedTasks
 *
 * @package App\Models
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    use LogsActivity;


    /** @var int */
    const ACCOUNT_CREATOR_ROLE_ID = 1 ;
    const ACCOUNT_MEMBER_ROLE_ID = 2 ;

    /** @var int */
    const STATUS_UNCONFIRMED = 0 ;

    /** @var int */
    const STATUS_CONFIRMED = 1 ;

    /** @var int */
    const STATUS_EMAIL_UNCONFIRMED = 2 ;

    /** @var int */
    const STATUS_MOBILE_UNCONFIRMED = 3 ;

    const STATUS_FINISH = 0 ;

    /** @var string */
    const DEFAULT_PASSWORD = '12345678';

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'users';

    /** @var array */
    protected $fillable = [
        'id',
        'email',
        'mobile',
        'password',
        'otp',
        'device_token',
        'specialty',
        'picture_id',
        'picture_url',
        'status',
        'language_id',
        'creator_id',
        'account_id',
        'manager_id',

        'is_admin',
        'is_attendee',
        'is_delegated',
        'is_member',

        'is_managing_director',


        'nationality_id',
        'birth_date',
        'identification_number',
        'residence_number',
        'passport_number',
        'shares',
        'expire_date',
        'secretary_change_voting',
        /*
            'shares_number',
            'shares_value',
            'currency_id',
            'ratio_value',
        */
        'role_id',
        'activation_email_code',
        'activation_mobile_code',
        'forgot_code',
        'forgot_time',
        'type',

    ];

    protected static $logAttributes = [
        'email',
        'mobile',
        ];

    /** @var array */
    protected $hidden = [
        'password',
        'otp',
        'activation_email_code',
        'activation_mobile_code',
        'forgot_code',
        'forgot_time',
    ];




    /**
     * User tokens.
     *
     * @return HasMany
     */
    public function userTokens()
    {
        return $this->hasMany(UserToken::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function translation()
    {
        return $this->hasOne(\App\Models\UserTranslation::class, 'user_id')->where('language_id',$this->getLangIdFromLocale())->latest();
    }

    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function translations()
    {
        return $this->hasMany(\App\Models\UserTranslation::class, 'user_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function committees()
    {
        return $this->hasMany(\App\Models\CommitteeMember::class, 'member_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function Governance()
    {
        return $this->hasMany(\App\Models\GovernanceManager::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     **/
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
       // return $this->belongsTo(\App\Models\AccountTranslation::class, 'account_id', 'account_id')->where('language_id',$this->getLangIdFromLocale())->latest();
    }

    /**
     * Language.
     *
     * @return BelongsTo
     */
    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id', 'id');
    }
    /**
     * Language.
     *
     * @return BelongsTo
     */
    public function nationality()
    {
       // return $this->belongsTo(Nationality::class, 'language_id', 'id');
       //return $this->hasOne(Nationality::class, 'id', 'nationality_id');
        return $this->hasOne(Nationality::class, 'translation_id', 'nationality_id')->where('language_id',$this->getLangIdFromLocale())->latest();

    }

    /**
     * Role.
     *
     * @return BelongsTo
     */
    public function role()
    {
        //return $this->belongsTo(Role::class, 'role_id', 'id');
        // return $this->hasOne(Role::class, 'id', 'role_id');
       return $this->hasOne(Role::class, 'translation_id', 'role_id')->where('language_id',$this->getLangIdFromLocale())->latest();

    }  

    /**
     * @param $value
     *
     * @return Carbon|null
     */
    public function getForgotTimeAttribute($value)
    {
        if ($value !== null) {
            return Carbon::parse($value);
        }

        return null;
    }

    /**
     * @param $value
     *
     * @return array|null
     */
    public function getPictureAttribute($value)
    {
        if ($value !== null && $value !== '') {
            return json_decode($value, true);
        }

        return null;
    }
}
