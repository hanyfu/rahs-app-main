<?php

namespace App\Http\Controllers;

use App\Models\ImportantContact;
use Illuminate\Http\Request;

class ImportantContactController extends Controller
{
    public function index()
    {
        $contacts = ImportantContact::query()->where('status', 'active')->orderBy('priority')->get();

        $role = auth()->check() ? auth()->user()->role : '';

        return view('important-contacts.index', compact('contacts', 'role'));
    }

    public function admin()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('important-contacts.index');
        }

        $contacts = ImportantContact::query()->where('status', 'active')->orderBy('priority')->get();

        return view('important-contacts.admin', compact('contacts'));
    }

    public function data()
    {
        return response()->json(
            ImportantContact::query()->where('status', 'active')->orderBy('priority')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'phone_primary' => ['required', 'string', 'max:50'],
            'phone_secondary' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'min:1'],
        ]);

        $contact = ImportantContact::create([
            'name' => $data['name'],
            'title' => $data['title'],
            'organization' => $data['organization'] ?? null,
            'phone_primary' => $data['phone_primary'],
            'phone_secondary' => $data['phone_secondary'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'priority' => $data['priority'] ?? 100,
            'status' => 'active',
        ]);

        return response()->json($contact, 201);
    }

    public function update(Request $request, string $id)
    {
        $contact = ImportantContact::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'phone_primary' => ['nullable', 'string', 'max:50'],
            'phone_secondary' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'min:1'],
        ]);

        $contact->update([
            'name' => $data['name'] ?? $contact->name,
            'title' => $data['title'] ?? $contact->title,
            'organization' => array_key_exists('organization', $data) ? ($data['organization'] ?: null) : $contact->organization,
            'phone_primary' => $data['phone_primary'] ?? $contact->phone_primary,
            'phone_secondary' => array_key_exists('phone_secondary', $data) ? ($data['phone_secondary'] ?: null) : $contact->phone_secondary,
            'email' => array_key_exists('email', $data) ? ($data['email'] ?: null) : $contact->email,
            'notes' => array_key_exists('notes', $data) ? ($data['notes'] ?: null) : $contact->notes,
            'priority' => $data['priority'] ?? $contact->priority,
        ]);

        return response()->json($contact);
    }

    public function deactivate(string $id)
    {
        ImportantContact::query()->findOrFail($id)->update(['status' => 'inactive']);

        return response()->json(['success' => true]);
    }

    public function bulkDeactivate(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string'],
        ]);

        ImportantContact::query()->whereIn('id', $data['ids'])->update(['status' => 'inactive']);

        return response()->json(['success' => true]);
    }
}
