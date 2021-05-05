<?php

namespace App\Models;

/**
 * Class Contract
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
class Contract extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'contracts';

    /** @var array */
    protected $fillable = [
        'id',
        'creator_id',
        'account_id',
        'language_id',
        'translation_id',
        'name',
        'status'

    ];

}
