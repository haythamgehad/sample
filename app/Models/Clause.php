<?php

namespace App\Models;

/**
 * Class Clause
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
class Clause extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'clauses';

    /** @var array */
    protected $fillable = [
        'id',
        'language_id',
        'translation_id',
        'name',
        'status'

    ];
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function fields()
    {
        return $this->hasMany(Field::class, 'clause_id')->where('language_id',$this->getLangIdFromLocale());
    }
}
