<?php

namespace App\Http\Controllers;

use App\Models\Annotation;
use App\Repositories\AnnotationRepository;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Constants\TranslationCode;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Services\AnnotationService;


use Response;

/**
 * Class AnnotationController
 * @package App\Http\Controllers
 */

class AnnotationController extends Controller
{
    private $annotationRepository;
    private $annotationService;


    public function __construct(AnnotationRepository $annotationRepo)
    {
        $this->annotationRepository = $annotationRepo;


        $this->annotationService = new AnnotationService() ;
    }

    /**
    * Show Annotation list
    * GET /annotations
    * @return Response
    */
    public function index(Request $request)
    {
        $annotations = $this->annotationRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse($annotations->toArray(), 'Annotations retrieved successfully');
    }

    /**
    * Post Annotation.
    * POST /annotations
    * @return Response
    * @bodyParam meeting_id int required The  ID of the Meeting. Example: 1
    * @bodyParam collection_id int required The  ID of the Collection. Example: 1
    * @bodyParam report_id int required The  ID of the Report. Example: 1
    * @bodyParam share_with string required The share_with  of the Annotation. Example: 1,2,3
    * @bodyParam content text required The  content of the Annotation. Example:  content
    */
    public function store(Request $request)
    {
        $user = Auth::user();

       $validator = $this->annotationService->validateCreateAnnotationRequest($request);

       if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
       }

        $input = $request->all();
        
        $input['creator_id']=$user->id;
        
        if($request->has('share_with')){
            $input['share_with']=','.$input['share_with'].','; 
        }
        

        $annotation = $this->annotationRepository->create($input);

    //     if(isset($input['meeting_id']))
    //    $this->annotationService->notifySecretary($annotation);

       $this->annotationService->notifyShareWith($annotation);

        $annotation = $this->annotationRepository->find($annotation->id);

        return $this->sendResponse($annotation, 'Annotation Added successfully');
    }

    /**
    * Post Multible Annotation.
    * POST /multible-annotations
    * @return Response
    * @bodyParam annotations[0][meeting_id] int required The  ID of the Meeting. Example: 1
    * @bodyParam annotations[0][collection_id] int required The  ID of the Collection. Example: 1
    * @bodyParam annotations[0][report_id] int required The  ID of the Report. Example: 1
    * @bodyParam annotations[0][share_with] string required The share_with  of the Annotation. Example: 1,2,3
    * @bodyParam annotations[0][content] text required The  content of the Annotation. Example:  content
    */
    public function storeMultible(Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        if(isset($input['annotations']) && !empty($input['annotations'])){
            foreach($input['annotations'] as $key => $annotationInput){

                $validator = $this->annotationService->validateCreateMultibleAnnotationRequest($request, $key);

                if (!$validator->passes()) {
                return $this->userErrorResponse($validator->messages()->toArray());
                }

                $annotationInput['creator_id'] = $user->id;
                if(!empty($annotationInput['share_with'])){
                    $annotationInput['share_with'] = ','.$annotationInput['share_with'].','; 
                }

                $annotation = $this->annotationRepository->create($annotationInput);

                $this->annotationService->notifySecretary($annotation);

                $this->annotationService->notifyShareWith($annotation);

                $annotation = $this->annotationRepository->find($annotation->id);
            }
        }

        return $this->sendResponse($annotation, 'Annotations Added successfully');
    }

    /**
    * Show the specified Annotation.
    * GET /annotations/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Annotation.
    */
    public function show($id)
    {
        $user = Auth::user();

        $annotation = $this->annotationRepository->find($id);

        if (empty($annotation)) {
            return $this->sendError('Annotation not found');
        }

        return $this->sendResponse($annotation->toArray(), 'Annotation retrieved successfully');
    }

    /**
    * Update the specified Annotation.
    * PUT/PATCH /annotations/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Annotation.
    * @bodyParam share_with string required The share_with  of the Annotation. Example: 1,2,3
    * @bodyParam content text required The  content of the Annotation. Example:  content
    */
    public function update($id, Request $request)
    {
        $user = Auth::user();

        $validator = $this->annotationService->validateUpdateAnnotationRequest($request);

        if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $annotation = $this->annotationRepository->find($id);

        if (empty($id)) {
            return $this->sendError('Annotation not found');
        }

        if ($annotation->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }

       $annotation = $this->annotationRepository->update($input, $id);
        // $annotation = Annotation::where('id', $id)->update($input);

        // $this->annotationService->notifySecretary($annotation);

        $this->annotationService->notifyShareWith($annotation);

        $annotation = $this->annotationRepository->find($id);

        return $this->sendResponse($annotation, 'Annotation updated successfully');

    }

    /**
    * Delete Annotation Details
    * Delete /annotations/{id}
    * @param int $id
    * @return Response
    */
    public function destroy($id)
    {
        $annotation = Annotation::find($id);

        if (empty($annotation)) {
            return $this->sendError('Annotation not found');
        }

        $annotation->delete();

        return $this->sendResponse('Annotation deleted successfully');
    }
}
