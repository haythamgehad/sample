<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Regulation
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
class Regulation extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'regulations';

    /** @var array */
    protected $fillable = [
        'id',
        'language_id',
        'translation_id',
        'name',
        'status'

    ];

    /** @var array */
    protected $visible = [
        'id',
        'language_id',
        'translation_id',
        'name',
        'status'

    ];

    /** @var array */
    protected $sortable = [
        'id',
        'language_id',
        'translation_id',
        'name',
    ];

    /** @var array */
    protected $searchable = [
        'language_id',
        'translation_id',
        'name',
    ];



}
