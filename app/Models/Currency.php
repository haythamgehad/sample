<?php

namespace App\Models;


/**
 * Class Currency
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
class Currency extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'currencies';

    /** @var array */
    protected $fillable = [
        'id',
        'language_id',
        'translation_id',
        'code',
        'name',
        'status'

    ];

    /** @var array */
    protected $visible = [
        'id',
        'language_id',
        'translation_id',
        'code',
        'name',
    ];

    /** @var array */
    protected $sortable = [
        'id',
        'language_id',
        'translation_id',
        'code',
        'name',
    ];

    /** @var array */
    protected $searchable = [
        'language_id',
        'translation_id',
        'code',
        'name',
    ];

}
