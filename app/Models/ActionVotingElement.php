<?php

namespace App\Models;

/**
 * Class ActionVotingElement
 * @package App\Models
 * @version Feb 22, 2021, 1:12 pm UTC
 *
 */
class ActionVotingElement extends Model
{
    public $table = 'action_voting_elements';

    public $fillable = [
        'id',
        'action_id',
        'text'
    ];
}
