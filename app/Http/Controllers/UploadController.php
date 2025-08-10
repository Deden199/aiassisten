<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Store an uploaded document in a private disk and return a signed URL.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,docx,pptx,txt|max:10240',
        ]);

        $file = $validated['file'];

        // Validate MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
        ];

        if (! in_array($mime, $allowedMimes, true)) {
            return response()->json(['message' => 'Invalid file type'], 422);
        }

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('', $filename, 'private');

        // Generate temporary download URL valid for 5 minutes
        $url = Storage::disk('private')->temporaryUrl($path, now()->addMinutes(5));

        return response()->json(['url' => $url, 'path' => $path]);
    }
}
