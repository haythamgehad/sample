<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
/**
 * Class Task
 * @package App\Models
 * @version March 10, 2020, 3:11 pm UTC
 * * @property \Illuminate\Database\Eloquent\Collection comments
 * @property \Illuminate\Database\Eloquent\Collection taskMedias
 * @property \App\Models\User user
 * @property \App\Models\Meeting meeting
 * @property \App\Models\User user1
 * @property \App\Models\User user2
 * @property integer action_id
 * @property integer resource_id
 * @property integer creator_id
 * @property string due_date
 * @property string content
 * @property string status
 */
class Task extends Model
{
    use LogsActivity;

    public $table = 'tasks';

    const STATUS_PUBLISHED = 1 ;
    CONST STATUS_INPROGRESS = 2 ;
    CONST STATUS_FINISHED = 3 ;

    protected $appends = ['custom_due_date'];
    public $fillable = [
        'account_id',
        'meeting_id',
        'todo_id',
        'action_id',
        'creator_id',
        'assignee_id',
        'start_date',
        'due_date',
        'title',
        'content',
        'status',
        'agenda_id',
    ];

    protected static $logAttributes = [
        'start_date',
        'due_date',
        'todo_id',
        'assignee_id',
        'title',
        'content',
        'status',
        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/

    public function medias()
    {
        return $this->hasMany(TaskMedia::class, 'task_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
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
    public function comments()
    {
        return $this->hasMany(Comment::class, 'task_id');
    }

    public function logs()
    {
        return $this->hasMany(TaskLog::class, 'task_id');
    }

    public function getCustomDueDateAttribute()
    {
        return $this->convertDate($this->due_date);
    }

    public function todo()
    {
        return $this->belongsTo(Todo::class, 'todo_id');
    
    }

    public function convertDate($date){
        
        if($date && true){

            $languages_codes=array(1=>'ar',2=>'en','ar'=>'ar','en'=>'en');

            \Carbon\Carbon::setLocale(app()->getLocale());
            \Alkoumi\LaravelHijriDate\Hijri::setLang(app()->getLocale());

            $dates['gregorian']['date']=\Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM, YYYY');
            $dates['gregorian']['time']=\Carbon\Carbon::parse($date)->isoFormat('h:mm A');


            $dates['hijri']['date']=\Alkoumi\LaravelHijriDate\Hijri::Date('l, j F, Y', $date);
            $dates['hijri']['time']=\Alkoumi\LaravelHijriDate\Hijri::Date('h:i a', $date);

            $dates['full']=$date;
        }else{
            $dates=NULL;
        }
        
        return $dates;
        
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'task_id');
    }
}
