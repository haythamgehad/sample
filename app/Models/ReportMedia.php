<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * Class ReportMedia
 * @package App\Models
 * @version March 10, 2020, 3:12 pm UTC
 *
 * @property \App\Models\Media media
 * @property integer report_id
 * @property integer media_id
 */
class ReportMedia extends Model
{

    public $table = 'reports_medias';
    



    public $fillable = [
        'report_id',
        'media_id'
    ];

    

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function media()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }
}
