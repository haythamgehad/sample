<?php

namespace App\Http\Controllers;

use App\Models\Statistic;
use App\Repositories\StatisticRepository;
use App\Services\StatisticService;
use Illuminate\Http\Request;
use Response;

/**
 * Class StatisticController
 * @package App\Http\Controllers\API
 */

class StatisticController extends Controller
{
    /** @var  StatisticRepository */
    private $statisticRepository;
    private $statisticService;

    public function __construct(StatisticRepository $statisticRepo)
    {
        $this->statisticRepository = $statisticRepo;
        $this->statisticService = new statisticService() ;
    }

    /**
     * Display a listing of the Statistic.
     * GET|HEAD /statistics
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $statistics = $this->statisticRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse($statistics->toArray(), 'Statistics retrieved successfully');
    }

    /**
     * Store a newly created Statistic in storage.
     * POST /statistics
     *
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $statistic = $this->statisticRepository->create($input);

        return $this->sendResponse($statistic->toArray(), 'Statistic saved successfully');
    }

    /**
     * Display the specified Statistic.
     * GET|HEAD /statistics/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var Statistic $statistic */
        $statistic = $this->statisticRepository->find($id);

        if (empty($statistic)) {
            return $this->sendError('Statistic not found');
        }

        return $this->sendResponse($statistic->toArray(), 'Statistic retrieved successfully');
    }

    /**
     * Update the specified Statistic in storage.
     * PUT/PATCH /statistics/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function update($id, Request $request)
    {
        $input = $request->all();

        /** @var Statistic $statistic */
        $statistic = $this->statisticRepository->find($id);

        if (empty($statistic)) {
            return $this->sendError('Statistic not found');
        }

        $statistic = $this->statisticRepository->update($input, $id);

        return $this->sendResponse($statistic->toArray(), 'Statistic updated successfully');
    }

        /**
     * Update the specified Statistic in storage.
     * PUT/PATCH /statistics/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function runCron()
    {

        $statistics = $this->statisticRepository->all(
            array('status'=>0),
            null,
            null
        )->toarray();
       
        foreach($statistics as $statistic){
            
            $this->statisticRepository->putCurl('/statistics/'.$statistic['id'], $statistic);

        }   

        return $this->sendResponse('Statistic updated successfully');
    }

    /**
     * Remove the specified Statistic from storage.
     * DELETE /statistics/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var Statistic $statistic */
        $statistic = $this->statisticRepository->find($id);

        if (empty($statistic)) {
            return $this->sendError('Statistic not found');
        }

        $statistic->delete();

        return $this->sendResponse('Statistic deleted successfully');
    }
}
