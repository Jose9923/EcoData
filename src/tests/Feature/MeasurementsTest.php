<?php

namespace Tests\Feature;

use App\Exports\PhysicalVariableRecordsExport;
use App\Models\Course;
use App\Models\Grade;
use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\PhysicalVariableRecord;
use App\Models\School;
use App\Models\Sensor;
use App\Models\User;
use App\Models\WeatherStation;
use App\Services\MeasurementCsv;
use App\Services\MeasurementService;
use App\Support\MeasurementDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeasurementsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private PhysicalVariable $variable;

    private WeatherStation $station;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['super_admin', 'admin_colegio', 'docente', 'estudiante'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->school = School::create(['name' => 'Colegio Prueba', 'slug' => 'prueba', 'is_active' => true]);
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true, 'document_number' => '123987654']);
        $this->teacher->assignRole('docente');
        $category = PhysicalVariableCategory::create(['name' => 'Clima', 'slug' => 'clima', 'is_active' => true]);
        $this->variable = PhysicalVariable::create(['school_id' => $this->school->id, 'category_id' => $category->id, 'name' => 'Temperatura', 'slug' => 'temperatura', 'unit' => '°C', 'data_type' => 'decimal', 'min_value' => '-100', 'max_value' => '100', 'decimals' => 4, 'is_active' => true]);
        $this->station = WeatherStation::create(['school_id' => $this->school->id, 'name' => 'Estación prueba', 'code' => 'EP', 'is_active' => true]);
        Sensor::create(['weather_station_id' => $this->station->id, 'physical_variable_id' => $this->variable->id, 'name' => 'Sensor', 'code' => 'S1', 'is_active' => true, 'status' => 'operativo']);
    }

    private function input(array $extra = []): array
    {
        return array_replace(['school_id' => $this->school->id, 'grade_id' => null, 'course_id' => null, 'weather_station_id' => $this->station->id, 'source_type' => 'station', 'recorded_at' => '2026-10-26 14:30', 'observations' => null, 'values' => [$this->variable->id => '21.1234']], $extra);
    }

    private function save(array $extra = [], ?User $actor = null): PhysicalVariableRecord
    {
        return app(MeasurementService::class)->save($actor ?? $this->teacher, $this->input($extra));
    }

    private function student(array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('estudiante');

        return $user;
    }

    private function csv(string $rows = "2026-10-26 14:30,21.1234,Ejemplo\n"): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('mediciones.csv', "recorded_at,temperatura [°C],observations\n".$rows);
    }

    public function test_update_preserves_hidden_and_inactive_values_and_their_ids_and_audits_changes(): void
    {
        $other = $this->variable->replicate();
        $other->slug = 'humedad';
        $other->name = 'Humedad';
        $other->save();
        $record = $this->save(['values' => [$this->variable->id => '21.1234', $other->id => '45.0000']]);
        $ids = $record->values()->pluck('id', 'physical_variable_id')->all();
        $other->update(['is_active' => false]);
        $this->actingAs($this->teacher)->put(route('admin.physical-variable-records.update', $record), $this->input(['values' => [$this->variable->id => '22.0001'], 'weather_station_id' => null, 'source_type' => 'manual']))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($ids, $record->values()->pluck('id', 'physical_variable_id')->all());
        $this->assertSame('45.0000', $record->values()->where('physical_variable_id', $other->id)->first()->value_numeric);
        $this->assertNull($record->fresh()->weather_station_id);
        $this->assertSame('manual', $record->fresh()->source_type);
        $audit = $record->audits()->first();
        $this->assertSame('updated', $audit->action);
        $this->assertSame($this->teacher->id, $audit->user_id);
        $this->assertSame('21.1234', $audit->before['values'][$this->variable->id]['value']);
        $this->assertSame('22.0001', $audit->after['values'][$this->variable->id]['value']);
        $this->actingAs($this->teacher)->get(route('admin.physical-variable-records.show', $record))->assertOk()->assertSee('Historial de cambios')->assertSee('22.0001');
    }

    public function test_blank_value_preserves_measurement_and_only_explicit_removal_deletes(): void
    {
        $other = $this->variable->replicate();
        $other->slug = 'otro';
        $other->save();
        $record = $this->save(['values' => [$this->variable->id => '21', $other->id => '45']]);
        app(MeasurementService::class)->save($this->teacher, $this->input(['values' => [$this->variable->id => ''], 'remove_values' => [$other->id]]), $record);
        $this->assertCount(1, $record->fresh()->values);
        $this->assertSame('21.0000', $record->fresh()->values->first()->value_numeric);
        $this->actingAs($this->teacher)->put(route('admin.physical-variable-records.update', $record), $this->input(['values' => [], 'remove_values' => [$this->variable->id]]))->assertSessionHasErrors('values');
        $this->assertDatabaseCount('physical_variable_record_values', 1);
    }

    public function test_edit_preloads_station_source_and_inactive_values(): void
    {
        $record = $this->save();
        $this->variable->update(['is_active' => false]);
        $this->station->update(['is_active' => false]);
        $this->actingAs($this->teacher)->get(route('admin.physical-variable-records.edit', $record))->assertOk()
            ->assertViewHas('selectedWeatherStationId', $this->station->id)
            ->assertViewHas('selectedSourceType', 'station')
            ->assertViewHas('variables', fn ($variables) => $variables->contains('id', $this->variable->id))
            ->assertViewHas('weatherStations', fn ($stations) => $stations->contains('id', $this->station->id));
    }

    public function test_unchanged_update_does_not_create_a_fake_audit(): void
    {
        $record = $this->save();
        app(MeasurementService::class)->save($this->teacher, $this->input(), $record);
        $this->assertSame(1, $record->audits()->count());
    }

    public function test_precision_ranges_unknown_variables_and_school_context_are_rejected(): void
    {
        foreach (['21.12345', '101', '1e2', 'NaN'] as $value) {
            $this->actingAs($this->teacher)->post(route('admin.physical-variable-records.store'), $this->input(['values' => [$this->variable->id => $value]]))->assertSessionHasErrors('values.'.$this->variable->id);
        }
        $this->post(route('admin.physical-variable-records.store'), $this->input(['values' => [999999 => '2']]))->assertSessionHasErrors('values.999999');
        $this->post(route('admin.physical-variable-records.store'), $this->input(['school_id' => 999999]))->assertSessionHasErrors('school_id');
        $this->assertDatabaseCount('physical_variable_records', 0);
    }

    public function test_integer_and_decimal_values_are_exact_without_float_conversion(): void
    {
        $this->assertSame('99999999999.9999', MeasurementDecimal::normalize('99999999999.9999'));
        $this->assertSame(-1, MeasurementDecimal::compare('-0.0001', '0'));
        $this->assertSame('0.0001', MeasurementDecimal::format('0.0001', 4));
        $this->variable->update(['data_type' => 'integer', 'decimals' => 0]);
        $this->actingAs($this->teacher)->post(route('admin.physical-variable-records.store'), $this->input(['values' => [$this->variable->id => '2.1']]))->assertSessionHasErrors('values.'.$this->variable->id);
        $record = $this->save(['values' => [$this->variable->id => '0']]);
        $this->assertSame('0.0000', $record->values->first()->value_numeric);
    }

    public function test_student_visibility_matches_own_general_grade_and_course_and_denies_other_school(): void
    {
        $grade = Grade::create(['school_id' => $this->school->id, 'name' => '5', 'is_active' => true]);
        $course = Course::create(['school_id' => $this->school->id, 'grade_id' => $grade->id, 'name' => 'A', 'is_active' => true]);
        $otherCourse = Course::create(['school_id' => $this->school->id, 'grade_id' => $grade->id, 'name' => 'B', 'is_active' => true]);
        $student = $this->student(['grade_id' => $grade->id, 'course_id' => $course->id]);
        $general = $this->save();
        $matched = $this->save(['grade_id' => $grade->id, 'course_id' => $course->id]);
        $excluded = $this->save(['grade_id' => $grade->id, 'course_id' => $otherCourse->id]);
        $own = $this->save([], $student);
        $own->update(['course_id' => $otherCourse->id]); // Own historical assignment remains visible.
        $ids = PhysicalVariableRecord::visibleTo($student)->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$general->id, $matched->id, $own->id], $ids);
        $this->actingAs($student)->get(route('admin.physical-variable-records.show', $excluded))->assertForbidden();
        $this->get(route('admin.physical-variable-records.show', $general))->assertOk()->assertDontSee($this->teacher->email)->assertDontSee($this->teacher->document_number)->assertDontSee('Historial de cambios');
        $this->get(route('admin.physical-variable-records.index'))->assertOk()
            ->assertDontSee($this->teacher->email)->assertDontSee($this->teacher->document_number)->assertDontSee($this->teacher->name);
        foreach ([$this->teacher->email, $this->teacher->document_number, $this->teacher->name] as $identity) {
            $this->get(route('admin.physical-variable-records.index', ['search' => $identity]))->assertOk()
                ->assertViewHas('records', fn ($records) => $records->total() === 0);
        }
        $this->put(route('admin.physical-variable-records.update', $general), $this->input())->assertForbidden();
        $otherSchool = School::create(['name' => 'Otro', 'slug' => 'otro']);
        $student->update(['school_id' => $otherSchool->id]);
        $this->assertSame(0, PhysicalVariableRecord::visibleTo($student)->count());
    }

    public function test_student_without_assignment_sees_only_own_and_general_measurements(): void
    {
        $student = $this->student();
        $grade = Grade::create(['school_id' => $this->school->id, 'name' => '6']);
        $general = $this->save();
        $this->save(['grade_id' => $grade->id]);
        $this->assertSame([$general->id], PhysicalVariableRecord::visibleTo($student)->pluck('id')->all());
    }

    public function test_export_omits_identities_by_default_and_prevents_excel_formula_execution(): void
    {
        $record = $this->save(['observations' => '=HYPERLINK("https://example.invalid","x")']);
        $export = new PhysicalVariableRecordsExport(PhysicalVariableRecord::query());
        $json = json_encode($export->collection());
        $this->assertStringNotContainsString($this->teacher->email, $json);
        $this->assertStringNotContainsString($this->teacher->document_number, $json);
        $this->assertNotContains('Correo usuario', $export->headings());
        $this->assertStringContainsString('21.1234', $json);
        $sheet = (new Spreadsheet)->getActiveSheet();
        $export->bindValue($sheet->getCell('A1'), $record->observations);
        $this->assertSame('s', $sheet->getCell('A1')->getDataType());
        $staff = new PhysicalVariableRecordsExport(PhysicalVariableRecord::query(), null, null, null, true);
        $this->assertContains('Correo usuario', $staff->headings());
    }

    public function test_csv_preview_is_read_only_and_commit_is_atomic_and_replay_safe(): void
    {
        $this->actingAs($this->teacher)->post(route('admin.physical-variable-record-imports.preview'), ['weather_station_id' => $this->station->id, 'delimiter' => ',', 'csv_file' => $this->csv()])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('physical_variable_records', 0);
        $this->assertDatabaseCount('physical_variable_record_audits', 0);
        $preview = session('measurement_csv_preview');
        $this->assertSame([], $preview['errors']);
        $this->get(route('admin.physical-variable-record-imports.create'))->assertOk()->assertSee('21.1234')->assertSee('°C');
        $this->post(route('admin.physical-variable-record-imports.commit'), ['token' => $preview['token']])->assertSessionHasNoErrors()->assertSessionHas('import_result.count', 1);
        $this->assertDatabaseCount('physical_variable_records', 1);
        $this->assertDatabaseCount('measurement_import_receipts', 1);
        $this->assertDatabaseHas('physical_variable_records', ['source_type' => 'csv', 'weather_station_id' => $this->station->id]);
        $this->withSession(['measurement_csv_preview' => $preview])->post(route('admin.physical-variable-record-imports.commit'), ['token' => $preview['token']])->assertSessionHasErrors('token');
        $this->assertDatabaseCount('physical_variable_records', 1);
    }

    public function test_csv_reports_invalid_rows_and_cannot_partially_import(): void
    {
        $this->actingAs($this->teacher)->post(route('admin.physical-variable-record-imports.preview'), ['weather_station_id' => $this->station->id, 'delimiter' => ',', 'csv_file' => $this->csv("2026-10-26 14:30,21.1234,OK\n2026-10-26 14:31,999,Fuera de rango\n")])->assertSessionHasNoErrors();
        $preview = session('measurement_csv_preview');
        $this->assertArrayHasKey(3, $preview['errors']);
        $this->post(route('admin.physical-variable-record-imports.commit'), ['token' => $preview['token']])->assertSessionHasErrors('csv_file');
        $this->assertDatabaseCount('physical_variable_records', 0);
        $this->assertDatabaseCount('measurement_import_receipts', 0);
    }

    public function test_csv_revalidates_station_permissions_variables_and_expiry_on_confirmation(): void
    {
        $this->actingAs($this->teacher)->post(route('admin.physical-variable-record-imports.preview'), ['weather_station_id' => $this->station->id, 'delimiter' => ',', 'csv_file' => $this->csv()]);
        $preview = session('measurement_csv_preview');
        $this->variable->update(['unit' => 'K']);
        $this->post(route('admin.physical-variable-record-imports.commit'), ['token' => $preview['token']])->assertSessionHasErrors('csv_file');
        $preview['expires_at'] = now()->subMinute()->timestamp;
        $this->withSession(['measurement_csv_preview' => $preview])->post(route('admin.physical-variable-record-imports.commit'), ['token' => $preview['token']])->assertSessionHasErrors('token');
        $this->assertDatabaseCount('physical_variable_records', 0);
    }

    public function test_csv_validates_exact_columns_units_row_limits_and_delimiter(): void
    {
        $csv = app(MeasurementCsv::class);
        foreach ([
            "recorded_at,temperatura [K]\n2026-10-26 14:30,20\n",
            "recorded_at,temperatura [°C],temperatura [°C]\n2026-10-26 14:30,20,21\n",
            "recorded_at,temperatura [°C]\n".str_repeat("2026-10-26 14:30,20\n", 501),
        ] as $content) {
            try {
                $csv->preview($this->teacher, $this->station, $content, ',');
                $this->fail('Expected invalid CSV');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('csv_file', $error->errors());
            }
        }
        $result = $csv->preview($this->teacher, $this->station, "\xEF\xBB\xBFrecorded_at;temperatura [°C]\n2026-10-26 14:30;20.1234\n", ';');
        $this->assertSame([], $result['errors']);
        $this->assertSame("'=SUM(1)", MeasurementCsv::safeCell('=SUM(1)'));
    }

    public function test_template_is_scoped_and_student_cannot_import(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('admin.physical-variable-record-imports.template', ['weather_station_id' => $this->station->id]));
        $response->assertOk()->assertDownload('plantilla_mediciones.csv');
        $this->assertStringContainsString('temperatura [°C]', $response->streamedContent());
        $other = $this->student(['school_id' => School::create(['name' => 'Otro', 'slug' => 'otro'])->id]);
        $this->actingAs($other)->get(route('admin.physical-variable-record-imports.create'))->assertForbidden();
        $teacher = User::factory()->create(['school_id' => $other->school_id]);
        $teacher->assignRole('docente');
        $this->actingAs($teacher)->get(route('admin.physical-variable-record-imports.template', ['weather_station_id' => $this->station->id]))->assertNotFound();
    }

    public function test_existing_precision_is_preserved_if_configuration_is_reduced(): void
    {
        $record = $this->save();
        $this->variable->update(['decimals' => 2, 'max_value' => '20']);
        $this->actingAs($this->teacher)->put(route('admin.physical-variable-records.update', $record), $this->input(['observations' => 'Corrección de contexto']))->assertSessionHasNoErrors();
        $this->assertSame('21.1234', $record->fresh()->values->first()->value_numeric);
    }

    public function test_foreign_school_variables_stations_and_mismatched_courses_are_rejected(): void
    {
        $otherSchool = School::create(['name' => 'Otro colegio', 'slug' => 'otro']);
        $variable = $this->variable->replicate();
        $variable->school_id = $otherSchool->id;
        $variable->save();
        $station = WeatherStation::create(['school_id' => $otherSchool->id, 'name' => 'Otra estación', 'code' => 'OTHER', 'is_active' => true]);
        $grade = Grade::create(['school_id' => $this->school->id, 'name' => '7']);
        $course = Course::create(['school_id' => $this->school->id, 'grade_id' => $grade->id, 'name' => 'A']);
        $this->actingAs($this->teacher)->post(route('admin.physical-variable-records.store'), $this->input(['values' => [$variable->id => '20']]))->assertSessionHasErrors('values.'.$variable->id);
        $this->post(route('admin.physical-variable-records.store'), $this->input(['weather_station_id' => $station->id]))->assertSessionHasErrors('weather_station_id');
        $this->post(route('admin.physical-variable-records.store'), $this->input(['course_id' => $course->id]))->assertSessionHasErrors('course_id');
        $this->assertDatabaseCount('physical_variable_records', 0);
    }

    public function test_variable_configuration_rejects_more_than_four_decimals_and_preserves_measurement_units(): void
    {
        $admin = User::factory()->create(['school_id' => $this->school->id]);
        $admin->assignRole('admin_colegio');
        $payload = ['school_id' => $this->school->id, 'category_id' => $this->variable->category_id, 'name' => 'Precisa', 'slug' => 'precisa', 'unit' => 'm', 'data_type' => 'decimal', 'min_value' => '0', 'max_value' => '100', 'decimals' => 5, 'is_active' => true];
        $this->actingAs($admin)->post(route('admin.physical-variables.store'), $payload)->assertSessionHasErrors('decimals');
        $this->save();
        $this->put(route('admin.physical-variables.update', $this->variable), array_replace($payload, ['slug' => $this->variable->slug, 'decimals' => 4]))->assertSessionHasErrors('unit');
    }

    public function test_csv_size_limit_and_invalid_boolean_and_date_are_rejected(): void
    {
        $this->actingAs($this->teacher)->post(route('admin.physical-variable-record-imports.preview'), ['weather_station_id' => $this->station->id, 'delimiter' => ',', 'csv_file' => UploadedFile::fake()->create('big.csv', 2049)])->assertSessionHasErrors('csv_file');
        $this->variable->update(['data_type' => 'boolean']);
        $this->post(route('admin.physical-variable-records.store'), $this->input(['values' => [$this->variable->id => 'yes']]))->assertSessionHasErrors('values.'.$this->variable->id);
        $this->variable->update(['data_type' => 'date']);
        $this->post(route('admin.physical-variable-records.store'), $this->input(['values' => [$this->variable->id => '2026-02-31']]))->assertSessionHasErrors('values.'.$this->variable->id);
        $this->assertDatabaseCount('physical_variable_records', 0);
    }

    public function test_csv_database_failure_rolls_back_records_receipt_and_audits(): void
    {
        $this->actingAs($this->teacher)->post(route('admin.physical-variable-record-imports.preview'), ['weather_station_id' => $this->station->id, 'delimiter' => ',', 'csv_file' => $this->csv("2026-10-26 14:30,20,Primera\n2026-10-26 14:31,21,Segunda\n")]);
        $token = session('measurement_csv_preview.token');
        $created = 0;
        PhysicalVariableRecord::creating(function () use (&$created) {
            if (++$created === 2) {
                throw new \RuntimeException('Simulated persistence failure');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->post(route('admin.physical-variable-record-imports.commit'), ['token' => $token]);
            $this->fail('Expected persistence failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated persistence failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('physical_variable_records', 0);
        $this->assertDatabaseCount('physical_variable_record_audits', 0);
        $this->assertDatabaseCount('measurement_import_receipts', 0);
    }

    public function test_actual_xlsx_contains_literal_formula_text_and_no_student_identity_columns(): void
    {
        $this->save(['observations' => '=1+1']);
        $export = new PhysicalVariableRecordsExport(PhysicalVariableRecord::query(), $this->school, 'Estudiante');
        $binary = \Maatwebsite\Excel\Facades\Excel::raw($export, Excel::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'measurement-export-');
        try {
            file_put_contents($path, $binary);
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame('=1+1', $sheet->getCell('J9')->getValue());
            $this->assertSame('s', $sheet->getCell('J9')->getDataType());
            $this->assertStringNotContainsString($this->teacher->document_number, json_encode($sheet->toArray()));
            $this->assertStringNotContainsString($this->teacher->email, json_encode($sheet->toArray()));
        } finally {
            unlink($path);
        }
    }
}
