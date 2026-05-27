<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactArea;
use App\Models\Municipality;
use Illuminate\Http\Request;

class ContactAreaController extends Controller
{
    public function index(Municipality $municipality)
    {
        $areas = $municipality->contactAreas()->get();
        return view('admin.municipalities.contact-areas', compact('municipality', 'areas'));
    }

    public function store(Request $request, Municipality $municipality)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'email'        => ['nullable', 'email', 'max:150', 'regex:/@/'],
            'notification_email' => ['nullable', 'email', 'max:150', 'regex:/@/'],
            'backup_contact_name' => ['nullable', 'string', 'max:120'],
            'backup_email' => ['nullable', 'email', 'max:150', 'regex:/@/'],
            'backup_phone' => ['nullable', 'string', 'max:40'],
            'phone'        => ['nullable', 'string', 'max:40'],
            'notes'        => ['nullable', 'string'],
            'active'       => ['nullable', 'boolean'],
        ]);

        $municipality->contactAreas()->create([
            'name'         => $data['name'],
            'contact_name' => $data['contact_name'] ?? null,
            'email'        => $data['email'] ?? null,
            'notification_email' => $data['notification_email'] ?? null,
            'backup_contact_name' => $data['backup_contact_name'] ?? null,
            'backup_email' => $data['backup_email'] ?? null,
            'backup_phone' => $data['backup_phone'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'active'       => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.municipalities.contact-areas.index', $municipality)
            ->with('success', 'Área de contato adicionada.');
    }

    public function update(Request $request, Municipality $municipality, ContactArea $contactArea)
    {
        if ($contactArea->municipality_id !== $municipality->id) abort(403);

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'email'        => ['nullable', 'email', 'max:150', 'regex:/@/'],
            'notification_email' => ['nullable', 'email', 'max:150', 'regex:/@/'],
            'backup_contact_name' => ['nullable', 'string', 'max:120'],
            'backup_email' => ['nullable', 'email', 'max:150', 'regex:/@/'],
            'backup_phone' => ['nullable', 'string', 'max:40'],
            'phone'        => ['nullable', 'string', 'max:40'],
            'notes'        => ['nullable', 'string'],
            'active'       => ['nullable', 'boolean'],
        ]);

        $contactArea->update([
            'name'         => $data['name'],
            'contact_name' => $data['contact_name'] ?? null,
            'email'        => $data['email'] ?? null,
            'notification_email' => $data['notification_email'] ?? null,
            'backup_contact_name' => $data['backup_contact_name'] ?? null,
            'backup_email' => $data['backup_email'] ?? null,
            'backup_phone' => $data['backup_phone'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'active'       => $request->boolean('active'),
        ]);

        return back()->with('success', 'Área de contato atualizada.');
    }

    public function destroy(Municipality $municipality, ContactArea $contactArea)
    {
        if ($contactArea->municipality_id !== $municipality->id) abort(403);
        $contactArea->delete();
        return back()->with('success', 'Área de contato removida.');
    }
}
