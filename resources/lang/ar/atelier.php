<?php

declare(strict_types=1);

/**
 * Panel-facing strings: field labels, help text, block picker names.
 * Not page content, which carries its own per-locale map inside each
 * block's JSON (see `translatable()` on each block class).
 */
return [

    'navigation_groups' => [
        'content' => 'المحتوى',
        'settings' => 'الإعدادات',
    ],

    'block_categories' => [
        'content' => 'المحتوى',
        'layout' => 'التخطيط',
        'media' => 'الوسائط',
    ],

    'shared_controls' => [
        'section_heading' => 'نمط القسم',
        'background' => 'الخلفية',
        'background_placeholder' => 'الافتراضية',
        'padding' => 'المسافة العمودية',
        'padding_placeholder' => 'الخاصة بالقسم',
        'padding_options' => [
            'none' => 'بلا',
            'tight' => 'ضيقة',
            'normal' => 'عادية',
            'loose' => 'واسعة',
        ],
    ],

    'blocks' => [

        'hero' => [
            'label' => 'الواجهة الرئيسية',
            'fields' => [
                'eyebrow' => 'نص علوي',
                'heading' => 'العنوان',
                'subheading' => 'العنوان الفرعي',
                'cta_label' => 'نص الزر',
                'cta_url' => 'رابط الزر',
                'image' => 'صورة الخلفية',
                'align' => 'المحاذاة',
            ],
            'options' => [
                'align' => [
                    'left' => 'إلى البداية',
                    'center' => 'في الوسط',
                ],
            ],
        ],

        'cta' => [
            'label' => 'دعوة لاتخاذ إجراء',
            'fields' => [
                'heading' => 'العنوان',
                'body' => 'النص',
                'cta_label' => 'نص الزر',
                'cta_url' => 'رابط الزر',
            ],
        ],

        'faq' => [
            'label' => 'الأسئلة الشائعة',
            'fields' => [
                'heading' => 'العنوان',
                'items' => 'الأسئلة',
                'question' => 'السؤال',
                'answer' => 'الإجابة',
            ],
        ],

        'features' => [
            'label' => 'المزايا',
            'fields' => [
                'heading' => 'العنوان',
                'subheading' => 'العنوان الفرعي',
                'columns' => 'الأعمدة',
                'items' => 'المزايا',
                'icon' => 'الأيقونة',
                'icon_help' => 'أي اسم من أيقونات Heroicons.',
                'title' => 'العنوان',
                'body' => 'النص',
                'item_fallback' => 'ميزة',
            ],
            'options' => [
                'columns' => [
                    'two' => 'عمودان',
                    'three' => 'ثلاثة',
                    'four' => 'أربعة',
                ],
            ],
        ],

        'gallery' => [
            'label' => 'معرض الصور',
            'fields' => [
                'heading' => 'العنوان',
                'columns' => 'الأعمدة',
                'images' => 'الصور',
                'image' => 'الصورة',
                'alt' => 'النص البديل',
            ],
            'options' => [
                'columns' => [
                    'two' => 'عمودان',
                    'three' => 'ثلاثة',
                    'four' => 'أربعة',
                ],
            ],
        ],

        'image' => [
            'label' => 'صورة',
            'fields' => [
                'image' => 'الصورة',
                'alt' => 'النص البديل',
                'alt_help' => 'ما تعرضه الصورة. اتركه فارغًا فقط إذا كانت للزينة.',
                'caption' => 'التعليق',
                'width' => 'العرض',
            ],
            'options' => [
                'width' => [
                    'container' => 'ضمن الحاوية',
                    'wide' => 'عريضة',
                    'full' => 'بعرض الشاشة',
                ],
            ],
        ],

        'logo_wall' => [
            'label' => 'شعارات العملاء',
            'fields' => [
                'heading' => 'العنوان',
                'logos' => 'الشعارات',
                'logo' => 'الشعار',
                'name' => 'الشركة',
                'url' => 'الرابط',
            ],
        ],

        'rich_text' => [
            'label' => 'نص منسق',
            'fields' => [
                'heading' => 'العنوان',
                'body' => 'المحتوى',
            ],
        ],

        'testimonials' => [
            'label' => 'آراء العملاء',
            'fields' => [
                'heading' => 'العنوان',
                'items' => 'الآراء',
                'quote' => 'الاقتباس',
                'name' => 'الاسم',
                'role' => 'المنصب',
                'avatar' => 'الصورة',
                'item_fallback' => 'رأي',
            ],
        ],

        'collection' => [
            'label' => 'قائمة صفحات',
            'fields' => [
                'page_type' => 'ما الذي يُعرض',
                'heading' => 'العنوان',
                'source' => 'أيها',
                'source_help' => 'التي تحت هذه الصفحة تعني حسب مسار الرابط: صفحة على services تعرض services/web-design.',
                'pages' => 'المختارة',
                'pages_add' => 'أضف واحدة',
                'pages_help' => 'اسحب لترتيب ظهورها.',
                'order' => 'الترتيب',
                'limit' => 'العدد',
                'limit_placeholder' => 'الكل',
                'columns' => 'الأعمدة',
                'empty' => 'النص عندما لا يوجد ما يُعرض',
                'empty_placeholder' => 'لا يوجد شيء لعرضه بعد.',
            ],
            'options' => [
                'source' => [
                    'all' => 'كل المنشور',
                    'picked' => 'التي أختارها',
                    'children' => 'التي تحت هذه الصفحة فقط',
                ],
                'order' => [
                    'title' => 'العنوان، أبجديًا',
                    'newest' => 'الأحدث أولًا',
                    'oldest' => 'الأقدم أولًا',
                ],
                'columns' => [
                    'one' => 'عمود واحد',
                    'two' => 'عمودان',
                    'three' => 'ثلاثة',
                    'four' => 'أربعة',
                ],
            ],
        ],

    ],

    'pages' => [

        'menu_manager' => [
            'nav_label' => 'القوائم',
            'title' => 'القوائم',
            'subheading' => 'روابط التنقل في الموقع العام. تُحفظ التغييرات فور إجرائها.',
            'add_source' => 'إضافة :source',
            'sources' => [
                'page' => 'صفحة',
            ],
            'no_locations' => 'لا توجد مواقع قوائم مسجلة. أضف واحدًا في :config، في المكان نفسه الذي توجد فيه :locales.',
            'empty' => 'لا يوجد شيء بعد. أضف عنصرًا أدناه.',
            'add_custom_link' => 'إضافة رابط مخصص',
            'untitled' => 'بلا عنوان',
            'show_item' => 'إظهار في الموقع العام',
            'hide_item' => 'إخفاء من الموقع العام',
            'edit_item' => [
                'trigger' => 'تعديل',
                'modal_heading' => 'تعديل العنصر',
                'submit' => 'حفظ',
                'label_field' => 'النص',
                'url_field' => 'الرابط',
                'url_help' => 'مسار مثل /about، أو رابط كامل يبدأ بـ https://.',
                'target_field' => 'يفتح في',
                'target_same_tab' => 'التبويب نفسه',
                'target_new_tab' => 'تبويب جديد',
            ],
            'delete_item' => [
                'trigger' => 'حذف',
                'modal_heading' => 'حذف هذا العنصر؟',
            ],
        ],

        'page_editor' => [
            'fallback_title' => 'محرر الصفحة',
            'back_to_pages' => 'العودة إلى الصفحات',
            'back_to_sections' => 'العودة إلى الأقسام',
            'pages' => 'الصفحات',
            'sections' => 'الأقسام',
            'section_fallback' => 'قسم',
            'section_settings' => 'إعدادات القسم',
            'select_section_first' => 'اختر قسمًا أولًا',
            'open_preview' => 'فتح المعاينة في تبويب جديد',
            'publish' => 'نشر',
            'status' => [
                'unpublished' => 'تغييرات غير منشورة',
                'published' => 'منشورة',
                'draft' => 'مسودة',
            ],
            'widths' => [
                'desktop' => 'سطح المكتب',
                'tablet' => 'جهاز لوحي',
                'mobile' => 'جوال',
            ],
            'search_pages' => 'ابحث في الصفحات',
            'search_sections' => 'ابحث في الأقسام',
            'editing_badge' => 'قيد التعديل',
            'page_search_no_match_prefix' => 'لا توجد صفحة تطابق "',
            'page_search_no_match_suffix' => '".',
            'section_search_no_match_prefix' => 'لا يوجد قسم يطابق "',
            'section_search_no_match_suffix' => '".',
            'move_up' => 'نقل لأعلى',
            'move_down' => 'نقل لأسفل',
            'duplicate' => 'تكرار',
            'show' => 'إظهار',
            'hide' => 'إخفاء',
            'delete' => 'حذف',
            'delete_confirm' => 'حذف هذا القسم؟',
            'drag_to_reorder' => 'اسحب لإعادة الترتيب',
            'settings' => 'الإعدادات',
            'empty_state' => 'لا يوجد شيء بعد. أضف أول قسم أدناه.',
            'add_section' => 'إضافة قسم',
            'preview_title' => 'معاينة الصفحة',
        ],

        'site_details' => [
            'nav_label' => 'بيانات الموقع',
            'title' => 'بيانات الموقع',
            'subheading' => 'الجهة التي يتبع لها الموقع. تُستخدم في البيانات المنظمة في كل صفحة.',
            'save_action' => 'حفظ التغييرات',
            'saved_notification' => 'تم الحفظ',
            'sections' => [
                'identity' => [
                    'heading' => 'الهوية',
                    'description' => 'لمن يتبع الموقع. اترك النوع Organization ما لم يكن للعميل مقر يزوره العملاء.',
                ],
                'profiles' => [
                    'heading' => 'الحسابات',
                    'description' => 'روابط الشبكات الاجتماعية والأدلة. تصبح sameAs، وبها يربط محرك البحث هذا الموقع بحسابات يعرفها مسبقًا.',
                ],
                'contact' => [
                    'heading' => 'التواصل',
                    'description' => 'تظهر لمحركات البحث، لا في الموقع. لا شيء هنا يُعرض في أي صفحة.',
                ],
                'contact_points' => [
                    'heading' => 'جهات الاتصال',
                    'description' => 'من يرد، وبأي لغة. رقم الهاتف وحده لا يقول شيئًا من ذلك.',
                ],
                'legal' => [
                    'heading' => 'البيانات القانونية',
                    'description' => 'اختيارية، وتزيد الثقة عند البحث عن نشاط تجاري.',
                ],
            ],
            'fields' => [
                'name' => 'الاسم',
                'description' => 'الوصف',
                'legal_name' => 'الاسم القانوني',
                'legal_name_help' => 'فقط إذا اختلف عن الاسم التجاري.',
                'type' => 'النوع',
                'type_help' => 'نوع LocalBusiness يفعّل بيانات التواصل أدناه، وهو ما يضع العميل على الخريطة.',
                'logo' => 'الشعار',
                'logo_help' => 'مربع أو عريض، بارتفاع 112px على الأقل. يُستخدم شعارًا للجهة في نتائج البحث.',
                'same_as' => 'روابط الحسابات',
                'same_as_add' => 'إضافة حساب',
                'telephone' => 'الهاتف',
                'email' => 'البريد الإلكتروني',
                'address_street' => 'عنوان الشارع',
                'address_locality' => 'المدينة',
                'address_region' => 'المنطقة أو الإمارة',
                'address_postal_code' => 'الرمز البريدي',
                'address_country' => 'رمز الدولة',
                'address_country_help' => 'حرفان، مثل AE أو SA أو LB.',
                'latitude' => 'خط العرض',
                'longitude' => 'خط الطول',
                'price_range' => 'نطاق الأسعار',
                'price_range_help' => 'نص حر، مثل $$ أو من 500 إلى 5000 درهم.',
                'area_served' => 'المناطق المخدومة',
                'area_served_help' => 'مفصولة بفواصل، مثل دبي، أبوظبي.',
                'opening_hours' => 'ساعات العمل',
                'opening_hours_days' => 'الأيام',
                'opens' => 'يفتح',
                'closes' => 'يغلق',
                'opening_hours_add' => 'إضافة ساعات',
                'opening_hours_help' => 'صف لكل مجموعة ساعات. اجمع الأيام التي تتشارك الساعات نفسها، وأضف صفًا ثانيًا للأيام المختلفة.',
                'opening_hours_fallback' => 'الساعات',
                'opening_hours_range' => 'من :opens إلى :closes',
                'contact_points' => 'جهات الاتصال',
                'contact_point_for' => 'لـ',
                'contact_point_languages' => 'اللغات',
                'contact_point_languages_help' => 'مفصولة بفواصل، مثل العربية، الإنجليزية.',
                'contact_points_add' => 'إضافة جهة اتصال',
                'contact_point_fallback' => 'جهة اتصال',
                'founding_date' => 'تاريخ التأسيس',
                'employees' => 'عدد الموظفين',
                'vat_id' => 'رقم ضريبة القيمة المضافة',
                'tax_id' => 'الرقم الضريبي',
            ],
            'options' => [
                'contact_point_for' => [
                    'customer_support' => 'خدمة العملاء',
                    'sales' => 'المبيعات',
                    'billing_support' => 'الفواتير',
                    'technical_support' => 'الدعم الفني',
                    'reservations' => 'الحجوزات',
                ],
            ],
            'days' => [
                'monday' => 'الإثنين',
                'tuesday' => 'الثلاثاء',
                'wednesday' => 'الأربعاء',
                'thursday' => 'الخميس',
                'friday' => 'الجمعة',
                'saturday' => 'السبت',
                'sunday' => 'الأحد',
            ],
            'types' => [
                'organization' => 'جهة (بلا مقر فعلي)',
                'local_business' => 'نشاط تجاري محلي',
                'professional_service' => 'خدمة مهنية',
                'store' => 'متجر',
                'restaurant' => 'مطعم أو مقهى',
                'medical_business' => 'عيادة أو مركز طبي',
                'real_estate_agent' => 'وكالة عقارية',
                'auto_repair' => 'ورشة أو صيانة سيارات',
                'home_and_construction_business' => 'مقاولات أو حِرف',
                'educational_organization' => 'مدرسة أو جهة تدريب',
            ],
        ],

    ],

    'resources' => [

        'page' => [
            'fallback_label' => 'صفحة',
            'type_section_heading' => 'تفاصيل :type',
            'actions' => [
                'new' => ':label جديدة',
                'build' => 'تعديل محتوى الصفحة',
                'view' => 'عرض الصفحة المنشورة',
                'publish' => 'نشر',
                'unpublish' => 'إلغاء النشر',
                'unpublish_modal_description' => 'ستعرض الصفحة خطأ 404 في الموقع العام. يبقى محتواها محفوظًا.',
            ],
            'sections' => [
                'structured_data' => [
                    'heading' => 'البيانات المنظمة',
                    'description' => 'ما هي هذه الصفحة، لمحركات البحث. تصبح JSON-LD في رأس الصفحة.',
                ],
            ],
            'tabs' => [
                'faq' => 'الأسئلة الشائعة',
                'breadcrumbs' => 'مسار التنقل',
            ],
            'fields' => [
                'title_help' => 'اسم داخلي، والبديل عن عنوان الميتا.',
                'layout' => 'القالب',
                'layout_placeholder' => 'الافتراضي',
                'layout_help' => 'الإطار المحيط بهذه الصفحة. اتركه على الافتراضي لاستخدام قالب الموقع.',
                'slug' => 'الرابط المختصر',
                'slug_help' => 'اتركه فارغًا لتوليده من العنوان.',
                'meta_title' => 'عنوان الميتا',
                'meta_title_help' => 'نحو 60 حرفًا. يُستخدم عنوان الصفحة إن تُرك فارغًا.',
                'meta_description' => 'وصف الميتا',
                'meta_description_help' => 'نحو 155 حرفًا.',
                'og_image' => 'صورة المشاركة',
                'og_image_help' => 'المقاس الآمن 1200 × 630.',
                'noindex' => 'إخفاء عن محركات البحث',
                'noindex_help' => 'يضيف وسم noindex ويحذف الصفحة من خريطة الموقع. تبقى الصفحة متاحة للعامة.',
                'nofollow' => 'اطلب من محركات البحث عدم تتبع روابطها',
                'nofollow_help' => 'مستقل عما سبق. يمكن أرشفة الصفحة دون أن تمرر قيمة روابطها.',
                'canonical' => 'الرابط الأساسي (canonical)',
                'canonical_help' => 'اتركه فارغًا لاستخدام رابط هذه الصفحة.',
                'schema_type' => 'النوع لمحركات البحث',
                'schema_type_help' => 'الصفحة العادية مناسبة لمعظم الصفحات.',
                'faq_questions' => 'الأسئلة',
                'faq_question' => 'السؤال',
                'faq_answer' => 'الإجابة',
                'faq_add' => 'إضافة سؤال',
                'faq_help' => 'اكتبها هنا عندما لا يولّدها شيء في الصفحة. يجب أن تكون الإجابات في مكان يقرؤه الزائر، نصًا أو بأي شكل آخر؛ السؤال الذي لا يظهر في الصفحة يخالف قواعد محركات البحث نفسها.',
                'breadcrumbs_mode' => 'المسار',
                'breadcrumbs_mode_help' => 'من مسار الرابط يبني الرئيسية › الخدمات › هذه الصفحة لصفحة على services/this-page.',
                'breadcrumbs_trail' => 'المسار',
                'breadcrumb_name' => 'الاسم',
                'breadcrumb_url' => 'الرابط',
                'breadcrumb_url_help' => 'اترك الأخير فارغًا لاستخدام هذه الصفحة.',
                'breadcrumb_step_fallback' => 'خطوة',
                'breadcrumbs_add' => 'إضافة خطوة',
            ],
            'options' => [
                'breadcrumbs_mode' => [
                    'auto' => 'من مسار الرابط',
                    'custom' => 'مكتوب هنا',
                    'none' => 'بلا',
                ],
            ],
            'table' => [
                'last_edited' => 'آخر تعديل',
                'status_unpublished_changes' => 'تغييرات غير منشورة',
                'open_builder' => 'فتح المحرر',
                'empty_heading' => 'لا توجد صفحات بعد',
                'empty_description' => 'أنشئ صفحة، واملأ إعداداتها، ثم ابنها من الأقسام.',
            ],
        ],

    ],

    'schema' => [

        'page_types' => [
            'options' => [
                'web_page' => 'صفحة عادية',
                'about_page' => 'صفحة من نحن',
                'contact_page' => 'صفحة التواصل',
                'collection_page' => 'صفحة قائمة',
                'article' => 'مقال أو تدوينة',
                'service' => 'خدمة',
                'product' => 'منتج',
                'event' => 'فعالية',
                'person' => 'شخص أو ملف تعريفي',
                'job_posting' => 'وظيفة شاغرة',
                'availability' => [
                    'in_stock' => 'متوفر',
                    'out_of_stock' => 'غير متوفر',
                    'pre_order' => 'طلب مسبق',
                ],
                'condition' => [
                    'new' => 'جديد',
                    'used' => 'مستعمل',
                    'refurbished' => 'مُجدَّد',
                    'damaged' => 'تالف',
                ],
                'event_status' => [
                    'scheduled' => 'في موعدها',
                    'rescheduled' => 'أُعيدت جدولتها',
                    'postponed' => 'مؤجلة',
                    'cancelled' => 'ملغاة',
                    'moved_online' => 'نُقلت إلى الإنترنت',
                ],
                'attendance' => [
                    'offline' => 'حضوري',
                    'online' => 'عبر الإنترنت',
                    'mixed' => 'كلاهما',
                ],
                'employment_type' => [
                    'full_time' => 'دوام كامل',
                    'part_time' => 'دوام جزئي',
                    'contractor' => 'بعقد',
                    'temporary' => 'مؤقت',
                    'intern' => 'تدريب',
                ],
                'salary_unit' => [
                    'hour' => 'ساعة',
                    'day' => 'يوم',
                    'week' => 'أسبوع',
                    'month' => 'شهر',
                    'year' => 'سنة',
                ],
            ],
            'fields' => [
                'article' => [
                    'author' => 'الكاتب',
                    'author_help' => 'شخص. اتركه فارغًا لنسب المقال إلى الجهة.',
                    'published_at' => 'تاريخ النشر',
                    'published_at_help' => 'فقط إذا اختلف عن تاريخ نشر الصفحة.',
                ],
                'service' => [
                    'service_type' => 'نوع الخدمة',
                    'service_type_help' => 'ما هي ببساطة: تصميم مواقع، استشارات ضريبية.',
                    'area_served' => 'المناطق المخدومة',
                    'area_served_help' => 'مفصولة بفواصل. اتركها فارغة لاستخدام بيانات الموقع.',
                    'price' => 'السعر',
                    'currency' => 'العملة',
                    'currency_help' => 'ثلاثة أحرف، مثل AED.',
                ],
                'product' => [
                    'sku' => 'رمز المنتج (SKU)',
                    'brand' => 'العلامة التجارية',
                    'price' => 'السعر',
                    'currency' => 'العملة',
                    'currency_help' => 'ثلاثة أحرف، مثل AED.',
                    'availability' => 'التوفر',
                    'condition' => 'الحالة',
                    'price_valid_until' => 'السعر صالح حتى',
                    'price_valid_until_help' => 'السعر بلا تاريخ انتهاء يُعامل على أنه قديم.',
                ],
                'event' => [
                    'start' => 'تبدأ',
                    'end' => 'تنتهي',
                    'location' => 'المكان',
                    'location_help' => 'اسم المكان، أو Online.',
                    'location_address' => 'العنوان',
                    'price' => 'سعر التذكرة',
                    'currency' => 'العملة',
                    'status' => 'الحالة',
                    'status_help' => 'الإلغاء من هنا هو ما يوقف الإعلان عنها.',
                    'attendance' => 'طريقة الحضور',
                ],
                'job_posting' => [
                    'posted_at' => 'تاريخ النشر',
                    'posted_at_help' => 'فقط إذا اختلف عن تاريخ نشر الصفحة.',
                    'valid_through' => 'تُغلق في',
                    'valid_through_help' => 'الوظيفة بلا تاريخ إغلاق تبقى معروضة إلى الأبد.',
                    'employment_type' => 'النوع',
                    'remote' => 'عن بُعد',
                    'remote_help' => 'من دونه تُستبعد الوظيفة عن بُعد من نتائج البحث عن وظائف عن بُعد.',
                    'location' => 'المدينة',
                    'location_help' => 'اتركها فارغة لاستخدام بيانات الموقع.',
                    'country' => 'رمز الدولة',
                    'salary' => 'الراتب',
                    'currency' => 'العملة',
                    'salary_unit' => 'لكل',
                ],
                'person' => [
                    'name' => 'الاسم الكامل',
                    'name_help' => 'اتركه فارغًا لاستخدام عنوان الميتا.',
                    'job_title' => 'المسمى الوظيفي',
                    'same_as' => 'رابط الحساب',
                    'same_as_help' => 'لينكدإن، أو أي مكان يُعرف فيه هذا الشخص.',
                ],
            ],
        ],

    ],

];
