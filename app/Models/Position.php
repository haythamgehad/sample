<?php

namespace App\Models;

/**
 * Class Position
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
class Position extends Model
{

    const MEMBER = 5; 

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'positions';

    /** @var array */
    protected $fillable = [
        'id',
        'language_id',
        'translation_id',
        'can_be_many',
        'name',
        'status'

    ];


}
