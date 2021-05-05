<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Crypt;


/**
 * Class Attachment
 * @package App\Models
 * @version March 17, 2020, 1:06 am UTC
 *
 * @property integer meeting_id
 * @property integer creator_id
 * @property string title
 * @property string brief
 * @property string content
 * @property string status
 */
class Attachment extends Model
{

    use LogsActivity;

    public $table = 'attachments';
    
    const STATUS_DRAFT = 1 ;

    public $fillable = [
        'account_id',
        'committee_id',
        'meeting_id',
        'agenda_id',
        'creator_id',
        'title',
        'brief',
        'content',
        'status',
        'media_id',
        'task_id',
        'action_id'
    ];
    protected static $logAttributes = [
        'title',
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
  /*  public function media()
    {
        return $this->hasMany(AttachmentMedia::class);
    }*/

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function medias()
    {
        return $this->hasMany(AttachmentMedia::class);
    }
    public function media()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }
}
