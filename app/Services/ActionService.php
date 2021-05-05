<?php

namespace App\Services;

use App\Constants\TranslationCode;
use App\Models\Language;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Models\Action;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\CommitteeMember;
use App\Models\Committee;
use App\Models\ActionVoting;
use App\Models\User;
use App\Models\ActionVotingElement;
use App\Models\UserTranslation;
use Carbon\Carbon;



use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use App\Services\NotificationService;
use App\Models\Attendee;
use App\Models\NotificationType;
use App\Models\ReopenVote;
use App\Services\RegulationService;
use Illuminate\Support\Facades\Log;

/**
 * Class ActionService
 *
 * @package App\Services
 */
class ActionService extends BaseService
{

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */

    public function validateCreateActionRequest(Request $request)
    {

        $rules = [

            'type_id' => 'required|numeric|exists:actions_types,id',
            'agenda_id' => 'required|numeric|exists:agendas,id',
            'assignee_id' => 'required|numeric|exists:users,id',
            'due_date' => 'required|date|date_format:Y/m/d',
            'show_to' => 'in:ALL,MEMBERS,ATTENDEES',
            'title' => 'required',
            'content' => 'required'
            /*

            'title'=>'required',
            'brief'=>'required',
            
            
            'can_change_after_publish'=>'required|in:true,false',
            'minimum_meeting_requests'=>'required|in:true,false',
            'status'=>'required|in:'.Action::STATUS_NEW.','.Action::STATUS_READY_TO_VOTE,
            */
        ];

        $messages = [
            /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateCreatePrivateActionRequest(Request $request)
    {

        $rules = [
            'committee_id' => 'required|numeric|exists:committees,id',
            'type_id' => 'numeric|exists:actions_types,id',
            'agenda_id' => 'numeric|exists:agendas,id',
            'assignee_id' => 'numeric|exists:users,id',
            'due_date' => 'required|date|date_format:Y/m/d',
            'show_to' => 'in:ALL,MEMBERS,ATTENDEES',
            'title' => 'required',

            /*
            'committee_id'=>'required|numeric|exists:committees,id',
            'title'=>'required',
            'brief'=>'required',
            'content'=>'required',
            'voting_visibility'=>'required|in:ALL,HIDE',
            'status'=>'required|in:'.Action::STATUS_NEW.','.Action::STATUS_READY_TO_VOTE,
            'show_to'=>'required|in:ALL,MEMBERS,ATTENDEES',
            */
        ];

        $messages = [
            /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */

    public function validateDelegateVotingActionRequest(Request $request)
    {

        $rules = [
            'user_id' => 'required|numeric|exists:users,id',
        ];

        $messages = [
            /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */

    public function validateAttachmentActionRequest($key, Request $request)
    {

        $rules = [
            'attachments.' . $key . '.media_id' => 'required|numeric|exists:medias,id',
        ];

        $messages = [
            /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateVotingActionRequest(Request $request)
    {

        $action = Action::find($request->action_id);
        if (in_array($action->voting_type, [Action::VISIBLE, Action::HIDDEN])) {
            $rules = [
                'status' => 'required|integer|min:1|digits_between: 1,5',
            ];
        } else {
            $rules = [
                'status' => 'integer|min:1|digits_between: 1,5',
            ];
        }

        $messages = [
            /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateVotingConfirmationRequest(Request $request)
    {

        $rules = [
            'action_id' => 'required|integer|exists:actions,id',
            'code' => 'required|integer',
        ];

        $messages = [];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Update
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */

    public function validateUpdateActionRequest(Request $request)
    {

        $rules = [
            'title' => 'required',
            'content' => 'required',
            'show_to' => 'in:ALL,MEMBERS,ATTENDEES',
        ];

        $messages = [
            /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Update
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */

    public function validateUpdatePrivateActionRequest(Request $request)
    {

        $rules = [
            'committee_id' => 'required|numeric|exists:committees,id',
            'title' => 'required',
            'brief' => 'required',
            // 'content'=>'required',
            // 'voting_visibility' => 'required|in:ALL,HIDE',
            'show_to' => 'in:ALL,MEMBERS,ATTENDEES',
        ];

        $messages = [
            /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Assignee
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */

    public function validateAssigneeActionRequest(Request $request)
    {

        $rules = [
            'assignee_id' => 'required|numeric|exists:users,id',
        ];

        $messages = [
            /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function vote($user_id, $action, $status)
    {
        $reopen_vote  = ReopenVote::where('user_id', $user_id)->where('action_id', $action->id)->where('status', 1)->count();
        $action_voting  = ActionVoting::where('creator_id', $user_id)->where('action_id', $action->id)->count();
        if ($action_voting == 0 && $reopen_vote == 0) {
            ActionVoting::create(array('action_id' => $action->id, 'creator_id' => $user_id, 'status' => $status, 'confirmed' => 1));
        }
        elseif($reopen_vote > 0)
        {
            $data['status'] = 0 ;
            ReopenVote::where('user_id', $user_id)->where('action_id', $action->id)->update($data);

            if($action_voting == 0)
            ActionVoting::create(array('action_id' => $action->id, 'creator_id' => $user_id, 'status' => $status, 'confirmed' => 1));
            else
            ActionVoting::where('creator_id', $user_id)->where('action_id', $action->id)->update(array('status' => $status));
        }
        else {
            if($action->can_change_vote){
                ActionVoting::where('creator_id', $user_id)->where('action_id', $action->id)->update(array('status' => $status));
            }
        }
        $this->updateCounts($action->id);
        $this->notifyVote($user_id, $action);
        return true;
    }

    public function updateCounts($action_id)
    {

        $action = Action::where('id', $action_id)->first();
        // if is_prvate = 1 => then total votes count will be = committee members
        // check if under meeting
        if ($action->is_private !== 1) {

            $attendees_count = Attendee::where('meeting_id', $action->meeting_id)->where('status', '3')->where('is_committee_member','1')->count();
        } else {

            $attendees_count = CommitteeMember::where('committee_id', $action->committee_id)->count();
        }

        $accept_voted_count = ActionVoting::where('action_id', $action_id)->where('status', ActionVoting::STATUS_ACCEPT)->count();

        $reject_voted_count = ActionVoting::where('action_id', $action_id)->where('status', ActionVoting::STATUS_REJECT)->count();

        $refrain_voted_count = ActionVoting::where('action_id', $action_id)->where('status', ActionVoting::STATUS_REFRAIN)->count();

        $request_meeting_voted_count = ActionVoting::where('action_id', $action_id)->where('status', ActionVoting::STATUS_REQUEST_MEETING)->count();

        if ($request_meeting_voted_count >= $action->minimum_meeting_requests && $action->minimum_meeting_requests !== null) {
            Action::where('id', $action_id)->update(array('status' => Action::STATUS_NEW_MEETING));
        }

        $votingResult[] = [
            'total_voted_count' => $attendees_count,
            'accept_voted_count' => $accept_voted_count,
            'reject_voted_count' => $reject_voted_count,
            'refrain_voted_count' => $refrain_voted_count,
            'request_meeting_voted_count' => $request_meeting_voted_count
        ];

        Action::where('id', $action_id)->update(
            [
                'total_voted_count' => $attendees_count,
                'accept_voted_count' => $accept_voted_count,
                'reject_voted_count' => $reject_voted_count,
                'refrain_voted_count' => $refrain_voted_count,
                'request_meeting_voted_count' => $request_meeting_voted_count,
                'voting_result' => json_encode($votingResult, true)
            ]
        );
    }

    public function cumulativeVoting(User $user, Action $action, array $votingElements): bool
    {
        try{
            if (!$this->checkUserCanVoting($user, $action, $votingElements)) {
                return false;
            }
            $confirmationCode = $this->generateDigits(4);
            ActionVoting::where(['action_id' => $action->id, 'creator_id' => $user->id])->delete();
            foreach($votingElements as $element) {
                ActionVoting::create([
                    'action_id' => $action->id, 
                    'creator_id' => $user->id, 
                    'shares' => (!empty($element['shares'])) ? $element['shares'] : 0,
                    'action_voting_element_id' => $element['id'],
                    'confirmation_code' => $confirmationCode
                ]);
            }
            
            $this->notifyUserForOTP($user, $confirmationCode);
            return true;
        } catch (\Exception $e) {
            Log::info('Can not save voting: ' . $e->getMessage());
            return false;
        }
    }

    public function questionaireVoting(User $user, Action $action, array $votingElements): bool
    {
        try{
            $reopenVote  = ReopenVote::where('user_id', $user->id)->where('action_id', $action->id)->where('status', 1)->count();
            if ($reopenVote > 0) {
                ReopenVote::where('user_id', $user->id)->where('action_id', $action->id)->update(['status' => 0]);
                $action->boss_vote_weight_doubled = 0;
                $action->save();
            }
            ActionVoting::where(['action_id' => $action->id, 'creator_id' => $user->id])->delete();
            foreach($votingElements as $element) {
                ActionVoting::create([
                    'action_id' => $action->id, 
                    'creator_id' => $user->id, 
                    'status' => $element['status'],
                    'action_voting_element_id' => $element['id'],
                    'confirmed' => 1
                ]);
            }
            
            $this->updateQuestionaireVotingResult($action);

            $this->notifyVote($user->id, $action);
        
            return true;
        } catch (\Exception $e) {
            Log::info('Can not save voting: ' . $e->getMessage());
            return false;
        }
    }

    public function checkUserCanVoting(User $user, Action $action, array $votingElements): bool
    {
        $committeeBoard = Committee::where('parent_id', $action->committee_id)->first();
        $member = CommitteeMember::where('member_id', $user->id)
            ->whereIn('committee_id', [$action->committee_id, $committeeBoard->id])->first();

        $votingShares = 0;
        foreach ($votingElements as $element) {
            $votingShares += (!empty($element['shares'])) ? $element['shares'] : 0;
        }

        if ($member->shares == $votingShares) {
            return true;
        }

        return false;
    }

    public function votingConfirmation(int $actionId, int $confirmationCode): bool
    {
        try{
            $user = Auth::user();
            $checkCode = ActionVoting::where([
                'creator_id' => $user->id,
                'action_id' => $actionId,
                'confirmation_code' => $confirmationCode,
                ])->get();

            if ($checkCode) {
                $unsetCode = ActionVoting::where([
                    'creator_id' => $user->id,
                    'action_id' => $actionId,
                    'confirmation_code' => $confirmationCode,
                    ])->update(['confirmation_code' => null, 'confirmed' => 1]);
                
                if ($unsetCode) {
                    $action = Action::find($actionId);
                    $this->updateVotingResult($action);
                    $this->notifyVote($user->id, $action);
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::info('Can not confirm voting: ' . $e->getMessage());
        }

        return false;
    }

    public function resendConfirmationCode(int $actionId): bool
    {
        try{
            $confirmationCode = $this->generateDigits(4);
            $user = Auth::user();
            ActionVoting::where([
                'creator_id' => $user->id,
                'action_id' => $actionId
            ])->update(['confirmation_code' => $confirmationCode]);
            $this->notifyUserForOTP($user, $confirmationCode);

            return true;
        } catch (\Exception $e) {
            Log::info('Can not resend confirm code: ' . $e->getMessage());
        }

        return false;
    }

    public function votingResult(int $actionId)
    {
        try{
            $users = ActionVoting::join('users', 'users.id', '=', 'actions_votings.creator_id')
                ->where('action_id', $actionId)
                ->where('actions_votings.confirmed', 1)
                ->select('users.*')
                ->groupBy('users.id')
                ->get();

            foreach ($users as $user) {
                $translation = UserTranslation::where('user_id', $user->id)->where('language_id', $this->getLangIdFromLocale())->first();
                $votings = ActionVoting::where('creator_id', $user->id)
                    ->where('action_id', $actionId)
                    ->where('confirmed', 1)
                    ->get();
                $userVotings = [];
                foreach ($votings as $vote) {
                    $userVotings[$vote->action_voting_element_id] = $vote;
                }
                $user->votings = $userVotings;
                $user->translation = $translation;
            }

            return $users;
        } catch (\Exception $e) {
            Log::info('Can not confirm voting: ' . $e->getMessage());
        }
    }

    public function generateDigits($length)
    {
        $characters = '123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function notifyUserForOTP(User $user, int $confirmationCode): void
    {
        $link = '';
        $title = $confirmationCode;
        $notificationService = new NotificationService();
        $notificationService->sendNotification(
            $user->id,
            $user->account_id,
            $title,
            $link,
            NotificationType::OTP_INVITE,
            array(),
        );
    }

    public function updateQuestionaireVotingResult(Action $action): void
    {

        $actionElements = ActionVotingElement::where('action_id', $action->id)->get();
        $votingResult = [];
        $attendees = Attendee::where('meeting_id', $action->meeting_id)
        ->where('status', Attendee::STATUS_IS_ADMIN_ATTENDED)->get();
       
        foreach($actionElements as $element) {
            $votesNumber =  ActionVoting::where('action_voting_element_id', $element->id)
            ->where('confirmed', 1)->where('status', ActionVoting::STATUS_ACCEPT)->count();

            $refrainVotesCount =  ActionVoting::where('action_voting_element_id', $element->id)
            ->where('confirmed', 1)->where('status', ActionVoting::STATUS_REFRAIN)->count();
            $votingResult[] = [
                'votes_number' => $votesNumber,
                'refrain_votes_number' => $refrainVotesCount,
                'element' => $element->text,
                'action_voting_element_id' => $element->id
            ];
        }

        Action::where('id', $action->id)->update(['voting_result' => json_encode($votingResult, true)]);
    }


    public function updateVotingResult(Action $action): void
    {

        $actionElements = ActionVotingElement::where('action_id', $action->id)->get();
        $votingResult = [];
        $attendees = Attendee::where('meeting_id', $action->meeting_id)
        ->where('status', Attendee::STATUS_IS_ADMIN_ATTENDED)->get();
        $committeeMembers = CommitteeMember::whereIn('member_id', $attendees->pluck('member_id')->toArray())->get();

        foreach($actionElements as $element) {
            $elementVotings =  ActionVoting::where('action_voting_element_id', $element->id)
            ->where('confirmed', 1)->get();
            $votingResult[] = [
                'shares' => $elementVotings->sum('shares'),
                'element' => $element->text,
                'action_voting_element_id' => $element->id,
                'voting_percentage' => round(($elementVotings->sum('shares') / $committeeMembers->sum('shares')) * 100, 2)
            ];
        }

        Action::where('id', $action->id)->update(['voting_result' => json_encode($votingResult, true)]);
    }

    public function notifyVote($from_id, $action)
    {

        $notificationService = new NotificationService();

        $link = url('/actions/' . $action->id);

        $link . "?meeting_id=" . $action->meeting_id;

        $committee = Committee::find($action->committee_id);
        $amanuensisId = $committee->amanuensis_id;

        $amanuensisIsAboard = true;
        
        if($action->meeting_id) {
            $notifiers = Attendee::where('meeting_id', $action->meeting_id)->get();
        } else {
            $notifiers = CommitteeMember::where('committee_id', $action->committee_id)->get();
        }
        foreach($notifiers as $notifier) {
            if($notifier->member_id == $amanuensisId) {
                $amanuensisIsAboard = false;
            }
            $notificationService->sendNotification(
                $notifier->member_id,
                $action->account_id,
                $action->title,
                $link,
                NotificationType::ACTION_VOTING_NOTIFICATION,
                array('action_id' => $action->id,'agenda_id' => $action->agenda_id, 'meeting_id' => $action->meeting_id),
            );
        }

        if($amanuensisIsAboard)
        $notificationService->sendNotification(
            $amanuensisId,
            $action->account_id,
            $action->title,
            $link,
            NotificationType::ACTION_VOTING_NOTIFICATION,
            array('action_id' => $action->id,'agenda_id' => $action->agenda_id, 'meeting_id' => $action->meeting_id),
        );
    }

    public function notifyDelegate($from_id, $to_id, $action)
    {

        $notificationService = new NotificationService();

        $link = url('/actions/' . $action->id);

        $notificationService->sendNotification(
            $to_id,
            $action->account_id,
            $action->title,
            $link,
            NotificationType::ACTION_DELEGATE_VOTING_NOTIFICATION,
            array(),
        );
    }

    public function notifyDelegateReject($from_id, $to_id, $action)
    {

        $notificationService = new NotificationService();

        $link = url('/actions/' . $action->id);

        $notificationService->sendNotification(
            $to_id,
            $action->account_id,
            $action->title,
            $link,
            NotificationType::ACTION_DELEGATE_VOTING_REJECTED_NOTIFICATION,
            array(),
        );
    }

    public function notifyMembersWithNewAction($action, $emailLink=null)
    {

        $notificationService = new NotificationService();

        $link = url('/actions/' . $action->id);

        if ($action->show_to == 'ALL') {
            $attendees = Attendee::where('meeting_id', $action->meeting_id)->where('status', Attendee::STATUS_ATTENDED)->get();
        }

        if ($action->show_to == 'MEMBERS') {
            $attendees = Attendee::where('meeting_id', $action->meeting_id)->where('is_committee_member', 1)->where('status', Attendee::STATUS_ATTENDED)->get();
        }

        if ($action->show_to == 'ATTENDEES') {
            $attendees = Attendee::where('meeting_id', $action->meeting_id)->where('is_committee_member', 0)->where('status', Attendee::STATUS_ATTENDED)->get();
        }

        foreach ($attendees as $key => $attendee) {

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                $attendee_id = $attendee->delegated_to_id;
            } else {
                $attendee_id = $attendee->member_id;
            }

            $notificationBody = [];

            if($action->meeting) {
                $meeting = $action->meeting;
                $notificationBody = [
                    __("Action") => $action->title,
                    __("Committee") => optional($meeting->committee)->translation->name,
                    __("Meeting Title") => $meeting->title,
                    __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                    __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                    __("Location") => optional($meeting->location)->translation->name
                ];
            }

            $notificationService->sendNotification(
                $attendee_id,
                $action->account_id,
                $action->title,
                $link,
                NotificationType::ATTENDEES_NEW_ACTION,
                array('body' => $notificationBody),
                $emailLink,
                __('Go to Meeting')
            );
        }
    }


    public function notifyMembersWithPrivateAction($action, $actionLink=null)
    {

        $notificationService = new NotificationService();

        $link = url('/actions/' . $action->id);

        $members = CommitteeMember::where('committee_id', $action->committee_id)->get();

        foreach ($members as $key => $member) {

            $languageId = optional($member->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            $notificationService->sendNotification(
                $member->member_id,
                $action->account_id,
                $action->title,
                $link,
                NotificationType::ATTENDEES_NEW_ACTION,
                array(),
                $actionLink,
                __('Go to Action')
            );
        }
    }

    public function notifyAssigneeWithNewAction($action)
    {

        $notificationService = new NotificationService();

        $link = url('/actions/' . $action->id);

        $notificationService->sendNotification(
            $action->assignee_id,
            $action->account_id,
            $action->title,
            $link,
            NotificationType::ASSIGNEE_NEW_ACTION,
            array(),
        );
    }

    public function notifyAssigneeWithUpdateAction($action, $emailLink=null)
    {
        $this->notifyAssigneeWithAction($action, NotificationType::ASSIGNEE_UPDATE_ACTION, $emailLink);
    }

    public function notifyAssigneeWithPublishAction($action, $emailLink=null)
    {
        $this->notifyAssigneeWithAction($action, NotificationType::ASSIGNEE_PUBLISH_ACTION, $emailLink);
    }

    public function notifyAssigneeWithCancelAction($action, $emailLink=null)
    {
        $this->notifyAssigneeWithAction($action, NotificationType::ASSIGNEE_CANCEL_ACTION, $emailLink);
    }

    public function notifyAssigneeWithEndAction($action, $emailLink=null)
    {
        $this->notifyAssigneeWithAction($action, NotificationType::ASSIGNEE_END_ACTION, $emailLink);
    }

    public function notifyAssigneeWithAction($action, $notification_type, $emailLink=null)
    {

        $notificationService = new NotificationService();

        $link = url('/actions/' . $action->id);

        $notificationBody = [];

        if($action->meeting) {
            $meeting = $action->meeting;
            $notificationBody = [
                __("Action") => $action->title,
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];
        }

        $notificationService->sendNotification(
            $action->assignee_id,
            $action->account_id,
            $action->title,
            $link,
            $notification_type,
            array('body' => $notificationBody, 'action_id' => $action->id),
            $emailLink,
            __('Go to Meeting')
        );
    }

    public function notifyMembersWithUpdateAction($action, $emailLink=null)
    {
        $this->notifyMembersWithAction($action, NotificationType::ATTENDEES_UPDATE_ACTION, $emailLink);
    }

    public function notifyMembersWithPublishAction($action, $emailLink=null)
    {
        $this->notifyMembersWithAction($action, NotificationType::ATTENDEES_PUBLISH_ACTION, $emailLink);
    }

    public function notifyMembersWithCancelAction($action, $emailLink=null)
    {
        $this->notifyMembersWithAction($action, NotificationType::ATTENDEES_CANCEL_ACTION, $emailLink);
    }

    public function notifyMembersWithEndAction($action, $emailLink=null)
    {
        $this->notifyMembersWithAction($action, NotificationType::ATTENDEES_END_ACTION, $emailLink);
    }

    public function notifyMembersWithAction($action, $notification_type, $emailLink=null)
    {

        $notificationService = new NotificationService();

        $link = url('/actions/' . $action->id);

        if ($action->show_to == 'ALL') {
            $attendees = Attendee::where('meeting_id', $action->meeting_id)->where('status', Attendee::STATUS_ATTENDED)->get();
        }

        if ($action->show_to == 'MEMBERS') {
            $attendees = Attendee::where('meeting_id', $action->meeting_id)->where('is_committee_member', 1)->where('status', Attendee::STATUS_ATTENDED)->get();
        }

        if ($action->show_to == 'ATTENDEES') {
            $attendees = Attendee::where('meeting_id', $action->meeting_id)->where('is_committee_member', 0)->where('status', Attendee::STATUS_ATTENDED)->get();
        }

        foreach ($attendees as $key => $attendee) {

            $notificationBody = [];

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                $attendee_id = $attendee->delegated_to_id;
            } else {
                $attendee_id = $attendee->member_id;
            }

            if($action->meeting) {
                $meeting = $action->meeting;
                $notificationBody = [
                    __("Action") => $action->title,
                    __("Committee") => optional($meeting->committee)->translation->name,
                    __("Meeting Title") => $meeting->title,
                    __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                    __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                    __("Location") => optional($meeting->location)->translation->name
                ];
            }


            $notificationService->sendNotification(
                $attendee_id,
                $action->account_id,
                $action->title,
                $link,
                $notification_type,
                array('body' => $notificationBody),
                $emailLink,
                __('Go to Meeting')
            );
        }

        $creator = User::find($action->creator_id);
        if($creator) {
            $languageId = $creator->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);    
        }
        $notificationBody = [];
        if($action->meeting) {
            $meeting = $action->meeting;
            $notificationBody = [
                __("Action") => $action->title,
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];
        }

        $notificationService->sendNotification(
            $action->creator_id,
            $action->account_id,
            $action->title,
            $link,
            $notification_type,
            array('body' => $notificationBody),
            $emailLink,
            __('Go to Meeting')
        );
    }

    public function calculateProgress($action)
    {
        $progress = 0;
        $count_all = count($action->tasks);
        $count_in_prgress = 0;
        foreach ($action->tasks as $task) {
            if ($task->status == Task::STATUS_FINISHED) {
                $count_in_prgress  = $count_in_prgress + 1;
            }
        }
        if ($count_all > 0) {
            $progress = ($count_in_prgress / $count_all) * 100;
        }

        return $progress;
    }

    public function calculateExecution($action)
    {
        $today = Carbon::today();

        if ($action->due_date < $today) {
            return -1;
        } else {
            return 1;
        }
    }

    public function getVoting($action, $user_id)
    {
        if ($action->voting_visibility == Action::SHOW_VOTING) {
            return ActionVoting::where('action_id', $action->id)->get();
        } else {
            return ActionVoting::where('creator_id', $user_id)->where('action_id', $action->id)->get();
        }
    }

    public function search($text, $user)
    {
        $meetings = Meeting::where(function($query2) use ($user){
            $query2->Where(function($query) use ($user){
                $query->whereHas('attendees', function ($query3) use ($user){
                    return $query3->where('member_id', '=', $user->id);
                })->where('status', '!=', 0);
            })
            ->orWhere(function($query4) use ($user){
                $query4->whereHas('committee', function ($query5) use ($user){
                    return $query5->where('secretary_id', '=', $user->id)
                        ->orWhere('amanuensis_id', '=', $user->id);
                });
            })
            ->orWhere(function($query) use($user){
                $query->whereHas('reports', function ($query) use($user){
                    return $query->whereHas('shares', function ($query) use($user){
                        return $query->where('shared_to_id', '=', $user->id)
                            ->where('share_status', 2);
                    });
                })->where('status', 5);
            });
        })->select('meetings.id')->get();


        $actions = Action::where('account_id', $user->account_id)
        ->whereIn('meeting_id', $meetings->pluck('id')->toArray())
        ->get();

        $actionsIds= $actions->map(function ($value, $key) use($text) {
            if (strpos($value->title, $text) || strpos($value->content, $text) || strpos($value->brief, $text)){
                return $value->id;
            }
        });

        return  Action::whereIn('id', $actionsIds)->get();
    }

    public function isValidCloseActionRegulations($action)
    {
        $regulationService = new RegulationService();
        $meeting = Meeting::where('id', $action->meeting_id)->first();
        $regulations = null;
        if ($meeting) {
            if ($regulationService->isBoardMeeting($meeting)) {
                //التصويت على القرارات
                // $regulations[0] = $regulationService->isBoardActionApprovedBasedOnRegulation($meeting, $action);

                // ترجيح كفة الرئيس في حالة تعادل الأصوات 
                // $regulations[1] = $regulationService->isBoardActionBossApprovedBasedOnRegulation($meeting, $action);
            }

            if ($regulationService->isCommitteeMeeting($meeting)) {
                //التصويت على القرارات
                // $regulations[0] = $regulationService->isCommitteeActionApprovedBasedOnRegulation($meeting, $action);

                // ترجيح كفة الرئيس في حالة تعادل الأصوات 
                // $regulations[1] = $regulationService->isCommitteeActionBossApprovedBasedOnRegulation($meeting, $action);
            }
        } else {

            $regulations[0] = $regulationService->isCommitteePrivateActionValidQuorum($action);
            // ترجيح كفة الرئيس في حالة تعادل الأصوات 
            //$regulations[1] = $regulationService->isCommitteePrivateActionBossApprovedBasedOnRegulation($action);
        }


        return $regulationService->mergeRegulationsConfigurations($regulations);
    }

    public function isBossWeightingRegulations($action){
        $regulationService = new RegulationService();
        return $regulationService->isBossWeightingRegulations($action);
    }


    public function notifyMemberToActionVote($action, $list = '', $emailLink=null, $type=null)
    {

        $notificationService = new NotificationService();

        if (isset($action->meeting_id) && !empty($action->meeting_id)) {
            $link = url('/meetings/' . $action->meeting_id);
            $attendees = Attendee::where('meeting_id', $action->meeting_id)->get();
        } elseif (isset($action->committee_id) && !empty($action->committee_id)) {
            $link = url('/committees/' . $action->committee_id);
            $attendees = CommitteeMember::where('committee_id', $action->committee_id)->get();
        }

        $notificationBody = [];

        if ($list != '') {
            $attendees = explode(',', $list);
            foreach ($attendees as $key => $attendee) {
                $userId = $attendee;
                $attendee = CommitteeMember::where('committee_id', $action->committee_id)->where('member_id', $attendee)->first();
                $committee = Committee::find($action->committee_id);

                if(!$attendee && $userId==$committee->amanuensis_id){
                    $attendee_id = $action->meeting->committee->amanuensis_id;
                    $languageId = $this->getLangIdFromLocale();
                }
                // elseif ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                //     $attendee_id = $attendee->delegated_to_id;
                //     $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                // } 
                else {
                    $attendee_id = $attendee->member_id;
                    $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                }
                
                $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
                Lang::setLocale($languageCode);

                if($action->meeting) {
                    $meeting = $action->meeting;
                    $notificationBody = [
                        __("Action") => $action->title,
                        __("Committee") => optional($meeting->committee)->translation->name,
                        __("Meeting Title") => $meeting->title,
                        __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                        __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                        __("Location") => optional($meeting->location)->translation->name
                    ];
                }

                $notificationService->sendNotification(
                    $attendee_id,
                    $action->account_id,
                    $action->title,
                    $link,
                    NotificationType::VOTE_MEETING_ACTION,
                    array('type' => $type, 'action_id' => $action->id, 'agenda_id' => $action->agenda_id, 'meeting_id' => $action->meeting_id, 'body' => $notificationBody),
                    $emailLink,
                    __('Go to Meeting')

                );
            }
        } else {
            foreach ($attendees->where('status', 3) as $key => $attendee) {

                $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
                Lang::setLocale($languageCode);

                if($action->meeting) {
                    $meeting = $action->meeting;
                    $notificationBody = [
                        __("Action") => $action->title,
                        __("Committee") => optional($meeting->committee)->translation->name,
                        __("Meeting Title") => $meeting->title,
                        __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                        __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                        __("Location") => optional($meeting->location)->translation->name
                    ];
                }

                if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                    $attendee_id = $attendee->delegated_to_id;
                } else {
                    $attendee_id = $attendee->member_id;
                }

                $notificationService->sendNotification(
                    $attendee_id,
                    $action->account_id,
                    $action->title,
                    $link,
                    NotificationType::VOTE_MEETING_ACTION,
                    array('type' => $type,'action_id' => $action->id, 'agenda_id' => $action->agenda_id, 'meeting_id' => $action->meeting_id, 'body' => $notificationBody),
                    $emailLink,
                    __('Go to Meeting')
                );
            }
        }
    }

    public function notifyMemberToAssociationActionVote($meeting, $emailLink=null)
    {

        $notificationService = new NotificationService();

        $attendees = Attendee::where('meeting_id', $meeting->id)->get();

        $amanuensisId = optional($meeting->committee)->amanuensis_id;

        $link = url('/meetings/association/during-meeting/' . $meeting->id);

        $notificationBody = [];
        
        foreach ($attendees->where('status', 3) as $key => $attendee) {
            if($attendee->member_id != $amanuensisId) {
                $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
                Lang::setLocale($languageCode);

                $notificationBody = [
                    __("Meeting") => $meeting->title,
                    __("Committee") => optional($meeting->committee)->translation->name,
                    __("Meeting Title") => $meeting->title,
                    __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                    __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                    __("Location") => optional($meeting->location)->translation->name
                ];

                if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                    $attendee_id = $attendee->delegated_to_id;
                } else {
                    $attendee_id = $attendee->member_id;
                }

                $notificationService->sendNotification(
                    $attendee_id,
                    $meeting->account_id,
                    $meeting->title,
                    $link,
                    NotificationType::VOTE_MEETING_ACTION,
                    array('meeting_id' => $meeting->id, 'body' => $notificationBody),
                    $emailLink,
                    __('Go to Meeting')
                );
            }
        }

        if($amanuensisId) {
            $user = User::find($amanuensisId);
            $languageId = $user->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            $notificationBody = [
                __("Meeting") => $meeting->title,
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];
            
            $notificationService->sendNotification(
                $amanuensisId,
                $meeting->account_id,
                $meeting->title,
                $link,
                NotificationType::VOTE_MEETING_ACTION,
                array('meeting_id' => $meeting->id, 'body' => $notificationBody),
                $emailLink,
                __('Go to Meeting')
            );
        }
    }

    public function notifyMemberToActionVoteForDecision($action, $list = '', $emailLink=null)
    {

        $notificationService = new NotificationService();

        $link = url('/committees/' . $action->committee_id);
        $members = CommitteeMember::where('committee_id', $action->committee_id)->get();

        if ($list != '') {
            $members = explode(',', $list);
            foreach ($members as $key => $member) {
                $notificationService->sendNotification(
                    $member,
                    $action->account_id,
                    $action->title,
                    $link,
                    NotificationType::VOTE_MEETING_ACTION,
                    array('action_id' => $action->id)
                );
            }
        } else {
            foreach ($members as $key => $member) {
                $notificationService->sendNotification(
                    $member->member_id,
                    $action->account_id,
                    $action->title,
                    $link,
                    NotificationType::VOTE_MEETING_ACTION,
                    array('action_id' => $action->id)
                );
            }
        }
    }

    public function notifyMemberToCloseActionVoteForDecision($action, $list = '', $emailLink=null)
    {

        $notificationService = new NotificationService();
        $link = url('/committees/' . $action->committee_id);
        $members = CommitteeMember::where('committee_id', $action->committee_id)->get();

        if ($list != '') {
            $members = explode(',', $list);
            foreach ($members as $key => $member) {
                $notificationService->sendNotification(
                    $member,
                    $action->account_id,
                    $action->title,
                    $link,
                    NotificationType::CLOSE_VOTING,
                    array('action_id' => $action->id)
                );
            }
        } else {
            foreach ($members as $key => $member) {
                $notificationService->sendNotification(
                    $member->member_id,
                    $action->account_id,
                    $action->title,
                    $link,
                    NotificationType::CLOSE_VOTING,
                    array('action_id' => $action->id)
                );
            }
        }
    }

    public function notifyMemberToCloseActionVote($action, $list = '', $emailLink=null)
    {

        $notificationService = new NotificationService();

        if (isset($action->meeting_id) && !empty($action->meeting_id)) {
            $link = url('/meetings/' . $action->meeting_id);
            $attendees = Attendee::where('meeting_id', $action->meeting_id)->get();
        } elseif (isset($action->committee_id) && !empty($action->committee_id)) {
            $link = url('/committees/' . $action->committee_id);
            $attendees = CommitteeMember::where('committee_id', $action->committee_id)->get();
        }

        $notificationBody = [];

        if ($list != '') {
            $attendees = explode(',', $list);
            foreach ($attendees as $key => $attendee) {
                $userId = $attendee;
                $attendee = CommitteeMember::where('committee_id', $action->committee_id)->where('member_id', $attendee)->first();
                $committee = Committee::find($action->committee_id);

                if(!$attendee && $userId==$committee->amanuensis_id){
                    $attendee_id = $action->meeting->committee->amanuensis_id;
                    $languageId = $this->getLangIdFromLocale();
                }
                // elseif ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                //     $attendee_id = $attendee->delegated_to_id;
                //     $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                // } 
                else {
                    $attendee_id = $attendee->member_id;
                    $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                }

                $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
                Lang::setLocale($languageCode);

                if($action->meeting) {
                    $meeting = $action->meeting;
                    $notificationBody = [
                        __("Action") => $action->title,
                        __("Committee") => optional($meeting->committee)->translation->name,
                        __("Meeting Title") => $meeting->title,
                        __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                        __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                        __("Location") => optional($meeting->location)->translation->name
                    ];
                }

                $notificationService->sendNotification(
                    $attendee_id,
                    $action->account_id,
                    $action->title,
                    $link,
                    NotificationType::CLOSE_VOTING,
                    array('action_id' => $action->id,'agenda_id' => $action->agenda_id, 'meeting_id' => $action->meeting_id, 'body' => $notificationBody),
                    $emailLink,
                    __('Go to Meeting')
                );
            }
        } else {
            foreach ($attendees->where('status', 3) as $key => $attendee) {

                $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
                Lang::setLocale($languageCode);

                if($action->meeting) {
                    $meeting = $action->meeting;
                    $notificationBody = [
                        __("Action") => $action->title,
                        __("Committee") => optional($meeting->committee)->translation->name,
                        __("Meeting Title") => $meeting->title,
                        __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                        __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                        __("Location") => optional($meeting->location)->translation->name
                    ];
                }

                if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                    $attendee_id = $attendee->delegated_to_id;
                } else {
                    $attendee_id = $attendee->member_id;
                }

                $notificationService->sendNotification(
                    $attendee_id,
                    $action->account_id,
                    $action->title,
                    $link,
                    NotificationType::CLOSE_VOTING,
                    array('action_id' => $action->id,'agenda_id' => $action->agenda_id, 'meeting_id' => $action->meeting_id, 'body' => $notificationBody),
                    $emailLink,
                    __('Go to Meeting')
                );
            }
        }
    }

    public function notifyMemberToCloseAssociationActionVote($meeting, $emailLink=null)
    {

        $notificationService = new NotificationService();

        $link = url('/meetings/association/during-meeting/' . $meeting->id);
        
        $attendees = Attendee::where('meeting_id', $meeting->id)->get();

        $amanuensisId = optional($meeting->committee)->amanuensis_id;

        $notificationBody = [];

        foreach ($attendees->where('status', 3) as $key => $attendee) {

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            $notificationBody = [
                __("Meeting") => $meeting->title,
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];

            if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                $attendee_id = $attendee->delegated_to_id;
            } else {
                $attendee_id = $attendee->member_id;
            }

            $notificationService->sendNotification(
                $attendee_id,
                $meeting->account_id,
                $meeting->title,
                $link,
                NotificationType::CLOSE_VOTING,
                array('meeting_id' => $meeting->id, 'body' => $notificationBody),
                $emailLink,
                __('Go to Meeting')
            );
        }

        if($amanuensisId) {
            $user = User::find($amanuensisId);
            $languageId = $user->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            $notificationBody = [
                __("Meeting") => $meeting->title,
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];

            $notificationService->sendNotification(
                $amanuensisId,
                $meeting->account_id,
                $meeting->title,
                $link,
                NotificationType::CLOSE_VOTING,
                array('meeting_id' => $meeting->id, 'body' => $notificationBody),
                $emailLink,
                __('Go to Meeting')
            );
        }
    }

    public function notifyMemberToFinishActionVote($action, $list = '', $emailLink=null)
    {

        $notificationService = new NotificationService();

        if (isset($action->meeting_id) && !empty($action->meeting_id)) {
            $link = url('/meetings/' . $action->meeting_id);
            $attendees = Attendee::where('meeting_id', $action->meeting_id)->get();
        } elseif (isset($action->committee_id) && !empty($action->committee_id)) {
            $link = url('/committees/' . $action->committee_id);
            $attendees = CommitteeMember::where('committee_id', $action->committee_id)->get();
        }

        $notificationBody = [];

        if ($list != '') {
            $attendees = explode(',', $list);
            foreach ($attendees as $key => $attendee) {
                $attendee = Attendee::where('member_id', $attendee)->first();
                if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                    $attendee_id = $attendee->delegated_to_id;
                } else {
                    $attendee_id = $attendee->member_id;
                }
                $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
                Lang::setLocale($languageCode);

                if($action->meeting) {
                    $meeting = $action->meeting;
                    $notificationBody = [
                        __("Action") => $action->title,
                        __("Committee") => optional($meeting->committee)->translation->name,
                        __("Meeting Title") => $meeting->title,
                        __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                        __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                        __("Location") => optional($meeting->location)->translation->name
                    ];
                }

                $notificationService->sendNotification(
                    $attendee_id,
                    $action->account_id,
                    $meeting->title,
                    $link,
                    NotificationType::CLOSE_VOTING,
                    array('action_id' => $action->id,'agenda_id' => $action->agenda_id, 'meeting_id' => $action->meeting_id, 'body' => $notificationBody),
                    $emailLink,
                    __('Go to Meeting')
                );
            }
        } else {
            foreach ($attendees->where('status', 3) as $key => $attendee) {

                $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
                Lang::setLocale($languageCode);

                if($action->meeting) {
                    $meeting = $action->meeting;
                    $notificationBody = [
                        __("Action") => $action->title,
                        __("Committee") => optional($meeting->committee)->translation->name,
                        __("Meeting Title") => $meeting->title,
                        __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                        __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                        __("Location") => optional($meeting->location)->translation->name
                    ];
                }

                if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                    $attendee_id = $attendee->delegated_to_id;
                } else {
                    $attendee_id = $attendee->member_id;
                }

                $notificationService->sendNotification(
                    $attendee_id,
                    $action->account_id,
                    $meeting->title,
                    $link,
                    NotificationType::CLOSE_VOTING,
                    array('action_id' => $action->id,'agenda_id' => $action->agenda_id, 'meeting_id' => $action->meeting_id, 'body' => $notificationBody),
                    $emailLink,
                    __('Go to Meeting')
                );
            }
        }
    }

    public function weightingBossVoting(Action $action): bool
    {
        try {
            $boss = CommitteeMember::where('committee_id', $action->committee_id)->where('position_id', 1)->first();
            $bossVoting = ActionVoting::where(['action_id' => $action->id, 'creator_id' => $boss->member_id])->first();
            if (in_array($action->voting_type, [Action::VISIBLE, Action::HIDDEN])) {
                if ($bossVoting->status == ActionVoting::STATUS_ACCEPT) {
                    $action->accept_voted_count += 1;
                } elseif ($bossVoting->status == ActionVoting::STATUS_REJECT) {
                    $action->reject_voted_count += 1;
                } elseif ($bossVoting->status == ActionVoting::STATUS_REQUEST_MEETING) {
                    $action->request_meeting_voted_count += 1;
                } elseif ($bossVoting->status == ActionVoting::STATUS_REFRAIN) {
                    $action->refrain_voted_count += 1;
                }
                $action->total_voted_count += 1;
            } elseif (in_array($action->voting_type, [Action::QUESTIONAIRE, Action::CUMULATIVE, Action::HIDDEN_QUESTIONAIRE])) {

                $elementVotedByBoss = ActionVoting::where(['action_id' => $action->id, 'creator_id' => $boss->member_id])
                                            ->where('status', ActionVoting::STATUS_ACCEPT)->first();
                $actionElements = ActionVotingElement::where('action_id', $action->id)->get();
                $votingResult = [];
                $attendees = Attendee::where('meeting_id', $action->meeting_id)
                ->where('status', Attendee::STATUS_IS_ADMIN_ATTENDED)->get();
                
                foreach($actionElements as $element) {
                    $votesNumber =  ActionVoting::where('action_voting_element_id', $element->id)
                    ->where('confirmed', 1)->where('status', ActionVoting::STATUS_ACCEPT)->count();

                    if($element->id == $elementVotedByBoss->action_voting_element_id) {
                        $votesNumber += 1;
                    }
        
                    $refrainVotesCount =  ActionVoting::where('action_voting_element_id', $element->id)
                    ->where('confirmed', 1)->where('status', ActionVoting::STATUS_REFRAIN)->count();
                    $votingResult[] = [
                        'votes_number' => $votesNumber,
                        'refrain_votes_number' => $refrainVotesCount,
                        'element' => $element->text,
                        'action_voting_element_id' => $element->id
                    ];
                }

                $action->voting_result = json_encode($votingResult, true);
            }
            $action->boss_vote_weight_doubled = 1;
            $action->save();

            $this->notifyMemberToActionVote($action);

            if($action->committee_id) {
                $committee = Committee::find($action->committee_id);
                $notifier = $committee->amanuensis_id ? $committee->amanuensis_id : '';
                $this->notifyMemberToActionVote($action, $notifier);
            }

            return true;
        } catch (\Exception $e) {
            dd($e);
            Log::info('Some thing went wrong: ' . $e->getMessage());
            return false;
        }
    }
}
