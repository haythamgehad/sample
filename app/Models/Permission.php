<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Permission
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection|Role[] $roles
 * @property-read Collection|RolePermission[] $rolePermissions
 *
 * @package App\Models
 */
class Permission extends Model
{

    CONST IDS = array(
        'ID_BOARDS'=>1,
        'ID_COMMITTEES'=>2,
        'ID_ACTIVITIES'=>3,
        'ID_USERS'=>4,
        'ID_ACCOUNTS'=>5,
        'ID_TREES'=>6,
        'ID_ASSOCIATIONS'=>7,
        'ID_MEETINGS'=>8,
        'ID_AGENDAS'=>9,
        'ID_AGENDA_NOT_WORK'=>10,
        'ID_TASKS'=>11,
        'ID_LOCATIONS'=>12,
        'ID_DIRECTORIES'=>13,
        'ID_MEDIAS'=>14,
        'ID_ACTION_TYPES'=>15,
        'ID_ROLE_PERMISSION'=>16,
    );

    CONST BOARD_CODE = 'BOARD';
    CONST COMMITTEE_CODE = 'COMMITTEE';
    CONST ACTIVITY_CODE = 'ACTIVITY';
    CONST USER_CODE = 'USER';
    CONST ACCOUNT_CODE = 'ACCOUNT';
    CONST TREE_CODE = 'TREE';
    CONST ASSOCIATION_CODE = 'ASSOCIATION';
    CONST MEETING_CODE = 'MEETING';
    CONST AGENDA_CODE = 'AGENDA';
    CONST AGENDA_NOT_WORK_CODE = 'AGENDA_NOT_WORK';
    CONST TASK_CODE = 'TASK';
    CONST LOCATION_CODE = 'LOCATION';
    CONST DIRECTORY_CODE = 'DIRECTORY';
    CONST MEDIA_CODE = 'MEDIA';
    CONST ACTIONTYPE_CODE = 'ACTIONTYPE';
    CONST AccountTYPE_CODE = 'AccountTYPE_CODE';
    CONST ROLE_PERMISSION_CODE = 'ROLE_PERMISSION';
    
    
    /** @var int */
    //const ID_USERS = 1;

    /** @var int */
    //const ID_ROLES = 2;

    /** @var int */
    //const ID_TASKS = 3;

    /** @var int */
    //const ID_OFFERS = 4;

    /** @var int */
       //const ID_ACCOUNT = 5;

    /** @var int */
    //const ID_COMMITTEE = 6;

    /** @var int */
    //const ID_MEETING = 7;


    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'permissions';

    /** @var array */
    protected $fillable = [
       'id',
        'name',
        'code',
        'account_id',
        'language_id',
        'translation_id',
        'status'

    ];

    /** @var array */
    protected $visible = [
        'id',
        'name',
        'code',
        'roles',
        'access',
        'rolePermissions'
    ];

    /** @var array */
    protected $sortable = [
        'id',
        'name'
    ];

    /** @var array */
    protected $searchable = [
        'name'
    ];

    /**
     * @return BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')
            ->as('access')
            ->using(RolePermission::class)
            ->withPivot([
                'permission_id',
                'role_id',

                
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
                
            ]);
    }

    /**
     * Role permissions.
     *
     * @return HasMany
     */
    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'permission_id', 'id');
    }
}
