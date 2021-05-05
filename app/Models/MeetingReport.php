<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

/**
 * Class MeetingReport
 * @package App\Models
 * @version March 10, 2020, 4:13 pm UTC
 *
 * @property \App\Models\User creator
 * @property \Illuminate\Database\Eloquent\Collection meetings
 * @property \Illuminate\Database\Eloquent\Collection meetingReports
 * @property \Illuminate\Database\Eloquent\Collection meetingReportsMedias
 * @property \Illuminate\Database\Eloquent\Collection meetingReportsNotices
 * @property \App\Models\MeetingReportsSignatures meetingReportsSignatures
 * @property integer resource_type_id
 * @property integer meeting_id
 * @property integer creator_id
 * @property integer parent_id
 * @property string content
 * @property string status
 */
class MeetingReport extends Model
{
    use LogsActivity;

    public $table = 'meetings_reports';
    
    const STATUS_DRAFT = 1 ;
    const STATUS_HISTORY = 2 ;
    const STATUS_PUBLISHED = 3 ;
    const STATUS_MINISTRY_APPROVED = 4 ;
    const STATUS_MEMBERS_APPROVED = 5 ;
    const STATUS_PRESIDENT_APPROVED = 6 ;
    const STATUS_ALL_APPROVED = 7 ;
    //member approve
    //check if president
    //if pres. add var with approved by pres.
    //check if all committee members shared with
    //in above case if all approved but the pres. not status approved by members
    //else all approved ,approved by all
    // else if only shared and approved by pres. status approved by pres.


    public $fillable = [
        'account_id',
        'meeting_id',
        'committee_id',
        'creator_id',
        'parent_id',
        'content',
        'is_reopen',
        'reopen_reason',
        'media_id',
        'docx_media_id',
        'review_date',
        'singed',
        'status',
        'version_id',
        'approved_date',
        'send_to_president',
        'send_to_members'
    ];

    protected static $logAttributes = [
        'content',
        'status',
        'review_date',
        'is_reopen',
        'reopen_reason',
        ];

    
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
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function committee()
    {
        return $this->belongsTo(Committee::class, 'committee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function meetingReports()
    {
        return $this->hasMany(MeetingReport::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function media()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function notices()
    {
        return $this->hasMany(ReportNotice::class, 'report_id')->whereNull('reply_to_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function myNotices()
    {
        return $this->hasMany(ReportNotice::class, 'report_id')->whereNull('reply_to_id')
            ->where('creator_id', Auth::user()->id);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function shares()
    {
        return $this->hasMany(ReportShare::class, 'report_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function signatures()
    {
        return $this->hasMany(ReportSignature::class,'report_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function signatureHolders()
    {
        return $this->hasMany(SignatureHolder::class,'report_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function annotations()
    {
        return $this->hasMany(Annotation::class, 'report_id');
    }
}
