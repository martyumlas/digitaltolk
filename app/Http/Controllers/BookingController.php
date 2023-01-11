<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if($request->user_id) {
          
            return response($this->repository->getUsersJobs($request->user_id));
        }
        return response($this->repository->getAll($request));
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        return response($this->repository->with('translatorJobRel.user')->find($id));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        return response($this->repository->store(Auth::user(), $request->all()));

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->except('_token', 'submit');

        return response($this->repository->updateJob($id, $data, Auth::user()));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        return response($this->repository->storeJobEmail($request->all()));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if( $request->user_id) {
            $response = $this->repository->getUsersJobsHistory($request->user_id, $request->all());
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        return response(
            $this->repository->acceptJob($request->all(), 
            Auth::user())
        );
    }

    public function acceptJobWithId(Request $request)
    {
        $response = $this->repository->acceptJobWithId($request->job_id, Auth::user());

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $response = $this->repository->cancelJobAjax($request->all(), Auth::user());

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        return response($this->repository->endJob($request->all()));
    }

    public function customerNotCall(Request $request)
    {
        return response($this->repository->customerNotCall($request->all()));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $response = $this->repository->getPotentialJobs(Auth::user());

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();

        $distance = "";
        $time = "";
        $jobid = "";
        $session = "";
        $flagged = 'no';
        $manually_handled = 'no';
        $by_admin = 'no';
        $admincomment = "";

        if (isset($data['distance']) && $data['distance'] !== "") {
            $distance = $data['distance'];
        }

        if (isset($data['time']) && $data['time'] !== "") {
            $time = $data['time'];
        } 

        if (isset($data['jobid']) && $data['jobid'] !== "") {
            $jobid = $data['jobid'];
        }

        if (isset($data['session_time']) && $data['session_time'] !== "") {
            $session = $data['session_time'];
        }

        if ($data['flagged'] === 'true') {
            if($data['admincomment'] === '') {
                return "Please, add comment";
            } 
            $flagged = 'yes';
        } 
        
        if ($data['manually_handled'] == 'true') {
            $manually_handled = 'yes';
        } 

        if ($data['by_admin'] == 'true') {
            $by_admin = 'yes';
        } 

        if (isset($data['admincomment']) && $data['admincomment'] != "") {
            $admincomment = $data['admincomment'];
        } 

        //jobid is needed to update the resource
        if ($time && $jobid || $distance && $jobid) {

            Distance::where('job_id',  $jobid)
            ->update([
                'distance' => $distance, 
                'time' => $time
            ]);
        }

        if($jobid) {
            if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
                Job::where('id', '=', $jobid)
                ->update([
                     'admin_comments' => $admincomment,
                     'flagged' => $flagged, 
                     'session_time' => $session, 
                     'manually_handled' => $manually_handled, 
                     'by_admin' => $by_admin
                 ]);
     
             }
        }
        

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {  
        return response($this->repository->reopen($request->all()));
    }

    public function resendNotifications(Request $request)
    {
        $job = $this->repository->find($request->jobid);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $job = $this->repository->find($request->jobid);
        // $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
