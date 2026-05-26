@if(isset($pendingEnvironmentalEvents) && $pendingEnvironmentalEvents->isNotEmpty())
    <div class="modal fade" id="dailyEnvironmentalEventModal" tabindex="-1" aria-labelledby="dailyEnvironmentalEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="text-uppercase small fw-semibold text-muted mb-1">
                            Calendario ambiental
                        </div>

                        <h5 class="modal-title fw-bold" id="dailyEnvironmentalEventModalLabel">
                            @if($pendingEnvironmentalEvents->count() === 1)
                                Mensaje ambiental del día
                            @else
                                {{ $pendingEnvironmentalEvents->count() }} mensajes ambientales del día
                            @endif
                        </h5>
                    </div>
                </div>

                <div class="modal-body">
                    <p class="text-muted mb-4">
                        Tienes eventos ambientales vigentes para hoy que aún no has visto.
                    </p>

                    @foreach($pendingEnvironmentalEvents as $event)
                        <div class="border rounded-4 p-3 mb-3">
                            <div class="row g-3 align-items-start">
                                @if($event->image_path)
                                    <div class="col-12 col-md-4">
                                        <img src="{{ asset('storage/' . $event->image_path) }}"
                                             alt="{{ $event->title }}"
                                             class="img-fluid rounded-4 border w-100"
                                             style="height: 140px; object-fit: cover;">
                                    </div>
                                @endif

                                <div class="col-12 {{ $event->image_path ? 'col-md-8' : '' }}">
                                    <h6 class="fw-bold mb-2">
                                        {{ $event->title }}
                                    </h6>

                                    <div class="text-muted small fw-semibold mb-2">
                                        {{ $event->starts_at?->format('d/m/Y') }}
                                        @if($event->ends_at && ! $event->starts_at->isSameDay($event->ends_at))
                                            - {{ $event->ends_at->format('d/m/Y') }}
                                        @endif
                                    </div>

                                    @if($event->description)
                                        <p class="text-muted mb-0">
                                            {{ Str::limit($event->description, 180) }}
                                        </p>
                                    @else
                                        <p class="text-muted mb-0">
                                            Hay un evento ambiental activo para el día de hoy.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="modal-footer border-0 pt-0">
                    <a href="{{ route('environmental-events.index') }}"
                       class="btn btn-outline-dark rounded-4 px-4">
                        Ver calendario
                    </a>

                    <form method="POST" action="{{ route('environmental-events.acknowledge-all') }}">
                        @csrf

                        @foreach($pendingEnvironmentalEvents as $event)
                            <input type="hidden" name="event_ids[]" value="{{ $event->id }}">
                        @endforeach

                        <button type="submit"
                                class="btn text-white rounded-4 px-4"
                                style="background-color: var(--school-primary);">
                            {{ $pendingEnvironmentalEvents->count() === 1 ? 'Aceptar' : 'Aceptar todos' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('dailyEnvironmentalEventModal');

                if (!modalElement || !window.bootstrap) {
                    return;
                }

                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });

                modal.show();
            });
        </script>
    @endpush
@endif