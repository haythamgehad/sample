<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Class Todo
 * @package App\Models
 * @version March 10, 2020, 1:47 pm UTC
 *
 * @property integer action_id
 * @property string title
 * @property date due_date
 * @property string status
 */
class Todo extends Model
{    
    
    use LogsActivity;

    public $table = 'todos';
    
    const STATUS_PUBLISHED = 1 ;
    
    protected $appends = ['custom_due_date'];
    public $fillable = [
        'id',
        'creator_id',
        'assignee_id',
        'account_id',
        'action_id',
        'meeting_id',
        'owners',
        'title',
        'content',
        'due_date',
        'status'
    ];

    protected static $logAttributes = [
        'owners',
        'title',
        'due_date',
        'status'
        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

     /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function assignees()
    {
        return $this->hasOne(User::class, 'id', 'assignee_id');
    }

    public function action()
    {
        return $this->hasOne(Action::class, 'id', 'action_id');
    }
    
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }
    public function getCustomDueDateAttribute()
    {
        return $this->convertDate($this->due_date);
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


}
