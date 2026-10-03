<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleModelsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Include the module User model file from the root project
        require_once base_path('../Modules/Home/app/Models/DoctorRequest.php');
        require_once base_path('../Modules/Home/app/Models/Faq.php');
        require_once base_path('../Modules/Home/app/Models/Language.php');
        require_once base_path('../Modules/Home/app/Models/Specilaties.php');
        require_once base_path('../Modules/Home/app/Models/Welcome.php');
        //require_once base_path('../Modules/Doctor/app/Models/TempDoctor.php');
        require_once base_path('../Modules/Home/app/Models/DoctorTemplate.php');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
