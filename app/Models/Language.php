<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Language
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection|User[] $users
 *
 * @package App\Models
 */
class Language extends Model
{

    /** @var int */
    const ID_AR = 1;

     /** @var string */
     const CODE_AR = 'ar';

    /** @var int */
    const ID_EN = 2;

    /** @var string */
    const CODE_EN = 'en';

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'languages';

    /** @var array */
    protected $fillable = [
        'id',
        'name',
        'code',
        'status'

    ];

    /** @var array */
    protected $visible = [
        'id',
        'name',
        'code'
    ];

    /** @var array */
    protected $sortable = [
        'id',
        'name',
        'code'
    ];

    /** @var array */
    protected $searchable = [
        'name',
        'code'
    ];

    /**
     * language users.
     *
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'language_id', 'id');
    }
}
