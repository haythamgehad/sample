<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ReportSignature
 * @package App\Models
 * @version March 10, 2020, 4:23 pm UTC
 *
 * @property \App\Models\User user
 * @property \App\Models\User user1
 * @property \App\Models\Media media
 * @property integer meeting_report_id
 * @property integer creator_id
 * @property string content
 * @property integer signature_media_id
 */
class ReportSignature extends Model
{

    public $table = 'reports_signatures';
    



    public $fillable = [
        'report_id',
        'creator_id',
        'content',
        'signature_media_id'
    ];

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
    public function media()
    {
        return $this->hasOne(Media::class, 'id', 'signature_media_id');
    }
}
