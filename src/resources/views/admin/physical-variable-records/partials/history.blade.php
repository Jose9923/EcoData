<section class="admin-card bg-white p-4">
<h2 class="h5 fw-bold">Historial de cambios</h2>
<p class="small text-secondary">Conserva quién realizó cada cambio, cuándo y los valores anteriores y posteriores desde la activación de la auditoría.</p>
@forelse($record->audits as $audit)
<details class="border rounded p-3 mb-3">
<summary>
{{ $audit->created_at?->format('d/m/Y H:i:s') }} · {{ $audit->action === 'created' ? 'Creación' : 'Actualización' }} ·
{{ auth()->user()->hasRole('estudiante') && (int) $audit->user_id !== (int) auth()->id() ? 'Equipo del colegio' : ($audit->user?->name ?? 'Usuario retirado') }}
</summary>
<div class="table-responsive mt-3"><table class="table table-sm"><thead><tr><th>Campo</th><th>Antes</th><th>Después</th></tr></thead><tbody>
@php($contextLabels = ['school_id' => 'Colegio (ID)', 'grade_id' => 'Grado (ID)', 'course_id' => 'Curso (ID)', 'weather_station_id' => 'Estación (ID)', 'source_type' => 'Origen', 'recorded_at' => 'Fecha y hora', 'observations' => 'Observaciones'])
@foreach($contextLabels as $field => $label)
@php($beforeValue = $audit->before['context'][$field] ?? null)
@php($afterValue = $audit->after['context'][$field] ?? null)
@if($beforeValue !== $afterValue)
<tr><td>{{ $label }}</td><td>{{ $beforeValue ?? '—' }}</td><td>{{ $afterValue ?? '—' }}</td></tr>
@endif
@endforeach
@foreach(array_unique(array_merge(array_keys($audit->before['values'] ?? []), array_keys($audit->after['values'] ?? []))) as $id)
@php($beforeValue = $audit->before['values'][$id] ?? null)
@php($afterValue = $audit->after['values'][$id] ?? null)
@if($beforeValue !== $afterValue)
<tr>
<td>{{ $afterValue['name'] ?? $beforeValue['name'] ?? 'Variable '.$id }}</td>
<td>{{ is_bool($beforeValue['value'] ?? null) ? ($beforeValue['value'] ? 'Sí' : 'No') : ($beforeValue['value'] ?? '—') }} {{ $beforeValue['unit'] ?? '' }}</td>
<td>{{ is_bool($afterValue['value'] ?? null) ? ($afterValue['value'] ? 'Sí' : 'No') : ($afterValue['value'] ?? 'Retirado') }} {{ $afterValue['unit'] ?? '' }}</td>
</tr>
@endif
@endforeach
</tbody></table></div>
</details>
@empty
<p class="text-secondary mb-0">No hay cambios auditados para este registro. Los registros anteriores a la activación del historial se conservaron.</p>
@endforelse
</section>
