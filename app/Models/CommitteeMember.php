<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;



/**
 * Class CommitteeMember
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
class CommitteeMember extends Model
{
    use LogsActivity;

    public $table = 'committees_members';
    
    const STATUS_PUBLISHED = 1;

    const STATUS_FINISH = 0 ;

    const Independent_Membership = 3 ;
    const InExecutive_Membership = 1 ;

    const BOSS_POSITION_ID = 1 ;

    public $fillable = [
        'committee_id',
        'member_id',
        'position_id',
        'membership_id',
        'connected_absences_count',
        'joining_date',
        'shares',
        'confirmed',
        'organization_name'
    ];

    protected static $logAttributes = [
        'committee_id',
        'member_id',
        'position_id',
        'membership_id',
        'connected_absences_count',
        ];
    

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'member_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function committee()
    {
        return $this->hasOne(Committee::class, 'id', 'committee_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function position()
    {
        //return $this->hasOne(Position::class, 'id', 'position_id');
        return $this->hasOne(Position::class, 'translation_id', 'position_id')->where('language_id',$this->getLangIdFromLocale())->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function member()
    {
        return $this->hasOne(User::class, 'id', 'member_id');
    }



    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function membership()
    {
       // return $this->hasOne(Membership::class, 'id', 'membership_id');
       return $this->hasOne(Membership::class, 'translation_id', 'membership_id')->where('language_id',$this->getLangIdFromLocale())->latest();

    }

    
}
