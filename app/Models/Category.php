<?php

namespace App\Models;

/**
 * Class Category
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
class Category extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'categories';

    const STATUS_PUBLISHED = 1 ;

    /** @var array */
    protected $fillable = [
        'id',
        'creator_id',
        'account_id',
        'language_id',
        'translation_id',
        'parent_id',
        'name',
        'status'

    ];



}
