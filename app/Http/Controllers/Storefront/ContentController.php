<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\ContentPresenter;
use App\Http\Presenters\ProductPresenter;
use App\Models\Faq;
use App\Models\Page;
use App\Services\Seo\Seo;
use App\Services\Seo\StructuredData;
use Inertia\Response;

/**
 * Founder story, FAQ and editable policy pages.
 */
class ContentController extends Controller
{
    public function story(): Response
    {
        $story = settings()->group('story');

        $seo = Seo::page(
            'Our story',
            'How the founder of Aloe & Soul went from London to Marrakech, and why wellbeing and personal growth are at the heart of the brand.',
        );
        $seo->image = ContentPresenter::storyImage($story)['src'];
        $seo->jsonLd = [StructuredData::breadcrumbs([
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Our story', 'url' => route('story')],
        ])];

        return $this->render('story', [
            'story' => [
                'heading' => $story['heading'],
                'intro' => $story['intro'],
                'image' => ContentPresenter::storyImage($story),
                'milestones' => $story['milestones'],
                'chapters' => array_map(fn (array $c) => [
                    'key' => $c['key'],
                    'title' => $c['title'],
                    'paragraphs' => array_values(array_filter(array_map('trim', explode("\n\n", $c['body'])))),
                ], $story['chapters']),
                'closing' => $story['closing'],
                'disclaimer' => $story['disclaimer'],
                'tagline' => settings()->get('store.tagline'),
            ],
        ], $seo);
    }

    public function faq(): Response
    {
        return $this->render('faq', [
            'faqs' => Faq::query()->published()->get(['id', 'question', 'answer']),
        ], Seo::page('Frequently asked questions', 'Answers about ordering, cash on delivery, delivery areas and your order at Aloe & Soul.'));
    }

    public function page(string $slug): Response
    {
        $page = Page::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return $this->render('page', [
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'body_html' => ProductPresenter::markdown($page->body),
                'updated_at' => $page->updated_at?->toDateString(),
            ],
        ], Seo::page($page->seo_title ?: $page->title, $page->seo_description));
    }
}
