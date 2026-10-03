<?php

namespace Modules\Home\app\Http\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Modules\User\app\Models\User;
use Modules\Home\app\Models\DoctorRequest;
use Modules\Home\app\Models\Specilaties;
use Modules\Home\app\Models\Welcome;
use Carbon\Carbon;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\View;
use Modules\Home\app\Models\Faq;
use Modules\Home\app\Models\Language;
use Modules\Home\app\Models\Absence;
use Modules\Home\app\Models\Cancel;
use Modules\Home\app\Models\Delay;
use Modules\Home\app\Models\Reschedule;
use Modules\Home\app\Models\DoctorTemplate;
use Modules\Doctor\app\Models\AvailableDays;
use Modules\Doctor\app\Models\ConsultationType;
use Modules\Doctor\app\Models\DoctorAvailableDay;
use Modules\Doctor\app\Models\DoctorChangeDay;
use Modules\Doctor\app\Models\DateChangeConsultation;
use Modules\Home\app\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Modules\Doctor\app\Models\DoctorConsultationTime;
use Modules\Doctor\app\Models\TempDoctor;
use Modules\Doctor\app\Models\DoctorHoliday;
use Modules\Home\app\Models\DoctorOtherTemplate;
use DateTime;
use DatePeriod;
use DateInterval;
use Illuminate\Support\Facades\Http;
use Modules\Home\app\Models\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;


use Modules\Appointment\app\Http\Services\AppointmentService;

class HomeService
{
    private string $absPath = "";
    private string $relPath = "";
	private AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->absPath = public_path('uploads/files/');
        $this->relPath = 'uploads/files/';
		$this->appointmentService = $appointmentService; // Inject AppointmentService
    }


	public function fetchDoctorRating(int $doctor_id): array
    {
        // Call getRating method from AppointmentService
        $ratingData = $this->appointmentService->getRating(new Request(['doctor_id' => $doctor_id]));

		//echo "<pre>";print_r($ratingData);exit;
		return $ratingData;
//        return [
//            'res' => true,
//            'msg' => 'Rating fetched successfully',
//            'data' => $ratingData
//        ];
    }



	public function AddDoctorTemplate($request): array
	{
    $GetTemplat = Admin::select()->where('token', $request->token)->get()->toArray();

    if (!empty($GetTemplat)) {
        // Save template to database


        // Generate PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $htmlContent = "<html><head><style>{$request->style}</style></head><body>{$request->template_body}</body></html>";
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save the generated PDF to a file
        $pdfOutput = $dompdf->output();
		$file_name = 'doctor_template_' . time() . '.pdf';
        $filePath = public_path('uploads/doctor_template_pdf/') . $file_name;
        file_put_contents($filePath, $pdfOutput);



		$insert = new DoctorTemplate();
		if ($request->hasFile('template_image')) {
            // Validate image
            $file = $request->file('template_image');
            $fileName = rand() . time() . '.' . $file->getClientOriginalExtension();

            // Check if user has an old profile picture and unlink it
//            $oldFilePath = public_path($user->template_image);
//            if (File::exists($oldFilePath)) {//echo 1;exit;
//                File::delete(public_path($user->template_image));
//            }

            // Move new file to the uploads directory
            $file->move($this->absPath, $fileName);
            $insert->template_image = 'uploads/files/' . $fileName; // Update the path in the database
        }else{
			$insert->template_image ='';
		}

        $insert->user_id = $request->user_id;
		$insert->category = $request->category;
        $insert->template_name = $request->template_name;
        $insert->template_body = $request->template_body;
        $insert->style = $request->style;
		$insert->template_pdf_file = $file_name;
        $insert->status = 0;
        $insert->save();


        // Clear admin token
        $Admin = Admin::find(1);
        if ($Admin) {
            $Admin->token = '';
            $Admin->save();
        }

        return ['res' => true, 'msg' => "Successfully Added!", 'data' => $filePath];
    } else {
        return ['res' => false, 'msg' => "Token Invalid!", 'data' => ''];
    }
}



	public function EditDoctorTemplate($request): array
	{
    $GetTemplat = Admin::select()->where('token', $request->token)->get()->toArray();
		$GetTemplat_Doctor = DoctorTemplate::select()->where('id', $request->id)->where('status', 0)->get()->toArray();
    if (!empty($GetTemplat) && !empty($GetTemplat_Doctor)) {

		$template = DoctorTemplate::find($request->id);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $htmlContent = "<html><head><style>{$request->style}</style></head><body>{$request->template_body}</body></html>";
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save the generated PDF to a file
        $pdfOutput = $dompdf->output();
		if($template->template_pdf_file==''){
			$file_name = 'doctor_template_' . time() . '.pdf';
		}else{
			$file_name = $template->template_pdf_file;//'doctor_template_' . time() . '.pdf';
		}

        $filePath = public_path('uploads/doctor_template_pdf/') . $file_name;

        // Find the existing template entry


        if ($template && $template->template_pdf_file) {
            // Check if the existing PDF file exists and delete it
            $oldFilePath = public_path('uploads/doctor_template_pdf/') . $template->template_pdf_file;
            if (File::exists($oldFilePath)) {
                File::delete($oldFilePath);
            }
        }

        // Save the new PDF file
        file_put_contents($filePath, $pdfOutput);

        // Update the template entry with new data
        $template->user_id = $request->user_id;
		$template->category = $request->category;
        $template->template_name = $request->template_name;
        $template->template_body = $request->template_body;
        $template->style = $request->style;
        $template->template_pdf_file = $file_name;


		if ($request->hasFile('template_image')) {
            // Validate image
            $file = $request->file('template_image');
            $fileName = rand() . time() . '.' . $file->getClientOriginalExtension();

            $oldFilePath = public_path($template->template_image);
            if (File::exists($oldFilePath)) {//echo 1;exit;
                File::delete(public_path($template->template_image));
            }
            $file->move($this->absPath, $fileName);
            $template->template_image = 'uploads/files/' . $fileName; // Update the path in the database
        }else{
			$template->template_image ='';
		}
        $template->save();

        $Admin = Admin::find(1);
        if ($Admin) {
            $Admin->token = "";
            $Admin->save();
        }

        return ['res' => true, 'msg' => "Successfully Edited!", 'data' => ''];
    } else {
        return ['res' => false, 'msg' => "Token Invalid!", 'data' => ''];
    }
}



	public function checkadmintoken($request): array
    {


        $GetTemplat = Admin::select()->where('token',$request->token)->get()->toArray();
		//$GetTemplat = Admin::find($request->id);

        return [
            'res' => true,
            'msg' => '',
            'data' => $GetTemplat
        ];
    }


    public function getCityState($request): array
    {


        $results = DB::table('municipal')
            ->select('id', 'name', DB::raw("'city' as types"))
            ->where('name', 'LIKE', '%'.$request->location.'%')
            ->union(
                DB::table('cities')
                    ->select('id', 'name', DB::raw("'state' as types"))
                    ->where('name', 'LIKE', '%'.$request->location.'%')
            )
            ->get()
            ->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $results
        ];
    }


	public function getDoctorTemplate($request): array
    {


        //$GetTemplat = DoctorTemplate::select()->where('id',$request->id)->get();//->toArray()
		$GetTemplat = DoctorTemplate::find($request->id);

        return [
            'res' => true,
            'msg' => '',
            'data' => $GetTemplat
        ];
    }


    public function getOtherDoctorTemplate($request): array
    {


        //$GetTemplat = DoctorTemplate::select()->where('id',$request->id)->get();//->toArray()
        $GetTemplat = DoctorOtherTemplate::find($request->id);

        return [
            'res' => true,
            'msg' => '',
            'data' => $GetTemplat
        ];
    }



    /**
     * Request Doctor
     *
     * @param $request
     * @return array
     */
    public function requestDoctor($request): array
    {

        $proofDocument = $request->proof_document;

        $exists = User::where('email', $request->email)->exists();
        if ($exists) {

            return ['res' => false, 'msg' => "Doctor email already exist", 'data' => ''];
        }

        if (isset($proofDocument) && $proofDocument != "") {
            $fileName = Str::random(10) .'.'. $proofDocument->extension();
            $proofDocuments = $proofDocument->move($this->absPath, $fileName);
        }

        $insert = new DoctorRequest();
        $insert->first_name = $request->first_name;
        $insert->last_name = $request->last_name;
        $insert->postal_code = $request->postal_code;
        $insert->speciality = $request->speciality;

		$insert->city = $request->city;
		$insert->municipality = $request->municipality;

        $insert->email = $request->email;
        $insert->cell_phone = $request->cell_phone;
        $insert->save();

        $webUrl = env('APP_URL');
        $appName = env('APP_NAME');
        $mail = new PHPMailer(true);
        $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        $mail->addAddress("skjaha@gmail.com","Jahangir");
        $mail->Subject = $appName.'::Practitioner';
        $htmlContent = View::make('email.requestDoctor', ['site_url' => $webUrl, 'site_name' => $appName, 'name' => $request->first_name])->render();
        $mail->isHTML(true);
        $mail->Body = $htmlContent;
        $mail->send();

        return ['res' => true, 'msg' => "Successfully Send!", 'data' => ''];
    }



    /**
     * Help Center
     *
     * @param $request
     * @return array
     */
    public function helpCenter($request): array
    {

        DB::table('helpCenter')->insert([
            'name' => $request->name,
            'types' => $request->types,
            'phno' => $request->phno,
            'email' => $request->email,
            'subject' => $request->subject
        ]);



        return ['res' => true, 'msg' => "Successfully Send!", 'data' => ''];
    }



    /**
     * Get Speciality
     *
     * @return array
     */
    public function getSpeciality(): array
    {

//
//        // Fetch cities where lat/lng is NULL
//        $cities = DB::table('municipal')->whereNull('lat')->orWhereNull('lng')->get();
//
//        foreach ($cities as $city) {
//            $cityName = urlencode($city->name);
//            $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$cityName}&key={$apiKey}";
//
//            $response = Http::get($url);
//            $data = $response->json();
//
//            if (!empty($data['results'])) {
//                $location = $data['results'][0]['geometry']['location'];
//                DB::table('municipal')
//                    ->where('id', $city->id)
//                    ->update([
//                        'lat' => $location['lat'],
//                        'lng' => $location['lng']
//                    ]);
//            }
//        }

        $data = Specilaties::get()->toArray();

		$doctors_specilaties = Specilaties::get()->toArray();

		foreach($doctors_specilaties as $k=>$v){

			$user = User::where('speciality',$v['id'])->get()->toArray();
			if(empty($user)){
				unset($doctors_specilaties[$k]);
			}

		}

		$doctors_specilaties = array_values($doctors_specilaties);

        return [
            'res' => true,
            'msg' => '',
			'doctors_specilaties' => $doctors_specilaties,
            'data' => $data
        ];
    }

	public function getCity(): array
    {
        $data = DB::table('cities')->get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }

	public function municipalityList($request): array
    {
        $data = DB::table('municipal')->where('city_id',$request->id)->get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }
    /**
     * Get Faqs
     *
     * @return array
     */
    public function getFaqs(): array
    {
        $data = Faq::get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }

    /**
     * Get Language
     *
     * @return array
     */
    public function getLanguages(): array
    {
        $data = Language::get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }

    /**
     * Get Days
     *
     * @return array
     */
    public function getDays(): array
    {
        $data = AvailableDays::get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }

    /**
     * Get Consult Type
     *
     * @return array
     */
    public function getConsultation(): array
    {
        $data = ConsultationType::get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }


    /**
     * Fetch All Doctor By AutoComplete
     *
     * @param $request
     * @return array
     */
    public function fetchDoctor($request): array
    {
        $search= $request->search;

        $doctors = User::select('users.id as user_id', 'users.first_name', 'users.last_name', 'specialities.name as speciality', 'users.profile_pic','users.slug', \DB::raw('0 as types'))
            ->Join('specialities', 'specialities.id', '=', 'users.speciality')
            ->join('doctor_expert', 'doctor_expert.user_id', '=', 'users.id')
            ->leftjoin('doctor_experiances', 'doctor_experiances.user_id', '=', 'users.id')
            ->leftjoin('doctor_degree', 'doctor_degree.user_id', '=', 'users.id')
            ->leftjoin('doctor_language', 'doctor_language.user_id', '=', 'users.id')
            ->leftjoin('doctor_trainings', 'doctor_trainings.user_id', '=', 'users.id')
            ->where(function($query) use ($search) {
                $query->where('users.first_name', 'LIKE', "%$search%")
                    ->orWhere('users.last_name', 'LIKE', "%$search%")
                    ->orWhere('specialities.name', 'LIKE', "%$search%");
            })
            ->where('users.status', 1)
            ->where('users.speciality', '!=', 0)
            ->whereNotNull('users.cabin_address')
            ->where('users.cabin_address', '!=', '')
            ->where('users.type', 'Doctor')
            ->distinct()
            ->get();

        if ($doctors->isEmpty()) {

            $doctors = User::select('users.id as user_id', 'users.first_name', 'users.last_name', 'specialities.name as speciality', 'users.profile_pic','users.slug', \DB::raw('2 as types'))
                ->Join('specialities', 'specialities.id', '=', 'users.speciality')
                ->join('doctor_expert', 'doctor_expert.user_id', '=', 'users.id')
                ->leftjoin('doctor_experiances', 'doctor_experiances.user_id', '=', 'users.id')
                ->leftjoin('doctor_degree', 'doctor_degree.user_id', '=', 'users.id')
                ->leftjoin('doctor_language', 'doctor_language.user_id', '=', 'users.id')
                ->leftjoin('doctor_trainings', 'doctor_trainings.user_id', '=', 'users.id')
                ->where(function($query) use ($search) {
                    $query->where('users.first_name', 'LIKE', "%$search%")
                        ->orWhere('users.last_name', 'LIKE', "%$search%")
                        ->orWhere('specialities.name', 'LIKE', "%$search%");
                })
                ->where('users.status', 2)
                ->where('users.speciality', '!=', 0)
                ->whereNotNull('users.cabin_address')
                ->where('users.cabin_address', '!=', '')
                ->where('users.type', 'Doctor')
                ->distinct()
                ->get();
        }

        if ($doctors->isEmpty()) {

            $doctors = TempDoctor::select('tempdoctors.id as user_id', 'tempdoctors.first_name', 'tempdoctors.last_name', 'specialities.name as speciality','tempdoctors.slug', \DB::raw('1 as types'))
                ->Join('specialities', 'specialities.id', '=', 'tempdoctors.speciality')
                ->where(function($query) use ($search) {
                    $query->where('tempdoctors.first_name', 'LIKE', "%$search%")
                        ->orWhere('tempdoctors.last_name', 'LIKE', "%$search%")
                        ->orWhere('specialities.name', 'LIKE', "%$search%");
                })
                ->whereNotNull('tempdoctors.cabin_address')
                ->where('tempdoctors.cabin_address', '!=', '')
                ->get();
        }

        $specialities = Specilaties::where('name', 'LIKE', "%$search%")->get();



        return [
            'res' => true,
            'msg' => '',
            'doctors' => $doctors,
            'speciality' => $specialities
        ];
    }


    /**
     * Search Doctor By Availability for App
     *
     * @param $request
     * @return array
     */
    public function searchDoctorApp($request): array
    {
        $search_filter = isset($request->search_filter) ? $request->search_filter : "1";
        $search = $request->search;
        $lat = $request->lat;
        $long = $request->lng;
        $radius = 50; // Radius in kilometers
        $page = isset($request->page) ? (int)$request->page : 1;
        $perPage = isset($request->per_page)?$request->per_page:"3"; // Number of records per page
        $offset = ($page - 1) * $perPage;
        $types = $request->types;
        $id = $request->id;

        if ($lat == "") {
            return [
                'res' => false,
                'msg' => 'Please enter at least one search keyword!',
                'doctors' => ''
            ];
        }

        $query = User::select(
            'users.id as user_id',
            'users.first_name',
            'users.last_name',
            'users.lat',
            'users.lng',
            'users.consultation_type',
            'users.cabin_address',
            'specialities.name as speciality',
            'users.profile_pic',
            'users.future_days',
            'users.slug', \DB::raw('0 as types')
        )
            ->join('specialities', 'specialities.id', '=', 'users.speciality')
            ->join('doctor_expert', 'doctor_expert.user_id', '=', 'users.id')
            ->leftjoin('municipal', 'municipal.id', '=', 'users.municipality_id')
            ->leftjoin('doctor_experiances', 'doctor_experiances.user_id', '=', 'users.id')
            ->leftjoin('doctor_degree', 'doctor_degree.user_id', '=', 'users.id')
            ->leftjoin('doctor_language', 'doctor_language.user_id', '=', 'users.id')
            ->leftjoin('doctor_trainings', 'doctor_trainings.user_id', '=', 'users.id')
            ->where('users.status', 1)
            ->where('users.speciality', '!=', 0)

            ->whereNotNull('users.cabin_address')
            ->where('users.cabin_address', '!=', '')
            ->where('users.type', 'Doctor');

        if (!empty($search)) {
            $query->where(function($query) use ($search) {
                $query->Where('specialities.name', $search);
            });
        }

        if (!empty($lat) && !empty($long)) {
            $query->addSelect(DB::raw("( 6371 * acos( cos( radians($lat) ) * cos( radians( municipal.lat ) ) * cos( radians( municipal.lng ) - radians($long) ) + sin( radians($lat) ) * sin( radians( municipal.lat ) ) ) ) AS distance"))
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }

        if (!empty($types)) {
            if($types=='state')
            {
                $query->where(function($query) use ($id, $search) {
                    $query->Where('users.city_id', $id);
                });
            }
            else
            {
                $query->where(function($query) use ($id, $search) {
                    $query->Where('users.municipality_id', $id);
                });
            }

        }


        $tempQuery = TempDoctor::select(
            'tempdoctors.id as user_id',
            'tempdoctors.first_name',
            'tempdoctors.last_name',
            'tempdoctors.lat',
            'tempdoctors.lng'
            , \DB::raw('1 as consultation_type'),
            'tempdoctors.cabin_address',
            'specialities.name as speciality'
            , \DB::raw('"" as profile_pic'),
            \DB::raw('"" as future_days'),
            'tempdoctors.slug', \DB::raw('1 as types'),

        )
            ->join('specialities', 'specialities.id', '=', 'tempdoctors.speciality')
            ->leftjoin('municipal', 'municipal.id', '=', 'tempdoctors.municipality')
            ->whereNotNull('tempdoctors.cabin_address')
            ->where('tempdoctors.cabin_address', '!=', '');

        if (!empty($search)) {
            $tempQuery->where(function ($tempQuery) use ($search) {
                $tempQuery->Where('specialities.name', $search);
            });
        }


        if (!empty($lat) && !empty($long)) {
            $tempQuery->addSelect(DB::raw("( 6371 * acos( cos( radians($lat) ) * cos( radians( municipal.lat ) ) * cos( radians( municipal.lng ) - radians($long) ) + sin( radians($lat) ) * sin( radians( municipal.lat ) ) ) ) AS distance"))
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }



        if (!empty($types)) {
            if($types=='state')
            {
                $tempQuery->where(function($tempQuery) use ($id, $search) {
                    $tempQuery->Where('tempdoctors.city', $id);
                });
            }
            else
            {
                $tempQuery->where(function($tempQuery) use ($id, $search) {
                    $tempQuery->Where('tempdoctors.municipality', $id);
                });
            }

        }

        $query = $query->union($tempQuery);
        $doctors = $query->distinct()
            ->skip($offset)
            ->take($perPage)
            ->get()
            ->toArray();

        if(!empty($doctors))
        {
            foreach ($doctors as &$doctor) {
                $availArray = [];
                $holidayArray = [];
                $availableDays = DoctorAvailableDay::where('user_id', $doctor['user_id'])->get();
                $count = 0;
                foreach ($availableDays as $row) {
                    $availArray[$count] = array(
                        "date" => date('Y-m-d', strtotime('next '.$row['day'])),
                    );
                    $count++;
                }

                usort($availArray, function ($a, $b) {
                    return strtotime($a['date']) - strtotime($b['date']);
                });

                $holidays = DoctorHoliday::where('user_id', $doctor['user_id'])->get();
                foreach ($holidays as $holiday) {
                    $holidayArray[] = array(
                        "date" => $holiday['holiday_date'],
                    );
                    $count++;
                }

                $doctor['nextDay'] = $availArray; // Append availability data
                $doctor['holiday_date'] = $holidayArray;
            }
        }




        if ($search_filter == "1") {
            // Default filter
        } elseif ($search_filter == "2") {
            $start_date = date('Y-m-d');
            $end_date = date('y-m-d');

            foreach($doctors as $k => $v) {
                $consults_type = ConsultationType::where('id', $v['consultation_type'])->get()->toArray();
                if($v['consultation_type']==0)
                {
                    $consults_type[0]['types']= '';
                }
                $availDays = $this->dateTodayAndNextDay($start_date, $end_date, $v['user_id'], $consults_type[0]['types']);
                if (empty($availDays)) {
                    unset($doctors[$k]);
                }


            }
            $doctors = array_values($doctors);
        } elseif ($search_filter == "3") {
            $start_date = date('Y-m-d');
            $end_date = date('y-m-d', strtotime('+3 days'));

            foreach($doctors as $k => $v) {
                $consults_type = ConsultationType::where('id', $v['consultation_type'])->get()->toArray();
                if($v['consultation_type']==0)
                {
                    $consults_type[0]['types']= '';
                }
                $availDays = $this->dateTodayAndNextDay($start_date, $end_date, $v['user_id'], $consults_type[0]['types']);
                if (empty($availDays)) {
                    unset($doctors[$k]);
                }
            }
            $doctors = array_values($doctors);
        }

			foreach($doctors as $k=>$v){
				$ratingResponse = $this->fetchDoctorRating($v['user_id']);
				$doctors[$k]['doctor_rating']=$ratingResponse['averageRating'];
			}

        $query_total = User::select(
            'users.id as user_id',
            'users.first_name',
            'users.last_name',
            'users.lat',
            'users.lng',
            'users.consultation_type',
            'users.cabin_address',
            'specialities.name as speciality',
            'users.profile_pic',
            'users.slug'
        )
            ->join('specialities', 'specialities.id', '=', 'users.speciality')
            ->join('doctor_expert', 'doctor_expert.user_id', '=', 'users.id')
            ->leftjoin('doctor_experiances', 'doctor_experiances.user_id', '=', 'users.id')
            ->leftjoin('doctor_degree', 'doctor_degree.user_id', '=', 'users.id')
            ->leftjoin('doctor_language', 'doctor_language.user_id', '=', 'users.id')
            ->leftjoin('doctor_trainings', 'doctor_trainings.user_id', '=', 'users.id')
            ->leftjoin('municipal', 'municipal.id', '=', 'users.municipality_id')
            ->where('users.status', 1)
            ->where('users.speciality', '!=', 0)

            ->whereNotNull('users.cabin_address')
            ->where('users.cabin_address', '!=', '')
            ->where('users.type', 'Doctor');

        if (!empty($search)) {
            $query_total->where(function($query_total) use ($search) {
                $query_total->Where('specialities.name', $search);
            });
        }

        if (!empty($lat) && !empty($long)) {
            $query_total->addSelect(DB::raw("( 6371 * acos( cos( radians($lat) ) * cos( radians( municipal.lat ) ) * cos( radians( municipal.lng ) - radians($long) ) + sin( radians($lat) ) * sin( radians( municipal.lat ) ) ) ) AS distance"))
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }
        if (!empty($types)) {
            if($types=='state')
            {
                $query_total->where(function($query_total) use ($id, $search) {
                    $query_total->Where('users.city_id', $id);
                });
            }
            else
            {
                $query_total->where(function($query_total) use ($id, $search) {
                    $query_total->Where('users.municipality_id', $id);
                });
            }

        }

        $tempQueryTotal = TempDoctor::select(
            'tempdoctors.id as user_id',
            'tempdoctors.first_name',
            'tempdoctors.last_name',
            'tempdoctors.lat',
            'tempdoctors.lng'
            , \DB::raw('1 as consultation_type'),
            'tempdoctors.cabin_address',
            'specialities.name as speciality'
            , \DB::raw('"" as profile_pic'),
            'tempdoctors.slug'
        )->join('specialities', 'specialities.id', '=', 'tempdoctors.speciality')
            ->leftjoin('municipal', 'municipal.id', '=', 'tempdoctors.municipality')
            ->whereNotNull('tempdoctors.cabin_address')
            ->where('tempdoctors.cabin_address', '!=', '');

        if (!empty($search)) {
            $tempQueryTotal->where(function($tempQueryTotal) use ($search) {
                $tempQueryTotal->Where('specialities.name', $search);
            });
        }

        if (!empty($lat) && !empty($long)) {
            $tempQueryTotal->addSelect(DB::raw("( 6371 * acos( cos( radians($lat) ) * cos( radians( municipal.lat ) ) * cos( radians( municipal.lng ) - radians($long) ) + sin( radians($lat) ) * sin( radians( municipal.lat ) ) ) ) AS distance"))
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }
        if (!empty($types)) {
            if($types=='state')
            {
                $tempQueryTotal->where(function($tempQueryTotal) use ($id, $search) {
                    $tempQueryTotal->Where('tempdoctors.city', $id);
                });
            }
            else
            {
                $tempQueryTotal->where(function($tempQueryTotal) use ($id, $search) {
                    $tempQueryTotal->Where('tempdoctors.municipality', $id);
                });
            }

        }

        $query_total = $query_total->union($tempQueryTotal);



        $doctors_total = $query_total->distinct()->get()->toArray();

        //echo "<pre>";print_r($doctors_total);

        if ($search_filter == "1") {
            // Default filter
        } elseif ($search_filter == "2") {
            $start_date = date('Y-m-d');
            $end_date = date('y-m-d');

            foreach($doctors_total as $k => $v) {
                $consults_type = ConsultationType::where('id', $v['consultation_type'])->get()->toArray();
                if($v['consultation_type']==0)
                {
                    $consults_type[0]['types']= '';
                }
                $availDays = $this->dateTodayAndNextDay($start_date, $end_date, $v['user_id'], $consults_type[0]['types']);
                if (empty($availDays)) {
                    unset($doctors_total[$k]);
                }
            }
            $doctors_total = array_values($doctors_total);
        } elseif ($search_filter == "3") {
            $start_date = date('Y-m-d');
            $end_date = date('y-m-d', strtotime('+3 days'));

            foreach($doctors_total as $k => $v) {
                $consults_type = ConsultationType::where('id', $v['consultation_type'])->get()->toArray();
                if($v['consultation_type']==0)
                {
                    $consults_type[0]['types']= '';
                }
                $availDays = $this->dateTodayAndNextDay($start_date, $end_date, $v['user_id'], $consults_type[0]['types']);
                if (empty($availDays)) {
                    unset($doctors_total[$k]);
                }
            }
            $doctors_total = array_values($doctors_total);
        }

        return [
            'res' => true,
            'msg' => '',
            'doctors_total' => count($doctors_total),
            'doctors' => $doctors
        ];
    }

    /**
     * Search All Doctor By Availability
     *
     * @param $request
     * @return array
     */
    public function searchDoctor($request): array
    {
        $search_filter = isset($request->search_filter) ? $request->search_filter : "1";
        $search = $request->search;
        $lat = $request->lat;
        $long = $request->lng;
        $radius = 50; // Radius in kilometers
        $page = isset($request->page) ? (int)$request->page : 1;
        $perPage = isset($request->per_page)?$request->per_page:"3"; // Number of records per page
        $offset = ($page - 1) * $perPage;
        $types = $request->types;
        $id = $request->id;

        if ($search == "" && $lat == "") {
            return [
                'res' => false,
                'msg' => 'Please enter at least one search keyword!',
                'doctors' => ''
            ];
        }






        $query = User::select(
            'users.id as user_id',
            'users.city_id',
            'users.municipality_id',
            'users.first_name',
            'users.last_name',
            'users.lat',
            'users.lng',
            'users.consultation_type',
            'users.cabin_address',
            'specialities.name as speciality',
            'users.profile_pic',
            'users.future_days',
            'users.slug', \DB::raw('0 as types')
        )
            ->join('specialities', 'specialities.id', '=', 'users.speciality')
            ->join('doctor_expert', 'doctor_expert.user_id', '=', 'users.id')
            ->leftjoin('municipal', 'municipal.id', '=', 'users.municipality_id')
            ->leftjoin('doctor_experiances', 'doctor_experiances.user_id', '=', 'users.id')
            ->leftjoin('doctor_degree', 'doctor_degree.user_id', '=', 'users.id')
            ->leftjoin('doctor_language', 'doctor_language.user_id', '=', 'users.id')
            ->leftjoin('doctor_trainings', 'doctor_trainings.user_id', '=', 'users.id')
            ->where('users.status', 1)
            ->where('users.speciality', '!=', 0)

            ->whereNotNull('users.cabin_address')
            ->where('users.cabin_address', '!=', '')
            ->where('users.type', 'Doctor');

        if (!empty($search)) {
            $query->where(function($query) use ($search) {
                $query->Where('specialities.name', $search);
            });
        }

        if (!empty($lat) && !empty($long)) {
            $query->addSelect(DB::raw("( 6371 * acos( cos( radians($lat) ) * cos( radians( municipal.lat ) ) * cos( radians( municipal.lng ) - radians($long) ) + sin( radians($lat) ) * sin( radians( municipal.lat ) ) ) ) AS distance"))
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }

        if (!empty($types)) {
            if($types=='state')
            {
                $query->where(function($query) use ($id, $search) {
                    $query->Where('users.city_id', $id);
                });
            }
            else
            {
                $query->where(function($query) use ($id, $search) {
                    $query->Where('users.municipality_id', $id);
                });
            }

        }


        $tempQuery = TempDoctor::select(
            'tempdoctors.id as user_id',
            'tempdoctors.city as city_id',
            'tempdoctors.municipality as municipality_id',
            'tempdoctors.first_name',
            'tempdoctors.last_name',
            'tempdoctors.lat',
            'tempdoctors.lng'
            , \DB::raw('1 as consultation_type'),
            'tempdoctors.cabin_address',
            'specialities.name as speciality'
            , \DB::raw('"" as profile_pic'),
            \DB::raw('"" as future_days'),
            'tempdoctors.slug', \DB::raw('1 as types'),

        )
            ->join('specialities', 'specialities.id', '=', 'tempdoctors.speciality')
            ->leftjoin('municipal', 'municipal.id', '=', 'tempdoctors.municipality')
            ->whereNotNull('tempdoctors.cabin_address')
            ->where('tempdoctors.cabin_address', '!=', '');

        if (!empty($search)) {
            $tempQuery->where(function ($tempQuery) use ($search) {
                $tempQuery->Where('specialities.name', $search);
            });
        }


        if (!empty($lat) && !empty($long)) {
            $tempQuery->addSelect(DB::raw("( 6371 * acos( cos( radians($lat) ) * cos( radians( municipal.lat ) ) * cos( radians( municipal.lng ) - radians($long) ) + sin( radians($lat) ) * sin( radians( municipal.lat ) ) ) ) AS distance"))
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }



        if (!empty($types)) {
            if($types=='state')
            {
                $tempQuery->where(function($tempQuery) use ($id, $search) {
                    $tempQuery->Where('tempdoctors.city', $id);
                });
            }
            else
            {
                $tempQuery->where(function($tempQuery) use ($id, $search) {
                    $tempQuery->Where('tempdoctors.municipality', $id);
                });
            }

        }

        $query = $query->union($tempQuery);
        $doctors = $query->distinct()
            ->skip($offset)
            ->take($perPage)
            ->get()
            ->toArray();

        if(!empty($doctors))
        {
            foreach ($doctors as &$doctor) {
                $availArray = [];
                $holidayArray = [];
                $availableDays = DoctorAvailableDay::where('user_id', $doctor['user_id'])->get();
                $count = 0;
                foreach ($availableDays as $row) {
                    $availArray[$count] = array(
                        "date" => date('Y-m-d', strtotime('next '.$row['day'])),
                    );
                    $count++;
                }

                usort($availArray, function ($a, $b) {
                    return strtotime($a['date']) - strtotime($b['date']);
                });

                $holidays = DoctorHoliday::where('user_id', $doctor['user_id'])->get();
                foreach ($holidays as $holiday) {
                    $holidayArray[] = array(
                        "date" => $holiday['holiday_date'],
                    );
                    $count++;
                }

                $doctor['nextDay'] = $availArray; // Append availability data
                $doctor['holiday_date'] = $holidayArray;
            }
        }




        if ($search_filter == "1") {
            // Default filter
        } elseif ($search_filter == "2") {
            $start_date = date('Y-m-d');
            $end_date = date('y-m-d');

            foreach($doctors as $k => $v) {
                $consults_type = ConsultationType::where('id', $v['consultation_type'])->get()->toArray();
                if($v['consultation_type']==0)
                {
                    $consults_type[0]['types']= '';
                }
                $availDays = $this->dateTodayAndNextDay($start_date, $end_date, $v['user_id'], $consults_type[0]['types']);
                if (empty($availDays)) {
                    unset($doctors[$k]);
                }


            }
            $doctors = array_values($doctors);
        } elseif ($search_filter == "3") {
            $start_date = date('Y-m-d');
            $end_date = date('y-m-d', strtotime('+3 days'));

            foreach($doctors as $k => $v) {
                $consults_type = ConsultationType::where('id', $v['consultation_type'])->get()->toArray();
                if($v['consultation_type']==0)
                {
                    $consults_type[0]['types']= '';
                }
                $availDays = $this->dateTodayAndNextDay($start_date, $end_date, $v['user_id'], $consults_type[0]['types']);
                if (empty($availDays)) {
                    unset($doctors[$k]);
                }
            }
            $doctors = array_values($doctors);
        }

        foreach($doctors as $k=>$v){
            $ratingResponse = $this->fetchDoctorRating($v['user_id']);
            $doctors[$k]['doctor_rating']=$ratingResponse['averageRating'];
        }

        $query_total = User::select(
            'users.id as user_id',
            'users.first_name',
            'users.last_name',
            'users.lat',
            'users.lng',
            'users.consultation_type',
            'users.cabin_address',
            'specialities.name as speciality',
            'users.profile_pic',
            'users.slug'
        )
            ->join('specialities', 'specialities.id', '=', 'users.speciality')
            ->join('doctor_expert', 'doctor_expert.user_id', '=', 'users.id')
            ->leftjoin('doctor_experiances', 'doctor_experiances.user_id', '=', 'users.id')
            ->leftjoin('doctor_degree', 'doctor_degree.user_id', '=', 'users.id')
            ->leftjoin('doctor_language', 'doctor_language.user_id', '=', 'users.id')
            ->leftjoin('doctor_trainings', 'doctor_trainings.user_id', '=', 'users.id')
            ->leftjoin('municipal', 'municipal.id', '=', 'users.municipality_id')
            ->where('users.status', 1)
            ->where('users.speciality', '!=', 0)

            ->whereNotNull('users.cabin_address')
            ->where('users.cabin_address', '!=', '')
            ->where('users.type', 'Doctor');

        if (!empty($search)) {
            $query_total->where(function($query_total) use ($search) {
                $query_total->Where('specialities.name', $search);
            });
        }

        if (!empty($lat) && !empty($long)) {
            $query_total->addSelect(DB::raw("( 6371 * acos( cos( radians($lat) ) * cos( radians( municipal.lat ) ) * cos( radians( municipal.lng ) - radians($long) ) + sin( radians($lat) ) * sin( radians( municipal.lat ) ) ) ) AS distance"))
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }
        if (!empty($types)) {
            if($types=='state')
            {
                $query_total->where(function($query_total) use ($id, $search) {
                    $query_total->Where('users.city_id', $id);
                });
            }
            else
            {
                $query_total->where(function($query_total) use ($id, $search) {
                    $query_total->Where('users.municipality_id', $id);
                });
            }

        }

        $tempQueryTotal = TempDoctor::select(
            'tempdoctors.id as user_id',
            'tempdoctors.first_name',
            'tempdoctors.last_name',
            'tempdoctors.lat',
            'tempdoctors.lng'
            , \DB::raw('1 as consultation_type'),
            'tempdoctors.cabin_address',
            'specialities.name as speciality'
            , \DB::raw('"" as profile_pic'),
            'tempdoctors.slug'
        )->join('specialities', 'specialities.id', '=', 'tempdoctors.speciality')
            ->leftjoin('municipal', 'municipal.id', '=', 'tempdoctors.municipality')
            ->whereNotNull('tempdoctors.cabin_address')
            ->where('tempdoctors.cabin_address', '!=', '');

        if (!empty($search)) {
            $tempQueryTotal->where(function($tempQueryTotal) use ($search) {
                $tempQueryTotal->Where('specialities.name', $search);
            });
        }

        if (!empty($lat) && !empty($long)) {
            $tempQueryTotal->addSelect(DB::raw("( 6371 * acos( cos( radians($lat) ) * cos( radians( municipal.lat ) ) * cos( radians( municipal.lng ) - radians($long) ) + sin( radians($lat) ) * sin( radians( municipal.lat ) ) ) ) AS distance"))
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }
        if (!empty($types)) {
            if($types=='state')
            {
                $tempQueryTotal->where(function($tempQueryTotal) use ($id, $search) {
                    $tempQueryTotal->Where('tempdoctors.city', $id);
                });
            }
            else
            {
                $tempQueryTotal->where(function($tempQueryTotal) use ($id, $search) {
                    $tempQueryTotal->Where('tempdoctors.municipality', $id);
                });
            }

        }

        $query_total = $query_total->union($tempQueryTotal);



        $doctors_total = $query_total->distinct()->get()->toArray();

        //echo "<pre>";print_r($doctors_total);

        if ($search_filter == "1") {
            // Default filter
        } elseif ($search_filter == "2") {
            $start_date = date('Y-m-d');
            $end_date = date('y-m-d');

            foreach($doctors_total as $k => $v) {
                $consults_type = ConsultationType::where('id', $v['consultation_type'])->get()->toArray();
                if($v['consultation_type']==0)
                {
                    $consults_type[0]['types']= '';
                }
                $availDays = $this->dateTodayAndNextDay($start_date, $end_date, $v['user_id'], $consults_type[0]['types']);
                if (empty($availDays)) {
                    unset($doctors_total[$k]);
                }
            }
            $doctors_total = array_values($doctors_total);
        } elseif ($search_filter == "3") {
            $start_date = date('Y-m-d');
            $end_date = date('y-m-d', strtotime('+3 days'));

            foreach($doctors_total as $k => $v) {
                $consults_type = ConsultationType::where('id', $v['consultation_type'])->get()->toArray();
                if($v['consultation_type']==0)
                {
                    $consults_type[0]['types']= '';
                }
                $availDays = $this->dateTodayAndNextDay($start_date, $end_date, $v['user_id'], $consults_type[0]['types']);
                if (empty($availDays)) {
                    unset($doctors_total[$k]);
                }
            }
            $doctors_total = array_values($doctors_total);
        }


        foreach($doctors as $k=>$v){
            $city = DB::table('cities')->where('id',$v['city_id'])->first();
//            if($city)
//            {
//                $doctors[$k]['city'] = $city->name;
//            }
//            else
//            {
//                $doctors[$k]['city'] = '';
//            }

            $municiple = DB::table('municipal')->where('id',$v['municipality_id'])->first();
//            if($municiple)
//            {
//                $doctors[$k]['municiple'] = $municiple->name;
//            }
//            else
//            {
//                $doctors[$k]['municiple'] = '';
//            }
            if($city && $municiple){
                $doctors[$k]['cabin_address'] = $v['cabin_address'].', '.$municiple->name.', '.$city->name;
            }elseif($city){
                $doctors[$k]['cabin_address'] = $v['cabin_address'].', '.$city->name;
            }elseif($municiple){
                $doctors[$k]['cabin_address'] = $v['cabin_address'].', '.$municiple->name;
            }

        }



       // echo "<pre>";print_r($doctors);

        return [
            'res' => true,
            'msg' => '',
            'doctors_total' => count($doctors_total),
            'doctors' => $doctors
        ];
    }



    public function dateTodayAndNextDay($start_date,$end_date,$doctor_id,$consultation_type)
    {
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $end = $end->modify('+1 day');
        $dates = [];
        $period = new \DatePeriod($start, new DateInterval('P1D'), $end);

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        //$doctors = User::where('id',$doctor_id)->get()->toArray();


        $results = [];
        foreach($dates as $date){
            $data_array = $this->dateAvailabilityArray($date, $doctor_id, $consultation_type);
            if(!empty($data_array)){
                $results[$date] = !empty($data_array) ? $data_array : '';
            }

        }

        return  $results;
    }


    public function getDoctorAvailableday($request): array
    {
        if($request->start_date<date('Y-m-d'))
        {
            $start_date= date('Y-m-d');
        }
        else
        {
            $start_date= $request->start_date;
        }

        $end_date= $request->end_date;
        $doctor_id= $request->doctor_id;
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $end = $end->modify('+1 day');
        $dates = [];
        $period = new \DatePeriod($start, new DateInterval('P1D'), $end);

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        //$doctors = User::where('id',$doctor_id)->get()->toArray();

//"<pre>";print_r($dates);
        $results = [];
        foreach($dates as $date){

            $data_array = $this->dateAvailabilityArray($date, $doctor_id, 'For online consultation');

            if(!empty($data_array)){
                $results[$date] = !empty($data_array) ? $data_array : [];
            }
//echo 11;//"<pre>";print_r($data_array);

        }
//echo 2;exit;

        $allAppointments = Appointment::select('time','appointment_date')->where('doctor_id',$doctor_id)->get()->toArray();
//echo "<pre>";print_r($allAppointments);exit;
        return [
            'res' => true,
            'msg' => '',
            'doctors' => $results,
            'notAvailable' => $allAppointments
        ];
    }


    public function dateAvailabilityArray($requestedDate,$doctor_id,$consultation_types)
    {
        $user = Auth::user();
        $doctor = $doctor_id;

        //echo $requestedDate;exit;
        $availDays = DoctorChangeDay::where('user_id', $doctor)->where('day', $requestedDate)->where('types', 0)->where('emergency', 0)->orderBy('start_time')->get();
        $data = array();
        if (!$availDays->isEmpty()) {

            $interval = DateChangeConsultation::where('user_id', $doctor)
                ->where('types', $consultation_types)
                ->where('day', $requestedDate)
                ->first();

            foreach ($availDays as $row) {
                $data[] = array(
                    "start_time" => $row->start_time,
                    "end_time" => $row->end_time,
					"has_interval" => $row->has_interval,
                    "intervals" => $interval->time_slot,
                    "visitor" => $row->visitor,
                    "time_slot_type" => isset($interval->time_slot_type) ? $interval->time_slot_type : ""
                );
            }
        } else {


            $date = Carbon::createFromFormat('Y-m-d', $requestedDate);

            $interval = DoctorConsultationTime::where('user_id', $doctor)
                ->where('types', $consultation_types)
                ->first();

            //echo $doctor.'='.$consultation_types;//exit;


            $dayName = strtolower($date->format('l'));

            $availableDays = DoctorAvailableDay::where('user_id', $doctor)
                ->where('day', $dayName)
                ->where('emergency', 0)
                ->orderBy('start_time')
                ->get();
            $data = array();

            if (!$availableDays->isEmpty()) {
                foreach ($availableDays as $row) {

                    $data[] = array(
                        "start_time" => $row->start_time,
                        "end_time" => $row->end_time,
						"has_interval" => $row->has_interval,
                        "intervals" => isset($interval->time_slot) ? $interval->time_slot : "",
                        "visitor" => $row->visitor,
                        "time_slot_type" => isset($interval->time_slot_type) ? $interval->time_slot_type : ""
                    );
                }

            }



        }


        if(!is_null($interval)){

            $interval_slot = explode(':', $interval->time_slot)[0];

            $intervals = $this->processAvailabilityData($data, $interval_slot, $interval->time_slot_type);
            $allAppointments = Appointment::select('time')->where('doctor_id',$doctor)->where('appointment_date',$requestedDate)->get()->toArray();


//echo "<pre>";print_r($intervals);
            return  $intervals;
        }else{
            return [];
        }



    }


    private function processAvailabilityData($availabilities, $interval_time, $time_slot_type)
    {
        $intervals = [];
		$data = [];



        foreach ($availabilities as $availability) {

                $intervals = array_merge($intervals, $this->generateIntervals(
                    $availability['start_time'],
                    $availability['end_time'],
                    $availability['has_interval'],
                    $interval_time,
                    $time_slot_type,
                    $availability['visitor']
                ));
                $start = new DateTime($availability['start_time']);
                $end = new DateTime($availability['end_time']);
                $data[] = array(

                    "start_time"=>$start->format('H:i'),
                    "end_time"=>$end->format('H:i'),
                    "has_interval" => $availability['has_interval'],
                    "intervals" => $availability['intervals'],
                    "time_slot_type" => $availability['time_slot_type'],
                    "visitor" => $availability['visitor'],
                );
        }



        return $data;
    }

    private function generateIntervals($start_time, $end_time, $has_interval, $interval_time, $time_slot_type,$vistor)
    {//echo $has_interval;//exit;
        $start = new DateTime($start_time);
        $end = new DateTime($end_time);

        // Create DateInterval object from interval time
        if ($time_slot_type == 'Minute') {
            $interval = new DateInterval('PT' . $interval_time . 'M');
        } else {
            $interval = new DateInterval('PT' . $interval_time . 'H');
        }



//		if($has_interval=='1'){
//			$times = [];
//			$times[]=array(
//				'start'=>$start_time,
//				'end'=>$end_time,
//			);
//		}else{
//			$times = [];
//			while ($start < $end) {
//				$times[] = $start->format('H:i');
//				$start->add($interval);
//			}
//		}

		$times = [];
			while ($start < $end) {
				$times[] = $start->format('H:i');
				$start->add($interval);
			}


        return $times;
    }



    /**
     * Doctor Details
     *
     * @param $request
     * @return array
     */
    public function doctorDetails($request): array
    {
        $slug= $request->slug;
        $types= $request->types;
        if($types==1)
        {
            $doctor = TempDoctor::where('slug', $slug)->first();
            $speciality = Specilaties::where('id',$doctor->speciality)->first();

            $city = DB::table('cities')->where('id',$doctor->city)->first();
            if($city)
            {
                $doctor['city'] = $city->name;
            }
            else
            {
                $doctor['city'] = '';
            }


            $municiple = DB::table('municipal')->where('id',$doctor->municipality)->first();
            if($municiple)
            {
                $doctor['municiple'] = $municiple->name;
            }
            else
            {
                $doctor['municiple'] = '';
            }


            $doctor['speciality'] = $speciality->name;
            $doctor_languages = array();
            $doctor_faqs = array();

            if(!empty($doctor->standard_message))
            {
                $message = Welcome::where('id',$doctor->standard_message)->first();
                $lang_en= $message->lang_en;
            }
            else
            {
                $lang_en= '';
            }

            $appointments_has = Appointment::select('time','appointment_date')->where('doctor_id',$doctor->id)->get()->toArray();


            return [
                'res' => true,
                'msg' => '',
                "users" => $doctor,
                "appointments_has" => $appointments_has,
                'downloadApp' =>1,
                "doctor_languages" => $doctor_languages,
                "doctor_faqs" =>$doctor_faqs,
                'showmessage' => $lang_en
            ];

        }
        $doctor = User::where('slug',$slug)->first();

        $doctorLanguages = $doctor->doctorLanguages()->with('language')->get();

        $doctorFaqs = $doctor->doctorFaqs()
            ->with('faq')
            ->get()
            ->map(function ($faq) {
                $faq->answer = nl2br(e($faq->answer)); // Apply nl2br and escape HTML
                return $faq;
            });

        $speciality = Specilaties::where('id',$doctor->speciality)->first();

        $doctor['speciality'] = $speciality->name;


        $city = DB::table('cities')->where('id',$doctor->city_id)->first();
        if($city)
        {
            $doctor['city'] = $city->name;
        }
        else
        {
            $doctor['city'] = '';
        }


        $municiple = DB::table('municipal')->where('id',$doctor->municipality_id)->first();
        if($municiple)
        {
            $doctor['municiple'] = $municiple->name;
        }
        else
        {
            $doctor['municiple'] = '';
        }


        $message = Welcome::where('suspended',1)->first();
       if($message)
       {
           if($doctor->status==2)
           {
               $showmessage = $message->lang_en;
           }
           else
           {
               $showmessage = '';
           }
       }
       else
       {
           $showmessage = '';
       }


        $fetchDataList = DoctorAvailableDay::where('user_id', $doctor->id)->get();
        $availableDays = array();
        foreach ($fetchDataList as $fetchData) {
            $day = $fetchData->day;
            $availableDays[$day]['intervals'][] = [
                "id" => $fetchData->id,
                "start_time" => $fetchData->start_time,
                "end_time" => $fetchData->end_time,
            ];
        }

        foreach ($availableDays as $day => &$entry) {
            usort($entry['intervals'], function ($a, $b) {
                return strtotime($a['start_time']) - strtotime($b['start_time']);
            });
        }

        $formattedData = [];
        foreach ($availableDays as $day => $entry) {
            $intervalsStr = array_map(function($interval) {
                return date('H:i', strtotime($interval['start_time'])) . '-' . date('H:i', strtotime($interval['end_time']));
            }, $entry['intervals']);

            $formattedData[$day] = implode(', ', $intervalsStr);
        }

        $doctor->doctorExperts;
        $doctor->doctorConsultations;
        $doctor->doctorTransports;
        $doctor->doctorParkings;
        $doctor->doctorInformations;
        $doctor->doctorDocuments;
        $doctor->doctorDegrees;
        $doctor->doctorTrainings;
        $doctor->doctorExperiences;
        $doctor->doctorPublications;
        $doctor->availableDays = $formattedData;


		$ratingResponse = $this->fetchDoctorRating($doctor->id);

		//echo "<pre>";print_r($ratingResponse);exit;
		$doctor['doctor_rating']=$ratingResponse['averageRating'];

//		$reviewlist = $this->fetchDoctorRating($doctor->id);
		$doctor['reviewlist'] =$ratingResponse['reviewlist'];
        $appointments_has = Appointment::select('time','appointment_date')->where('doctor_id',$doctor->id)->get()->toArray();
        return [
            'res' => true,
            'msg' => '',
            "users" => $doctor,
            "appointments_has" => $appointments_has,
            "doctor_languages" => $doctorLanguages,
            "doctor_faqs" => $doctorFaqs,
            'showmessage' => $showmessage
        ];
    }

    /**
     * Get Cancel
     *
     * @return array
     */
    public function getCancelReason(): array
    {
        $data = Cancel::get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }

    /**
     * Get Absence
     *
     * @return array
     */
    public function getAbsenceReason(): array
    {
        $data = Absence::get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }

    /**
     * Get Reschedule
     *
     * @return array
     */
    public function getRescheduleReason(): array
    {
        $data = Reschedule::get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }

    /**
     * Get Delay
     *
     * @return array
     */
    public function getDelayReason(): array
    {
        $data = Delay::get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }

    /**
     * Get Delay
     *
     * @return array
     */
    public function downloadApp(): array
    {
        $data = Welcome::get()->toArray();

        return [
            'res' => true,
            'msg' => '',
            'data' => $data
        ];
    }

    public function cms($request): array
    {


        $GetTemplat = DB::table('cms')->where('id',$request->id)->get()->toArray();
        //$GetTemplat = Admin::find($request->id);

        return [
            'res' => true,
            'msg' => '',
            'data' => $GetTemplat
        ];
    }


    /**
     * Save image from base64 string.
     *
     * @param $image
     * @param $fileName
     * @return Stringable|string
     */
    private function imageUpload($image, $fileName): Stringable|string
    {
        $brandAbsPath = $this->absPath;
        $brandRelPath =  $this->relPath;
        $image_64 = $image; //your base64 encoded data
        $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
        $image_64 = str_replace($replace, '', $image_64);
        $image_64 = str_replace(' ', '+', $image_64);
        $imageName = $fileName;

        try {
            File::put($brandAbsPath . $imageName, base64_decode($image_64));
        } catch (Exception $e) {
            // Handle the error appropriately
            return 'Error saving the file';
        }

        return $brandRelPath . $imageName;
    }

    /**
     * Get File Extension
     *
     * @param $base64String
     * @return mixed|string
     */
    private function getExtensionFromBase64($base64String)
    {
        preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches);
        return $matches[1] ?? 'png'; // Default to png if no match
    }

}
