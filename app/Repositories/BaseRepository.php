<?php

namespace App\Repositories;

use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\Auth;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Application
     */
    protected $app;

        /**
     * The relations to eager load.
     *
     * @var
     */
    protected $with = [];

    private $dashboardbaseUrl;

  /**
     * Sets relations for eager loading.
     *
     * @param $relations
     * @return $this
     */
    public function with ($relations)
    {

        if (is_string($relations)) {
            $this->with = explode(',', $relations);

            return $this;
        }

        $this->with = is_array($relations) ? $relations : [];

        return $this;
    }

    /**
     * @param Application $app
     *
     * @throws \Exception
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeModel();

        $this->dashboardbaseUrl = env('DASHBOARD_API_BASE_URL');
    }

    /**
     * Get searchable fields array
     *
     * @return array
     */
    abstract public function getFieldsSearchable();

    /**
     * Configure the Model
     *
     * @return string
     */
    abstract public function model();

    /**
     * Make Model instance
     *
     * @throws \Exception
     *
     * @return Model
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Paginate records for scaffold.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    //public function all($search = [], $skip = null, $limit = null, $columns = ['*'])
    //public function paginate($perPage, $columns = ['*'])
    public function paginate($perPage, $search = [], $columns = ['*'])
    {
        //$query = $this->allQuery($search, $skip, $limit);
        $query = $this->allQuery($search);
        //$query = $this->allQuery();

        return $query->paginate($perPage, $columns);
    }

    function startsWith ($string, $startString) 
        { 
            $len = strlen($startString); 
            return (substr($string, 0, $len) === $startString); 
        } 

    function endsWith ($string, $startString) 
    { 
        $len = strlen($startString); 
        return (substr($string, $len-1,$len ) === $startString); 
    } 

    /**
     * Build a query for retrieving all records.
     *
     * @param array $search
     * @param int|null $skip
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function allQuery($search = [], $skip = null, $limit = null, $sort =null)
    {
        //$query = $this->model->newQuery();
          $query = $this->model->newQuery()->with($this->with);


        if (count($search)) {
            
            foreach($search as $key => $value) {
                    if(is_array($value)){
                        
                        $query->whereIN($key, $value);
                    }else{
                            if($this->startsWith( $value, '%' ) || $this->endsWith( $value, '%' )){
                                $query->where($key,'like', $value);
                            }else{
                                $query->where($key, $value);
                            }
                    }
            }
        }
        if (!is_null($skip)) {
            $query->skip($skip);
        }

        if (!is_null($limit)) {
            $query->limit($limit);
        }
        if (!is_null($sort)) {
            $query->orderBy('created_at', $sort);
        }

        return $query;
    }

    /**
     * Retrieve all records with given filter criteria
     *
     * @param array $search
     * @param int|null $skip
     * @param int|null $limit
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function all($search = [], $skip = null, $limit = null , $columns = ['*'], $sort = null)
    {
        $query = $this->allQuery($search, $skip, $limit,$sort);

        return $query->get($columns);
    }

    /**
     * Create model record
     *
     * @param array $input
     *
     * @return Model
     */
    public function create($input,$withAttachments=false)
    {
        
        $model = $this->model->newInstance($input);
        
        $model->save();

        if(!empty($input['attachments']) && $withAttachments){
            
            $this->assignAttachments($input['attachments'],$model->id);
        }

        return $model;
    }

    /**
     * Find model record for given id
     *
     * @param int $id
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function find($id, $columns = ['*'])
    {
        $query = $this->model->newQuery()->with($this->with);

        return $query->find($id, $columns);
    }

    /**
     * Update model record for given id
     *
     * @param array $input
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function update($input, $id, $withAttachments=false)
    {
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        $model->fill($input);

        $model->save();

        if(!empty($input['attachments']) && $withAttachments){
            
            $this->assignAttachments($input['attachments'],$model->id);
        }

        return $model;
    }


    
    /**
     * @param int $id
     *
     * @throws \Exception
     *
     * @return bool|mixed|null
     */
    public function delete($id)
    {
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        return $model->delete();
    }
    public function localMergeArray($array_1,$array2){
        foreach($array2 as $key=>$item){
            $array_1[$key]=$item;
        }
        return $array_1;
    }

    
    public function createMultiplewithAttachments($inputs, $extrasInput, $foreign_CODE=''){
     /*
        
        foreach($inputs as $key=>$input){
                $input = $this->localMergeArray($input, $extrasInput);
                $object = $this->create($input);
                    $this->uploadAttachments($input['attachments'],$object->id);
                if($foreign_CODE){
                $inputs[$key][$foreign_CODE]=$object->id;
                }
            
           
        }
        return $inputs;
        */
    }

    public function assignAttachments($attacchments,$objectId){
       
        if(!empty($attacchments) & !empty($objectId)){

            $media_relation_table=strtolower(str_replace("App\Models\\","",$this->model()))."s_medias";
            $object_relation_id=strtolower(str_replace("App\Models\\","",$this->model()))."_id";

         

            /**
            * Delete first exits
            */
            DB::table($media_relation_table)->where($object_relation_id, $objectId)->delete();
            

        //     if ($this->model() =="meeting")
        //     {
        //         $media_relation_table = "attachments" ;
        //         DB::table($media_relation_table)->where($object_relation_id, $objectId)->all();
        //         foreach($attacchments as $attacchment) 
        //             {
        //                 DB::table($media_relation_table)->where("meeting_id", $attacchment[$objectId])->delete();
        //             }    
        // }
           foreach($attacchments as $attacchment){
               /*
               $creator_id=$attacchment['creator_id'];
               $account_id=$attacchment['account_id'];
               */
               $attachment_id = $attacchment['media_id'];

                DB::table($media_relation_table)->insert(array('media_id'=>$attachment_id,
                                                              $object_relation_id=>$objectId,
                                                              'created_at'=>date("Y-m-d H:i:s"),
                                                                'updated_at'=>date("Y-m-d H:i:s")));
           }
        }   
        
    }
    public function getLookupTitles(){
        return \App\Models\Title::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupBoardsConfigurations(){
        $user = Auth::user();
        $account = $user->account;
        $response = \App\Models\RegulationConfiguration::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->where('is_board', 1);
        if(in_array($account->type_id, [1, 6])) {
            $response->where('governmental_facility', 1);
        }
        $response = $response->get(['translation_id as id','values_count','name'])->toArray();
        return $response;
    }

    public function getLookupCommitteesConfigurations(){
        $user = Auth::user();
        $account = $user->account;
        $response = \App\Models\RegulationConfiguration::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->where('is_committee', 1);
        if(in_array($account->type_id, [1, 6])) {
            $response->where('governmental_facility', 1);
        }
        $response = $response->get(['translation_id as id','values_count','name'])->toArray();
        return $response;
    }

    public function getLookupAssociationsConfigurations(){
        $user = Auth::user();
        $account = $user->account;
        $response = \App\Models\RegulationConfiguration::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->where('is_association', 1);
        if(in_array($account->type_id, [1, 6])) {
            $response->where('governmental_facility', 1);
        }
        $response = $response->get(['translation_id as id','values_count','name'])->toArray();
        return $response;
    
    }

    public function getLookupAssociations(){

        return \App\Models\Association::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupCategories(){

        return \App\Models\Category::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupCapabilities(){

        return \App\Models\Capability::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupActionTypes(){
        //echo "here";die();
        return \App\Models\ActionType::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupAccountTypes(){
        //echo "here";die();
        return \App\Models\AccountType::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupLanguages(){
        //echo "here";die();
        return \App\Models\Language::where('status', 1)
        ->get(['code','name'])->toArray();
    
    }

    public function getLookupCurrencies(){
        //echo "here";die();
        return \App\Models\Currency::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getAcccountLocations($account_id){
        return DB::table('locations')
        ->where('language_id', $this->getLangIdFromLocale())
        ->where('status', 1)
        ->where('account_id', $account_id)
        ->get(['translation_id as id','name','longitude','latitude'])->toArray();
    }

    public function getLookupMemberships($account_type_id){
        return \App\Models\Membership::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->where('account_type_id', $account_type_id)
        ->get(['translation_id as id','name'])->toArray();
    }

    public function getLookupRoles(){
        return \App\Models\Role::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupPermissions(){
        return \App\Models\Permission::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupNationalities(){
        return \App\Models\Nationality::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->orderBy('sort','DESC')
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupPositions(){
        return \App\Models\Position::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupSharesTypes(){
        return \App\Models\ShareType::where('status', 1)
        ->where('language_id', $this->getLangIdFromLocale())
        ->get(['translation_id as id','name'])->toArray();
    
    }

    public function getLookupUserTranslation($account_id){
        return \App\Models\User::with('translation:user_id,title,name')
                                //->where('status', 1)
                                ->where('account_id', $account_id)
                                ->get(['id'])
                                ->toArray();
    }

    public function getAccountBoards($user){
        $boards = \App\Models\Committee::with('translation:committee_id,name')
        ->where('status', 1)
        ->where('is_completed', 1)
        ->where('type', 'Boards');
        
        if($user->role_id !== 1)
        $boards = $boards->whereRaw('amanuensis_id = ? or secretary_id = ?', [$user->id,$user->id]);
        
        $boards = $boards->where('account_id', $user->account_id)
        ->get(['id'])->toArray();

        return $boards;
    }

    public function getAcccountAssociations($table,$account_id){

        return \App\Models\Committee::with('translation:committee_id,name')
        ->where('status', 1)
        ->where('type', 'Assocations')
        ->where('account_id', $account_id)
        ->get(['id'])->toArray();
    }

   

    public function getBoardCommittees($board_id){

        return \App\Models\Committee::with('translation:committee_id,name')
        ->where('status', 1)
        ->where('type', 'Committees')
        ->where('account_id', $account_id)
        ->where('parent_id', $board_id)
        ->get(['id'])->toArray();
    }

    public function getBoardMeetingAttendees($meeting_id){

        return \App\Models\Attendee::with('user:id','user.translation:language_id,user_id,title,name')
        ->where('meeting_id', $meeting_id)
        ->get(['member_id'])->toArray();
    }

    public function getBoardOrCommitteeMembers($board_id){

        return \App\Models\CommitteeMember::with('user:id','user.translation:language_id,user_id,title,name','user.translation:language_id,user_id,title,name','membership','position')
        ->where('committee_id', $board_id)
        ->get(['member_id','position_id','membership_id', 'id','committee_id','shares','joining_date'])->toArray();
    }

        /**
     * Return searchable fields
     *
     * @return array
     */
    public function mapResultByTranslation($data)
    {
        $response = [];

        foreach($data as $key => $item){
            $row = $item[0];
            foreach($item as $value){
    
                if($value['language_id'] === 1)
                    $row['name_ar'] = $value['name'];
                else
                    $row['name_en'] = $value['name'];
            }
            $response[] = $row;
        }

        return $response;
        
    }

    public function removeFormToken($data=[]){

        return $data;
    }


    public function getCurl(string $url, $data=[], string $token = null)
    {
         return Curl::to( $this->dashboardbaseUrl.$url)
            ->withData($data)
            ->asJson(true)
            ->get();
    }

    

    public function postCurl(string $url, $data=[], string $token = null)
    {
        return Curl::to($this->dashboardbaseUrl.$url)
            ->withData($data)
            ->asJson(true)
            ->withHeader("Accept: application/json")
            ->post();
    }

    public function putCurl(string $url, $data=[], string $token = null)
    {
        return Curl::to($this->dashboardbaseUrl.$url)
            ->withData($data)
            ->asJson(true)
            ->withHeader("Accept: application/json")
            ->put();    
    }

    public function deleteCurl(string $url, $data = [], string $token = null)
    {
        
        return Curl::to($this->dashboardbaseUrl.$url)
            ->asJson( true )
            ->withHeader("Accept: application/json")
            ->delete();
    }

    public function pullConfigurations($creator_id, $account_id){
        $this->pullTemplates($creator_id, $account_id);
        return true;
    }

    public function pullTemplates($creator_id, $account_id){

        $notifications_templates = DB::table('notifications_templates')->whereNull('deleted_at')->where('is_default', 1)->where('status', 1)->get()->toArray();
        foreach($notifications_templates as $key=>$template){
            \App\Models\NotificationTemplate::create(
                                                        array('account_id'=>$account_id,
                                                              'creator_id'=>$creator_id,
                                                              'type_id'=>$template->type_id,
                                                              'title'=>$template->title,
                                                              'content'=>$template->content,
                                                              'language_id'=>$template->language_id,
                                                              'is_sms'=>$template->is_sms,
                                                              'is_push'=>$template->is_push,
                                                              'is_email'=>$template->is_email,
                                                              'status'=>$template->status,
                                                              'is_default'=>0,
                                                        )
                                                    );
        }
        return true;
    }

    public function getAllAssociationCodes(){
        $return = array(
            '0'=>array('code'=>'ASSOCIATION_CONSTITUENT','en'=>'ASSOCIATION_CONSTITUENT','ar'=>'ASSOCIATION_CONSTITUENT'),
            '1'=>array('code'=>'ASSOCIATION_ORDINARY','en'=>'ASSOCIATION_ORDINARY','ar'=>'ASSOCIATION_ORDINARY'),
            '2'=>array('code'=>'ASSOCIATION_NON_ORDINARY','en'=>'ASSOCIATION_NON_ORDINARY','ar'=>'ASSOCIATION_NON_ORDINARY'),
            );

            return (object) $return;
    }

    public function getReportShareCodes(){
        $return = array(
            '0'=>array('code'=>1,'en'=>'SHARE_TO_ALL','ar'=>'إرسال للكل'),
            '1'=>array('code'=>2,'en'=>'SHARE_TO_PRESIDENT_THEN_MEMBERS','ar'=>'إرسال للرئيس أولاً ثم الأعضاء'),
            '2'=>array('code'=>3,'en'=>'SHARE_TO_MEMBERS_THEN_PRESIDENT','ar'=>'إرسال للأعضاءأولاً ثم الرئيس'),
            );
            return (object) $return;
    }

    public function getAllStatuses(){


        $statuses['users'][0]=array(
            '0'=>array('key'=>0,'en'=>'Not Confirmed','ar'=>'Not Confirmed'),
            '1'=>array('key'=>1,'en'=>'EMAIL_UNCONFIRMED','ar'=>'EMAIL_UNCONFIRMED'),
            '2'=>array('key'=>2,'en'=>'STATUS_MOBILE_UNCONFIRMED','ar'=>'STATUS_MOBILE_UNCONFIRMED'),
            '3'=>array('key'=>3,'en'=>'Confirmed','ar'=>'Confirmed'),
            );

        $statuses['accounts'][0]=array(
            '0'=>array('key'=>0,'en'=>'Blocked','ar'=>'Blocked'),
            '1'=>array('key'=>1,'en'=>'Published','ar'=>'Published'),
            );


        $statuses['boards'][0]=array(
            '0'=>array('key'=>0,'en'=>'Draft','ar'=>'Draft'),
            '1'=>array('key'=>1,'en'=>'Published','ar'=>'Published'),
            );

        $statuses['committees']=array(
            '0'=>array('key'=>0,'en'=>'Draft','ar'=>'Draft'),
            '1'=>array('key'=>1,'en'=>'Published','ar'=>'Published'),
        );
        $statuses['associations']=array(
            '0'=>array('key'=>0,'en'=>'Draft','ar'=>'Draft'),
            '1'=>array('key'=>1,'en'=>'Published','ar'=>'Published'),
        );

        $statuses['meetings']=array(
            '0'=>array('key'=>0,'en'=>'Draft','ar'=>'مسودة'),
            '1'=>array('key'=>1,'en'=>'Published','ar'=>'منشور'),
            '2'=>array('key'=>2,'en'=>'Started','ar'=>'بدأ'),
            '3'=>array('key'=>4,'en'=>'Canceled','ar'=>'ملغي'),
            '4'=>array('key'=>5,'en'=>'Finished','ar'=>'منتهي'),
            '5'=>array('key'=>3,'en'=>'MINISTRY APPROVED','ar'=>'متوافق عليه من الوزارة'),
            '6'=>array('key'=>6,'en'=>'Meeting times choices','ar'=>'مقترحات لمواعيد الإجتماع'),
        );

        $statuses['attendees']=array(
            '0'=>array('key'=>0,'en'=>'Draft','ar'=>'لم يؤكد'),
            '1'=>array('key'=>1,'en'=>'Invited','ar'=>'لم يؤكد حضور'),
            '2'=>array('key'=>2,'en'=>'Confirmed','ar'=>'أكد الحضور'),
            '3'=>array('key'=>5,'en'=>'Canceled','ar'=>'معتذر'),
            '4'=>array('key'=>3,'en'=>'Admin attended','ar'=>'أكد الحضور'),
            '5'=>array('key'=>4,'en'=>'Attended','ar'=>'حضر'),
            '6'=>array('key'=>7,'en'=>'Manager','ar'=>'مدير'),
            '7'=>array('key'=>6,'en'=>'Absence','ar'=>'غياب'),
        );

    
       

        $statuses['actions']=array(
            '0'=>array('key'=>1,'en'=>'new','ar'=>'جديد'),
            '1'=>array('key'=>2,'en'=>'Ready to Vote','ar'=>'جاهز للتصويت'),
            '2'=>array('key'=>3,'en'=>'Vote Closed','ar'=>'مغلق'),
            '3'=>array('key'=>4,'en'=>'Require New Meeting','ar'=>'يحتاج لإجتماع'),
            '4'=>array('key'=>5,'en'=>'Approved','ar'=>'متفق عليه'),
            '5'=>array('key'=>6,'en'=>'REJECTED','ar'=>'غير موافق'),
            '6'=>array('key'=>7,'en'=>'Published','ar'=>'منشور'),
            '7'=>array('key'=>8,'en'=>'Ended','ar'=>'منتهي'),
            '8'=>array('key'=>9,'en'=>'Canceled','ar'=>'ملغي'),
            '9'=>array('key'=>10,'en'=>'opened for voting','ar'=>'جاري التصويت'),
        );

        $statuses['action_votings']=array(
            '0'=>array('key'=>1,'en'=>'Accept','ar'=>'موافق'),
            '1'=>array('key'=>2,'en'=>'Reject','ar'=>'غير موافق'),
            '2'=>array('key'=>3,'en'=>'Request meeting','ar'=>'مطلوب اجتماع'),
            '3'=>array('key'=>5,'en'=>'Refrain','ar'=>'متحفظ')
        );

        $statuses['agendas']=array(
            '0'=>array('key'=>0,'en'=>'Draft','ar'=>'Draft'),
            '1'=>array('key'=>1,'en'=>'Published','ar'=>'Published'),
            '2'=>array('key'=>2,'en'=>'POSTPONED','ar'=>'POSTPONED'),
            '3'=>array('key'=>3,'en'=>'OFF','ar'=>'OFF'),
        );

        $statuses['todos']=array(
            '0'=>array('key'=>0,'en'=>'Draft','ar'=>'Draft'),
            '1'=>array('key'=>1,'en'=>'Published','ar'=>'Published'),
        );


        $statuses['tasks']=array(
            '0'=>array('key'=>1,'en'=>'New','ar'=>'جديدة'),
            '1'=>array('key'=>2,'en'=>'INPROGRESS','ar'=>'جاري العمل عليها'),
            '2'=>array('key'=>3,'en'=>'Finished','ar'=>'منتهية'),
        );

        $statuses['action_voting'] = array(
            '0'=>array('code'=>1,'en'=>'STATUS_ACCEPT','ar'=>'موافقة'),
            '1'=>array('code'=>2,'en'=>'STATUS_REJECT','ar'=>'رفض'),
            '3'=>array('code'=>3,'en'=>'STATUS_REQUEST_MEETING','ar'=>'طلب اجتماع'),
            '4'=>array('code'=>4,'en'=>'MINUMUM_REQUEST_MEETING_BEFORE_END_VOTING','ar'=>'MINUMUM_REQUEST_MEETING_BEFORE_END_VOTING'),
            '5'=>array('code'=>5,'en'=>'STATUS_REFRAIN','ar'=>' تحفظ ')
            );


        return (object) $statuses;
    }

    public function getLangIdFromLocale()
    {
        return (app()->getLocale() == 'ar') ? 1 : 2;
    }

}
