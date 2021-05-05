<?php

namespace App\Models;

/**
 * Class ContractUser
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
class ContractUser extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'contracts_users';

    /** @var array */
    protected $fillable = [
        'id',
        'contract_id',
        'user_id',

    ];

}
