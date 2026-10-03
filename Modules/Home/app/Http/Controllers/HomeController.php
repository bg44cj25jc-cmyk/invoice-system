<?php

namespace Modules\Home\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\app\Models\User;
use Modules\Home\app\Models\DoctorRequest;
use Modules\Home\app\Http\Requests\RequestInquiry;
use Modules\Home\app\Http\Services\HomeService;



class HomeController extends Controller
{
    private HomeService $homeService;

    public function __construct(HomeService $homeService)
    {
        $this->homeService = $homeService;
    }
    /**
     * Doctor Request
     *
     * @param RequestInquiry $request
     * @return JsonResponse
     */

	 public function AddDoctorTemplate(Request $request): JsonResponse
    {
        $response = $this->homeService->AddDoctorTemplate($request);

        return response()->json($response);
    }

	public function EditDoctorTemplate(Request $request): JsonResponse
    {
        $response = $this->homeService->EditDoctorTemplate($request);

        return response()->json($response);
    }



	public function checkadmintoken(Request $request): JsonResponse
    {
        $response = $this->homeService->checkadmintoken($request);

        return response()->json($response);
    }

	public function getDoctorTemplate(Request $request): JsonResponse
    {
        $response = $this->homeService->getDoctorTemplate($request);

        return response()->json($response);
    }


    public function getOtherDoctorTemplate(Request $request): JsonResponse
    {
        $response = $this->homeService->getOtherDoctorTemplate($request);

        return response()->json($response);
    }



    public function requestDoctor(RequestInquiry $request): JsonResponse
    {
        $response = $this->homeService->requestDoctor($request);

        return response()->json($response);
    }

    public function helpCenter(Request $request): JsonResponse
    {
        $response = $this->homeService->helpCenter($request);

        return response()->json($response);
    }

    /**
     * Get Speciality
     *
     * @return JsonResponse
     */
    public function getSpeciality(): JsonResponse
    {



        $response = $this->homeService->getSpeciality();

        return response()->json($response);
    }
	/**
     * Get City
     *
     * @return JsonResponse
     */
	public function getCity(): JsonResponse
    {
        $response = $this->homeService->getCity();

        return response()->json($response);
    }

	public function municipalityList(Request $request): JsonResponse
    {
        $response = $this->homeService->municipalityList($request);

        return response()->json($response);
    }

    /**
     * Get Faq
     *
     * @return JsonResponse
     */
    public function getFaqs(): JsonResponse
    {
        $response = $this->homeService->getFaqs();

        return response()->json($response);
    }

    /**
     * Get Language
     *
     * @return JsonResponse
     */
    public function getLanguages(): JsonResponse
    {
        $response = $this->homeService->getLanguages();

        return response()->json($response);
    }

    /**
     * Get Days
     *
     * @return JsonResponse
     */
    public function getDays(): JsonResponse
    {
        $response = $this->homeService->getDays();

        return response()->json($response);
    }

    /**
     * Get Consultation
     *
     * @return JsonResponse
     */
    public function getConsultation(): JsonResponse
    {
        $response = $this->homeService->getConsultation();

        return response()->json($response);
    }

    /**
     * Fetch All Doctor By AutoComplete
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchDoctor(Request $request): JsonResponse
    {
        $response = $this->homeService->fetchDoctor($request);

        return response()->json($response);
    }

    /**
     * Search All Doctor By App Availability
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchDoctorApp(Request $request): JsonResponse
    {
        $response = $this->homeService->searchDoctorApp($request);

        return response()->json($response);
    }


	/**
     * Search All Doctor By Availability
     *
     * @param Request $request
     * @return JsonResponse
     */
	public function searchDoctor(Request $request): JsonResponse
    {
        $response = $this->homeService->searchDoctor($request);

        return response()->json($response);
    }

	public function getDoctorAvailableday(Request $request): JsonResponse
    {
        $response = $this->homeService->getDoctorAvailableday($request);

        return response()->json($response);
    }

    /**
     * Doctor Details
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function doctorDetails(Request $request): JsonResponse
    {
        $response = $this->homeService->doctorDetails($request);

        return response()->json($response);
    }

    /**
     * City And State
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCityState(Request $request): JsonResponse
    {
        $response = $this->homeService->getCityState($request);

        return response()->json($response);
    }

    /**
     * Get Absence Reason
     *
     * @return JsonResponse
     */
    public function getAbsenceReason(): JsonResponse
    {
        $response = $this->homeService->getAbsenceReason();

        return response()->json($response);
    }

    /**
     * Get Cancel Reason
     *
     * @return JsonResponse
     */
    public function getCancelReason(): JsonResponse
    {
        $response = $this->homeService->getCancelReason();

        return response()->json($response);
    }

    /**
     * Get Delay Reason
     *
     * @return JsonResponse
     */
    public function getDelayReason(): JsonResponse
    {
        $response = $this->homeService->getDelayReason();

        return response()->json($response);
    }



    /**
     * Get Reschedule Reason
     *
     * @return JsonResponse
     */
    public function getRescheduleReason(): JsonResponse
    {
        $response = $this->homeService->getRescheduleReason();

        return response()->json($response);
    }

    /**
     * Download App
     *
     * @return JsonResponse
     */
    public function downloadApp(): JsonResponse
    {
        $response = $this->homeService->downloadApp();

        return response()->json($response);
    }


    /**
     * CMS
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cms(Request $request): JsonResponse
    {
        $response = $this->homeService->cms($request);

        return response()->json($response);
    }

}
