<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Restaurant;
use App\Entity\Review;
use App\Entity\User;
use App\Entity\Table;
use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;
use App\Entity\Menu;
use App\Entity\MenuItem;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@restaurant.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setFirstName('Pierre');
        $admin->setLastName('Dubois');
        $manager->persist($admin);

        // Create regular users
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->email);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setFirstName($faker->firstName);
            $user->setLastName($faker->lastName);
            $manager->persist($user);
            $users[] = $user;
        }

        // French restaurant categories with descriptions
        $categoriesData = [
            'Gastronomique' => 'Cuisine raffinée et créative, service d\'exception',
            'Bistrot' => 'Cuisine traditionnelle française dans une ambiance conviviale',
            'Brasserie' => 'Grands classiques français servis toute la journée',
            'Méditerranéen' => 'Saveurs du sud et de la mer Méditerranée',
            'Bio & Local' => 'Cuisine moderne privilégiant les produits locaux et biologiques'
        ];

        // Create categories
        $categories = [];
        foreach ($categoriesData as $name => $description) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription($description);
            $manager->persist($category);
            $categories[] = $category;
        }

        // French restaurant names and descriptions
        $restaurantNames = [
            'Le Petit Bistrot',
            'La Table d\'Or',
            'Chez Marcel',
            'L\'Assiette Gourmande',
            'Le Gastronome',
            'La Belle Époque',
            'Le Coq Hardy',
            'L\'Ardoise',
            'La Brasserie Parisienne',
            'Le Café des Arts'
        ];

        // Create restaurants with French names
        $restaurants = [];
        foreach ($restaurantNames as $i => $name) {
            $restaurant = new Restaurant();
            $restaurant->setName($name);
            $restaurant->setDescription(sprintf(
                "Situé au cœur de %s, %s vous propose une cuisine %s dans un cadre %s. Notre chef %s %s sublime les produits du terroir avec passion.",
                $faker->city,
                $name,
                ['raffinée', 'authentique', 'traditionnelle', 'moderne', 'créative'][$i % 5],
                ['chaleureux', 'élégant', 'convivial', 'contemporain', 'intimiste'][$i % 5],
                $faker->firstName,
                $faker->lastName
            ));
            $restaurant->setAddress($faker->streetAddress . ', ' . $faker->postcode . ' ' . $faker->city);
            $restaurant->setPhone('0' . $faker->numberBetween(1, 5) . $faker->randomNumber(8, true));
            $restaurant->setEmail(strtolower(str_replace(' ', '.', $name)) . '@restaurant.fr');
            $restaurant->addCategory($faker->randomElement($categories));
            $manager->persist($restaurant);
            $restaurants[] = $restaurant;

            // Create tables for each restaurant
            $tableStatuses = ['Libre', 'Réservée', 'En service', 'Nettoyage'];
            for ($t = 1; $t <= 10; $t++) {
                $table = new Table();
                $table->setNumber($t);
                $table->setCapacity($faker->numberBetween(2, 8));
                $table->setRestaurant($restaurant);
                $table->setStatus($faker->randomElement($tableStatuses));
                $table->setActive(true);
                $manager->persist($table);
            }

            // Define French menu items by category
            $menuItems = [
                'Entrées' => [
                    ['name' => 'Soupe à l\'oignon gratinée', 'description' => 'Soupe traditionnelle avec croûtons et fromage gratiné', 'price' => [8, 12]],
                    ['name' => 'Salade de chèvre chaud', 'description' => 'Salade verte, toasts de chèvre, miel et noix', 'price' => [9, 14]],
                    ['name' => 'Foie gras maison', 'description' => 'Foie gras mi-cuit, chutney de figues et pain toasté', 'price' => [15, 20]],
                    ['name' => 'Escargots de Bourgogne', 'description' => 'Six escargots au beurre persillé', 'price' => [12, 16]],
                    ['name' => 'Tartare de saumon', 'description' => 'Saumon frais, avocat et agrumes', 'price' => [11, 15]]
                ],
                'Plats principaux' => [
                    ['name' => 'Coq au vin', 'description' => 'Volaille mijotée au vin rouge, lardons et champignons', 'price' => [18, 25]],
                    ['name' => 'Magret de canard', 'description' => 'Magret rôti, sauce au miel et romarin', 'price' => [22, 28]],
                    ['name' => 'Boeuf bourguignon', 'description' => 'Boeuf mijoté au vin rouge et légumes', 'price' => [19, 26]],
                    ['name' => 'Blanquette de veau', 'description' => 'Veau mijoté, sauce crémeuse aux champignons', 'price' => [20, 27]],
                    ['name' => 'Sole meunière', 'description' => 'Sole fraîche, beurre citronné et pommes vapeur', 'price' => [24, 30]]
                ],
                'Desserts' => [
                    ['name' => 'Crème brûlée', 'description' => 'Crème vanillée caramélisée', 'price' => [7, 10]],
                    ['name' => 'Tarte Tatin', 'description' => 'Tarte aux pommes caramélisées, crème fraîche', 'price' => [8, 11]],
                    ['name' => 'Mousse au chocolat', 'description' => 'Mousse légère au chocolat noir', 'price' => [7, 9]],
                    ['name' => 'Profiteroles', 'description' => 'Choux, glace vanille, sauce chocolat chaud', 'price' => [9, 12]],
                    ['name' => 'Paris-Brest', 'description' => 'Pâte à choux, crème pralinée', 'price' => [8, 11]]
                ],
                'Boissons' => [
                    ['name' => 'Vin rouge - Bordeaux', 'description' => 'Château Margaux (75cl)', 'price' => [35, 45]],
                    ['name' => 'Vin blanc - Chablis', 'description' => 'Domaine William Fèvre (75cl)', 'price' => [30, 40]],
                    ['name' => 'Champagne', 'description' => 'Moët & Chandon (75cl)', 'price' => [60, 80]],
                    ['name' => 'Eau minérale', 'description' => 'Evian ou Badoit (75cl)', 'price' => [4, 6]],
                    ['name' => 'Café gourmand', 'description' => 'Expresso et mignardises', 'price' => [8, 10]]
                ]
            ];

            // Create menu items for each restaurant
            $menuItemsByCategory = [];
            foreach ($menuItems as $category => $items) {
                $menuItemsByCategory[$category] = [];
                foreach ($faker->randomElements($items, $faker->numberBetween(3, count($items))) as $item) {
                    $menuItem = new MenuItem();
                    $menuItem->setName($item['name']);
                    $menuItem->setDescription($item['description']);
                    $menuItem->setPrice($faker->randomFloat(2, $item['price'][0], $item['price'][1]));
                    $menuItem->setRestaurant($restaurant);
                    $menuItem->setAvailable(true);
                    $manager->persist($menuItem);
                    $menuItemsByCategory[$category][] = $menuItem;
                }
            }

            // Create menus with French names
            $menuTemplates = [
                'Menu Dégustation' => 'Une sélection raffinée de nos meilleures spécialités',
                'Menu du Marché' => 'Inspiré des produits frais du jour',
                'Menu du Chef' => 'Les créations spéciales de notre chef',
                'Menu Traditionnel' => 'Les grands classiques de la cuisine française',
                'Menu Découverte' => 'Une initiation aux saveurs de notre terroir',
                'Menu Gastronomique' => 'Une expérience culinaire d\'exception',
                'Menu Bistrot' => 'Les classiques de la cuisine française',
                'Menu du Midi' => 'Formula express pour le déjeuner'
            ];

            foreach ($menuTemplates as $menuName => $menuDesc) {
                $menu = new Menu();
                $menu->setName($menuName);
                $menu->setDescription($menuDesc);
                $menu->setPrice($faker->randomFloat(2, 35, 85));
                $menu->setCategory('Menus');
                $menu->setRestaurant($restaurant);
                $menu->setActive(true);

                // Add items from different categories
                foreach ($menuItemsByCategory as $category => $items) {
                    $menu->addMenuItem($faker->randomElement($items));
                }

                $manager->persist($menu);
            }
        }

        // Create reviews with French comments
        $reviewComments = [
            'Une expérience gastronomique exceptionnelle',
            'Service impeccable et cuisine délicieuse',
            'Cadre chaleureux et plats savoureux',
            'Rapport qualité-prix excellent',
            'Une belle découverte culinaire',
            'À recommander sans hésitation',
            'Cuisine authentique et généreuse',
            'Personnel attentionné et professionnel'
        ];

        foreach ($restaurants as $restaurant) {
            $numReviews = $faker->numberBetween(0, 10);
            for ($i = 0; $i < $numReviews; $i++) {
                $review = new Review();
                $review->setRating($faker->numberBetween(1, 5));
                $review->setComment($faker->randomElement($reviewComments) . '. ' . $faker->sentence());
                $review->setCreatedAt($faker->dateTimeThisYear());
                $review->setRestaurant($restaurant);
                $review->setIsApproved($faker->boolean(70)); // 70% chance of being approved

                // Randomly assign either a user or just an email
                if ($faker->boolean(80)) { // 80% chance of having a user
                    $review->setUser($faker->randomElement($users));
                } else {
                    $review->setCustomerEmail($faker->email);
                }

                $manager->persist($review);
            }
        }

        // Create reservations
        $statuses = [ReservationStatus::PENDING, ReservationStatus::CONFIRMED, ReservationStatus::CANCELLED];
        $statusLabels = [
            'En attente',
            'Confirmée',
            'Annulée'
        ];
        
        foreach ($users as $user) {
            $numReservations = $faker->numberBetween(0, 5);
            for ($i = 0; $i < $numReservations; $i++) {
                $reservation = new Reservation();
                $restaurant = $faker->randomElement($restaurants);
                
                $reservation->setRestaurant($restaurant);
                $reservation->setDateTime($faker->dateTimeBetween('now', '+2 months'));
                $reservation->setNumberOfGuests($faker->numberBetween(1, 8));
                $reservation->setStatus($faker->randomElement($statuses));
                $reservation->setCreatedAt($faker->dateTimeThisYear());
                $reservation->setCustomerFirstName($faker->firstName);
                $reservation->setCustomerLastName($faker->lastName);
                $reservation->setCustomerEmail($user->getEmail());
                $reservation->setCustomerPhone($faker->phoneNumber);
                
                // Randomly assign a table from the restaurant
                $tables = $restaurant->getTables();
                if (count($tables) > 0) {
                    $reservation->setRestaurantTable($tables[array_rand($tables->toArray())]);
                }
                
                $manager->persist($reservation);
            }
        }

        $manager->flush();
    }
}
