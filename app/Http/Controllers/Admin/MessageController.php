<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contact form inbox and newsletter subscribers.
 */
class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize(Permission::ManageContent->value);
        $unreadOnly = $request->boolean('unread');

        return Inertia::render('admin/messages/index', [
            'messages' => ContactMessage::query()
                ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
                ->latest()->paginate(20)->withQueryString()
                ->through(fn (ContactMessage $m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'phone' => $m->phone,
                    'message' => $m->message,
                    'read' => $m->read_at !== null,
                    'created_at' => $m->created_at?->toIso8601String(),
                ]),
            'unreadCount' => ContactMessage::query()->whereNull('read_at')->count(),
            'filters' => ['unread' => $unreadOnly],
        ]);
    }

    public function markRead(ContactMessage $message): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);
        $message->forceFill(['read_at' => $message->read_at ?? now()])->save();

        return back();
    }

    public function destroy(ContactMessage $message, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize(Permission::ManageContent->value);
        $audit->log('message.deleted', $message);
        $message->delete();

        return back()->with('success', 'Message deleted.');
    }

    public function subscribers(): Response
    {
        Gate::authorize(Permission::ManageContent->value);

        return Inertia::render('admin/messages/subscribers', [
            'subscribers' => NewsletterSubscriber::query()->latest()->paginate(50)
                ->through(fn (NewsletterSubscriber $s) => [
                    'id' => $s->id,
                    'email' => $s->email,
                    'consented_at' => $s->consented_at?->toIso8601String(),
                    'unsubscribed' => $s->unsubscribed_at !== null,
                ]),
            'activeCount' => NewsletterSubscriber::query()->whereNull('unsubscribed_at')->count(),
        ]);
    }

    public function exportSubscribers(AuditLogger $audit): StreamedResponse
    {
        Gate::authorize(Permission::ManageContent->value);
        $audit->log('subscribers.exported');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'consented_at', 'consent_text']);
            NewsletterSubscriber::query()->whereNull('unsubscribed_at')->orderBy('id')
                ->each(function (NewsletterSubscriber $s) use ($out) {
                    // Neutralise spreadsheet formula injection.
                    $email = preg_match('/^[=+\-@]/', $s->email) ? "'".$s->email : $s->email;
                    fputcsv($out, [$email, $s->consented_at?->toIso8601String(), $s->consent_text]);
                });
            fclose($out);
        }, 'subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
