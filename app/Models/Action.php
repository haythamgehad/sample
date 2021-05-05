<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;



/**
 * Class Action
 * @package App\Models
 * @version March 10, 2020, 2:31 pm UTC
 *
 * @property \App\Models\Committee committee
 * @property \App\Models\Meeting meeting
 * @property \Illuminate\Database\Eloquent\Collection tasks
 * @property \App\Models\User creator
 * @property \App\Models\User user1
 * @property \App\Models\User user2
 * @property \App\Models\ActionTypes actionTypes
 * @property \Illuminate\Database\Eloquent\Collection actionMedias
 * @property integer resource_type_id
 * @property integer account_id
 * @property integer committee_id
 * @property integer creator_id
 * @property integer meeting_id
 * @property integer agenda_id
 * @property string is_private
 * @property string number
 * @property string due_date
 * @property integer assignee_id
 * @property integer tasks_count
 * @property integer done_tasks_count
 * @property integer not_done_tasks_count
 * @property integer progress
 * @property integer type_id
 * @property integer accept_voted_count
 * @property integer reject_voted_count
 * @property string title
 * @property string brief
 * @property string content
 * @property string status

 */
class Action extends Model
{

    use LogsActivity;

    public $table = 'actions';

    const STATUS_NEW = 1 ; //in action state

    const STATUS_READY_TO_VOTE = 2 ; //in action state

    const STATUS_VOTE_CLOSED = 3; //in action state

    const STATUS_NEW_MEETING = 4 ; //in action voting result

    const STATUS_APPROVED = 5 ; //in action voting result

    const STATUS_REJECTED = 6 ; //in action voting result

    const STATUS_PUBLISHED = 7 ; //in action state

    const STATUS_ENDED = 8 ; //in action state

    const STATUS_CANCELED = 9 ; //in action state

    const SHOW_VOTING = 'ALL';

    const HIDE_VOTING = 'HIDE';

    CONST STATUS_START_VOTE = 10 ;
    
    CONST VISIBLE = 1;

    CONST HIDDEN = 2;

    CONST QUESTIONAIRE = 3;

    CONST CUMULATIVE = 4;

    CONST HIDDEN_QUESTIONAIRE = 5;
    
    public $fillable = [
        'account_id',
        'committee_id',
        'creator_id',
        'meeting_id',
        'agenda_id',
        'is_private',
        'voting_visibility',
        'is_approved',
        'can_change_after_voting',

        'can_change_after_publish',
        'minimum_meeting_requests',
        'boss_weighting',
        'boss_vote_weight_doubled',
        'reopen_vote_list',
        'assignee_details',
        
        'number',
        'due_date',
        'assignee_id',
        'tasks_count',
        'done_tasks_count',
        'not_done_tasks_count',
        'progress',
        'type_id',
        'quorum',
        'can_change_vote',
        'total_voted_count',
        'accept_voted_count',
        'reject_voted_count',
        'refrain_voted_count',
        'request_meeting_voted_count',
        'vote_started_at',
        'vote_ended_at',
        'title',
        'brief',
        'content',
        'show_to',
        'status',
        'start_date',
        'end_date',
        'voting_result',
        'action_number',
        'voting_type'
        
    ];

    protected static $logAttributes = [
        'title',
        'brief',
        'vote_started_at',
        'vote_ended_at',

        'reopen_vote_list',
        
        'content',
        'status',
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

    public function getDueDateAttribute($date){
        return $this->convertDate($date);
    }

    public function getVoteStartedAtAttribute($date){
        return $this->convertDate($date);
    }

    public function getVoteEndedAtAttribute($date){
        return $this->convertDate($date);
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
    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'agenda_id');
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
        return $this->hasMany(Task::class, 'action_id');
    }

    public function reopen_votes()
    {
        return $this->hasMany(ReopenVote::class, 'action_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
        return $this->hasMany(ActionMedia::class, 'action_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function comments()
    {
        $user = Auth::user();
        return $this->hasMany(Comment::class, 'action_id')
        ->where(function($query) use($user){
            $query->whereHas('action.committee', function($query) use($user){
                return $query->where('amanuensis_id', $user->id);
            })
            ->Where('comments.creator_id', '!=', $user->id);
        })
        ->orWhere('comments.creator_id', $user->id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function type()
    {
        //return $this->belongsTo(ActionType::class ,'type_id','id');
        //return $this->hasOne(ActionType::class ,'type_id','id');
        return $this->hasOne(ActionType::class, 'translation_id', 'type_id')->where('language_id',$this->getLangIdFromLocale())->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function votings()
    {
        return $this->hasMany(ActionVoting::class ,'action_id')->where('confirmed', 1);
    }

    public function getVotingResultAttribute($value)
    {
        return $this->attributes['voting_result'] = !is_array($value) ? json_decode($value ,true) : $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function votingElements()
    {
        return $this->hasMany(ActionVotingElement::class, 'action_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'action_id');
    }
}
