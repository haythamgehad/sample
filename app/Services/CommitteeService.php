<?php

namespace App\Services;
use App\Constants\TranslationCode;
use Illuminate\Support\Facades\Validator;
use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Models\Committee;
use App\Models\Position;
use App\Models\CommitteeShareholder;
use App\Models\CommitteeAuthority;
use App\Models\CommitteeMember;
use App\Models\GovernanceManager;
use App\Models\CommitteeMemberLog;
use App\Models\Attendee;
use App\Models\AccountConfiguration;
use App\Models\NotificationType;
use App\Models\CommitteeTranslation;
use App\Models\Language;
use App\Services\RegulationService;
use App\Services\NoitificationService;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

use Carbon\Carbon;

use App\Repositories\CommitteeAuthorityRepository;

/**
 * Class TaskService
 *
 * @package App\Services
 */
class CommitteeService extends BaseService
{

    private $committeeAuthorityRepository;

    private $meetingAuthorityRepository;

    private $actionAuthorityRepository;

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateTreeCreateRequest(Request $request)
    {
        $rules = [
            'name' => 'required',
            'type'=> 'required|in:board,committee,association',
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
    public function validateBoardCreateRequest(Request $request)
    {
        $rules = [
            'name_ar' => 'required',
            'name_en' => 'required',
            'is_permanent'=>'numeric',
            'start_at'=>'date|required_without:is_permanent',
            // 'end_at'=>'date|required_without:is_permanent',
            'amanuensis_id'=>'nullable|exists:users,id',
            'secretary_id'=>'nullable|exists:users,id',
            'managing_director_id'=>'nullable|exists:users,id',
            'location_id' => 'numeric|exists:locations,id',
            'media_id'=>'numeric|exists:medias,id',


            
        /*
            'quorum'=>'required|numeric',
            'independents_percentage'=>'required|numeric',
            'attendees_members_count'=>'required|numeric',
            'executive_members_count'=>'required|numeric',
            'non_executive_members_count'=>'required|numeric',
            'allow_delegation'=>'required|numeric',
            'number_of_reminders'=>'required|numeric',
            'minimum_days_before_meeting_invitation'=>'required|numeric',
            */
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateBoardUpdateRequest(Request $request)
    {
        $rules = [
            'name_ar' => 'required',
            'name_en' => 'required',
            'is_permanent'=>'numeric',
            'start_at'=>'date|required_without:is_permanent',
            // 'end_at'=>'date|required_without:is_permanent',
            'amanuensis_id'=>'nullable|exists:users,id',
            'secretary_id'=>'nullable|exists:users,id',
            'managing_director_id'=>'nullable|exists:users,id',
            'media_id'=>'numeric|exists:medias,id',
            
        /*
            'quorum'=>'required|numeric',
            'independents_percentage'=>'required|numeric',
            'attendees_members_count'=>'required|numeric',
            'executive_members_count'=>'required|numeric',
            'non_executive_members_count'=>'required|numeric',
            'allow_delegation'=>'required|numeric',
            'number_of_reminders'=>'required|numeric',
            'minimum_days_before_meeting_invitation'=>'required|numeric',
            */
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
    public function validateCommitteeCreateRequest(Request $request)
    {
        $rules = [
            'name_ar' => 'required',
            'name_en' => 'required',
            // 'parent_id'=>'required|numeric|exists:committees,id',
            'is_permanent'=>'numeric',
            'start_at'=>'date|required_without:is_permanent',
            // 'end_at'=>'date|required_without:is_permanent',
            'amanuensis_id'=>'nullable|exists:users,id',
            'secretary_id'=>'nullable|exists:users,id',
            'managing_director_id'=>'nullable|exists:users,id',
            'media_id'=>'numeric|exists:medias,id',
            
        /*
            'quorum'=>'required|numeric',
            'independents_percentage'=>'required|numeric',
            'attendees_members_count'=>'required|numeric',
            'executive_members_count'=>'required|numeric',
            'non_executive_members_count'=>'required|numeric',
            'allow_delegation'=>'required|numeric',
            'number_of_reminders'=>'required|numeric',
            'minimum_days_before_meeting_invitation'=>'required|numeric',
            */
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
    public function validateAssocationCreateRequest(Request $request)
    {
        $rules = [
            'name_ar' => 'required',
            'name_en' => 'required',
            'start_at'=>'date|required_without:is_permanent',
            // 'end_at'=>'date|required_without:is_permanent',
            'amanuensis_id'=>'nullable|exists:users,id',
            'secretary_id'=>'nullable|exists:users,id',
            'shares'=>'required|numeric',
            'capital'=>'required|numeric',
            'board_id'=>'required|exists:committees,id',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateCommitteeUpdateRequest(Request $request)
    {
        $rules = [
            'name_ar' => 'required',
            'name_en' => 'required',
            // 'parent_id'=>'required|numeric|exists:committees,id',
            'is_permanent'=>'numeric',
            'start_at'=>'date|required_without:is_permanent',
            // 'end_at'=>'date|required_without:is_permanent',
            'amanuensis_id'=>'nullable|exists:users,id',
            'secretary_id'=>'nullable|exists:users,id',
            'managing_director_id'=>'nullable|exists:users,id',
            'media_id'=>'numeric|exists:medias,id',
            
        /*
            'quorum'=>'required|numeric',
            'independents_percentage'=>'required|numeric',
            'attendees_members_count'=>'required|numeric',
            'executive_members_count'=>'required|numeric',
            'non_executive_members_count'=>'required|numeric',
            'allow_delegation'=>'required|numeric',
            'number_of_reminders'=>'required|numeric',
            'minimum_days_before_meeting_invitation'=>'required|numeric',
            */
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }


    public function validateAssocationUpdateRequest(Request $request)
    {
        $rules = [
            'name_ar' => 'required',
            'name_en' => 'required',
            'start_at'=>'date|required_without:is_permanent',
            // 'end_at'=>'date|required_without:is_permanent',
            'amanuensis_id'=>'nullable|exists:users,id',
            'secretary_id'=>'nullable|exists:users,id',
            'shares'=>'required|numeric',
            'capital'=>'required|numeric',
            'board_id'=>'required|exists:committees,id',
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
    public function validateCreateAssociationRequest(Request $request)
    {

        

        $rules = [
            'name_ar' => 'required',
            'name_en' => 'required',
            'is_permanent'=>'numeric',
            'start_at'=>'date|required_without:is_permanent',
            // 'end_at'=>'date|required_without:is_permanent',

            'commercial_register' => 'required',
            'capital' => 'required',
            'subscribed_capital' => 'required',
            'location_id' => 'required|numeric|exists:locations,id',
            'accountant_id' => 'nullable|exists:users,id',
            'secretary_id'=>'nullable|exists:users,id',
            'managing_director_id'=>'nullable|exists:users,id',
            'media_id'=>'numeric|exists:medias,id',
            
        /*
            'quorum'=>'required|numeric',
            'independents_percentage'=>'required|numeric',
            'attendees_members_count'=>'required|numeric',
            'executive_members_count'=>'required|numeric',
            'non_executive_members_count'=>'required|numeric',
            'allow_delegation'=>'required|numeric',
            'number_of_reminders'=>'required|numeric',
            'minimum_days_before_meeting_invitation'=>'required|numeric',
            */
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
    
    public function validatePositionsOccurrence(Request $request)
    {
        $user = Auth::user();
       $array_occurrence = array();
        foreach($request->members as $key=>$member){
            if(isset($array_occurrence[$member['position_id']])){
                $array_occurrence[$member['position_id']]=$array_occurrence[$member['position_id']]+1;
            }else{
                $array_occurrence[$member['position_id']]=1;
            }
            
        }

        foreach($array_occurrence as $key=>$value){
            $return=array();
            if($value >1){

                $position =  Possition::where('id', $key)->first();
                
                if(!$position->can_be_many){
                    $return['errors'][$key]='position #ID '.$position->id.' and name '.$position->name.' can not be more';
                }
            }
        }
        return  $return;
    }

   
    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    
    public function validateMembersRequest(Request $request, $key)
    {

        $rules = [
            'members.'.$key.'.member_id'=>'required|numeric|exists:users,id',
            'members.'.$key.'.position_id'=>'required|numeric|exists:positions,id',
            'members.'.$key.'.membership_id'=>'required|numeric|exists:memberships,id',
        ];

        $messages = [
         /*   
	'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED
	*/
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function createBoardNotification(Committee $committee){
        $notificationService = new NotificationService();
        $members = CommitteeMember::where('committee_id', $committee->id)->get();
        $link = '';
        foreach($members as $key=>$member){
            $notificationService->sendNotification(
                $member->member_id, 
                $committee->account_id , 
                $committee->translation->name , 
                $link ,
                NotificationType::BOARD_CREATE,
                array(),
            );
        }

    }

    public function updateBoardNotification(Committee $committee, array $oldMembers=[], string $emailLink=null){

        $notificationService = new NotificationService();
        $members = CommitteeMember::where('committee_id', $committee->id)->whereNotIn('member_id', $oldMembers)->get();
        $link = '';
        //dd($members);
        foreach($members as $key=>$member){
            $languageId = optional($member->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            $notificationService->sendNotification(
                $member->member_id, 
                $committee->account_id , 
                $committee->translation->name , 
                $link ,
                NotificationType::BOARD_CREATE,
                array(),
                $emailLink,
                __("Show Committee")
            );
        }

    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    
    public function validateShareholdersRequest(Request $request, $key)
    {

        $rules = [
            'shareholders.'.$key.'.member_id'=>'required|numeric|exists:users,id',
            'shareholders.'.$key.'.shares_count'=>'required|numeric',
            'shareholders.'.$key.'.shares_percentage'=>'required|numeric',
            'shareholders.'.$key.'.shares_value'=>'required|numeric',
        ];

        $messages = [
         /*   
	'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED
	*/
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
    
    public function validateConfigurationsRequest(Request $request, $key)
    {

        $rules = [
            'configurations.'.$key.'.regulation_configuration_id'=>'required|numeric|exists:regulations_configurations,id',
            'configurations.'.$key.'.value1'=>'numeric',
            'configurations.'.$key.'.value2'=>'numeric',
        ];

        $messages = [
         /*   
         'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED
        */
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
            'name_ar' => 'required',
            'name_en' => 'required',
            'amanuensis_id'=>'nullable|exists:users,id',
            'secretary_id'=>'nullable|exists:users,id',
            'managing_director_id'=>'nullable|exists:users,id',
            'is_permanent'=>'numeric',
            'start_at'=>'date|required_without:is_permanent',
            // 'end_at'=>'date|required_without:is_permanent',
        /*
            'quorum'=>'required|numeric',

            'independents_percentage'=>'required|numeric',
            'attendees_members_count'=>'required|numeric',
            'executive_members_count'=>'required|numeric',
            'non_executive_members_count'=>'required|numeric',

            'allow_delegation'=>'required|numeric',
            'number_of_reminders'=>'required|numeric',
            'minimum_days_before_meeting_invitation'=>'required|numeric',
        */
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
    public function validateAuthoritiesRequest(Request $request, $key)
    {
        $rules = [
            'authorities.'.$key.'.member_ids'=>'required',
            /*
            'authorities.'.$key.'.position_ids'=>'required',
            'authorities.'.$key.'.committee_ids'=>'required',
            'authorities.'.$key.'.meeting_ids'=>'required',
            'authorities.'.$key.'.action_ids'=>'required',*/
        ];

        $messages = [
        ];
    
        return Validator::make($request->all(), $rules, $messages);
    }





    public function saveCommitteeRelatedDetails($committee, Request $request){

        $this->saveConfigurations($committee, $request);

        $this->saveMembers($committee, $request);

        $this->saveShareholders($committee, $request);

        $this->saveAuthorities($committee, $request);

        $this->saveCommitteeGovernances($committee,$request);
    }

    public function saveAuthorities($committee, Request $request)
    {
        $input = $request->all();
        if(isset($input['authorities']))
            CommitteeAuthority::where('committee_id',$committee->id)->delete();
        if(isset($input['authorities']) && !empty($input['authorities'])){
            foreach($input['authorities'] as $key=>$authority){   
                $this->isErrorAuthoritiesRequest($request, $key);
                $input['authorities'][$key]['committee_id']=$committee->id;
                CommitteeAuthority::create($input['authorities'][$key]);
            }
        }
    }

    public function saveConfigurations($committee, Request $request){

        $input = $request->all();
        if(isset($input['configurations']))
            AccountConfiguration::where('committee_id',$committee->id )->delete();
        if(isset($input['configurations']) && !empty($input['configurations'])){
            foreach($input['configurations'] as $key=>$configuration){
                $this->isErrorConfigurationsRequest($request, $key);
                $input['configurations'][$key]['committee_id']=$committee->id;
                $input['configurations'][$key]['association_code']=$committee->association_code;
                $input['configurations'][$key]['account_id']=$committee->account_id;
                $input['configurations'][$key]['creator_id']=$committee->creator_id;
                AccountConfiguration::create($input['configurations'][$key]);
            }
        }
    }
    
    public function finishMember($committee_id, $member_id , $comment=null){

        CommitteeMember::where('committee_id', $committee_id)->where('member_id', $member_id)->update(array('status'=>CommitteeMember::STATUS_FINISH));

        CommitteeMemberLog::create(array('status'=>CommitteeMember::STATUS_FINISH, 'committee_id'=>$committee_id,'member_id'=>$member_id,'comment'=>$comment));

        Attendee::where('committee_id', $committee_id)->where('member_id', $member_id)->update(array('status'=>CommitteeMember::STATUS_FINISH));

        return true ;
    }
    public function saveMembers($committee, Request $request){
        
        $input = $request->all();
        if(isset($input['members']) && !empty($input['members'])){
            CommitteeMember::where('committee_id', $committee->id)->delete();
            foreach($input['members'] as $key=>$member){   
                $this->isErrorMembersRequest($request, $key);
                $input['members'][$key]['committee_id']=$committee->id;
                
                CommitteeMember::create($input['members'][$key]);
                CommitteeMemberLog::create(array( 'committee_id'=>$committee->id,'member_id'=>$input['members'][$key]['member_id']));
            }
        }
    }

    public function saveCommitteeGovernances($committee, Request $request){
        
        $input = $request->all();
        if(isset($input['governances']) ){
            GovernanceManager::where('committee_id', $committee->id)->delete();
            foreach($input['governances'] as $key=>$member){   
                GovernanceManager::create(['committee_id'=>$committee->id,'user_id'=>$member['id']]);
            }
        }
    }

    public function saveShareholders($committee, Request $request){

        $input = $request->all();
        if(isset($input['shareholders']) && !empty($input['shareholders'])){
            CommitteeShareholder::where('committee_id', $committee->id)->delete();
            foreach($input['shareholders'] as $key=>$shareholder){   
                $this->isErrorShareholdersRequest($request, $key);
                $input['shareholders'][$key]['committee_id']=$committee->id;
                CommitteeShareholder::create($input['shareholders'][$key]);
            }
        }
    }


    public function isErrorAuthoritiesRequest($request, $key){
        $this->isError($this->validateAuthoritiesRequest($request, $key));
    }

    public function isErrorConfigurationsRequest($request, $key){
        $this->isError($this->validateConfigurationsRequest($request, $key));
    }

    public function isErrorMembersRequest($request, $key){
        $this->isError($this->validateMembersRequest($request, $key));
    }

    public function isErrorShareholdersRequest($request, $key){
        $this->isError($this->validateShareholdersRequest($request, $key));
    }

    public function search($text, $user){
        $committeeTranslationIDs = CommitteeTranslation::with('translation')->where('name','like', '%'.$text.'%')->pluck('id')->toArray();
        return Committee::where('account_id',$user->account_id)->whereIN('id', $committeeTranslationIDs)->get();
    }

    public function validateBoardUpdateRegulations(Request $request, $id){

        
        $regulationService = new RegulationService();

        //عدد اعضاء مجلس الإدارة لا يتعدى قيمة معينة
       $regulations[0] = $regulationService->isValidBoardMembersCount($id, $this->calculateMembersFromRequest($id, $request));

        //نسبة المستقلين من اجمالى نسبة الأعضاء
        $regulations[1] = $regulationService->isValidBoardIndependentsCount($id, $this->calculateIndependentsFromRequest($id, $request));
        
        //نسبة الأعضاء غير التنفيذيين
        $regulations[2] = $regulationService->isValidBoardInExecutivesCount($id, $this->calculateInExecutivesFromRequest($id, $request));
        
        //مدة انعقاد المجلس
        $regulations[3] = $regulationService->isValidBoardDuration($id, $this->calculateDurationFromRequest($id, $request));
        
        return $regulationService->mergeRegulationsConfigurations($regulations);
    }

    public function validateCommitteeUpdateRegulations(Request $request, $id){
        $regulationService = new RegulationService();

        //عدد اعضاء اللجنة لا يتعدى قيمة معينة
        $regulations[0] = $regulationService->isValidCommitteeMembersCount($id, $this->calculateMembersFromRequest($id, $request));

        //نسبة الأعضاء غير التنفيذيين
        $regulations[1] = $regulationService->isValidCommitteeIndependentsCount($id,$this->calculateIndependentsFromRequest($id, $request));

        //نسبة تشكيل اللجنة من غير التنفيذيين
        $regulations[2] = $regulationService->isValidCommitteeInExecutivesCount($id, $this->calculateInExecutivesFromRequest($id, $request));
        

        //امكانية ان يكون اعضاء المجلس اعضاء في اللجنة
        $regulations[3] = $regulationService->isValidMembersExistInBoardMebers($id, $this->calculateMembersFromRequest($id, $request));

        //امكانية ان يكون رئيس اللمجلس رئيس للجنة
        $regulations[4] = $regulationService->isValidBossIsBoardBoss($id, $this->getBossFromRequest($id, $request));
        
        return $regulationService->mergeRegulationsConfigurations($regulations);
    }

    public function validateAssociationUpdateRegulations(Request $request, $id){
        
        $regulations = array();
        $regulationService = new RegulationService();
        return $regulationService->mergeRegulationsConfigurations($regulations);
    }




    public function calculateMembersFromRequest($id, Request $request){
        
        //return 50;

        $members = CommitteeMember::where('committee_id', $id)->get();

        $members_exists_array=array();

        $members_request_array=array();
        
        if(isset($members) && !empty($members)){
            foreach($members as $key=>$member){
                $members_exists_array[$key]=$member->member_id;
            }
        }
        
        if(isset($request['members']) && !empty($request['members'])){
            foreach($request['members'] as $key=>$member){
                $members_request_array[$key]=$member['member_id'];
            }
        }

        $members_array = array_unique(array_merge($members_exists_array, $members_request_array));

        return count($members_array);

    }

    public function calculateIndependentsFromRequest($id, Request $request){

        $input = $request->all();

        $members = CommitteeMember::where('committee_id', $id)->get();

        $members_exists_array=array();

        $members_request_array=array();

        $independent_members_exists_array=array();

        $independent_members_request_array=array();
        
        if(isset($members) && !empty($members)){
            foreach($members as $key=>$member){
                $members_exists_array[$key]=$member->member_id;
                if($member->membership_id == CommitteeMember::Independent_Membership){
                    $independent_members_exists_array[$key]=$member->member_id;
                }
            }
        }

        if(isset($input['members']) && !empty($input['members'])){
            foreach($input['members'] as $key=>$member){
                $members_request_array[$key]=$member['member_id'];
                if($member['membership_id'] == CommitteeMember::Independent_Membership){
                    $independent_members_request_array[$key]=$member['member_id'];
                }
            }
        }

        

        $members_array = array_unique(array_merge($members_exists_array, $members_request_array));

        $independent_members_array = array_unique(array_merge($independent_members_exists_array, $independent_members_request_array));
        if(!empty($independent_members_array) && !empty($members_array)){
            return (count($independent_members_array)/count($members_array))*100;
        }else{
            return 0;
        }
        
    }




    public function calculateInExecutivesFromRequest($id, Request $request){
        $input = $request->all();
       
       $members = CommitteeMember::where('committee_id', $id)->get();

       $members_exists_array=array();

       $members_request_array=array();

       $executive_members_exists_array=array();

       $executive_members_request_array=array();
        
        if(isset($members) && !empty($members)){
            foreach($members as $key=>$member){
                $members_exists_array[$key]=$member->member_id;
                if($member->membership_id == CommitteeMember::InExecutive_Membership){
                    $executive_members_exists_array[$key]=$member->member_id;
                }
            }
        }

        if(isset($input['members']) && !empty($input['members'])){
            foreach($input['members'] as $key=>$member){
                $members_request_array[$key]=$member['member_id'];
                if($member['membership_id'] == CommitteeMember::InExecutive_Membership){
                    $executive_members_request_array[$key]=$member['member_id'];
                }
            }
        }

        $members_array = array_unique(array_merge($members_exists_array, $members_request_array));
        $executive_members_array = array_unique(array_merge($executive_members_exists_array, $executive_members_request_array));

        if(!empty($executive_members_array) && !empty($members_array)){
            return (count($executive_members_array)/count($members_array))*100;
        }else{
            return 0;
        }
        
    }


    public function calculateDurationFromRequest($id, Request $request){
        $input = $request->all();
        $diff = 0;
        $committee = Committee::where('id', $id);

        if($request->has('start_at') && $request->has('end_at')){

           $diff = Carbon::parse( $request->get('start_at') )->diffInYears( $request->get('end_at') );
           
        }elseif(isset($committee->start_at) && sset($committee->end_at)){
            $diff = Carbon::parse( $committee->start_at )->diffInYears( $committee->end_at );
        }

        return $diff;
    }


    public function getBossFromRequest($id, Request $request){

        $input = $request->all();

        $boss_id = 0;

        $members = CommitteeMember::where('committee_id', $id)->where('position_id', CommitteeMember::BOSS_POSITION_ID)->first();

        if(isset($members->member_id) && $members->member_id){
            $boss_id = $members->member_id;
        }
        if(isset($input['members']) && !empty($input['members'])){
            foreach($input['members'] as $key=>$member){
                if($member['position_id'] == CommitteeMember::BOSS_POSITION_ID){
                    $boss_id = $member['member_id'];
                }
            }
        }

        return $boss_id;

    }  

}
