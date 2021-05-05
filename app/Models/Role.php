<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Role
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection|User[] $users
 * @property-read Collection|Permission[] $permissions
 * @property-read Collection|RolePermission[] $rolePermissions
 *
 * @package App\Models
 */
class Role extends Model
{
    const IDS = array(

        'ID_ADMIN' => 1,
        'ID_USER' => 2,
        'ID_SECRETARY' => 3
    );

    /** @var int */
    //const ID_ADMIN = 1;

    /** @var int */
    //const ID_USER = 2;

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'roles';

    /** @var array */
    protected $fillable = [

        'id',
        'language_id',
        'account_id',
        'translation_id',
        'name',
        'status'

    ];

    protected $appends = ['name_ar','name_en'];
    /**
     * Role users.
     *
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->as('access')
            ->using(RolePermission::class)
            ->withPivot([
                'permission_id',
                'role_id',


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

            ]);
    }

    /**
     * Role permissions.
     *
     * @return HasMany
     */
    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id', 'id');
    }

    
    public function getNameAr()
    {
   return   Role::where(['id'=>$this->translation_id,'language_id' =>1])->get('name');
    }
    public function getNameArAttribute()
    {   
        return $this->getNameAr();

    }


    public function getNameEn()
    {
   return   Role::where(['translation_id'=>$this->id,'language_id' =>2])->get('name');
    }
    public function getNameEnAttribute()
    {   
        return $this->getNameEn();
    }


    // public function getNameArAttribute()
    // {

    // }
}
