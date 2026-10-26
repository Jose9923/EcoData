<?php

namespace Tests\Feature;

use App\Exports\PhysicalVariableRecordsExport;
use App\Models\Course;
use App\Models\Grade;
use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\PhysicalVariableRecord;
use App\Models\School;
use App\Models\User;
use App\Models\WeatherStation;
use App\Notifications\PhysicalRecordsReportNotification;
use App\Services\PhysicalRecordReports;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportMailAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private Grade $grade;

    private Grade $otherGrade;

    private Course $course;

    private Course $otherCourse;

    private User $student;

    private User $teacher;

    private PhysicalVariable $variable;

    private PhysicalVariable $foreignVariable;

    private PhysicalVariable $textVariable;

    private WeatherStation $station;

    private WeatherStation $foreignStation;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        foreach (['super_admin', 'admin_colegio', 'docente', 'estudiante'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->school = School::create(['name' => 'Visible School', 'slug' => 'visible-school', 'is_active' => true]);
        $this->otherSchool = School::create(['name' => 'Foreign School', 'slug' => 'foreign-school', 'is_active' => true]);
        $this->grade = Grade::create(['school_id' => $this->school->id, 'name' => '6', 'is_active' => true]);
        $this->otherGrade = Grade::create(['school_id' => $this->school->id, 'name' => '7', 'is_active' => true]);
        $this->course = Course::create(['school_id' => $this->school->id, 'grade_id' => $this->grade->id, 'name' => '6-A', 'is_active' => true]);
        $this->otherCourse = Course::create(['school_id' => $this->school->id, 'grade_id' => $this->otherGrade->id, 'name' => '7-A', 'is_active' => true]);
        $this->student = User::factory()->create(['is_active' => true, 'school_id' => $this->school->id,
            'grade_id' => $this->grade->id, 'course_id' => $this->course->id]);
        $this->student->assignRole('estudiante');
        $this->teacher = User::factory()->create(['is_active' => true, 'school_id' => $this->school->id]);
        $this->teacher->assignRole('docente');
        $category = PhysicalVariableCategory::create(['name' => 'Climate', 'slug' => 'climate', 'is_active' => true]);
        $this->variable = PhysicalVariable::create(['school_id' => $this->school->id, 'category_id' => $category->id,
            'name' => 'Temperature', 'slug' => 'temperature', 'data_type' => 'decimal',
            'unit' => 'C', 'decimals' => 2, 'min_value' => 0, 'max_value' => 40, 'is_active' => true]);
        $this->foreignVariable = PhysicalVariable::create(['school_id' => $this->otherSchool->id,
            'category_id' => $category->id, 'name' => 'Foreign temperature', 'slug' => 'temperature',
            'data_type' => 'decimal', 'decimals' => 2, 'is_active' => true]);
        $this->textVariable = PhysicalVariable::create(['school_id' => $this->school->id,
            'category_id' => $category->id, 'name' => 'Notes', 'slug' => 'notes',
            'data_type' => 'text', 'is_active' => true]);
        $this->station = WeatherStation::create(['school_id' => $this->school->id, 'name' => 'Visible station', 'code' => 'A', 'is_active' => true]);
        $this->foreignStation = WeatherStation::create(['school_id' => $this->otherSchool->id, 'name' => 'Foreign station', 'code' => 'B', 'is_active' => true]);
    }

    private function filters(): array
    {
        return ['date_from' => '2026-10-01', 'date_to' => '2026-10-02', 'variable_id' => $this->variable->id];
    }

    private function record(string $label, float $value, array $attributes = [], ?PhysicalVariable $variable = null): PhysicalVariableRecord
    {
        $record = PhysicalVariableRecord::create(array_replace([
            'school_id' => $this->school->id, 'grade_id' => $this->grade->id,
            'course_id' => $this->course->id, 'weather_station_id' => $this->station->id,
            'source_type' => 'manual', 'user_id' => $this->teacher->id,
            'recorded_at' => '2026-10-01 12:00:00', 'observations' => $label,
        ], $attributes));
        $record->values()->create(['physical_variable_id' => ($variable ?? $this->variable)->id, 'value_numeric' => $value]);

        return $record;
    }

    private function seedVisibility(): array
    {
        $assigned = $this->record('assigned', 10);
        $own = $this->record('own', 30, ['user_id' => $this->student->id,
            'grade_id' => $this->otherGrade->id, 'course_id' => $this->otherCourse->id]);
        $general = $this->record('general', 50, ['grade_id' => null, 'course_id' => null,
            'recorded_at' => '2026-10-02 23:59:59']);
        $this->record('hidden', 1000, ['grade_id' => $this->otherGrade->id, 'course_id' => $this->otherCourse->id]);
        $this->record('foreign', 2000, ['school_id' => $this->otherSchool->id,
            'grade_id' => null, 'course_id' => null, 'weather_station_id' => $this->foreignStation->id],
            $this->foreignVariable);

        return [$assigned, $own, $general];
    }

    public function test_report_visibility_enforces_school_assignment_and_ownership(): void
    {
        $visible = $this->seedVisibility();
        $reports = app(PhysicalRecordReports::class);
        $ids = $reports->query($this->student, $this->filters())->orderBy('id')->pluck('id')->all();
        $this->assertSame(array_map(fn ($record) => $record->id, $visible), $ids);
        $this->assertCount(4, $reports->query($this->teacher, $this->filters())->get());
        $this->actingAs($this->student)->get(route('reports.mail', $this->filters()))
            ->assertOk()->assertViewHas('count', 3)
            ->assertDontSee('Foreign School')->assertDontSee('Foreign temperature')->assertDontSee('Foreign station');
    }

    public function test_analytics_compares_equal_periods_and_excludes_hidden_values(): void
    {
        $this->seedVisibility();
        $this->record('previous-one', 4, ['recorded_at' => '2026-09-29 12:00:00']);
        $this->record('previous-two', 8, ['recorded_at' => '2026-09-30 23:59:59']);
        $this->record('too-early', 999, ['recorded_at' => '2026-09-28 23:59:59']);
        $this->record('too-late', 999, ['recorded_at' => '2026-10-03 00:00:00']);
        $response = $this->actingAs($this->student)->get(route('analytics.index', $this->filters()))->assertOk();
        $current = $response->viewData('current');
        $previous = $response->viewData('previous');
        $this->assertSame(3, (int) $current->count);
        $this->assertEquals(30, $current->average);
        $this->assertEquals(10, $current->minimum);
        $this->assertEquals(50, $current->maximum);
        $this->assertSame(2, (int) $previous->count);
        $this->assertEquals(6, $previous->average);
        $this->assertSame('2026-09-29', $response->viewData('previousFilters')['date_from']);
        $this->assertSame('2026-09-30', $response->viewData('previousFilters')['date_to']);
        $this->assertSame(1, $response->viewData('outside'));
        $series = $response->viewData('series');
        $this->assertCount(2, $series);
        $this->assertEquals(20, $series[0]->average);
        $this->assertEquals(50, $series[1]->average);
    }

    public function test_analytics_rejects_foreign_and_non_numeric_variables_and_invalid_periods(): void
    {
        $this->actingAs($this->student);
        foreach ([$this->foreignVariable->id, $this->textVariable->id] as $variableId) {
            $this->get(route('analytics.index', array_replace($this->filters(), ['variable_id' => $variableId])))
                ->assertSessionHasErrors('variable_id');
        }
        $this->get(route('analytics.index', $this->filters() + ['weather_station_id' => $this->foreignStation->id]))
            ->assertSessionHasErrors('weather_station_id');
        $this->get(route('analytics.index', array_replace($this->filters(), ['date_from' => '2026-10-03'])))
            ->assertSessionHasErrors('date_to');
        $this->get(route('analytics.index', array_replace($this->filters(), ['date_from' => '2025-01-01'])))
            ->assertSessionHasErrors('date_to');
        $this->get(route('analytics.index'))->assertOk()->assertViewHas('current', null);
    }

    public function test_station_filter_limits_analytics_without_cross_school_access(): void
    {
        $this->record('at-station', 10);
        $this->record('without-station', 30, ['weather_station_id' => null]);
        $response = $this->actingAs($this->student)->get(route('analytics.index',
            $this->filters() + ['weather_station_id' => $this->station->id]))->assertOk();
        $this->assertSame(1, (int) $response->viewData('current')->count);
        $this->assertEquals(10, $response->viewData('current')->average);
    }

    public function test_report_is_queued_only_to_current_verified_user_and_student_export_omits_identity(): void
    {
        $this->seedVisibility();
        $this->actingAs($this->student)->post(route('reports.mail.send'), $this->filters() + [
            'email' => 'outside@example.test', 'recipient' => 'outside@example.test',
        ])->assertRedirect(route('reports.mail', $this->filters()))->assertSessionHas('success');
        $notification = Notification::sent($this->student, PhysicalRecordsReportNotification::class)->sole();
        Notification::assertCount(1);
        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertInstanceOf(ShouldBeEncrypted::class, $notification);
        $this->assertTrue($notification->afterCommit);
        $this->assertSame($this->student->email, $notification->requestedEmail);
        $this->assertSame($this->school->id, $notification->requestedSchoolId);
        $this->assertTrue($notification->shouldSend($this->student, 'mail'));
        Excel::shouldReceive('raw')->once()->withArgs(function (PhysicalVariableRecordsExport $export, string $format): bool {
            $this->assertSame('Xlsx', $format);
            $this->assertNotContains('Correo usuario', $export->headings());
            $this->assertNotContains('Número identificación usuario', $export->headings());
            $rows = $export->collection();
            $this->assertCount(3, $rows);
            $this->assertEqualsCanonicalizing(['assigned', 'own', 'general'], $rows->pluck('Observaciones')->all());
            $this->assertSame(['Participante'], $rows->pluck('Registrado por')->unique()->all());

            return true;
        })->andReturn('fake-workbook');
        $mail = $notification->toMail($this->student);
        $this->assertCount(1, $mail->rawAttachments);
    }

    public function test_queued_report_is_cancelled_when_recipient_or_access_changes(): void
    {
        $notification = new PhysicalRecordsReportNotification($this->filters(), $this->student->email, $this->school->id);
        $this->assertTrue($notification->shouldSend($this->student, 'mail'));
        $original = ['is_active' => true, 'email' => $this->student->email,
            'email_verified_at' => $this->student->email_verified_at, 'school_id' => $this->school->id];
        foreach ([
            ['is_active' => false], ['email' => 'changed@example.test'],
            ['email_verified_at' => null], ['school_id' => $this->otherSchool->id],
        ] as $change) {
            User::whereKey($this->student->id)->update($change);
            $this->assertFalse($notification->shouldSend($this->student, 'mail'));
            User::whereKey($this->student->id)->update($original);
        }
        $this->student->syncRoles([]);
        $this->assertFalse($notification->shouldSend($this->student, 'mail'));
    }

    public function test_queued_report_recomputes_assignment_visibility_before_attachment(): void
    {
        $this->seedVisibility();
        $notification = new PhysicalRecordsReportNotification($this->filters(), $this->student->email, $this->school->id);
        User::whereKey($this->student->id)->update(['grade_id' => null, 'course_id' => null]);
        $this->assertTrue($notification->shouldSend($this->student, 'mail'));
        Excel::shouldReceive('raw')->once()->withArgs(function (PhysicalVariableRecordsExport $export): bool {
            $this->assertEqualsCanonicalizing(['own', 'general'], $export->collection()->pluck('Observaciones')->all());

            return true;
        })->andReturn('fake-workbook');
        $notification->toMail($this->student);
    }

    public function test_mail_limit_accepts_2500_and_rejects_2501_and_worker_rechecks_count(): void
    {
        $row = ['school_id' => $this->school->id, 'user_id' => $this->student->id,
            'recorded_at' => '2026-10-01 12:00:00', 'source_type' => 'manual'];
        foreach (array_chunk(array_fill(0, 2500, $row), 250) as $chunk) {
            DB::table('physical_variable_records')->insert($chunk);
        }
        $filters = ['date_from' => '2026-10-01', 'date_to' => '2026-10-02'];
        $this->actingAs($this->student)->post(route('reports.mail.send'), $filters)
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        Notification::assertSentTo($this->student, PhysicalRecordsReportNotification::class);
        $notification = Notification::sent($this->student, PhysicalRecordsReportNotification::class)->sole();
        DB::table('physical_variable_records')->insert($row);
        $this->post(route('reports.mail.send'), $filters)->assertSessionHasErrors('date_from');
        Notification::assertCount(1);
        Excel::shouldReceive('raw')->never();
        $this->assertCount(0, $notification->toMail($this->student)->rawAttachments);
    }

    public function test_empty_period_cannot_enqueue_report(): void
    {
        $this->actingAs($this->student)->post(route('reports.mail.send'), $this->filters())
            ->assertSessionHasErrors('date_from');
        Notification::assertNothingSent();
    }
}
