<?php
require '/qa/ui-fixture.php';
use App\Models\{School, User, Grade, Course, PhysicalVariable, PhysicalVariableRecord, WeatherStation};
$school=School::where('slug','colegio-qa-1')->firstOrFail();
$teacher=User::where('email','qa-teacher1@example.test')->firstOrFail();
$student=User::where('email','qa-student1@example.test')->firstOrFail();
$variable=PhysicalVariable::where('school_id',$school->id)->firstOrFail();
$station=WeatherStation::where('school_id',$school->id)->firstOrFail();
$second=WeatherStation::create(['school_id'=>$school->id,'name'=>'Estación del jardín y zona de recuperación ambiental','code'=>'QA-JARDIN','is_active'=>true]);
$variable->update(['min_value'=>18,'max_value'=>32,'unit'=>'°C']);
$add=function($date,$value,$stationId,$who=null) use ($school,$teacher,$student,$variable) {
    $record=PhysicalVariableRecord::create(['school_id'=>$school->id,'grade_id'=>$student->grade_id,'course_id'=>$student->course_id,'weather_station_id'=>$stationId,'source_type'=>$stationId?'station':'manual','user_id'=>($who??$teacher)->id,'recorded_at'=>$date.' 12:00:00','observations'=>'Datos ficticios del módulo de gráficos']);
    $record->values()->create(['physical_variable_id'=>$variable->id,'value_numeric'=>$value]);
};
for($day=1;$day<=14;$day++) {
    $date=sprintf('2026-10-%02d',$day);
    if(!in_array($day,[5,6])) {
        $add($date,round(23+sin($day/2)*5+$day/3,2),$day%3===0?null:($day%2?$station->id:$second->id));
        if($day%4===0) $add($date,34.5,$station->id,$student);
    }
    $previous=Illuminate\Support\Carbon::parse($date)->subDays(14)->toDateString();
    $add($previous,round(20+sin($day/2)*4,2),$station->id);
}
$constant=$variable->replicate();
$constant->name='Nivel constante cero';
$constant->slug='constante-cero';
$constant->unit='mm';
$constant->min_value=null;
$constant->max_value=null;
$constant->save();
$record=PhysicalVariableRecord::create(['school_id'=>$school->id,'grade_id'=>null,'course_id'=>null,'weather_station_id'=>null,'source_type'=>'manual','user_id'=>$teacher->id,'recorded_at'=>'2026-10-01 08:00:00']);
$record->values()->create(['physical_variable_id'=>$constant->id,'value_numeric'=>0]);
echo 'CHARTS_FIXTURE_READY'.PHP_EOL;
