<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class RolePermission
 *
 * @property int $id
 * @property int $role_id
 * @property int $permission_id
 * @property int $read
 * @property int $create
 * @property int $update
 * @property int $delete
 * @property int $manage
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Role $role
 * @property-read Permission $permission
 *
 * @package App\Models
 */
class RolePermission extends Pivot
{

    /** @var bool */
    public $incrementing = true;

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'role_permissions';

    /** @var array */
    protected $fillable = [
        'account_id',
        'role_id',
        'permission_id',

        
        'read',
        'read_mine',
        'create',
        'create_mine',
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
        'account_id',

       
        'read',
        'read_mine',
        'create',
        'create_mine',
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
        
        'role',
        'permission',
        'role_id',
        'permission_id'
    ];


    /** @var array */
    protected $casts = [
        
      
        'read' => 'int',
        'read_mine' => 'int',
        'create' => 'int',
        'create_mine' => 'int',
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
        'permission_id' => 'int',
        'role_id' => 'int'
        
    ];

    /**
     * Role.
     *
     * @return BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Permission.
     *
     * @return BelongsTo
     */
    public function permissions()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}