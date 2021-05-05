<?php

namespace App\Models;

/**
 * Class Directory
 * @package App\Models
 * @version March 9, 2020, 11:30 pm UTC
 *
 * @property \App\Models\Directory directory
 * @property \Illuminate\Database\Eloquent\Collection media
 * @property \App\Models\Talent talent
 * @property \App\Models\User user
 * @property integer parent_id
 * @property integer account_id
 * @property integer creator_id
 * @property string name
 * @property string path
 * @property number size
 * @property integer count
 * @property string status
 */
class Directory extends Model
{

    public $table = 'directories';

    const STATUS_PUBLISHED = 1;

    const PUBLIC_SHARE = 1;

    const DEFAULT_SHARE = 0;

    const SHARE_TYPE_SHOW = 1;

    const IS_PUBLIC = 1;

    const IS_GARBAGE = 1;

    const IS_NOT_GARBAGE = 0;

    const DEFAULT_NOT_PUBLIC = 0;

    const DEFAULT_PUBLIC = 1;

    public $fillable = [
        'breadcrumbs',
        'parent_id',
        'account_id',
        'creator_id',
        'name',
        'path',
        'size',
        'count',
        'status',
        'is_public',
        'is_garbage',
        'committee_id',
        'meeting_id',
        'is_my_directory',
        'name_ar',
        'is_shared_directory',
        'is_system_directory'

    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function parent()
    {
        return $this->hasOne(Directory::class, 'id', 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function medias()
    {
        return $this->hasMany(Media::class, 'directory_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function directories()
    {
        $user = auth()->user();
        $account = Account::find($user->account_id);
        $meetingsIds = $this->getLoggedUserMeetings()->pluck('id')->toArray();
        $committeesIds = $this->getLoggedUserCommittees()->pluck('id')->toArray();

        return $this->hasMany(Directory::class, 'parent_id')
            ->Where(function ($query) use ($account, $meetingsIds, $committeesIds) {
                $query->Where(function ($query) use ($account, $meetingsIds) {
                    $query->where('is_public', 1)
                        ->WhereHas('meeting', function ($query) use ($meetingsIds) {
                            $query->whereIn('meetings.id', $meetingsIds);
                        });
                })
                ->orWhere(function ($query) use ($account, $committeesIds) {
                    $query->where('is_public', 1)
                        ->WhereHas('committee', function ($query) use ($committeesIds) {
                            $query->whereIn('committees.id', $committeesIds);
                        });
                })
                ->orWhere(function ($query) use ($account, $committeesIds) {
                    $query->where('is_public', 1)
                        ->Where(function ($query) {
                            $query->whereDoesntHave('committee')
                            ->whereDoesntHave('meeting');
                        });
                })
                ->orWhere(function ($query) use ($account) {
                    $query->where('is_my_directory', 1)
                        ->where('path', 'LIKE', 'Accounts/' . $account->slug . '/' . auth()->user()->id);
                })
                ->orWhere(function ($query) use ($account) {
                    $query->where('path', 'LIKE', 'Accounts/' . $account->slug . '/' . auth()->user()->id . '%');
                });
            });
    }

    public function getLoggedUserMeetings()
    {
        $user = auth()->user();
        return Meeting::where(function ($query2) use ($user) {
            $query2->Where(function ($query) use ($user) {
                $query->whereHas('attendees', function ($query3) use ($user) {
                    return $query3->where('member_id', '=', $user->id);
                })->where('status', '!=', 0);
            })
                ->orWhere(function ($query4) use ($user) {
                    $query4->whereHas('committee', function ($query5) use ($user) {
                        return $query5->where('secretary_id', '=', $user->id)
                            ->orWhere('amanuensis_id', '=', $user->id);
                    });
                })
                ->orWhere(function ($query) use ($user) {
                    $query->whereHas('reports', function ($query) use ($user) {
                        return $query->whereHas('shares', function ($query) use ($user) {
                            return $query->where('shared_to_id', '=', $user->id)
                                ->where('share_status', 2);
                        });
                    })->where('status', 5);
                });
        })->get();
    }

    public function getLoggedUserCommittees()
    {
        $user = auth()->user();
        return Committee::where(['account_id' => $user->account_id, 'is_completed' => 1])
            ->where(function ($q) use ($user) {
                $q->whereHas('members', function ($query) use ($user) {
                    return $query->where('member_id', '=', $user->id);
                })
                    ->orWhere('amanuensis_id', $user->id)
                    ->orWhere('secretary_id', $user->id);
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function shares()
    {
        return $this->hasMany(MediaUserShare::class, 'directory_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'creator_id');
    }

    public function committee()
    {
        return $this->hasOne(Committee::class, 'id', 'committee_id');
    }

    public function Meeting()
    {
        return $this->hasOne(Meeting::class, 'id', 'meeting_id');
    }
}
