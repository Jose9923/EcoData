<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Grade;
use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\PhysicalVariableRecord;
use App\Models\School;
use App\Models\User;
use App\Models\WeatherStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeasurementChartsTest extends TestCase
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
        $this->withoutVite();
        Notification::fake();
        foreach (['super_admin', 'admin_colegio', 'docente', 'estudiante'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->school = School::create(['name' => 'Colegio visible', 'slug' => 'visible', 'is_active' => true]);
        $this->otherSchool = School::create(['name' => 'Colegio privado', 'slug' => 'privado', 'is_active' => true]);
        $this->grade = Grade::create(['school_id' => $this->school->id, 'name' => '6', 'is_active' => true]);
        $this->otherGrade = Grade::create(['school_id' => $this->school->id, 'name' => '7', 'is_active' => true]);
        $this->course = Course::create(['school_id' => $this->school->id, 'grade_id' => $this->grade->id, 'name' => '6-A', 'is_active' => true]);
        $this->otherCourse = Course::create(['school_id' => $this->school->id, 'grade_id' => $this->otherGrade->id, 'name' => '7-A', 'is_active' => true]);
        $this->student = $this->user('estudiante', ['grade_id' => $this->grade->id, 'course_id' => $this->course->id]);
        $this->teacher = $this->user('docente', ['name' => 'Autor privado', 'email' => 'autor-privado@example.test', 'document_number' => '9876543210']);
        $category = PhysicalVariableCategory::create(['name' => 'Clima', 'slug' => 'clima', 'is_active' => true]);
        $this->variable = PhysicalVariable::create([
            'school_id' => $this->school->id, 'category_id' => $category->id,
            'name' => 'Temperatura', 'slug' => 'temperatura', 'unit' => '°C', 'data_type' => 'decimal',
            'min_value' => 0, 'max_value' => 40, 'decimals' => 4, 'is_active' => true,
        ]);
        $this->foreignVariable = PhysicalVariable::create([
            'school_id' => $this->otherSchool->id, 'category_id' => $category->id,
            'name' => 'Variable privada', 'slug' => 'temperatura', 'data_type' => 'decimal', 'decimals' => 2, 'is_active' => true,
        ]);
        $this->textVariable = PhysicalVariable::create([
            'school_id' => $this->school->id, 'category_id' => $category->id,
            'name' => 'Texto', 'slug' => 'texto', 'data_type' => 'text', 'is_active' => true,
        ]);
        $this->station = WeatherStation::create(['school_id' => $this->school->id, 'name' => 'Patio', 'code' => 'PAT', 'is_active' => true]);
        $this->foreignStation = WeatherStation::create(['school_id' => $this->otherSchool->id, 'name' => 'Estación privada', 'code' => 'PRIV', 'is_active' => true]);
    }

    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function filters(array $replace = []): array
    {
        return array_replace(['date_from' => '2026-10-01', 'date_to' => '2026-10-03', 'variable_id' => $this->variable->id], $replace);
    }

    private function record(float|string $value, array $attributes = [], ?PhysicalVariable $variable = null): PhysicalVariableRecord
    {
        $record = PhysicalVariableRecord::create(array_replace([
            'school_id' => $this->school->id, 'grade_id' => $this->grade->id,
            'course_id' => $this->course->id, 'weather_station_id' => $this->station->id,
            'user_id' => $this->teacher->id, 'source_type' => 'manual',
            'recorded_at' => '2026-10-01 12:00:00', 'observations' => 'Observación privada del autor',
        ], $attributes));
        $record->values()->create(['physical_variable_id' => ($variable ?? $this->variable)->id, 'value_numeric' => $value]);

        return $record;
    }

    private function chart(?User $user = null, array $filters = []): array
    {
        return $this->actingAs($user ?? $this->student)
            ->get(route('charts.index', $this->filters($filters)))->assertOk()->viewData('chartData');
    }

    public function test_charts_require_an_active_verified_account_and_recognized_role(): void
    {
        $this->get(route('charts.index'))->assertRedirect(route('login'));
        $this->teacher->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($this->teacher)->get(route('charts.index'))->assertRedirect(route('verification.notice'));
        $this->teacher->forceFill(['email_verified_at' => now(), 'is_active' => false])->save();
        $this->actingAs($this->teacher->fresh())->getJson(route('charts.index'))->assertForbidden();
        $withoutRole = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->actingAs($withoutRole)->get(route('charts.index'))->assertForbidden();
    }

    public function test_students_cannot_bypass_missing_school_assignment(): void
    {
        $this->student->update(['school_id' => null]);
        $this->actingAs($this->student->fresh())->get(route('charts.index'))->assertRedirect(route('account.school-required'));
    }

    public function test_student_visibility_is_enforced_across_all_chart_datasets_and_comparison(): void
    {
        $this->record(10);
        $this->record(30, ['user_id' => $this->student->id, 'grade_id' => $this->otherGrade->id, 'course_id' => $this->otherCourse->id]);
        $this->record(50, ['grade_id' => null, 'course_id' => null, 'weather_station_id' => null, 'recorded_at' => '2026-10-03 12:00:00']);
        $this->record(1000, ['grade_id' => $this->otherGrade->id, 'course_id' => $this->otherCourse->id]);
        $sameGradeOtherCourse = Course::create(['school_id' => $this->school->id, 'grade_id' => $this->grade->id, 'name' => '6-B', 'is_active' => true]);
        $this->record(1500, ['course_id' => $sameGradeOtherCourse->id]);
        $this->record(2000, ['school_id' => $this->otherSchool->id, 'grade_id' => null, 'course_id' => null, 'weather_station_id' => $this->foreignStation->id], $this->foreignVariable);
        $this->record(6, ['recorded_at' => '2026-09-28 12:00:00']);
        $this->record(900, ['recorded_at' => '2026-09-29 12:00:00', 'grade_id' => $this->otherGrade->id, 'course_id' => $this->otherCourse->id]);

        $data = $this->chart();
        $this->assertSame(3, $data['summary']['count']);
        $this->assertEquals(30, $data['summary']['average']);
        $this->assertEquals(50, $data['summary']['maximum']);
        $this->assertSame(1, $data['summary']['outside']);
        $this->assertSame(1, $data['summary']['previousCount']);
        $this->assertEquals(6, $data['summary']['previousAverage']);
        $this->assertEquals(24, $data['summary']['difference']);
        $this->assertSame(3, array_sum(array_column($data['trend'], 'count')));
        $this->assertSame(1, array_sum(array_column($data['trend'], 'previousCount')));
        $this->assertSame(3, array_sum(array_column($data['stations'], 'count')));
        $this->assertSame(3, array_sum(array_column($data['distribution'], 'count')));
        $this->assertNotContains('Estación privada', array_column($data['stations'], 'name'));
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        foreach (['Autor privado', 'autor-privado@example.test', '9876543210', 'Observación privada del autor', 'user_id', 'record_id'] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
    }

    public function test_school_staff_can_compare_all_groups_but_only_their_school(): void
    {
        $this->record(10);
        $this->record(30, ['grade_id' => $this->otherGrade->id, 'course_id' => $this->otherCourse->id]);
        $this->record(500, ['school_id' => $this->otherSchool->id, 'grade_id' => null, 'course_id' => null, 'weather_station_id' => $this->foreignStation->id], $this->foreignVariable);
        foreach ([$this->teacher, $this->user('admin_colegio')] as $user) {
            $data = $this->chart($user);
            $this->assertSame(2, $data['summary']['count']);
            $this->assertEquals(20, $data['summary']['average']);
            $this->actingAs($user)->get(route('charts.index', $this->filters(['variable_id' => $this->foreignVariable->id])))
                ->assertSessionHasErrors('variable_id');
        }
    }

    public function test_super_admin_can_select_another_school_but_not_mix_its_station_with_another_variable(): void
    {
        $admin = $this->user('super_admin', ['school_id' => null]);
        $this->record(17, ['school_id' => $this->otherSchool->id, 'grade_id' => null, 'course_id' => null, 'weather_station_id' => $this->foreignStation->id], $this->foreignVariable);
        $data = $this->chart($admin, ['variable_id' => $this->foreignVariable->id, 'weather_station_id' => $this->foreignStation->id]);
        $this->assertSame(1, $data['summary']['count']);
        $this->assertEquals(17, $data['summary']['average']);
        $this->actingAs($admin)->get(route('charts.index', $this->filters(['weather_station_id' => $this->foreignStation->id])))
            ->assertSessionHasErrors('weather_station_id');
    }

    public function test_statistics_are_weighted_by_measurements_and_days_without_data_stay_null(): void
    {
        foreach ([10, 20, 30] as $value) {
            $this->record($value);
        }
        $this->record(100, ['recorded_at' => '2026-10-03 12:00:00']);
        $this->record(0, ['recorded_at' => '2026-09-28 12:00:00']);
        $data = $this->chart();
        $this->assertEquals(40, $data['summary']['average']);
        $this->assertCount(3, $data['trend']);
        $this->assertEquals(20, $data['trend'][0]['average']);
        $this->assertSame(3, $data['trend'][0]['count']);
        $this->assertEquals(10, $data['trend'][0]['minimum']);
        $this->assertEquals(30, $data['trend'][0]['maximum']);
        $this->assertSame('2026-10-02', $data['trend'][1]['date']);
        $this->assertSame(0, $data['trend'][1]['count']);
        foreach (['average', 'minimum', 'maximum', 'previousAverage', 'previousMinimum', 'previousMaximum'] as $metric) {
            $this->assertNull($data['trend'][1][$metric]);
        }
        $this->assertEquals(0, $data['trend'][0]['previousAverage']);
        $this->assertSame('2026-09-28', $data['trend'][0]['previousDate']);
        $this->assertSame('2026-09-30', $data['trend'][2]['previousDate']);
    }

    public function test_current_and_previous_ranges_include_both_boundaries_exactly_once(): void
    {
        $this->record(1, ['recorded_at' => '2026-10-01 00:00:00']);
        $this->record(3, ['recorded_at' => '2026-10-03 23:59:59']);
        $this->record(5, ['recorded_at' => '2026-09-28 00:00:00']);
        $this->record(7, ['recorded_at' => '2026-09-30 23:59:59']);
        $this->record(999, ['recorded_at' => '2026-09-27 23:59:59']);
        $this->record(999, ['recorded_at' => '2026-10-04 00:00:00']);
        $data = $this->chart();
        $this->assertSame(2, $data['summary']['count']);
        $this->assertEquals(2, $data['summary']['average']);
        $this->assertSame(2, $data['summary']['previousCount']);
        $this->assertEquals(6, $data['summary']['previousAverage']);
        $this->assertEquals(-4, $data['summary']['difference']);
        $this->assertSame('2026-09-28', $data['range']['previousFrom']);
        $this->assertSame('2026-09-30', $data['range']['previousTo']);
    }

    public function test_single_day_and_zero_values_are_kept_as_observations(): void
    {
        $this->record(0);
        $this->record(0, ['recorded_at' => '2026-09-30 12:00:00']);
        $data = $this->chart(null, ['date_to' => '2026-10-01']);
        $this->assertCount(1, $data['trend']);
        $this->assertSame(1, $data['summary']['count']);
        $this->assertSame(1, $data['summary']['previousCount']);
        $this->assertEquals(0, $data['summary']['average']);
        $this->assertEquals(0, $data['summary']['difference']);
        $this->assertCount(1, $data['distribution']);
        $this->assertEquals(0, $data['distribution'][0]['from']);
        $this->assertEquals(0, $data['distribution'][0]['to']);
        $this->assertSame(1, $data['distribution'][0]['count']);
    }

    public function test_station_comparison_includes_manual_records_and_station_filter_applies_to_all_datasets(): void
    {
        $this->record(10);
        $this->record(30, ['weather_station_id' => null]);
        $this->record(100, ['weather_station_id' => null, 'recorded_at' => '2026-09-28 12:00:00']);
        $data = $this->chart();
        $manual = collect($data['stations'])->firstWhere('id', null);
        $this->assertSame('Sin estación', $manual['name']);
        $this->assertSame(1, $manual['count']);
        $this->assertEquals(30, $manual['average']);

        $filtered = $this->chart(null, ['weather_station_id' => $this->station->id]);
        $this->assertSame(1, $filtered['summary']['count']);
        $this->assertSame(0, $filtered['summary']['previousCount']);
        $this->assertNull($filtered['summary']['previousAverage']);
        $this->assertNull($filtered['summary']['difference']);
        $this->assertCount(1, $filtered['stations']);
        $this->assertSame($this->station->id, $filtered['stations'][0]['id']);
        $this->assertSame(1, array_sum(array_column($filtered['distribution'], 'count')));
    }

    public function test_histogram_handles_negative_values_and_includes_every_boundary_once(): void
    {
        for ($value = -10; $value <= 10; $value += 2) {
            $this->record($value);
        }
        $data = $this->chart();
        $bins = $data['distribution'];
        $this->assertCount(10, $bins);
        $this->assertEquals(-10, $bins[0]['from']);
        $this->assertEquals(10, $bins[9]['to']);
        $this->assertSame(11, array_sum(array_column($bins, 'count')));
        $this->assertSame(2, $bins[9]['count']);
        foreach (array_slice($bins, 0, 9) as $bin) {
            $this->assertSame(1, $bin['count']);
        }
        $this->assertEquals(0, $data['summary']['average']);
        $this->assertSame(5, $data['summary']['outside']);
    }

    public function test_constant_and_tiny_decimal_distributions_do_not_drop_values(): void
    {
        foreach (['0.0001', '0.0001', '0.0001'] as $value) {
            $this->record($value);
        }
        $data = $this->chart();
        $this->assertCount(1, $data['distribution']);
        $this->assertEqualsWithDelta(0.0001, $data['distribution'][0]['from'], 0.00000001);
        $this->assertSame(3, $data['distribution'][0]['count']);
        foreach (['-0.0002', '-0.0001', '0', '0.0002'] as $value) {
            $this->record($value);
        }
        $data = $this->chart();
        $this->assertSame(7, array_sum(array_column($data['distribution'], 'count')));
        $this->assertEqualsWithDelta(-0.0002, $data['summary']['minimum'], 0.00000001);
        $this->assertEqualsWithDelta(0.0002, $data['summary']['maximum'], 0.00000001);
    }

    public function test_histogram_covers_the_full_supported_decimal_range(): void
    {
        foreach (['-99999999999.9999', '0', '99999999999.9999'] as $value) {
            $this->record($value);
        }
        $data = $this->chart();
        $this->assertSame(3, array_sum(array_column($data['distribution'], 'count')));
        $this->assertEqualsWithDelta(-99999999999.9999, $data['distribution'][0]['from'], 0.00002);
        $this->assertEqualsWithDelta(99999999999.9999, $data['distribution'][array_key_last($data['distribution'])]['to'], 0.00002);
    }

    public function test_inactive_numeric_variable_remains_available_for_historical_analysis(): void
    {
        $this->record(12);
        $this->variable->update(['is_active' => false]);
        $this->station->update(['is_active' => false]);
        $data = $this->chart(null, ['weather_station_id' => $this->station->id]);
        $this->assertSame(1, $data['summary']['count']);
        $this->assertEquals(12, $data['summary']['average']);
    }

    public function test_numeric_options_and_station_permissions_cannot_be_forged(): void
    {
        $this->actingAs($this->student);
        foreach ([$this->textVariable->id, $this->foreignVariable->id, 999999] as $id) {
            $this->get(route('charts.index', $this->filters(['variable_id' => $id])))->assertSessionHasErrors('variable_id');
        }
        foreach ([$this->foreignStation->id, 999999] as $id) {
            $this->get(route('charts.index', $this->filters(['weather_station_id' => $id])))->assertSessionHasErrors('weather_station_id');
        }
        $this->get(route('charts.index'))->assertOk()->assertViewHas('current', null)
            ->assertDontSee('Variable privada')->assertDontSee('Estación privada');
    }

    public function test_grouping_and_chart_types_are_validated_without_changing_the_daily_source_data(): void
    {
        $this->record(10);
        foreach (['day', 'week', 'month'] as $grouping) {
            foreach (['line', 'bar'] as $type) {
                $response = $this->actingAs($this->student)->get(route('charts.index', $this->filters(['group_by' => $grouping, 'chart_type' => $type])))->assertOk();
                $this->assertSame($grouping, $response->viewData('filters')['group_by']);
                $this->assertSame($type, $response->viewData('filters')['chart_type']);
                $this->assertSame(1, $response->viewData('chartData')['summary']['count']);
                $this->assertCount(3, $response->viewData('chartData')['trend']);
            }
        }
        foreach (['group_by' => 'year', 'chart_type' => 'pie'] as $key => $value) {
            $this->get(route('charts.index', $this->filters([$key => $value])))->assertSessionHasErrors($key);
        }
    }

    public function test_the_range_supports_a_leap_year_but_rejects_367_days_and_reversed_dates(): void
    {
        $this->record(4, ['recorded_at' => '2024-02-29 12:00:00']);
        $data = $this->chart(null, ['date_from' => '2024-01-01', 'date_to' => '2024-12-31']);
        $this->assertCount(366, $data['trend']);
        $this->assertSame(1, $data['summary']['count']);
        $this->assertSame(1, collect($data['trend'])->firstWhere('date', '2024-02-29')['count']);
        foreach ([
            ['date_from' => '2024-01-01', 'date_to' => '2025-01-01'],
            ['date_from' => '2026-10-04', 'date_to' => '2026-10-03'],
        ] as $range) {
            $this->get(route('charts.index', $this->filters($range)))->assertSessionHasErrors('date_to');
        }
        $this->get(route('charts.index', $this->filters(['date_from' => '2026-02-30'])))->assertSessionHasErrors('date_from');
    }

    public function test_empty_periods_are_not_misrepresented_as_zero_measurements(): void
    {
        $data = $this->chart();
        $this->assertSame(0, $data['summary']['count']);
        foreach (['average', 'minimum', 'maximum', 'previousAverage', 'difference'] as $metric) {
            $this->assertNull($data['summary'][$metric]);
        }
        $this->assertSame([], $data['stations']);
        $this->assertSame([], $data['distribution']);
        foreach ($data['trend'] as $point) {
            $this->assertSame(0, $point['count']);
            $this->assertNull($point['average']);
        }
    }

    public function test_chart_metadata_is_escaped_in_html_and_does_not_create_executable_markup(): void
    {
        $payload = '</script><script>alert("chart-xss")</script>';
        $this->variable->update(['name' => $payload]);
        $this->station->update(['name' => $payload]);
        $this->record(12);
        $response = $this->actingAs($this->student)->get(route('charts.index', $this->filters()))->assertOk();
        $response->assertDontSee($payload, false)->assertSee(e($payload), false);
        $this->assertSame($payload, $response->viewData('chartData')['variable']['name']);
        $this->assertSame($payload, $response->viewData('chartData')['stations'][0]['name']);
    }

    public function test_legacy_evolution_link_uses_the_same_authorized_chart_data(): void
    {
        $this->record(21);
        $this->record(3000, ['grade_id' => $this->otherGrade->id, 'course_id' => $this->otherCourse->id]);
        $expected = $this->chart();
        $legacy = $this->actingAs($this->student)->get(route('analytics.index', $this->filters()))->assertOk();
        $this->assertSame($expected, $legacy->viewData('chartData'));
        $this->assertSame(1, (int) $legacy->viewData('current')->count);
    }
}
