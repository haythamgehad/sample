<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Language;
use App\Models\Directory;
use App\Constants\TranslationCode;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Setting;
use App\Models\User;
use App\Models\Membership;
use App\Models\CommitteeMember;
use App\Models\Committee;
use App\Models\Meeting;
use App\Repositories\AccountRepository;
use App\Services\DirectoryService;
use App\Services\MediaService;
use App\Repositories\AccountTranslationRepository;
use App\Services\AccountService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Response;
use DateTime;

/**
 * Class AccountController
 * @package App\Http\Controllers
 */

class AccountController extends Controller
{
    private $accountRepository;

    private $accountTranslationRepository;

    private $accountService;

    private $directoryService;

    private $mediaService;



    public function __construct(
        AccountRepository $accountRepo,
        AccountTranslationRepository $accountTranslationRepo
    ) {
        $this->accountRepository = $accountRepo;

        $this->accountTranslationRepository = $accountTranslationRepo;

        $this->accountService = new AccountService();

        $this->directoryService = new DirectoryService();

        $this->mediaService = new MediaService();
    }

    /**
     * Show Account Settings
     * GET /accounts-settings/{id}
     * @param int $id
     * @return Response
     */
    public function settings($id)
    {

        $user = Auth::user();


        $account = $this->accountRepository->with('settings')->find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        if ($account->status == Account::STATUS_BLOCKED) {
            return $this->sendError('Account BLOCKED ');
        }

        $hasAccess = $this->accountService->hasReadAccess($user->id, $account->creator_id, Permission::ACCOUNT_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        return $this->sendResponse($account, 'Account Settings Retrieved successfully');
    }

    /**
     * Show Account Details
     * GET /accounts/{id}
     * @param int $id
     * @return Response
     */
    public function show($id)
    {

        $user = Auth::user();

        $columns = $this->accountRepository->getFields('item');

        $relations = $this->accountRepository->getRelations('item');

        $account = $this->accountRepository->with($relations)->find($id, $columns);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        if ($account->status == Account::STATUS_BLOCKED) {
            return $this->sendError('Account BLOCKED ');
        }

        $hasAccess = $this->accountService->hasReadAccess($user->id, $account->creator_id, Permission::ACCOUNT_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        return $this->sendResponse($account, 'Account retrieved successfully');
    }

    /**
     * Show Account Details by Details
     * GET /accounts/{slug}
     * @param int $slug
     * @return Response
     */
    public function getBySlug($slug)
    {

        $columns = $this->accountRepository->getFields('item');

        $relations = $this->accountRepository->getRelations('item');

        $account = $this->accountRepository->with($relations)->all(array('slug' => $slug), null, null, $columns)->first();
        if (empty($account)) {
            return $this->sendError('Account not found');
        }
        $setting_theme = Setting::where('account_id', '=', $account->id)->where('type', 'theme')->get();
        $setting_notification = Setting::where('account_id', '=', $account->id)->where('type', 'notification')->get();

      

        if ($account->status == Account::STATUS_BLOCKED) {
            return $this->sendError('Account BLOCKED');
        }

        $account["theme"] = $setting_theme;
        $account["notification"] = $setting_notification;


        return $this->sendResponse($account, 'Account retrieved successfully');
    }

    /**
     * Edit Account.
     * PUT/PATCH /users/{id}
     * @param int $id
     * @return Response
     * @urlParam id required The ID of the post.
     * @bodyParam name_en string required The English Name of the account. Example: Account name ar 1
     * @bodyParam name_ar string required The Arabic Name of the account. Example: Account name ar 1
     * @bodyParam type_id int required The type_id of the account. Example: 1
     * @bodyParam has_associations int required The has_associations  of the Account. Example: 0,1
     * @bodyParam associations_configurations[0][regulation_configuration_id] int  The  regulation_configuration_id  . Example:1 
     * @bodyParam associations_configurations[0][status] int  The  regulation_configuration_id  . Example:1 
     * @bodyParam associations_configurations[0][value1] int  The  regulation_configuration_id  . Example:1 
     * @bodyParam associations_configurations[0][value2] int  The  regulation_configuration_id  . Example:1 
     */
    public function update(Request $request, $id)
    {

        $user = Auth::user();

        $account = $this->accountRepository->find($id);

        if ($account->status == Account::STATUS_BLOCKED) {
            return $this->sendError('Account BLOCKED ');
        }

        $hasAccess = $this->accountService->hasUpdateAccess($user->id, $account->creator_id, Permission::ACCOUNT_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $validator = $this->accountService->validateUpdateRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        if ($request->has('slug')) {
            $directory = Directory::where('path', 'Accounts/' . $account->slug)->first();

            $this->directoryService->rename($directory, $input['slug']);
        }

        $account = $this->accountRepository->update($input, $id);

        if ($request->has('logo_id')) {
            $this->mediaService->moveLogoToDirectoryByPath($input['logo_id'], 'Accounts/' . $account->slug . '/Medias', $account->id);
            $input['logo_url'] = url() . "/medias-logo/" . $input['logo_id'];
        }

        $this->accountService->deleteOneToManyRelations('accounts_translations', 'account_id', $id);

        $input['account_id'] = $id;

        $input['language_id'] = Language::ID_AR;

        $input['name'] = $input['name_' . Language::CODE_AR];

        $accountTranslation = $this->accountTranslationRepository->create($input);

        $input['language_id'] = Language::ID_EN;

        $input['name'] = $input['name_' . Language::CODE_EN];

        $accountTranslation = $this->accountTranslationRepository->create($input);

        $relations = $this->accountRepository->getRelations('item');

        $columns = $this->accountRepository->getFields('item');

        if ($request->has('has_associations') && $input['has_associations'] == 1) {
            $this->accountService->saveAssociationsConfiguration($account, $request);
        }

        $account = $this->accountRepository->with($relations)->find($account->id, $columns);


        return $this->sendResponse($account, 'Account updated successfully');
    }

    /**
     * Delete Account
     * Delete /accounts/{id}
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {

        $user = Auth::user();

        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        $hasAccess = $this->accountService->hasReadAccess($user->id, $account->creator_id, Permission::ACCOUNT_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $account->delete();

        return $this->sendResponse('Account deleted successfully');
    }


    public function account_statistics()
    {
        $user = Auth::user();
        $statistics = [];

        $committees_ids =  Committee::where(['account_id' => $user->account_id])->pluck('id');

        $users_ids =  User::where(['account_id' => $user->account_id])->pluck('id');

        $boards_ids =  Committee::where(['account_id' => $user->account_id, 'type' => 'Boards'])->pluck('id');
        $committee_ids =  Committee::where(['account_id' => $user->account_id, 'type' => 'Committees'])->pluck('id');

        $statistics['committee_members'] =
            User::where('account_id', $user->account_id)->whereHas('committees', function ($q) {
                $q->where(['status' => 1]);
            })->count();

        $memberShips = Membership::where('account_type_id', $user->account->type_id)->where('language_id', 2)->get();

        foreach($memberShips as $i => $memberShip) {
            $arabic = Membership::where('id', $memberShip->translation_id)->where('language_id', 1)->first();
            $memberShipsIds = [$memberShip->id, $memberShip->translation_id];
            $statistics['members'][$i]['count'] = CommitteeMember::whereIn('membership_id', $memberShipsIds)->whereIn('member_id', $users_ids)->whereIn('committee_id', $committees_ids)->distinct('member_id')->count();
            $statistics['members'][$i]['ar_name'] = $arabic->name;
            $statistics['members'][$i]['en_name'] = $memberShip->name;
        }

        // $statistics['executive_members'] =  CommitteeMember::where('membership_id', 1)->whereIn('member_id', $users_ids)->whereIn('committee_id', $committees_ids)->groupBy('member_id')->count();
        // $statistics['non_executive_members'] =  CommitteeMember::where('membership_id', 2)->whereIn('member_id', $users_ids)->groupBy('member_id')->count();
        // $statistics['independent_members'] =  CommitteeMember::where('membership_id', 3)->whereIn('member_id', $users_ids)->groupBy('member_id')->count();
        $statistics['committees_count'] =  Committee::where(['account_id' => $user->account_id, 'type' => "Committees", 'is_completed' => 1])->count();
        $statistics['boards_count'] =  Committee::where(['account_id' => $user->account_id, 'type' => "Boards", 'is_completed' => 1])->count();
        $statistics['committee_meetings'] = Meeting::whereIn('committee_id', $committee_ids)->count();
        $statistics['board_meetings'] = Meeting::whereIn('committee_id', $boards_ids)->count();
        return $this->sendResponse($statistics, 'Account statistics retrived successfully');
    }

    public function generalSettings(Request $request, $id)
    {

        $account = $this->accountRepository->find($id);

        if ($account->status == Account::STATUS_BLOCKED) {
            return $this->sendError('Account BLOCKED ');
        }

        $validator = $this->accountService->validateGeneralSettingsRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $settings = $request->all();
        $this->accountRepository->update(['general_settings' => $settings], $id);

        $relations = $this->accountRepository->getRelations('item');
        $columns = $this->accountRepository->getFields('item');
        $account = $this->accountRepository->with($relations)->find($id, $columns);

        return $this->sendResponse($account, 'Settings has been saved successfully');
    }
}
