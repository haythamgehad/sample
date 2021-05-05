<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;


/**
 * Class Agenda
 * @package App\Models
 * @version March 10, 2020, 1:54 pm UTC
 *
 * @property \App\Models\Meeting meeting
 * @property \App\Models\User creator
 * @property \Illuminate\Database\Eloquent\Collection agendaMedias
 * @property \Illuminate\Database\Eloquent\Collection comments
 * @property integer account_id
 * @property integer committee_id
 * @property integer meeting_id
 * @property integer creator_id
 * @property integer assignee_id
 * @property integer tasks_count
 * @property integer done_tasks_count
 * @property integer not_done_tasks_count
 * @property integer progress
 * @property integer duration
 * @property string title
 * @property string brief
 * @property string content
 * @property string status
 */
class Agenda extends Model
{

    use LogsActivity;

    public $table = 'agendas';
    
    const STATUS_POSTPONED = 2 ;

    const STATUS_PUBLISHED = 1 ;

    const STATUS_OFF = 0 ;

    public $fillable = [
        //'id',
        'account_id',

        'clause_id',

        'committee_id',
        'meeting_id',
        'creator_id',
        'assignee_id',
        'presenter',
        'total_voted_count',
        'accept_voted_count',
        'reject_voted_count',
        'refrain_voted_count',
        'can_acccess_list',
        'is_work_agenda',
        'has_voting',
        'collection_included',
        'has_hidden_voting',
        'has_visable_voting',
        'has_cumulative_voting',

        'duration',
        'title',
        'brief',
        'content',
        'status',
        'all_members_can_view'
    ];

    protected static $logAttributes = [
        'duration',
        'title',
        'presenter',
        'brief',
        'content',
        'status'
        ];
        public function setTitleAttribute($value)
        {
            $this->attributes['title'] = Crypt::encryptString($value);
        }

        public function getTitleAttribute($value)
        {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }

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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'creator_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function assignee()
    {
        return $this->hasOne(User::class, 'id', 'assignee_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function medias()
    {
        return $this->hasMany(AgendaMedia::class, 'agenda_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function meetings()
    {
        return $this->hasMany(MeetingAgenda::class, 'agenda_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function notices()
    {
        return $this->hasMany(Notice::class, 'agenda_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function comments()
    {
        $user = Auth::user();
        return $this->hasMany(Comment::class, 'agenda_id')
        ->where(function($query) use($user){
            $query->where(function($query) use($user){
                $query->whereHas('agenda.committee', function($query) use($user){
                    return $query->where('amanuensis_id', $user->id);
                })
                ->Where('comments.creator_id', '!=', $user->id);
            })
            ->orWhere('comments.creator_id', $user->id);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function actions()
    {
        $user = Auth::user();
        $outsideMeetingsIds = Attendee::where('is_committee_member', 0)->where('member_id', $user->id)->pluck('meeting_id')->toArray();
        return $this->hasMany(Action::class, 'agenda_id')
            ->where(function ($query) use ($outsideMeetingsIds) {
                $query->where(function ($query) use ($outsideMeetingsIds) {
                    if (count($outsideMeetingsIds) > 0) {
                        $query->whereNotIn('meeting_id', $outsideMeetingsIds);
                    }
                    $query->where('show_to', 'MEMBERS');
                })
                    ->orWhere('show_to', 'ATTENDEES');
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function myActions()
    {
        $user = Auth::user();
        $outsideMeetingsIds = Attendee::where('is_committee_member', 0)->where('member_id', $user->id)->pluck('meeting_id')->toArray();
        return $this->hasMany(Action::class, 'agenda_id')
            ->where(function ($query) use($outsideMeetingsIds) {
                if(count($outsideMeetingsIds) > 0){
                    $query->where(function ($query) use($outsideMeetingsIds){
                        $query->whereNotIn('meeting_id', $outsideMeetingsIds)
                            ->where('show_to', 'MEMBERS');
                    })
                        ->orWhere('show_to', 'ATTENDEES');
                } else {
                    $query->where('show_to', 'ATTENDEES');
                }
            });
    }

     /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'agenda_id');
    }

    public function mine($q){
        if($this->can_acccess_list !== null){
            $user = Auth::user();
            $usersCanAccess = explode(",",$this->can_acccess_list);
            dd($usersCanAccess);
            if(in_array($user->id,$usersCanAccess))
                return $this;
            else
                return false;
        }
        
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function tasks()
    {
        return $this->hasMany(Task::class, 'agenda_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function committee()
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }
}
