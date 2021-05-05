<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class SignatureHolder
 * @package App\Models
 * @version March 10, 2020, 4:23 pm UTC
 *
 * @property \App\Models\User creator
 * @property \App\Models\User member
 * @property integer meeting_report_id
 * @property integer creator_id
 * @property integer member_id
 * @property string content
 */
class SignatureHolder extends Model
{

    public $table = 'signature_holder';

    public $fillable = [
        'report_id',
        'creator_id',
        'member_id',
        'content',
        'annot_id'
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
    public function member()
    {
        return $this->hasOne(User::class, 'id', 'member_id');
    }

}
