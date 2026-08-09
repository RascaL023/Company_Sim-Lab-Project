<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Attachment::query()->with('uploader');

        if ($request->filled('attachable_type')) {
            $query->where('attachable_type', $request->get('attachable_type'));
        }

        if ($request->filled('attachable_id')) {
            $query->where('attachable_id', $request->get('attachable_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        $attachments = $query->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 20));

        return AttachmentResource::collection($attachments);
    }

    public function store(Request $request)
    {
        if (! $request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $path = $file->store('attachments', 'public');

        $attachment = Attachment::create([
            'attachable_type' => $request->input('attachable_type'),
            'attachable_id' => $request->input('attachable_id'),
            'type' => $request->input('type'),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'disk' => 'public',
            'description' => $request->input('description'),
            'uploaded_by' => $request->user()->id,
        ]);

        return (new AttachmentResource($attachment->load('uploader')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Attachment $attachment)
    {
        return new AttachmentResource($attachment->load('uploader'));
    }

    public function destroy(Attachment $attachment)
    {
        $attachment->delete();

        return response()->json(null, 204);
    }

    public function download(Attachment $attachment)
    {
        return response()->download(storage_path('app/public/'.$attachment->file_path));
    }
}
