<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Crypt;
use App\Models\Annotation;

/**
 * Class MeetingCollection
 * @package App\Models
 * @version March 10, 2020, 2:12 pm UTC
 *
 */
class MeetingCollection extends Model
{

    public $table = 'meetings_collections';
    
    const STATUS_MINISTRY_APPROVED = 1 ;


    public $fillable = [
        'meeting_id',
        'media_id',
        'content',
        'xml',
        'status'
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function media()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function annotations()
    {
        return $this->hasMany(Annotation::class, 'collection_id');
    }

    
}
