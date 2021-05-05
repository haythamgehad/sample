<?php

namespace App\Models;


/**
 * Class Statistic
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
class Statistic extends Model
{

    public $table = 'statistics';
    



    public $fillable = [
        'id',
        'account_id',
        'users_count',
        'boards_count',
        'committees_count',
        'sub_committees_count',
        'sub_sub_committees_count',
        'meetings_count',
        'storage_size',
        'emails_count',
        'sms_count',
        'status'
    ];

}
