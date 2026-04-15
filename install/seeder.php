<?php
/**
 * Jiji-Inspired-1.0 Seeder Script
 * Includes Nigerian States & LGAs, and COMPLETE Jiji Categories
 */

function seed_database($pdo) {
    // 0. Clear existing categories to ensure fresh start
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE categories");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 1. Seed COMPLETE Jiji-Standard Categories
    $jobs_subs = [
        'Accounting, Auditing & Finance', 'Admin & Office Support', 'Agricultural Jobs', 'Aviation Jobs',
        'Banking Jobs', 'Construction Jobs', 'Customer Service & Call Centre', 'Energy, Oil & Gas Jobs',
        'Engineering & Technical Jobs', 'Healthcare & Nursing', 'Hospitality & Hotel Jobs', 'HR & Recruitment Jobs',
        'ICT & Computer Jobs', 'Legal Jobs', 'Management & Business Development', 'Manufacturing Jobs',
        'Marketing & Communication Jobs', 'Media & Advertisement Jobs', 'NGO, Social & Charity Jobs',
        'Procurement & Logistics Jobs', 'Real Estate Jobs', 'Sales Jobs', 'Transportation & Driving Jobs'
    ];

    $categories_data = [
        'Vehicles' => [
            'icon' => 'fa-car', 'is_top' => 1, 'order' => 1,
            'subs' => ['Cars', 'Motorcycles & Scooters', 'Trucks & Trailers', 'Buses & Microbuses', 'Boats', 'Heavy Equipment', 'Auto Parts & Accessories', 'Car Care & Detailing', 'Number Plates & Stamps']
        ],
        'Property (Real Estate)' => [
            'icon' => 'fa-home', 'is_top' => 1, 'order' => 2,
            'subs' => ['Houses & Apartments for Rent', 'Houses & Apartments for Sale', 'Land & Plots for Sale', 'Land & Plots for Rent / Lease', 'Commercial Property for Rent', 'Commercial Property for Sale', 'Short Let / Daily Rentals', 'New Developments']
        ],
        'Phones & Tablets' => [
            'icon' => 'fa-mobile-alt', 'is_top' => 1, 'order' => 3,
            'subs' => ['Mobile Phones', 'Tablets', 'Accessories for Phones & Tablets']
        ],
        'Electronics' => [
            'icon' => 'fa-tv', 'is_top' => 1, 'order' => 4,
            'subs' => ['Computers & Laptops', 'Computer Accessories & Peripherals', 'TV & DVD Equipment', 'Cameras & Video Cameras', 'Sound & Music Equipment', 'Games & Gaming', 'Printers & Scanners', 'Networking & Connectivity']
        ],
        'Home, Furniture & Appliances' => [
            'icon' => 'fa-couch', 'is_top' => 0, 'order' => 5,
            'subs' => ['Kitchen Appliances', 'Fridges & Freezers', 'Washing Machines', 'Air Conditioning & Fans', 'Sofas & Living Room Sets', 'Beds & Mattresses', 'Dining Sets', 'Office Furniture', 'Wardrobes & Closets', 'Generators, UPS & Solar Energy', 'Lighting & Ceiling Fans', 'Cooking & Baking Appliances', 'Garden & Outdoor Items', 'Curtains & Blinds']
        ],
        'Fashion & Accessories' => [
            'icon' => 'fa-tshirt', 'is_top' => 0, 'order' => 6,
            'subs' => ["Men's Clothing", "Women's Clothing", "Children's Clothing", "Men's Shoes", "Women's Shoes", 'Bags', 'Watches & Accessories', 'Jewelry & Gemstones', 'Sunglasses & Eyewear']
        ],
        'Beauty & Personal Care' => [
            'icon' => 'fa-heartbeat', 'is_top' => 0, 'order' => 7,
            'subs' => ['Skin Care', 'Hair Care & Wigs', 'Make-up & Cosmetics', 'Health Care & Supplements', 'Perfumes & Fragrances', 'Nail Care']
        ],
        'Services' => [
            'icon' => 'fa-concierge-bell', 'is_top' => 0, 'order' => 8,
            'subs' => ['Financial Services', 'Legal Services', 'Education & Training', 'Cleaning & Household Services', 'Car Services & Repair', 'Catering & Chef Services', 'Computer & Technology Services', 'Health Services', 'Moving & Delivery Services', 'Photography & Videography', 'Event Planning & Management', 'Social Media & Digital Marketing']
        ],
        'Repair & Construction' => [
            'icon' => 'fa-tools', 'is_top' => 0, 'order' => 9,
            'subs' => ['Plumbing & Water Systems', 'Electrical Work', 'Construction & Civil Engineering', 'Painting & Decorating', 'Security Systems & Surveillance', 'Tiling & Flooring', 'Roofing', 'AC Repair & Maintenance']
        ],
        'Commercial Equipment & Tools' => [
            'icon' => 'fa-industry', 'is_top' => 0, 'order' => 10,
            'subs' => ['Agricultural Equipment & Tools', 'Construction Equipment', 'Office Equipment & Supplies', 'Industrial Equipment', 'Medical & Lab Equipment', 'Restaurant & Catering Equipment', 'Power Tools']
        ],
        'Leisure & Activities' => [
            'icon' => 'fa-running', 'is_top' => 0, 'order' => 11,
            'subs' => ['Sports Equipment', 'Gym & Fitness Equipment', 'Travel & Tourism Deals', 'Events, Catering & Venues', 'Books, Movies & Music', 'Musical Instruments', 'Arts & Crafts']
        ],
        'Babies & Kids' => [
            'icon' => 'fa-baby', 'is_top' => 0, 'order' => 12,
            'subs' => ['Baby Clothes & Shoes', 'Baby Furniture & Gear', 'Toys & Games', 'Baby Care & Health Products', "Children's Bicycles"]
        ],
        'Food, Agriculture & Farming' => [
            'icon' => 'fa-tractor', 'is_top' => 0, 'order' => 13,
            'subs' => ['Food Products & Groceries', 'Farming Equipment & Tools', 'Livestock & Poultry', 'Crops, Seeds & Fertilizers', 'Farm Lands']
        ],
        'Animals & Pets' => [
            'icon' => 'fa-dog', 'is_top' => 0, 'order' => 14,
            'subs' => ['Dogs', 'Cats', 'Birds', 'Fish & Aquariums', 'Rabbits & Small Animals', 'Pet Accessories & Supplies', 'Pet Food', 'Veterinary Services']
        ],
        'Jobs & Employment' => [
            'icon' => 'fa-briefcase', 'is_top' => 0, 'order' => 15,
            'subs' => $jobs_subs
        ],
        'Seeking Work — CVs' => [
            'icon' => 'fa-id-card', 'is_top' => 0, 'order' => 16,
            'subs' => $jobs_subs
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_class, is_top, sort_order) VALUES (?, ?, ?, ?, ?)");
    $sub_stmt = $pdo->prepare("INSERT INTO categories (name, slug, parent_id) VALUES (?, ?, ?)");

    foreach ($categories_data as $name => $data) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $stmt->execute([$name, $slug, $data['icon'], $data['is_top'], $data['order']]);
        $parent_id = $pdo->lastInsertId();

        foreach ($data['subs'] as $sub_name) {
            $sub_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $sub_name . '-' . $name)));
            $sub_stmt->execute([$sub_name, $sub_slug, $parent_id]);
        }
        // Add "Others" subcategory to every main category
        $other_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', 'Others-' . $name)));
        $sub_stmt->execute(['Others', $other_slug, $parent_id]);
    }

    // 2. Seed Nigerian States & LGAs
    $nigeria_data = [
        "Abia" => ["Aba North", "Aba South", "Arochukwu", "Bende", "Ikwuano", "Isiala Ngwa North", "Isiala Ngwa South", "Isuikwuato", "Obingwa", "Ohafia", "Osisioma Ngwa", "Ugwunagbo", "Ukwa East", "Ukwa West", "Umuahia North", "Umuahia South", "Umu Nneochi"],
        "Adamawa" => ["Demsa", "Fufure", "Ganye", "Gayuk", "Gombi", "Grie", "Hong", "Jada", "Lamurde", "Madagali", "Maiha", "Mayo Belwa", "Michika", "Mubi North", "Mubi South", "Numan", "Shelleng", "Song", "Toungo", "Yola North", "Yola South"],
        "Akwa Ibom" => ["Abak", "Eastern Obolo", "Eket", "Esit Eket", "Essien Udim", "Etim Ekpo", "Etinan", "Ibeno", "Ibesikpo Asutan", "Ibiono-Ibom", "Ika", "Ikono", "Ikot Abasi", "Ikot Ekpene", "Ini", "Itu", "Mbo", "Mkpat-Enin", "Nsit-Atai", "Nsit-Ibom", "Nsit-Ubium", "Obot Akara", "Okobo", "Onna", "Oron", "Oruk Anam", "Udung-Uko", "Ukanafun", "Uruan", "Urue-Offong/Oruko", "Uyo"],
        "Anambra" => ["Aguata", "Anambra East", "Anambra West", "Anaocha", "Awka North", "Awka South", "Ayamelum", "Dunukofia", "Ekwusigo", "Idemili North", "Idemili South", "Ihiala", "Njikoka", "Nnewi North", "Nnewi South", "Ogbaru", "Onitsha North", "Onitsha South", "Orumba North", "Orumba South", "Oyi"],
        "Bauchi" => ["Alkaleri", "Bauchi", "Bogoro", "Damban", "Darazo", "Dass", "Gamawa", "Ganjuwa", "Giade", "Itas/Gadau", "Jama'are", "Katagum", "Kirfi", "Misau", "Ningi", "Shira", "Tafawa Balewa", "Toro", "Warji", "Zaki"],
        "Bayelsa" => ["Brass", "Ekeremor", "Kolokuma/Opokuma", "Nembe", "Ogbia", "Sagbama", "Southern Ijaw", "Yenagoa"],
        "Benue" => ["Agatu", "Apa", "Ado", "Buruku", "Gboko", "Guma", "Gwer East", "Gwer West", "Katsina-Ala", "Konshisha", "Kwande", "Logo", "Makurdi", "Obi", "Ogbadibo", "Ohimini", "Oju", "Okpokwu", "Oturkpo", "Tarka", "Ukum", "Ushongo", "Vandeikya"],
        "Borno" => ["Abadam", "Askira/Uba", "Bama", "Bayo", "Biu", "Chibok", "Damboa", "Dikwa", "Gubio", "Guzamala", "Gwoza", "Hawul", "Jere", "Kaga", "Kala/Balge", "Konduga", "Kukawa", "Kwaya Kusar", "Mafa", "Magumeri", "Maiduguri", "Marte", "Mobbar", "Monguno", "Ngala", "Nganzai", "Shani"],
        "Cross River" => ["Abi", "Akamkpa", "Akpabuyo", "Bakassi", "Bekwarra", "Biase", "Boki", "Calabar Municipal", "Calabar South", "Etung", "Ikom", "Obanliku", "Obubra", "Obudu", "Odukpani", "Ogoja", "Yakuur", "Yala"],
        "Delta" => ["Aniocha North", "Aniocha South", "Bomadi", "Burutu", "Ethiope East", "Ethiope West", "Ika North East", "Ika South", "Isoko North", "Isoko South", "Ndokwa East", "Ndokwa West", "Okpe", "Oshimili North", "Oshimili South", "Patani", "Sapele", "Udu", "Ughelli North", "Ughelli South", "Ukwuani", "Uvwie", "Warri North", "Warri South", "Warri South West"],
        "Ebonyi" => ["Abakaliki", "Afikpo North", "Afikpo South", "Ebonyi", "Ezza North", "Ezza South", "Ikwo", "Ishielu", "Ivo", "Izzi", "Ohaozara", "Ohaukwu", "Onicha"],
        "Edo" => ["Akoko-Edo", "Egor", "Esan Central", "Esan North-East", "Esan South-East", "Esan West", "Etsako Central", "Etsako East", "Etsako West", "Igueben", "Ikpoba Okha", "Orhionmwon", "Oredo", "Ovia North-East", "Ovia South-West", "Owan East", "Owan West", "Uhunmwonde"],
        "Ekiti" => ["Ado Ekiti", "Efon", "Ekiti East", "Ekiti South-West", "Ekiti West", "Emure", "Gbonyin", "Ido Osi", "Ijero", "Ikere", "Ikole", "Ilejemeje", "Irepodun/Ifelodun", "Ise/Orun", "Moba", "Oye"],
        "Enugu" => ["Aninri", "Awgu", "Enugu East", "Enugu North", "Enugu South", "Ezeagu", "Igbo Etiti", "Igbo Eze North", "Igbo Eze South", "Isi Uzo", "Nkanu East", "Nkanu West", "Nsukka", "Oji River", "Udenu", "Udi", "Uzo Uwani"],
        "FCT" => ["Abaji", "Bwari", "Gwagwalada", "Kuje", "Kwali", "Municipal Area Council"],
        "Gombe" => ["Akko", "Balanga", "Billiri", "Dukku", "Funakaye", "Gombe", "Kaltungo", "Kwami", "Nafada", "Shongom", "Yamaltu/Deba"],
        "Imo" => ["Aboh Mbaise", "Ahiazu Mbaise", "Ehime Mbano", "Ezinihitte", "Ideato North", "Ideato South", "Ihitte/Uboma", "Ikeduru", "Isiala Mbano", "Isu", "Mbaitoli", "Ngor Okpala", "Njaba", "Nkwerre", "Nwangele", "Obowo", "Oguta", "Ohaji/Egbema", "Okigwe", "Orlu", "Orsu", "Oru East", "Oru West", "Owerri Municipal", "Owerri North", "Owerri South", "Onuimo"],
        "Jigawa" => ["Auyo", "Babura", "Biriniwa", "Birnin Kudu", "Buji", "Dutse", "Gagarawa", "Garki", "Gumel", "Guri", "Gwaram", "Gwiwa", "Hadejia", "Jahun", "Kafin Hausa", "Kazaure", "Kiri Kasama", "Kiyawa", "Kaugama", "Maigatari", "Malam Madori", "Miga", "Ringim", "Roni", "Sule Tankarkar", "Taura", "Yankwashi"],
        "Kaduna" => ["Birnin Gwari", "Chikun", "Giwa", "Igabi", "Ikara", "Jaba", "Jema'a", "Kachia", "Kaduna North", "Kaduna South", "Kagarko", "Kajuru", "Kaura", "Kauru", "Kubau", "Kudan", "Lere", "Makarfi", "Sabon Gari", "Sanga", "Soba", "Zangon Kataf", "Zaria"],
        "Kano" => ["Ajingi", "Albasu", "Bagwai", "Bebeji", "Bichi", "Bunkure", "Dala", "Dambatta", "Dawakin Kudu", "Dawakin Tofa", "Doguwa", "Fagge", "Gabasawa", "Garko", "Garun Mallam", "Gaya", "Gezawa", "Gwale", "Gwarzo", "Kabo", "Kano Municipal", "Karaye", "Kibiya", "Kiru", "Kumbotso", "Kunchi", "Kura", "Madobi", "Makoda", "Minjibir", "Nasarawa", "Rano", "Rimin Gado", "Rogo", "Shanono", "Sumaila", "Takai", "Tarauni", "Tofa", "Tsanyawa", "Tudun Wada", "Ungogo", "Warawa", "Wudil"],
        "Katsina" => ["Bakori", "Batagarawa", "Batsari", "Baure", "Bindawa", "Charanchi", "Dandume", "Danja", "Dan Musa", "Daura", "Dutsi", "Dutsi Ma", "Faskari", "Funtua", "Ingawa", "Jibia", "Kafur", "Kaita", "Kankara", "Kankia", "Katsina", "Kurfi", "Kusada", "Mai'Adua", "Malumfashi", "Mani", "Mashi", "Musawa", "Rimi", "Sabuwa", "Safana", "Sandamu", "Zango"],
        "Kebbi" => ["Aleiro", "Arewa Dandi", "Argungu", "Augie", "Bagudo", "Birnin Kebbi", "Bunza", "Dandi", "Fakai", "Gwandu", "Jega", "Kalgo", "Koko/Besse", "Maiyama", "Ngaski", "Sakaba", "Shanga", "Suru", "Wasagu/Danko", "Yauri", "Zuru"],
        "Kogi" => ["Adavi", "Ajaokuta", "Ankpa", "Bassa", "Dekina", "Ibaji", "Idah", "Igalamela Odolu", "Ijumu", "Kabba/Bunu", "Kogi", "Lokoja", "Mopa Muro", "Ofu", "Ogori/Magongo", "Okehi", "Okene", "Olamaboro", "Omala", "Yagba East", "Yagba West"],
        "Kwara" => ["Asa", "Baruten", "Edu", "Ekiti", "Ifelodun", "Ilorin East", "Ilorin South", "Ilorin West", "Irepodun", "Isin", "Kaiama", "Moro", "Offa", "Oke Ero", "Oyun", "Pategi"],
        "Lagos" => ["Agege", "Ajeromi-Ifelodun", "Alimosho", "Amuwo-Odofin", "Apapa", "Badagry", "Epe", "Eti Osa", "Ibeju-Lekki", "Ifako-Ijaiye", "Ikeja", "Ikorodu", "Kosofe", "Lagos Island", "Lagos Mainland", "Mushin", "Ojo", "Oshodi-Isolo", "Shomolu", "Surulere"],
        "Nasarawa" => ["Akwanga", "Awe", "Doma", "Karu", "Keana", "Keffi", "Kokona", "Lafia", "Nasarawa", "Nasarawa Egon", "Obi", "Toto", "Wamba"],
        "Niger" => ["Agaie", "Agwara", "Bida", "Borgu", "Bosso", "Chanchaga", "Edati", "Gbako", "Gurara", "Katcha", "Kontagora", "Lapai", "Lavun", "Magama", "Mariga", "Mashegu", "Mokwa", "Muya", "Pailoro", "Rafi", "Rijau", "Shiroro", "Suleja", "Tafa", "Wushishi"],
        "Ogun" => ["Abeokuta North", "Abeokuta South", "Ado-Odo/Ota", "Ewekoro", "Ifo", "Ijebu East", "Ijebu North", "Ijebu North East", "Ijebu Ode", "Ikenne", "Imeko Afon", "Ipokia", "Obafemi Owode", "Odeda", "Odogbolu", "Ogun Waterside", "Remo North", "Shagamu", "Yewa North", "Yewa South"],
        "Ondo" => ["Akoko North-East", "Akoko North-West", "Akoko South-West", "Akoko South-East", "Akure North", "Akure South", "Ese Odo", "Idanre", "Ifedore", "Ilaje", "Ile Oluji/Okeigbo", "Irele", "Odigbo", "Okitipupa", "Ondo East", "Ondo West", "Ose", "Owo"],
        "Osun" => ["Atakunmosa East", "Atakunmosa West", "Aiyedaade", "Aiyedaire", "Boluwaduro", "Boripe", "Ede North", "Ede South", "Ife Central", "Ife East", "Ife North", "Ife South", "Egbedore", "Ejigbo", "Ifedayo", "Ifelodun", "Ila", "Ilesa North", "Ilesa South", "Irepodun", "Irewole", "Isokan", "Iwo", "Obokun", "Odo Otin", "Ola Oluwa", "Olorunda", "Oriade", "Orolu", "Osogbo"],
        "Oyo" => ["Afijio", "Akinyele", "Atiba", "Atisbo", "Egbeda", "Ibadan North", "Ibadan North-East", "Ibadan North-West", "Ibadan South-East", "Ibadan South-West", "Ibarapa Central", "Ibarapa East", "Ibarapa North", "Ido", "Irepo", "Iseyin", "Itesiwaju", "Iwajowa", "Kajola", "Lagelu", "Ogbomosho North", "Ogbomosho South", "Ogo Oluwa", "Olorunsogo", "Oluyole", "Ona Ara", "Orelope", "Ori Ire", "Oyo", "Oyo East", "Saki East", "Saki West", "Surulere"],
        "Plateau" => ["Bokkos", "Barkin Ladi", "Bassa", "Jos East", "Jos North", "Jos South", "Kanam", "Kanke", "Langtang North", "Langtang South", "Mangu", "Mikang", "Pankshin", "Qua'an Pan", "Riyom", "Shendam", "Wase"],
        "Rivers" => ["Abua/Odual", "Ahoada East", "Ahoada West", "Akuku-Toru", "Andoni", "Asari-Toru", "Bonny", "Degema", "Eleme", "Emuoha", "Etche", "Gokana", "Ikwerre", "Khana", "Obio/Akpor", "Ogba/Egbema/Ndoni", "Ogu/Bolo", "Okrika", "Omuma", "Opobo/Nkoro", "Oyigbo", "Port Harcourt", "Tai"],
        "Sokoto" => ["Binji", "Bodinga", "Dange Shuni", "Gada", "Goronyo", "Gudu", "Gwadabawa", "Illela", "Isa", "Kebbe", "Kware", "Rabah", "Sabon Birni", "Shagari", "Silame", "Sokoto North", "Sokoto South", "Tambuwal", "Tangaza", "Tureta", "Wamako", "Wurno", "Yabo"],
        "Taraba" => ["Ardo Kola", "Bali", "Donga", "Gashaka", "Gassol", "Ibi", "Jalingo", "Karim Lamido", "Kumi", "Lau", "Sardauna", "Takum", "Ussa", "Wukari", "Yorro", "Zing"],
        "Yobe" => ["Bade", "Bursari", "Damaturu", "Fika", "Fune", "Geidam", "Gujba", "Gulani", "Jakusko", "Karasuwa", "Machina", "Nangere", "Nguru", "Potiskum", "Tarmuwa", "Yunusari", "Yusufari"],
        "Zamfara" => ["Anka", "Bakura", "Birnin Magaji/Kiyaw", "Bukkuyum", "Bungudu", "Gummi", "Gusau", "Kaura Namoda", "Maradun", "Maru", "Shinkafi", "Talata Mafara", "Chafe", "Zurmi"]
    ];

    $state_stmt = $pdo->prepare("INSERT INTO states (name) VALUES (?)");
    $lga_stmt = $pdo->prepare("INSERT INTO lgas (state_id, name) VALUES (?, ?)");

    foreach ($nigeria_data as $state => $lgas) {
        $state_stmt->execute([$state]);
        $state_id = $pdo->lastInsertId();
        foreach ($lgas as $lga) {
            $lga_stmt->execute([$state_id, $lga]);
        }
    }

    // 3. Seed Countries
    $countries = [
        ['Nigeria', 'NG'],
        ['United States', 'US'],
        ['United Kingdom', 'GB'],
        ['Canada', 'CA'],
        ['Ghana', 'GH'],
        ['South Africa', 'ZA'],
        ['Germany', 'DE'],
        ['France', 'FR'],
        ['China', 'CN'],
        ['India', 'IN']
    ];
    $country_stmt = $pdo->prepare("INSERT INTO countries (name, code) VALUES (?, ?)");
    foreach ($countries as $country) {
        $country_stmt->execute($country);
    }

    // 4. Seed Default CMS Pages
    $default_pages = [
        ['Terms & Conditions', 'terms', 'Acceptable use policy...', 'Classifieds terms and conditions', 'terms, conditions, rules'],
        ['Privacy Policy', 'privacy', 'Your data is safe...', 'Our privacy policy', 'privacy, data, safety'],
        ['Billing Policy', 'billing', 'Refunds and payments...', 'Billing and refund policy', 'billing, refund, payment'],
        ['Safety Tips', 'safety', 'Meet in public...', 'Stay safe while buying and selling', 'safety, tips, security'],
        ['FAQ', 'faq', 'Frequently asked questions...', 'Classifieds Help Center', 'faq, help, questions']
    ];
    $page_stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, meta_desc, meta_keys) VALUES (?, ?, ?, ?, ?)");
    foreach ($default_pages as $page) {
        $page_stmt->execute($page);
    }
}
