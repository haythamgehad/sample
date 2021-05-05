<?php

namespace App\Models;


/**
 * Class Attendee
 * @package App\Models
 * @version March 10, 2020, 1:47 pm UTC
 *
 * @property \App\Models\Meeting meeting
 * @property \App\Models\User user
 * @property \App\Models\User user1
 * @property integer meeting_id
 * @property integer member_id
 * @property integer position_id
 * @property string status
 */
class Attendee extends Model
{

    public $table = 'attendees';

    const STATUS_DRAFT = 0;

    const STATUS_INVITED = 1;

    const STATUS_CONFIRMED = 2;

    const STATUS_IS_ADMIN_ATTENDED = 3;

    const STATUS_ATTENDED = 4;

    const STATUS_CANCELED = 5;

    const STATUS_ABSENCE = 6;

    const MANAGER_ID = 7;


    protected $appends = ['status_text'];

    public $fillable = [
        'meeting_id',
        'member_id',
        'delegated_to_id',
        'shares',
        'committee_id',
        'is_committee_member',
        'position_id',
        'membership_id',
        'status',
        'can_acccess_ids_list',
        'speciality',
        'apologize_reason',
        'member_status',
        'organization_name'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function meeting()
    {
        //return $this->belongsTo(\App\Models\Meeting::class, 'meeting_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function user()
    {
        return $this->hasOne(\App\Models\User::class, 'id', 'member_id');
    }

    // public agendas(){
    //     $ids = $this->can_access_list

    //     //check if not null

    //     //get the agendas model and get ids from it

    //     //add agendas in relation in meeting repositry

    // }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function delegatedTo()
    {
        return $this->hasOne(\App\Models\User::class, 'id', 'delegated_to_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function position()
    {
        //       c
        return $this->hasOne(Position::class, 'translation_id', 'position_id')->where('language_id', $this->getLangIdFromLocale())->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function membership()
    {
        return $this->hasOne(Membership::class, 'translation_id', 'membership_id')->where('language_id', $this->getLangIdFromLocale())->latest();
    }

    public function getStatusTextAttribute()
    {
        $status = "" ;
        if ($this->status == 0)
        {
            $status = "مسودة" ;
        }
        if ($this->status == 1)
        {
            $status = "تمت دعوته" ;
        }
        elseif ($this->status == 2)
        {
            $status = " تم تأكيد الحضور" ;
        }
        elseif ($this->status == 3)
        {
            $status = "تم تحضيره من الادمن " ;
        }
        elseif ($this->status == 4)
        {
            $status = " حضر" ;
        }
        elseif ($this->status == 5)
        {
            $status = "تم الالغاء" ;
        }
        elseif ($this->status == 6)
        {
            $status = "لم يحضر " ;
        }
        return $status ;
        
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function votingCard()
    {
        return $this->hasOne(Media::class, 'id', 'voting_card_id');
    }

}
