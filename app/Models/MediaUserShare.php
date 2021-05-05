<?php

namespace App\Models;


/**
 * Class MediaUserShare
 * @package App\Models
 * @version March 10, 2020, 3:35 pm UTC
 *
 * @property \App\Models\User user
 * @property \App\Models\User user1
 * @property integer directory_id
 * @property integer media_id
 * @property integer type_id
 * @property integer creator_id
 * @property integer shared_to_id
 */
class MediaUserShare extends Model
{

    public $table = 'medias_users_shares';

    public $fillable = [
        'directory_id',
        'media_id',
        'type_id',
        'creator_id',
        'shared_to_id',
        'start_at',
        'end_at',
        'status'
    ];

    /**
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    **/
    public function sharedTo()
    {
        return $this->belongsTo(User::class, 'shared_to_id');
    }
}
