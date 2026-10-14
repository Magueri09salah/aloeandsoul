<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ContactRequest;
use App\Http\Requests\Storefront\NewsletterRequest;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Services\Seo\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(): Response
    {
        return $this->render('contact', [], Seo::page(
            __('Contact'),
            __('Questions about a product or an order? Write to Aloe & Soul or message us on WhatsApp.'),
        ));
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::query()->create([
            ...$request->safe()->only(['name', 'email', 'phone', 'message']),
            'ip_hash' => ip_hash($request->ip()),
        ]);

        Log::info('contact.received', ['id' => $message->id]);

        return back()->with('success', __('Thank you. Your message has been sent and we will reply soon.'));
    }

    /**
     * Same response whether or not the address was already subscribed, so the
     * form cannot be used to discover who is on the list.
     */
    public function subscribe(NewsletterRequest $request): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $request->validated('email')]);

        if (! $subscriber->exists || $subscriber->unsubscribed_at !== null) {
            $subscriber->forceFill([
                'consented_at' => now(),
                // The wording the subscriber actually agreed to, in their language.
                'consent_text' => __(NewsletterRequest::CONSENT_TEXT),
                'unsubscribed_at' => null,
                'ip_hash' => ip_hash($request->ip()),
            ])->save();
        }

        return back()->with('success', __('Thank you for subscribing.'));
    }
}
