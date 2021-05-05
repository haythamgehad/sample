<?php

namespace App\Models;


/**
 * Class CommitteeMedia
 * @package App\Models
 * @version March 10, 2020, 11:56 am UTC
 *
 * @property \App\Models\Committee committee
 * @property \App\Models\Media media
 * @property integer committee_id
 * @property integer media_id
 */
class CommitteeMedia extends Model
{

    public $table = 'committees_medias';
    
    const STATUS_PUBLISHED = 1;


    public $fillable = [
        'committee_id',
        'media_id'
    ];



    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function media()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }
}
