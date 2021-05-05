<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

/**
 * Class Committee
 * @package App\Models
 * @version March 10, 2020, 11:48 am UTC
 *
 * @property \App\Models\User user
 * @property \App\Models\ResourceTypes resourceType
 * @property \App\Models\Account account
 * @property \Illuminate\Database\Eloquent\Collection committeeTranslations
 * @property \Illuminate\Database\Eloquent\Collection meetings
 * @property \Illuminate\Database\Eloquent\Collection committeeMedias
 * @property \Illuminate\Database\Eloquent\Collection committeeMembers
 * @property integer creator_id
 * @property integer account_id
 * @property string association_code
 * @property string type
 * @property integer parent_id
 * @property integer secretary_id
 * @property integer managing_director_id
 * @property integer board_type_id
 * @property integer committee_type_id
 * @property integer quorum
 * @property string start_at
 * @property string end_at
 * @property integer independents_percentage
 * @property integer meetings_number_yearly
 * @property integer attendees_members_count
 * @property integer executive_members_count
 * @property integer non_executive_members_count
 * @property string status
 * @property integer shares
 * @property integer capital
 * @property integer is_assocation_board
 * @property integer company_system
 */
class Committee extends Model
{
    use LogsActivity;

    protected $appends = ['edit_access' ,'committees_count','boards_count','members_count'];

    public $table = 'committees';
    
    const STATUS_PUBLISHED = 1;

    const DEFAULT_BOARD_TYPE_ID = 1;

    const DEFAULT_COMMITTEE_TYPE_ID = 1;

    public $fillable = [
        'creator_id',
        'account_id',
        'association_code',
        'type',
        'has_sub',
        'is_permanent',
        'parent_id',
        'amanuensis_id',
        'secretary_id',
        'managing_director_id',
       // 'board_type_id',
       // 'committee_type_id',
      //  'type_id',
        'media_id',
        'start_at',
        'end_at',
        'is_completed',
        'shares',
        'capital',
        'is_assocation_board',
        'company_system',
    /*    
        'quorum',
        'independents_percentage',
        'meetings_number_yearly',
        'attendees_members_count',
        'executive_members_count',
        'non_executive_members_count',

        'allow_delegation',
        'number_of_reminders',
        'minimum_days_before_meeting_invitation',
    */

        'commercial_register',
        'capital',
        'subscribed_capital',
        'accountant_id',
        'location_id',
        
        'status'
    ];

    protected static $logAttributes = [
        'amanuensis_id',
        'secretary_id',
        'managing_director_id',
        'type'
        ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function parent()
    {
        return $this->belongsTo(Committee::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function committees()
    {

        return  $this->hasMany(Committee::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function board()
    {
        return $this->hasOne(Committee::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function authorities()
    {
        return $this->hasMany(CommitteeAuthority::class, 'committee_id');
    }

    

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function amanuensis()
    {
        return $this->belongsTo(User::class,  'amanuensis_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function secretary()
    {
        return $this->belongsTo(User::class,  'secretary_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function managingDirector()
    {
        return $this->belongsTo(User::class,  'managing_director_id');
    }

    public function commercialregistermedia(){
        return $this->belongsTo(Media::class,  'commercial_register');
    }

    public function companySystemMedia(){
        return $this->belongsTo(Media::class,  'company_system');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function translation()
    {
        return $this->hasOne(\App\Models\CommitteeTranslation::class, 'committee_id')->where('language_id',$this->getLangIdFromLocale())->latest();
    }
    

     /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function translations()
    {
        return $this->hasMany(CommitteeTranslation::class, 'committee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'committee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function media()
    {
        return $this->hasOne(Media::class, 'media_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function medias()
    {
        return $this->hasMany(CommitteeMedia::class, 'committee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function members()
    {
        return $this->hasMany(CommitteeMember::class, 'committee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function governances()
    {
        return $this->hasMany(GovernanceManager::class, 'committee_id');
    }

    /**
     * @return Array
     **/
    public function getEditAccessAttribute()
    {
        $hasEditAccess = [];

        if($this->secretary_id)
            $hasEditAccess[] = $this->secretary_id;
        if($this->amanuensis_id)
            $hasEditAccess[] = $this->amanuensis_id;
        if(!empty($this->governances))
            foreach($this->governances as $governance){
                $hasEditAccess[] = $governance->user_id;
            }
        return $hasEditAccess;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function memberslogs()
    {
        return $this->hasMany(CommitteeMemberLog::class, 'committee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function accountconfiguration()
    {
        return $this->hasMany(AccountConfiguration::class, 'committee_id');
    }

    public function getCommitteesCountAttribute()
    {
      return  Committee::where(['account_id'=>$this->account_id,'type'=>"Committees"])->count();
    }

    public function getBoardsCountAttribute()
    {
      return  Committee::where(['account_id'=>$this->account_id,'type'=>"Boards"])->count();
    }
    public function getMembersCountAttribute()
    {
      return  CommitteeMember::where('committee_id', $this->id)->count();
    }

    }
    
