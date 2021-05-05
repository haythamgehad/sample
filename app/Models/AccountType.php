<?php

namespace App\Models;
use Spatie\Activitylog\Traits\LogsActivity;



/**
 * Class AccountType
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
class AccountType extends Model
{

    use LogsActivity;

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'accounts_types';

    /** @var array */
    protected $fillable = [
        'id',
        'language_id',
        'translation_id',
        'name',
        'status'

    ];

    protected static $logAttributes = [
        'name',
        'status'
        ];

   

}
