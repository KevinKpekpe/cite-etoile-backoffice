<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerDocumentRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerDocumentController extends Controller
{
    public function store(StoreCustomerDocumentRequest $request, Customer $customer): RedirectResponse
    {
        $file = $request->file('document');
        $path = $file->store("customers/{$customer->id}/documents", 'local');
        abort_if($path === false, 500, __('Le document n’a pas pu être enregistré.'));

        $document = $customer->documents()->create([
            'document_type' => $request->string('document_type'),
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLog::query()->create([
            'user_id' => $request->user()->id, 'action' => 'customer.document_uploaded',
            'entity_type' => CustomerDocument::class, 'entity_id' => $document->id,
            'new_values' => ['customer_id' => $customer->id, 'document_type' => $document->document_type],
            'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', __('Document ajouté.'));
    }

    public function download(Request $request, Customer $customer, CustomerDocument $document): StreamedResponse
    {
        abort_unless($request->user()?->can('documents.download'), 403);
        abort_unless($document->customer_id === $customer->id, 404);

        return Storage::disk('local')->download($document->file_path, $document->name);
    }
}
