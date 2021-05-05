<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\User;

use App\Models\MediaUserShare;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Services\MediaService;
use App\Services\DirectoryService;
use Illuminate\Support\Facades\Hash;
use App\Repositories\MediaRepository;
use App\Repositories\DirectoryRepository;
use App\Repositories\MediaUserShareRepository;
use IonGhitun\JwtToken\Jwt;


use Illuminate\Support\Facades\Auth;
use App\Constants\TranslationCode;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Services\CommitteeService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

/**
 * Class MediaController
 * @package App\Http\Controllers
 */

class MediaController extends Controller
{
    private $mediaRepository;
    private $mediaService;

    private $directoryRepository;

    private $mediausershareRepository;

    private $directoryService;


    public function __construct(MediaRepository $mediaRepo, DirectoryRepository $directoryRepo , MediaUserShareRepository $mediausershareRepo)
    {
        $this->mediaRepository = $mediaRepo;
        $this->mediaService = new MediaService();

        $this->directoryRepository = $directoryRepo;

        $this->mediausershareRepository = $mediausershareRepo;


        $this->directoryService = new DirectoryService();
    }

    public function toPDF(){
       
        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        $file = $storagePath."test/test.docx";
        echo "Running: 'sudo /usr/local/bin/convertPDF.sh {$file}'";
            shell_exec('sudo /usr/local/bin/convertPDF.sh ' . $file);
        die('<br/>Done');
    }

   

    

    public function mergePDF(){
        /*
        echo $storagePath;
        echo "<br/>";
        */

        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();


        $file1 = $storagePath."test/pdf1.pdf";
        $file2 = $storagePath."test/pdf2.pdf";
        $file3 = $storagePath."test/pdf3.pdf";

        echo "Running: 'sudo /usr/local/bin/mergePDF.sh {$file1} {$file2} {$file3}'";
            shell_exec('sudo /usr/local/bin/mergePDF.sh ' . $file1.' '.$file2.' '.$file3);
        die('<br/>Done');
    }

    public function convertHTMLToDOCX(){
        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        $url="https://beta.development-majles.tech/test/html";
        $docx = $storagePath."test/docxgenerated.docx";
        $command = 'sudo /usr/local/bin/htmlToDOCX.sh ' . $url.' '.$docx;

        echo "Running: ".$command;
        shell_exec($command);
        
        die('<br/>Done');
    }

    public function convertHTMLToPDF(){
        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        $url="https://beta.development-majles.tech/test/html";
        $pdf = $storagePath."test/docxgenerated.pdf";
        $command = 'sudo /usr/local/bin/htmlToPDF.sh ' . $url.' '.$pdf;

        echo "Running: ".$command;
        shell_exec($command);
        
        die('<br/>Done');
    }

     public function testHTML(){
         echo "<p>Test HTML content </p>";
     }

    /**
    * Upload file.
    * POST /medias-upload
    * @return Response
    * @bodyParam file file required The file of the Media.
    * @bodyParam directory_id int required The directory id of the Media. Example 1
    */
    public function store(Request $request)
    {
        if($request->api_token){
            $token = Jwt::validateToken($request->api_token);
            $user = User::find($token['id']);
        }else{
            $user = Auth::user();
        }

        $hasAccess = $this->mediaService->hasCreateAccess($user->id, Permission::MEDIA_CODE);
        if(!$hasAccess){
            return $this->forbiddenResponse();
        }

        $validator = $this->mediaService->validateUploadRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $request->merge(['creator_id' => $user->id]);

        $request->merge(['account_id' => $user->account_id]);

        $request->merge(['is_garbage' => Media::IS_NOT_GARBAGE]);

        $request->merge(['hash' =>Hash::make(time())]);

        if(! $request->has('directory_id')){

            $search=array('is_garbage'=>0,'account_id'=>$user->account_id);

            $garbage_directory = $this->directoryRepository->all($search)->first();
            
            $request->merge(['directory_id' => $garbage_directory->id]);
            $request->merge(['is_my_directory' =>1]);

            $request->merge(['is_garbage' => Media::IS_GARBAGE]);
        }

        if(! $request->has('is_public')){

            $request->merge(['is_public' => Media::DEFAULT_PUBLIC]);
        }
        
        $directory = $this->directoryRepository->find($request->directory_id);
        
        if (empty($directory)) {

            return $this->sendError('Directory not found');
        }

        $id=$this->mediaService->create($request, $directory);


        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if($media) {
            $this->mediaService->notifyMemberWithUploadedMedia($media);
        }

        if($request->api_token)
        return ['link'=>url() . "/view-medias-by-hash/" .$media->id.'?hash='. $request->hash,'media'=>$media];
        return $this->sendResponse($media, 'Media Uploaded successfully');
    }


    /**
    * Upload Multiple files.
    * POST /medias-multiple-upload
    * @return Response
    * @bodyParam files[] files required The files[] of the Media.
    * @bodyParam directory_id int required The directory id of the Media. Example 1
    */
    public function storeMultiple(Request $request)
    {
        $user = Auth::user();

        $hasAccess = $this->mediaService->hasCreateAccess($user->id, Permission::MEDIA_CODE);
        if(!$hasAccess){
            return $this->forbiddenResponse();
        }

        $validator = $this->mediaService->validateMultipleUploadRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $request->merge(['creator_id' => $user->id]);

        $request->merge(['account_id' => $user->account_id]);

        $request->merge(['is_garbage' => Media::IS_NOT_GARBAGE]);

        if(! $request->has('directory_id')){

            $search=array('is_garbage'=>0,'account_id'=>$user->account_id);

            $garbage_directory = $this->directoryRepository->all($search)->first();
            
            if(isset($garbage_directory->id)){
                $request->merge(['directory_id' => $garbage_directory->id]);
                $request->merge(['is_my_directory' => 1]);
            }else{
                $request->merge(['directory_id' => 1]);
            }


            $request->merge(['is_garbage' => Media::IS_GARBAGE]);
        }

        $request->merge(['is_my_directory' => 1]);

        if(! $request->has('is_public')){

            $request->merge(['is_public' => Media::DEFAULT_PUBLIC]);
        }
        
        $directory = $this->directoryRepository->find($request->directory_id);
        
        if (empty($directory)) {

            return $this->sendError('Directory not found');
        }

        $ids=$this->mediaService->createMultiple($request, $directory);

        $search = array('id'=>$ids);

        $medias = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->all(
            $search, 
            null, 
            null, 
            '*'
        );

        return $this->sendResponse($medias, 'Media Uploaded successfully');   
    }


    /**
    * Show File.
    * GET /medias-pngbyname
    * @param int $name
    * @return Response
    * @urlParam id required The name of the Media.
    */
    public function pngbyname(Request $request)
    {
        $input = $request->all();
        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
        $path = $input['path'];
        $headers = ['Content-Type' => 'image/png'];
        return response()->download($storagePath.$path, 'File', $headers, 'inline');

    }
    /**
    * Show File.
    * GET /medias-logo
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Media.
    */
    public function logo($id)
    {
        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if(empty($media) || !$media->is_account_logo){
                return $this->sendError('Media not found');
        }
        
        if($media->encrypted_extention){
            if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path) && false){
                Storage::disk('local')->delete($media->directory['path']."/".$media ->path);
            }

            if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path.$media->encrypted_extention) && false){
                \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
            }
            
        }

        
        if(!$media->is_created_pdf){
            
            $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
            $path = $media->directory['path']."/".$media ->path ;
            $headers = ['Content-Type' => $media->type];
            return response()->download($storagePath.$path, 'File', $headers, 'inline');
            
        
        }else{
            $path = $media->directory['path']."/".$media ->path ;
            
            $contents = Storage::disk('local')->get($path);

           // Storage::disk('local')->delete($media->directory['path']."/".$media ->path);
           
            header("Content-type: ".$media->type);
        
            echo  $contents;
        }
    
        
    }
    /**
    * Show File.
    * GET /medias-show
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Media.
    */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

            if(empty($media) || !$media->is_account_logo){
                if (empty($media) || $media->account_id != $user->account_id) {
                    return $this->sendError('Media not found');
                }
            }


            if($media->encrypted_extention){
                if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path) && false){
                    Storage::disk('local')->delete($media->directory['path']."/".$media ->path);
                }

                if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path.$media->encrypted_extention) && false){
                    \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
                }

            }


            if(!$media->is_created_pdf){

                $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
                $path = $media->directory['path']."/".$media ->path ;
                $headers = ['Content-Type' => $media->type];
                return response()->download($storagePath.$path, 'File', $headers, 'inline');


            }else{
                $path = $media->directory['path']."/".$media ->path ;

                $contents = Storage::disk('local')->get($path);

                // Storage::disk('local')->delete($media->directory['path']."/".$media ->path);

                header("Content-type: ".$media->type);

                echo  $contents;
            }
        } catch (\Exception $e) {
            return $this->sendError('Media not found');
        }
    }

        /**
    * Show File.
    * GET /view-medias-by-hash
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Media.
    */
    public function showByHash($id,Request $request)
    {
        try {

            if(!$request->hash)
                return $this->sendError('Media not found');
            $media = Media::with($this->mediaRepository->getRelations('item'))->where('id',$id)
            ->where('hash',$request->hash)->first();
            if(empty($media)){
                    return $this->sendError('Media not found');
            }


            if($media->encrypted_extention){
                if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path) && false){
                    Storage::disk('local')->delete($media->directory['path']."/".$media ->path);
                }

                if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path.$media->encrypted_extention) && false){
                    \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
                }

            }


            if(!$media->is_created_pdf){

                $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
                $path = $media->directory['path']."/".$media ->path ;
                $headers = ['Content-Type' => $media->type];
                return response()->download($storagePath.$path, $media ->path , $headers, 'inline');


            }else{
                $path = $media->directory['path']."/".$media ->path ;

                $contents = Storage::disk('local')->get($path);

                // Storage::disk('local')->delete($media->directory['path']."/".$media ->path);

                header("Content-type: ".$media->type);

                echo  $contents;
            }
        } catch (\Exception $e) {
            return $this->sendError('Media not found');
        }
    }
    /**
     * Show File.
     * GET /medias-view
     * @param int $id
     * @return Response
     * @urlParam id required The ID of the Media.
     */
    public function viewMedia(int $id, Request $request)
    {
        $user = User::find($request->user_id);

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if(empty($media) || !$media->is_account_logo){
            if (empty($media) || $media->account_id != $user->account_id) {
                return $this->sendError('Media not found');
            }
        }


        if($media->encrypted_extention){
            if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path) && false){
                Storage::disk('local')->delete($media->directory['path']."/".$media ->path);
            }

            if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path.$media->encrypted_extention) && false){
                \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
            }

        }


        if(!$media->is_created_pdf){

            $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
            $path = $media->directory['path']."/".$media ->path ;
            $headers = ['Content-Type' => $media->type];
            return response()->download($storagePath.$path, 'File', $headers, 'inline');


        }else{
            $path = $media->directory['path']."/".$media ->path ;

            $contents = Storage::disk('local')->get($path);

            // Storage::disk('local')->delete($media->directory['path']."/".$media ->path);

            header("Content-type: ".$media->type);

            echo  $contents;
        }

    }

    public function viewMediaPdf(int $id, Request $request)
    {
        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if (!$media) {
            return $this->sendError('Media not found');
        }


        if ($media->encrypted_extention) {
            if (Storage::disk('local')->exists($media->directory['path'] . "/" . $media->path) && false) {
                Storage::disk('local')->delete($media->directory['path'] . "/" . $media->path);
            }
            if (Storage::disk('local')->exists($media->directory['path'] . "/" . $media->path . $media->encrypted_extention) && false) {
                \SoareCostin\FileVault\Facades\FileVault::key($media->encryption_CODE)->decryptCopy($media->directory['path'] . "/" . $media->path . $media->encrypted_extention);
            }
        }


        if (!$media->is_created_pdf) {
            $storagePath = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
            $path = $media->directory['path'] . "/" . $media->path;
            $headers = ['Content-Type' => $media->type];
            return response()->download($storagePath . $path, 'File', $headers, 'inline');

        } else {
            $path = $media->directory['path'] . "/" . $media->path;
            $contents = Storage::disk('local')->get($path);
            header("Content-type: " . $media->type);
            echo $contents;
        }
    }

    public function viewVotingCardPdf(int $id, Request $request)
    {
        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if (!$media) {
            return $this->sendError('Media not found');
        }
        
        $storagePath = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
        $filePath = $media->directory['path'] . "/" . $media->path;
        $path = $storagePath . $media->directory['path'] . "/" . $media->path;
       
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }



    public function show____($id)
    {
        $user = Auth::user();

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if (empty($media)) {

            return $this->sendError('Media not found');
        }
        

        if($media->encrypted_extention){
            if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path)){
                Storage::disk('local')->delete($media->directory['path']."/".$media ->path);
            }

            if(Storage::disk('local')->exists($media->directory['path']."/".$media ->path.$media->encrypted_extention)){
                \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
            }
            
        }
    
        $path = $media->directory['path']."/".$media ->path ;

        if($media->type=="application/pdfd"){
            
                $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
                $headers = ['Content-Type' => 'application/pdf'];
                return response()->download($storagePath.$path, 'File', $headers, 'inline');
                /*
                $name = 'download.pdf';
                $content = Storage::disk('local')->get($path);
                header('Content-Type: application/pdf');
                header('Content-Length: '.strlen( $content ));
                header('Content-disposition: inline; filename="' . $name . '"');
                header('Cache-Control: public, must-revalidate, max-age=0');
                header('Pragma: public');
                header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                echo $content;
                */
            
        }else{
            $contents = Storage::disk('local')->get($path);

            Storage::disk('local')->delete($media->directory['path']."/".$media ->path);

            echo $media ->type;

            header("Content-type: ".$media->type);
        
            echo  $contents;
        }
        
    }

    public function showold($id)
    {
        $user = Auth::user();

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if (empty($media)) {

            return $this->sendError('Media not found');
        }
        /*
        $hasAccess = $this->mediaService->hasReadAccess($user->id, $media->creator_id, Permission::MEDIA_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }
        
        if(!$this->mediaService->checkAllow($media,$user->id,Media::SHARE_TYPE_SHOW)){

            return $this->sendError('Media not Allowed found');
            
        }
        */

        print_r($media);die();
        \SoareCostin\FileVault\Facades\FileVault::key($media ->encryption_CODE)->decryptCopy($media->directory['path']."/".$media ->path.$media->encrypted_extention);
    
        $contents = Storage::disk('local')->response($media->directory['path']."/".$media ->path);

        Storage::disk('local')->delete($media->directory['path']."/".$media ->path);
        
        header("Content-type: ".$media->type);

        echo $contents;
    }

    /**
    * Move File.
    * POST /medias-move
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Media.
    * @bodyParam directory_id int required The directory id. Example 1
    */
    public function move($id ,Request $request){

        $user = Auth::user();

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if (empty($media)) {
            return $this->sendError('Media not found');
        }

        $hasAccess = $this->mediaService->hasUpdateAccess($user->id, $media->creator_id, Permission::MEDIA_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $validator = $this->mediaService->validateMoveRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $directory = $this->directoryRepository->find($input['directory_id']);

        if (empty($directory)) {
            return $this->sendError('Directory not found');
        }

        $current_path = $media->directory->path."/".$media->path.$media->encrypted_extention;

        $new_path = $directory->path."/".$media->path.$media->encrypted_extention;

        if($new_path != $current_path ){
            
            Storage::disk('local')->move($current_path, $new_path);

            $media = $this->mediaRepository->update($input, $id);
            
        }

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        return $this->sendResponse($media, 'Media Moved successfully');
        
    }

    /**
    * Rename File.
    * POST /medias-rename
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Media.
    * @bodyParam name string required The name of media. Example test
    */
    public function rename($id ,Request $request){

        $user = Auth::user();

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if (empty($media)) {
            return $this->sendError('Media not found');
        }

        $hasAccess = $this->mediaService->hasUpdateAccess($user->id, $media->creator_id, Permission::MEDIA_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $validator = $this->mediaService->validateRenameRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $input['path'] = $input['name'];

        $current_path = $media->directory['path'].'/'.$media->path.$media->encrypted_extention;

        $new_path = $media->directory['path'].'/'.$input['name'].$media->encrypted_extention;


        $file_exists = Storage::disk('local')->exists($new_path);
        
        if($file_exists){
            return $this->sendError('File with same name exists');
        }

        if($current_path !== $new_path){
            Storage::disk('local')->move($current_path, $new_path);
        }
        

        $media = $this->mediaRepository->update($input, $id);

        return $this->sendResponse($media, 'Media Moved successfully');
        
    }

    /**
    * Share File.
    * POST /medias-share
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Media.
    * @bodyParam type_id int required The Type id. Example 1
    * @bodyParam shared_to_id int required The User id to share. Example 1
    */
    function share($id ,Request $request){

        $user = Auth::user();

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if (empty($media)) {
            return $this->sendError('Media not found');
        }

        $hasAccess = $this->mediaService->hasUpdateAccess($user->id, $media->creator_id, Permission::MEDIA_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $validator = $this->mediaService->validateShareRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        if($media->creator_id  != $user->id) {
            return $this->sendError('You do not have permission to share this directory');
        }

        $input = $request->all();

        $input['is_public']=Media::DEFAULT_NOT_PUBLIC;

        $media = $this->mediaRepository->update($input, $id);

        $input['media_id'] = $media->id;

        $input['creator_id'] = $user->id;

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        MediaUserShare::where(['media_id' => $media->id])->delete();
        $shares = explode(',' , $input['shared_to_id']);
        foreach($shares as $share) {
            $input['shared_to_id'] = $share;
            $this->mediausershareRepository->create($input);
            $this->mediaService->notifyShare($media, $input['type_id'], $input['shared_to_id']);
        }

        return $this->sendResponse($media, 'Media Shared successfully');

    }


    /**
    * Share Remove File.
    * POST /medias-share-remove
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Media.
    * @bodyParam shared_to_id int required The User id to share. Example 1
    */
    function removeShare($id ,Request $request){

        $user = Auth::user();

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if (empty($media)) {

            return $this->sendError('Media not found');

        }

        $hasAccess = $this->mediaService->hasUpdateAccess($user->id, $media->creator_id, Permission::MEDIA_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $validator = $this->mediaService->validateRemoveShareRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $this->mediaService->deleteManyToManyRelations('medias_users_shares','media_id',$id,'shared_to_id',$input['shared_to_id']);
    
        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        return $this->sendResponse($media, 'Media Shared Removed successfully');

    }

    /**
    * Share Update File.
    * POST /medias-share-update
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Media.
    * @bodyParam type_id int required The Type id . Example 1
    * @bodyParam shared_to_id int required The User id to share. Example 1
    */
    function updateShare($id ,Request $request){


        $user = Auth::user();

        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        if (empty($media)) {

            return $this->sendError('Media not found');

        }

        $hasAccess = $this->mediaService->hasUpdateAccess($user->id, $media->creator_id, Permission::MEDIA_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $validator = $this->mediaService->validateRemoveShareRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();
        
        $this->mediaService->deleteManyToManyRelations('medias_users_shares','media_id',$id,'shared_to_id',$input['shared_to_id']);

        $input['is_public']=Media::DEFAULT_NOT_PUBLIC;
        
        $media = $this->mediaRepository->update($input, $id);

        $input['media_id'] = $media->id;

        $input['creator_id'] = $user->id;

        $this->mediausershareRepository->create($input);
    
        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);

        return $this->sendResponse($media, 'Media Shared successfully');

    }

    /**
    * Delete Media Details
    * Delete /medias/{id}
    * @param int $id
    * @return Response
    */
    public function destroy($id)
    {
        $user = Auth::user();
        $media = $this->mediaRepository->with($this->mediaRepository->getRelations('item'))->find($id);
        $deletedMedia = $media;
        if (empty($media)) {
            return $this->sendError('Media not found');
        }

        $hasAccess = $this->mediaService->hasDeleteAccess($user->id, $media->creator_id, Permission::MEDIA_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        if($media->delete()) {
            $this->mediaService->notifyMemberWithUploadedMedia($deletedMedia);
        }

        return $this->sendResponse('Media deleted successfully');
    }
}
