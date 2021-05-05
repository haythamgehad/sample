<?php

namespace App\Models;

/**
 * Class Clause
 *
 * @property int $id
 * @property int $language_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection|User[] $users
 *
 * @package App\Models
 */
class ReopenVote extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'reopen_vote';

    /** @var array */
    protected $fillable = [
        'id',
        'user_id',
        'action_id',
        'status'

    ];
}
