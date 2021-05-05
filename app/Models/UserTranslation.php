<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class UserTranslation
 *
 * @property int $language_id
 * @property int $user_id
 * @property string $title
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class UserTranslation extends Model
{
    use LogsActivity;

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'users_translations';

    /** @var array */
    protected $fillable = [
        'user_id',
        'language_id',
        'title',
        'name',
    ];

    protected static $logAttributes = [
        'language_id',
        'title',
        'name',
        ];

    /**
    * @return \Illuminate\Database\Eloquent\Relations\hasMany
    **/
    public function language()
    {
        return $this->hasOne(\App\Models\Language::class, 'id','language_id');
    }
}
