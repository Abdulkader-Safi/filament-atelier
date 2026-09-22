<?php

declare(strict_types=1);

/**
 * Chaînes du panneau d'administration : libellés de champs, textes d'aide,
 * noms des blocs. Pas le contenu des pages, qui porte sa propre carte par
 * locale dans le JSON de chaque bloc (voir `translatable()` sur chaque
 * classe de bloc).
 */
return [

    'navigation_groups' => [
        'content' => 'Contenu',
        'settings' => 'Paramètres',
    ],

    'block_categories' => [
        'content' => 'Contenu',
        'layout' => 'Mise en page',
        'media' => 'Médias',
    ],

    'shared_controls' => [
        'section_heading' => 'Style de la section',
        'background' => 'Arrière-plan',
        'background_placeholder' => 'Par défaut',
        'padding' => 'Espacement vertical',
        'padding_placeholder' => 'Celui du bloc',
        'padding_options' => [
            'none' => 'Aucun',
            'tight' => 'Réduit',
            'normal' => 'Normal',
            'loose' => 'Large',
        ],
    ],

    'blocks' => [

        'hero' => [
            'label' => 'Héros',
            'fields' => [
                'eyebrow' => 'Surtitre',
                'heading' => 'Titre',
                'subheading' => 'Sous-titre',
                'cta_label' => 'Texte du bouton',
                'cta_url' => 'Lien du bouton',
                'image' => 'Image de fond',
                'align' => 'Alignement',
            ],
            'options' => [
                'align' => [
                    'left' => 'Gauche',
                    'center' => 'Centre',
                ],
            ],
        ],

        'cta' => [
            'label' => "Appel à l'action",
            'fields' => [
                'heading' => 'Titre',
                'body' => 'Texte',
                'cta_label' => 'Texte du bouton',
                'cta_url' => 'Lien du bouton',
            ],
        ],

        'faq' => [
            'label' => 'FAQ',
            'fields' => [
                'heading' => 'Titre',
                'items' => 'Questions',
                'question' => 'Question',
                'answer' => 'Réponse',
            ],
        ],

        'features' => [
            'label' => 'Fonctionnalités',
            'fields' => [
                'heading' => 'Titre',
                'subheading' => 'Sous-titre',
                'columns' => 'Colonnes',
                'items' => 'Fonctionnalités',
                'icon' => 'Icône',
                'icon_help' => "N'importe quel nom d'icône Heroicon.",
                'title' => 'Titre',
                'body' => 'Texte',
                'item_fallback' => 'Fonctionnalité',
            ],
            'options' => [
                'columns' => [
                    'two' => 'Deux',
                    'three' => 'Trois',
                    'four' => 'Quatre',
                ],
            ],
        ],

        'gallery' => [
            'label' => 'Galerie',
            'fields' => [
                'heading' => 'Titre',
                'columns' => 'Colonnes',
                'images' => 'Images',
                'image' => 'Image',
                'alt' => 'Texte alternatif',
            ],
            'options' => [
                'columns' => [
                    'two' => 'Deux',
                    'three' => 'Trois',
                    'four' => 'Quatre',
                ],
            ],
        ],

        'image' => [
            'label' => 'Image',
            'fields' => [
                'image' => 'Image',
                'alt' => 'Texte alternatif',
                'alt_help' => "Ce que montre l'image. À laisser vide uniquement si elle est décorative.",
                'caption' => 'Légende',
                'width' => 'Largeur',
            ],
            'options' => [
                'width' => [
                    'container' => 'Conteneur',
                    'wide' => 'Large',
                    'full' => 'Pleine largeur',
                ],
            ],
        ],

        'logo_wall' => [
            'label' => 'Mur de logos',
            'fields' => [
                'heading' => 'Titre',
                'logos' => 'Logos',
                'logo' => 'Logo',
                'name' => 'Société',
                'url' => 'Lien',
            ],
        ],

        'rich_text' => [
            'label' => 'Texte enrichi',
            'fields' => [
                'heading' => 'Titre',
                'body' => 'Corps',
            ],
        ],

        'testimonials' => [
            'label' => 'Témoignages',
            'fields' => [
                'heading' => 'Titre',
                'items' => 'Témoignages',
                'quote' => 'Citation',
                'name' => 'Nom',
                'role' => 'Fonction',
                'avatar' => 'Photo',
                'item_fallback' => 'Témoignage',
            ],
        ],

        'collection' => [
            'label' => 'Collection',
            'fields' => [
                'page_type' => 'Quoi lister',
                'heading' => 'Titre',
                'source' => 'Lesquelles',
                'source_help' => '« Sous cette page » se base sur le chemin du slug : une page à services affiche services/web-design.',
                'pages' => 'Sélectionnées',
                'pages_add' => 'Ajouter une page',
                'pages_help' => "Glisser pour définir l'ordre d'affichage.",
                'order' => 'Ordre',
                'limit' => 'Combien',
                'limit_placeholder' => 'Toutes',
                'columns' => 'Colonnes',
                'empty' => "Texte quand il n'y a rien à lister",
                'empty_placeholder' => 'Rien à afficher pour le moment.',
            ],
            'options' => [
                'source' => [
                    'all' => 'Toutes les pages publiées',
                    'picked' => 'Celles que je choisis',
                    'children' => 'Seulement celles sous cette page',
                ],
                'order' => [
                    'title' => 'Titre, de A à Z',
                    'newest' => 'Plus récentes en premier',
                    'oldest' => 'Plus anciennes en premier',
                ],
                'columns' => [
                    'one' => 'Une',
                    'two' => 'Deux',
                    'three' => 'Trois',
                    'four' => 'Quatre',
                ],
            ],
        ],

    ],

    'pages' => [

        'menu_manager' => [
            'nav_label' => 'Menus',
            'title' => 'Menus',
            'subheading' => 'Navigation du site public. Les modifications sont enregistrées au fur et à mesure.',
            'add_source_prefix' => 'Ajouter',
            'edit_item' => [
                'trigger' => 'Modifier',
                'modal_heading' => "Modifier l'élément",
                'submit' => 'Enregistrer',
                'label_field' => 'Libellé',
                'url_field' => 'URL',
                'url_help' => 'Un chemin comme /about, ou une URL https:// complète.',
                'target_field' => 'Ouverture',
                'target_same_tab' => 'Même onglet',
                'target_new_tab' => 'Nouvel onglet',
            ],
            'delete_item' => [
                'trigger' => 'Supprimer',
                'modal_heading' => 'Supprimer cet élément ?',
            ],
        ],

        'page_editor' => [
            'fallback_title' => 'Éditeur de page',
            'back_to_pages' => 'Retour aux pages',
            'back_to_sections' => 'Retour aux sections',
            'pages' => 'Pages',
            'sections' => 'Sections',
            'section_fallback' => 'Section',
            'section_settings' => 'Réglages de la section',
            'select_section_first' => "Sélectionnez d'abord une section",
            'open_preview' => "Ouvrir l'aperçu dans un nouvel onglet",
            'publish' => 'Publier',
            'status' => [
                'unpublished' => 'Modifications non publiées',
                'published' => 'Publié',
                'draft' => 'Brouillon',
            ],
            'widths' => [
                'desktop' => 'Ordinateur',
                'tablet' => 'Tablette',
                'mobile' => 'Mobile',
            ],
            'search_pages' => 'Rechercher des pages',
            'search_sections' => 'Rechercher des sections',
            'editing_badge' => 'édition',
            'page_search_no_match_prefix' => 'Aucune page ne correspond à « ',
            'page_search_no_match_suffix' => ' ».',
            'section_search_no_match_prefix' => 'Aucune section ne correspond à « ',
            'section_search_no_match_suffix' => ' ».',
            'move_up' => 'Monter',
            'move_down' => 'Descendre',
            'duplicate' => 'Dupliquer',
            'show' => 'Afficher',
            'hide' => 'Masquer',
            'delete' => 'Supprimer',
            'delete_confirm' => 'Supprimer cette section ?',
            'drag_to_reorder' => 'Glisser pour réordonner',
            'settings' => 'Réglages',
            'empty_state' => 'Rien pour le moment. Ajoutez votre première section ci-dessous.',
            'add_section' => 'Ajouter une section',
            'preview_title' => 'Aperçu de la page',
        ],

        'site_details' => [
            'nav_label' => 'Coordonnées du site',
            'title' => 'Coordonnées du site',
            'subheading' => "L'organisation derrière le site. Utilisé dans les données structurées de chaque page.",
            'save_action' => 'Enregistrer les modifications',
            'saved_notification' => 'Enregistré',
            'sections' => [
                'identity' => [
                    'heading' => 'Identité',
                    'description' => "À qui appartient le site. Laisser le type sur Organization sauf si le client dispose de locaux qu'un visiteur peut visiter.",
                ],
                'profiles' => [
                    'heading' => 'Profils',
                    'description' => "URL sociales et annuaires. Elles deviennent sameAs, ce qui permet à un moteur de recherche de relier ce site à des comptes qu'il connaît déjà.",
                ],
                'contact' => [
                    'heading' => 'Contact',
                    'description' => "Affiché aux moteurs de recherche, pas sur le site. Rien ici n'est rendu dans une page.",
                ],
                'contact_points' => [
                    'heading' => 'Points de contact',
                    'description' => 'Qui répond, et dans quelle langue. Un simple numéro de téléphone ne dit rien de tout cela.',
                ],
                'legal' => [
                    'heading' => 'Mentions légales',
                    'description' => 'Facultatif, et un signal de confiance pour une recherche professionnelle.',
                ],
            ],
            'fields' => [
                'name' => 'Nom',
                'description' => 'Description',
                'legal_name' => 'Raison sociale',
                'legal_name_help' => 'Uniquement si elle diffère du nom commercial.',
                'type' => 'Type',
                'type_help' => "Un type LocalBusiness débloque les coordonnées ci-dessous, et c'est ce qui place le client sur la carte.",
                'logo' => 'Logo',
                'logo_help' => "Carré ou large, au moins 112px de haut. Utilisé comme logo de l'organisation dans les résultats de recherche.",
                'same_as' => 'URL de profils',
                'same_as_add' => 'Ajouter un profil',
                'telephone' => 'Téléphone',
                'email' => 'E-mail',
                'address_street' => 'Adresse',
                'address_locality' => 'Ville',
                'address_region' => 'Région ou émirat',
                'address_postal_code' => 'Code postal',
                'address_country' => 'Code pays',
                'address_country_help' => 'Deux lettres, ex. AE, SA, LB.',
                'latitude' => 'Latitude',
                'longitude' => 'Longitude',
                'price_range' => 'Gamme de prix',
                'price_range_help' => 'Texte libre, ex. $$ ou 500 à 5000 AED.',
                'area_served' => 'Zones desservies',
                'area_served_help' => 'Séparées par des virgules, ex. Dubaï, Abu Dhabi.',
                'opening_hours' => "Horaires d'ouverture",
                'opening_hours_days' => 'Jours',
                'opens' => 'Ouverture',
                'closes' => 'Fermeture',
                'opening_hours_add' => 'Ajouter des horaires',
                'opening_hours_help' => 'Une ligne par plage horaire. Regrouper les jours qui la partagent, et ajouter une seconde ligne pour les jours différents.',
                'opening_hours_fallback' => 'Horaires',
                'contact_points' => 'Points de contact',
                'contact_point_for' => 'Pour',
                'contact_point_languages' => 'Langues',
                'contact_point_languages_help' => 'Séparées par des virgules, ex. arabe, anglais.',
                'contact_points_add' => 'Ajouter un point de contact',
                'contact_point_fallback' => 'Point de contact',
                'founding_date' => 'Fondée le',
                'employees' => 'Employés',
                'vat_id' => 'Numéro de TVA',
                'tax_id' => "Numéro d'imposition",
            ],
            'options' => [
                'contact_point_for' => [
                    'customer_support' => 'Support client',
                    'sales' => 'Ventes',
                    'billing_support' => 'Facturation',
                    'technical_support' => 'Support technique',
                    'reservations' => 'Réservations',
                ],
            ],
            'days' => [
                'monday' => 'Lun',
                'tuesday' => 'Mar',
                'wednesday' => 'Mer',
                'thursday' => 'Jeu',
                'friday' => 'Ven',
                'saturday' => 'Sam',
                'sunday' => 'Dim',
            ],
            'types' => [
                'organization' => 'Organisation (sans locaux physiques)',
                'local_business' => 'Commerce local',
                'professional_service' => 'Service professionnel',
                'store' => 'Boutique',
                'restaurant' => 'Restaurant ou café',
                'medical_business' => 'Clinique ou cabinet médical',
                'real_estate_agent' => 'Agence immobilière',
                'auto_repair' => 'Garage ou réparation automobile',
                'home_and_construction_business' => 'Construction ou artisanat',
                'educational_organization' => 'École ou organisme de formation',
            ],
        ],

    ],

    'resources' => [

        'page' => [
            'fallback_label' => 'page',
            // La concaténation `new_prefix . ' ' . label` ne permet pas
            // d'accorder l'adjectif au genre du type de page : accord
            // choisi pour le cas par défaut ("page", féminin).
            'type_section_heading' => 'Détails :type',
            'actions' => [
                'new_prefix' => 'Nouvelle',
                'build' => 'Modifier le contenu de la page',
                'view' => 'Voir en ligne',
                'publish' => 'Publier',
                'unpublish' => 'Dépublier',
                'unpublish_modal_description' => 'La page renverra une erreur 404 sur le site public. Son contenu est conservé.',
            ],
            'sections' => [
                'structured_data' => [
                    'heading' => 'Données structurées',
                    'description' => "Ce qu'est cette page, pour les moteurs de recherche. Cela devient le JSON-LD dans le head.",
                ],
            ],
            'tabs' => [
                'faq' => 'FAQ',
                'breadcrumbs' => "Fil d'Ariane",
            ],
            'fields' => [
                'title_help' => 'Nom interne, et solution de repli pour le titre méta.',
                'layout' => 'Mise en page',
                'layout_placeholder' => 'Par défaut',
                'layout_help' => 'Le gabarit qui enveloppe cette page. Laisser sur Par défaut pour utiliser celui du site.',
                'slug' => 'Slug',
                'slug_help' => 'Laisser vide pour en générer un à partir du titre.',
                'meta_title' => 'Titre méta',
                'meta_title_help' => 'Environ 60 caractères. Revient au titre de la page par défaut.',
                'meta_description' => 'Description méta',
                'meta_description_help' => 'Environ 155 caractères.',
                'og_image' => 'Image de partage social',
                'og_image_help' => '1200 par 630 est la taille sûre.',
                'noindex' => 'Masquer des moteurs de recherche',
                'noindex_help' => 'Ajoute une balise noindex et retire la page du sitemap. La page reste publique.',
                'nofollow' => 'Indiquer aux moteurs de ne pas suivre ses liens',
                'nofollow_help' => 'Indépendant du réglage précédent. Une page peut être indexée sans transmettre son crédit de lien.',
                'canonical' => 'URL canonique',
                'canonical_help' => "Laisser vide pour utiliser l'URL propre à cette page.",
                'schema_type' => 'Type pour les moteurs de recherche',
                'schema_type_help' => 'Page standard convient dans la plupart des cas.',
                'faq_questions' => 'Questions',
                'faq_question' => 'Question',
                'faq_answer' => 'Réponse',
                'faq_add' => 'Ajouter une question',
                'faq_help' => "À saisir ici lorsque rien sur la page ne les génère. Les réponses doivent tout de même être lisibles quelque part sur la page, en prose ou autrement ; une question qui n'apparaît nulle part sur la page enfreint les règles des moteurs de recherche eux-mêmes.",
                'breadcrumbs_mode' => 'Fil',
                'breadcrumbs_mode_help' => 'Depuis le chemin du slug construit Accueil › Services › Cette page pour une page à services/cette-page.',
                'breadcrumbs_trail' => 'Fil',
                'breadcrumb_name' => 'Nom',
                'breadcrumb_url' => 'URL',
                'breadcrumb_url_help' => 'Laisser le dernier vide pour utiliser cette page.',
                'breadcrumb_step_fallback' => 'Étape',
                'breadcrumbs_add' => 'Ajouter une étape',
            ],
            'options' => [
                'breadcrumbs_mode' => [
                    'auto' => 'Depuis le chemin du slug',
                    'custom' => 'Saisi ici',
                    'none' => 'Aucun',
                ],
            ],
            'table' => [
                'last_edited' => 'Dernière modification',
                'status_unpublished_changes' => 'Modifications non publiées',
                'open_builder' => 'Ouvrir le constructeur',
                'empty_heading' => 'Aucune page pour le moment',
                'empty_description' => 'Créez-en une, renseignez ses réglages, puis construisez-la à partir de sections.',
            ],
        ],

    ],

    'schema' => [

        'page_types' => [
            'options' => [
                'web_page' => 'Page standard',
                'about_page' => 'Page À propos',
                'contact_page' => 'Page de contact',
                'collection_page' => 'Page de listing',
                'article' => 'Article ou billet de blog',
                'service' => 'Service',
                'product' => 'Produit',
                'event' => 'Événement',
                'person' => 'Personne ou profil',
                'job_posting' => "Offre d'emploi",
                'availability' => [
                    'in_stock' => 'En stock',
                    'out_of_stock' => 'Rupture de stock',
                    'pre_order' => 'Précommande',
                ],
                'condition' => [
                    'new' => 'Neuf',
                    'used' => "D'occasion",
                    'refurbished' => 'Reconditionné',
                    'damaged' => 'Endommagé',
                ],
                'event_status' => [
                    'scheduled' => 'Confirmé',
                    'rescheduled' => 'Reporté à une nouvelle date',
                    'postponed' => 'Reporté',
                    'cancelled' => 'Annulé',
                    'moved_online' => 'Déplacé en ligne',
                ],
                'attendance' => [
                    'offline' => 'En présentiel',
                    'online' => 'En ligne',
                    'mixed' => 'Hybride',
                ],
                'employment_type' => [
                    'full_time' => 'Temps plein',
                    'part_time' => 'Temps partiel',
                    'contractor' => 'Contrat',
                    'temporary' => 'Temporaire',
                    'intern' => 'Stage',
                ],
                'salary_unit' => [
                    'hour' => 'Heure',
                    'day' => 'Jour',
                    'week' => 'Semaine',
                    'month' => 'Mois',
                    'year' => 'Année',
                ],
            ],
            'fields' => [
                'article' => [
                    'author' => 'Auteur',
                    'author_help' => "Une personne. Laisser vide pour créditer l'organisation à la place.",
                    'published_at' => 'Publié le',
                    'published_at_help' => 'Uniquement si différent de la date de publication de la page.',
                ],
                'service' => [
                    'service_type' => 'Type de service',
                    'service_type_help' => 'Ce que c\'est, simplement : Conception web, Conseil fiscal.',
                    'area_served' => 'Zones desservies',
                    'area_served_help' => 'Séparées par des virgules. Laisser vide pour utiliser les coordonnées du site.',
                    'price' => 'Prix',
                    'currency' => 'Devise',
                    'currency_help' => 'Trois lettres, ex. AED.',
                ],
                'product' => [
                    'sku' => 'Référence (SKU)',
                    'brand' => 'Marque',
                    'price' => 'Prix',
                    'currency' => 'Devise',
                    'currency_help' => 'Trois lettres, ex. AED.',
                    'availability' => 'Disponibilité',
                    'condition' => 'État',
                    'price_valid_until' => "Prix valable jusqu'au",
                    'price_valid_until_help' => 'Un prix sans date de fin est considéré comme périmé.',
                ],
                'event' => [
                    'start' => 'Débute',
                    'end' => 'Se termine',
                    'location' => 'Lieu',
                    'location_help' => 'Nom du lieu, ou En ligne.',
                    'location_address' => 'Adresse',
                    'price' => 'Prix du billet',
                    'currency' => 'Devise',
                    'status' => 'Statut',
                    'status_help' => "L'annulation ici est ce qui arrête sa promotion.",
                    'attendance' => 'Participation',
                ],
                'job_posting' => [
                    'posted_at' => 'Publiée le',
                    'posted_at_help' => 'Uniquement si différent de la date de publication de la page.',
                    'valid_through' => 'Clôture',
                    'valid_through_help' => 'Une offre sans date de clôture reste affichée indéfiniment.',
                    'employment_type' => 'Type',
                    'remote' => 'Télétravail',
                    'remote_help' => 'Sans cela, un poste en télétravail est exclu des recherches en télétravail.',
                    'location' => 'Ville',
                    'location_help' => 'Laisser vide pour utiliser les coordonnées du site.',
                    'country' => 'Code pays',
                    'salary' => 'Salaire',
                    'currency' => 'Devise',
                    'salary_unit' => 'Par',
                ],
                'person' => [
                    'name' => 'Nom complet',
                    'name_help' => 'Laisser vide pour utiliser le titre méta.',
                    'job_title' => 'Intitulé du poste',
                    'same_as' => 'URL de profil',
                    'same_as_help' => 'LinkedIn, ou tout endroit où cette personne est déjà connue.',
                ],
            ],
        ],

    ],

];
