<?php

namespace App\Services;
use App\Constants\TranslationCode;
use App\Models\MeetingAgenda;
use Illuminate\Support\Facades\Validator;
use App\Services\BaseService;
use App\Services\NotificationService;
use App\Services\DirectoryService;
use App\Services\PDFService;
use App\Services\MediaService;
use App\Services\WebexService;
use App\Models\Language;
use App\Models\Media;
use App\Models\Annotation;
use App\Models\Directory;
use App\Models\MeetingCollection;
use Illuminate\Http\Request;
use App\Models\Meeting;
use App\Models\MeetingTime;
use App\Models\Attendee;
use App\Models\Action;
use App\Models\Recommendation;

use App\Models\Organizer;
use App\Models\MediaUserShare;

use App\Models\Attachment;
use App\Models\AttachmentMedia;
use App\Models\Agenda;
use App\Models\CommitteeTranslation;

use App\Models\CommitteeMember;
use App\Models\User;
use App\Models\Location;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Notice;
use App\Models\Account;
use App\Models\MeetingReport;
use App\Models\GovernanceManager;
use App\Services\RegulationService;


use App\Models\AccountConfiguration;

use App\Models\Committee;

use App\Models\NotificationType;
use App\Models\ActionVotingElement;


use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\Auth;

use App\Services\EmailService;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

use Ixudra\Curl\Facades\Curl;
use IonGhitun\JwtToken\Jwt;




/**
 * Class TaskService
 *
 * @package App\Services
 */
class MeetingService extends BaseService
{
    private $emailService;
    /**
     */
    

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateCreateTimesRequest(Request $request)
    {
        $rules = [
            'title' => 'required',
            'committee_id' => 'required|numeric|exists:committees,id',    
            'location_id' => 'required|numeric|exists:locations,id',

           // 'type_id' => 'numeric|exists:meetings_types,id',            

        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    public function validateUpdateTimesRequest(Request $request)
    {
        $rules = [
            'title' => 'required',
            'committee_id' => 'required|numeric|exists:committees,id',    
          //  'type_id' => 'numeric|exists:meetings_types,id',            

        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    public function validateStartRequest(Request $request)
    {
        $rules = [
            'is_valid_quorum' => 'required|integer|min:0|digits_between: 0,1',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    public function validateattendeeMemberRequest(Request $request)
    {
        $rules = [
            'member_id' => 'required|integer|exists:users,id',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    public function validateICALRequest(Request $request)
    {
        $rules = [
            'start_at' => 'required',
            'end_at' => 'required',
            'summery' => 'required',
            

        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    public function validateCreateRequest(Request $request)
    {
        $rules = [
            
            'title' => 'required',
            'start_at' => 'required|date|date_format:Y/m/d H:i',
            'end_at' => 'required|date|date_format:Y/m/d H:i',
            'committee_id' => 'required|numeric|exists:committees,id',
            'location_id' => 'required|numeric|exists:locations,id',
            'time_voting_end_at'=>'date|date_format:Y/m/d H:i',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    public function validateCreateAssociationRequest(Request $request)
    {
        $rules = [
            
            'title' => 'required',
            'start_at' => 'required|date|date_format:Y/m/d H:i',
            'end_at' => 'required|date|date_format:Y/m/d H:i',
            'committee_id' => 'required|numeric|exists:committees,id',
            'location_id' => 'required|numeric|exists:locations,id',

            'is_association' => 'required|numeric',
            'procedure_id' => 'required|numeric|exists:procedures,id',
            
            'attendees_minimum_shares_count' => 'required|numeric',
            'shares_for_one_vote' => 'required|numeric',
            'time_voting_end_at'=>'date|date_format:Y/m/d H:i',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    
    public function validateAttendeesRequest(Request $request, $key)
    {   

        $rules = [
            'attendees.'.$key.'.member_id'=>'required|numeric|exists:users,id',
            'attendees.'.$key.'.position_id'=>'required|numeric|exists:positions,id',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    
    public function validateOrganizersRequest(Request $request, $key)
    {   

        $rules = [
            'organizers.'.$key.'.member_id'=>'required',
            'organizers.'.$key.'.capabilities'=>'required',
            'organizers.'.$key.'.expires_at'=>'required|date|date_format:Y/m/d H:i',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    
    public function validateTimesRequest(Request $request, $key)
    {

        $rules = [
            'times.'.$key.'.start_at'=>'required|date|date_format:Y/m/d H:i',
            'times.'.$key.'.end_at'=>'required|date|date_format:Y/m/d H:i',
            'time_voting_end_at'=>'required|date|date_format:Y/m/d H:i',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    
    public function validateAttachmentsRequest(Request $request, $key)
    {

        $rules = [
            'attachments.'.$key.'.title'=>'required',
            'attachments.'.$key.'.media_id'=>'required|numeric|exists:medias,id',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateAgendasAttachmentsRequest(Request $request, $agendaKey, $attachmentKey)
    {

        $rules = [
            'agendas.'.$agendaKey.'.attachments.'.$attachmentKey.'.title'=>'required',
            'agendas.'.$agendaKey.'.attachments.'.$attachmentKey.'.media_id'=>'required|numeric|exists:medias,id',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    
    public function validateAgendasRequest(Request $request, $key)
    {

        $rules = [
            'agendas.'.$key.'.title'=>'required',
            'agendas.'.$key.'.assignee_id'=>'required|numeric|exists:users,id',
            'agendas.'.$key.'.duration'=>'required|numeric',
            'agendas.'.$key.'.brief'=>'required',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateAgendasActionsRequest(Request $request, $agendaKey, $actionKey)
    {

        $rules = [

            'agendas.'.$agendaKey.'.actions.'.$actionKey.'.type_id'=>'required|numeric|exists:actions_types,id',
            'agendas.'.$agendaKey.'.actions.'.$actionKey.'.assignee_id'=>'required|numeric|exists:users,id',
            'agendas.'.$agendaKey.'.actions.'.$actionKey.'.due_date' => 'required|date|date_format:Y/m/d',
            'agendas.'.$agendaKey.'.actions.'.$actionKey.'.show_to'=>'required|in:ALL,MEMBERS,ATTENDEES',
            'agendas.'.$agendaKey.'.actions.'.$actionKey.'.title'=>'required',
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
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
    public function validateUpdateRequest(Request $request)
    {
        $rules = [
            
            'title' => 'required',
            'start_at' => 'required|date|date_format:Y/m/d H:i',
            'end_at' => 'required|date|date_format:Y/m/d H:i',
            'committee_id' => 'required|numeric|exists:committees,id',
            'location_id' => 'required|numeric|exists:locations,id',
        ];

        $messages = [
            /*'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID*/
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
    public function validateUpdateAssociationRequest(Request $request)
    {
        $rules = [
            
            'title' => 'required',
            'start_at' => 'required|date|date_format:Y/m/d H:i',
            'end_at' => 'required|date|date_format:Y/m/d H:i',
            'committee_id' => 'required|numeric|exists:committees,id',
            'location_id' => 'required|numeric|exists:locations,id',

            'is_association' => 'required|numeric',
            'procedure_id' => 'required|numeric|exists:procedures,id',
            'attendees_minimum_shares_count' => 'required|numeric',
            'shares_to_vote_percentage' => 'required|numeric',
        ];

        $messages = [
            /*'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID*/
        ];
    
        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateAcceptTermsRequest(Request $request)
    {

        $rules = [
            'meeting_id' => 'required|integer|exists:meetings,id',
            'code' => 'required|integer',
        ];

        $messages = [];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function sendAcceptTermsCode(int $meetingId): bool
    {
        try{
            $acceptTermsCode = $this->generateDigits(4);
            $user = Auth::user();
            Attendee::where([
                'member_id' => $user->id,
                'meeting_id' => $meetingId
            ])->update(['accept_terms_code' => $acceptTermsCode]);
            $this->notifyUserForOTP($user, $acceptTermsCode);

            return true;
        } catch (\Exception $e) {
            Log::info('Can not send accept terms code: ' . $e->getMessage());
        }

        return false;
    }

    public function notifyUserForOTP(User $user, int $acceptTermsCode): void
    {
        $link = '';
        $title = $acceptTermsCode;
        $notificationService = new NotificationService();
        $notificationService->sendNotification(
            $user->id,
            $user->account_id,
            $title,
            $link,
            NotificationType::OTP_INVITE,
            array()
        );
    }

    public function updateCounts($meeting_id){

        $confirmed_count = Attendee::where('meeting_id',$meeting_id)->where('status', Attendee::STATUS_CONFIRMED)->orWhere('status', Attendee::STATUS_IS_ADMIN_ATTENDED)->count();

        $canceled_count = Attendee::where('meeting_id',$meeting_id)->where('status', Attendee::STATUS_CANCELED)->count();

        $invited_count = Attendee::where('meeting_id',$meeting_id)->count();

        Meeting::where('id', $meeting_id)->update(
            [
                'invited_count' => $invited_count, 
                'confirmed_count' => $confirmed_count, 
                'canceled_count' => $canceled_count
            ]
        );
        
    }


    public function notifyMemberToTimeVote($meeting){
        $notificationService = new NotificationService();
        $link = url('/times/'.$meeting->id) ;
        $attendees = Attendee::where('meeting_id',$meeting->id)->get();
        foreach($attendees as $key=>$attendee){

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }

            $notificationService->sendNotification(
                $attendee_id, 
                $meeting->account_id , 
                $meeting->title , 
                $link ,
                NotificationType::VOTE_MEETING_TIMES,
                array(),
            );
        }
    }


    public function InviteAttendeesNotification($meeting, $emailLink=null){

        $notificationService = new NotificationService();
        
        $link = url('/meetings/'.$meeting->id) ;

        $attendees = Attendee::where('meeting_id',$meeting->id)->where('status', Attendee::STATUS_DRAFT)->get();
        
        $creator = User::where('id',$meeting->creator_id)->first();

        $location = Location::where('id',$meeting->location_id)->first();

        $organizer_name=$creator->title.' '.$creator->name;

        $organizer_email=$creator->email;

        $location=$location->name;

        $summery=$meeting->title;

        $description=$meeting->brief;

        $start_at=$meeting->start_at['full'];

        $end_at=$meeting->end_at['full'];


        $request_to_ical = 'organizer_name='.$organizer_name.'&organizer_email='.$organizer_email.'&description='.$description.'&location='.$location.'&start_at='.$start_at.'&end_at='.$end_at.'&summery='.$summery;

        $ical_file = url('/meetings/ical/').'?'.$request_to_ical ;
        
        foreach($attendees as $key=>$attendee){

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }

            $notificationBody = [
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];

            $notificationService->sendNotification(
                $attendee_id, 
                $meeting->account_id , 
                $meeting->title , 
                $link ,
                NotificationType::ATTENDEES_INVITATION,
                array('body' => $notificationBody),
                $emailLink,
                __('Go to Meeting')
            );
            
            Attendee::where('meeting_id',$meeting->id)->where('member_id',$attendee->member_id)->update(array('status'=>Attendee::STATUS_INVITED));

        }
        
    }

    public function updateMeetingNotification($meeting, string $emailLink=null){

        $notificationService = new NotificationService();
        
        $link = url('/meetings/'.$meeting->id) ;

        $attendees = Attendee::where('meeting_id',$meeting->id)->get();
        
        foreach($attendees as $key=>$attendee){

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }

            $notificationBody = [
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];

            $notificationService->sendNotification(
                $attendee_id, 
                $meeting->account_id , 
                $meeting->title , 
                $link ,
                NotificationType::MEETING_UPDATES,
                array('body' => $notificationBody, 'meeting_id' => $meeting->id),
                $emailLink,
                __("Go to Meeting")
            );
        }
    }

    public function sendStartVideoMeetingNotification($meeting){
        
        $notificationService = new NotificationService();
        $attendees = Attendee::where('meeting_id',$meeting->id)->get();
        $link = url('/meetings/'.$meeting->id);

        foreach($attendees as $key=>$attendee){
            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

        $notificationService->sendNotification(
            $attendee->member_id, 
            $meeting->account_id , 
            $meeting->title , 
            $link ,
            NotificationType::MEETING_UPDATES,
            array()
        );
    }
    }

    public function publishMeetingNotification($meeting ,$email_link, $secondMeetingUrl){

        $notificationService = new NotificationService();
        
        $link = url('/meetings/'.$meeting->id);

        $attendees = Attendee::where('meeting_id',$meeting->id)->get();
        
        foreach($attendees as $key=>$attendee){
            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }

            $notificationBody = [
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];


            $notificationService->sendNotification(
                $attendee_id,
                $meeting->account_id ,
                $meeting->title ,
                $link ,
                NotificationType::MEETING_PUBLISH,
                array('link'=>$email_link, 'meeting_id' => $meeting->id, 'body' => $notificationBody, 'second_link' => $secondMeetingUrl, 'second_text' => __('Apologize for attendance')),
                $email_link,
                __('Confirm attendance')
            );
        }
    }

    public function publishAssociationMeetingNotification($meeting ,$email_link, $secondMeetingUrl){

        $notificationService = new NotificationService();
        
        $link = url('/meetings/'.$meeting->id);

        $attendees = Attendee::where('meeting_id',$meeting->id)->get();
        
        foreach($attendees as $key=>$attendee){
            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }

            $notificationBody = [
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];


            $notificationService->sendNotification(
                $attendee_id,
                $meeting->account_id ,
                $meeting->title ,
                $email_link ,
                NotificationType::ASSOCIATION_MEETING_PUBLISH,
                array('link'=>$email_link, 'meeting_id' => $meeting->id, 'body' => $notificationBody, 'second_link' => $secondMeetingUrl, 'second_text' => __('Apologize for attendance'),
                'meeting_date'=>date('Y-m-d', strtotime($meeting->start_at['full'])),
                'meeting_time'=>date('H:i a', strtotime($meeting->start_at['full'])),
            ),
                $email_link,
                __('Confirm attendance')
            );
        }
    }

    public function startMeetingNotifications($meeting ,$email_link){

        $notificationService = new NotificationService();
        
        $link = url('/meetings/during-meeting/'.$meeting->id) ;

        $attendees = Attendee::where('meeting_id',$meeting->id)->get();
        
        foreach($attendees as $key=>$attendee){

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }

            $notificationBody = [
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];

            $notificationService->sendNotification(
                $attendee_id, 
                $meeting->account_id , 
                $meeting->title , 
                $link ,
                NotificationType::START_MEETING_NOTIFICATION,
                array('link'=>$email_link, 'body' => $notificationBody, 'meeting_id' => $meeting->id),
                $email_link,
                __('Join meeting')
            );
        }
    }

    public function finishMeetingNotifications($meeting){

        $notificationService = new NotificationService();

        $link = url('/meetings/during-meeting/'.$meeting->id) ;

        $attendees = Attendee::where('meeting_id',$meeting->id)->get();

        foreach($attendees as $key=>$attendee){

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }

            $notificationService->sendNotification(
                $attendee_id,
                $meeting->account_id ,
                $meeting->title ,
                $link ,
                NotificationType::MEETING_FINISH,
                array('meeting_id' => $meeting->id)
            );
        }
    }

    public function sendNotificationToAbsentAttendee($meeting){

        $notificationService = new NotificationService();

        $lastThreeMeetings = Meeting::where('status', 5)->where('committee_id', $meeting->committee_id)
        ->orderBy('id', 'desc')->take(3)->get();

        $attendees = Attendee::where('meeting_id',$meeting->id)->get();

        foreach($attendees as $key=>$attendee){

            $countOfAttendee = Attendee::whereIn('meeting_id',$lastThreeMeetings->pluck('id')->toArray())
            ->where('member_id', $attendee->member_id)->where('status', 3)
            ->count();

            if($countOfAttendee != 0) {
                continue;
            }

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            $notificationService->sendNotification(
                $attendee->member_id,
                $meeting->account_id,
                optional(optional($meeting->committee)->translation)->title,
                '',
                NotificationType::MEETING_ABSENCE_REGULATION,
                array('meeting_id' => $meeting->id)
            );
        }
    }

    public function publishMeetingCollectionNotification($meeting, $meetingCollection, $meetingUrl=null){

        $notificationService = new NotificationService();
        
        $link = url('/meeting-collections/'.$meeting->id) ;

        $attendees = Attendee::where('meeting_id',$meeting->id)->get();
        
        foreach($attendees as $key=>$attendee){

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }

            $notificationBody = [
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];

            $notificationService->sendNotification(
                $attendee_id, 
                $meeting->account_id , 
                $meeting->title , 
                $link ,
                NotificationType::MEETING_COLLECTION_PUBLISH,
                array('body' => $notificationBody),
                $meetingUrl,
                __('Go to Meeting')
            );
        }
    }

    public function cancelMeetingNotification($meeting){

        $notificationService = new NotificationService();
        
        $link = url('/meetings/'.$meeting->id) ;

        $attendees = Attendee::where('meeting_id',$meeting->id)->get();
        
        foreach($attendees as $key=>$attendee){

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }

            $notificationBody = [
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];

            $notificationService->sendNotification(
                $attendee_id, 
                $meeting->account_id , 
                $meeting->title , 
                $link ,
                NotificationType::MEETING_CANCEL,
                array('body' => $notificationBody, 'meeting_id' => $meeting->id),
            );
        }
    }
    
    public function meetingTimeVotingNotification($meeting, $meetingUrl=null){

        $notificationService = new NotificationService();

        $link = url('/times/'.$meeting->id);

        $attendees = Attendee::where('meeting_id',$meeting->id)->where('status', Attendee::STATUS_DRAFT)->get();
        

        foreach($attendees as $key=>$attendee){

            if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                $attendee_id = $attendee->delegated_to_id;
            }else{
                $attendee_id = $attendee->member_id;
            }
            
            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            $times = MeetingTime::where('meeting_id', $meeting->id)->get();

            $notificationBody = [
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Location") => optional($meeting->location)->translation->name
            ];

            $dir = ($languageId == 1) ? "rtl" : "ltr";
            $textAlign = ($languageId == 1) ? "text-align: right;" : "text-align: left;";

            $imes = '<ul style="list-style: decimal;'.$textAlign.'" dir="'.$dir.'">';

            foreach($times as $time) {
                $imes .= '<li>'.
                __("Start Date").' '.$time->start_at['gregorian']['date'].' - '.$time->start_at['gregorian']['time'].'<br>'.
                __("End Date").' '.$time->end_at['gregorian']['date'].' - '.$time->end_at['gregorian']['time'].
                '</li>';
            }

            $imes .= '</ul>';

            $notificationBody[__("Times")] = $imes;

            $notificationService->sendNotification(
                $attendee_id, 
                $meeting->account_id , 
                $meeting->title , 
                $link ,
                NotificationType::TIME_VOTING_INVITATION,
                array('body'=>$notificationBody, 'meeting_id' => $meeting->id),
                $meetingUrl,
                __("Voting")
            );
        }
    }

    public function delegateAttendeeCanceledNotification($delegated_to_id, $member_id, $meeting){

        $notificationService = new NotificationService();

        $link = url('/meetings/'.$meeting->id);

        $notificationService->sendNotification(
            $member_id, 
            $meeting->account_id , 
            $meeting->title , 
            $link ,
            NotificationType::DELEGATE_INVITATION_CANCELED,
            array('meeting_id' => $meeting->id),
        );
    }

    public function attendeesAttendanceUpdateNotification($meeting){

        $notificationService = new NotificationService();

        $link = url('/meetings/'.$meeting->id);

        $amanuensisId = $meeting->committee->amanuensis_id;
        $amanuensisIsAboard = true;
        foreach($meeting->attendees as $key=>$attendee){

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            if($amanuensisId === $attendee->member_id)
                $amanuensisIsAboard = false;
            $notificationService->sendNotification(
                $attendee->member_id, 
                $meeting->account_id , 
                $meeting->title , 
                $link ,
                NotificationType::NOTIFY_ATTENDEES_WITH_ATTENDENCE_STATUS_UPDATE,
                array(),
            );
        }

        if($amanuensisIsAboard){
            $notificationService->sendNotification(
                $amanuensisId, 
                $meeting->account_id , 
                $meeting->title , 
                $link ,
                NotificationType::NOTIFY_ATTENDEES_WITH_ATTENDENCE_STATUS_UPDATE,
                array(),
            );
        }
    }

    public function startMeetingNotification($to_user_id, $meeting){

        $notificationService = new NotificationService();

        $link = url('/meetings/'.$meeting->id);

        $notificationBody = [
            __("Committee") => optional($meeting->committee)->translation->name,
            __("Meeting Title") => $meeting->title,
            __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
            __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
            __("Location") => optional($meeting->location)->translation->name
        ];

        $notificationService->sendNotification(
            $to_user_id, 
            $meeting->account_id , 
            $meeting->title , 
            $link ,
            NotificationType::START_MEETING_NOTIFICATION,
            array('body' => $notificationBody, 'meeting_id' => $meeting->id)
        );
    }

    public function startMeeting($meeting, $input){

        Meeting::where('id',$meeting->id)->update(['status'=>Meeting::STATUS_STARTED,'is_valid_quorum'=>$input['is_valid_quorum']]);
        Attendee::where('meeting_id',$meeting->id)->where('position_id', 1)->update(['status'=>Attendee::STATUS_IS_ADMIN_ATTENDED]);
    }

    public function attendeeMemberMeeting($meeting, $input){

        if(isset($input['status']))
            Attendee::where('meeting_id',$meeting->id)->where('member_id',$input['member_id'])->update(['status'=>$input['status']]);
        else
            Attendee::where('meeting_id',$meeting->id)->where('member_id',$input['member_id'])->update(['status'=>Attendee::STATUS_IS_ADMIN_ATTENDED]);

        $this->updateCounts($meeting->id);
        
        // $this->startMeetingNotification($input['member_id'], $meeting);
        $emailLink = '';
        if(isset($input['link'])) {
            $emailLink = $input['link'] . '/meetings/show/' . $meeting->id;
        }
        $this->updateMeetingNotification($meeting, $emailLink);
    }

    public function joinMeeting($meeting,$user){

        Attendee::where('meeting_id',$meeting->id)->where('member_id',$user->id)->update(['status'=>Attendee::STATUS_ATTENDED]);

    }

    public function getMeetingAnnotations($meeting_id){
        return Annotation::where('meeting_id',$meeting_id)->get();
    }

    public function getCollectionAnnotation($meeting_id,$media_id){
        return Annotation::where('meeting_id',$meeting_id)->where('media_id',$media_id)->get();
    }

    public function generateCollection($meeting, $meetingUrl=null,$createNewVersion=true){

        if($createNewVersion){

            $pdfService = new PDFService();
            $media_id = $pdfService->getMeetingAsPdf($meeting);
    
            $this->cacheAttachmentsPdftron($meeting);
    //        $media_id = $this->mergeCollection($meeting);
    
            MeetingCollection::create(array('content'=>'','xml'=>'', 'meeting_id'=>$meeting->id, 'media_id'=>$media_id, 'status'=>1));
    
            $meetingCollection = MeetingCollection::where('meeting_id', $meeting->id)->where('status', 1)->with('media:id')->first();
    
            if($meeting->status != 0) {
                $this->publishMeetingCollectionNotification($meeting, $meetingCollection, $meetingUrl);
            }
    
        }else{
            $meetingCollection = MeetingCollection::where('meeting_id', $meeting->id)->where('status', 1)->with('media:id')->first();
            $this->cacheAttachmentsPdftron($meeting);
        }
        return $meetingCollection;
    }

    public function cacheAttachmentsPdftron($meeting){
            
        foreach ($meeting->attachments as $attachment) {
                if($attachment->media) {
                    $fileUrl = url('/medias-view-pdf/'.$attachment->media->id.'?meeting_key='.$meeting->meeting_key);
                    $pdftronServerUrl = 'https://pdf.development-majles.tech';
                    $fullUrl = $pdftronServerUrl.'/blackbox/PreloadURL?url='.$fileUrl;
                    Curl::to($fullUrl)
                    ->withOption('TIMEOUT', 1)
                    ->get();
                }
            }

            foreach ($meeting->agendas as $agenda) {
                foreach ($agenda->attachments as $attachment) {
                    if($attachment->media) {
                        $fileUrl = url('/medias-view-pdf/'.$attachment->media->id.'?meeting_key='.$meeting->meeting_key);
                        $pdftronServerUrl = 'https://pdf.development-majles.tech';
                        $fullUrl = $pdftronServerUrl.'/blackbox/PreloadURL?url='.$fileUrl;
                        Curl::to($fullUrl)
                        ->withOption('TIMEOUT', 1)
                        ->get();
                    }
                }
            }

    }

    public function generateCollectionForMeeting (Meeting $meeting, string $meetingUrl=null): void
    {
        $user = Auth::user();
        $generateCollectionUrl = url('/meetings/generate-collection/'.$meeting->id.'?meetingUrl='.$meetingUrl);
        Curl::to($generateCollectionUrl)
                    ->withHeader('Authorization: Bearer '.Jwt::generateToken(['id' => $user->id]))
                    ->withOption('RETURNTRANSFER', true)
                    ->withOption('TIMEOUT', 1)
                    ->get();
    }

   

    public function generateMeetingCollection($meeting, Request $request){

        $media_id = $this->mergeMeetingAttachments($meeting, $request, 'Collections');
       

        $meetingCollection =  MeetingCollection::where('meeting_id', $meeting->id)->update(array('status'=>0));

        $meetingCollection =  MeetingCollection::create(array('content'=>'','xml'=>'', 'meeting_id'=>$meeting->id, 'media_id'=>$media_id, 'status'=>1));

        $meetingCollection = MeetingCollection::where('meeting_id', $meeting->id)->where('status', 1)->with('media:id')->first();
        
        $this->publishMeetingCollectionNotification($meeting, $meetingCollection);

        return $meetingCollection;
    }

    public function ministryApproved($meeting){
        Meeting::where('id', $meeting->id)->update(array('status'=>Meeting::STATUS_MINISTRY_APPROVED));
        return true;
    }

    public function getMeetingMainDetails($meeting){
        $content = '<h1>'.$meeting->title.'</h1><br/>';
        return $content;
    }

    public function getAgendaMainDetails($agenda){
        $content = '<h1>'.$agenda->title.'</h1><br/>'.$agenda->content.' </br>';
        return $content;
    }

    public function getAttachmentMainDetails($attachment){
        //build full path of file and display in img tag
       $full_path=  'https://beta.development-majles.tech/'.$this->getMediaPath($attachment->media_id);
    
        $content = '<img src='.$full_path.'>';
        return $full_path;
    }

    public function getMeetingMainDetailsPDF($meeting){

        $pdfService = new PDFService();
        $directoryService = new DirectoryService();
        $mediaService = new MediaService();

        $account = Account::where('id', $meeting->account_id)->first();
        $content_url=$this->getRouteURL("/meeting-main-details/").$meeting->id;

        $media_id = $this->pdfFromContent($content_url, $meeting, $account, 'Collections');
        
        return $media_id[0];
    }

    public function pdfFromContent($content_url, $meeting, $account, $type){

        $pdfService = new PDFService();
        $directoryService = new DirectoryService();
        $mediaService = new MediaService();

        $docx_media_id = $pdfService->fileFromContent($content_url, $meeting, $account, $type, 'docx');
        $pdf_path = $pdfService->convertToPDF($this->getMediaPath($docx_media_id));

        $pdf_media_id = $mediaService->CreateExistingFullPath($pdf_path,$this->getMediaDirectory($docx_media_id));

        $return =array($pdf_media_id,$docx_media_id );

        //print_r($return);
        //die();
        return $return;

    }

    public function getAgendasDetailsPDF($meeting){

        $pdfService = new PDFService();
        $directoryService = new DirectoryService();
        $mediaService = new MediaService();

        $account = Account::where('id', $meeting->account_id)->first();

        $agendas = Agenda::where('meeting_id',$meeting->id)->where('collection_included',1)->get();
        
        $media_array = array();
        foreach($agendas as $key => $agenda){
          
        $content_url=$this->getRouteURL("/agenda-main-details/").$agenda->id;

        $media_id = $this->pdfFromContent($content_url, $meeting, $account, 'Collections');
        

        array_push($media_array, $media_id[0]);

        $agendaAttachmentsMedias = $this->getAgendaAttachmentsDetailsPDF($agenda);

        foreach($agendaAttachmentsMedias as $media){
            $media_array[] = $media;
        }
       // array_push($media_array, );
        }
        return $media_array;
        //print_r($media_array);
       // die('in getAgendasDetailsPDF');
       // return $this->mergeMedias($media_array);
    }

    public function getAgendaAttachmentsDetailsPDF($agenda){
        $pdfService = new PDFService();
        $directoryService = new DirectoryService();
        $mediaService = new MediaService();
        $media_array=array();
        $meeting = Meeting::where('id', $agenda->meeting_id)->first();
        $account = Account::where('id', $meeting->account_id)->first();

        $attachments = Attachment::with('media')->where('agenda_id',$agenda->id)->get();
       // TODO:: to be reviewd
        foreach($attachments as $attachment){
          
         if ($attachment->media['type'] == "application/pdf" )
         {
            array_push($media_array, $attachment->media_id);
         }else{
            // $content_url=$this->getRouteURL("/attachment-main-details/").$attachment->id;
            // $media_id = $this->pdfFromContent($content_url, $meeting, $account, 'Collections' );
            // array_push($media_array, $media_id[0]);
            array_push($media_array, $attachment->media_id);
         }

            // foreach($attachment->medias as $media){
            //     array_push($media_array, $media->media_id);
            // }
        }
        return $media_array;
       // return $this->mergeMedias($media_array);
       
    }

    public function createRemoteMeeting($meeting,$user,$token){
        
        $webexService = new WebexService();
        //to add diffrent channel in future
        $remoteMeeting = $webexService->createRemoteMeeting($meeting,$user,$token);
        if(isset($remoteMeeting->sipAddress))
            $meeting = Meeting::where('id', $meeting->id)->update(array('remote_meeting'=>1,'remote_meeting_url'=>$remoteMeeting->sipAddress,
        'remote_meeting_creator_id'=>$user->id,'remote_meeting_id'=>$remoteMeeting->id));
        else
            return false;
        return $meeting;
    }

    public function getMeetingAttachmentsDetailsPDF($meeting){

        $pdfService = new PDFService();
        $directoryService = new DirectoryService();
        $mediaService = new MediaService();

        $media_array=array();

        $account = Account::where('id', $meeting->account_id)->first();
        $attachments = Attachment::with('medias')->where('meeting_id',$meeting->id)->where('agenda_id',NULL)->get();
        // foreach($attachments as $attachment){
        //     $content_url=$this->getRouteURL("/attachment-main-details/").$attachment->id;

        //     $media_id = $this->pdfFromContent($content_url, $meeting, $account, 'Collections');

        //     array_push($media_array, $media_id[0]);
        //     foreach($attachment->medias as $media){
        //         array_push($media_array, $media->media_id);
        //     }
            
        // }

        foreach($attachments as $attachment){
          
            if ($attachment->media['type'] == "application/pdf" )
            {
               array_push($media_array, $attachment->media_id);
            }else{
               array_push($media_array, $attachment->media_id);
            }
           }
        return $media_array;
        //return $this->mergeMedias($media_array);
    }

    public function getMediasPath($medias){

        $medias_id_array = array();
        foreach($medias as $key => $media){
            $medias_id_array[$key] = $media->media_id;
        }
       // return $this->mergeMedias($medias_id_array);
    }

    

    public function getMediaPath($media_id){

            $media = Media::with('directory')->where('id', $media_id)->first();
            if(isset($media->path)){

            
            $file_exists = Storage::disk('local')->exists($media->directory['path']."/".$media->path);

            $encrypted_file_exists = Storage::disk('local')->exists($media->directory['path']."/".$media->path.$media->encrypted_extention);
            
            if($media->encrypted_extention && !$file_exists && $encrypted_file_exists ){
                \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
            }
            return $media->directory['path']."/".$media ->path ;
            }else{
                return false;
            }
    }

    public function getMediaDirectory($media_id){
        $media = Media::where('id', $media_id)->first();
        if($media && isset($media->directory_id))
        return Directory::where('id', $media->directory_id)->first();
        else
        return false;
    }

    public function mergeMedias($media_ids_array){
        $pdfService = new PDFService();
        $directoryService = new DirectoryService();
        $mediaService = new MediaService();

        $path_array = array();

        foreach($media_ids_array as $key => $media_id){
            
            $path_array[$key] = $this->getMediaPath($media_id) ;
            $directory_array[$key] = $this->getMediaDirectory($media_id) ;
        }

        
        return $pdfService->mergePDFs($path_array, $directory_array);
    }

    // taha adel
    public function mergeCollection_test($meeting){
        $media_ids[0] = $this->getMeetingMainDetailsPDF($meeting);
        $media_ids[1] = $this->getMeetingMainDetailsPDF($meeting);
        $media_id=$this->mergeMedias($media_ids);
        return $media_id;
    }
    public function mergeCollection($meeting){

        $media_ids[0] = array($this->getMeetingMainDetailsPDF($meeting));

        $media_ids[1] = $this->getMeetingAttachmentsDetailsPDF($meeting);

        $media_ids[2] = $this->getAgendasDetailsPDF($meeting);

        $media_id=$this->mergeMedias(array_merge($media_ids[0],$media_ids[1],$media_ids[2]));
        //echo $media_id;die();
        return $media_id;
    }

    public function mergeMeetingAttachments($meeting, Request $request, $type){

        $pdfsArray = array();

        $pdfService = new PDFService();

        $account = Account::where('id', $meeting->account_id)->first();

        $content = '<h1>'.$meeting->title.'</h1><br/>';
                
        $agendas = Agenda::where('meeting_id',$meeting->id)->get();
        
        foreach($agendas as $agenda){

            $content = $content.'<h1>'.$agenda->title.'</h1><br/>'.$agenda->content.' </br>';

            $attachments = Attachment::where('agenda_id',$agenda->id)->get();

            foreach($attachments as $attachment){
                $content = $content.'<h1>'.$attachment->title.'</h1><br/>'.$attachment->content.' </br>';
            }
        }

        $attachments = Attachment::where('meeting_id',$meeting->id)->where('agenda_id',NULL)->get();
        foreach($attachments as $attachment){
            $content = $content.'<h1>'.$attachment->title.'</h1><br/>'.$attachment->content.' </br>';
        }
        $media_id = $pdfService->createPDFfromMettingDetails($content, $meeting, $account, $type);

        return $media_id;
    }

   
    public function pushMediaToArray($medias, $array){

        $pdfService = new PDFService();

        foreach($medias as $media){

            $file_exists = Storage::disk('local')->exists($media->directory['path']."/".$media ->path);
            if(!$file_exists){
                \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
            }
            
            if($media ->type !=='application/pdf'){
                $path = $media->directory['path']."/".$media ->path;
            }else{
                $path = $pdfService->convertToPDF($media->directory['path']."/".$media ->path);
            }
            array_push($array, $path);
        }
        return $array;
    }

    public function createReportContentfromMettingDetails($meeting){


        $agendas = Agenda::where('meeting_id',$meeting->id)->get();

        $meetingAttachments = Attachment::where('meeting_id',$meeting->id)->where('agenda_id',NULL)->get();

        $notices = Notice::where('meeting_id',$meeting->id)->get();

        $attendees = Attendee::where('meeting_id',$meeting->id)->where('status', Attendee::STATUS_IS_ADMIN_ATTENDED)->get()->toArray();

        $members = CommitteeMember::with('member.translation','position','membership')->where('committee_id',$meeting->committee_id)->get();

        $actions = Action::where('meeting_id',$meeting->id)->get();

        $html = "";

        $html = $html .'<h1> Meeting Details </h1><h1>'. $meeting->title.'</h1>';

        $html = $html .'<br/>'. $meeting->brief.'<br/>';

        $html = $html .'<br/>'. $meeting->content.'<br/>';

        $html = $html .'<h1> Attendees Details </h1>';

        foreach($members as $member){
            if(isset($member->member->translation->title) && isset($member->member->translation->name)){
                $html = $html.'<h1>'.$member->member->translation->title.'</h1><br/>';
                $html = $html.'<h1>'.$member->member->translation->name.' </h1><br/>';
            }
           
            $html = $html.'<h1>'.$member->position->name.' </h1><br/>';
            $html = $html.'<h1>'.$member->membership->name.' </h1><br/>';
        }

        $html = $html .'<h1> Actions Details </h1>';

        foreach($actions as $action){
            $html = $html.'<h1>'.$action->title.' </h1><br/>';
            $html = $html.$action->content.' <br/>';
        }

        $html = $html .'<h1> Agendas Details </h1>';
        foreach($agendas as $key=>$agenda){
            $html = $html .'<h1>'.$agenda->title.'</h1>';
            $html = $html .'<br/>'.$agenda->content.'<br/>';
            $agendaAttachments = Attachment::where('agenda_id',$agenda->id)->get();

            foreach($agendaAttachments as $attachment){
                $html = $html.'<h1>'.$attachment->title.'</h1><br/>'.$attachment->content.' </br>';
            }

            $recommendations = Recommendation::where('agenda_id',$agenda->id)->get();

            foreach($recommendations as $recommendation){
                $html = $html.'<h1>'.$recommendation->title.'</h1><br/>'.$recommendation->content.' </br>';
            }
        }

        foreach($meetingAttachments as $attachment){
            $html = $html.'<h1>'.$attachment->title.'</h1><br/>'.$attachment->content.' </br>';
        }

        $html = $html .'<h1> Notices</h1> ';

        foreach($notices as $key=>$notice){
            $html = $html .'<br/>'.$notice->content.'<br/>';
        }


        return $html;
    }
    


    public function hasSecrectaryAccess($userId, $meeting){

        $committee = Committee::where('id', $meeting->committee_id)->first();
        
        if( ($userId == $committee->amanuensis_id) || ($userId == $committee->secretary_id) ){
            return true; 
        }
        //???
        return true;
    } 

    public function finishMeeting($meeting, Request $request){

        $pdfService = new PDFService();

        $mediaService = new MediaService();

        Meeting::where('id', $meeting->id)->update(array('status'=>Meeting::STATUS_FINISHED));

        $account = Account::where('id', $meeting->account_id)->first();

        $content = $this->createReportContentfromMettingDetails($meeting);
//
//        $content_url=$this->getRouteURL("/meetings-report-content/").$meeting->id;
//
//        $medias = $this->pdfFromContent($content_url, $meeting, $account, 'Report');
//
//        $media_id = $medias[0] ;
//
//        $docx_media_id = $medias[1];


        $pdfService = new PDFService();
        // $media_id = $pdfService->getMeetingReportAsPdf($meeting);
        $media_id = $pdfService->getMeetingReportAsDocx($meeting);


        MeetingReport::where('meeting_id', $meeting->id)->update(array('status'=>MeetingReport::STATUS_HISTORY));

        if($media_id){
            $committee = $meeting->committee;
            $meetingReport = MeetingReport::create(
                array(
                    'creator_id'=>$meeting->creator_id, 
                    'account_id'=>$meeting->account_id, 
                    'committee_id'=>$meeting->committee_id, 
                    'meeting_id'=>$meeting->id, 'media_id'=>$media_id, 
                    'content'=>$content ,
                    'status'=>MeetingReport::STATUS_DRAFT)
                );

            $user = Auth::user();
                    
            foreach([$committee->amanuensis_id, $committee->secretary_id] as $sharedTo) {       
                if($sharedTo && !empty($sharedTo)) {
                    MediaUserShare::create([
                        'media_id' => $meetingReport->media_id,
                        'creator_id' => $user->id,
                        'shared_to_id' => $sharedTo,
                        'type_id' => 1,
                    ]);
                }
            }
        }
        Attendee::where('meeting_id', $meeting->id)->whereIn('status', [Attendee::STATUS_DRAFT, Attendee::STATUS_CANCELED])->update(array('status'=>Attendee::STATUS_ABSENCE));

        $this->closeStartedVoting($meeting);

        return true;


    }

    public function closeStartedVoting($meeting) {
        try {
            return Action::where('meeting_id', $meeting->id)->where('status', Action::STATUS_START_VOTE)
            ->update(['status' => Action::STATUS_VOTE_CLOSED]);
        } catch(\Exception $e) {
            Log::info('Can not update action: ' . $e->getMessage());
            return false;
        }
    }

    public function saveMeetingRelatedDetails($meeting, Request $request){

        $this->saveCommitteeMembersToAttendees($meeting);

        $this->saveTimes($meeting, $request);

         $return = $this->saveAttendees($meeting, $request);  

        $this->saveOrganizers($meeting, $request);  

        $this->saveAttachments($meeting, $request);  

        $this->saveAgendas($meeting, $request);  

        $this->updateCounts($meeting->id);
        return $return;
    }

    public function saveTimes($meeting, Request $request){
        $input = $request->all();
        if(isset($input['times']) && !empty($input['times'])){
            foreach($input['times'] as $key=>$time){   
                $this->isErrorTimesRequest($request, $key);
                $input['times'][$key]['meeting_id']=$meeting->id;
                MeetingTime::create($input['times'][$key]);
            }
            $meetingUrl = '';
            if(isset($input['link'])){
                $meetingUrl = $input['link'].'/meetings/voting/'.$meeting->id;
            }
            $this->meetingTimeVotingNotification($meeting, $meetingUrl);
        }
        
    }

    public function getMaxTimeVotedForMeeting($meeting){
        return MeetingTime::where('meeting_id', $meeting->id)->orderBy('votes_count', 'desc')->first();
    }

    public function saveAttendees($meeting, Request $request){

        $return=false;
        $input = $request->all();
        
        if(isset($input['attendees']) && !empty($input['attendees'])){
            foreach($input['attendees'] as $key=>$member){
                $this->isErrorAttendeesRequest($request, $key);
                $input['attendees'][$key]['shares']=$this->getSharesFromUser($input['attendees'][$key]['member_id']);
              //  $return = $this->isErrorAssociationAttendeesRegulation($meeting, $input['attendees'][$key]['member_id'], $input['attendees'][$key]['shares']);
                $return = true;
                $input['attendees'][$key]['meeting_id']=$meeting->id;
                $input['attendees'][$key]['committee_id']=$meeting->committee_id;
                
                
                $attendee = Attendee::create($input['attendees'][$key]);
                /*
                if(isset($input['attendees'][$key]['can_acccess_ids_list']) && !empty($input['attendees'][$key]['can_acccess_ids_list'])){
                    $this->saveAttendeeAccess( $attendee->member_id, $meeting->creator_id,$input['attendees'][$key]['can_acccess_ids_list']);
                }
                */
            }
        }

        return $return;
    }

    public function saveOrganizers($meeting, Request $request){
       
        $input = $request->all();
        if(isset($input['organizers']) && !empty($input['organizers'])){
            foreach($input['organizers'] as $key=>$member){
                $this->isErrorOrganizersRequest($request, $key); 
                $input_organizer['meeting_id']=$meeting->id;
                $input_organizer['capabilities']=$input['organizers'][$key]['capabilities'];
                $input_organizer['expires_at']=$input['organizers'][$key]['expires_at'];
                $input_organizer['member_id']=$input['organizers'][$key]['member_id'];
                $organizer = Organizer::create($input_organizer);
                
            }
        }
    }


    public function saveAttachments($meeting, Request $request){

        $directoryService = new DirectoryService();
        $mediaService = new MediaService();

        $input = $request->all();

        $account = Account::where('id', $meeting->account_id)->first();

        if(isset($input['attachments']) && !empty($input['attachments'])){
            foreach($input['attachments'] as $key=>$attachment){
                $this->isErrorAttachmentsRequest($request, $key);
                $input['attachments'][$key]['account_id']=$meeting->account_id;
                $input['attachments'][$key]['committee_id']=$meeting->committee_id;
                $input['attachments'][$key]['meeting_id']=$meeting->id;
                $input['attachments'][$key]['creator_id']=$meeting->creator_id;
                $attachment = Attachment::create($input['attachments'][$key]);
                if($input['attachments'][$key]['media_id']){
                    $committeeTranslation = CommitteeTranslation::where('committee_id', $meeting->committee_id)->where('language_id', 2)->first();
                    $committeeName = preg_replace("/\s+/", "-", $committeeTranslation->name);
                    $meetingFormatName = preg_replace("/\s+/", "-", $meeting->title);
                    
                    $pathName = 'Accounts/'.$account->slug.'/'.$meeting->committee->type.'/'.$committeeName.'/'.$meetingFormatName.'/Attachment';
                  //  AttachmentMedia::create(array('media_id'=>$input['attachments'][$key]['media_id'],'attachment_id'=>$attachment->id));
                    $mediaService->moveDirectoryByPath($input['attachments'][$key]['media_id'], $pathName );
                }
            }
        }
    }


    public function saveAgendas($meeting, Request $request){

        $directoryService = new DirectoryService();
        $mediaService = new MediaService();

        $input = $request->all();

        $account = Account::where('id', $meeting->account_id)->first();
        
        if(isset($input['agendas']) && !empty($input['agendas'])){
            
            foreach($input['agendas'] as $key=>$agenda){
                //add condition to check if there is id field
                $this->isErrorAgendasRequest($request, $key);
                $input['agendas'][$key]['account_id']=$meeting->account_id;
                $input['agendas'][$key]['committee_id']=$meeting->committee_id;
                $input['agendas'][$key]['meeting_id']=$meeting->id;
                $input['agendas'][$key]['creator_id']=$meeting->creator_id;

                $meetingAgendasData = [];
                if(isset($input['agendas'][$key]['id']) && is_int($input['agendas'][$key]['id'])) {
                    $agenda = Agenda::find($input['agendas'][$key]['id']);
                    if($agenda) {
                        $updatedData = [
                            'assignee_id' => $input['agendas'][$key]['assignee_id'],
                            'account_id' => $meeting->account_id,
                            'brief' => $input['agendas'][$key]['brief'],
                            'can_acccess_list' => $input['agendas'][$key]['can_acccess_list'],
                            'collection_included' => $input['agendas'][$key]['collection_included'],
                            'duration' => $input['agendas'][$key]['duration'],
                            'has_visable_voting' => $input['agendas'][$key]['has_visable_voting'],
                            'has_voting' => $input['agendas'][$key]['has_voting'],
                            'is_work_agenda' => $input['agendas'][$key]['is_work_agenda'],
                        ];
                        Agenda::where('id', $input['agendas'][$key]['id'])->update($updatedData);
                        
                        $meetingAgendasData = [
                            'meeting_id' => $meeting->id,
                            'agenda_id' => $agenda->id,
                            'original' => 0
                        ];
                        
                        Attachment::where('agenda_id', $agenda->id)->delete();
                    }
                } else {
                    $agenda = Agenda::create($input['agendas'][$key]);
                    $meetingAgendasData = [
                        'meeting_id' => $meeting->id,
                        'agenda_id' => $agenda->id,
                        'original' => 1
                    ];
                }

                if(count($meetingAgendasData) > 0) {
                    MeetingAgenda::create($meetingAgendasData);
                }

                if(isset($agenda['can_acccess_list']) && !empty($agenda['can_acccess_list'])) {
                    $accessList = explode(',', $agenda['can_acccess_list']);
                    foreach ($accessList as $one) {
                        if($one != ''){
                            $checkAttendee = Attendee::where('member_id', $one)->where('meeting_id', $agenda->meeting_id)->first();
                            $attendeeData = [];
                            if(!$checkAttendee) {
                                $attendeeData['member_id'] = $one;
                                $attendeeData['meeting_id'] = $agenda->meeting_id;
                                $attendeeData['is_committee_member'] = 0;
                                Attendee::create($attendeeData);
                            }
                        }
                    }
                }
                
                if(isset($input['agendas'][$key]['actions']) && !empty($input['agendas'][$key]['actions'])){
                    foreach($input['agendas'][$key]['actions'] as $actionKey=>$actions){
                        if(isset($input['agendas'][$key]['actions'][$actionKey]['id']) && is_int($input['agendas'][$key]['actions'][$actionKey]['id'])) {
                            $action = Action::find($input['agendas'][$key]['actions'][$actionKey]['id']);
                            if($action) {
                                $updatedActionData = [
                                    'assignee_id' => $input['agendas'][$key]['actions'][$actionKey]['assignee_id'],
                                    'title' => $input['agendas'][$key]['actions'][$actionKey]['title'],
                                    'content' => $input['agendas'][$key]['actions'][$actionKey]['content'],
                                    'type_id' => $input['agendas'][$key]['actions'][$actionKey]['type_id'],
                                    'due_date' => $input['agendas'][$key]['actions'][$actionKey]['due_date'],
                                    'show_to' => $input['agendas'][$key]['actions'][$actionKey]['show_to'],
                                    'voting_type' => $input['agendas'][$key]['actions'][$actionKey]['voting_type'],
                                    'account_id' => $agenda->account_id,
                                    'committee_id' => $agenda->committee_id,
                                    'agenda_id' => $agenda->id,
                                    'creator_id' =>$agenda->creator_id,
                                    'meeting_id' => $agenda->meeting_id
                                ];
                                Action::where('id', $input['agendas'][$key]['id'])->update($updatedActionData);
                            
                            }
                        }else{
                            $this->isErrorAgendasActionsRequest($request, $key,$actionKey);

                            $input['agendas'][$key]['actions'][$actionKey]['account_id']=$agenda->account_id;
                            $input['agendas'][$key]['actions'][$actionKey]['committee_id']=$agenda->committee_id;
                            $input['agendas'][$key]['actions'][$actionKey]['agenda_id']=$agenda->id;
                            $input['agendas'][$key]['actions'][$actionKey]['creator_id']=$agenda->creator_id;
                            $input['agendas'][$key]['actions'][$actionKey]['meeting_id']=$meeting->id;

                            $action = Action::create($input['agendas'][$key]['actions'][$actionKey]);

                            if (isset($input['agendas'][$key]['actions'][$actionKey]['voting_elements'])) {
                                foreach($input['agendas'][$key]['actions'][$actionKey]['voting_elements'] as $element) {
                                    $votingElement = [
                                        'action_id' =>  $action->id,
                                        'text' =>  $element['text'],
                                    ];
                                    ActionVotingElement::create($votingElement);
                                }
                            }
                        }
                    }
                }

                if(isset($input['agendas'][$key]['attachments']) && !empty($input['agendas'][$key]['attachments'])){
                    foreach($input['agendas'][$key]['attachments'] as $attachmentKey=>$attachment){
                       // $this->isErrorAgendasAttachmentsRequest($request, $key,$attachmentKey);
                        $input['agendas'][$key]['attachments'][$attachmentKey]['account_id']=$agenda->account_id;
                        $input['agendas'][$key]['attachments'][$attachmentKey]['committee_id']=$agenda->committee_id;
                        $input['agendas'][$key]['attachments'][$attachmentKey]['agenda_id']=$agenda->id;
                        $input['agendas'][$key]['attachments'][$attachmentKey]['creator_id']=$agenda->creator_id;
                        $input['agendas'][$key]['attachments'][$attachmentKey]['media_id'] =$attachment['media_id'];
                        $attachment = Attachment::create($input['agendas'][$key]['attachments'][$attachmentKey]);
                        if($input['agendas'][$key]['attachments'][$attachmentKey]['media_id']){
                            $committeeTranslation = CommitteeTranslation::where('committee_id', $meeting->committee_id)->where('language_id', 2)->first();
                            $committeeName = preg_replace("/\s+/", "-", $committeeTranslation->name);
                            $meetingFormatName = preg_replace("/\s+/", "-", $meeting->title);
                            
                            $pathName = 'Accounts/'.$account->slug.'/'.$meeting->committee->type.'/'.$committeeName.'/'.$meetingFormatName.'/Agenda';
                            //AttachmentMedia::create(array('media_id'=>$input['agendas'][$key]['attachments'][$attachmentKey]['media_id'],'attachment_id'=>$attachment->id));
                            $mediaService->moveDirectoryByPath($input['agendas'][$key]['attachments'][$attachmentKey]['media_id'], $pathName );

                        }
                    }
                }
            }
        }
    }

    public function checkIsSecretaryAboard($meeting){
        $attendeesIds = $meeting->attendees->pluck('member_id')->toArray();

        return !in_array($meeting->committee->amanuensis_id,$attendeesIds);
    }

    public function checkIsGovernance($committee_id,$user){
        $governance = GovernanceManager::where('committee_id',$committee_id)->where('user_id',$user->id)->first();

        return $governance;
    }

    public function saveCommitteeMembersToAttendees($meeting){
        // check if assoc meeting get the member of assoc and the board under
        if($meeting->committee->type == 'Associations') {
            $committeeBoard = Committee::where('parent_id', $meeting->committee_id)->first();
            $committeeMembers = CommitteeMember::whereIn('committee_id', [$meeting->committee_id, $committeeBoard->id])->get();
        } else {
            $committeeMembers = CommitteeMember::where('committee_id', $meeting->committee_id)->get();
        }

        foreach($committeeMembers as $committeeMember){
            $input['shares'] = $committeeMember->shares;
            $input['member_id']=$committeeMember->member_id;

            $input['position_id']=$committeeMember->position_id;
            $input['membership_id']=$committeeMember->membership_id;
            $input['organization_name']=$committeeMember->organization_name;
            
            $input['meeting_id']=$meeting->id;
            $input['is_committee_member']=1;
            $input['committee_id']=$meeting->committee_id;
           
            Attendee::create($input);
        }
    }

    public function getSharesFromUser($user_id){
    
        $user = User::where('id', $user_id)->first();
        
        return $user->shares;
    }

    public function isErrorAgendasAttachmentsRequest($request, $agendaKey, $attachmentKey){
            $this->isError($this->validateAgendasAttachmentsRequest($request, $agendaKey, $attachmentKey));
    }

    public function isErrorAgendasRequest($request, $key){
        $this->isError($this->validateAgendasRequest($request, $key));
    }

    public function isErrorAgendasActionsRequest($request, $agendaKey, $actionKey){
        $this->isError($this->validateAgendasActionsRequest($request, $agendaKey, $actionKey));
    }
    
    public function isErrorAttachmentsRequest($request, $key){
        $this->isError($this->validateAttachmentsRequest($request, $key));
    }

    public function isErrorAttendeesRequest($request, $key){
    
        $this->isError($this->validateAttendeesRequest($request, $key));
    }

    public function isErrorOrganizersRequest($request, $key){
    
        $this->isError($this->validateOrganizersRequest($request, $key));
    }

    public function isErrorTimesRequest($request, $key){
        $this->isError($this->validateTimesRequest($request, $key));
    }
   
    public function search($text, $user){
        return Meeting::where(function($query2) use ($user){
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
        })
        ->where(function($query) use($text){
            $query->where('title','like', '%'.$text.'%')
            ->orWhere('brief','like', '%'.$text.'%')
            ->orWhere('content','like', '%'.$text.'%');
        })
        ->get();
    }


    

    public function isValidStartMeetingRegulations($meeting){
        $regulationService = new RegulationService();

        if($regulationService->isBoardMeeting($meeting)){
            //عدد اجتماعات المجلس في العام
            $regulations[0] = $regulationService->isValidBoardYearlyMeetingCount($meeting);
            //النصاب القانوني للإجتماع
            $regulations[1] = $regulationService->isValidBoardMeetingQuorum($meeting);
        }

        if($regulationService->isCommitteeMeeting($meeting)){
            //عدد اجتماعات المجلس في العام
            $regulations[0] = $regulationService->isValidCommitteeYearlyMeetingCount($meeting);
            //النصاب القانوني للإجتماع
            $regulations[1] = $regulationService->isValidCommitteeMeetingQuorum($meeting);
        }

        if($regulationService->isAssociationMeeting($meeting)){

            //عدد اجتماعات  في العام
            $regulations[0] = $regulationService->isValidAssocciationYearlyMeetingCount($meeting);
            //النصاب القانوني لعقد الجمعية العادية في حال لم يتحقق النصاب في الجمعية الأولى (% number من رأس مال الشركة)  
            
            $regulations[1] = $regulationService->isValidAssocciationMeetingQuorum($meeting);

            $regulations[2] = $regulationService->isValidAssocciationMeetingAttendees($meeting);
        }
        return $regulationService->mergeRegulationsConfigurations($regulations);
    }

    public function isValidInviteMeetingRegulations($meeting){

        $regulationService = new RegulationService();

        if($regulationService->isBoardMeeting($meeting)){
            //يجب ان تتم الدعوة الى الاجتماع بمدة - - قبل موعد الاجتماع
            $regulations[0] = $regulationService->isValidBoardMeetingInvitationTime($meeting);
        }  
        
        if($regulationService->isCommitteeMeeting($meeting)){
            //يجب ان تتم الدعوة الى الاجتماع بمدة - - قبل موعد الاجتماع
            $regulations[0] = $regulationService->isValidCommitteeMeetingInvitationTime($meeting);
        } 
        
        
        if($regulationService->isAssociationMeeting($meeting)){

            //يجب الدعوة للإنعقاد الجمعية العادية قبل (Days)
            //الدعوة للإنعقاد في حال عدم اكتمال النصاب للاجتماع الأول قبل ب (Days)

            $regulations[0] = $regulationService->isValidAssocationMeetingInvitationTime($meeting);
        } 

        

        return $regulationService->mergeRegulationsConfigurations($regulations);
    }

    public function isValidFinishRegulations($meeting){

        $regulationService = new RegulationService();

        $regulations = [];
        if($regulationService->isBoardMeeting($meeting)){
            $regulations[0] = $this->notifyAbsenceNotificationRegulationMembers($meeting);
        }

        return $regulationService->mergeRegulationsConfigurations($regulations);
    }

    public function notifyAbsenceNotificationRegulationMembers($meeting){

        $regulationService = new RegulationService();

        $notificationService = new NotificationService();
        
        if($regulationService->isBoardMeeting($meeting)){
            //اشعار في حال تغيب احد الاعضاء 3 اجتماعات متتالية
            $members_ids_array = $regulationService->isValidBoardAbsenceNotificationMembers($meeting);
        }

        if($regulationService->isCommitteeMeeting($meeting)){
            //اشعار في حال تغيب احد الاعضاء 3 اجتماعات متتالية
            $members_ids_array = $regulationService->isValidCommitteeAbsenceNotificationMembers($meeting);
        }
        
        if(isset($members_ids_array) && !empty($members_ids_array)){
            $attendees = Attendee::where('meeting_id', $meeting->id)->whereIn('member_id', $members_ids_array)->get();

            CommitteeMember::where('committee_id', $meeting->committee_id)->whereIn('member_id', $members_ids_array)->update('connected_absences_count',1);
            
            $link = url('/meetings/'.$meeting->id) ;
        
            foreach($attendees as $key=>$attendee){

                $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
                $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
                Lang::setLocale($languageCode);

                if($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id){
                    $attendee_id = $attendee->delegated_to_id;
                }else{
                    $attendee_id = $attendee->member_id;
                }

                $notificationBody = [
                    __("Committee") => optional($meeting->committee)->translation->name,
                    __("Meeting Title") => $meeting->title,
                    __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                    __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                    __("Location") => optional($meeting->location)->translation->name
                ];

                $notificationService->sendNotification(
                    $attendee_id, 
                    $meeting->account_id , 
                    $meeting->title , 
                    $link ,
                    NotificationType::MEETING_UPDATES,
                    array('body' => $notificationBody, 'meeting_id' => $meeting->id)
                );
            }
        }

        return array('status'=>'success', 'message'=>'');
    }

    public function updateAllVotingTimesCounts($meeting_id,$count){
        $input['total_vote_counts'] = $count;
        MeetingTime::where('meeting_id', $meeting_id)->update($input);
       
    }

    public function getRouteURL($routeURL){

        return url($routeURL)."/";
    }

    public function getMeetingById(int $meetingId): ?Meeting
    {
        return Meeting::find($meetingId);
    }


    public function acceptTerms(int $meetingId, int $acceptTermsCode): bool
    {
        try {
            $user = Auth::user();
            $checkCode = Attendee::where([
                'member_id' => $user->id,
                'meeting_id' => $meetingId,
                'accept_terms_code' => $acceptTermsCode,
            ])->get();

            if ($checkCode) {
                $acceptTerms = Attendee::where([
                    'member_id' => $user->id,
                    'meeting_id' => $meetingId,
                    'accept_terms_code' => $acceptTermsCode,
                ])->update(['accept_terms' => 1]);

                if ($acceptTerms) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::info('Can not update meeting: ' . $e->getMessage());
        }

        return false;
    }
}
