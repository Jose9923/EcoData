<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentalEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnvironmentalEventImageController extends Controller
{
    public function show(Request $request, EnvironmentalEvent $environmental_event): StreamedResponse
    {
        $authUser = $request->user();

        if (! $authUser->hasRole('super_admin')) {
            abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');
            abort_if((int) $environmental_event->school_id !== (int) $authUser->school_id, 403);

            if (! $environmental_event->is_active) {
                abort_unless(
                    $authUser->hasAnyRole(['admin_colegio', 'docente']),
                    404
                );
            }
        }

        abort_if(! $environmental_event->image_path, 404, 'El evento no tiene imagen asociada.');

        $disk = $this->resolveStorageDisk($environmental_event->image_path);

        abort_if(! $disk, 404, 'La imagen del evento no existe.');

        return Storage::disk($disk)->response(
            $environmental_event->image_path,
            basename($environmental_event->image_path)
        );
    }

    private function resolveStorageDisk(string $path): ?string
    {
        if (Storage::disk('local')->exists($path)) {
            return 'local';
        }

        if (Storage::disk('public')->exists($path)) {
            return 'public';
        }

        return null;
    }
}
