<?php

namespace App\Services;
use App\Constants\TranslationCode;
use Illuminate\Support\Facades\Validator;
use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Models\RegulationConfiguration;
use App\Models\AccountConfiguration;
use App\Models\Membership;
use App\Models\CommitteeMember;
use App\Models\Committee;
use App\Models\Meeting;
use App\Models\AccountCommitment;
use App\Models\Attendee;
use App\Models\Action;
use App\Services\NotificationService;
use App\Models\NotificationType;
use App\Models\ActionVoting;
use Carbon\Carbon;
Use DB;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Class RegulationService
 *
 * @package App\Services
 */
class RegulationService  extends BaseService
{


        /**
     * Validate request on login
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateCreateRequest(Request $request)
    {
        $rules = [
            'name' => 'required',
            'language_id' => 'required|numeric|exists:languages,id',
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
    
    public function validateUpdateRequest(Request $request)
    {

        $rules = [
            'name' => 'required',

        ];

        $messages = [
            
        ];
        return Validator::make($request->all(), $rules, $messages);
    }



    public function getAccountConfiguration($id, $code){
        
        $regulationConfiguration = RegulationConfiguration::where('language_id',1)->where('code', $code)->first();


        if(isset($regulationConfiguration->id) && !empty($regulationConfiguration->id)){
            $regulation = AccountConfiguration::where('committee_id', $id)
            ->where('regulation_configuration_id', $regulationConfiguration->id)
            ->first();
            if(isset($regulation->id)){
                $regulation->message=sprintf($regulationConfiguration->name,$regulation->value1,$regulation->value2) ;
            }
             
        }
        if(isset($regulation->status) && $regulation->status){
            return $regulation;
        }else{
            $regulation['id'] = 0;
            $regulation['value1'] = 0;
            $regulation['value2'] = 0;
            $regulation['status'] = 0;
            $regulation['message'] = 'this Regulation not activated for this Committee';
            return (object) $regulation; 
        }
    }

    public function success(){
        $regulationReturn =array();
        $regulationReturn['status'] = 'success';
        $regulationReturn['message'] = 'success';
        return $regulationReturn;
    }

    public function check($regulation){
        
        $regulationReturn =array();

        if(isset($regulation->id) && $regulation->id){
            if($regulation->status ==0){

                $regulationReturn['status'] = 'success';

                $regulationReturn['message'] = 'success';
            }
            if($regulation->status ==1){
                $regulationReturn['status'] = 'warning';
                $regulationReturn['message'] = $regulation->message;
            }
    
            if($regulation->status ==2){
                $regulationReturn['status'] = 'error';
                $regulationReturn['message'] = $regulation->message;
            }
        }else{
                $regulationReturn['status'] = 'success';
                $regulationReturn['message'] = 'success';
        }
        

        return $regulationReturn;
    }



//============================================================================================//

    // Common Validation Regulations
    public function isValidIndependentsCount($code ,$id, $percentage){
        
        
        $regulation = $this->getAccountConfiguration($id, $code);

        if($regulation->value1 && $regulation->value1 > $percentage){
            $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $id, $regulation->value1, $percentage );

            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }
    }

    public function isValidMembersCount($code ,$id, $count){
        
       // echo $count;die();
        $regulation = $this->getAccountConfiguration($id, $code);
       // echo 'value 1 is :'.$regulation->value1.' and value 2 is :'.$regulation->value2.' and countis :'.$count;die();
        
        if($regulation->value1 && $regulation->value2){
            if(($regulation->value1 <= $count) && ($regulation->value2 >= $count)){
                return $this->success($regulation);
            }else{
                $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $id, $regulation->value1, $regulation->value2 );
                return $this->check($regulation);
            }
        }elseif($regulation->value1){
            if(($regulation->value1 < $count)){
                return $this->success($regulation);
            }else{
                $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $id, $regulation->value1, $regulation->value2 );
                return $this->check($regulation);
            }
        }elseif($regulation->value2){
            if(($regulation->value2 > $count)){
                return $this->success($regulation);
            }else{
                $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $id, $regulation->value1, $regulation->value2 );
                return $this->check($regulation);
            }
        }else{
            return $this->success($regulation);
        }

        
    }

    public function isValidInExecutivesCount($code, $id, $percentage){

        $regulation = $this->getAccountConfiguration($id, $code);

        if($regulation->value1 && $regulation->value1 > $percentage){

            $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $id, $regulation->value1, $percentage );

            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }
    }

    public function isSetBossWeighting($code, $id){

        $regulation = $this->getAccountConfiguration($id, $code);

        if($regulation->status == 1 || $regulation->status == 2){
            return true;
        }else{
            return false;
        }
    }

    public function isValidAbsenceNotificationMembers($code, $meeting){

        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);
        $array = array();
        if($regulation->status !== 0 && $regulation->value1){
            $committeeMembers = CommitteeMember::where('connected_absences_count','>=',$regulation->value1)
            ->where('committee_id', $meeting->id)->get();
            foreach($committeeMembers as $key => $member){
                $array[$key]=$member->member_id;
            }
        }
        return $array;
    }

    public function isValidMeetingInvitationTime($code, $meeting){

        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);

        $now = Carbon::now();
        $start_at = Carbon::parse($meeting->start_at['full']);
        $diff_days = $start_at->diffInDays($now);

        if($regulation->value1 && $regulation->value1 > $diff_days){
            $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $meeting->committee_id, $diff_days );
            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }
    }

    public function isValidYearlyMeetingCount($code, $meeting){

        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);

        $date1 = Carbon::createFromDate(Carbon::now()->year, 1, 1);
        $date2 = Carbon::createFromDate(Carbon::now()->year, 3, 31);
        $date3 = Carbon::createFromDate(Carbon::now()->year, 6, 31);
        $date4 = Carbon::createFromDate(Carbon::now()->year, 9, 31);
        $date5 = Carbon::createFromDate(Carbon::now()->year, 12, 31);

        $yearlyMeetingCount = Meeting::where('committee_id', $meeting->committee_id)->where('account_id', $meeting->account_id)->whereBetween('created_at', [$date1, $date5])->count();

        $quarterOneMeetingCount = Meeting::where('committee_id', $meeting->committee_id)->where('account_id', $meeting->account_id)->whereBetween('created_at', [$date1, $date2])->count();

        $quarterTwoMeetingCount = Meeting::where('committee_id', $meeting->committee_id)->where('account_id', $meeting->account_id)->whereBetween('created_at', [$date2, $date3])->count();

        $quarterThreeMeetingCount = Meeting::where('committee_id', $meeting->committee_id)->where('account_id', $meeting->account_id)->whereBetween('created_at', [$date3, $date4])->count();

        $quarterFourMeetingCount = Meeting::where('committee_id', $meeting->committee_id)->where('account_id', $meeting->account_id)->whereBetween('created_at', [$date4, $date5])->count();
        $now = Carbon::now();

        if( $now == $date5){
            $total_meeting_count = $yearlyMeetingCount;
            $value1=$regulation->value1*4;
        }elseif($now >= $date4){
            $total_meeting_count = $quarterThreeMeetingCount+$quarterTwoMeetingCount+$quarterOneMeetingCount;
            $value1=$regulation->value1*3;
        }elseif($now >= $date3){
            $total_meeting_count = $quarterThreeMeetingCount+$quarterTwoMeetingCount;
            $value1=$regulation->value1*2;
        }
        elseif($now >= $date2){
            $total_meeting_count = $quarterThreeMeetingCount;
            $value1=$regulation->value1*1;
        }elseif($now < $date2){
            $total_meeting_count = $quarterThreeMeetingCount;
            $value1=$regulation->value1*0;
        }

        if($total_meeting_count<=$value1 && $value1 !=0 ){
            $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $meeting->committee_id, $total_meeting_count );
            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }


    }

    public function isValidMeetingQuorum($code, $meeting){

        $count = $this->countMeetingAttendeesPercentage($meeting);
        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);
        if(isset($regulation->regulation_configuration_id)){
            $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $meeting->committee_id, $count );
        }

        if($count < $regulation->value1)
            return $this->check($regulation);
        else
            return true;
    }
    

    public function isActionApprovedBasedOnRegulation($code, $meeting, $action ){

        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);

        $count = $this->getActionApprovedPercentage($action);

        if($regulation->value1 && $regulation->value1< $count){
            Action::where('id', $action)->update(array('status' => Action::STATUS_REJECTED));
            return $this->check($regulation);
        }else{
            Action::where('id', $action)->update(array('status' => Action::STATUS_APPROVED));
            return $this->success($regulation);
        }
        
    }

    public function isPrivateActionApprovedBasedOnRegulation($code, $action){

        $regulation = $this->getAccountConfiguration($action->committee_id, $code);

        $count = $this->getActionApprovedPercentage($action);

        if($regulation->value1 && $regulation->value1< $count){
            Action::where('id', $action)->update(array('status' => Action::STATUS_REJECTED));
            return $this->check($regulation);
        }else{
            Action::where('id', $action)->update(array('status' => Action::STATUS_APPROVED));
            return $this->success($regulation);
        }
        
    }

    public function isActionBossApprovedBasedOnRegulation($code, $meeting, $action){

        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);

        $count = $this->getActionApprovedPercentage($action);


        if($regulation->status !=0 && $regulation->value1 < $count+1){
            Action::where('id', $action)->update(array('status'=> Action::STATUS_REJECTED));
            return $this->check($regulation);
        }else{
            Action::where('id', $action)->update(array('status'=> Action::STATUS_APPROVED));
            return $this->success($regulation);
        }
    }

    public function isPrivateActionBossApprovedBasedOnRegulation($code,$action){

        $regulation = $this->getAccountConfiguration($action->committee_id, $code);

        $count = $this->getActionApprovedPercentage($action);

        //check if approved and rejected is equal
        //if not equal set the action status to either approved or rejected according to majority
        //else check if the regulation status = 1 then find where is the president voting status
        // set the action status according to the president side


        if($regulation->status !=0 ){
            Action::where('id', $action)->update(array('status'=> Action::STATUS_REJECTED));
            return $this->check($regulation);
        }else{
            Action::where('id', $action)->update(array('status'=> Action::STATUS_APPROVED));
            return $this->success($regulation);
        }
    }


    public function isValidCanDelegate($code, $meeting){
        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);

        if(!$regulation->value1){
            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }
    }

    public function isValidCanExternalDelegate($code, $meeting){

        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);

        if(!$regulation->value1){
            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }
    }

    
 

//============================================================================================//

    // Board Validation Regulations

    //عدد اعضاء مجلس الإدارة لا يتعدى قيمة معينة
    public function isValidBoardMembersCount($board_id, $count){
       
        return $this->isValidMembersCount('BOARD_CODE_1', $board_id, $count);
    }

    //السماح بترجيح كفة الرئيس
    public function isBossWeightingRegulations($action){
       
        if($action->committee->type == "Boards")
        return $this->isSetBossWeighting('BOARD_CODE_10', $action->committee_id);

        if($action->committee->type == "Committees")
        return $this->isSetBossWeighting('COMMITTEE_CODE_7', $action->committee_id);
    }

    //نسبة المستقلين من اجمالى نسبة الأعضاء
    public function isValidBoardIndependentsCount($board_id, $percentage){
        
        return $this->isValidIndependentsCount('BOARD_CODE_2', $board_id, $percentage);
    }

    //نسبة الأعضاء غير التنفيذيين
    public function isValidBoardInExecutivesCount($board_id, $percentage){

        return $this->isValidInExecutivesCount('BOARD_CODE_3', $board_id, $percentage);
    }

    //مدة انعقاد المجلس
    public function isValidBoardDuration($board_id, $duration){
        
        $regulation = $this->getAccountConfiguration($board_id, 'BOARD_CODE_4');

        if($regulation->value1 && $regulation->value1 < $duration){
            
            $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $board_id, $duration );

            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }
    }

    //اشعار في حال تغيب احد الاعضاء 3 اجتماعات متتالية
    public function isValidBoardAbsenceNotificationMembers($meeting){

        return $this->isValidAbsenceNotificationMembers('BOARD_CODE_5', $meeting);
    }

    //يجب ان تتم الدعوة الى الاجتماع بمدة - - قبل موعد الاجتماع
    public function isValidBoardMeetingInvitationTime($meeting){

        return $this->isValidMeetingInvitationTime('BOARD_CODE_6', $meeting);
        
    }

    //عدد اجتماعات المجلس في العام
    public function isValidBoardYearlyMeetingCount($meeting){
        return $this->isValidYearlyMeetingCount('BOARD_CODE_7', $meeting);
    }

    //النصاب القانوني للإجتماع
    public function isValidBoardMeetingQuorum($meeting){
        return $this->isValidMeetingQuorum('BOARD_CODE_8', $meeting);
    }

    //التصويت على القرارات
    public function isBoardActionApprovedBasedOnRegulation($meeting, $action){

        return $this->isActionApprovedBasedOnRegulation('BOARD_CODE_9', $meeting, $action);
    }

    // ترجيح كفة الرئيس في حالة تعادل الأصوات 
    public function isBoardActionBossApprovedBasedOnRegulation($meeting, $action){

        return $this->isActionBossApprovedBasedOnRegulation('BOARD_CODE_10', $meeting, $action);
    }

    //امكانية التفويض بالنيابة
    public function isValidBoardCanDelegate($meeting){

        return $this->isValidCanDelegate('BOARD_CODE_11',$meeting);
    }


    //تفويض الحضور من خارج المجلس
    public function isValidBoardCanExternalDelegate($meeting){
        return $this->isValidCanExternalDelegate('BOARD_CODE_12',$meeting);
    }


//============================================================================================//

// Committee Validation Regulations

    //عدد اعضاء اللجنة لا يتعدى قيمة معينة
    public function isValidCommitteeMembersCount($committee_id, $count){

        return $this->isValidMembersCount('COMMITTEE_CODE_1', $committee_id, $count);
    }

    //النصاب القانوني للإجتماع
    public function isValidCommitteeMeetingQuorum($meeting){
        
        return $this->isValidMeetingQuorum('COMMITTEE_CODE_2', $meeting); 
    }

    //نسبة الأعضاء غير التنفيذيين
    public function isValidCommitteeIndependentsCount($committee_id, $percentage){

        return $this->isValidIndependentsCount('COMMITTEE_CODE_3', $committee_id, $percentage);
    }

    //نسبة تشكيل اللجنة من غير التنفيذيين
    public function isValidCommitteeInExecutivesCount($committee_id, $percentage){

        return $this->isValidInExecutivesCount('COMMITTEE_CODE_4', $committee_id, $percentage);
    }

    //امكانية ان يكون اعضاء المجلس اعضاء في اللجنة
    public function isValidMembersExistInBoardMebers($committee_id, $members){

        $regulation = $this->getAccountConfiguration($committee_id, 'COMMITTEE_CODE_5');

        $committee = Committee::where('id',$committee_id)->first();

        $members = CommitteeMember::where('committee_id', $committee->parent_id)->pluck('member_id')->toArray();
        $members_exists_array=[];
        if(isset($members) && !empty($members)){
            foreach($members as $key=>$member){
                $members_exists_array[$key]=$member;
            }
        }
        $array_intersect = array_intersect($members_exists_array,$members);

        if($regulation->status !=0 && !$regulation->value1  && !empty($array_intersect) ){
            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }
        
    }

    //امكانية ان يكون رئيس اللمجلس رئيس للجنة
    public function isValidBossIsBoardBoss($committee_id, $boss_id){

        $regulation = $this->getAccountConfiguration($committee_id, 'COMMITTEE_CODE_6');

        $committee = Committee::where('id',$committee_id)->first();

        $boss = CommitteeMember::where('committee_id', $committee->parent_id)->where('position_id', CommitteeMember::BOSS_POSITION_ID)->first();

        if($regulation->status !=0 && !$regulation->value1  && isset($boss->member_id)  && ($boss->member_id == $boss_id) ){
            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }
    }

    //التصويت على القرارات
    public function isCommitteeActionApprovedBasedOnRegulation($meeting, $action ){
       return $this->isActionApprovedBasedOnRegulation('COMMITTEE_CODE_7', $meeting, $action);
    }
    
    //التصويت على القرارات
    public function isCommitteePrivateActionApprovedBasedOnRegulation($action ){
       return $this->isPrivateActionApprovedBasedOnRegulation('COMMITTEE_CODE_7', $action);
    }

    // ترجيح كفة الرئيس في حالة تعادل الأصوات 
    public function isCommitteePrivateActionBossApprovedBasedOnRegulation($action){
        return $this->isPrivateActionBossApprovedBasedOnRegulation('BOARD_CODE_10', $action);
    }

    // ترجيح كفة الرئيس في حالة تعادل الأصوات 
    public function isCommitteeActionBossApprovedBasedOnRegulation($meeting, $action){
        return $this->isActionBossApprovedBasedOnRegulation('COMMITTEE_CODE_8', $meeting, $action);
    }

    //عدد اجتماعات المجلس في العام
    public function isValidCommitteeYearlyMeetingCount($meeting){

        return $this->isValidYearlyMeetingCount('COMMITTEE_CODE_8', $meeting);
    }

    //امكانية التفويض بالنيابة
    public function isValidCommitteeCanDelegate($meeting){
        return $this->isValidCanDelegate('COMMITTEE_CODE_9',$meeting);
    }
    
    
    //تفويض الحضور من خارج المجلس
    public function isValidCommitteeCanExternalDelegate($meeting){

        return $this->isValidCanExternalDelegate('COMMITTEE_CODE_10',$meeting);
    }

    //اشعار في حال تغيب احد الاعضاء 3 اجتماعات متتالية
    public function isValidCommitteeAbsenceNotificationMembers($meeting){

        return $this->isValidAbsenceNotificationMembers('COMMITTEE_CODE_11', $meeting);
    }


    //يجب ان تتم الدعوة الى الاجتماع بمدة - - قبل موعد الاجتماع
    public function isValidCommitteeMeetingInvitationTime($meeting){

        return $this->isValidMeetingInvitationTime('COMMITTEE_CODE_12', $meeting);
    }


    // Assocations ================================================================================================

    public function isValidAssocciationYearlyMeetingCount($meeting){
        $committee = Committee::where('committee_id', $meeting->id)->first();
        if($committee->association_code =="ASSOCIATION_ORDINARY"){
            $code="ASSOCIATION_ORDINARY_CODE_1";
        }

        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);

        $date1 = Carbon::createFromDate(Carbon::now()->year, 1, 1);
        $date2 = Carbon::createFromDate(Carbon::now()->year, 12, 31);

        $yearlyMeetingCount = Meeting::where('committee_id', $meeting->committee_id)->where('account_id', $meeting->account_id)->whereBetween('created_at', [$date1, $date2])->count();

        $now = Carbon::now();

        if( $now == $date5){
            $total_meeting_count = $yearlyMeetingCount;
            $value1=$regulation->value1*4;
        }

        if($total_meeting_count<=$value1 && $value1 !=0 ){
            if(isset($regulation->regulation_configuration_id)){
                $this->updateCommitment($regulation->regulation_configuration_id, AccountCommitment::OBJECT_TYPE_BOARDS, $meeting->committee_id, $total_meeting_count );
            }
            return $this->check($regulation);
        }else{
            return $this->success($regulation);
        }



    }

    public function isValidAssocciationMeetingAttendees($meeting){
        
        $committee = Committee::where('id', $meeting->id)->first();

        if($committee->association_code =="ASSOCIATION_ORDINARY"){
            $code="ASSOCIATION_ORDINARY_CODE_2";
        }

        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);

        $attendees = Attendee::where('meeting_id', $meeting->id)->where('is_committee_member',0)->get();

        if($regulation->status == 0){
            return $this->success();
        }
        $is_Error=false;
        foreach($attendees as $key=>$attendee){
            if($this->calculateShare($attendee->id) < $regulation->value1){
                $is_Error =true;
            }
        }
        if($is_Error){
            return $this->check($regulation);
        }
    }

    public function calculateShare($user_id){
        $user = User::where('id', $attendee)->first();
        return $user->shares;
    }



    public function isValidAssocciationMeetingQuorum($meeting){
        if(!$meeting->is_second_to_id){
            if($committee->association_code =="ASSOCIATION_CONSTITUENT"){
                $code="ASSOCIATION_CONSTITUENT_CODE_2";
            }
            if($committee->association_code =="ASSOCIATION_ORDINARY"){
                $code="ASSOCIATION_ORDINARY_CODE_4";
            }
            if($committee->association_code =="ASSOCIATION_NON_ORDINARY"){
                $code="ASSOCIATION_NON_ORDINARY_CODE_2";
            }
        }else{
            if($committee->association_code =="ASSOCIATION_CONSTITUENT"){
                $code="ASSOCIATION_CONSTITUENT_CODE_4";
            }
            if($committee->association_code =="ASSOCIATION_ORDINARY"){
                $code="ASSOCIATION_ORDINARY_CODE_6";
            }
            if($committee->association_code =="ASSOCIATION_NON_ORDINARY"){
                $code="ASSOCIATION_NON_ORDINARY_CODE_4";
            }
        }

        return $this->isValidMeetingQuorum($code, $meeting);

    }



    public function isValidAssocationMeetingInvitationTime($meeting){
        $code = "ASSOCIATION_CONSTITUENT_CODE_1";
        $regulation = $this->getAccountConfiguration($meeting->committee_id, $code);
        return $this->success($regulation);
        // disabled temp till checked
        // if(!$meeting->is_second_to_id){
        //     if($committee->association_code =="ASSOCIATION_CONSTITUENT"){
        //         $code = "ASSOCIATION_CONSTITUENT_CODE_1";
        //     }
        //     if($committee->association_code =="ASSOCIATION_ORDINARY"){
        //         $code = "ASSOCIATION_ORDINARY_CODE_3";
        //     }
        //     if($committee->association_code =="ASSOCIATION_NON_ORDINARY"){
        //         $code = "ASSOCIATION_NON_ORDINARY_CODE_1";
        //     }
        // }else{
        //     if($committee->association_code =="ASSOCIATION_CONSTITUENT"){
        //         $code = "ASSOCIATION_CONSTITUENT_CODE_3";
        //     }
        //     if($committee->association_code =="ASSOCIATION_ORDINARY"){
        //         $code = "ASSOCIATION_ORDINARY_CODE_5";
        //     }
        //     if($committee->association_code =="ASSOCIATION_NON_ORDINARY"){
        //         $code = "ASSOCIATION_NON_ORDINARY_CODE_3";
        //     }
        // }

        // return $this->isValidMeetingInvitationTime($code, $meeting);

    }
    // ================================================================================================
    public function isBoardMeeting($meeting){
        $committee = Committee::where('id', $meeting->committee_id)->first();
        if($committee->type == "Boards"){
            return true;
        }else{
            return false;
        }
        
    }

    public function isAssociationMeeting($meeting){

        $committee = Committee::where('id', $meeting->committee_id)->first();
        if($committee->type == "Associations"){
            return true;
        }else{
            return false;
        }
        
    }

    public function isCommitteeMeeting($meeting){

        $committee = Committee::where('id', $meeting->committee_id)->first();
        if($committee->type == "Committees"){
            return true;
        }else{
            return false;
        }
    }

    public function countMeetingAttendeesPercentage($meeting){

        $confirmed_count = Attendee::where('meeting_id',$meeting->id)->where('is_committee_member',1)
        ->where(function ($query) {
            $query->where('status', '=', Attendee::STATUS_CONFIRMED)
                  ->orWhere('status', '=', Attendee::STATUS_IS_ADMIN_ATTENDED);
        })
        ->count();
        
        $invited_count = Attendee::where('meeting_id',$meeting->id)->where('is_committee_member',1)->count();

        if(isset($confirmed_count) && isset($invited_count) && $confirmed_count && $invited_count ){
            return ($confirmed_count/$invited_count)*100;
        }else{
            return 0;
        }
        
    }

    public function getActionApprovedPercentage($action){

        if(isset($accept_voted_count) && isset($total_voted_count) && $accept_voted_count && $total_voted_count ){
            return ($accept_voted_count/$total_voted_count)*100;
        }else{
            return 0;
        }
    }

    public function mergeRegulationsConfigurations($regulations){

        $returnRegulation['status'] = 'success';
        $returnRegulation['message'] = '';
        
        if(isset($regulations) && !empty($regulations)){
            
            foreach($regulations as $regulation){
                if(is_array($regulation)) {
                    if($regulation['status'] == 1 || $regulation['status'] == 'warning'){
                        $returnRegulation['status'] = 'warning';
                        $returnRegulation['message'].= $regulation['message']." , ";
                         
                    }elseif($regulation['status'] == 2 || $regulation['status'] == 'error'){
                        $returnRegulation['status'] = 'error';
                        $returnRegulation['message'].=$regulation['message']." , ";
                    }
                }
            }

            // foreach($regulations as $regulation){
            //     if($regulation['status'] == 2 || $regulation['status'] == 'error'){
            //         $returnRegulation['status'] = 'error';
            //         $returnRegulation['message'].=$regulation['message']." , ";
            //     }
            // }
        }


        if($returnRegulation['status']=="success"){
            $returnRegulation['message'] = 'success';
        }
        return (object) $returnRegulation;
    }

    public function updateCommitment($configuration_id, $object_type, $object_id, $value1, $value2 = 0)
    {

        $regulation_configuration = RegulationConfiguration::where('id', $configuration_id)->first();
        $regulation_id = $regulation_configuration->regulation_id;
        $object = DB::table($object_type)->where('id', $object_id)->first();
        $account_id = $object->account_id;

        AccountCommitment::where('account_id', $account_id)
            ->where('account_id', $account_id)
            ->where('regulation_id', $regulation_id)
            ->where('regulation_configuration_id', $configuration_id)
            ->where('object_type', $object_type)
            ->where('object_id', $object_id)
            ->delete();

        AccountCommitment::create(
            array(
                'account_id' => $account_id,
                'regulation_id' => $regulation_id,
                'account_id' => $account_id,
                'regulation_configuration_id' => $configuration_id,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'value1' => $value1,
                'value2' => $value2,
            )
        );
        return true;
    }

    public function checkPrivateActionVotingPercentage($action): bool
    {
        if($action->quorum) {
            $membersCount = CommitteeMember::where('committee_id', $action->committee_id)->count();
            $votedMembersCount = ActionVoting::where('action_id', $action->id)->count();
            $percentage = ($membersCount != 0) ? ($votedMembersCount/$membersCount)*100 : 0;
            
            return $percentage >= $action->quorum;
        }

        return true;
    }

    public function isCommitteePrivateActionValidQuorum(Action $action): array
    {
        if(!$this->checkPrivateActionVotingPercentage($action)) {
            $returnRegulation['status'] = 'error';
            $returnRegulation['message'] = __('Invalid Action Quorum');

            return $returnRegulation;
        }

        $returnRegulation['status'] = 'success';
        $returnRegulation['message'] = '';
        return $returnRegulation;
    }

}
