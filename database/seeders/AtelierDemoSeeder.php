<?php

declare(strict_types=1);

namespace Safi\Atelier\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\SiteSettings;

/**
 * Demo content, so a fresh install opens on something real rather than an
 * empty list. Safe to run more than once: it matches on slug.
 *
 * Not for production. Delete the pages when you have your own.
 */
class AtelierDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->siteDetails();

        $this->page('Home', ['en' => '', 'ar' => ''], $this->home(), publish: true);
        $this->page('About', ['en' => 'about', 'ar' => 'about'], $this->about(), publish: true, schema: ['type' => 'AboutPage']);
        $this->page('Contact', ['en' => 'contact', 'ar' => 'contact'], $this->contact(), publish: false, schema: ['type' => 'ContactPage']);

        $this->page('Web design', ['en' => 'services/web-design', 'ar' => 'services/web-design'], $this->service(), publish: true, schema: [
            'type' => 'Service',
            'data' => [
                'service_type' => 'Web design',
                'area_served' => 'Dubai, Abu Dhabi',
                'price' => '5000',
                'currency' => 'AED',
            ],
        ]);
    }

    /**
     * Enough of a site for the structured data to say something.
     *
     * Left alone if somebody has already filled it in, because this seeder is
     * meant to be safe to run twice.
     */
    protected function siteDetails(): void
    {
        $settings = SiteSettings::current();

        if (filled($settings->data)) {
            return;
        }

        $settings->update(['data' => [
            'name' => ['en' => 'Atelier Demo', 'ar' => 'أتيليه'],
            'description' => ['en' => 'A demo site built with Filament Atelier.'],
            'type' => 'ProfessionalService',
            'telephone' => '+971 4 000 0000',
            'email' => 'hello@example.com',
            'address' => [
                'street' => '1 Sheikh Zayed Road',
                'locality' => 'Dubai',
                'country' => 'AE',
            ],
            'geo' => ['latitude' => '25.2048', 'longitude' => '55.2708'],
            'same_as' => ['https://github.com/Abdulkader-Safi/filament-atelier'],
            'price_range' => '$$',
            'area_served' => 'Dubai, Abu Dhabi',
        ]]);
    }

    /** @param array<string, string> $slugs */
    protected function page(string $title, array $slugs, array $tree, bool $publish, array $schema = []): void
    {
        $default = array_key_first(config('atelier.locales'));
        $slug = $slugs[$default] === '' ? 'home' : $slugs[$default];

        $page = Page::whereHas('slugs', fn ($q) => $q->where('locale', $default)->where('slug', $slug))->first()
            ?? new Page;

        $page->fill([
            'title' => $title,
            'draft_content' => $tree,
            'schema' => $schema ?: null,
            'seo' => [
                $default => [
                    'meta_title' => $title,
                    'meta_description' => "The {$title} page, built with Atelier.",
                ],
            ],
        ])->save();

        $page->setSlugs(array_map(fn (string $s) => $s === '' ? 'home' : $s, $slugs));

        if ($publish) {
            $page->publish();
        }
    }

    protected function id(string $prefix): string
    {
        return 'b_'.$prefix.'_'.Str::lower(Str::random(4));
    }

    protected function block(string $type, array $attributes): array
    {
        return [
            'id' => $this->id($type),
            'type' => $type,
            'attributes' => $attributes,
            'children' => [],
        ];
    }

    protected function home(): array
    {
        return [
            $this->block('hero', [
                'eyebrow' => ['en' => 'Laravel + Filament', 'ar' => 'لارافيل + فيلامنت'],
                'heading' => ['en' => 'Build pages, not tickets.', 'ar' => 'ابنِ صفحاتك بنفسك.'],
                'subheading' => [
                    'en' => 'Your client edits the site and watches the real page update as they type. You still own every block, in code.',
                    'ar' => 'يحرّر عميلك الموقع ويرى الصفحة الحقيقية تتغيّر أثناء الكتابة.',
                ],
                'cta_label' => ['en' => 'See how it works', 'ar' => 'كيف يعمل'],
                'cta_url' => '/about',
                'align' => 'center',
            ]),

            $this->block('logo-wall', [
                'heading' => ['en' => 'Trusted by', 'ar' => 'موثوق من'],
                'logos' => [],
            ]),

            $this->block('features', [
                'heading' => ['en' => 'What you get', 'ar' => 'ما الذي تحصل عليه'],
                'subheading' => [
                    'en' => 'A small set of decisions, made once, that keep every page fast and consistent.',
                    'ar' => 'قرارات قليلة تُتخذ مرة واحدة وتُبقي كل صفحة سريعة ومتناسقة.',
                ],
                'columns' => '3',
                'items' => [
                    'en' => [
                        ['icon' => 'heroicon-o-bolt', 'title' => 'Server rendered', 'body' => 'Full HTML in the first response. Works with JavaScript off.'],
                        ['icon' => 'heroicon-o-eye', 'title' => 'Live preview', 'body' => 'The real page, real stylesheet, real widths, while you type.'],
                        ['icon' => 'heroicon-o-language', 'title' => 'Bilingual', 'body' => 'English and Arabic on one page, mirrored properly in RTL.'],
                    ],
                    'ar' => [
                        ['icon' => 'heroicon-o-bolt', 'title' => 'تصيير من الخادم', 'body' => 'صفحة كاملة من أول استجابة، وتعمل بدون جافاسكربت.'],
                        ['icon' => 'heroicon-o-eye', 'title' => 'معاينة حية', 'body' => 'الصفحة الحقيقية بتنسيقها الحقيقي أثناء الكتابة.'],
                        ['icon' => 'heroicon-o-language', 'title' => 'ثنائي اللغة', 'body' => 'الإنجليزية والعربية في صفحة واحدة، باتجاه صحيح.'],
                    ],
                ],
            ]),

            $this->block('testimonials', [
                'heading' => ['en' => 'What clients say', 'ar' => 'آراء العملاء'],
                'items' => ['en' => [
                    ['quote' => 'We stopped raising tickets for copy changes in the first week.', 'name' => 'A very happy client', 'role' => 'Marketing lead'],
                    ['quote' => 'It looks like the site, because it is the site.', 'name' => 'Another one', 'role' => 'Founder'],
                ]],
            ]),

            $this->block('faq', [
                'heading' => ['en' => 'Questions', 'ar' => 'أسئلة'],
                'items' => ['en' => [
                    ['question' => 'Can the client break the design?', 'answer' => 'No. They pick from blocks you wrote, and the styling comes from your design tokens.'],
                    ['question' => 'How do I add a new block?', 'answer' => 'One PHP class and one Blade view, registered in your panel provider. Nothing inside the plugin changes.'],
                    ['question' => 'Is the public page fast?', 'answer' => 'It is plain Blade, server rendered, with per-block assets. Same as hand-writing the page.'],
                ]],
            ]),

            $this->block('cta', [
                'heading' => ['en' => 'Ready when you are', 'ar' => 'جاهزون متى شئت'],
                'body' => ['en' => 'Tell us what you need and we will come back within a day.', 'ar' => 'أخبرنا بما تحتاج وسنعود إليك خلال يوم.'],
                'cta_label' => ['en' => 'Start a project', 'ar' => 'ابدأ مشروعاً'],
                'cta_url' => '/contact',
            ]),
        ];
    }

    protected function about(): array
    {
        return [
            $this->block('hero', [
                'heading' => ['en' => 'About', 'ar' => 'من نحن'],
                'subheading' => ['en' => 'A short page to prove the second one works.', 'ar' => 'صفحة قصيرة لإثبات أن الصفحة الثانية تعمل.'],
                'align' => 'left',
            ]),

            $this->block('rich-text', [
                'heading' => ['en' => 'How this page was made', 'ar' => 'كيف صُنعت هذه الصفحة'],
                'body' => ['en' => '<p>Every section on this page is a PHP class and a Blade view. The editor arranges them and fills them in. Nothing here was designed in the browser.</p>'],
            ]),

            $this->block('image', [
                'alt' => ['en' => 'A placeholder, until you upload something'],
                'width' => 'wide',
            ]),
        ];
    }

    /** A service page: a thing-shaped type, and a nested slug so breadcrumbs appear. */
    protected function service(): array
    {
        return [
            $this->block('hero', [
                'eyebrow' => ['en' => 'Services', 'ar' => 'الخدمات'],
                'heading' => ['en' => 'Web design', 'ar' => 'تصميم المواقع'],
                'subheading' => ['en' => 'Sites that load fast, read well, and stay editable by the people who own them.'],
                'cta_label' => ['en' => 'Talk to us'],
                'cta_url' => '/contact',
                'align' => 'center',
            ]),
            $this->block('features', [
                'heading' => ['en' => 'What it includes', 'ar' => 'ما الذي يشمله'],
                'columns' => 'three',
                'items' => ['en' => [
                    ['icon' => 'heroicon-o-swatch', 'title' => 'Design', 'body' => 'A layout built around your content, not a template.'],
                    ['icon' => 'heroicon-o-code-bracket', 'title' => 'Build', 'body' => 'Server-rendered Blade, so it is fast and crawlable.'],
                    ['icon' => 'heroicon-o-pencil-square', 'title' => 'Handover', 'body' => 'You edit it afterwards, without calling anyone.'],
                ]],
            ]),
            // On the same page as the Service node and the breadcrumb trail,
            // so one URL shows every part of the structured data working.
            $this->block('faq', [
                'heading' => ['en' => 'Questions about this service', 'ar' => 'أسئلة عن هذه الخدمة'],
                'items' => [
                    'en' => [
                        ['question' => 'How long does a site take?', 'answer' => 'Four to eight weeks for most, depending on how much content exists on day one.'],
                        ['question' => 'Can we edit it afterwards?', 'answer' => 'That is the point. Every section on the site is editable without calling a developer.'],
                        ['question' => 'Do you work in Arabic?', 'answer' => 'Yes. Every page is bilingual, right to left, from the same content tree.'],
                    ],
                    'ar' => [
                        ['question' => 'كم يستغرق بناء الموقع؟', 'answer' => 'من أربعة إلى ثمانية أسابيع في الغالب، حسب حجم المحتوى الجاهز.'],
                        ['question' => 'هل يمكننا التعديل لاحقاً؟', 'answer' => 'هذا هو الهدف. كل قسم قابل للتعديل دون الرجوع إلى المطور.'],
                    ],
                ],
            ]),
            $this->block('cta', [
                'heading' => ['en' => 'Start a project', 'ar' => 'ابدأ مشروعاً'],
                'body' => ['en' => 'Tell us what you need and we will tell you what it takes.'],
                'cta_label' => ['en' => 'Get in touch'],
                'cta_url' => '/contact',
            ]),
        ];
    }

    protected function contact(): array
    {
        return [
            $this->block('hero', [
                'heading' => ['en' => 'Contact', 'ar' => 'تواصل معنا'],
                'subheading' => ['en' => 'This page is a draft, so it 404s on the public site until you publish it.', 'ar' => 'هذه الصفحة مسودة.'],
                'align' => 'center',
            ]),

            $this->block('cta', [
                'heading' => ['en' => 'Say hello', 'ar' => 'قل مرحباً'],
                'body' => ['en' => 'Email is fine.'],
                'cta_label' => ['en' => 'Email us'],
                'cta_url' => 'mailto:hello@example.com',
            ]),
        ];
    }
}
