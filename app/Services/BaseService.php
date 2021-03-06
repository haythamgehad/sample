<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;

/**
 * Class BaseService
 *
 * @package App\Services
 */
class BaseService
{
    /**
     * Apply search
     *
     * @param Builder $builder
     * @param $term
     *
     * @return Builder
     */
    public function applySearch(Builder $builder, $term)
    {
        $builder->where(function ($query) use ($term) {
            foreach ($query->getModel()->getSearchable() as $searchColumn) {
                if (in_array($searchColumn, $query->getModel()->getEncrypted())) {
                    $query->orWhereEncrypted($searchColumn, '%' . $term . '%');
                } else {
                    $query->orWhere($searchColumn, 'LIKE', '%' . $term . '%');
                }
            }
        });

        return $builder;
    }

    /**
     * Apply filters
     *
     * @param Builder $builder
     * @param array $filters
     *
     * @return Builder
     */
    public function applyFilters(Builder $builder, array $filters)
    {
        foreach ($filters as $filter => $value) {
            if (in_array($filter, $builder->getModel()->getFilterable())) {
                if (in_array($filter, $builder->getModel()->getEncrypted())) {
                    $builder->whereEncrypted($filter, $value);
                } else {
                    $builder->where($filter, $value);
                }
            }
        }

        return $builder;
    }

    /**
     * Apply sort params.
     *
     * @param Request $request
     * @param $builder
     *
     * @return Builder
     */
    public function applySortParams(Request $request, Builder $builder)
    {
        if ($request->has('sortColumn') || $request->has('sortOrder')) {
            $sortColumn = strtolower($request->get('sortColumn', 'id'));
            $sortOrder = strtolower($request->get('sortOrder', 'asc'));

            if (in_array($sortColumn, $builder->getModel()->getSortable()) && in_array($sortOrder, ['asc', 'desc'])) {
                if (in_array($sortColumn, $builder->getModel()->getEncrypted())) {
                    return $builder->orderByEncrypted($sortColumn, $sortOrder);
                }

                return $builder->orderBy($sortColumn, $sortOrder);
            }
        }

        return $builder;
    }

    /**
     * Get pagination offset and limit.
     *
     * @param Request $request
     *
     * @return array
     */
    public function getPaginationParams(Request $request)
    {
        $limit = 10;
        if ($request->has('limit')) {
            $requestLimit = (int)$request->get('limit');

            if ($requestLimit > 0) {
                $limit = $requestLimit;
            }
        }

        $offset = 0;
        $page = 1;

        if ($request->has('page')) {
            $requestPage = (int)$request->get('page');

            if ($requestPage > 1) {
                $page = $requestPage;
            }

            $offset = ($page - 1) * $limit;
        }

        return [
            'page' => $page,
            'offset' => $offset,
            'limit' => $limit
        ];
    }

    /**
     * Get pagination data.
     *
     * @param Builder $builder
     * @param $page
     * @param $limit
     *
     * @return array
     */
    public function getPaginationData(Builder $builder, $page, $limit)
    {
        $totalEntries = $builder->count();

        $totalPages = ceil($totalEntries / $limit);

        return [
            'currentPage' => $page > $totalPages ? $totalPages : $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'totalEntries' => $totalEntries
        ];
    }

    /**
     * Get language to use.
     *
     * @param Request $request
     *
     * @return Language
     *
     * @throws Exception
     */
    public function getLanguage(Request $request)
    {
        if ($request->has('language')) {
            /** @var Language $language */
            $language = Language::where('code', strtolower($request->get('language')))->first();

            if ($language) {
                return $language;
            }
        }

        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            /** @var Language $language */
            $language = Language::where('id', $user->language_id)->first();

            if ($language) {
                return $language;
            }
        }

        /** @var Language $language */
        $language = Language::where('code', env('APP_LOCALE'))->first();

        if ($language) {
            return $language;
        }

        throw new Exception('Application is bad configured!');
    }

    


    public function hasPermissionAccess($userId, $code){
        
        return true;

        $permission = Permission::where('code',$code)->where('language_id',1)->first();

        $rolePermission = RolePermission::where('permission_id', $permission->id)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();
        if(!empty($rolePermission) && $rolePermission->permission){
            return true;
        }
            return true;
    }

    public function hasConfigurationAccess($userId, $permissionCode){

        return true;

        $permission = Permission::where('code',$permissionCode)->where('language_id',1)->first();

        $rolePermission = RolePermission::where('permission_id', $permission->id)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();
        if( !empty($rolePermission) && $rolePermission->configuration){
            return true;
        }
            return true;
    }

    public function hasActivityAccess($userId, $permissionCode){

        return true;

        $permission = Permission::where('code',$permissionCode)->where('language_id',1)->first();

        $rolePermission = RolePermission::where('permission_id', $permission->id)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();

        if( !empty($rolePermission) && $rolePermission->log){
            return true;
        }
            return true;
    }

    public function hasCreateAccess($userId, $permissionCode){
        
        return true;

        $permission = Permission::where('code',$permissionCode)->where('language_id',1)->first();
        
        $rolePermission = RolePermission::where('permission_id', $permission->id)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();
        if(!empty($rolePermission)  && $rolePermission->create){
            return true;
        }
            return true;
    }

    public function hasUpdateAccess($userId, $creatorId, $permissionCode){

        return true;

        $permission = Permission::where('code',$permissionCode)->where('language_id',1)->first();

        $rolePermission = RolePermission::where('permission_id', $permission->id)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();
        if(!empty($rolePermission) && ($rolePermission->update || ($rolePermission->update_mine && $userId == $creatorId))){
            return true;
        }
        return true;
        
    }

    public function hasReadAccess($userId, $creatorId, $permissionCode){
        
        return true;
        
        $permission = Permission::where('code',$permissionCode)->where('language_id',1)->first();

        $rolePermission = RolePermission::where('permission_id', $permission->id)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();
        if(!empty($rolePermission) && ($rolePermission->read || ($rolePermission->read_mine && $userId == $creatorId))){
            return true;
        }
        return true;
        
    }

    public function hasListAccess($userId, $permissionCode){

        return true;
        
        $permission = Permission::where('code',$permissionCode)->where('language_id',1)->first();

        $rolePermission = RolePermission::where('permission_id', $permission->id)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();
        if(!empty($rolePermission) && $rolePermission->list){
            return true;
        }
        return true;
        
    }

    public function hasDeleteAccess($userId, $creatorId, $permissionCode){

        return true;
        
        $permission = Permission::where('code',$permissionCode)->where('language_id',1)->first();

        $rolePermission = RolePermission::where('permission_id', $permission->id)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();
        if(!empty($rolePermission) && ($rolePermission->delete || ($rolePermission->delete_mine && $userId == $creatorId))){
            return true;
        }
        return true;
        
    }

    /**
     * Check if a logged user has permission on action
     *
     * @param $userId
     * @param $permissionId
     *
     * @return RolePermission
     */
    public function getUserPermissionActions($userId, $permissionId)
    {
       
        /** @var RolePermission $rolePermission */
        return RolePermission::where('permission_id', $permissionId)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();

        /*
        $rolePermission = Cache::tags(['permissions'])
            ->remember(
                'permission' . $userId . $permissionId,
                env('CACHE_PERIOD'),
                function () use ($userId, $permissionId) {
                    return RolePermission::where('permission_id', $permissionId)
                        ->whereHas('role', function ($query) use ($userId) {
                            $query->whereHas('users', function ($query) use ($userId) {
                                $query->where('id', $userId);
                            });
                        })->first();
                }
            );

        return $rolePermission;
        */
    }

    /**
     * Process images.
     *
     * @param $path
     * @param $image
     * @param $name
     * @param bool $generateAvatar
     * @param bool $onlyAvatar
     *
     * @return false|string
     */
    public function processImage($path, $image, $name, $generateAvatar = false, $onlyAvatar = false)
    {
        $pictureData = [];

        if ($generateAvatar) {
            $avatarImage = Image::make($image)->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $color = $this->getColorAverage($avatarImage);

            $avatarCanvas = Image::canvas(200, 200, $color);

            $avatarCanvas->insert($avatarImage, 'center');

            $avatarPath = $path . 'avatar/';
            File::makeDirectory($avatarPath, 0777, true, true);

            $avatarCanvas->save($avatarPath . $name, 100);

            $pictureData['avatar'] = $avatarPath . $name;
        }

        if ($onlyAvatar) {
            return json_encode($pictureData);
        }

        $mediumImage = Image::make($image)->resize(1024, 768, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $mediumPath = $path . 'medium/';
        File::makeDirectory($mediumPath, 0777, true, true);

        $mediumImage->save($mediumPath . $name, 100);
        $pictureData['medium'] = $mediumPath . $name;

        $originalMaxImage = Image::make($image)->resize(1920, 1080, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $originalPath = $path . 'original/';
        File::makeDirectory($originalPath, 0777, true, true);

        $originalMaxImage->save($originalPath . $name, 100);
        $pictureData['original'] = $originalPath . $name;

        return json_encode($pictureData);
    }

    /**
     * Get average image color.
     *
     * @param $image
     * @return array
     */
    private function getColorAverage($image)
    {
        $image = clone $image;

        $color = $image->limitColors(1)->pickColor(0, 0);
        $image->destroy();

        return $color;
    }
     /**
     * Delete model record for given id
     *
     * @param array $input
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function deleteOneToManyRelations($table,$key_1,$value_1)
    {
        DB::table($table)->where($key_1, '=' ,$value_1)->delete();
        return true;
    }

    /**
     * Delete model record for given id
     *
     * @param array $input
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function deleteManyToManyRelations($table,$key_1,$value_1,$key_2,$value_2)
    {
        $object = DB::table($table)->where($key_1, '=' ,$value_1)
        ->where($key_2, '=' ,$value_2)->first();
        
        if(isset($object->id) && !empty($object->id)){
            DB::table($table)->where($key_1, '=' ,$value_1)
            ->where($key_2, '=' ,$value_2)->delete();

            return true;
        }else{
            return false;
        }
        
    }

    public function generateDigits($length){
        $characters = '123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function isError($validator){
        if (!$validator->passes()) {
            $return['isError']=true;
            $return['errorMessage']=json_decode($validator->messages());
            echo json_encode($return) ;
            exit();
        }
    }

    public function getLangIdFromLocale()
    {
        return (app()->getLocale() == 'ar') ? 1 : 2;
    }
}
