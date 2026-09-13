<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\PageTypes\ServiceType;
use Illuminate\Database\Seeder;
use Safi\Atelier\BlockRegistry;
use Safi\Atelier\Models\Page;

/**
 * Three services and a page that lists them, so the panel and the public site
 * both have something to look at without clicking anything first.
 *
 * Example-app only, the same way everything else in here is. Safe to run
 * twice: it matches on the English slug.
 */
class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['Web design', 'web-design', 'Sites that load fast and read well.', 'مواقع سريعة وواضحة.', 12000],
            ['Brand identity', 'brand-identity', 'A name, a mark, and the rules that keep them straight.', 'هوية بصرية متكاملة.', 18000],
            ['Content strategy', 'content-strategy', 'What to say, where, and how often.', 'استراتيجية محتوى.', 9000],
        ];

        foreach ($services as [$title, $slug, $excerpt, $excerptAr, $price]) {
            $this->service($title, $slug, $excerpt, $excerptAr, $price);
        }

        $this->listingPage();
    }

    protected function service(string $title, string $slug, string $excerpt, string $excerptAr, int $price): void
    {
        $page = $this->find("services/{$slug}") ?? new Page;

        $page->fill([
            'title' => $title,
            'type' => 'service',
            'draft_content' => app(BlockRegistry::class)->tree(ServiceType::template()),
            'schema' => ['type' => 'Service'],
            'data' => [
                'starting_price' => $price,
                'featured' => false,
                'en' => ['excerpt' => $excerpt],
                'ar' => ['excerpt' => $excerptAr],
            ],
        ])->save();

        $page->setSlugs(['en' => $slug, 'ar' => $slug], ['en' => 'services', 'ar' => 'خدمات']);
        $page->publish();
    }

    /** A page carrying a Collection block, which is the point of the exercise. */
    protected function listingPage(): void
    {
        $page = $this->find('what-we-do') ?? new Page;

        $page->fill([
            'title' => 'What we do',
            'draft_content' => [
                [
                    'id' => 'b_seed_collection',
                    'type' => 'collection',
                    'attributes' => [
                        'page_type' => 'service',
                        'heading' => ['en' => 'What we do', 'ar' => 'ما نقدمه'],
                        'source' => 'all',
                        'order' => 'title',
                        'columns' => '3',
                        'empty' => ['en' => 'Nothing published yet.'],
                    ],
                    'children' => [],
                ],
            ],
        ])->save();

        $page->setSlugs(['en' => 'what-we-do', 'ar' => 'what-we-do-ar']);
        $page->publish();
    }

    protected function find(string $slug): ?Page
    {
        return Page::whereHas('slugs', fn ($query) => $query->where('locale', 'en')->where('slug', $slug))->first();
    }
}
