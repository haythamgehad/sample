<?php

namespace App\Services;
use App\Constants\TranslationCode;
use Illuminate\Support\Facades\Validator;
use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Models\Media;
use App\Models\Attendee;
use App\Models\Directory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\DirectoryService;
use App\Services\PDFService;
use App\Services\NotificationService;
use App\Models\NotificationType;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class MediaService
 *
 * @package App\Services
 */
class MediaService extends BaseService
{

    public function copyToPDF($media_id){

        $pdfService = new PDFService();

        $media = Media::with('directory')->where('id', $media_id)->first();

        if($media->encrypted_extention){
            \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
        }
    
        $path = $media->directory['path']."/".$media ->path ;

        $pdfPath = $pdfService->convertToPDF($path);

        $newMedia = $media;

        $newMedia->path = str_replace($media->directory['path']."/", "",$pdfPath);

        $newMedia->save();

        return $newMedia->id;
    }

    public function convertToPDFAndCopyByID($media_id){
        $media = Media::with('directory')->where('id', $media_id)->first();

        if($media->encrypted_extention){
            \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
        }
    
        $path = $media->directory['path']."/".$media ->path ;

        return $path;
    }

    public function getDecryptedPath($media_id){
        $media = Media::with('directory')->where('id', $media_id)->first();

        if($media->encrypted_extention){
            \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
        }
    
        $path = $media->directory['path']."/".$media ->path ;

        return $path;
    }
    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */


    public function create(Request $request, $directory)
    {        

            $file = $request->file('file');

            if(isset($request->inputFile)){
                $file = $request->inputFile;
            }
        
            $disk = Storage::disk( 'local' );

            if($request->media_id){
                $media = Media::find($request->media_id);
                $directory = Directory::find($media->directory_id);
            }
            $path = Storage::disk('local')->putFile($directory->path, $file);

            $short_path = $file->getClientOriginalName();

            $new_path = $directory->path.'/'.$short_path;
            
            $file_exists = Storage::disk('local')->exists($new_path.'.enc');

            if($file_exists){
                $short_path=time().'_copy_'.$short_path;
                $new_path = $directory->path.'/'.$short_path;
            }

            $size = $disk->size($path);
    
            $mimeType = $disk->mimeType($path);
    
            $encryptionKey = $this->generateKey(43);

            if($request->media_id){
                $short_path=time().'_copy_'.$short_path.'.pdf';
                $new_path = $directory->path.'/'.$short_path;
                Storage::disk('local')->move($path, $new_path);
                $media = Media::where('id', $request->media_id)->update(array('path'=>$short_path));
                return $request->media_id;
            }else{
                Storage::disk('local')->move($path, $new_path);
                \SoareCostin\FileVault\Facades\FileVault::key($encryptionKey)->encryptCopy($new_path);
    
                $media = new Media;
                $media->title = $request->title;
                $media->directory_id = $request->directory_id;
                $media->account_id = $request->account_id;
                $media->creator_id = $request->creator_id;
                $media->is_created_pdf = 0;
                $media->name = $short_path;
                $media->path = $short_path;
                $media->size = $size;
                $media->encrypted_extention = '.enc';
                $media->type = $mimeType;
                $media->encryption_CODE = $encryptionKey;
                $media->status = 1;
                $media->is_public = $request->is_public;
                $media->is_garbage = $request->is_garbage;
                $media->is_account_logo = $request->is_account_logo ?? 0;
                $media->hash = $request->hash ?? null;
    
                $media->save();
            }
            
           return $media->id; 
    
    }

    public function createMultiple(Request $request, $directory)
    {        
            
            $ids = array();

            $files = $request->file('files');

            $disk = Storage::disk( 'local' );  
            foreach($files as $key => $file){
                $path = Storage::disk('local')->putFile($directory->path, $file);

                $short_path = $file->getClientOriginalName();

                $new_path = $directory->path.'/'.$short_path;
                
                $file_exists = Storage::disk('local')->exists($new_path.'.enc');

                if($file_exists){
                    $short_path=time().'_copy_'.$short_path;
                    $new_path = $directory->path.'/'.$short_path;
                }

                
                $size = $disk->size($path);
        
                $mimeType = $disk->mimeType($path);
        
                $encryptionKey = $this->generateKey(43);

                Storage::disk('local')->move($path, $new_path);
                
                \SoareCostin\FileVault\Facades\FileVault::key($encryptionKey)->encryptCopy($new_path);

                $media = new Media;

                $media->directory_id = $request->directory_id;
                $media->account_id = $request->account_id;
                $media->creator_id = $request->creator_id;
                $media->is_created_pdf = 0;
                $media->name = $short_path;
                $media->path = $short_path;
                $media->size = $size;
                $media->encrypted_extention = '.enc';
                $media->type = $mimeType;
                $media->encryption_CODE = $encryptionKey;
                $media->status = 1;
                $media->is_public = $request->is_public;
                $media->is_garbage = $request->is_garbage;
                $media->is_my_directory = $request->is_my_directory;
                $media->is_system_file = $request->is_system_file ?? 1;

                $media->save();
                
                $ids[$key] = $media->id; 
            }
        
            return $ids;
    }

    public function moveLogoToDirectoryByPath($media_id, $directory_path, $account_id){
        $this->moveDirectoryByPath($media_id, $directory_path);
        Media::where('id', $media_id)->update(array('is_account_logo'=>1));
    }
    public function moveDirectoryByPath($media_id, $directory_path){
        $directory = Directory::where('path', $directory_path)->first();
        
        $media = Media::with('directory')->where('id', $media_id)->first();

        $current_path = $media->directory->path."/".$media->path;

        $new_path = $directory_path."/".$media->path;

        if($new_path != $current_path ){

            if (Storage::disk('local')->exists($current_path) && !Storage::disk('local')->exists($new_path) ) {
                Storage::disk('local')->move($current_path, $new_path);
            }

            if (Storage::disk('local')->exists($current_path.$media->encrypted_extention) && !Storage::disk('local')->exists($new_path.$media->encrypted_extention) ) {
                Storage::disk('local')->move($current_path.$media->encrypted_extention, $new_path.$media->encrypted_extention);
            }

            if(isset($directory->id)){
                $input['directory_id'] = $directory->id ;
                $media = Media::where('id', $media_id)->update($input);
            }
        }
    }

    public function moveDirectoryByID($media_id, $directory_id){

        $directory = Directory::where('id', $directory_id)->first();

        $this->moveDirectoryByPath($media_id, $directory->path);
    }

    public function getMeetingDirectoryID($meeting_id,$sub_folder){

        $directoryService = new DirectoryService();

        $meeting = Meeting::where('id', $meeting_id)->first();

        $meetingDirectory = $directoryService->getMeetingDirectory($meeting);

        $meetingPath = $meetingDirectory->path;
        
        switch ($sub_folder) {
            case '':
                $path = $meetingPath;
                break;
            case 'Agendas':
                $path = $meetingPath.'/Agendas';
                break;
           /* case 'Attachments':
                $path = $meetingPath.'/Attachments';
                break;*/
            case 'Actions':
                $path = $meetingPath.'/Actions';
                break;
            case 'Collections':
                $path = $meetingPath.'/Collections';
                break;
            case 'Reports':
                $path = $meetingPath.'/Reports';
                break;
            }
        return Directory::where('path', $path)->first();
    }

    public function decryptCollectionCopy($path){

        $path_array = explode('/Collections/', $path);
        $media = Media::where('path', $path_array[1])->first();
        \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
        return true;
    }

    public function decryptSubDirectoryCopy($path, $type){

        $path_array = explode('/'.$type.'/', $path);
        $media = Media::where('path', $path_array[1])->first();
        \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
        return true;
    }

    public function getFileNameWithPath($path){

        $path_array = explode( "/", $path ) ;
        return $path_array[count($path_array)-1];
    }

    public function CreateExistingFullPath($path, $directory){

        $name = $this->getFileNameWithPath($path);
        
        $disk = Storage::disk( 'local' );

        $size = $disk->size($path);
    
        
        $mimeType = $disk->mimeType($path);

        $encryptionKey = $this->generateKey(43);

        
        \SoareCostin\FileVault\Facades\FileVault::key($encryptionKey)->encryptCopy($path);

        $media = new Media;

        $media->directory_id = $directory->id;
        $media->account_id = $directory->account_id;
        $media->creator_id = $directory->creator_id;
        $media->name = $name;
        $media->path = $name;
        $media->is_created_pdf = 1;
        $media->size = $size;
        $media->encrypted_extention = '.enc';
        $media->type = $mimeType;
        $media->encryption_CODE = $encryptionKey;
        $media->status = 1;
        $media->is_public = 0;
        $media->is_garbage = 0;

        $media->save();
        
       return $media->id; 
    }

    public function CreateExisting($name, $directory){

        $path = $directory->path.'/'.$name;

        $disk = Storage::disk( 'local' );

        $size = $disk->size($path);
        
        $mimeType = $disk->mimeType($path);

        $encryptionKey = $this->generateKey(43);
        
        \SoareCostin\FileVault\Facades\FileVault::key($encryptionKey)->encryptCopy($path);

        $media = new Media;

        $media->directory_id = $directory->id;
        $media->account_id = $directory->account_id;
        $media->creator_id = $directory->creator_id;
        $media->name = $name;
        $media->path = $name;
        $media->is_created_pdf = 1;
        $media->size = $size;
        $media->encrypted_extention = '.enc';
        $media->type = $mimeType;
        $media->encryption_CODE = $encryptionKey;
        $media->status = 1;
        $media->is_public = 0;
        $media->is_garbage = 0;

        $media->save();
        
       return $media->id; 
            
    }

     public function generateKey($length){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return 'base64:'.$randomString.'=';
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
            'directory_id' => 'required',
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
    public function validateUploadRequest(Request $request)
    {
        $rules = [
            // 'file' => 'required|mimes:doc,docx,pdf,ppt,pptx,svg,txt,png,jpg,jpeg|max:50048',
            // 'file' => 'required|mimes:doc,docx,pdf,ppt,pptx,xlsx,xls,txt,png,jpg,jpeg|max:50048',
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
    public function validateMultipleUploadRequest(Request $request)
    {
        $rules = [
          //  'files[]' => 'required|mimes:doc,docx,pdf,txt,png,jpg,jpeg|max:50048',
          'files' => 'required',
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
            'end_at' => 'required'
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
            'end_at' => 'required'
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

    function checkAllow($media,$user_id,$share_type_id){

        if(!$media->is_public && $media->creator_id !=$user_id){

            foreach($media->shares as $key=>$value){
                if($value['shared_to_id'] != $user_id || $value['type_id']  != $share_type_id ){

                    return false;
                    
                }
            }
        }
            return true;
    }

    public function notifyShare($media, $type_id, $shared_to_id){

        $link = url('/medias-show/'.$media->id) ;
        
        $notificationService = new NotificationService();

        $notificationService->sendNotification(
            $shared_to_id, 
            $media->account_id , 
            $media->name , 
            $link ,
            NotificationType::MEDIA_SHARE_INITATION,
            array(),
        );
    }

    public function notifyMemberWithUploadedMedia($media)
    {
        $notificationService = new NotificationService();
        $link = url('/medias-show/' . $media->id);
        $attendees = Attendee::where('meeting_id', $media->meeting_id)->where('status', 3)->get();
        foreach ($attendees as $key => $attendee) {
            if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                $attendee_id = $attendee->delegated_to_id;
            } else {
                $attendee_id = $attendee->member_id;
            }

            $notificationService->sendNotification(
                $attendee_id,
                $media->account_id,
                $media->name,
                $link,
                NotificationType::MEETING_COLLECTION_PUBLISH,
                array()
            );
        }
    }
    
}
