<?php

namespace App\Models;

/**
 * Class Field
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
class Field extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'fields';

    /** @var array */
    protected $fillable = [
        'id',
        'label',
        'is_required',
        'type',//attachment, user,contract,bank, text, content, date, date-time, number
        'clause_id',
        'language_id',
        'translation_id',
        'status'

    ];

}
