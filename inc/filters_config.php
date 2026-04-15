<?php
/**
 * Category-Specific Filters Configuration - Enhanced based on Jiji Blueprint
 */

function get_category_filters($cat_name) {
    $cat_name = strtoupper($cat_name);

    $filters = [];

    // Vehicles / Cars
    if (strpos($cat_name, 'VEHICLES') !== false || strpos($cat_name, 'CARS') !== false) {
        $filters = [
            'make' => [
                'label' => 'Make (Brand)',
                'type' => 'select',
                'options' => ['Toyota', 'Mercedes-Benz', 'Lexus', 'Honda', 'Hyundai', 'Acura', 'Audi', 'BMW', 'BYD', 'Bentley', 'Cadillac', 'Changan', 'Chevrolet', 'Chrysler', 'Dodge', 'Ford', 'GAC', 'Geely', 'GMC', 'Infiniti', 'Isuzu', 'IVM', 'JAC', 'Jaguar', 'Jeep', 'Jetour', 'Kia', 'Lamborghini', 'Land Rover', 'Lincoln', 'Maserati', 'Mazda', 'Mini', 'Mitsubishi', 'Nissan', 'Opel', 'Peugeot', 'Pontiac', 'Porsche', 'Rolls-Royce', 'Subaru', 'Tesla', 'Volkswagen', 'Volvo', 'XPeng'],
                'quick' => true
            ],
            'year' => [
                'label' => 'Year of Manufacture',
                'type' => 'number',
                'ranges' => [
                    ['2022', '2026'], ['2017', '2021'], ['2012', '2016'], ['2007', '2011'], ['2002', '2006'], ['1997', '2001'], ['1992', '1996'], ['1900', '1987']
                ]
            ],
            'condition' => [
                'label' => 'Condition',
                'type' => 'select',
                'options' => ['Brand New', 'Foreign Used', 'Local Used']
            ],
            'transmission' => [
                'label' => 'Transmission',
                'type' => 'select',
                'options' => ['AMT', 'Automatic', 'CVT', 'Manual']
            ],
            'mileage' => [
                'label' => 'Mileage (km)',
                'type' => 'number'
            ],
            'registered' => [
                'label' => 'Registered Car',
                'type' => 'select',
                'options' => ['Yes', 'No']
            ],
            'body_type' => [
                'label' => 'Body Type',
                'type' => 'select',
                'options' => ['SUV', 'Sedan', 'Hatchback', 'Coupe', 'Convertible', 'Pickup', 'Minivan', 'Station Wagon', 'Van', 'Truck', 'Wagon', 'Panel Van', 'Crossover', 'Convertible Coupe']
            ],
            'second_condition' => [
                'label' => 'Second Condition',
                'type' => 'select',
                'options' => ['No faults', 'Original parts', 'Unpainted', 'First owner', 'First registration', 'After crash', 'Engine issue', 'Gear issue', 'Need body repair', 'Need repainting', 'Need repair', 'Wiring problems']
            ],
            'color' => [
                'label' => 'Color',
                'type' => 'select',
                'options' => ['Black', 'Blue', 'Gray', 'Silver', 'White', 'Beige', 'Brown', 'Burgundy', 'Gold', 'Green', 'Ivory', 'Maroon', 'Matt Black', 'Off white', 'Orange', 'Pearl', 'Pink', 'Purple', 'Red', 'Teal', 'Yellow', 'Other']
            ],
            'engine_size' => [
                'label' => 'Engine Size (cc)',
                'type' => 'number'
            ],
            'powertrain' => [
                'label' => 'Powertrain',
                'type' => 'select',
                'options' => ['Internal Combustion', 'Hybrid', 'Electric', 'Fuel Plug-in Hybrid']
            ],
            'fuel_type' => [
                'label' => 'Fuel Type',
                'type' => 'select',
                'options' => ['Petrol', 'Diesel', 'Hybrid', 'Mild Hybrid', 'Electric', 'CNG', 'Plug-in Hybrid']
            ],
            'exchange' => [
                'label' => 'Exchange Possible',
                'type' => 'select',
                'options' => ['Yes', 'No']
            ]
        ];
    }
    // Electronics
    elseif (strpos($cat_name, 'ELECTRONICS') !== false || strpos($cat_name, 'LAPTOPS') !== false || strpos($cat_name, 'TV') !== false) {
        $filters = [
            'condition' => [
                'label' => 'Condition',
                'type' => 'select',
                'options' => ['Brand New', 'Foreign Used', 'Local Used']
            ],
            'brand' => [
                'label' => 'Brand',
                'type' => 'select',
                'options' => ['Dell', 'HP', 'Lenovo', 'Apple', 'Sony', 'LG', 'Samsung', 'Acer', 'Asus', 'Toshiba', 'Panasonic', 'Hisense', 'TCL']
            ],
            'screen_size' => [
                'label' => 'Screen Size (inches)',
                'type' => 'number'
            ],
            'storage' => [
                'label' => 'Storage',
                'type' => 'select',
                'options' => ['128GB SSD', '256GB SSD', '512GB SSD', '1TB SSD', '500GB HDD', '1TB HDD']
            ]
        ];
    }
    // Property
    elseif (strpos($cat_name, 'PROPERTY') !== false || strpos($cat_name, 'HOUSES') !== false || strpos($cat_name, 'LAND') !== false) {
        $filters = [
            'property_type' => [
                'label' => 'Property Type',
                'type' => 'select',
                'options' => ['Land', 'Residential', 'Commercial', 'Industrial']
            ],
            'transaction_type' => [
                'label' => 'Transaction Type',
                'type' => 'select',
                'options' => ['For Sale (Outright)', 'For Rent (per annum)', 'For Rent (per month)', 'Short Let (per day)']
            ],
            'bedrooms' => [
                'label' => 'Bedrooms',
                'type' => 'select',
                'options' => ['1', '2', '3', '4+']
            ],
            'size' => [
                'label' => 'Size (sqm)',
                'type' => 'number'
            ],
            'verified_seller' => [
                'label' => 'Verified Sellers Only',
                'type' => 'select',
                'options' => ['Yes', 'No']
            ],
            'trusted_agent' => [
                'label' => 'Trusted Real Estate Agent',
                'type' => 'select',
                'options' => ['Yes', 'No']
            ]
        ];
    }
    // Phones
    elseif (strpos($cat_name, 'PHONES') !== false || strpos($cat_name, 'TABLETS') !== false) {
        $filters = [
            'brand' => [
                'label' => 'Brand',
                'type' => 'select',
                'options' => ['Apple', 'Samsung', 'Tecno', 'Infinix', 'Itel', 'Nokia', 'Xiaomi', 'Huawei', 'OnePlus', 'Oppo', 'Vivo', 'Google', 'HTC', 'Sony', 'Motorola']
            ],
            'storage' => [
                'label' => 'Storage',
                'type' => 'select',
                'options' => ['16GB', '32GB', '64GB', '128GB', '256GB', '512GB', '1TB']
            ],
            'ram' => [
                'label' => 'RAM',
                'type' => 'select',
                'options' => ['1GB', '2GB', '3GB', '4GB', '6GB', '8GB', '12GB', '16GB']
            ],
            'network' => [
                'label' => 'Network',
                'type' => 'select',
                'options' => ['2G', '3G', '4G', '5G']
            ]
        ];
    }

    return $filters;
}
