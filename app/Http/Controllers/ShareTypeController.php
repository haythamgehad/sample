<?php

namespace App\Http\Controllers;

use App\Models\ShareType;
use App\Repositories\ShareTypeRepository;
use App\Services\ShareTypeService;
use Illuminate\Http\Request;
use Response;

/**
 * Class ShareTypeController
 * @package App\Http\Controllers
 */

class ShareTypeController extends Controller
{
    private $shareTypeRepository;
    private $shareTypeService;

    public function __construct(ShareTypeRepository $shareTypeRepo)
    {
        $this->shareTypeRepository = $shareTypeRepo;

        $this->shareTypeService = new ShareTypeService();
    }

    /**
    * Show ShareType list
    * GET /sharestypes
    * @return Response
    */
    public function index(Request $request)
    {
        $shareTypes = $this->shareTypeRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse($shareTypes->toArray(), 'Share Types retrieved successfully');
    }

    /**
    * Post ShareType.
    * POST /sharestypes
    * @return Response
    * @bodyParam name string required The  name of the ShareType. Example:  ShareType
    * @bodyParam language_id int required The  language_id . Example:  1
    */
    public function store(Request $request)
    {
        
        $input = $request->all();

        $validator = $this->shareTypeService->validateCreateRequest($request);
     
        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $shareType = $this->shareTypeRepository->create($input);

        return $this->sendResponse($shareType->toArray(), 'Share Type saved successfully');
    }

    /**
    * Show the specified ShareType.
    * GET /sharestypes/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the ShareType.
    */
    public function show($id)
    {
        $shareType = $this->shareTypeRepository->find($id);

        if (empty($shareType)) {
            return $this->sendError('Share Type not found');
        }

        return $this->sendResponse($shareType->toArray(), 'Share Type retrieved successfully');
    }

    /**
    * Update the specified ShareType.
    * PUT/PATCH /sharestypes/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the ShareType.
    * @bodyParam name string required The  name of the Location. Example:  ShareType
    * @bodyParam language_id int required The  language_id . Example:  1
    */
    public function update($id, Request $request)
    {
        $input = $request->all();

        $validator = $this->shareTypeService->validateUpdateRequest($request);
     
        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $shareType = $this->shareTypeRepository->find($id);

        if (empty($shareType)) {
            return $this->sendError('Share Type not found');
        }

        $shareType = $this->shareTypeRepository->update($input, $id);

        return $this->sendResponse($shareType->toArray(), 'Share Type updated successfully');
    }

    /**
    * Delete ShareType 
    * Delete /sharestypes/{id}
    * @param int $id
    * @return Response
    */
    public function destroy($id)
    {
        $shareType = $this->shareTypeRepository->find($id);

        if (empty($shareType)) {
            return $this->sendError('Share Type not found');
        }

        $shareType->delete();

        return $this->sendResponse('Share Type deleted successfully');
    }
}
