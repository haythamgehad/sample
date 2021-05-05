<?php

namespace App\Models;

/**
 * Class Tag
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
class Tag extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'tags';

    const STATUS_PUBLISHED = 1 ;

    /** @var array */
    protected $fillable = [
        'language_id',
        'translation_id',
        'parent_id',
        'name',
    ];


}
