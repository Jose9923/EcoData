<?php
require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== '/tmp/ecodata-preview.sqlite') {
    throw new RuntimeException('Refusing to initialize any database except the ephemeral QA SQLite file.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\Notification::fake();
use App\Models\{School, User, Grade, Course, PhysicalVariableCategory, PhysicalVariable, PhysicalVariableRecord, WeatherStation, Sensor, FieldDiaryActivity};
use Spatie\Permission\Models\Role;
foreach (['docente','estudiante','admin_colegio','super_admin'] as $role) Role::create(['name'=>$role,'guard_name'=>'web']);
$category=PhysicalVariableCategory::create(['name'=>'Clima de prueba','slug'=>'clima-qa','is_active'=>true]);
foreach ([1,2] as $n) {
    $school=School::create(['name'=>'Colegio QA '.$n,'slug'=>'colegio-qa-'.$n,'is_active'=>true,'primary_color'=>'#166534','secondary_color'=>'#0f172a']);
    $grade=Grade::create(['school_id'=>$school->id,'name'=>'8','label'=>'Octavo','is_active'=>true]);
    $course=Course::create(['school_id'=>$school->id,'grade_id'=>$grade->id,'name'=>'8A','label'=>'8 A','is_active'=>true]);
    $users=[];
    foreach (['docente'=>'teacher','estudiante'=>'student'] as $role=>$alias) {
        $user=User::create(['name'=>ucfirst($alias).' QA '.$n,'email'=>'qa-'.$alias.$n.'@example.test','password'=>Illuminate\Support\Facades\Hash::make('QA-Temporal-2026!'),'email_verified_at'=>now(),'school_id'=>$school->id,'grade_id'=>$grade->id,'course_id'=>$course->id,'is_active'=>true]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole($role);
        $users[$role]=$user;
    }
    $station=WeatherStation::create(['school_id'=>$school->id,'responsible_user_id'=>$users['docente']->id,'name'=>'Estación QA '.$n,'code'=>'QA-EST-'.$n,'location_name'=>'Patio de pruebas','is_active'=>true]);
    $variable=PhysicalVariable::create(['school_id'=>$school->id,'category_id'=>$category->id,'name'=>'Temperatura QA','slug'=>'temperatura','unit'=>'C','data_type'=>'decimal','min_value'=>-20,'max_value'=>60,'decimals'=>2,'is_active'=>true]);
    Sensor::create(['weather_station_id'=>$station->id,'physical_variable_id'=>$variable->id,'name'=>'Termómetro QA','code'=>'QA-SEN-'.$n,'status'=>'operativo','is_active'=>true]);
    for ($day=6;$day>=0;$day--) {
        $record=PhysicalVariableRecord::create(['school_id'=>$school->id,'grade_id'=>$grade->id,'course_id'=>$course->id,'weather_station_id'=>$station->id,'source_type'=>'manual','user_id'=>$day%2 ? $users['docente']->id : $users['estudiante']->id,'recorded_at'=>now()->subDays($day),'observations'=>'Medición ficticia para QA']);
        $record->values()->create(['physical_variable_id'=>$variable->id,'value_numeric'=>20.5+$day]);
    }
    $activity=FieldDiaryActivity::create(['school_id'=>$school->id,'grade_id'=>$grade->id,'course_id'=>$course->id,'created_by'=>$users['docente']->id,'weather_station_id'=>$station->id,'title'=>'Observación ambiental QA '.$n,'description'=>'Describe lo que observas en el patio y guarda tu respuesta. Estos datos son ficticios.','entry_type'=>'observacion','starts_at'=>today()->subDay(),'ends_at'=>today()->addDays(5),'is_active'=>true]);
    $activity->questions()->create(['question_text'=>'¿Qué observaste en el patio?','question_type'=>'textarea','order'=>1,'is_required'=>true]);
    $activity->questions()->create(['question_text'=>'Selecciona lo observado','question_type'=>'checkbox','options'=>['Árboles','Aves'],'order'=>2,'is_required'=>false]);
    $activity->questions()->create(['question_text'=>'Evidencia opcional','question_type'=>'file','order'=>3,'is_required'=>false]);
}
echo 'QA_FIXTURE_READY'.PHP_EOL;
