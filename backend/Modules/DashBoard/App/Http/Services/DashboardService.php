<?php

namespace Modules\DashBoard\App\Http\Services;

use Illuminate\Http\Request;

use Modules\Login\App\Models\User;
use Modules\DashBoard\App\Models\FrontEndUser;
use Modules\Home\App\Models\Language;
use Modules\Home\App\Models\Specilaties;
use Modules\DashBoard\App\Models\Medicine;

use Modules\Home\App\Models\Faq;
use Modules\Home\app\Models\DoctorRequest;
use Modules\Home\App\Models\Welcome;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use DB;

class DashboardService
{


    /**
     * Create Language
     *
     * @param $request
     * @return array
     */
    public function submitLanguage($request): array
    {
        $en_language = $request->en_language;
		$is_language = $request->is_language;
		
        $language= new Language;
        $language->en_language = $en_language;
		$language->is_language = $is_language;
        $language->save();
        return [
            'res' => true,
            'msg' => 'Created Successfully',
            'data' => $language->id
        ];
    }

    /**
     * Fetch Language
     *
     * @return array
     */
    public function fetchLanguage(): array
    {
        return Language::orderByDesc('id')->get()->toArray();
    }

    /**
     * Fetch CMS
     *
     * @return array
     */
    public function cms(): array
    {
        return DB::table('cms')->orderByDesc('id')->get()->toArray();
    }

    /**
     * Fetch CMS
     *
     * @return array
     */
    public function helpCenter(): array
    {
        return DB::table('helpCenter')->orderByDesc('id')->get()->toArray();
    }

    /**
     * Edit CMS
     *
     * @param $id
     * @return mixed
     */
    public function editCms($id): mixed
    {
        return DB::table('cms')->where('id', $id)->first();
    }


    /**
     * Update CMS
     *
     * @param  $request
     * @return array
     */
    public function updateCms($request): array
    {
        $description = $request->description;
        DB::table('cms')
            ->where('id', $request->id)
            ->update(['description' => $description]);
        return [
            'res' => true,
            'msg' => 'Updated Successfully',
            'data' => ''
        ];
    }

    /**
     * Detail Language
     *
     * @param $id
     * @return mixed
     */
    public function editLanguage($id): mixed
    {
        return Language::where('id', $id)->first();
    }

    /**
     * Update Language
     *
     * @param  $request
     * @return array
     */
    public function updateLanguage($request): array
    {
        $en_language = $request->en_language;
		$is_language = $request->is_language;
		
        $language= Language::findOrFail($request->id);
        $language->en_language = $en_language;
		$language->is_language = $is_language;
        $language->save();
        return [
            'res' => true,
            'msg' => 'Updated Successfully',
            'data' => ''
        ];
    }





    /**
     * Create Medicine
     *
     * @param $request
     * @return array
     */
    public function submitMedicine($request): array
    {
        $name = $request->name;
        $speciality= new Medicine;


        $speciality->brand = $request->brand;
        $speciality->name = $request->name;
        $speciality->dosage = $request->dosage;
        $speciality->packaging = $request->packaging;
        $speciality->save();
        return [
            'res' => true,
            'msg' => 'Created Successfully',
            'data' => $speciality->id
        ];
    }

    /**
     * Fetch Medicine
     *
     * @return array
     */

    public function fetchMedicine(int $perPage = 10)
    {
        return Medicine::orderByDesc('id')->paginate($perPage);
    }

    /**
     * Detail Medicine
     *
     * @param $id
     * @return mixed
     */
    public function editMedicine($id): mixed
    {
        return Medicine::where('id', $id)->first();
    }

    /**
     * Update Medicine
     *
     * @param  $request
     * @return array
     */
    public function updateMedicine($request): array
    {
        $name = $request->name;
        $speciality= Medicine::findOrFail($request->id);
        $speciality->brand = $request->brand;
        $speciality->name = $request->name;
        $speciality->dosage = $request->dosage;
        $speciality->packaging = $request->packaging;
        $speciality->save();
        return [
            'res' => true,
            'msg' => 'Updated Successfully',
            'data' => ''
        ];
    }











    /**
     * Create Speciality
     *
     * @param $request
     * @return array
     */
    public function submitSpeciality($request): array
    {
        $name = $request->name;
        $name_ar = $request->name_ar;
        $speciality= new Specilaties;

        if ($request->hasFile('speciality_image') && $request->file('speciality_image')->isValid()) {
            // If speciality exists, remove the old image
            if ($speciality && $speciality->speciality_image) {
                $oldImagePath = public_path($speciality->speciality_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath); // Deletes the old image file
                }
            }

            $logo = $request->file('speciality_image');
            $tempPath = $logo->store('temp', 'public');
            $destinationPath = '../public/uploads/speciality/';
            $newFileName = time() . '_' . $logo->getClientOriginalName();
            $logo->move($destinationPath, $newFileName);
            \Storage::disk('public')->delete($tempPath);
            $speciality->speciality_image = 'uploads/speciality/'.$newFileName;
        }
        $speciality->name = $name;
        $speciality->name_ar = $name_ar;
        $speciality->save();
        return [
            'res' => true,
            'msg' => 'Created Successfully',
            'data' => $speciality->id
        ];
    }

    /**
     * Fetch Speciality
     *
     * @return array
     */
    public function fetchSpeciality(): array
    {
        return Specilaties::orderByDesc('id')->get()->toArray();
    }

    /**
     * Detail Speciality
     *
     * @param $id
     * @return mixed
     */
    public function editSpeciality($id): mixed
    {
        return Specilaties::where('id', $id)->first();
    }

    /**
     * Update Speciality
     *
     * @param  $request
     * @return array
     */
    public function updateSpeciality($request): array
    {
        $name = $request->name;
        $name_ar = $request->name_ar;
        $speciality= Specilaties::findOrFail($request->id);
        $speciality->name = $name;
        $speciality->name_ar = $name_ar;
        if ($request->hasFile('speciality_image') && $request->file('speciality_image')->isValid()) {
            $logo = $request->file('speciality_image');
            $tempPath = $logo->store('temp', 'public');
            $destinationPath = '../public/uploads/speciality/';
            $newFileName = time() . '_' . $logo->getClientOriginalName();
            $logo->move($destinationPath, $newFileName);
            \Storage::disk('public')->delete($tempPath);
            $speciality->speciality_image = 'uploads/speciality/'.$newFileName;
        }
        $speciality->save();
        return [
            'res' => true,
            'msg' => 'Updated Successfully',
            'data' => ''
        ];
    }

    /**
     * Create Faq
     *
     * @param $request
     * @return array
     */
    public function submitFaq($request): array
    {
        $name = $request->name;
        $faq= new Faq;
        $faq->name = $name;
        $faq->save();
        return [
            'res' => true,
            'msg' => 'Created Successfully',
            'data' => $faq->id
        ];
    }

    /**
     * Fetch Faq
     *
     * @return array
     */
    public function fetchFaq(): array
    {
        return Faq::orderByDesc('id')->get()->toArray();
    }

    /**
     * Detail Faq
     *
     * @param $id
     * @return mixed
     */
    public function editFaq($id): mixed
    {
        return Faq::where('id', $id)->first();
    }

    /**
     * Update Faq
     *
     * @param  $request
     * @return array
     */
    public function updateFaq($request): array
    {
        $name = $request->name;
        $faq= Faq::findOrFail($request->id);
        $faq->name = $name;
        $faq->save();
        return [
            'res' => true,
            'msg' => 'Updated Successfully',
            'data' => ''
        ];
    }

    /**
     * Doctor Request
     *
     * @param $request
     * @return array
     */
    public function doctorRequest($request): array
    {
        $search = $request->search;
        $sortBy = $request->sortBy ?? 'id';
        $sortDirection = $request->sortDirection ?? 'asc';
        $perPage = $request->perPage ?? 20;

        $query = DoctorRequest::query()
            ->join('specialities', 'doctor_requests.speciality', '=', 'specialities.id')
			->leftjoin('cities', 'doctor_requests.city', '=', 'cities.id')
			->leftjoin('municipal', 'doctor_requests.municipality', '=', 'municipal.id')
            ->select(['doctor_requests.*',
					  'specialities.name as speciality_name',
					  'cities.name as city_name',
					  'municipal.name as municipality_name',

					  ]);
		if ($request->request_date) {
			$query->whereDate('doctor_requests.created_at', $request->request_date);
		}

		if ($request->postal_code) {
			$query->where('postal_code', $request->postal_code);
		}

		if ($request->status !== null) {
			$query->where('status', $request->status);
		}

        if ($search) {
            $query->where('first_name', 'LIKE', "%{$search}%");
            $query->orWhere('last_name', 'LIKE', "%{$search}%");
            $query->orWhere('postal_code', 'LIKE', "%{$search}%");
            $query->orWhere('email', 'LIKE', "%{$search}%");
            $query->orWhere('specialities.name', 'LIKE', "%{$search}%");;
        }
        $query->orderBy($sortBy, $sortDirection);
        $doctorRequests = $query->paginate($perPage);

        return [
            'res' => true,
            'msg' => '',
            'list' => $doctorRequests
        ];
    }

    /**
     * Delete Request
     *
     * @param $request
     * @return array
     */
    public function deleteRequest($request): array
    {
        $requests = DoctorRequest::find($request->id);
        if ($requests) {
            $requests->delete();
        }

        return [
            'res' => true,
            'msg' => '',
            'data' => ''
        ];
    }

    /**
     * Approve Request
     *
     * @param $request
     * @return array
     */
    public function approveRequest($request): array
    {
        $requests = DoctorRequest::find($request->id);
        if ($requests) {

            $slugBase = Str::slug($requests->first_name . '-' . $requests->last_name);
            $slug = $slugBase;
            $count = 1;
            while (FrontEndUser::where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . $count;
                $count++;
            }

            $password = Str::random(12);
            $email = $requests->email;
            $name = $requests->first_name.' '.$requests->last_name;
            $user = new FrontEndUser;
            $user->first_name = $requests->first_name;
            $user->last_name = $requests->last_name;
            $user->email = $email;
            $user->type = 'Doctor';
            $user->status = 1;
            $user->postal_code = $requests->postal_code;
            $user->cell_phone = $requests->cell_phone;
            $user->speciality = $requests->speciality;

			$user->city_id = $requests->city;
			$user->municipality_id = $requests->municipality;

            $user->password = Hash::make($password);
            $user->slug = $slug;
            $user->save();

            $webUrl = env('MAIN_URL');
            $appName = env('APP_NAME');
            $mail = new PHPMailer(true);
            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($email,$name);
            //$mail->addAddress('sumon@properbounce.com',$name);
			$mail->Subject = $appName.'::Your request has been approve!';
            $htmlContent = View::make('email.approveRequest', ['site_url' => $webUrl, 'site_name' => $appName, 'name' => $name,'email'=>$email,'password'=>$password])->render();
            $mail->isHTML(true);
            $mail->Body = $htmlContent;
            $mail->send();
            $requests->delete();
        }

        return [
            'res' => true,
            'msg' => '',
            'data' => ''
        ];
    }


    /**
     * Approve Request
     *
     * @param $request
     * @return array
     */
    public function deleteHelp($request): array
    {

        DB::table('helpCenter')->where('id', $request->id)->delete();
        return [
            'res' => true,
            'msg' => '',
            'data' => ''
        ];
    }

    /**
     * Welcome
     *
     * @param $id
     * @return mixed
     */
    public function welcome(): mixed
    {
        //return Welcome::where('id', $id)->first();
		//return Welcome::orderByDesc('id')->get()->toArray();
		return Welcome::orderBy('id', 'asc')->get()->toArray();

    }



    /**
     * Update Faq
     *
     * @param  $request
     * @return array
     */
    public function updateWelcome($request): array
    {
		//echo "<pre>";print_r($request);exit;
        $lang_en = $request->editorContentEn;
        $lang_ar = $request->editorContentAr;
        $lang_fr = $request->editorContentFr;
        $faq= Welcome::findOrFail($request->id);
        $faq->lang_ar = $lang_ar;
        $faq->lang_fr = $lang_fr;
        $faq->lang_en = $lang_en;
        $faq->save();
        return [
            'res' => true,
            'msg' => 'Updated Successfully',
            'data' => ''
        ];
    }

}
