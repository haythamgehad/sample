<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;



/**
 * Class Meeting
 * @package App\Models
 * @version March 10, 2020, 1:41 pm UTC
 *
 * @property \App\Models\User creator
 * @property \App\Models\Committee committee
 * @property \App\Models\Location location
 * @property \Illuminate\Database\Eloquent\Collection attendees
 * @property \Illuminate\Database\Eloquent\Collection meetingReports
 * @property \Illuminate\Database\Eloquent\Collection agendas
 * @property \Illuminate\Database\Eloquent\Collection media
 * @property \Illuminate\Database\Eloquent\Collection meetingMedias
 * @property integer committee_id
 * @property integer meeting_id
 * @property integer creator_id
 * @property integer location_id
 * @property integer number
 * @property string start_at
 * @property string end_at
 * @property integer duration
 * @property integer invited_count
 * @property integer confirmed_count
 * @property integer canceled_count
 * @property string title
 * @property string brief
 * @property string content
 * @property string status
 */
class Meeting extends Model
{
    use LogsActivity;

    public $table = 'meetings';

    protected $appends = ['edit_access'];

    const STATUS_DRAFT = 0 ;

    const STATUS_PUBLISHED = 1 ;

    const STATUS_STARTED = 2 ;

    const STATUS_MINISTRY_APPROVED = 3 ;

    const STATUS_CANCELED = 4 ;

    const STATUS_FINISHED = 5 ;
    
    const STATUS_VOTE = 6 ;

    protected $hidden = ['pivot'];

    public $fillable = [
        'id',
        'account_id',
        'committee_id',
        'creator_id',
        'location_id',
        'type',
        'remote_meeting_creator_id',
        'time_voting_end_at',

        'remote_meeting',
        'remote_meeting_url',
        'remote_meeting_id',

        'is_association',
        'procedure_id',
        'is_first_time',
        'is_second_to_id',
        'role_text',

        'attendees_minimum_shares_count',
        'shares_for_one_vote',

        'allow_electronic_voting',
        'electronic_voting_start_at',
        'electronic_voting_end_at',

        'number',
        'start_at',
        'end_at',
        'duration',
        'invited_count',
        'confirmed_count',
        'canceled_count',
        'title',
        'brief',
        'content',
        'quorum',
        'status',
        'has_approved_report',
        'meeting_association_type',
        'meeting_key'
    ];

    protected static $logAttributes = [
        'type',
        'number',
        'start_at',
        'end_at',
        'duration',
        'title',
        'brief',
        'content',
        'quorum',
        'status'
        ];


        // public function setTitleAttribute($value)
        // {
        //     $this->attributes['title'] = Crypt::encryptString($value);
        // }

        // public function getTitleAttribute($value)
        // {
        //     try {
        //         return Crypt::decryptString($value);
        //     } catch (\Exception $e) {
        //         return $value;
        //     }
        // }

        public function setBriefAttribute($value)
        {
            $this->attributes['brief'] = Crypt::encryptString($value);
        }

        public function getBriefAttribute($value)
        {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }


        public function setContentAttribute($value)
        {
            $this->attributes['content'] = Crypt::encryptString($value);
        }

        public function getContentAttribute($value)
        {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function committee()
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function organizers()
    {
        return $this->hasMany(Organizer::class,'meeting_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function collections()
    {
        return $this->hasMany(MeetingCollection::class, 'meeting_id')->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function timescomments()
    {
        return $this->hasMany(Comment::class, 'meeting_id')->where('type', 'time');
    }


    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function IsSecondTo()
    {
        return $this->belongsTo(Meeting::class, 'is_second_to_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function attendees()
    {
        return $this->hasMany(Attendee::class, 'meeting_id');
    }
    

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function reports()
    {
        return $this->hasMany(MeetingReport::class, 'meeting_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function mySharedReports()
    {
        $user = Auth::user();
        return $this->hasMany(MeetingReport::class, 'meeting_id')
            ->whereHas('shares', function ($query) use ($user){
                return $query->where('shared_to_id', '=', $user->id);
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function agendasOld()
    {
        if (isset($GLOBALS['meeting_id'])) {
            $user = Auth::user();
            $meeting = self::where('id', $GLOBALS['meeting_id'])->with('committee')->first();
            $outSideAttendee = Attendee::where('meeting_id', $GLOBALS['meeting_id'])
                ->where('is_committee_member', 0)->where('member_id', $user->id)->first();
            if ($user->id !== $meeting->committee->amanuensis_id && $user->id !== $meeting->committee->secretary_id && !$outSideAttendee) {
                return $this->hasMany(Agenda::class, 'meeting_id')->where(function ($query) use ($user) {
                    $query->whereRaw("find_in_set('".$user->id."',agendas.can_acccess_list)")
                        ->orWhere('can_acccess_list', '=', '')
                        ->orWhereNull('can_acccess_list');
                });
            } elseif ($outSideAttendee) {
                return $this->hasMany(Agenda::class, 'meeting_id')
                    ->where(function ($query) use ($outSideAttendee) {
                        $query->whereRaw("find_in_set('".$outSideAttendee->member_id."',agendas.can_acccess_list)")
                        ->orWhereIn('agendas.id', explode(',', $outSideAttendee->can_acccess_ids_list));
                    });
            }
        }

        return $this->hasMany(Agenda::class, 'meeting_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function agendas()
    {
        if (isset($GLOBALS['meeting_id'])) {
            $user = Auth::user();
            $meeting = self::where('id', $GLOBALS['meeting_id'])->with('committee')->first();
            $outSideAttendee = Attendee::where('meeting_id', $GLOBALS['meeting_id'])
                ->where('is_committee_member', 0)->where('member_id', $user->id)->first();
            if ($user->id !== $meeting->committee->amanuensis_id && $user->id !== $meeting->committee->secretary_id && !$outSideAttendee) {
                return $this->belongsToMany(Agenda::class,'meeting_agendas','meeting_id')
                    ->where(function ($query) use ($user) {
                    $query->where(function ($query) use ($user){
                        $query->whereRaw("find_in_set('".$user->id."',agendas.can_acccess_list)")
                        ->where('all_members_can_view', 0);
                    })
                    ->orWhere('all_members_can_view', 1);
                });
            } elseif ($outSideAttendee) {
                return $this->belongsToMany(Agenda::class,'meeting_agendas','meeting_id')
                    ->where(function ($query) use ($outSideAttendee) {
                        $query->whereRaw("find_in_set('".$outSideAttendee->member_id."',agendas.can_acccess_list)")
                            ->orWhereIn('agendas.id', explode(',', $outSideAttendee->can_acccess_ids_list));
                    });
            }
        }

        return $this->belongsToMany(Agenda::class,'meeting_agendas','meeting_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'meeting_id')->where('agenda_id',NULL);
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
        return $this->hasMany(MeetingMedia::class, 'meeting_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function actions()
    {
        return $this->hasMany(Action::class, 'meeting_id');
    }


    public function directories()
    {
        return $this->hasMany(Directory::class,'meeting_id');
    }


    /**
    * @return Array
    **/
    public function getEditAccessAttribute()
    {
        $committee = $this->committee()->with('governances')->first();
        $hasEditAccess = [];
        if($committee){
        if($committee->secretary_id)
            $hasEditAccess[] = $committee->secretary_id;
        if($committee->amanuensis_id)
            $hasEditAccess[] = $committee->amanuensis_id;
        if($committee->managing_director_id)
            $hasEditAccess[] = $committee->managing_director_id;
        if(!empty($committee->governances))
            foreach($committee->governances as $governance){
                $hasEditAccess[] = $governance->user_id;
            }
        }
        return $hasEditAccess;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function times()
    {
        return $this->hasMany(MeetingTime::class, 'meeting_id');
    }
}
