<?php

namespace App\Models;


/**
 * Class ReportShare
 * @package App\Models
 * @version March 10, 2020, 3:35 pm UTC
 *
 * @property \App\Models\User user
 * @property \App\Models\User user1
 * @property integer report_id
 * @property integer type_id
 * @property integer creator_id
 * @property integer shared_to_id
 */
class ReportShare extends Model
{

    public $table = 'reports_shares';

    const IS_NEW = 1;
    const ACCEPTED = 2;
    const IS_REVIEWED = 3;
    const IS_SIGNED = 4 ; // when the user sign the report
    // const SHARE_TO_ALL = 1;
    // const SHARE_TO_PRESIDENT_THEN_MEMBERS = 2;
    // const SHARE_TO_MEMBERS_THEN_PRESIDENT =3;

    const OFFICIAL_SHARE = 1;
    const NON_OFFICIAL_SHARE = 2;

    public $fillable = [
        'report_id',
        'type_id',
        'creator_id',
        'shared_to_id',
        'start_at',
        'end_at',
        'status',
        'share_status',
        'position_id',
        'is_aboard_secretary'
    ];

    /**
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    **/
    public function sharedTo()
    {
        return $this->belongsTo(User::class, 'shared_to_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }


}
