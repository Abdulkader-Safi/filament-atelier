<?php

declare(strict_types=1);

/**
 * Panel-facing strings: field labels, help text, block picker names.
 * Not page content, which carries its own per-locale map inside each
 * block's JSON (see `translatable()` on each block class).
 */
return [

    'navigation_groups' => [
        'content' => 'Content',
        'settings' => 'Settings',
    ],

    'block_categories' => [
        'content' => 'Content',
        'layout' => 'Layout',
        'media' => 'Media',
    ],

    'shared_controls' => [
        'section_heading' => 'Section style',
        'background' => 'Background',
        'background_placeholder' => 'Default',
        'padding' => 'Vertical space',
        'padding_placeholder' => "The block's own",
        'padding_options' => [
            'none' => 'None',
            'tight' => 'Tight',
            'normal' => 'Normal',
            'loose' => 'Loose',
        ],
    ],

    'blocks' => [

        'hero' => [
            'label' => 'Hero',
            'fields' => [
                'eyebrow' => 'Eyebrow',
                'heading' => 'Heading',
                'subheading' => 'Subheading',
                'cta_label' => 'Button label',
                'cta_url' => 'Button link',
                'image' => 'Background image',
                'align' => 'Alignment',
            ],
            'options' => [
                'align' => [
                    'left' => 'Left',
                    'center' => 'Centre',
                ],
            ],
        ],

        'cta' => [
            'label' => 'Call to action',
            'fields' => [
                'heading' => 'Heading',
                'body' => 'Text',
                'cta_label' => 'Button label',
                'cta_url' => 'Button link',
            ],
        ],

        'faq' => [
            'label' => 'FAQ',
            'fields' => [
                'heading' => 'Heading',
                'items' => 'Questions',
                'question' => 'Question',
                'answer' => 'Answer',
            ],
        ],

        'features' => [
            'label' => 'Features',
            'fields' => [
                'heading' => 'Heading',
                'subheading' => 'Subheading',
                'columns' => 'Columns',
                'items' => 'Features',
                'icon' => 'Icon',
                'icon_help' => 'Any Heroicon name.',
                'title' => 'Title',
                'body' => 'Text',
                'item_fallback' => 'Feature',
            ],
            'options' => [
                'columns' => [
                    'two' => 'Two',
                    'three' => 'Three',
                    'four' => 'Four',
                ],
            ],
        ],

        'gallery' => [
            'label' => 'Gallery',
            'fields' => [
                'heading' => 'Heading',
                'columns' => 'Columns',
                'images' => 'Images',
                'image' => 'Image',
                'alt' => 'Alt text',
            ],
            'options' => [
                'columns' => [
                    'two' => 'Two',
                    'three' => 'Three',
                    'four' => 'Four',
                ],
            ],
        ],

        'image' => [
            'label' => 'Image',
            'fields' => [
                'image' => 'Image',
                'alt' => 'Alt text',
                'alt_help' => 'What the image shows. Leave empty only if it is decorative.',
                'caption' => 'Caption',
                'width' => 'Width',
            ],
            'options' => [
                'width' => [
                    'container' => 'Container',
                    'wide' => 'Wide',
                    'full' => 'Full bleed',
                ],
            ],
        ],

        'logo_wall' => [
            'label' => 'Logo wall',
            'fields' => [
                'heading' => 'Heading',
                'logos' => 'Logos',
                'logo' => 'Logo',
                'name' => 'Company',
                'url' => 'Link',
            ],
        ],

        'rich_text' => [
            'label' => 'Rich text',
            'fields' => [
                'heading' => 'Heading',
                'body' => 'Body',
            ],
        ],

        'testimonials' => [
            'label' => 'Testimonials',
            'fields' => [
                'heading' => 'Heading',
                'items' => 'Testimonials',
                'quote' => 'Quote',
                'name' => 'Name',
                'role' => 'Role',
                'avatar' => 'Photo',
                'item_fallback' => 'Testimonial',
            ],
        ],

        'collection' => [
            'label' => 'Collection',
            'fields' => [
                'page_type' => 'What to list',
                'heading' => 'Heading',
                'source' => 'Which ones',
                'source_help' => 'Under this page means by slug path: a page at services shows services/web-design.',
                'pages' => 'Chosen',
                'pages_add' => 'Add one',
                'pages_help' => 'Drag to set the order they appear in.',
                'order' => 'Order',
                'limit' => 'How many',
                'limit_placeholder' => 'All of them',
                'columns' => 'Columns',
                'empty' => 'Text when there is nothing to list',
                'empty_placeholder' => 'Nothing to show yet.',
            ],
            'options' => [
                'source' => [
                    'all' => 'Everything published',
                    'picked' => 'The ones I choose',
                    'children' => 'Only the ones under this page',
                ],
                'order' => [
                    'title' => 'Title, A to Z',
                    'newest' => 'Newest first',
                    'oldest' => 'Oldest first',
                ],
                'columns' => [
                    'one' => 'One',
                    'two' => 'Two',
                    'three' => 'Three',
                    'four' => 'Four',
                ],
            ],
        ],

    ],

    'pages' => [

        'menu_manager' => [
            'nav_label' => 'Menus',
            'title' => 'Menus',
            'subheading' => 'Navigation for the public site. Changes save as you make them.',
            'add_source_prefix' => 'Add',
            'edit_item' => [
                'trigger' => 'Edit',
                'modal_heading' => 'Edit item',
                'submit' => 'Save',
                'label_field' => 'Label',
                'url_field' => 'URL',
                'url_help' => 'A path like /about, or a full https:// URL.',
                'target_field' => 'Opens in',
                'target_same_tab' => 'Same tab',
                'target_new_tab' => 'New tab',
            ],
            'delete_item' => [
                'trigger' => 'Delete',
                'modal_heading' => 'Delete this item?',
            ],
        ],

        'page_editor' => [
            'fallback_title' => 'Page editor',
            'back_to_pages' => 'Back to pages',
            'back_to_sections' => 'Back to sections',
            'pages' => 'Pages',
            'sections' => 'Sections',
            'section_fallback' => 'Section',
            'section_settings' => 'Section settings',
            'select_section_first' => 'Select a section first',
            'open_preview' => 'Open preview in a new tab',
            'publish' => 'Publish',
            'status' => [
                'unpublished' => 'Unpublished changes',
                'published' => 'Published',
                'draft' => 'Draft',
            ],
            'widths' => [
                'desktop' => 'Desktop',
                'tablet' => 'Tablet',
                'mobile' => 'Mobile',
            ],
            'search_pages' => 'Search pages',
            'search_sections' => 'Search sections',
            'editing_badge' => 'editing',
            'page_search_no_match_prefix' => 'No page matches "',
            'page_search_no_match_suffix' => '".',
            'section_search_no_match_prefix' => 'No section matches "',
            'section_search_no_match_suffix' => '".',
            'move_up' => 'Move up',
            'move_down' => 'Move down',
            'duplicate' => 'Duplicate',
            'show' => 'Show',
            'hide' => 'Hide',
            'delete' => 'Delete',
            'delete_confirm' => 'Delete this section?',
            'drag_to_reorder' => 'Drag to reorder',
            'settings' => 'Settings',
            'empty_state' => 'Nothing here yet. Add your first section below.',
            'add_section' => 'Add section',
            'preview_title' => 'Page preview',
        ],

        'site_details' => [
            'nav_label' => 'Site details',
            'title' => 'Site details',
            'subheading' => 'The organisation behind the site. Used in the structured data on every page.',
            'save_action' => 'Save changes',
            'saved_notification' => 'Saved',
            'sections' => [
                'identity' => [
                    'heading' => 'Identity',
                    'description' => 'Who the site belongs to. Leave the type as Organization unless the client has premises a customer can visit.',
                ],
                'profiles' => [
                    'heading' => 'Profiles',
                    'description' => 'Social and directory URLs. They become sameAs, which is how a search engine ties this site to accounts it already knows.',
                ],
                'contact' => [
                    'heading' => 'Contact',
                    'description' => 'Shown to search engines, not on the site. Nothing here is rendered into a page.',
                ],
                'contact_points' => [
                    'heading' => 'Contact points',
                    'description' => 'Who answers, and in which language. A bare telephone number says none of that.',
                ],
                'legal' => [
                    'heading' => 'Legal',
                    'description' => 'Optional, and a trust signal for a business search.',
                ],
            ],
            'fields' => [
                'name' => 'Name',
                'description' => 'Description',
                'legal_name' => 'Legal name',
                'legal_name_help' => 'Only if it differs from the trading name.',
                'type' => 'Type',
                'type_help' => 'A LocalBusiness type unlocks the contact details below, and is what puts a client on the map.',
                'logo' => 'Logo',
                'logo_help' => 'Square or wide, at least 112px tall. Used as the organisation logo in search results.',
                'same_as' => 'Profile URLs',
                'same_as_add' => 'Add a profile',
                'telephone' => 'Telephone',
                'email' => 'Email',
                'address_street' => 'Street address',
                'address_locality' => 'City',
                'address_region' => 'Region or emirate',
                'address_postal_code' => 'Postal code',
                'address_country' => 'Country code',
                'address_country_help' => 'Two letters, e.g. AE, SA, LB.',
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
                'price_range' => 'Price range',
                'price_range_help' => 'Free text, e.g. $$ or 500 to 5000 AED.',
                'area_served' => 'Areas served',
                'area_served_help' => 'Comma separated, e.g. Dubai, Abu Dhabi.',
                'opening_hours' => 'Opening hours',
                'opening_hours_days' => 'Days',
                'opens' => 'Opens',
                'closes' => 'Closes',
                'opening_hours_add' => 'Add hours',
                'opening_hours_help' => 'One row per set of hours. Group the days that share them, and add a second row for the days that differ.',
                'opening_hours_fallback' => 'Hours',
                'contact_points' => 'Contact points',
                'contact_point_for' => 'For',
                'contact_point_languages' => 'Languages',
                'contact_point_languages_help' => 'Comma separated, e.g. Arabic, English.',
                'contact_points_add' => 'Add a contact point',
                'contact_point_fallback' => 'Contact point',
                'founding_date' => 'Founded',
                'employees' => 'Employees',
                'vat_id' => 'VAT number',
                'tax_id' => 'Tax number',
            ],
            'options' => [
                'contact_point_for' => [
                    'customer_support' => 'Customer support',
                    'sales' => 'Sales',
                    'billing_support' => 'Billing',
                    'technical_support' => 'Technical support',
                    'reservations' => 'Reservations',
                ],
            ],
            'days' => [
                'monday' => 'Mon',
                'tuesday' => 'Tue',
                'wednesday' => 'Wed',
                'thursday' => 'Thu',
                'friday' => 'Fri',
                'saturday' => 'Sat',
                'sunday' => 'Sun',
            ],
            'types' => [
                'organization' => 'Organization (no physical premises)',
                'local_business' => 'Local business',
                'professional_service' => 'Professional service',
                'store' => 'Shop',
                'restaurant' => 'Restaurant or cafe',
                'medical_business' => 'Clinic or medical practice',
                'real_estate_agent' => 'Real estate agency',
                'auto_repair' => 'Garage or auto repair',
                'home_and_construction_business' => 'Construction or trades',
                'educational_organization' => 'School or training provider',
            ],
        ],

    ],

    'resources' => [

        'page' => [
            'fallback_label' => 'page',
            'type_section_heading' => ':type details',
            'actions' => [
                'new_prefix' => 'New',
                'build' => 'Edit page content',
                'view' => 'View live',
                'publish' => 'Publish',
                'unpublish' => 'Unpublish',
                'unpublish_modal_description' => 'The page will 404 on the public site. Its content is kept.',
            ],
            'sections' => [
                'structured_data' => [
                    'heading' => 'Structured data',
                    'description' => 'What this page is, for search engines. It becomes the JSON-LD in the head.',
                ],
            ],
            'tabs' => [
                'faq' => 'FAQ',
                'breadcrumbs' => 'Breadcrumbs',
            ],
            'fields' => [
                'title_help' => 'Internal name, and the fallback for the meta title.',
                'layout' => 'Layout',
                'layout_placeholder' => 'Default',
                'layout_help' => 'The shell wrapped around this page. Leave as Default to use the site-wide one.',
                'slug' => 'Slug',
                'slug_help' => 'Leave empty to generate one from the title.',
                'meta_title' => 'Meta title',
                'meta_title_help' => 'Around 60 characters. Falls back to the page title.',
                'meta_description' => 'Meta description',
                'meta_description_help' => 'Around 155 characters.',
                'og_image' => 'Social share image',
                'og_image_help' => '1200 by 630 is the safe size.',
                'noindex' => 'Hide from search engines',
                'noindex_help' => 'Adds a noindex tag and drops the page from the sitemap. The page stays public.',
                'nofollow' => 'Tell search engines not to follow its links',
                'nofollow_help' => 'Independent of the above. A page can be indexed and still not pass link credit.',
                'canonical' => 'Canonical URL',
                'canonical_help' => "Leave empty to use this page's own URL.",
                'schema_type' => 'Type for search engines',
                'schema_type_help' => 'Standard page is right for most.',
                'faq_questions' => 'Questions',
                'faq_question' => 'Question',
                'faq_answer' => 'Answer',
                'faq_add' => 'Add a question',
                'faq_help' => 'Type them here when nothing on the page generates them. The answers should still be somewhere a visitor can read, in prose or anywhere else; a question that appears nowhere on the page is against the search engines\' own rules.',
                'breadcrumbs_mode' => 'Trail',
                'breadcrumbs_mode_help' => 'From the slug path builds Home › Services › This page for a page at services/this-page.',
                'breadcrumbs_trail' => 'Trail',
                'breadcrumb_name' => 'Name',
                'breadcrumb_url' => 'URL',
                'breadcrumb_url_help' => 'Leave the last one empty to use this page.',
                'breadcrumb_step_fallback' => 'Step',
                'breadcrumbs_add' => 'Add a step',
            ],
            'options' => [
                'breadcrumbs_mode' => [
                    'auto' => 'From the slug path',
                    'custom' => 'Typed here',
                    'none' => 'None',
                ],
            ],
            'table' => [
                'last_edited' => 'Last edited',
                'status_unpublished_changes' => 'Unpublished changes',
                'open_builder' => 'Open builder',
                'empty_heading' => 'No pages yet',
                'empty_description' => 'Create one, fill in its settings, then build it from sections.',
            ],
        ],

    ],

    'schema' => [

        'page_types' => [
            'options' => [
                'web_page' => 'Standard page',
                'about_page' => 'About page',
                'contact_page' => 'Contact page',
                'collection_page' => 'Listing page',
                'article' => 'Article or blog post',
                'service' => 'Service',
                'product' => 'Product',
                'event' => 'Event',
                'person' => 'Person or profile',
                'job_posting' => 'Job vacancy',
                'availability' => [
                    'in_stock' => 'In stock',
                    'out_of_stock' => 'Out of stock',
                    'pre_order' => 'Pre-order',
                ],
                'condition' => [
                    'new' => 'New',
                    'used' => 'Used',
                    'refurbished' => 'Refurbished',
                    'damaged' => 'Damaged',
                ],
                'event_status' => [
                    'scheduled' => 'Going ahead',
                    'rescheduled' => 'Rescheduled',
                    'postponed' => 'Postponed',
                    'cancelled' => 'Cancelled',
                    'moved_online' => 'Moved online',
                ],
                'attendance' => [
                    'offline' => 'In person',
                    'online' => 'Online',
                    'mixed' => 'Both',
                ],
                'employment_type' => [
                    'full_time' => 'Full time',
                    'part_time' => 'Part time',
                    'contractor' => 'Contract',
                    'temporary' => 'Temporary',
                    'intern' => 'Internship',
                ],
                'salary_unit' => [
                    'hour' => 'Hour',
                    'day' => 'Day',
                    'week' => 'Week',
                    'month' => 'Month',
                    'year' => 'Year',
                ],
            ],
            'fields' => [
                'article' => [
                    'author' => 'Author',
                    'author_help' => 'A person. Leave empty and the organisation is credited instead.',
                    'published_at' => 'Published',
                    'published_at_help' => 'Only if it differs from when the page was published.',
                ],
                'service' => [
                    'service_type' => 'Service type',
                    'service_type_help' => 'What it is, plainly: Web design, Tax advice.',
                    'area_served' => 'Areas served',
                    'area_served_help' => 'Comma separated. Leave empty to use the site details.',
                    'price' => 'Price',
                    'currency' => 'Currency',
                    'currency_help' => 'Three letters, e.g. AED.',
                ],
                'product' => [
                    'sku' => 'SKU',
                    'brand' => 'Brand',
                    'price' => 'Price',
                    'currency' => 'Currency',
                    'currency_help' => 'Three letters, e.g. AED.',
                    'availability' => 'Availability',
                    'condition' => 'Condition',
                    'price_valid_until' => 'Price valid until',
                    'price_valid_until_help' => 'A price with no end date is treated as stale.',
                ],
                'event' => [
                    'start' => 'Starts',
                    'end' => 'Ends',
                    'location' => 'Location',
                    'location_help' => 'Venue name, or Online.',
                    'location_address' => 'Address',
                    'price' => 'Ticket price',
                    'currency' => 'Currency',
                    'status' => 'Status',
                    'status_help' => 'Cancelling here is what stops it being advertised.',
                    'attendance' => 'Attendance',
                ],
                'job_posting' => [
                    'posted_at' => 'Posted',
                    'posted_at_help' => 'Only if it differs from when the page was published.',
                    'valid_through' => 'Closes',
                    'valid_through_help' => 'A vacancy with no closing date stays listed forever.',
                    'employment_type' => 'Type',
                    'remote' => 'Remote',
                    'remote_help' => 'Without this a remote role is filtered out of remote searches.',
                    'location' => 'City',
                    'location_help' => 'Leave empty to use the site details.',
                    'country' => 'Country code',
                    'salary' => 'Salary',
                    'currency' => 'Currency',
                    'salary_unit' => 'Per',
                ],
                'person' => [
                    'name' => 'Full name',
                    'name_help' => 'Leave empty to use the meta title.',
                    'job_title' => 'Job title',
                    'same_as' => 'Profile URL',
                    'same_as_help' => 'LinkedIn, or wherever this person is already known.',
                ],
            ],
        ],

    ],

];
