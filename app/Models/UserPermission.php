<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class UserPermission
 *
 * @property int $id
 * @property int $user_id
 * @property int $permission_id
 * @property int $read
 * @property int $create
 * @property int $update
 * @property int $delete
 * @property int $manage
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 * @property-read Permission $permission
 *
 * @package App\Models
 */
class UserPermission extends Pivot
{

    /** @var bool */
    public $incrementing = true;

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'users_permissions';

    /** @var array */
    protected $fillable = [
        'user_id',
        'permission_id',

        
        'read',
        'read_mine',
        'create',
        'update',
        'delete',
        'list',
        'list_mine',
        'update_mine',
        'delete_mine',
        
        'configuration',
        'setting',
        'log',
        'permission'
        
    ];

    /** @var array */
    protected $visible = [
        'id',

       
        'read',
        'read_mine',
        'create',
        'update',
        'delete',
        'list',
        'list_mine',
        'update_mine',
        'delete_mine',

        'configuration',
        'setting',
        'log',
        'permission',
        
        'user',
        'permission'
    ];

    /** @var array */
    protected $hidden = [
        'user_id',
        'permission_id'
    ];

    /** @var array */
    protected $casts = [
        
      
        'read' => 'int',
        'read_mine' => 'int',
        'create' => 'int',
        'update' => 'int',
        'delete' => 'int',
        'list' => 'int',
        'list_mine' => 'int',
        'update_mine' => 'int',
        'delete_mine' => 'int',

        'configuration' => 'int',
        'setting' => 'int',
        'log' => 'int',
        'permission' => 'int',
        
    ];

    /**
     * User.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Permission.
     *
     * @return BelongsTo
     */
    public function permission()
    {
       // return $this->belongsTo(Permission::class, 'permission_id', 'id');
       // return $this->hasOne(Permission::class, 'id', 'permission_id');
       return $this->hasOne(Permission::class, 'translation_id', 'permission_id')->where('language_id',$this->getLangIdFromLocale())->latest();

    }
}