<?php

namespace App\Services;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Directory;
use App\Models\Media;
use App\Models\CommitteeMember;
use App\Models\Attendee;
use App\Models\Language;
use App\Models\Meeting;
use App\Models\NotificationType;
use App\Models\User;
use App\Services\MediaService;
use App\Services\DirectoryService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str ;
use Illuminate\Support\Facades\Log;
use NcJoes\OfficeConverter\OfficeConverter;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;


/**
 * Class PDFService
 *
 * @package App\Services
 */
class PDFService
{



    public function getPdfFromHtml(string $html, string $path, $attachments = null): void
    {
        try {
            $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];

            $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];

            $mpdf = new \Mpdf\Mpdf([
                'tempDir' => storage_path('app/public') .'/tmp',
                'fontDir' => array_merge($fontDirs, [
                    storage_path() . '/mpdf/fonts',
                ]),
                'fontdata' => $fontData + [
                        'tajawal' => [
                            'R' => 'Tajawal-Regular.ttf',
                            'B' => 'Tajawal-Bold.ttf',
                            'useOTL' => 0xFF,
                            'useKashida' => 75,
                        ]
                    ],
                'default_font' => 'tajawal'
            ]);


            $mpdf->WriteHTML($html);

            if ($attachments) {
                foreach ($attachments as $attachment) {
                    if($attachment['type'] != 'image') {
                        $attachmentPath = null;
                        if(in_array($attachment['type'], ['word', 'ppt', 'excel'])) {
                            $attachmentPath = $this->convertOfficeToPdf($attachment['path'], $attachment['name']);
                        } else {
                            $originPath =  $attachment['path'] . '/' . $attachment['name'];
                            $newPath = $attachment['path'] . '/' . 'new_version_'.$attachment['name'];
                            $this->changePdfVersion($originPath, $newPath, 1.7);
                            $attachmentPath = $newPath;
                        }
                        if($attachmentPath) {
                            $pageCount = $mpdf->SetSourceFile($attachmentPath);
                            $mpdf->AddPage();
                            for ($i = 1; $i <= $pageCount; $i++) {
                                $tplId = $mpdf->ImportPage($i);

                                $mpdf->UseTemplate($tplId);
                                if ($i < $pageCount)
                                    $mpdf->AddPage();
                            }
                            if(in_array($attachment['type'], ['word', 'ppt', 'excel'])) {
                                unlink($attachmentPath);
                            }
                        }
                    }
                }
            }

            $mpdf->Output($path, 'F');
        } catch (Exception $e) {
            Log::info('Failed to get pdf');
        }
    }


    public function getMeetingAsPdf(Meeting $meeting): int
    {
        try {
            $directoryService = new DirectoryService();
            $mediaService = new MediaService();

            $meetingDirectory = $directoryService->getMeetingDirectory($meeting);
            $meetingPath = storage_path('app/public/').$meetingDirectory->path;

            $parentPath =  $meetingDirectory->path.'/Collection';
            $parentDirectory = $directoryService->getDirectoryByPath($parentPath);

            $attachments = [];
            $types = [
                'application/pdf' => 'pdf',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'ppt',
                'application/vnd.ms-powerpoint' => 'ppt',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
                'application/msword' => 'word'
            ];

            // foreach ($meeting->attachments as $attachment) {
            //     if($attachment->media) {
            //         $mediaDirectory = $directoryService->getDirectoryById($attachment->media->directory_id);
            //         $mediaPath = storage_path('app/public/').$mediaDirectory->path;

            //         $attachments[] = [
            //             'path' => $mediaPath,
            //             'name' => $attachment->media->name,
            //             'type' => isset($types[$attachment->media->type]) ? $types[$attachment->media->type] : 'image'
            //         ];
            //     }
            // }

            // foreach ($meeting->agendas as $agenda) {
            //     foreach ($agenda->attachments as $attachment) {
            //         if($attachment->media) {
            //             $mediaDirectory = $directoryService->getDirectoryById($attachment->media->directory_id);
            //             $mediaPath = storage_path('app/public/').$mediaDirectory->path;

            //             $attachments[] = [
            //                 'path' => $mediaPath,
            //                 'name' => $attachment->media->name,
            //                 'type' => isset($types[$attachment->media->type]) ? $types[$attachment->media->type] : 'image'
            //             ];
            //         }
            //     }
            // }

            $meetingFormatName = preg_replace("/\s+/", "-", $meeting->title);

            $collectionHtml = view('pdf.meeting.index')->with(['meeting' => $meeting, 'attachments' => $attachments]);

            $this->getPdfFromHtml($collectionHtml, $meetingPath.'/Collection/'.$meetingFormatName.'_collection.pdf', $attachments);
            $filePath = $meetingDirectory->path.'/Collection/'.$meetingFormatName.'_collection.pdf';

            $disk = Storage::disk( 'local' );
            $size = $disk->size($filePath);
            $mimeType = $disk->mimeType($filePath);
            $encryptionKey = $mediaService->generateKey(43);

            \SoareCostin\FileVault\Facades\FileVault::key($encryptionKey)->encryptCopy($filePath);

            $media = new Media;
            $media->title = $meeting->title;
            $media->directory_id = $parentDirectory->id ?? null;
            $media->account_id = $meeting->account_id;
            $media->creator_id = $meeting->creator_id;
            $media->is_created_pdf = 1;
            $media->name = $meeting->title;
            $media->path = $meetingFormatName.'_collection.pdf';
            $media->size = $size;
            $media->encrypted_extention = '.enc';
            $media->type = $mimeType;
            $media->encryption_CODE = $encryptionKey;
            $media->status = 1;
            $media->is_public = 0;
            $media->is_garbage = 0;

            $media->save();

            return $media->id;

        } catch (Exception $e) {
            Log::info('Failed to get pdf for meeting ' . $meeting->id);
        }
    }

    public function convertDocToPdf(string $path, string $name): ?string
    {
        try {
            chmod($path, 0777);
            $domPdfPath = base_path('vendor/dompdf/dompdf');
            \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
            \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
            $Content = \PhpOffice\PhpWord\IOFactory::load($path . '/' . $name);
            $newName = time() . '_teamPdf.pdf';
            $pdf = \PhpOffice\PhpWord\IOFactory::createWriter($Content, 'PDF');

            $pdf->save($path . '/' . $newName);

            return $path . '/' . $newName;
        } catch (Exception $e) {
            Log::info('Can not convert file to pdf');
            return null;
        }
    }

    public function convertOfficeToPdf(string $path, string $name): ?string
    {
        try {
            $realPath = pathinfo($path . '/' . $name);
            $newName = $realPath['filename'].'.pdf';
            $converter = new OfficeConverter($path . '/' . $name);
           
            $converter->convertTo($newName);

            return $path . '/' . $newName;
        } catch (Exception $e) {
            Log::info('Can not convert file to pdf');
            return null;
        }
    }

    public function getMeetingReportAsPdf(Meeting $meeting): int
    {
        try {
            $directoryService = new DirectoryService();
            $mediaService = new MediaService();
            $collectionHtml = view('pdf.meeting.meeting-report')->with(['meeting' => $meeting]);
            $meetingDirectory = $directoryService->getMeetingDirectory($meeting);
            $meetingPath = storage_path('app/public/').$meetingDirectory->path;
            
            $parentPath =  $meetingDirectory->path.'/Report';
            $parentDirectory = $directoryService->getDirectoryByPath($parentPath);

            $fileName = $meeting->title.'_'.time().'.pdf';
            $this->getPdfFromHtml($collectionHtml, $meetingPath.'/Report/'.$fileName);
            $filePath = $meetingDirectory->path.'/Report/'.$fileName;

            $disk = Storage::disk( 'local' );
            $size = $disk->size($filePath);
            $mimeType = $disk->mimeType($filePath);
            $encryptionKey = $mediaService->generateKey(43);

            \SoareCostin\FileVault\Facades\FileVault::key($encryptionKey)->encryptCopy($filePath);

            $media = new Media;
            $media->title = $meeting->title;
            $media->directory_id = $parentDirectory->id ?? null;
            $media->account_id = $meeting->account_id;
            $media->creator_id = $meeting->creator_id;
            $media->is_created_pdf = 1;
            $media->name = $meeting->title;
            $media->path = $fileName;
            $media->size = $size;
            $media->encrypted_extention = '.enc';
            $media->type = $mimeType;
            $media->encryption_CODE = $encryptionKey;
            $media->status = 1;
            $media->is_public = 0;
            $media->is_garbage = 0;

            $media->save();

            return $media->id;

        } catch (Exception $e) {
            Log::info('Failed to get pdf for meeting ' . $meeting->id);
        }
    }

    public function getMeetingReportAsDocx(Meeting $meeting): int
    {
        try {
            $user = Auth::user();
            $account = $user->account;
            $directoryService = new DirectoryService();
            $mediaService = new MediaService();
            $bgColor = $account->bg_color ?? '#4188f7';
            $collectionHtml = view('doc.meeting.meeting-report')->with(['meeting' => $meeting, 'bgColor' => $bgColor]);
            $meetingDirectory = $directoryService->getMeetingDirectory($meeting);
            $meetingPath = storage_path('app/public/').$meetingDirectory->path;
            
            $parentPath =  $meetingDirectory->path.'/Report';
            $parentDirectory = $directoryService->getDirectoryByPath($parentPath);

            $fileName = $meeting->title.'_'.time().'.docx';
            $this->getDocxFromHtml($meeting, $collectionHtml, $meetingPath.'/Report/'.$fileName);
            $filePath = $meetingDirectory->path.'/Report/'.$fileName;

            $disk = Storage::disk( 'local' );
            $size = $disk->size($filePath);
            $mimeType = $disk->mimeType($filePath);
            $encryptionKey = $mediaService->generateKey(43);

            \SoareCostin\FileVault\Facades\FileVault::key($encryptionKey)->encryptCopy($filePath);

            $media = new Media;
            $media->title = $meeting->title;
            $media->directory_id = $parentDirectory->id ?? null;
            $media->account_id = $meeting->account_id;
            $media->creator_id = $meeting->creator_id;
            $media->name = $meeting->title;
            $media->path = $fileName;
            $media->size = $size;
            $media->encrypted_extention = '.enc';
            $media->type = $mimeType;
            $media->encryption_CODE = $encryptionKey;
            $media->status = 1;
            $media->is_public = 0;
            $media->is_garbage = 0;

            $media->save();

            return $media->id;

        } catch (Exception $e) {
            Log::info('Failed to get docx for meeting ' . $meeting->id);
        }
    }

    public function getDocxFromHtml($meeting, $content, $path): void
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $header = $section->addHeader();
        $header->addImage(
            optional($meeting->account)->logo_url,
            array(
                'width'         =>  200,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT
            )
        );
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $content, false, false);
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($path, "Word2007");
    }

    public function getVotingCardAsPdf(Meeting $meeting): int
    {
        $user = Auth::user();
        try {
            $member = CommitteeMember::where('member_id', $user->id)->where('committee_id', $meeting->committee_id)->first();
            $committeeShares = 	$meeting->committee->shares;
            $memberShares = 	$member->shares ?? 0;
            $directoryService = new DirectoryService();
            $mediaService = new MediaService();
            $actionsIds = $meeting->actions->pluck('id')->toArray();
            $collectionHtml = view('pdf.meeting.voting-card')->with([
                    'meeting' => $meeting, 
                    'user' => $user,
                    'memberShares' => $memberShares,
                    'committeeShares' => $committeeShares
                ]);
            $meetingDirectory = $directoryService->getMeetingDirectory($meeting);
            $meetingPath = storage_path('app/public/').$meetingDirectory->path;
            
            $parentPath =  $meetingDirectory->path.'/votingCards';
            $parentDirectory = $directoryService->getDirectoryByPath($parentPath);

            $fileName = $user->id.'.pdf';
            $this->getPdfFromHtml($collectionHtml, $meetingPath.'/votingCards/'.$fileName);
            
            if(file_exists(app()->basePath('public/uploads/votingCards/'.$fileName))) {
                unlink(app()->basePath('public/uploads/votingCards/'.$fileName));
            }

            $this->getPdfFromHtml($collectionHtml, app()->basePath('public/uploads/votingCards/'.$fileName));
            $filePath = $meetingDirectory->path.'/votingCards/'.$fileName;

            $disk = Storage::disk( 'local' );
            $size = $disk->size($filePath);
            $mimeType = $disk->mimeType($filePath);
            $encryptionKey = $mediaService->generateKey(43);

            \SoareCostin\FileVault\Facades\FileVault::key($encryptionKey)->encryptCopy($filePath);

            $media = new Media;
            $media->title = $meeting->title;
            $media->directory_id = $parentDirectory->id ?? null;
            $media->account_id = $meeting->account_id;
            $media->creator_id = $meeting->creator_id;
            $media->is_created_pdf = 1;
            $media->name = 'بطاقة التصويت';
            $media->path = $fileName;
            $media->size = $size;
            $media->encrypted_extention = '.enc';
            $media->type = $mimeType;
            $media->encryption_CODE = $encryptionKey;
            $media->status = 1;
            $media->is_public = 0;
            $media->is_garbage = 0;

            $media->save();

            Attendee::where(['member_id' => $user->id, 'meeting_id' => $meeting->id])->update(['voting_card_id' => $media->id]);

            $this->sendVotingCard($user->id, $meeting, $fileName);

            return $media->id;

        } catch (Exception $e) {
            Log::info('Failed to get pdf for meeting ' . $meeting->id);
        }
    }

    public function sendVotingCard($userId, $meeting, $fileName){

        $user = User::find($userId);
        $languageId = $user->language_id ?? $this->getLangIdFromLocale();
        $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
        Lang::setLocale($languageCode);

        $notificationService = new NotificationService();
        
        $link = url('/meetings/association/'.$meeting->id);

        $emailLink = '';

        if(request()->link) {
            $emailLink = request()->link . '/meetings/association/' . $meeting->id;
        }

        $file = url('uploads/votingCards/'.$fileName);


        $notificationService->sendNotification(
            $userId, 
            $meeting->account_id , 
            $meeting->title , 
            $link ,
            NotificationType::SEND_VOTING_CARD,
            array(),
            $emailLink,
            __('Go to Meeting'),
            $file
        );
    }

    public function fileFromContent($htmlURL, $meeting, $account, $type, $extention){
        
        $directoryService = new DirectoryService();

        $mediaService = new MediaService();

        $directory = $directoryService->getMeetingSubDirectory($meeting, $type);
        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
        
        $pos = strpos(url("/"), 'http://localhost:8888');
        if ($pos === false) {

            $name = time().'-'.$meeting->id.'-'.$type.'.'.$extention;

        }else{
            $name ="localhost.".$extention;
        }

        if(isset($directory->path)){
            $path = $directory->path.'/'.$name;
            if($extention == "pdf"){
                $this->htmlToPDF($htmlURL ,$path);
            }elseif($extention == "docx"){
                $this->htmlToDOCX($htmlURL,$path);
            }
            return $mediaService->CreateExisting($name, $directory);
        }else{
            return 0;
        }
    }

    public function createPDFfromMettingDetails($html, $meeting, $account, $type){
/*
        
        $directoryService = new DirectoryService();

        $mediaService = new MediaService();

        $directory = $directoryService->getAccountSubDirectory($account, $type);
        
        $name = time().'-'.$meeting->id.'-'.$type.'.pdf';

        $path = $directory->path.'/'.$name;

        if(isset($directory->path)){

            $this->htmlToPDF($path, $name, $html);
            return $mediaService->CreateExisting($name, $directory);
        }else{
            return 0;
        }
       */ 
    }

    public function createDOCXfromMettingDetails($html, $meeting, $account, $type){

        /*
        $directoryService = new DirectoryService();

        $mediaService = new MediaService();

        $directory = $directoryService->getAccountSubDirectory($account, $type);
        
        $name = time().'-'.$meeting->id.'-'.$type.'.docx';

        $path = $directory->path.'/'.$name;

        if(isset($directory->path)){

            $this->htmlToDOCX($path, $name, $html);
            
            $media_id = $mediaService->CreateExisting($name, $directory);
            
            return $media_id;
        }else{
            return 0;
        }
        */
    }

    public function convertToPDF($path){

        $pos = strpos(strtolower($path), '.pdf');
            if ($pos === false) {
                $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
                $file = $storagePath.$path;
                shell_exec('sudo /usr/local/bin/convertPDF.sh ' . $file);
                return $this->renameExtension($path, 'pdf');
            }else{
                return $path;
            }
        
    }

    public function htmlToPDF($url ,$path){

        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
        $pdf = $storagePath.$path;
        $command = 'sudo /usr/local/bin/htmlToPDF.sh ' . $url.' '.$pdf;
        
        shell_exec($command);

    }

    public function htmlToDOCX($url ,$path){

        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();
        $pdf = $storagePath.$path;
        $command = 'sudo /usr/local/bin/htmlToDOCX.sh ' . $url.' '.$pdf;
        shell_exec($command);
    }

    public function mergePDFs($filesArray, $directoryArray){

        $filesArray = array_values(array_unique($filesArray));
        $directoryService = new DirectoryService();
        $mediaService = new MediaService();
        /*
        $output=$filesArray[0];
        for($i=1;$i < count($filesArray)-1; $i++){
         $output = $this->mergeTwoPDF($output, $filesArray[$i]);
        }*/
       // $output = $filesArray[0];
       // echo $output;echo "====";
        $output = $this->mergeMultibleTwoPDF($filesArray);
       // echo $output;
        //$output = $this->mergeTwoPDF($output, $filesArray[2]);
        //$output = $this->mergeTwoPDF($output, $filesArray[3]);
        //$output = $this->mergeTwoPDF($output, $filesArray[4]);
        //$output = $this->mergeTwoPDF($output, $filesArray[5]);
       // echo $output;echo "====";
       // die();
        return $mediaService->CreateExistingFullPath($output, $directoryArray[0]);
    }
    public function mergeMultibleTwoPDF($paths){
        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        //TODO add loop on the $pathsarray
        $output = $this->getPathWithoutName($paths[0]);
        $output = $output.time().".pdf";
        $files="";
        foreach($paths as $path){
            $path = $this->convertToPDF($path);
            $file=$storagePath.$path;
            $files=$files.' '.$file;
        }
        $command = 'sudo /usr/local/bin/mergePDF.sh ' . $files.' '.$storagePath.$output;

      //  echo $command;die();
        shell_exec($command);
        $pos = strpos(url("/"), 'http://localhost:8888');
        if ($pos === false) {
            return str_replace($storagePath,"",$output);
        }else{
            return $paths[0];
        }
    }

    public function mergeTwoPDF($path1, $path2){

        $path1 = $this->convertToPDF($path1);

        $path2 = $this->convertToPDF($path2);

        $storagePath  = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        $file1=$storagePath.$path1;
        $file2=$storagePath.$path2;

        $output = $this->getPathWithoutName($file1);

        $output = $output.time().".pdf";
        
        $command = 'sudo /usr/local/bin/mergePDF.sh ' . $file1.' '.$file2.' '.$output;

        
        shell_exec($command);
        $pos = strpos(url("/"), 'http://localhost:8888');
        if ($pos === false) {
            return str_replace($storagePath,"",$output);
        }else{
            return $path1;
        }
        
        
    }

    public function checkExists($path){

        return $path;
        
    }

    public function getFileName($path){

        $path_array = explode( "/", $path ) ;
        $current_full_name = $path_array[count($path_array)-1];

        $path_array = explode( ".", $path ) ;
        $current_extention = $path_array[count($path_array)-1];
        return str_replace(".".$current_extention,"",$current_full_name);
    }

    public function getFileNameWithPath($path){

        $path_array = explode( "/", $path ) ;
        return $path_array[count($path_array)-1];
    }

    public function getPathWithoutName($path){

        $path_array = explode( "/", $path ) ;
        $current_name = $path_array[count($path_array)-1];
        return str_replace($current_name,'',$path);
    }

    public function renameExtension($path, $extension){
        $path_array = explode( ".", $path ) ;
        $current_extention = $path_array[count($path_array)-1];
        return str_replace($current_extention,$extension,$path);
    }

    public function changePdfVersion($originalFile, $newFile, $newVersion)
    {
        try {
            $baseCommand = 'gs -sDEVICE=pdfwrite -dCompatibilityLevel=%s -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -dColorConversionStrategy=/LeaveColorUnchanged -dEncodeColorImages=false -dEncodeGrayImages=false -dEncodeMonoImages=false -dDownsampleMonoImages=false -dDownsampleGrayImages=false -dDownsampleColorImages=false -dAutoFilterColorImages=false -dAutoFilterGrayImages=false -dColorImageFilter=/FlateEncode -dGrayImageFilter=/FlateEncode  -sOutputFile=%s %s';
            $command = sprintf($baseCommand, $newVersion, $newFile, escapeshellarg($originalFile));
            $process =exec($command);
        } catch (Exception $e) {
            Log::info('Can not convert pdf version');
        }
    }   
}
