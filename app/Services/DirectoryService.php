<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Committee;
use App\Models\CommitteeTranslation;
use App\Models\Directory;
use App\Models\NotificationType;
use App\Services\BaseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Class DirectoryService
 *
 * @package App\Services
 */
class DirectoryService extends BaseService
{
    public function formateName($name)
    {

        return preg_replace("/\s+/", "-", $name);
    }

    public function createUser($user)
    {
        $directoryRequest = new \Illuminate\Http\Request();
        $account = Account::find($user->account_id);

        $path = 'Accounts/' . $account->slug;

        $directory = Directory::where('path', $path)->first();

        $directoryRequest->merge(['account_id' => $user->account_id]);

        $directoryRequest->merge(['creator_id' => Auth::user()->id ?? $user->id]);

        $directoryRequest->merge(['parent_id' => $directory->id ?? null]);

        $directoryRequest->merge(['name' => 'My Files']);

        $directoryRequest->merge(['name_ar' => 'ملفاتى']);

        $directoryRequest->merge(['path' => $path . '/' . $user->id]);

        $directoryRequest->merge(['is_garbage' => 0]);
        $directoryRequest->merge(['is_public' => 0]);

        $directoryRequest->merge(['is_my_directory' => 1]);

        $validator = $this->validateCreateRequest($directoryRequest);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $main_directory_id = $this->create($directoryRequest);

        return $main_directory_id;
    }

    public function getAccountDirectory($account)
    {

        $path = 'Accounts/' . $account->slug;

        $directory = Directory::where('path', $path)->first();

        return $directory;
    }

    public function getAccountSubDirectory($account, $type)
    {

        $path = 'Accounts/' . $account->slug . '/' . $type;

        $directory = Directory::where('path', $path)->first();

        return $directory;
    }

    public function getAccountCollectionDirectory($account)
    {

        $path = 'Accounts/' . $account->slug . '/Collections';

        $directory = Directory::where('path', $path)->first();

        return $directory;
    }

    public function getDirectoryByPath($path)
    {

        $directory = Directory::where('path', $path)->first();

        return $directory;
    }

    public function getDirectoryByName($name)
    {

        $directory = Directory::where('name', $name)->first();

        return $directory;
    }

    public function getDirectoryIDByName($name)
    {

        $directory = Directory::where('name', $name)->orWhere('id', $name)->first();

        return $directory->id;
    }

    public function getMeetingDirectory($meeting)
    {

//        $account = Account::where('id',$meeting->account_id )->first();
        //
        //        $meeting_title = $this->formateName($meeting->title);
        //
        //        $path = 'Accounts/'.$account->slug.'/Meetings/'.$meeting_title;

        $directory = Directory::where('meeting_id', $meeting->id)->first();

        return $directory;
    }

    public function getDirectoryById(int $id): ?Directory
    {
        return Directory::find($id);
    }

    public function getMeetingSubDirectory($meeting, $name)
    {

//        $account = Account::where('id',$meeting->account_id )->first();
        //
        //        $meeting_title = $this->formateName($meeting->title);
        //
        //        $path = 'Accounts/'.$account->slug.'/Meetings/'.$meeting_title;

        $directory = Directory::where('meeting_id', $meeting->id)->where('name', $name)->first();

        return $directory;
    }

    public function createMeeting($meeting, Request $request)
    {

        $input = $request->all();

        $account = Account::where('id', $meeting->account_id)->first();
        $committee = Committee::find($meeting->committee_id);

        //  $account_directory = Directory::where('path', 'Accounts/'.$account->slug)->first();
        $account_directory = Directory::where('path', 'LIKE', '%' . 'Accounts/' . $account->slug . '/' . $committee->type . '/' . '%')->where('committee_id', $meeting->committee_id)->first();

        $meeting_title = $this->formateName($meeting->title);
        $committeeTranslation = CommitteeTranslation::where('committee_id', $meeting->committee_id)->where('language_id', 2)->first();
        $committeeName = $this->formateName($committeeTranslation->name);

        $meeting_path = 'Accounts/' . $account->slug . '/' . $committee->type . '/' . $committeeName . '/' . $meeting_title;

        $request->account_id = $account->id;
        if (isset($account_directory->id)) {
            $request->parent_id = $account_directory->id;
        }

        $request->creator_id = $meeting->creator_id;
        $request->name = $meeting_title;
        $request->path = $meeting_path;
        $request->is_public = 1;
        $request->is_garbage = 0;
        $request->committee_id = $meeting->committee_id;
        $request->meeting_id = $meeting->id;

        $meeting_directory_id = $this->create($request);

        $this->createMettingFolder($meeting, $meeting_directory_id, $request, "Attachment");
        $this->createMettingFolder($meeting, $meeting_directory_id, $request, "Report");
        $this->createMettingFolder($meeting, $meeting_directory_id, $request, "Agenda");
        $this->createMettingFolder($meeting, $meeting_directory_id, $request, "Collection");
        $this->createMettingFolder($meeting, $meeting_directory_id, $request, "votingCards");

        return $meeting_directory_id;
    }

    public function createMettingFolder($meeting, $meeting_directory_id, Request $request, $type)
    {
        $account = Account::where('id', $meeting->account_id)->first();

        $meeting_title = $this->formateName($meeting->title);
        $committeeTranslation = CommitteeTranslation::where('committee_id', $meeting->committee_id)->where('language_id', 2)->first();
        $committeeName = $this->formateName($committeeTranslation->name);

        $meeting_folder_path = 'Accounts/' . $account->slug . '/' . $meeting->committee->type . '/' . $committeeName . '/' . $meeting_title . '/' . $type;

        if ($type == "Attachment") {$name_ar = "المرفقات";}
        if ($type == "Report") {$name_ar = "تقرير";}
        if ($type == "Agenda") {$name_ar = "بنود";}
        if ($type == "Collection") {$name_ar = "المرفقات";}
        if ($type == "votingCards") {$name_ar = "بطاقات التصويت";}

        $orgRequest = $request;

        $inputArr = $orgRequest->all();

        $request = new \stdClass();
        foreach ($inputArr as $key => $value) {
            $request->$key = $value;
        }

        $request->account_id = $account->id;
        $request->parent_id = $meeting_directory_id['id'];
        $request->creator_id = $meeting->creator_id;
        $request->name = $type;
        $request->name_ar = $name_ar;
        $request->path = $meeting_folder_path;
        $request->is_public = 1;
        $request->is_garbage = 0;
        $request->meeting_id = $meeting->id;
        $this->create($request);

    }

    public function getCommitteeDirectory($committee)
    {

        $account = Account::where('id', $committee->account_id)->first();

        $committee_translatoin = CommitteeTranslation::where('committee_id', $committee->id)->where('language_id', '2')->first();

        return Directory::where('path', 'Accounts/' . $account->slug . '/' . $committee->type . '/' . $committee_translatoin->name)->first();

    }

    public function createCommittee($committee, Request $request)
    {

        $input = $request->all();

        $account = Account::where('id', $committee->account_id)->first();

        $committee_name = $this->formateName($input['name_en']);

        $request->path = 'Accounts/' . $account->slug . '/' . $committee->type . '/' . $committee_name;

        $account_directory = Directory::where('path', 'Accounts/' . $account->slug . '/' . $committee->type)->first();

        if (isset($account_directory->id)) {
            $request->account_id = $account->id;
            $request->parent_id = $account_directory->id;
            $request->creator_id = $committee->creator_id;
            $request->name = $committee_name;
            $request->is_public = 1;
            $request->is_garbage = 0;
            $request->committee_id = $committee->id;

            $id = $this->create($request);

            return $id;
        } else {
            return 0;
        }

    }

    public function createAction($action, Request $request)
    {

        $input = $request->all();

        $account = Account::where('id', $action->account_id)->first();

        $actionTitle = $this->formateName($action->title);

        $request->path = 'Accounts/' . $account->slug . '/' . $action->committee->type . '/Resolutions/' . $actionTitle;

        $account_directory = Directory::where('path', 'Accounts/' . $account->slug . '/' . $action->committee->type . '/Resolutions')->first();
        if(!$account_directory) {
            $account_directory = $this->createResolution($action);
        }
        if (isset($account_directory->id)) {
            $request->account_id = $account->id;
            $request->parent_id = $account_directory->id;
            $request->creator_id = $action->creator_id;
            $request->name = $actionTitle;
            $request->is_public = 1;
            $request->is_garbage = 0;
            $request->committee_id = $action->committee_id;
            $request->action_id = $action->id;

            $id = $this->create($request);

            return $id;
        } else {
            return 0;
        }

    }

    public function createResolution($action)
    {
        $user = Auth::user();
        $account = $user->account;
        $parentDirectory = Directory::where('path', 'Accounts/' . $account->slug . '/' . $action->committee->type)->first();

        $directoryRequest = new \Illuminate\Http\Request();

        $directoryRequest->merge(['account_id' => $account->id]);

        $directoryRequest->merge(['creator_id' => $account->creator_id]);

        $directoryRequest->merge(['parent_id' => $parentDirectory->id]);

        $directoryRequest->merge(['name' => 'Resolutions']);

        $directoryRequest->merge(['name_ar' => 'قرارات بالتمرير']);

        $directoryRequest->merge(['path' => 'Accounts/' . $account->slug . '/' . $action->committee->type . '/Resolutions']);

        $directoryRequest->merge(['is_garbage' => 0]);

        $directoryRequest->merge(['is_public' => 1]);

        $validator = $this->validateCreateRequest($directoryRequest);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $this->create($directoryRequest, $account);

    }

    public function create($request)
    {

        $response = Storage::disk('local')->makeDirectory($request->path);
        $where = ['account_id' => $request->account_id, 'path' => $request->path];

        if (isset($request->meeting_id)) {
            $where['meeting_id'] = $request->meeting_id;
        }

        if (isset($request->committee_id)) {
            $where['committee_id'] = $request->committee_id;
        }

        $directory = DB::table('directories')->where($where)->first();
        $return = array();

        if (isset($directory) && !empty($directory->id)) {
            $return = array('path' => $request->path, 'name' => $directory->name, 'id' => $directory->id);
        } else {
            $id = DB::table('directories')->insertGetId(
                ['parent_id' => $request->parent_id,
                    'account_id' => $request->account_id,
                    'creator_id' => $request->creator_id,
                    'name' => $request->name,
                    'path' => $request->path,
                    'size' => '0',
                    'count' => '0',
                    'status' => Directory::STATUS_PUBLISHED,
                    'is_public' => $request->is_public,
                    'is_garbage' => $request->is_garbage,
                    'committee_id' => $request->committee_id,
                    'meeting_id' => $request->meeting_id,
                    'is_my_directory' => $request->is_my_directory ?? 0,
                    'is_system_directory' => $request->is_system_directory ?? 1,
                    'name_ar' => $request->name_ar,
                ]
            );

            $return['id'] = $id;

            $this->updateBreadCrumbs($id);
        }

        return $return;
    }

    public function rename($directory, $name)
    {

        if (!isset($directory->path)) {
            return true;
        }
        $old_path = $directory->path;

        $old_path_part = substr($old_path, 0, strrpos($old_path, '/'));

        $name = $this->formateName($name);

        $new_path = $old_path_part . '/' . $name;

        if ($new_path == $old_path) {
            return true;
        }

        Storage::disk('local')->move($old_path, $new_path);

        $this->upadateSubDirectoriesPath($old_path, $new_path);

        $updates = array('name' => $name, 'path' => $new_path);

        Directory::where('id', $directory->id)->update($updates);

        $this->updateBreadCrumbs($directory->id);
    }

    public function updateBreadCrumbs($id)
    {

        $directory = Directory::where('id', $id)->first();

        $paths = str_replace("Accounts/", "", $directory->path);

        $paths = str_replace("migration/", "", $paths);

        $paths_array = explode('/', $paths);

        foreach ($paths_array as $key => $path) {
            $directories[$key]['id'] = $this->getDirectoryIDByName($path);
            $directories[$key]['name'] = $path;
        }

        $breadcrumbs = json_encode($directories);

        $updates = array('breadcrumbs' => $breadcrumbs);

        Directory::where('id', $directory->id)->update($updates);

        return true;
    }

    public function upadateSubDirectoriesPath($old_path, $new_path)
    {

        DB::statement("update directories set path = REPLACE(path,'" . $old_path . "','" . $new_path . "') where path like '" . $old_path . "%' ");
        return true;
    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateCreateRequest(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateMoveRequest(Request $request)
    {
        $rules = [
            'parent_id' => 'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateRenameRequest(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateShareRequest(Request $request)
    {
        $rules = [
//            'type_id' => 'required',
            'shared_to_id' => 'required',
//            'start_at' => 'required',
            'end_at' => 'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateUpdateShareRequest(Request $request)
    {
        $rules = [
//            'type_id' => 'required',
            'shared_to_id' => 'required',
//            'start_at' => 'required',
            'end_at' => 'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateRemoveShareRequest(Request $request)
    {
        $rules = [

            'shared_to_id' => 'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function checkAllow($directory, $user_id, $share_type_id)
    {

        if (!$directory->is_public && $directory->creator_id != $user_id) {

            foreach ($directory->shares as $key => $value) {
                if ($value['shared_to_id'] != $user_id || $value['type_id'] != $share_type_id) {

                    return false;

                }
            }
        }
        return true;
    }

    public function createMainDirectories($account)
    {

        $user = Auth::user();

        $directoryRequest = new \Illuminate\Http\Request();

        $directoryRequest->merge(['account_id' => $account->id]);

        $directoryRequest->merge(['creator_id' => $account->creator_id]);

        $directoryRequest->merge(['parent_id' => null]);

        $directoryRequest->merge(['name' => $account->slug]);

        $directoryRequest->merge(['path' => 'Accounts/' . $account->slug]);

        $directoryRequest->merge(['is_garbage' => 0]);

        $directoryRequest->merge(['is_public' => 1]);

        $validator = $this->validateCreateRequest($directoryRequest);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $main_directory_id = $this->create($directoryRequest);

        $directoryRequest->merge(['parent_id' => $main_directory_id['id']]);

        $this->createAllSubDirectories($directoryRequest, $account);

    }

    public function createAllSubDirectories($directoryRequest, $account)
    {

        // $sub_folders=array(
        //     'Garbage', 'Medias', 'Boards', 'Associations', 'Committees',
        //     'Agendas', 'Actions', 'Tasks', 'Attachments', 'Reports', 'Collections','Annotations'
        // );

        $subFolders = ['Boards', 'Committees', 'Associations', 'My Shared Files'];
        $this->createOneSubDirectory($directoryRequest, $account, $subFolders);
    }

    public function createOneSubDirectory($directoryRequest, $account, $sub_folders)
    {
        foreach ($sub_folders as $sub_folder) {
            $directoryRequest->merge(['name' => $sub_folder]);
            $name_ar = null;
            if ($sub_folder == "Boards") {$name_ar = "المجالس";}
            if ($sub_folder == "Committees") {$name_ar = "اللجان";}
            if ($sub_folder == "Associations") {$name_ar = "الجمعيات";}
            if ($sub_folder == "My Shared Files") {$name_ar = "الملفات المشاركة";}

            if ($name_ar) {
                $directoryRequest->merge(['name_ar' => $name_ar]);
            }

            $directoryRequest->merge(['path' => 'Accounts/' . $account->slug . '/' . $sub_folder]);

            if ($sub_folder == 'Garbage') {
                $directoryRequest->merge(['is_garbage' => 1]);
            } else {
                $directoryRequest->merge(['is_garbage' => 0]);
            }

            if ($sub_folder == 'My Shared Files') {
                $directoryRequest->merge(['is_shared_directory' => 1]);
            }

            $directory = $this->create($directoryRequest);

            if(in_array($sub_folder, ['Boards', 'Committees', 'Associations'])) {
                $resolutionRequest = new \Illuminate\Http\Request();
                $resolutionRequest->merge(['account_id' => $account->id]);
                $resolutionRequest->merge(['creator_id' => $account->creator_id]);
                $resolutionRequest->merge(['parent_id' => $directory['id']]);
                $resolutionRequest->merge(['name' => 'Resolutions']);
                $resolutionRequest->merge(['name_ar' => 'قرارات بالتمرير']);
                $resolutionRequest->merge(['path' => 'Accounts/' . $account->slug . '/' . $sub_folder . '/Resolutions']);
                $resolutionRequest->merge(['is_garbage' => 0]);
                $resolutionRequest->merge(['is_public' => 1]);
                $this->create($resolutionRequest);
            }
        }

    }

    public function notifyShare($directory, $type_id, $shared_to_id)
    {
        $notificationService = new NotificationService();

        $link = url('/directories-show/' . $directory->id);

        $notificationService = new NotificationService();

        $notificationService->sendNotification(
            $shared_to_id,
            $directory->account_id,
            $directory->name,
            $link,
            NotificationType::DIRECTORY_SHARE_INITATION,
            array()
        );
    }

}
