<?php

namespace App\Models;

/**
 * Class Procedure
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
class Procedure extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'procedures';

    /** @var array */
    protected $fillable = [
        'id',
        'language_id',
        'translation_id',
        'name',
        'status'

    ];

}
