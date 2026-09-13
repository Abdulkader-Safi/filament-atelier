<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Safi\Atelier\BlockRegistry;
use Safi\Atelier\Models\Menu;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\SiteSettings;

/**
 * A whole small site, so the pieces can be seen working together rather than
 * one at a time: five ordinary pages, three services with three pricing tiers
 * each, three products with variations, the menus, and the site details the
 * JSON-LD reads.
 *
 * Example-app only. Safe to run twice: everything matches on its English slug.
 */
class SparkleCleanSeeder extends Seeder
{
    public function run(): void
    {
        $this->siteDetails();

        $services = $this->services();
        $products = $this->products();

        $this->home();
        $this->about();
        $this->servicesPage();
        $this->productsPage();
        $this->contact();

        $this->menus();

        $this->command?->info('Sparkle Clean: '.$services.' services, '.$products.' products, 5 pages.');
    }

    // The site --------------------------------------------------------------

    protected function siteDetails(): void
    {
        SiteSettings::current()->update(['data' => [
            'name' => ['en' => 'Sparkle Clean', 'ar' => 'سباركل كلين'],
            'description' => ['en' => 'Home and office cleaning across Dubai, by a vetted team that turns up when it says it will.'],
            'type' => 'ProfessionalService',
            'telephone' => '+971 4 555 0100',
            'email' => 'hello@sparkleclean.test',
            'address' => [
                'street' => 'Unit 12, Al Quoz Industrial 3',
                'locality' => 'Dubai',
                'region' => 'Dubai',
                'postal_code' => '00000',
                'country' => 'AE',
            ],
        ]]);
    }

    // Services ----------------------------------------------------------------

    protected function services(): int
    {
        $services = [
            [
                'title' => 'Home deep clean',
                'slug' => 'home-deep-clean',
                'icon' => 'heroicon-o-home',
                'duration' => '4 to 6 hours',
                'featured' => true,
                'excerpt' => 'Skirting boards to extractor fans, the clean you book twice a year.',
                'excerpt_ar' => 'تنظيف عميق للمنزل من الألف إلى الياء.',
                'tiers' => [
                    ['name' => 'Studio', 'price' => 450, 'unit' => 'visit', 'recommended' => false, 'features' => "Up to 2 hours\nKitchen and one bathroom\nSupplies included"],
                    ['name' => 'Two bedroom', 'price' => 750, 'unit' => 'visit', 'recommended' => true, 'features' => "Up to 5 hours\nEvery room, inside cupboards\nOven and fridge\nSupplies included"],
                    ['name' => 'Villa', 'price' => 1400, 'unit' => 'visit', 'recommended' => false, 'features' => "Full day, two cleaners\nEvery room, windows inside\nOven, fridge and balcony\nSupplies included"],
                ],
                'faq' => [
                    ['question' => 'Do I need to be home?', 'answer' => 'No. Most clients leave a key with the concierge and we send photos when we finish.'],
                    ['question' => 'Do you bring your own supplies?', 'answer' => 'Yes, every visit. If you would rather we used your products, say so in the request.'],
                ],
                'features' => [
                    ['icon' => 'heroicon-o-sparkles', 'title' => 'Inside the cupboards', 'body' => 'Emptied, wiped and put back the way we found them.'],
                    ['icon' => 'heroicon-o-shield-check', 'title' => 'Vetted and insured', 'body' => 'The same team each time, police-checked and covered.'],
                    ['icon' => 'heroicon-o-clock', 'title' => 'On the clock', 'body' => 'A two-hour arrival window, and a call if we are early.'],
                ],
            ],
            [
                'title' => 'Move-out clean',
                'slug' => 'move-out-clean',
                'icon' => 'heroicon-o-truck',
                'duration' => '5 to 8 hours',
                'featured' => true,
                'excerpt' => 'The clean that gets the deposit back, with a checklist the agent signs.',
                'excerpt_ar' => 'تنظيف ما قبل التسليم لاسترداد التأمين.',
                'tiers' => [
                    ['name' => 'Apartment', 'price' => 900, 'unit' => 'property', 'recommended' => false, 'features' => "Whole property\nInside every cupboard\nAgent checklist"],
                    ['name' => 'Apartment plus carpets', 'price' => 1300, 'unit' => 'property', 'recommended' => true, 'features' => "Everything in Apartment\nCarpet and sofa steam clean\nAgent checklist\nFree re-clean within 7 days"],
                    ['name' => 'Villa', 'price' => 2200, 'unit' => 'property', 'recommended' => false, 'features' => "Whole property, two days\nCarpets, windows, garage\nAgent checklist\nFree re-clean within 7 days"],
                ],
                'faq' => [
                    ['question' => 'What if the agent is not happy?', 'answer' => 'On the two larger tiers we come back once inside seven days at no charge.'],
                    ['question' => 'How much notice do you need?', 'answer' => 'Three days in season, next day if we have a gap.'],
                ],
                'features' => [
                    ['icon' => 'heroicon-o-clipboard-document-check', 'title' => 'Agent checklist', 'body' => 'Signed off room by room, so there is nothing to argue about.'],
                    ['icon' => 'heroicon-o-arrow-path', 'title' => 'Free re-clean', 'body' => 'Seven days to call us back on the two larger tiers.'],
                    ['icon' => 'heroicon-o-photo', 'title' => 'Photos on finish', 'body' => 'Every room, timestamped, sent to you and the agent.'],
                ],
            ],
            [
                'title' => 'Office contract',
                'slug' => 'office-contract',
                'icon' => 'heroicon-o-building-office',
                'duration' => 'Nightly or weekly',
                'featured' => true,
                'excerpt' => 'A team that comes after hours, on a contract you can end with a month.',
                'excerpt_ar' => 'عقود تنظيف للمكاتب بعد ساعات العمل.',
                'tiers' => [
                    ['name' => 'Weekly', 'price' => 2400, 'unit' => 'month', 'recommended' => false, 'features' => "One visit a week\nDesks, kitchen, bathrooms\nConsumables billed at cost"],
                    ['name' => 'Three a week', 'price' => 5200, 'unit' => 'month', 'recommended' => true, 'features' => "Three visits a week\nDesks, kitchen, bathrooms\nConsumables included\nNamed supervisor"],
                    ['name' => 'Nightly', 'price' => 9800, 'unit' => 'month', 'recommended' => false, 'features' => "Five nights a week\nEverything, plus meeting rooms\nConsumables included\nNamed supervisor and cover"],
                ],
                'faq' => [
                    ['question' => 'Is there a lock-in?', 'answer' => 'One month. Tell us by the 25th and the next month does not run.'],
                    ['question' => 'Can you work around our security?', 'answer' => 'Yes. We hold passes, sign in and out, and the same team comes each time.'],
                ],
                'features' => [
                    ['icon' => 'heroicon-o-moon', 'title' => 'After hours', 'body' => 'From 7pm, so nobody is cleaning around a meeting.'],
                    ['icon' => 'heroicon-o-user-group', 'title' => 'The same faces', 'body' => 'A named supervisor who knows the floor plan.'],
                    ['icon' => 'heroicon-o-document-text', 'title' => 'One invoice', 'body' => 'Monthly, with consumables itemised.'],
                ],
            ],
        ];

        foreach ($services as $service) {
            $this->service($service);
        }

        return count($services);
    }

    protected function service(array $service): void
    {
        $page = $this->find('services/'.$service['slug']) ?? new Page;

        $page->fill([
            'title' => $service['title'],
            'type' => 'service',
            'schema' => ['type' => 'Service'],
            'seo' => [
                'en' => [
                    'meta_title' => $service['title'].' in Dubai | Sparkle Clean',
                    'meta_description' => $service['excerpt'],
                ],
            ],
            'data' => [
                'icon' => $service['icon'],
                'duration' => $service['duration'],
                'featured' => $service['featured'],
                'tiers' => $service['tiers'],
                'en' => ['excerpt' => $service['excerpt']],
                'ar' => ['excerpt' => $service['excerpt_ar']],
            ],
            'draft_content' => $this->tree([
                $this->hero(
                    eyebrow: 'Sparkle Clean',
                    heading: $service['title'],
                    subheading: $service['excerpt'],
                    label: 'See the packages',
                    url: '#pricing',
                ),
                ['type' => 'features', 'attributes' => [
                    'heading' => ['en' => "What's included"],
                    'columns' => '3',
                    'items' => ['en' => $service['features']],
                ]],
                ['type' => 'pricing'],
                ['type' => 'request-form', 'attributes' => [
                    'heading' => ['en' => 'Book '.Str::lower($service['title'])],
                    'subheading' => ['en' => 'Pick a package and we will confirm a time within one working day.'],
                    'button' => ['en' => 'Send the request'],
                    'note' => ['en' => 'No payment now. We confirm the price before anyone turns up.'],
                    'background' => ['token' => 'color.tint'],
                ]],
                ['type' => 'faq', 'attributes' => [
                    'heading' => ['en' => 'Questions'],
                    'items' => ['en' => $service['faq']],
                ]],
            ]),
        ])->save();

        $page->setSlugs(['en' => $service['slug'], 'ar' => $service['slug']], ['en' => 'services', 'ar' => 'خدمات']);
        $page->publish();
    }

    // Products ----------------------------------------------------------------

    protected function products(): int
    {
        $products = [
            [
                'title' => 'Citrus all-purpose cleaner',
                'slug' => 'citrus-all-purpose-cleaner',
                'sku' => 'SC-APC',
                'excerpt' => 'The one our teams carry. Cuts grease, safe on sealed stone.',
                'excerpt_ar' => 'منظف متعدد الاستخدامات برائحة الحمضيات.',
                'body' => '<p>Concentrated, so a 5 litre bottle makes 50 litres of spray. Plant-derived surfactants, no bleach, and a scent that fades in ten minutes rather than sitting in the room all day.</p><p>Dilute one part to nine for daily surfaces, one to four for a kitchen after a big cook.</p>',
                'variations' => [
                    ['name' => '750ml spray', 'price' => 28, 'sku' => 'SC-APC-750', 'in_stock' => true],
                    ['name' => '5 litre refill', 'price' => 95, 'sku' => 'SC-APC-5L', 'in_stock' => true],
                    ['name' => '20 litre drum', 'price' => 310, 'sku' => 'SC-APC-20L', 'in_stock' => false],
                ],
            ],
            [
                'title' => 'Microfibre cloth set',
                'slug' => 'microfibre-cloth-set',
                'sku' => 'SC-MF',
                'excerpt' => 'Colour-coded so the bathroom cloth never meets the kitchen.',
                'excerpt_ar' => 'مجموعة مناشف ميكروفايبر بألوان مخصصة.',
                'body' => '<p>Four colours, four jobs: red for bathrooms, blue for glass, green for kitchens, yellow for dusting. 300gsm, and they survive about 200 washes before they stop grabbing.</p>',
                'variations' => [
                    ['name' => 'Pack of 4', 'price' => 45, 'sku' => 'SC-MF-4', 'in_stock' => true],
                    ['name' => 'Pack of 12', 'price' => 120, 'sku' => 'SC-MF-12', 'in_stock' => true],
                ],
            ],
            [
                'title' => 'Steam mop',
                'slug' => 'steam-mop',
                'sku' => 'SC-SM',
                'excerpt' => 'No chemicals on tile and sealed wood. Ready in 30 seconds.',
                'excerpt_ar' => 'ممسحة بخارية للأرضيات.',
                'body' => '<p>1500W, a 400ml tank, and two washable pads in the box. Heats in half a minute and runs about 20 minutes on a fill, which is a floor and a half in most apartments.</p><p>One year of parts and labour, handled by us rather than a call centre.</p>',
                'variations' => [
                    ['name' => 'Standard', 'price' => 420, 'sku' => 'SC-SM-STD', 'in_stock' => true],
                    ['name' => 'With carpet glider', 'price' => 495, 'sku' => 'SC-SM-CG', 'in_stock' => true],
                ],
            ],
        ];

        foreach ($products as $product) {
            $this->product($product);
        }

        return count($products);
    }

    protected function product(array $product): void
    {
        $page = $this->find('products/'.$product['slug']) ?? new Page;

        $page->fill([
            'title' => $product['title'],
            'type' => 'product',
            'schema' => ['type' => 'Product'],
            'seo' => [
                'en' => [
                    'meta_title' => $product['title'].' | Sparkle Clean',
                    'meta_description' => $product['excerpt'],
                ],
            ],
            'data' => [
                'sku' => $product['sku'],
                'featured' => true,
                'variations' => $product['variations'],
                'en' => ['excerpt' => $product['excerpt']],
                'ar' => ['excerpt' => $product['excerpt_ar']],
            ],
            'draft_content' => $this->tree([
                $this->hero(
                    eyebrow: 'Shop',
                    heading: $product['title'],
                    subheading: $product['excerpt'],
                    label: 'Order it',
                    url: '#request',
                ),
                ['type' => 'rich-text', 'attributes' => [
                    'heading' => ['en' => 'What it is'],
                    'body' => ['en' => $product['body']],
                ]],
                ['type' => 'request-form', 'attributes' => [
                    'heading' => ['en' => 'Order '.Str::lower($product['title'])],
                    'subheading' => ['en' => 'Pick a size and we will confirm stock and delivery.'],
                    'button' => ['en' => 'Place the order'],
                    'note' => ['en' => 'Delivery in Dubai is free over AED 200. We invoice on delivery.'],
                    'background' => ['token' => 'color.tint'],
                ]],
            ]),
        ])->save();

        $page->setSlugs(['en' => $product['slug'], 'ar' => $product['slug']], ['en' => 'products', 'ar' => 'products']);
        $page->publish();
    }

    // The ordinary pages --------------------------------------------------------

    protected function home(): void
    {
        $this->page('Home', 'home', [
            $this->hero(
                eyebrow: 'Dubai, since 2014',
                heading: 'A clean flat, without the Sunday spent on it',
                subheading: 'Home and office cleaning by a vetted team that turns up when it says it will. Fixed prices, no contracts on the home side.',
                label: 'See our services',
                url: '/services',
                align: 'center',
            ),
            ['type' => 'features', 'attributes' => [
                'heading' => ['en' => 'Why people stay with us'],
                'columns' => '3',
                'items' => ['en' => [
                    ['icon' => 'heroicon-o-currency-dollar', 'title' => 'Fixed prices', 'body' => 'The price on the page is the price on the invoice.'],
                    ['icon' => 'heroicon-o-shield-check', 'title' => 'Vetted and insured', 'body' => 'Police-checked, trained, and covered to AED 1m.'],
                    ['icon' => 'heroicon-o-user-group', 'title' => 'The same team', 'body' => 'They learn your home. You stop giving instructions.'],
                ]],
            ]],
            ['type' => 'collection', 'attributes' => [
                'page_type' => 'service',
                'heading' => ['en' => 'What we clean'],
                'source' => 'all',
                'order' => 'title',
                'columns' => '3',
                'background' => ['token' => 'color.tint'],
            ]],
            ['type' => 'testimonials', 'attributes' => [
                'heading' => ['en' => 'What clients say'],
                'items' => ['en' => [
                    ['quote' => 'Booked the move-out clean on a Tuesday, deposit back on the Thursday.', 'name' => 'Hannah R.', 'role' => 'Marina'],
                    ['quote' => 'Third year on the office contract. I have never had to chase them.', 'name' => 'Omar K.', 'role' => 'Office manager, JLT'],
                    ['quote' => 'They photograph every room when they finish. I stopped worrying.', 'name' => 'Priya S.', 'role' => 'Arabian Ranches'],
                ]],
            ]],
            ['type' => 'collection', 'attributes' => [
                'page_type' => 'product',
                'heading' => ['en' => 'The products we use, if you want them'],
                'source' => 'all',
                'order' => 'title',
                'columns' => '3',
                'limit' => 3,
            ]],
            ['type' => 'cta', 'attributes' => [
                'heading' => ['en' => 'Get a price today'],
                'body' => ['en' => 'Tell us the size of the place and when suits. We come back within one working day.'],
                'cta_label' => ['en' => 'Ask for a quote'],
                'cta_url' => '/contact',
                'background' => ['token' => 'color.primary'],
            ]],
        ]);
    }

    protected function about(): void
    {
        $this->page('About us', 'about', [
            $this->hero(
                eyebrow: 'About',
                heading: 'Eleven people, one van each, no call centre',
                subheading: 'Sparkle Clean started in 2014 with one team and a Hilux. We have grown slowly on purpose.',
            ),
            ['type' => 'rich-text', 'attributes' => [
                'heading' => ['en' => 'How we work'],
                'body' => ['en' => '<p>Most cleaning companies in Dubai are booking platforms: you pay an app, and whoever is nearest turns up. We are the opposite. Eleven cleaners, all on our payroll, all trained here, and the same person comes back to your home every visit.</p><p>That is slower to grow and it is why we still cover only Dubai. It is also why our repeat rate is 82 percent and why nobody has to explain where the vacuum lives twice.</p>'],
            ]],
            ['type' => 'features', 'attributes' => [
                'heading' => ['en' => 'The rules we hold ourselves to'],
                'columns' => '2',
                'items' => ['en' => [
                    ['icon' => 'heroicon-o-clock', 'title' => 'A two-hour window', 'body' => 'And a call if we are running early or late.'],
                    ['icon' => 'heroicon-o-banknotes', 'title' => 'No surprise line items', 'body' => 'Supplies, insurance and travel are in the price.'],
                    ['icon' => 'heroicon-o-hand-raised', 'title' => 'Say no to us freely', 'body' => 'Skip a room, change a day, end a contract with a month.'],
                    ['icon' => 'heroicon-o-heart', 'title' => 'Paid properly', 'body' => 'Above-market wages, overtime paid, passports never held.'],
                ]],
            ]],
            ['type' => 'cta', 'attributes' => [
                'heading' => ['en' => 'Come and see'],
                'body' => ['en' => 'Book one clean. There is nothing to cancel if you would rather we did not come back.'],
                'cta_label' => ['en' => 'Browse services'],
                'cta_url' => '/services',
            ]],
        ], schema: ['type' => 'AboutPage']);
    }

    /**
     * The Services page, built by hand at the slug the type would otherwise
     * generate an index for. Publishing it is what takes /services over from
     * the generated listing, which is the point of it being demonstrated here.
     */
    protected function servicesPage(): void
    {
        $this->page('Services', 'services', [
            $this->hero(
                eyebrow: 'Services',
                heading: 'Pick the clean you actually need',
                subheading: 'Three services, each with three packages. Prices are per visit and include supplies.',
                align: 'center',
            ),
            ['type' => 'collection', 'attributes' => [
                'page_type' => 'service',
                'heading' => ['en' => 'Everything we do'],
                'source' => 'all',
                'order' => 'title',
                'columns' => '3',
            ]],
            ['type' => 'faq', 'attributes' => [
                'heading' => ['en' => 'Before you book'],
                'items' => ['en' => [
                    ['question' => 'Do you charge per hour or per job?', 'answer' => 'Per job. The package you pick is the price, however long it takes us.'],
                    ['question' => 'What if my place is bigger than the tier says?', 'answer' => 'Tell us in the request. We will say so before the visit, not after it.'],
                    ['question' => 'Do you clean at weekends?', 'answer' => 'Friday and Saturday, same price. Sunday is the team\'s day off.'],
                ]],
            ]],
            ['type' => 'cta', 'attributes' => [
                'heading' => ['en' => 'Not sure which one?'],
                'body' => ['en' => 'Send us the size of the place and what state it is in. We will tell you which package fits.'],
                'cta_label' => ['en' => 'Ask us'],
                'cta_url' => '/contact',
                'background' => ['token' => 'color.tint'],
            ]],
        ], schema: ['type' => 'CollectionPage']);
    }

    protected function productsPage(): void
    {
        $this->page('Products', 'products', [
            $this->hero(
                eyebrow: 'Shop',
                heading: 'The kit our teams carry',
                subheading: 'What we buy by the pallet, sold at what it costs us plus delivery.',
                align: 'center',
            ),
            ['type' => 'collection', 'attributes' => [
                'page_type' => 'product',
                'heading' => ['en' => 'In stock'],
                'source' => 'all',
                'order' => 'title',
                'columns' => '3',
            ]],
            ['type' => 'cta', 'attributes' => [
                'heading' => ['en' => 'Buying for a building?'],
                'body' => ['en' => 'Drums and pallet quantities are priced separately. Tell us what you get through in a month.'],
                'cta_label' => ['en' => 'Ask for trade pricing'],
                'cta_url' => '/contact',
                'background' => ['token' => 'color.tint'],
            ]],
        ], schema: ['type' => 'CollectionPage']);
    }

    protected function contact(): void
    {
        $this->page('Contact us', 'contact', [
            $this->hero(
                eyebrow: 'Contact',
                heading: 'Tell us what needs cleaning',
                subheading: 'We answer every request within one working day, usually the same morning.',
            ),
            ['type' => 'request-form', 'attributes' => [
                'heading' => ['en' => 'Ask for a quote'],
                'subheading' => ['en' => 'No package to pick here. Describe the place and we will come back with a price.'],
                'button' => ['en' => 'Send it'],
                'note' => ['en' => 'Or call +971 4 555 0100, 8am to 6pm.'],
            ]],
            ['type' => 'features', 'attributes' => [
                'heading' => ['en' => 'Where to find us'],
                'columns' => '3',
                'items' => ['en' => [
                    ['icon' => 'heroicon-o-phone', 'title' => 'Phone', 'body' => '+971 4 555 0100, 8am to 6pm.'],
                    ['icon' => 'heroicon-o-envelope', 'title' => 'Email', 'body' => 'hello@sparkleclean.test'],
                    ['icon' => 'heroicon-o-map-pin', 'title' => 'Yard', 'body' => 'Unit 12, Al Quoz Industrial 3, Dubai.'],
                ]],
            ]],
        ], schema: ['type' => 'ContactPage']);
    }

    // Menus -------------------------------------------------------------------

    protected function menus(): void
    {
        Menu::forLocation('primary')->update(['items' => [
            $this->item('Services', '/services'),
            $this->item('Products', '/products'),
            $this->item('About', '/about'),
            $this->item('Contact', '/contact'),
        ]]);

        Menu::forLocation('footer')->update(['items' => [
            $this->item('Home deep clean', '/services/home-deep-clean'),
            $this->item('Move-out clean', '/services/move-out-clean'),
            $this->item('Office contract', '/services/office-contract'),
            $this->item('Contact', '/contact'),
        ]]);
    }

    protected function item(string $label, string $url): array
    {
        return [
            'id' => 'm_'.Str::lower(Str::random(6)),
            'label' => ['en' => $label, 'ar' => $label],
            'url' => ['en' => $url, 'ar' => '/ar'.$url],
            'target' => '_self',
            'hidden' => false,
            'children' => [],
        ];
    }

    // Plumbing ------------------------------------------------------------------

    /** @param array<int, array<string, mixed>> $tree */
    protected function page(string $title, string $slug, array $tree, array $schema = []): void
    {
        $page = $this->find($slug) ?? new Page;

        $page->fill([
            'title' => $title,
            'type' => 'page',
            'layout' => 'marketing',
            'schema' => $schema ?: null,
            'seo' => ['en' => [
                'meta_title' => $title === 'Home' ? 'Sparkle Clean | Home and office cleaning in Dubai' : $title.' | Sparkle Clean',
            ]],
            'draft_content' => $this->tree($tree),
        ])->save();

        $page->setSlugs(['en' => $slug, 'ar' => $slug]);
        $page->publish();
    }

    /**
     * Attributes on top of each block's own defaults, with ids filled in.
     *
     * BlockRegistry::tree() is the same call the panel makes when it seeds a
     * page type's template, so seeded pages and created ones are shaped
     * identically.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    protected function tree(array $nodes): array
    {
        $registry = app(BlockRegistry::class);

        return $registry->tree(array_map(function (array $node) use ($registry) {
            $block = $registry->resolve($node['type']);

            $defaults = $block && method_exists($block, 'defaults') ? $block::defaults() : [];

            return [
                'type' => $node['type'],
                'attributes' => array_replace($defaults, $node['attributes'] ?? []),
            ];
        }, $nodes));
    }

    /** @return array<string, mixed> */
    protected function hero(
        string $heading,
        ?string $eyebrow = null,
        ?string $subheading = null,
        ?string $label = null,
        ?string $url = null,
        string $align = 'left',
    ): array {
        return ['type' => 'hero', 'attributes' => array_filter([
            'eyebrow' => $eyebrow ? ['en' => $eyebrow] : null,
            'heading' => ['en' => $heading],
            'subheading' => $subheading ? ['en' => $subheading] : null,
            'cta_label' => $label ? ['en' => $label] : null,
            'cta_url' => $url,
            'align' => $align,
        ])];
    }

    protected function find(string $slug): ?Page
    {
        return Page::whereHas('slugs', fn ($query) => $query->where('locale', 'en')->where('slug', $slug))->first();
    }
}
