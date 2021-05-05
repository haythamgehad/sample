<?php

namespace App\Models;

/**
 * Class Media
 * @package App\Models
 * @version March 9, 2020, 11:37 pm UTC
 *
 * @property \App\Models\Directory directory
 * @property \App\Models\User creator
 * @property \App\Models\Talent talent
 * @property integer directory_id
 * @property integer creator_id
 * @property integer account_id
 * @property string name
 * @property string title
 * @property number size
 * @property string type
 * @property string url
 * @property string encryption_CODE
 * @property string status
 */
class Media extends Model
{

    public $table = 'medias';
    
    const STATUS_PUBLISHED = 1;

    const SHARE_TYPE_SHOW = 1;

    const IS_PUBLIC = 1;

    const IS_GARBAGE = 1;

    const IS_NOT_GARBAGE = 0;

    const DEFAULT_NOT_PUBLIC = 0;

    const DEFAULT_PUBLIC = 1;


    public $fillable = [
        'directory_id',
        'creator_id',
        'account_id',
        'name',
        'size',
        'type',
        'path',
        'hash',
        'encrypted_extention',
        'encryption_CODE',
        'is_created_pdf',
        'status',
        'title',
        'is_garbage',
        'is_public',
        'is_created_pdf',
        'is_migrated',
        'is_account_logo',
        'is_my_directory',
        'is_system_file'
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function directory()
    {
        return $this->hasOne(Directory::class, 'id', 'directory_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function shares()
    {
        return $this->hasMany(MediaUserShare::class, 'media_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function annotations()
    {
        return $this->hasMany(Annotation::class, 'media_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'creator_id');
    }

    
}
