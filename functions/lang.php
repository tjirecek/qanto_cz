<?php
/* qanto.cz public frontend language catalog */

$currentLang = (($lang ?? 'cz') === 'en') ? 'en' : 'cz';

$legacyWords = [
    'cz' => [
        1 => 'Qanto',
        2 => 'Qanto, velkoobchod, markety, potraviny, nápoje',
        3 => 'Qanto je regionální partner pro velkoobchod, markety, prodejny a služby v oblasti potravin a nápojů.',
        4 => 'Qanto',
        100 => 'Qanto',
        900 => 'Copyright <strong>&copy; ' . date('Y') . ' Astur & Qanto s.r.o.</strong>, Všechna práva vyhrazena',
        901 => 'Created by: <strong>Bc. Tomáš Jireček</strong>',
    ],
    'en' => [
        1 => 'Qanto',
        2 => 'Qanto, wholesale, markets, food, beverages',
        3 => 'Qanto is a regional partner for wholesale, markets, stores and services in food and beverages.',
        4 => 'Qanto',
        100 => 'Qanto',
        900 => 'Copyright <strong>&copy; ' . date('Y') . ' Astur & Qanto s.r.o.</strong>, All rights reserved',
        901 => 'Created by: <strong>Bc. Tomas Jirecek</strong>',
    ],
];

$word = $legacyWords[$currentLang];
$ui_texts = [
    'site.name' => [
        'cz' => 'Qanto',
        'en' => 'Qanto',
    ],
    'site.meta.keywords' => [
        'cz' => 'Qanto, velkoobchod, markety, potraviny, nápoje',
        'en' => 'Qanto, wholesale, markets, food, beverages',
    ],
    'site.meta.description' => [
        'cz' => 'Qanto je regionální partner pro velkoobchod, markety, prodejny a služby v oblasti potravin a nápojů.',
        'en' => 'Qanto is a regional partner for wholesale, markets, stores and services in food and beverages.',
    ],

    'aria.main_navigation' => [
        'cz' => 'Hlavní navigace',
        'en' => 'Main navigation',
    ],
    'aria.footer_navigation' => [
        'cz' => 'Spodní navigace',
        'en' => 'Footer navigation',
    ],
    'aria.social_navigation' => [
        'cz' => 'Sociální sítě',
        'en' => 'Social networks',
    ],
    'aria.language' => [
        'cz' => 'Jazyk webu',
        'en' => 'Website language',
    ],
    'aria.home' => [
        'cz' => 'Qanto domů',
        'en' => 'Qanto home',
    ],
    'aria.contact' => [
        'cz' => 'Kontakty',
        'en' => 'Contacts',
    ],
    'aria.open_menu' => [
        'cz' => 'Otevřít menu',
        'en' => 'Open menu',
    ],
    'aria.scroll_top' => [
        'cz' => 'Nahoru',
        'en' => 'Back to top',
    ],
    'aria.breadcrumb' => [
        'cz' => 'Drobečková navigace',
        'en' => 'Breadcrumb navigation',
    ],
    'aria.router' => [
        'cz' => 'Rychlý rozcestník',
        'en' => 'Quick navigation',
    ],
    'aria.ads' => [
        'cz' => 'Aktuální reklamy',
        'en' => 'Current promotions',
    ],
    'aria.ads_controls' => [
        'cz' => 'Ovládání reklam',
        'en' => 'Promotion controls',
    ],
    'aria.ads_prev' => [
        'cz' => 'Předchozí reklama',
        'en' => 'Previous promotion',
    ],
    'aria.ads_next' => [
        'cz' => 'Další reklama',
        'en' => 'Next promotion',
    ],
    'detail.sidebar_links' => [
        'cz' => 'Doporučené odkazy',
        'en' => 'Recommended links',
    ],

    'nav.akce' => [
        'cz' => 'Letáky',
        'en' => 'Flyers',
    ],
    'nav.markety' => [
        'cz' => 'Markety',
        'en' => 'Markets',
    ],
    'nav.velkoobchod' => [
        'cz' => 'Velkoobchod',
        'en' => 'Wholesale',
    ],
    'nav.prodejny' => [
        'cz' => 'Prodejny',
        'en' => 'Stores',
    ],
    'nav.kariera' => [
        'cz' => 'Kariéra',
        'en' => 'Careers',
    ],
    'nav.volna_mista' => [
        'cz' => 'Volná místa',
        'en' => 'Open positions',
    ],
    'nav.nase_znacky' => [
        'cz' => 'Naše značky',
        'en' => 'Our brands',
    ],
    'nav.o_nas' => [
        'cz' => 'O nás',
        'en' => 'About us',
    ],
    'nav.o_spolecnosti' => [
        'cz' => 'O nás',
        'en' => 'About us',
    ],
    'nav.historie' => [
        'cz' => 'Historie',
        'en' => 'History',
    ],
    'nav.media' => [
        'cz' => 'Média',
        'en' => 'Media',
    ],
    'nav.podporujeme' => [
        'cz' => 'Podporujeme',
        'en' => 'We support',
    ],
    'nav.kontakt' => [
        'cz' => 'Kontakty',
        'en' => 'Contacts',
    ],
    'nav.admin' => [
        'cz' => 'Administrace',
        'en' => 'Administration',
    ],

    'page.main.title' => [
        'cz' => 'Qanto',
        'en' => 'Qanto',
    ],
    'page.main.description' => [
        'cz' => 'Qanto je regionální partner pro velkoobchod, markety, prodejny a služby v oblasti potravin a nápojů.',
        'en' => 'Qanto is a regional partner for wholesale, markets, stores and services in food and beverages.',
    ],
    'page.akce.title' => [
        'cz' => 'Letáky | Qanto',
        'en' => 'Flyers | Qanto',
    ],
    'page.akce.description' => [
        'cz' => 'Aktuální letáky Qanto.',
        'en' => 'Current Qanto flyers.',
    ],
    'page.news.title' => [
        'cz' => 'Novinky | Qanto',
        'en' => 'News | Qanto',
    ],
    'page.news.description' => [
        'cz' => 'Aktuální informace, zprávy a novinky Qanto.',
        'en' => 'Current information, updates and Qanto news.',
    ],
    'page.markety.title' => [
        'cz' => 'Markety | Qanto',
        'en' => 'Markets | Qanto',
    ],
    'page.markety.description' => [
        'cz' => 'Markety Qanto, lokální prodejny a služby pro maloobchodní zákazníky.',
        'en' => 'Qanto markets, local stores and services for retail customers.',
    ],
    'page.velkoobchod.title' => [
        'cz' => 'Velkoobchod | Qanto',
        'en' => 'Wholesale | Qanto',
    ],
    'page.velkoobchod.description' => [
        'cz' => 'Velkoobchodní distribuce Qanto, obchodní zástupci a služby pro firmy.',
        'en' => 'Qanto wholesale distribution, sales representatives and business services.',
    ],
    'page.prodejny.title' => [
        'cz' => 'Prodejny | Qanto',
        'en' => 'Stores | Qanto',
    ],
    'page.prodejny.description' => [
        'cz' => 'Přehled prodejen a poboček Qanto.',
        'en' => 'Overview of Qanto stores and branches.',
    ],
    'page.kariera.title' => [
        'cz' => 'Kariéra | Qanto',
        'en' => 'Careers | Qanto',
    ],
    'page.kariera.description' => [
        'cz' => 'Volná pracovní místa ve společnosti Qanto.',
        'en' => 'Open job positions at Qanto.',
    ],
    'page.ples.title' => [
        'cz' => 'Ples | Qanto',
        'en' => 'Ball | Qanto',
    ],
    'page.ples.description' => [
        'cz' => 'Registrace na ples Qanto.',
        'en' => 'Qanto ball registration.',
    ],
    'page.tenisqcup.title' => [
        'cz' => 'TenisQcup | Qanto',
        'en' => 'TenisQcup | Qanto',
    ],
    'page.tenisqcup.description' => [
        'cz' => 'Registrace na tenisový turnaj TenisQcup.',
        'en' => 'TenisQcup tennis tournament registration.',
    ],
    'page.inventury.title' => [
        'cz' => 'Inventury | Qanto',
        'en' => 'Stocktaking | Qanto',
    ],
    'page.inventury.description' => [
        'cz' => 'Registrace a informace k inventurám Qanto.',
        'en' => 'Qanto stocktaking registration and information.',
    ],
    'kariera.breadcrumb' => [
        'cz' => 'Kariéra',
        'en' => 'Careers',
    ],
    'kariera.hero_title' => [
        'cz' => 'Kariéra v Qanto',
        'en' => 'Careers at Qanto',
    ],
    'kariera.video_label' => [
        'cz' => 'Představujeme Qanto',
        'en' => 'Introducing Qanto',
    ],
    'kariera.position_label' => [
        'cz' => 'Volná pozice',
        'en' => 'Open position',
    ],
    'kariera.contact_title' => [
        'cz' => 'Kontakt',
        'en' => 'Contact',
    ],
    'kariera.jobs_filter_empty' => [
        'cz' => 'Pro vybrané město nejsou dostupné žádné pozice.',
        'en' => 'No open positions are available for the selected city.',
    ],
    'kariera.map_label' => [
        'cz' => 'Mapa poboček a volných míst',
        'en' => 'Map of branches and open positions',
    ],
    'kariera.map_branch' => [
        'cz' => 'Pobočka',
        'en' => 'Branch',
    ],
    'kariera.map_open_jobs' => [
        'cz' => 'Volná místa',
        'en' => 'Open positions',
    ],
    'kariera.map_no_gps' => [
        'cz' => 'Pro mapu nejsou dostupné souřadnice poboček.',
        'en' => 'Branch coordinates are not available for the map.',
    ],
    'kariera.map_city' => [
        'cz' => 'Město',
        'en' => 'City',
    ],
    'kariera.map_all_cities' => [
        'cz' => 'Celá ČR',
        'en' => 'All Czechia',
    ],
    'kariera.map_only_jobs' => [
        'cz' => 'Jen volná místa',
        'en' => 'Open positions only',
    ],
    'kariera.reset_filter' => [
        'cz' => 'Zrušit filtr',
        'en' => 'Clear filter',
    ],
    'kariera.map_filter_empty' => [
        'cz' => 'Pro vybraný filtr nejsou dostupné žádné pobočky.',
        'en' => 'No branches are available for the selected filter.',
    ],
    'kariera.map_legend_branches' => [
        'cz' => 'pobočky',
        'en' => 'branches',
    ],
    'kariera.map_legend_jobs' => [
        'cz' => 'volná místa',
        'en' => 'open positions',
    ],
    'kariera.job_count' => [
        'cz' => '%d míst',
        'en' => '%d openings',
    ],
    'kariera.job_count_one' => [
        'cz' => '1 místo',
        'en' => '1 opening',
    ],
    'kariera.more' => [
        'cz' => 'Detail pozice',
        'en' => 'Position detail',
    ],
    'kariera.no_jobs' => [
        'cz' => 'Aktuálně zde nejsou žádná volná místa.',
        'en' => 'There are currently no open positions.',
    ],
    'kariera.process_title' => [
        'cz' => 'Jak to u nás chodí',
        'en' => 'How it works here',
    ],
    'kariera.application_label' => [
        'cz' => 'Online dotazník',
        'en' => 'Online questionnaire',
    ],
    'kariera.application_title' => [
        'cz' => 'Mám zájem o pozici',
        'en' => 'I am interested in this position',
    ],
    'kariera.application_intro' => [
        'cz' => 'Vyplňte kontaktní údaje a krátký dotazník. Pokud máte životopis nebo motivační dopis, můžete ho přiložit jako PDF nebo Word.',
        'en' => 'Fill in your contact details and a short questionnaire. If you have a CV or cover letter, you can attach it as PDF or Word.',
    ],
    'kariera.application.name' => [
        'cz' => 'Jméno a příjmení',
        'en' => 'Full name',
    ],
    'kariera.application.phone' => [
        'cz' => 'Mobil',
        'en' => 'Phone',
    ],
    'kariera.application.email' => [
        'cz' => 'E-mail',
        'en' => 'Email',
    ],
    'kariera.application.address' => [
        'cz' => 'Adresa',
        'en' => 'Address',
    ],
    'kariera.application.birthdate' => [
        'cz' => 'Datum narození',
        'en' => 'Date of birth',
    ],
    'kariera.application.education' => [
        'cz' => 'Vzdělání',
        'en' => 'Education',
    ],
    'kariera.application.license' => [
        'cz' => 'Řidičský průkaz',
        'en' => 'Driving licence',
    ],
    'kariera.application.work_time' => [
        'cz' => 'Možná pracovní doba',
        'en' => 'Possible working hours',
    ],
    'kariera.application.salary' => [
        'cz' => 'Představa o platu',
        'en' => 'Salary expectation',
    ],
    'kariera.application.previous_employer' => [
        'cz' => 'Předchozí zaměstnavatel',
        'en' => 'Previous employer',
    ],
    'kariera.application.previous_role' => [
        'cz' => 'Funkce',
        'en' => 'Role',
    ],
    'kariera.application.previous_duration' => [
        'cz' => 'Délka zaměstnání',
        'en' => 'Employment duration',
    ],
    'kariera.application.languages' => [
        'cz' => 'Jazykové znalosti',
        'en' => 'Language skills',
    ],
    'kariera.application.pc' => [
        'cz' => 'Práce na PC',
        'en' => 'Computer skills',
    ],
    'kariera.application.hobbies' => [
        'cz' => 'Záliby',
        'en' => 'Hobbies',
    ],
    'kariera.application.source' => [
        'cz' => 'Jak jste se o nás dozvěděl/a?',
        'en' => 'How did you hear about us?',
    ],
    'kariera.application.benefit' => [
        'cz' => 'Čím můžete být pro Qanto přínosem?',
        'en' => 'How can you contribute to Qanto?',
    ],
    'kariera.application.criminal_record' => [
        'cz' => 'Rejstřík trestů',
        'en' => 'Criminal record',
    ],
    'kariera.application.cv_text' => [
        'cz' => 'Profesní životopis',
        'en' => 'Professional CV',
    ],
    'kariera.application.smoking' => [
        'cz' => 'Kouření',
        'en' => 'Smoking',
    ],
    'kariera.application.health' => [
        'cz' => 'Zdravotní stav',
        'en' => 'Health condition',
    ],
    'kariera.application.attachment' => [
        'cz' => 'Zde můžete nahrát životopis, motivační dopis nebo další dokumenty',
        'en' => 'Here you can upload a CV, cover letter or other documents',
    ],
    'kariera.application.attachment_help' => [
        'cz' => 'Povolené typy: PDF, DOC, DOCX. Maximálně 5 souborů, každý do 10 MB.',
        'en' => 'Allowed types: PDF, DOC, DOCX. Maximum 5 files, each up to 10 MB.',
    ],
    'kariera.application.error_upload_count' => [
        'cz' => 'Nahrát lze maximálně 5 souborů.',
        'en' => 'You can upload a maximum of 5 files.',
    ],
    'kariera.application.submit' => [
        'cz' => 'Odeslat dotazník',
        'en' => 'Send questionnaire',
    ],
    'kariera.application.success' => [
        'cz' => 'Dotazník byl odeslán. Děkujeme.',
        'en' => 'The questionnaire has been sent. Thank you.',
    ],
    'kariera.application.error_required' => [
        'cz' => 'Vyplňte prosím jméno, mobil a e-mail.',
        'en' => 'Please fill in your name, phone and email.',
    ],
    'kariera.application.error_email' => [
        'cz' => 'Zadejte prosím platný e-mail.',
        'en' => 'Please enter a valid email.',
    ],
    'kariera.application.error_security' => [
        'cz' => 'Formulář vypršel, obnovte stránku a zkuste odeslání znovu.',
        'en' => 'The form has expired. Refresh the page and try again.',
    ],
    'kariera.application.error_position' => [
        'cz' => 'Vybraná pozice není dostupná.',
        'en' => 'The selected position is not available.',
    ],
    'kariera.application.error_upload' => [
        'cz' => 'Přílohu se nepodařilo nahrát.',
        'en' => 'The attachment could not be uploaded.',
    ],
    'kariera.application.error_upload_size' => [
        'cz' => 'Příloha může mít maximálně 10 MB.',
        'en' => 'The attachment can be up to 10 MB.',
    ],
    'kariera.application.error_upload_type' => [
        'cz' => 'Příloha musí být PDF, DOC nebo DOCX.',
        'en' => 'The attachment must be PDF, DOC or DOCX.',
    ],
    'kariera.application.error_generic' => [
        'cz' => 'Dotazník se nepodařilo odeslat. Zkuste to prosím znovu.',
        'en' => 'The questionnaire could not be sent. Please try again.',
    ],
    'page.nase_znacky.title' => [
        'cz' => 'Naše značky | Qanto',
        'en' => 'Our brands | Qanto',
    ],
    'page.nase_znacky.description' => [
        'cz' => 'Přehled značek a sortimentu Qanto.',
        'en' => 'Overview of Qanto brands and assortment.',
    ],
    'page.brigada.title' => [
        'cz' => 'Registrace brigádníka | Qanto',
        'en' => 'Part-time Work Registration | Qanto',
    ],
    'page.brigada.description' => [
        'cz' => 'Registrace zájemců o brigádu v marketech, prodejnách a velkoobchodech Qanto.',
        'en' => 'Registration for part-time work in Qanto markets, stores and wholesale branches.',
    ],
    'brigada.breadcrumb' => [
        'cz' => 'Registrace brigádníka',
        'en' => 'Part-time Work Registration',
    ],
    'brigada.label' => [
        'cz' => 'Brigáda v Qanto',
        'en' => 'Part-time work at Qanto',
    ],
    'brigada.title' => [
        'cz' => 'Registrace brigádníka',
        'en' => 'Part-time Work Registration',
    ],
    'brigada.intro' => [
        'cz' => 'Máte volný čas a chcete si přivydělat v našich marketech, prodejnách nebo velkoobchodech? Vyplňte krátkou registraci a podle vybrané pobočky se vám ozveme.',
        'en' => 'Do you have free time and want to earn extra money in our markets, stores or wholesale branches? Fill in a short registration and we will contact you based on the selected branch.',
    ],
    'brigada.point_1' => [
        'cz' => 'Vyberte pobočku přes přehledný seznam.',
        'en' => 'Choose a branch from a clear list.',
    ],
    'brigada.point_2' => [
        'cz' => 'Uveďte kontakt, na kterém vás zastihneme.',
        'en' => 'Enter contact details where we can reach you.',
    ],
    'brigada.point_3' => [
        'cz' => 'Zkušenosti z maloobchodu nebo velkoobchodu jsou výhodou, ne podmínkou.',
        'en' => 'Retail or wholesale experience is an advantage, not a requirement.',
    ],
    'brigada.form_label' => [
        'cz' => 'Online registrace',
        'en' => 'Online registration',
    ],
    'brigada.form_title' => [
        'cz' => 'Mám zájem o brigádu',
        'en' => 'I am interested in part-time work',
    ],
    'brigada.form_intro' => [
        'cz' => 'Formulář je společný pro maloobchod i velkoobchod. Typ registrace se přiřadí automaticky podle vybrané pobočky.',
        'en' => 'The form is shared for retail and wholesale. The registration type is assigned automatically according to the selected branch.',
    ],
    'brigada.branch_label' => [
        'cz' => 'Pobočka',
        'en' => 'Branch',
    ],
    'brigada.branch_choose' => [
        'cz' => 'Vyberte pobočku',
        'en' => 'Choose a branch',
    ],
    'brigada.branch_help' => [
        'cz' => 'Market, prodejna Qanto+ nebo velkoobchod',
        'en' => 'Market, Qanto+ store or wholesale branch',
    ],
    'brigada.position' => [
        'cz' => 'Pozice, o kterou se ucházíte',
        'en' => 'Position you are applying for',
    ],
    'brigada.position_value' => [
        'cz' => 'Brigádník',
        'en' => 'Part-time worker',
    ],
    'brigada.first_name' => [
        'cz' => 'Vaše jméno',
        'en' => 'First name',
    ],
    'brigada.last_name' => [
        'cz' => 'Vaše příjmení',
        'en' => 'Last name',
    ],
    'brigada.phone' => [
        'cz' => 'Telefon, mobil',
        'en' => 'Phone',
    ],
    'brigada.email' => [
        'cz' => 'E-mailová adresa',
        'en' => 'Email address',
    ],
    'brigada.experience' => [
        'cz' => 'Zkušenosti s prací v maloobchodě či velkoobchodě',
        'en' => 'Experience working in retail or wholesale',
    ],
    'brigada.note' => [
        'cz' => 'Poznámka',
        'en' => 'Note',
    ],
    'brigada.submit' => [
        'cz' => 'Odeslat registraci',
        'en' => 'Send registration',
    ],
    'brigada.success' => [
        'cz' => 'Registrace byla odeslána. Děkujeme.',
        'en' => 'The registration has been sent. Thank you.',
    ],
    'brigada.error_generic' => [
        'cz' => 'Registraci se nepodařilo odeslat. Zkuste to prosím znovu.',
        'en' => 'The registration could not be sent. Please try again.',
    ],
    'brigada.error_security' => [
        'cz' => 'Formulář vypršel, obnovte stránku a zkuste odeslání znovu.',
        'en' => 'The form has expired. Refresh the page and try again.',
    ],
    'brigada.error_branch' => [
        'cz' => 'Vyberte prosím pobočku.',
        'en' => 'Please choose a branch.',
    ],
    'brigada.error_required' => [
        'cz' => 'Vyplňte prosím jméno, příjmení, telefon a e-mail.',
        'en' => 'Please fill in first name, last name, phone and email.',
    ],
    'brigada.error_email' => [
        'cz' => 'Zadejte prosím platný e-mail.',
        'en' => 'Please enter a valid email address.',
    ],
    'brigada.modal_label' => [
        'cz' => 'Pobočky',
        'en' => 'Branches',
    ],
    'brigada.modal_title' => [
        'cz' => 'Vyberte pobočku',
        'en' => 'Choose a branch',
    ],
    'brigada.modal_search' => [
        'cz' => 'Název, město nebo středisko',
        'en' => 'Name, city or cost centre',
    ],
    'brigada.modal_empty' => [
        'cz' => 'Žádná pobočka neodpovídá hledání.',
        'en' => 'No branch matches the search.',
    ],
    'brigada.branch_type_markets' => [
        'cz' => 'Markety',
        'en' => 'Markets',
    ],
    'brigada.branch_type_qantoplus' => [
        'cz' => 'Prodejny Qanto+',
        'en' => 'Qanto+ stores',
    ],
    'brigada.branch_type_wholesale' => [
        'cz' => 'Velkoobchody',
        'en' => 'Wholesale branches',
    ],
    'brigada.branch_market' => [
        'cz' => 'market',
        'en' => 'market',
    ],
    'brigada.branch_qantoplus' => [
        'cz' => 'prodejna Qanto+',
        'en' => 'Qanto+ store',
    ],
    'brigada.branch_wholesale' => [
        'cz' => 'velkoobchod',
        'en' => 'wholesale',
    ],
    'page.o_nas.title' => [
        'cz' => 'O nás | Qanto',
        'en' => 'About us | Qanto',
    ],
    'page.o_nas.description' => [
        'cz' => 'Informace o společnosti Astur & Qanto.',
        'en' => 'Information about Astur & Qanto.',
    ],
    'page.historie.title' => [
        'cz' => 'Historie | Qanto',
        'en' => 'History | Qanto',
    ],
    'page.historie.description' => [
        'cz' => 'Historie společnosti Qanto.',
        'en' => 'Qanto company history.',
    ],
    'page.media.title' => [
        'cz' => 'Média | Qanto',
        'en' => 'Media | Qanto',
    ],
    'page.media.description' => [
        'cz' => 'Média a materiály společnosti Qanto.',
        'en' => 'Qanto media and materials.',
    ],
    'page.podporujeme.title' => [
        'cz' => 'Podporujeme | Qanto',
        'en' => 'We support | Qanto',
    ],
    'page.podporujeme.description' => [
        'cz' => 'Aktivity a projekty, které Qanto podporuje.',
        'en' => 'Activities and projects supported by Qanto.',
    ],
    'page.obchodni_podminky.title' => [
        'cz' => 'Obchodní podmínky | Qanto',
        'en' => 'Terms and Conditions | Qanto',
    ],
    'page.obchodni_podminky.description' => [
        'cz' => 'Obchodní podmínky společnosti Qanto.',
        'en' => 'Qanto Terms and Conditions.',
    ],
    'page.elektronicka_vymena_dat.title' => [
        'cz' => 'Elektronická výměna dat | Qanto',
        'en' => 'Electronic Data Interchange | Qanto',
    ],
    'page.elektronicka_vymena_dat.description' => [
        'cz' => 'Informace k elektronické výměně dat.',
        'en' => 'Information about electronic data interchange.',
    ],
    'page.zakaznicke_karty.title' => [
        'cz' => 'Zákaznické karty | Qanto',
        'en' => 'Customer Cards | Qanto',
    ],
    'page.zakaznicke_karty.description' => [
        'cz' => 'Informace k zákaznickým kartám Qanto.',
        'en' => 'Information about Qanto customer cards.',
    ],
    'page.kontakt.title' => [
        'cz' => 'Kontakty | Qanto',
        'en' => 'Contacts | Qanto',
    ],
    'page.kontakt.description' => [
        'cz' => 'Kontakty na pobočky, markety, velkoobchody a obchodní zástupce Qanto.',
        'en' => 'Contacts for Qanto branches, markets, wholesale and sales representatives.',
    ],
    'contacts.title' => [
        'cz' => 'Kontakty',
        'en' => 'Contacts',
    ],
    'contacts.jump_wholesale' => [
        'cz' => 'Přejděte na kontakty pro velkoobchodní pobočky',
        'en' => 'Go to wholesale branch contacts',
    ],
    'contacts.contact_us' => [
        'cz' => 'Kontaktujte nás',
        'en' => 'Contact us',
    ],
    'contacts.form_link' => [
        'cz' => 'Využijte kontaktní formulář',
        'en' => 'Use the contact form',
    ],
    'contacts.company_section' => [
        'cz' => 'Firemní a administrativní kontakt',
        'en' => 'Company and administrative contact',
    ],
    'contacts.company_contact' => [
        'cz' => 'Firemní kontakt',
        'en' => 'Company contact',
    ],
    'contacts.admin_contact' => [
        'cz' => 'Administrativní kontakt',
        'en' => 'Administrative contact',
    ],
    'contacts.company_address_label' => [
        'cz' => 'Sídlo',
        'en' => 'Registered office',
    ],
    'contacts.company_vat_label' => [
        'cz' => 'DIČ',
        'en' => 'VAT ID',
    ],
    'contacts.company_id_label' => [
        'cz' => 'IČ',
        'en' => 'Company ID',
    ],
    'contacts.people_title' => [
        'cz' => 'Lidé ve společnosti',
        'en' => 'People in the company',
    ],
    'contacts.wholesale_title' => [
        'cz' => 'Velkoobchodní pobočky',
        'en' => 'Wholesale branches',
    ],
    'contacts.branch_manager' => [
        'cz' => 'Vedoucí',
        'en' => 'Manager',
    ],
    'contacts.form_title' => [
        'cz' => 'Připomínka nebo dotaz',
        'en' => 'Comment or question',
    ],
    'contacts.form_text' => [
        'cz' => 'Napište nám, s čím vám můžeme pomoci. Dotaz pošleme na správné oddělení podle vybrané kategorie.',
        'en' => 'Write to us and tell us how we can help. We will send your question to the right department based on the selected category.',
    ],
    'contacts.form_category' => [
        'cz' => 'Typ dotazu',
        'en' => 'Question type',
    ],
    'contacts.form_name' => [
        'cz' => 'Vaše jméno a příjmení',
        'en' => 'Your first and last name',
    ],
    'contacts.form_name_placeholder' => [
        'cz' => 'Vaše jméno',
        'en' => 'Your name',
    ],
    'contacts.form_email' => [
        'cz' => 'Váš e-mail',
        'en' => 'Your e-mail',
    ],
    'contacts.form_email_placeholder' => [
        'cz' => 'Váš e-mail',
        'en' => 'Your e-mail',
    ],
    'contacts.form_phone' => [
        'cz' => 'Váš telefon',
        'en' => 'Your phone',
    ],
    'contacts.form_phone_placeholder' => [
        'cz' => 'Váš telefon',
        'en' => 'Your phone',
    ],
    'contacts.form_message' => [
        'cz' => 'Zpráva',
        'en' => 'Message',
    ],
    'contacts.form_message_placeholder' => [
        'cz' => 'Co máte na srdci?',
        'en' => 'What would you like to tell us?',
    ],
    'contacts.form_submit' => [
        'cz' => 'Odeslat zprávu',
        'en' => 'Send message',
    ],
    'contacts.form_consent' => [
        'cz' => 'Odesláním souhlasíte se',
        'en' => 'By sending, you agree to',
    ],
    'contacts.form_privacy_link' => [
        'cz' => 'zpracováním osobních údajů',
        'en' => 'personal data processing',
    ],
    'contacts.form_success' => [
        'cz' => 'Zpráva byla přijata. Děkujeme.',
        'en' => 'The message has been received. Thank you.',
    ],
    'contacts.form_invalid' => [
        'cz' => 'Formulář vypršel. Odešlete ho prosím znovu.',
        'en' => 'The form has expired. Please submit it again.',
    ],
    'contacts.form_category_error' => [
        'cz' => 'Vyberte prosím typ dotazu.',
        'en' => 'Please select a question type.',
    ],
    'contacts.form_name_error' => [
        'cz' => 'Zadejte prosím jméno a příjmení.',
        'en' => 'Please enter your first and last name.',
    ],
    'contacts.form_email_error' => [
        'cz' => 'Zadejte prosím platný e-mail.',
        'en' => 'Please enter a valid e-mail address.',
    ],
    'contacts.form_message_error' => [
        'cz' => 'Napište prosím zprávu.',
        'en' => 'Please write a message.',
    ],
    'contacts.form_error' => [
        'cz' => 'Zprávu se nepodařilo odeslat. Zkuste to prosím později.',
        'en' => 'The message could not be sent. Please try again later.',
    ],
    'page.napiste_nam.title' => [
        'cz' => 'Napište nám | Qanto',
        'en' => 'Write to us | Qanto',
    ],
    'page.napiste_nam.description' => [
        'cz' => 'Kontaktní formulář pro dotazy zákazníků.',
        'en' => 'Contact form for customer questions.',
    ],
    'page.privacy.title' => [
        'cz' => 'Zpracování osobních údajů | Qanto',
        'en' => 'Personal Data Processing | Qanto',
    ],
    'page.privacy.description' => [
        'cz' => 'Informace o zpracování osobních údajů ve společnosti Qanto.',
        'en' => 'Information about personal data processing at Qanto.',
    ],
    'page.cookies.title' => [
        'cz' => 'Soubory Cookies | Qanto',
        'en' => 'Cookies | Qanto',
    ],
    'page.cookies.description' => [
        'cz' => 'Informace o používání souborů cookies na webu Qanto.',
        'en' => 'Information about cookies used on the Qanto website.',
    ],

    'router.markety.title' => [
        'cz' => 'markety',
        'en' => 'markets',
    ],
    'router.velkoobchod.title' => [
        'cz' => 'velkoobchody',
        'en' => 'wholesale',
    ],
    'router.qantoplus.title' => [
        'cz' => 'velkoobchodní prodejny',
        'en' => 'wholesale stores',
    ],
    'velkoobchod.title' => [
        'cz' => 'Velkoobchod',
        'en' => 'Wholesale',
    ],
    'velkoobchod.intro_fallback' => [
        'cz' => 'Zásobujeme firmy, gastro provozy, prodejny a další zákazníky v regionech, které dlouhodobě obsluhujeme z našich velkoobchodních skladů.',
        'en' => 'We supply companies, food service operations, stores and other customers in regions served from our wholesale warehouses.',
    ],
    'velkoobchod.finder_title' => [
        'cz' => 'Velkoobchodní pobočky',
        'en' => 'Wholesale warehouses',
    ],
    'velkoobchod.finder_text' => [
        'cz' => 'Seznam skladů a mapa oblastí, do kterých pravidelně zavážíme.',
        'en' => 'Warehouse list and a map of areas we deliver to regularly.',
    ],
    'velkoobchod.branches_empty' => [
        'cz' => 'Aktuálně nejsou dostupné žádné velkoobchodní sklady.',
        'en' => 'No wholesale warehouses are currently available.',
    ],
    'velkoobchod.detail_link' => [
        'cz' => 'Detail velkoobchodu',
        'en' => 'Wholesale detail',
    ],
    'velkoobchod.detail_not_found' => [
        'cz' => 'Velkoobchod nebyl nalezen.',
        'en' => 'Wholesale branch not found.',
    ],
    'velkoobchod.detail_photo_label' => [
        'cz' => 'Velkoobchodní sklad',
        'en' => 'Wholesale warehouse',
    ],
    'velkoobchod.services_empty' => [
        'cz' => 'Služby pro tento velkoobchod doplníme.',
        'en' => 'Services for this wholesale branch will be added.',
    ],
    'velkoobchod.branch_contact_title' => [
        'cz' => 'Kontakt na velkoobchod',
        'en' => 'Wholesale contact',
    ],
    'velkoobchod.availability_title' => [
        'cz' => 'Vyhledat obec',
        'en' => 'Find a town',
    ],
    'velkoobchod.availability_text' => [
        'cz' => 'Zadejte obec nebo PSČ a ověřte dostupnost závozu.',
        'en' => 'Enter a town or postcode to check delivery availability.',
    ],
    'velkoobchod.availability_label' => [
        'cz' => 'Obec nebo PSČ',
        'en' => 'Town or postcode',
    ],
    'velkoobchod.availability_placeholder' => [
        'cz' => 'Např. Svitavy nebo 568 02',
        'en' => 'E.g. Svitavy or 568 02',
    ],
    'velkoobchod.availability_submit' => [
        'cz' => 'Ověřit',
        'en' => 'Check',
    ],
    'velkoobchod.availability_served' => [
        'cz' => 'Do této obce zavážíme.',
        'en' => 'We deliver to this town.',
    ],
    'velkoobchod.availability_excluded' => [
        'cz' => 'Do této obce standardně nezavážíme.',
        'en' => 'We do not normally deliver to this town.',
    ],
    'velkoobchod.availability_review' => [
        'cz' => 'Dostupnost závozu v této obci ověřujeme.',
        'en' => 'We are reviewing delivery availability for this town.',
    ],
    'velkoobchod.availability_not_served' => [
        'cz' => 'Do této obce aktuálně nezavážíme.',
        'en' => 'We do not currently deliver to this town.',
    ],
    'velkoobchod.availability_no_result' => [
        'cz' => 'Obec jsme v číselníku nenašli. Zkuste prosím zadat přesnější název nebo PSČ.',
        'en' => 'We could not find the town. Please enter a more precise name or postcode.',
    ],
    'velkoobchod.contact_person' => [
        'cz' => 'Kontaktní osoba',
        'en' => 'Contact person',
    ],
    'velkoobchod.no_contact' => [
        'cz' => 'Kontakt doplníme.',
        'en' => 'Contact will be added.',
    ],
    'velkoobchod.map_title' => [
        'cz' => 'Mapa závozového území',
        'en' => 'Delivery area map',
    ],
    'velkoobchod.map_text' => [
        'cz' => 'Na mapě jsou vyznačené obce, do kterých pravidelně zavážíme. Body označují velkoobchodní sklady.',
        'en' => 'The map shows towns we regularly deliver to. Points mark wholesale warehouses.',
    ],
    'velkoobchod.map_empty' => [
        'cz' => 'Pro mapu nejsou dostupná data závozových obcí.',
        'en' => 'No delivery area data is available for the map.',
    ],
    'velkoobchod.map_branch' => [
        'cz' => 'Velkoobchod',
        'en' => 'Wholesale',
    ],
    'velkoobchod.map_reset' => [
        'cz' => 'Celá ČR',
        'en' => 'Whole Czech Republic',
    ],
    'velkoobchod.representatives_title' => [
        'cz' => 'Obchodní zástupci',
        'en' => 'Sales representatives',
    ],
    'velkoobchod.representatives_text' => [
        'cz' => 'Vyberte sklad a zobrazte příslušné obchodní zástupce.',
        'en' => 'Choose a warehouse to show its sales representatives.',
    ],
    'velkoobchod.representatives_detail_text' => [
        'cz' => 'Obchodní zástupci přiřazení k tomuto velkoobchodnímu skladu.',
        'en' => 'Sales representatives assigned to this wholesale warehouse.',
    ],
    'velkoobchod.representatives_detail_empty' => [
        'cz' => 'K tomuto velkoobchodu nejsou přiřazeni žádní obchodní zástupci.',
        'en' => 'No sales representatives are assigned to this wholesale warehouse.',
    ],
    'velkoobchod.all_warehouses' => [
        'cz' => 'Všechny sklady',
        'en' => 'All warehouses',
    ],
    'velkoobchod.representatives_empty_filter' => [
        'cz' => 'Pro vybraný sklad nejsou dostupní žádní obchodní zástupci.',
        'en' => 'No sales representatives are available for the selected warehouse.',
    ],
    'velkoobchod.representatives_empty' => [
        'cz' => 'Obchodní zástupce doplníme.',
        'en' => 'Sales representatives will be added.',
    ],
    'velkoobchod.contact_text' => [
        'cz' => 'Chcete začít odebírat zboží velkoobchodně? Ozvěte se nám a najdeme vhodné řešení.',
        'en' => 'Would you like to buy wholesale? Contact us and we will find the right solution.',
    ],

    'footer.claim' => [
        'cz' => 'Markety, velkoobchody, velkoobchodní prodejny a služby pro zákazníky, kteří potřebují spolehlivého partnera.',
        'en' => 'Wholesale, markets and services for customers who need a reliable partner.',
    ],
    'footer.social_title' => [
        'cz' => 'Sociální sítě',
        'en' => 'Social networks',
    ],
    'footer.legal_title' => [
        'cz' => 'Informace',
        'en' => 'Information',
    ],
    'footer.other_title' => [
        'cz' => 'Ostatní',
        'en' => 'Other',
    ],
    'footer.business_terms' => [
        'cz' => 'Obchodní podmínky',
        'en' => 'Terms and Conditions',
    ],
    'footer.edi' => [
        'cz' => 'Elektronická výměna dat',
        'en' => 'Electronic Data Interchange',
    ],
    'footer.customer_cards' => [
        'cz' => 'Zákaznické karty',
        'en' => 'Customer Cards',
    ],
    'footer.cookies' => [
        'cz' => 'Soubory Cookies',
        'en' => 'Cookies',
    ],
    'footer.privacy' => [
        'cz' => 'Zpracování osobních údajů',
        'en' => 'Personal Data Processing',
    ],
    'footer.facebook_wholesale' => [
        'cz' => 'Facebook velkoobchod',
        'en' => 'Facebook wholesale',
    ],
    'footer.facebook_retail' => [
        'cz' => 'Facebook markety',
        'en' => 'Facebook markets',
    ],
    'footer.instagram_wholesale' => [
        'cz' => 'Instagram velkoobchod',
        'en' => 'Instagram wholesale',
    ],
    'footer.instagram_retail' => [
        'cz' => 'Instagram markety',
        'en' => 'Instagram markets',
    ],
    'footer.copy' => [
        'cz' => 'Astur & Qanto s.r.o.',
        'en' => 'Astur & Qanto s.r.o.',
    ],

    'common.preparing' => [
        'cz' => 'Připravujeme',
        'en' => 'Coming soon',
    ],
    'common.more' => [
        'cz' => 'Zjistěte více',
        'en' => 'Learn more',
    ],
    'common.close' => [
        'cz' => 'Zavřít',
        'en' => 'Close',
    ],
    'common.previous' => [
        'cz' => 'Předchozí',
        'en' => 'Previous',
    ],
    'common.next' => [
        'cz' => 'Další',
        'en' => 'Next',
    ],
    'common.text_unavailable' => [
        'cz' => 'Text není aktuálně dostupný.',
        'en' => 'The text is not currently available.',
    ],
    'akce.view_offer' => [
        'cz' => 'Prohlédnout',
        'en' => 'View',
    ],
    'akce.page' => [
        'cz' => 'Strana',
        'en' => 'Page',
    ],
    'akce.back_to_list' => [
        'cz' => 'Zpět na letáky',
        'en' => 'Back to flyers',
    ],
    'akce.page_title' => [
        'cz' => 'Letáky',
        'en' => 'Flyers',
    ],
    'akce.page_text' => [
        'cz' => '',
        'en' => 'Current, upcoming and past flyers in one place.',
    ],
    'akce.current_title' => [
        'cz' => 'Právě platné',
        'en' => 'Currently valid',
    ],
    'akce.valid_title' => [
        'cz' => 'Právě platné',
        'en' => 'Currently valid',
    ],
    'akce.valid_text' => [
        'cz' => 'Letáky, které jsou aktuálně v platnosti.',
        'en' => 'Flyers that are currently valid.',
    ],
    'akce.upcoming_title' => [
        'cz' => 'Nadcházející letáky',
        'en' => 'Upcoming flyers',
    ],
    'akce.upcoming_text' => [
        'cz' => 'Připravované akce, které začnou platit v nejbližších dnech.',
        'en' => 'Upcoming promotions that will become valid soon.',
    ],
    'akce.archive_title' => [
        'cz' => 'Uplynulé letáky',
        'en' => 'Past flyers',
    ],
    'akce.archive_text' => [
        'cz' => 'Archiv posledních uplynulých nabídek.',
        'en' => 'Archive of recent past offers.',
    ],
    'akce.no_pages' => [
        'cz' => 'Pro tento leták zatím nejsou dostupné stránky prohlížeče.',
        'en' => 'Viewer pages are not available for this flyer yet.',
    ],
    'akce.no_current' => [
        'cz' => 'Aktuálně zde nejsou žádné platné letáky.',
        'en' => 'There are no current promotions available.',
    ],
    'akce.no_upcoming' => [
        'cz' => 'Nejsou zde žádné nadcházející letáky.',
        'en' => 'There are no upcoming promotions available.',
    ],
    'akce.no_archive' => [
        'cz' => 'Archiv uplynulých letáků je zatím prázdný.',
        'en' => 'The archive of past flyers is empty for now.',
    ],
    'flyers.title' => [
        'cz' => 'Letáky',
        'en' => 'Flyers',
    ],
    'flyers.text' => [
        'cz' => 'Prohlédněte si poslední letáky.',
        'en' => 'Browse the latest flyers.',
    ],
    'flyers.all' => [
        'cz' => 'Všechny letáky',
        'en' => 'All flyers',
    ],
    'flyers.all_categories' => [
        'cz' => 'Všechny',
        'en' => 'All',
    ],
    'flyers.category' => [
        'cz' => 'Kategorie',
        'en' => 'Category',
    ],
    'flyers.validity_from_to' => [
        'cz' => 'Platí od %s do %s',
        'en' => 'Valid from %s to %s',
    ],
    'flyers.validity_to' => [
        'cz' => 'Platí do %s',
        'en' => 'Valid until %s',
    ],
    'flyers.validity_from' => [
        'cz' => 'Platí od %s',
        'en' => 'Valid from %s',
    ],
    'flyers.status_valid' => [
        'cz' => 'platné',
        'en' => 'valid',
    ],
    'flyers.status_upcoming' => [
        'cz' => 'nadcházející',
        'en' => 'upcoming',
    ],
    'flyers.status_expired' => [
        'cz' => 'uplynulé',
        'en' => 'past',
    ],
    'flyers.pdf' => [
        'cz' => 'PDF',
        'en' => 'PDF',
    ],
    'flyers.download_pdf' => [
        'cz' => 'Stáhnout PDF',
        'en' => 'Download PDF',
    ],
    'flyers.browse' => [
        'cz' => 'Prolistovat',
        'en' => 'Browse',
    ],
    'flyers.preview_missing' => [
        'cz' => 'Náhled se připravuje',
        'en' => 'Preview is being prepared',
    ],
    'flyers.pagination_label' => [
        'cz' => 'Stránkování letáků',
        'en' => 'Flyer pagination',
    ],
    'flyers.pagination_first' => [
        'cz' => 'První stránka',
        'en' => 'First page',
    ],
    'flyers.pagination_prev' => [
        'cz' => 'Předchozí stránka',
        'en' => 'Previous page',
    ],
    'flyers.pagination_next' => [
        'cz' => 'Další stránka',
        'en' => 'Next page',
    ],
    'flyers.pagination_last' => [
        'cz' => 'Poslední stránka',
        'en' => 'Last page',
    ],
    'flyers.first_page' => [
        'cz' => 'První strana',
        'en' => 'First page',
    ],
    'flyers.prev_page' => [
        'cz' => 'Předchozí strana',
        'en' => 'Previous page',
    ],
    'flyers.next_page' => [
        'cz' => 'Další strana',
        'en' => 'Next page',
    ],
    'flyers.last_page' => [
        'cz' => 'Poslední strana',
        'en' => 'Last page',
    ],
    'flyers.fullscreen' => [
        'cz' => 'Celá obrazovka',
        'en' => 'Fullscreen',
    ],
    'flyers.zoom_in' => [
        'cz' => 'Zvětšit',
        'en' => 'Zoom in',
    ],
    'flyers.zoom_out' => [
        'cz' => 'Zmenšit',
        'en' => 'Zoom out',
    ],
    'flyers.zoom_reset' => [
        'cz' => 'Původní velikost',
        'en' => 'Reset zoom',
    ],
    'flyers.close_viewer' => [
        'cz' => 'Zavřít prohlížeč',
        'en' => 'Close viewer',
    ],
    'flyers.page_thumbs' => [
        'cz' => 'Náhledy stran',
        'en' => 'Page thumbnails',
    ],
    'flyers.empty' => [
        'cz' => 'Aktuálně zde nejsou žádné platné ani nadcházející letáky.',
        'en' => 'There are no valid or upcoming flyers available.',
    ],
    'flyers.subscribe_kicker' => [
        'cz' => 'Odběr letáků',
        'en' => 'Flyer subscription',
    ],
    'flyers.subscribe_title' => [
        'cz' => 'Nový leták vám pošleme přímo na e-mail',
        'en' => 'We will send new flyers directly to your e-mail',
    ],
    'flyers.subscribe_text' => [
        'cz' => 'Vyberte si typy letáků, které chcete dostávat.',
        'en' => 'Choose the types of flyers you want to receive.',
    ],
    'flyers.subscribe_email' => [
        'cz' => 'Váš e-mail',
        'en' => 'Your e-mail',
    ],
    'flyers.subscribe_button' => [
        'cz' => 'Odebírat',
        'en' => 'Subscribe',
    ],
    'flyers.subscribe_types' => [
        'cz' => 'Typy letáků',
        'en' => 'Flyer types',
    ],
    'flyers.subscribe_type_markety' => [
        'cz' => 'Markety',
        'en' => 'Markets',
    ],
    'flyers.subscribe_type_velkoobchod' => [
        'cz' => 'Velkoobchod',
        'en' => 'Wholesale',
    ],
    'flyers.subscribe_type_qantoplus' => [
        'cz' => 'Qanto+',
        'en' => 'Qanto+',
    ],
    'flyers.subscribe_consent' => [
        'cz' => 'Odběrem souhlasíte se',
        'en' => 'By subscribing, you agree to',
    ],
    'flyers.subscribe_privacy_link' => [
        'cz' => 'zpracováním osobních údajů',
        'en' => 'personal data processing',
    ],
    'flyers.subscribe_success' => [
        'cz' => 'Odběr letáků byl uložen.',
        'en' => 'Your flyer subscription has been saved.',
    ],
    'flyers.subscribe_invalid' => [
        'cz' => 'Formulář vypršel. Odešlete ho prosím znovu.',
        'en' => 'The form has expired. Please submit it again.',
    ],
    'flyers.subscribe_email_error' => [
        'cz' => 'Zadejte prosím platný e-mail.',
        'en' => 'Please enter a valid e-mail address.',
    ],
    'flyers.subscribe_type_error' => [
        'cz' => 'Vyberte prosím alespoň jeden typ letáků.',
        'en' => 'Please select at least one flyer type.',
    ],
    'flyers.subscribe_error' => [
        'cz' => 'Odběr se nepodařilo uložit. Zkuste to prosím později.',
        'en' => 'The subscription could not be saved. Please try again later.',
    ],
    'news.latest_title' => [
        'cz' => 'Poslední novinky',
        'en' => 'Latest news',
    ],
    'news.page_title' => [
        'cz' => 'Novinky',
        'en' => 'News',
    ],
    'news.more_title' => [
        'cz' => 'Další novinky',
        'en' => 'More news',
    ],
    'news.tags_filter' => [
        'cz' => 'Filtrovat podle štítků',
        'en' => 'Filter by tags',
    ],
    'news.tags_all' => [
        'cz' => 'Všechny',
        'en' => 'All',
    ],
    'news.pagination_label' => [
        'cz' => 'Stránkování novinek',
        'en' => 'News pagination',
    ],
    'news.pagination_first' => [
        'cz' => 'První stránka',
        'en' => 'First page',
    ],
    'news.pagination_prev' => [
        'cz' => 'Předchozí stránka',
        'en' => 'Previous page',
    ],
    'news.pagination_next' => [
        'cz' => 'Další stránka',
        'en' => 'Next page',
    ],
    'news.pagination_last' => [
        'cz' => 'Poslední stránka',
        'en' => 'Last page',
    ],
    'news.subscribe_kicker' => [
        'cz' => 'Odběr novinek',
        'en' => 'News subscription',
    ],
    'news.subscribe_title' => [
        'cz' => 'Novinky na e-mail',
        'en' => 'News by e-mail',
    ],
    'news.subscribe_text' => [
        'cz' => 'Pošleme vám aktuální informace ze světa Qanto.',
        'en' => 'We will send you current information from Qanto.',
    ],
    'news.subscribe_email' => [
        'cz' => 'Váš e-mail',
        'en' => 'Your e-mail',
    ],
    'news.subscribe_button' => [
        'cz' => 'Odebírat',
        'en' => 'Subscribe',
    ],
    'news.subscribe_consent' => [
        'cz' => 'Odběrem souhlasíte se',
        'en' => 'By subscribing, you agree to',
    ],
    'news.subscribe_privacy_link' => [
        'cz' => 'zpracováním osobních údajů',
        'en' => 'personal data processing',
    ],
    'news.subscribe_success' => [
        'cz' => 'Odběr novinek byl uložen.',
        'en' => 'Your news subscription has been saved.',
    ],
    'news.subscribe_invalid' => [
        'cz' => 'Formulář vypršel. Odešlete ho prosím znovu.',
        'en' => 'The form has expired. Please submit it again.',
    ],
    'news.subscribe_email_error' => [
        'cz' => 'Zadejte prosím platný e-mail.',
        'en' => 'Please enter a valid e-mail address.',
    ],
    'news.subscribe_error' => [
        'cz' => 'Odběr se nepodařilo uložit. Zkuste to prosím později.',
        'en' => 'The subscription could not be saved. Please try again later.',
    ],
    'news.latest_text' => [
        'cz' => 'Přečtěte si aktuální informace, zprávy, novinky.',
        'en' => 'Read current information, updates and news.',
    ],
    'news.all' => [
        'cz' => 'Všechny novinky',
        'en' => 'All news',
    ],
    'news.read_more' => [
        'cz' => 'přečíst celé',
        'en' => 'read more',
    ],
    'news.empty' => [
        'cz' => 'Aktuálně zde nejsou žádné novinky.',
        'en' => 'There are no news items available.',
    ],
    'news.back_to_list' => [
        'cz' => 'Zpět na novinky',
        'en' => 'Back to news',
    ],
    'form.captcha_label' => [
        'cz' => 'Ověření',
        'en' => 'Verification',
    ],
    'form.captcha_question_sum' => [
        'cz' => 'Kolik je %d + %d?',
        'en' => 'What is %d + %d?',
    ],
    'form.captcha_answer' => [
        'cz' => 'Výsledek',
        'en' => 'Answer',
    ],
    'form.captcha_help' => [
        'cz' => 'Pro ochranu proti robotům napište výsledek.',
        'en' => 'To protect against bots, enter the answer.',
    ],
    'form.captcha_invalid' => [
        'cz' => 'Ověření proti robotům nebylo správné. Zkuste to prosím znovu.',
        'en' => 'The anti-bot verification was not correct. Please try again.',
    ],
    'common.back_home' => [
        'cz' => 'Zpět na úvod',
        'en' => 'Back to home',
    ],
    'common.select_empty' => [
        'cz' => 'Vyberte',
        'en' => 'Select',
    ],
    'common.yes' => [
        'cz' => 'Ano',
        'en' => 'Yes',
    ],
    'common.no' => [
        'cz' => 'Ne',
        'en' => 'No',
    ],
    'common.page_not_found' => [
        'cz' => 'Stránka nebyla nalezena.',
        'en' => 'Page not found.',
    ],
    'placeholder.text' => [
        'cz' => 'Tato část frontendu bude napojená na hotovou administraci a doladíme ji podle Figmy.',
        'en' => 'This frontend section will be connected to the prepared administration and tuned according to Figma.',
    ],
    'markety.title' => [
        'cz' => 'Markety',
        'en' => 'Markets',
    ],
    'markety.finder_title' => [
        'cz' => 'Najděte Qanto market podle města',
        'en' => 'Find a Qanto Market by town',
    ],
    'markety.finder_text' => [
        'cz' => 'Zobrazují se všechny dostupné markety.',
        'en' => 'All available markets are shown.',
    ],
    'markety.all_cities' => [
        'cz' => 'Celá ČR',
        'en' => 'All towns',
    ],
    'markety.city_search_placeholder' => [
        'cz' => 'Hledat obec',
        'en' => 'Search town',
    ],
    'markety.city_search_empty' => [
        'cz' => 'Žádná obec neodpovídá hledání.',
        'en' => 'No town matches the search.',
    ],
    'markety.empty' => [
        'cz' => 'Aktuálně nejsou dostupné žádné markety.',
        'en' => 'No markets are currently available.',
    ],
    'markety.filter_empty' => [
        'cz' => 'Pro vybrané město nejsou dostupné žádné markety.',
        'en' => 'No markets are available for the selected town.',
    ],
    'markety.map_empty' => [
        'cz' => 'Pro mapu nejsou dostupné souřadnice marketů.',
        'en' => 'No market coordinates are available for the map.',
    ],
    'markety.map_branch' => [
        'cz' => 'Qanto Market',
        'en' => 'Qanto Market',
    ],
    'markety.opening_unknown' => [
        'cz' => 'Otevírací doba není uvedena',
        'en' => 'Opening hours are not available',
    ],
    'markety.closed_today' => [
        'cz' => 'Dnes zavřeno',
        'en' => 'Closed today',
    ],
    'markety.open_now' => [
        'cz' => 'Otevřeno',
        'en' => 'Open',
    ],
    'markety.open_from_to' => [
        'cz' => 'Otevřeno od %s do %s',
        'en' => 'Open from %s to %s',
    ],
    'markety.closed_now' => [
        'cz' => 'Zavřeno',
        'en' => 'Closed',
    ],
    'markety.closed_today_from_to' => [
        'cz' => 'Zavřeno, dnes od %s do %s',
        'en' => 'Closed, today from %s to %s',
    ],
    'markety.closed_tomorrow_from_to' => [
        'cz' => 'Zavřeno, zítra od %s do %s',
        'en' => 'Closed, tomorrow from %s to %s',
    ],
    'markety.closed' => [
        'cz' => 'Zavřeno',
        'en' => 'Closed',
    ],
    'markety.today' => [
        'cz' => 'Dnes',
        'en' => 'Today',
    ],
    'markety.exception' => [
        'cz' => 'Výjimka',
        'en' => 'Exception',
    ],
    'markety.opening_exception_today' => [
        'cz' => 'Dnes platí upravená otevírací doba.',
        'en' => 'Adjusted opening hours apply today.',
    ],
    'markety.detail_link' => [
        'cz' => 'Detail marketu',
        'en' => 'Market detail',
    ],
    'markety.detail_not_found' => [
        'cz' => 'Market nebyl nalezen.',
        'en' => 'Market not found.',
    ],
    'markety.detail_kicker' => [
        'cz' => 'Qanto Market',
        'en' => 'Qanto Market',
    ],
    'markety.gallery_placeholder' => [
        'cz' => 'Fotogalerie připravujeme',
        'en' => 'Photo gallery coming soon',
    ],
    'markety.gallery_count' => [
        'cz' => '%d fotek',
        'en' => '%d photos',
    ],
    'markety.gallery_title' => [
        'cz' => 'Fotogalerie',
        'en' => 'Photo gallery',
    ],
    'markety.gallery_intro' => [
        'cz' => 'Prohlédněte si %d fotografií této prodejny.',
        'en' => 'Browse %d photos of this store.',
    ],
    'markety.gallery_open_photo' => [
        'cz' => 'Otevřít fotografii %d',
        'en' => 'Open photo %d',
    ],
    'markety.opening_title' => [
        'cz' => 'Otevírací doba',
        'en' => 'Opening hours',
    ],
    'markety.services_title' => [
        'cz' => 'Služby',
        'en' => 'Services',
    ],
    'markety.services_empty' => [
        'cz' => 'Služby pro tuto prodejnu doplníme.',
        'en' => 'Services for this store will be added.',
    ],
    'markety.store_contact_title' => [
        'cz' => 'Kontakt na prodejnu',
        'en' => 'Store contact',
    ],
    'markety.manager' => [
        'cz' => 'Vedoucí',
        'en' => 'Manager',
    ],
    'markety.flyers_title' => [
        'cz' => 'Letáky',
        'en' => 'Flyers',
    ],
    'markety.flyer_current' => [
        'cz' => 'Platný leták',
        'en' => 'Current flyer',
    ],
    'markety.flyer_weekend' => [
        'cz' => 'Víkendový leták',
        'en' => 'Weekend flyer',
    ],
    'markety.flyer_upcoming' => [
        'cz' => 'Nadcházející leták',
        'en' => 'Upcoming flyer',
    ],
    'markety.jobs_title' => [
        'cz' => 'Volná místa na prodejně',
        'en' => 'Open positions in this store',
    ],
    'markety.jobs_empty' => [
        'cz' => 'Na této prodejně teď nejsou vypsaná volná místa.',
        'en' => 'There are no open positions in this store right now.',
    ],
    'markety.feedback_title' => [
        'cz' => 'Připomínka nebo dotaz',
        'en' => 'Comment or question',
    ],
    'markety.feedback_text' => [
        'cz' => 'Napište nám zprávu k této prodejně. Odesílání napojíme po doladění formuláře.',
        'en' => 'Send us a message about this store. Submission will be connected after the form is finalized.',
    ],
    'markety.contact_title' => [
        'cz' => 'Kontaktujte nás',
        'en' => 'Contact us',
    ],
    'markety.contact_text' => [
        'cz' => 'Spojte se s námi, ať už potřebujete cokoliv vyřídit nebo zanechat připomínku.',
        'en' => 'Get in touch if you need to arrange anything or leave us a message.',
    ],
    'markety.contact_button' => [
        'cz' => 'Zobrazit kontakty',
        'en' => 'Show contacts',
    ],
    'prodejny.title' => [
        'cz' => 'Prodejny',
        'en' => 'Stores',
    ],
    'prodejny.finder_title' => [
        'cz' => 'Najděte prodejnu Qanto+ podle města',
        'en' => 'Find a Qanto+ store by town',
    ],
    'prodejny.finder_text' => [
        'cz' => 'Zobrazují se všechny dostupné prodejny Qanto+.',
        'en' => 'All available Qanto+ stores are shown.',
    ],
    'prodejny.empty' => [
        'cz' => 'Aktuálně nejsou dostupné žádné prodejny.',
        'en' => 'No stores are currently available.',
    ],
    'prodejny.filter_empty' => [
        'cz' => 'Pro vybrané město nejsou dostupné žádné prodejny.',
        'en' => 'No stores are available for the selected town.',
    ],
    'prodejny.map_empty' => [
        'cz' => 'Pro mapu nejsou dostupné souřadnice prodejen.',
        'en' => 'No store coordinates are available for the map.',
    ],
    'prodejny.map_branch' => [
        'cz' => 'Qanto+',
        'en' => 'Qanto+',
    ],
    'prodejny.detail_link' => [
        'cz' => 'Detail prodejny',
        'en' => 'Store detail',
    ],
    'prodejny.detail_not_found' => [
        'cz' => 'Prodejna nebyla nalezena.',
        'en' => 'Store not found.',
    ],
    'contact.form.name' => [
        'cz' => 'Vaše jméno a příjmení',
        'en' => 'Your full name',
    ],
    'contact.form.name_placeholder' => [
        'cz' => 'Vaše jméno',
        'en' => 'Your name',
    ],
    'contact.form.email' => [
        'cz' => 'Váš email',
        'en' => 'Your email',
    ],
    'contact.form.email_placeholder' => [
        'cz' => 'Váš e-mail',
        'en' => 'Your email',
    ],
    'contact.form.phone' => [
        'cz' => 'Váš telefon',
        'en' => 'Your phone',
    ],
    'contact.form.phone_placeholder' => [
        'cz' => 'Váš telefon',
        'en' => 'Your phone',
    ],
    'contact.form.message' => [
        'cz' => 'Zpráva',
        'en' => 'Message',
    ],
    'contact.form.message_placeholder' => [
        'cz' => 'Co máte na srdci?',
        'en' => 'What would you like to tell us?',
    ],
    'contact.form.submit' => [
        'cz' => 'Odeslat zprávu',
        'en' => 'Send message',
    ],
    'page.volani.title' => [
        'cz' => 'Vyúčtování volání | Qanto',
        'en' => 'Phone billing | Qanto',
    ],
    'page.volani.description' => [
        'cz' => 'Zákaznické vyúčtování telefonních služeb Qanto.',
        'en' => 'Customer phone service billing at Qanto.',
    ],
    'volani.title' => [
        'cz' => 'Vyúčtování volání',
        'en' => 'Phone billing',
    ],
    'volani.overview_title' => [
        'cz' => 'Přehled vyúčtování',
        'en' => 'Billing overview',
    ],
    'volani.summary_title' => [
        'cz' => 'Souhrnné vyúčtování',
        'en' => 'Billing summary',
    ],
    'volani.detail_title' => [
        'cz' => 'Podrobný výpis',
        'en' => 'Detailed statement',
    ],
    'volani.not_found' => [
        'cz' => 'Vyúčtování nebylo nalezeno.',
        'en' => 'Billing record was not found.',
    ],
    'volani.period' => [
        'cz' => 'Období',
        'en' => 'Period',
    ],
    'volani.select_period' => [
        'cz' => 'Vyberte období',
        'en' => 'Select period',
    ],
    'volani.email' => [
        'cz' => 'E-mail',
        'en' => 'E-mail',
    ],
    'volani.name' => [
        'cz' => 'Jméno',
        'en' => 'Name',
    ],
    'volani.phone' => [
        'cz' => 'Telefon',
        'en' => 'Phone',
    ],
    'volani.without_vat' => [
        'cz' => 'Bez DPH',
        'en' => 'Excl. VAT',
    ],
    'volani.with_vat' => [
        'cz' => 'S DPH',
        'en' => 'Incl. VAT',
    ],
    'volani.summary' => [
        'cz' => 'Souhrn',
        'en' => 'Summary',
    ],
    'volani.detail' => [
        'cz' => 'Detail',
        'en' => 'Detail',
    ],
    'volani.back_overview' => [
        'cz' => 'Zpět na přehled',
        'en' => 'Back to overview',
    ],
    'volani.product' => [
        'cz' => 'Produktová řada',
        'en' => 'Product line',
    ],
    'volani.item' => [
        'cz' => 'Položka',
        'en' => 'Item',
    ],
    'volani.service' => [
        'cz' => 'Služba',
        'en' => 'Service',
    ],
    'volani.datetime' => [
        'cz' => 'Datum a čas',
        'en' => 'Date and time',
    ],
    'volani.direction' => [
        'cz' => 'Směr',
        'en' => 'Direction',
    ],
    'volani.called_number' => [
        'cz' => 'Volané číslo',
        'en' => 'Called number',
    ],
    'volani.duration' => [
        'cz' => 'Trvání',
        'en' => 'Duration',
    ],
    'volani.count' => [
        'cz' => 'Počet',
        'en' => 'Count',
    ],
    'volani.volume' => [
        'cz' => 'Objem',
        'en' => 'Volume',
    ],
    'volani.vat' => [
        'cz' => 'DPH',
        'en' => 'VAT',
    ],
    'common.total' => [
        'cz' => 'Celkem',
        'en' => 'Total',
    ],
    'common.show' => [
        'cz' => 'Zobrazit',
        'en' => 'Show',
    ],
];
