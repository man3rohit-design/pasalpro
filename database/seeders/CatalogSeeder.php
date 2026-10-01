<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\RecordStockMovement;
use App\Enums\ProductUnit;
use App\Enums\StockTransactionType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class CatalogSeeder extends Seeder
{
    private const SUPPLIERS = [
        'grains' => ['Kalimati Grain Traders', 'Ram Bahadur Shrestha', '01-4271532', 'orders@kalimatigrain.com.np', 'Kalimati, Kathmandu', '601245789'],
        'fmcg'   => ['Himalayan FMCG Distributors Pvt. Ltd.', 'Sabina Joshi', '01-4352210', 'sales@himalayanfmcg.com.np', 'Balaju Industrial Area, Kathmandu', '602318654'],
        'dairy'  => ['Bagmati Dairy Suppliers', 'Krishna Prasad Bhattarai', '01-6612874', 'bagmatidairy@gmail.com', 'Sallaghari, Bhaktapur', '603457812'],
        'drinks' => ['Valley Beverage Distributors', 'Manish Shakya', '01-4256019', 'info@valleybeverage.com.np', 'Teku, Kathmandu', '604129563'],
        'oil'    => ['Annapurna Edible Oil Traders', 'Gita Pandey', '01-4279945', 'annapurnaoil@gmail.com', 'Kuleshwor, Kathmandu', '605873214'],
        'home'   => ['Sagarmatha Home & Personal Care', 'Dipesh Lama', '01-4101876', 'orders@sagarmathahpc.com.np', 'Tripureshwor, Kathmandu', '606548127'],
    ];

    /**
     * Each product row: name, unit, cost price, selling price, shelf life in days, featured.
     */
    private const CATALOG = [
        'Rice, Dal & Grains' => ['grains', 'Rice, lentils, flour and beaten rice for the everyday Nepali kitchen.', [
            ['Daawat Sona Masoori Rice 5 kg', ProductUnit::Packet, 760, 850, null, true],
            ['Sona Mansuli Rice 25 kg', ProductUnit::Packet, 1950, 2200, null, false],
            ['Basmati Rice (Loose)', ProductUnit::Kilogram, 175, 210, null, false],
            ['Masoor Dal (Red Lentil)', ProductUnit::Kilogram, 150, 180, null, false],
            ['Moong Dal', ProductUnit::Kilogram, 190, 230, null, false],
            ['Rahar Dal', ProductUnit::Kilogram, 230, 270, null, false],
            ['Chiura (Beaten Rice)', ProductUnit::Kilogram, 95, 120, null, false],
            ['Aashirvaad Whole Wheat Atta 5 kg', ProductUnit::Packet, 410, 470, null, false],
            ['Sugar (Loose)', ProductUnit::Kilogram, 105, 120, null, false],
        ]],
        'Instant Noodles' => ['fmcg', 'Quick noodles loved across Nepal, from Wai Wai to Rara.', [
            ['Wai Wai Chicken Noodles 75 g', ProductUnit::Packet, 18, 25, 240, true],
            ['Wai Wai Veg Cup Noodles 70 g', ProductUnit::Packet, 18, 25, 240, false],
            ['Rara Noodles 70 g', ProductUnit::Packet, 18, 25, 240, false],
            ['Maggi 2-Minute Noodles 70 g', ProductUnit::Packet, 16, 20, 240, false],
            ['Current Hot & Spicy Noodles 100 g', ProductUnit::Packet, 45, 55, 240, true],
            ['2PM Korean Ramen Noodles 120 g', ProductUnit::Packet, 60, 75, 240, false],
        ]],
        'Biscuits & Snacks' => ['fmcg', 'Biscuits, namkeen and crisps for tea time and tiffin.', [
            ['Coconut Crunchy Biscuit 115 g', ProductUnit::Packet, 32, 40, 180, false],
            ['Marie Biscuit 120 g', ProductUnit::Packet, 20, 25, 180, false],
            ['Parle-G Biscuit 130 g', ProductUnit::Packet, 16, 20, 180, false],
            ['Kurkure Masala Munch 90 g', ProductUnit::Packet, 45, 55, 120, false],
            ['Lays Classic Salted 52 g', ProductUnit::Packet, 45, 55, 120, false],
            ["Haldiram's Navrattan 200 g", ProductUnit::Packet, 90, 110, 150, true],
            ['Peanut Chikki 100 g', ProductUnit::Packet, 40, 50, 90, false],
        ]],
        'Dairy & Eggs' => ['dairy', 'Milk, curd, paneer, butter and eggs restocked every morning.', [
            ['Amul Taaza Toned Milk 500 ml', ProductUnit::Packet, 48, 55, 3, true],
            ['Gokul Pasteurised Cow Milk 500 ml', ProductUnit::Packet, 48, 55, 3, false],
            ['Amul Masti Dahi 400 g', ProductUnit::Packet, 75, 90, 7, false],
            ['Fresh Paneer 200 g', ProductUnit::Packet, 160, 190, 10, false],
            ['Amul Butter 100 g', ProductUnit::Packet, 120, 140, 60, false],
            ['Farm Fresh Eggs (Tray of 30)', ProductUnit::Box, 480, 540, 14, true],
        ]],
        'Beverages' => ['drinks', 'Soft drinks, juices, energy drinks and bottled water.', [
            ['Coca-Cola 1.5 L', ProductUnit::Bottle, 120, 150, 180, false],
            ['Fanta 500 ml', ProductUnit::Bottle, 50, 65, 180, false],
            ['Real Mixed Fruit Juice 1 L', ProductUnit::Packet, 230, 275, 240, true],
            ['Frooti Mango Drink 250 ml', ProductUnit::Packet, 22, 30, 180, false],
            ['Bisleri Mineral Water 1 L', ProductUnit::Bottle, 18, 25, 365, false],
            ['Red Bull 250 ml', ProductUnit::Piece, 140, 175, 365, false],
        ]],
        'Tea & Coffee' => ['drinks', 'Black tea, Darjeeling leaf tea and instant coffee.', [
            ['Brooke Bond Red Label Tea 500 g', ProductUnit::Packet, 280, 330, 540, true],
            ['Wagh Bakri Premium Tea 500 g', ProductUnit::Packet, 260, 310, 540, false],
            ['Duncans Darjeeling Tea 100 g', ProductUnit::Box, 380, 450, 540, false],
            ['Nescafe Classic 50 g', ProductUnit::Bottle, 320, 375, 540, false],
        ]],
        'Cooking Oil & Ghee' => ['oil', 'Mustard oil, soybean oil and pure ghee.', [
            ['Dhara Mustard Oil 1 L', ProductUnit::Bottle, 320, 365, 365, true],
            ['Fortune Soyabean Oil 1 L', ProductUnit::Packet, 240, 275, 365, false],
            ['Sunflower Oil 5 L', ProductUnit::Bottle, 1350, 1550, 365, false],
            ['Amul Cow Ghee 1 L', ProductUnit::Packet, 1150, 1300, 270, false],
        ]],
        'Spices & Masala' => ['grains', 'Masala, seeds and Himalayan spices.', [
            ['Everest Sabji Masala 100 g', ProductUnit::Packet, 95, 115, 365, false],
            ['Everest Garam Masala 100 g', ProductUnit::Packet, 100, 120, 365, false],
            ['Turmeric Powder 200 g', ProductUnit::Packet, 70, 85, 365, false],
            ['Jeera (Cumin Seeds) 100 g', ProductUnit::Packet, 75, 95, 365, false],
            ['Timur ko Chhop 200 g', ProductUnit::Bottle, 160, 200, 365, false],
            ['Tata Salt 1 kg', ProductUnit::Packet, 22, 25, null, false],
        ]],
        'Personal Care' => ['home', 'Soap, toothpaste, shampoo and daily essentials.', [
            ['Lifebuoy Soap 100 g', ProductUnit::Piece, 45, 55, null, false],
            ['Dettol Original Soap 75 g', ProductUnit::Piece, 60, 75, null, false],
            ['Colgate Strong Teeth 200 g', ProductUnit::Piece, 175, 205, null, false],
            ['Sunsilk Shampoo 340 ml', ProductUnit::Bottle, 330, 385, null, false],
            ['Pepsodent Toothbrush', ProductUnit::Piece, 50, 65, null, false],
            ['Himalaya Neem Face Wash 100 ml', ProductUnit::Piece, 230, 270, null, false],
        ]],
        'Household Cleaning' => ['home', 'Detergents, dishwash and cleaners.', [
            ['Surf Excel Easy Wash 1 kg', ProductUnit::Packet, 290, 340, null, false],
            ['Ariel Matic Powder 1 kg', ProductUnit::Packet, 420, 480, null, false],
            ['Vim Dishwash Bar 200 g', ProductUnit::Piece, 35, 45, null, false],
            ['Harpic Toilet Cleaner 500 ml', ProductUnit::Bottle, 170, 200, null, false],
            ['Lizol Floor Cleaner 500 ml', ProductUnit::Bottle, 220, 260, null, false],
            ['Good Knight Gold Flash Refill', ProductUnit::Box, 125, 150, null, false],
        ]],
    ];

    public function run(RecordStockMovement $recordStockMovement): void
    {
        $suppliers = collect(self::SUPPLIERS)->map(fn (array $supplier): Supplier => Supplier::create([
            'name'           => $supplier[0],
            'contact_person' => $supplier[1],
            'phone'          => $supplier[2],
            'email'          => $supplier[3],
            'address'        => $supplier[4],
            'pan_number'     => $supplier[5],
        ]));

        foreach (self::CATALOG as $categoryName => [$supplierKey, $description, $products]) {
            $category = Category::create([
                'name'        => $categoryName,
                'slug'        => str($categoryName)->slug(),
                'description' => $description,
            ]);

            foreach ($products as $index => [$name, $unit, $costPrice, $sellingPrice, $shelfLifeDays, $isFeatured]) {
                $slug = str($name)->slug()->toString();

                $product = $category->products()->create([
                    'supplier_id'   => $suppliers[$supplierKey]->id,
                    'name'          => $name,
                    'slug'          => $slug,
                    'sku'           => str($categoryName)->substr(0, 3)->upper().'-'.str((string) ($category->id * 100 + $index + 1))->padLeft(4, '0'),
                    'barcode'       => Product::generateBarcode(),
                    'description'   => sprintf('%s from our %s range.', $name, $categoryName),
                    'unit'          => $unit,
                    'cost_price'    => $costPrice,
                    'selling_price' => $sellingPrice,
                    'expiry_date'   => $shelfLifeDays ? today()->addDays(DatabaseSeeder::HISTORY_DAYS + $shelfLifeDays) : null,
                    'image_path'    => $this->storePhoto($slug),
                    'is_featured'   => $isFeatured,
                ]);

                $reorderLevel = $sellingPrice < 100 ? 24 : ($sellingPrice < 500 ? 12 : 6);

                $product->inventory->update(['reorder_level' => $reorderLevel]);

                $recordStockMovement->handle(
                    $product,
                    $reorderLevel * fake()->numberBetween(4, 7),
                    StockTransactionType::OpeningStock,
                    note: 'Opening stock count',
                );
            }
        }
    }

    private function storePhoto(string $slug): ?string
    {
        $photo = database_path(sprintf('seeders/images/products/%s.jpg', $slug));

        if (! file_exists($photo)) {
            return null;
        }

        return Storage::disk('public')->putFileAs('products', new File($photo), $slug.'.jpg') ?: null;
    }
}
