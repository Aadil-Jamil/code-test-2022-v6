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
     * Get the bookings list based on the user id or user type
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request): JsonResponse
{
    try {
        $response = [];

        // Get the user jobs based on the user id
        if ($user_id = $request->get('user_id')) {
            $response = $this->repository->getUsersJobs($user_id);
        }
        // Get all the jobs for admin or super admin
        elseif ($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID')) {
            $response = $this->repository->getAll($request);
        } else {
            throw new \InvalidArgumentException('Invalid request. Please provide a valid user id or user type.');
        }

        // Return the response as JSON
        return response()->json($response);
    } catch (\Exception $e) {
        // Handle any exceptions and return error response
        return response()->json(['error' => $e->getMessage()], 400);
    }
}

    /**
     * Get the booking details based on the booking id
     * @param $id
     * @return mixed
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Find the booking details using the repository
            $job = $this->repository->with('translatorJobRel.user')->find($id);

            // Return the booking details as JSON
            return response()->json($job);
        } catch (\Exception $e) {
            // Handle any exceptions and return error response
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        try {
            // Get all the input data from the request
            $data = $request->all();
            // Store the booking using the repository and authenticated user
            $response = $this->repository->store($request->__authenticatedUser, $data);
            // Return the response with success status
            return response($response);
        } catch (\Exception $e) {
            // Handle any exceptions that occurred while storing the booking
            return response(['status' => 'fail', 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        try {
            // Get the request data
            $data = $request->all();
            // Get the authenticated user
            $cuser = $request->__authenticatedUser;

            // Call the repository method to update the job
            $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $cuser);

            // Return a success response with the updated job data
            return response()->json([
                'success' => true,
                'data' => $response
            ]);

        } catch (\Exception $e) {
            // If an exception occurs, log the error and return an error response
            \Log::error($e->getMessage(), [
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating job.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        try {
            // Get admin sender email from config
            $adminSenderEmail = config('app.adminemail');
            
            // Get all data from the request
            $data = $request->all();
            
            // Store job email data and get the response from the repository
            $response = $this->repository->storeJobEmail($data);
            
            // Return success response with the response from the repository
            return response()->json([
                'status' => 'success',
                'data' => $response
            ]);
        } catch (\Throwable $th) {
            // Log error and return error response
            \Log::error('Error in immediateJobEmail function: ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while storing job email data.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        try {
            // Get the user ID from the request
            $user_id = $request->get('user_id');

            // Check if user ID exists in the request
            if (!$user_id) {
                throw new InvalidArgumentException('User ID is required.');
            }

            // Call the repository function to get user's job history
            $response = $this->repository->getUsersJobsHistory($user_id, $request);

            // Return the response
            return response($response);
        } catch (Exception $e) {
            // Handle any exceptions that may occur and return an error response
            return response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();

        if (isset($data['distance']) && $data['distance'] != "") {
            $distance = $data['distance'];
        } else {
            $distance = "";
        }
        if (isset($data['time']) && $data['time'] != "") {
            $time = $data['time'];
        } else {
            $time = "";
        }
        if (isset($data['jobid']) && $data['jobid'] != "") {
            $jobid = $data['jobid'];
        }

        if (isset($data['session_time']) && $data['session_time'] != "") {
            $session = $data['session_time'];
        } else {
            $session = "";
        }

        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }
        
        if ($data['manually_handled'] == 'true') {
            $manually_handled = 'yes';
        } else {
            $manually_handled = 'no';
        }

        if ($data['by_admin'] == 'true') {
            $by_admin = 'yes';
        } else {
            $by_admin = 'no';
        }

        if (isset($data['admincomment']) && $data['admincomment'] != "") {
            $admincomment = $data['admincomment'];
        } else {
            $admincomment = "";
        }
        if ($time || $distance) {

            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {

            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));

        }

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
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
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}