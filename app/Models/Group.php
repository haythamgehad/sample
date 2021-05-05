<?php

namespace App\Models;

/**
 * Class Group
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
class Group extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'groups';

    /** @var array */
    protected $fillable = [
        'creator_id',
        'account_id',
        'parent_id',
        'language_id',
        'translation_id',
        'name',
        'status'

    ];


}
