<?php

namespace Modules\DashBoard\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Home\App\Models\Language;
use Modules\DashBoard\App\Http\Services\DashboardService;
use Modules\DashBoard\App\Http\Requests\LanguageRequest;
use Modules\DashBoard\App\Http\Requests\SpecialityRequest;
use Modules\DashBoard\App\Http\Requests\MedicineRequest;
use Modules\DashBoard\App\Http\Requests\FaqRequest;
use Modules\DashBoard\App\Models\Medicine;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Modules\Home\app\Models\DoctorRequest;
use Modules\DashBoard\App\Models\FrontEndUser;
use Modules\Home\App\Models\Welcome;
use DB;

use Illuminate\Support\Facades\Log;

class DashBoardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }

//		$doctor_list = FrontEndUser::where('type', 'Doctor')->get()->toArray();
//		$patient_list = FrontEndUser::where('type', 'Patient')->get()->toArray();
//		$nurse_list = FrontEndUser::where('type', 'Nurse')->get()->toArray();
//		$appointments_list = DB::table('appointments')->get()->toArray();
//		$temp_doctor_list = DB::table('tempdoctors')->get()->toArray();

		//echo "<pre>";print_r($user);
        return view('dashboard::index', [
//			'doctor_list'=>$doctor_list,
//			'patient_list'=>$patient_list,
//			'nurse_list'=>$nurse_list,
//			'appointments_list'=>$appointments_list,
//			'temp_doctor_list'=>$temp_doctor_list

		]);
    }
	
	
	
	
	
	
	
	

    /**
     * Fetch Contact Data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function doctorRequest(Request $request): JsonResponse
    {

        $data = $this->dashboardService->doctorRequest($request);

        return response()->json($data);
    }

	public function receivedRequests()
    {

         $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }

		$postalCodes = DoctorRequest::query()
		->select('postal_code')
		->groupBy('postal_code')
		->get()->toArray();
//echo "<pre>";print_r($query);exit;

        //return response()->json($data);
		return view('dashboard::received_requests', ['postalCodes' => $postalCodes]);
		//return view('dashboard::received_requests', ['postalCodes' => $postalCodes]);
    }

    /**
     * Delete Request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteRequest(Request $request): JsonResponse
    {

        $data = $this->dashboardService->deleteRequest($request);

        return response()->json($data);
    }

    /**
     * Approve Request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function approveRequest(Request $request): JsonResponse
    {

        $data = $this->dashboardService->approveRequest($request);

        return response()->json($data);
    }


    /**
     * Approve Request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteHelp(Request $request): JsonResponse
    {

        $data = $this->dashboardService->deleteHelp($request);

        return response()->json($data);
    }

	public function updateStatus(Request $request)
	{
		$doctorRequest = DoctorRequest::find($request->id);
		if ($doctorRequest) {
			$doctorRequest->status = $request->status;
			$doctorRequest->save();

			// If status is 3, trigger the approve function
//			if ($request->status == 3) {
//				return $this->approveDoctor($doctorRequest->id);
//			}

			return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
		}
		return response()->json(['success' => false, 'message' => 'Doctor request not found.']);
	}


    public function updateUserStatus(Request $request)
    {
        $doctorRequest = FrontEndUser::find($request->id);
        if ($doctorRequest) {
            $doctorRequest->status = $request->status;
            $doctorRequest->save();

            // If status is 3, trigger the approve function
//			if ($request->status == 3) {
//				return $this->approveDoctor($doctorRequest->id);
//			}

            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Doctor request not found.']);
    }


	public function updateComment(Request $request)
	{
		$request->validate([
			'id' => 'required|exists:doctor_requests,id',
			'comment' => 'nullable|string|max:1000',
		]);

		$doctorRequest = DoctorRequest::findOrFail($request->id);
		$doctorRequest->comment = $request->comment;
		$doctorRequest->save();

		return response()->json(['success' => true, 'message' => 'Comment updated successfully']);
	}



    /**
     * Fetch Language
     */
    public function language()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        $data = $this->dashboardService->fetchLanguage();

        return view('dashboard::language', ['data' => $data]);
    }


    /**
     * Fetch Language
     */
    public function cms()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        $data = $this->dashboardService->cms();

        return view('dashboard::cms', ['data' => $data]);
    }


    /**
     * Fetch Language
     */
    public function helpCenter()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        $data = $this->dashboardService->helpCenter();

        return view('dashboard::helpcenter', ['data' => $data]);
    }

    /**
     * Create Language
     */
    public function createLanguage()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        return view('dashboard::create-language');
    }

    /**
     * Create Language
     *
     * @param languageRequest $request
     * @return RedirectResponse
     */
    public function submitLanguage(languageRequest $request): RedirectResponse
    {

        $this->dashboardService->submitLanguage($request);

        return redirect('/dashboard/create-language')->with('success', 'Language created successfully');
    }

    /**
     * Edit Language
     *
     * @param  $id
     * @return Renderable
     */
    public function editLanguage($id): Renderable
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
        $data = $this->dashboardService->editLanguage($id);

        return view('dashboard::edit-language', ['data' => $data]);

    }


    /**
     * editCms
     *
     * @param  $id
     * @return Renderable
     */
    public function editCms($id): Renderable
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
        $data = $this->dashboardService->editCms($id);

        return view('dashboard::edit-cms', ['data' => $data]);

    }


    /**
     * Edit CMS
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateCms(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
        $this->dashboardService->updateCms($request);

        return redirect('/dashboard/edit-cms/'.$request->id)->with('success', 'Updated successfully');
    }

    /**
     * Edit Language
     *
     * @param languageRequest $request
     * @return RedirectResponse
     */
    public function updateLanguage(Request $request)
	{
		$language = Language::findOrFail($request->id);
		$language->en_language = $request->en_language;
		$language->is_language = $request->is_language;
		$language->save();

		return response()->json([
			'res' => true,
			'msg' => 'Updated Successfully'
		]);
	}






    /**
     * Fetch Speciality
     */
    public function medicine(Request $request, int $perPage = 10)
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        //$data = $this->dashboardService->fetchMedicine();

        $query = Medicine::query();

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('brand', 'like', "%{$searchTerm}%");
        }

        $data = $query->orderByDesc('id')->paginate($perPage);

        return view('dashboard::medicine', ['data' => $data]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array'
        ]);

        Medicine::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Selected medicines deleted successfully.');
    }
    public function deleteMedicine($id)
    {
        $medicine = Medicine::findOrFail($id);
        $medicine->delete();

        return redirect()->back()->with('success', 'Medicine deleted successfully.');
    }

    public function deleteAllMedicines()
    {
//        if (!auth()->user()->isAdmin()) {
//            abort(403, 'Unauthorized action.');
//        }

        Medicine::truncate();

        return redirect()->back()->with('success', 'All medicines have been deleted successfully.');
    }



public function uploadCSV(Request $request)
{
    $request->validate([
        'csv_file' => 'required|mimes:csv,txt'
    ]);

    // Read and clean the file
    $content = file_get_contents($request->file('csv_file')->getPathname());

    // Remove UTF-8 BOM if present
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    $lines = array_map('trim', explode("\n", $content));

    // Remove completely empty lines
    $lines = array_filter($lines, function($line) {
        return !empty(trim($line));
    });

    $lines = array_values($lines); // Reset array indexes

    if (count($lines) < 1) {
        return redirect()->back()->with('error', 'CSV file is empty or improperly formatted.');
    }

    $firstLine = $lines[0];
    $delimiter = $this->detectDelimiter($firstLine);

    foreach ($lines as $index => $line) {
        // Skip header
        if ($index === 0 && (str_contains($line, 'Name') || str_contains($line, 'S1 No'))) {
            continue;
        }

        try {
            // Pipe-delimited format
         
               $row = explode(';', $line);

// Trim whitespace and quotes from each value
$row = array_map(function($value) {
    return trim($value, " \t\n\r\0\x0B\""); // also trims quotes
}, $row);

if (count($row) < 4) {
    Log::warning("Skipped row $index due to insufficient columns.");
    continue;
}

Medicine::create([
    'name' => $row[0] ?? null,
    'brand' => $row[1] ?? null,
    'packaging' => $row[2] ?? null,
    'dosage' => $row[3] ?? null,
]);

           
        } catch (\Exception $e) {
            Log::error("Failed to insert row {$index}: " . $e->getMessage());
        }
    }

    return redirect()->back()->with('success', 'CSV uploaded successfully.');
}

/**
 * Detect delimiter by checking which character occurs most in the line
 */
private function detectDelimiter($line)
{
    $delimiters = [
        ',' => substr_count($line, ','),
        ';' => substr_count($line, ';'),
        '|' => substr_count($line, '|')
    ];

    // Return delimiter with max occurrences
    return array_search(max($delimiters), $delimiters);
}

    
    
    public function __uploadCSV(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);

        $file = fopen($request->file('csv_file')->getPathname(), 'r');
        
        
        while (($row = fgetcsv($file)) !== FALSE) {
            Medicine::create([
                'name' => $row[0],
                'brand' => $row[1],
                'dosage' => $row[2],
                'packaging' => $row[3],
            ]);
        }
        fclose($file);

        return redirect()->back()->with('success', 'CSV uploaded successfully.');
    }


    /**
     * Create Speciality
     */
    public function createMedicine()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        return view('dashboard::create-medicine');
    }

    /**
     * Create Medicine
     *
     * @param medicineRequest $request
     * @return RedirectResponse
     */
    public function submitMedicine(medicineRequest $request): RedirectResponse
    {

        $this->dashboardService->submitMedicine($request);

        return redirect('/dashboard/create-medicine')->with('success', 'Data created successfully');
    }

    /**
     * Edit Medicine
     *
     * @param  $id
     * @return Renderable
     */
    public function editMedicine($id): Renderable
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
        $data = $this->dashboardService->editMedicine($id);

        return view('dashboard::edit-medicine', ['data' => $data]);

    }

    /**
     * Edit Medicine
     *
     * @param specialityRequest $request
     * @return RedirectResponse
     */
    public function updateMedicine(medicineRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
        $this->dashboardService->updateMedicine($request);

        return redirect('/dashboard/edit-medicine/'.$request->id)->with('success', 'Data updated successfully');
    }















    /**
     * Fetch Speciality
     */
    public function speciality()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        $data = $this->dashboardService->fetchSpeciality();

        return view('dashboard::speciality', ['data' => $data]);
    }

    /**
     * Create Speciality
     */
    public function createSpeciality()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        return view('dashboard::create-speciality');
    }

    /**
     * Create Speciality
     *
     * @param specialityRequest $request
     * @return RedirectResponse
     */
    public function submitSpeciality(specialityRequest $request): RedirectResponse
    {

        $this->dashboardService->submitSpeciality($request);

        return redirect('/dashboard/create-speciality')->with('success', 'Speciality created successfully');
    }

    /**
     * Edit Speciality
     *
     * @param  $id
     * @return Renderable
     */
    public function editSpeciality($id): Renderable
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
        $data = $this->dashboardService->editSpeciality($id);

        return view('dashboard::edit-speciality', ['data' => $data]);

    }

    /**
     * Edit Speciality
     *
     * @param specialityRequest $request
     * @return RedirectResponse
     */
    public function updateSpeciality(specialityRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
        $this->dashboardService->updateSpeciality($request);

        return redirect('/dashboard/edit-speciality/'.$request->id)->with('success', 'Speciality updated successfully');
    }

    /**
     * Fetch Faq
     */
    public function faq()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        $data = $this->dashboardService->fetchFaq();

        return view('dashboard::faq', ['data' => $data]);
    }

    /**
     * Create Faq
     */
    public function createFaq()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }
        return view('dashboard::create-faq');
    }

    /**
     * Create Faq
     *
     * @param faqRequest $request
     * @return RedirectResponse
     */
    public function submitFaq(faqRequest $request): RedirectResponse
    {

        $this->dashboardService->submitFaq($request);

        return redirect('/dashboard/create-faq')->with('success', 'Faq created successfully');
    }

    /**
     * Edit Faq
     *
     * @param  $id
     * @return Renderable
     */
    public function editFaq($id): Renderable
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
        $data = $this->dashboardService->editFaq($id);

        return view('dashboard::edit-faq', ['data' => $data]);

    }

    /**
     * Edit Faq
     *
     * @param faqRequest $request
     * @return RedirectResponse
     */
    public function updateFaq(faqRequest $request): RedirectResponse
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
        $this->dashboardService->updateFaq($request);

        return redirect('/dashboard/edit-faq/'.$request->id)->with('success', 'Faq updated successfully');
    }


    /**
     * Welcome
     *
     * @param  $id
     * @return Renderable
     */
    public function welcome(): Renderable
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect()->intended('/login');
        }
       // $data = $this->dashboardService->welcome($id);
 		$data = $this->dashboardService->welcome();
		//echo "<pre>";print_r($data);exit;

        $standard_message = DB::table('welcomes')

            ->get()->toArray();

        return view('dashboard::welcome', ['data' => $data,'standard_message'=>$standard_message]);

    }


    /**
     * Edit Welcome Text
     *
     * @param Request $request
     * @return RedirectResponse
     */
   public function updateWelcome(Request $request): RedirectResponse
	{
	   //echo 111;exit;
		$user = auth()->user();
		if (empty($user)) {
			return redirect()->intended('/login');
		}

        $standard_message = $request->standard_message;
        $profile_message = $request->profile_message;

		// Loop through each submitted message
		foreach ($request->messages as $id => $message) {
			$faq = Welcome::find($id);
			if ($faq) {
				$faq->lang_en = $message;
				$faq->save();
			}
		}

        if(isset($standard_message))
        {
            DB::table('welcomes')
                ->update([
                    'suspended' => 0
                ]);

            DB::table('welcomes')
                ->where('id', $standard_message)
                ->update([
                    'suspended' => 1
                ]);
        }

        if(isset($profile_message))
        {
            DB::table('welcomes')
                ->update([
                    'profile' => 0
                ]);

            DB::table('welcomes')
                ->where('id', $profile_message)
                ->update([
                    'profile' => 1
                ]);
        }


		return redirect('/dashboard/standard-message')->with('success', 'All messages updated successfully');
	}




    public function logout()
    {

        Auth::logout();
        return redirect('/login');

    }
}
