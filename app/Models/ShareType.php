<?php

namespace App\Models;


/**
 * Class ShareType
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
class ShareType extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'shares_types';

    /** @var array */
    protected $fillable = [
        'id',
        'language_id',
        'translation_id',
        'name',
        'code',
        'status'

    ];


}
