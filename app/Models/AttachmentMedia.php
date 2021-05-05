<?php

namespace App\Models;



/**
 * Class AttachmentMedia
 * @package App\Models
 * @version March 10, 2020, 3:12 pm UTC
 *
 * @property \App\Models\Media media
 * @property integer reply_id
 * @property integer media_id
 */
class AttachmentMedia extends Model
{

    public $table = 'attachments_medias';
    



    public $fillable = [
        'attachment_id',
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
