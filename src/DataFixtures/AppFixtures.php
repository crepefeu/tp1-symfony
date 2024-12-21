<?php
// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Restaurant;
use App\Entity\Category;
use App\Entity\Table;
use App\Entity\Menu;
use App\Entity\MenuItem;
use App\Entity\Review;
use App\Entity\Reservation;
use App\Entity\Employee;
use App\Entity\Discount;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\LoyaltyTransaction;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // Categories
        $categories = [];
        $categoryNames = ['Italien', 'Français', 'Japonais', 'Mexicain', 'Indien'];

        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription("Cuisine $name authentique");
            $manager->persist($category);
            $categories[] = $category;
        }

        // Users
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPhone('0123456789');
        $admin->setVerified(true);
        $admin->setLoyaltyPoints(100);
        $manager->persist($admin);

        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $user->setFirstName('Normal');
        $user->setLastName('User');
        $user->setPhone('0987654321');
        $user->setVerified(true);
        $user->setLoyaltyPoints(50);
        $manager->persist($user);

        $transactions = [
            [
                'user' => $user,
                'points' => 50,
                'description' => 'Points de bienvenue',
            ],
            [
                'user' => $user,
                'points' => 10,
                'description' => 'Réservation au restaurant La Belle Époque',
            ],
            [
                'user' => $admin,
                'points' => 100,
                'description' => 'Points administrateur',
            ]
        ];

        foreach ($transactions as $transactionData) {
            $transaction = new LoyaltyTransaction();
            $transaction->setUser($transactionData['user']);
            $transaction->setPoints($transactionData['points']);
            $transaction->setDescription($transactionData['description']);
            $manager->persist($transaction);
        }

        // Restaurants with full data
        $restaurantsData = [
            [
                'name' => 'La Belle Époque',
                'description' => 'Restaurant français traditionnel avec une touche moderne',
                'address' => '123 rue de Paris',
                'phone' => '0123456789',
                'email' => 'contact@belleepoque.fr',
                'openingHours' => "Lundi-Vendredi: 12h-14h30, 19h-22h30\nSamedi: 19h-23h\nDimanche: Fermé",
                'category' => $categories[1]
            ],
            [
                'name' => 'Sakura',
                'description' => 'Authentique cuisine japonaise',
                'address' => '45 avenue des Sushi',
                'phone' => '0123456790',
                'email' => 'contact@sakura.fr',
                'openingHours' => "Mardi-Dimanche: 12h-14h, 19h-22h\nLundi: Fermé",
                'category' => $categories[2]
            ],
            [
                'name' => 'Bella Italia',
                'description' => 'Les meilleures pizzas de la ville',
                'address' => '78 rue de Rome',
                'phone' => '0123456791',
                'email' => 'contact@bellaitalia.fr',
                'openingHours' => "Tous les jours: 12h-23h",
                'category' => $categories[0]
            ]
        ];

        foreach ($restaurantsData as $restaurantData) {
            $restaurant = new Restaurant();
            $restaurant->setName($restaurantData['name']);
            $restaurant->setDescription($restaurantData['description']);
            $restaurant->setAddress($restaurantData['address']);
            $restaurant->setPhone($restaurantData['phone']);
            $restaurant->setEmail($restaurantData['email']);
            $restaurant->setOpeningHours($restaurantData['openingHours']);
            $restaurant->addCategory($restaurantData['category']);
            $restaurant->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($restaurant);

            // Tables
            for ($i = 1; $i <= 5; $i++) {
                $table = new Table();
                $table->setNumber($i);
                $table->setCapacity($i % 2 == 0 ? 4 : 2);
                $table->setStatus('available');
                $table->setActive(true);
                $table->setRestaurant($restaurant);
                $manager->persist($table);
            }

            // Menu
            $menu = new Menu();
            $menu->setName('Menu du jour');
            $menu->setDescription('Notre sélection quotidienne');
            $menu->setPrice('35.00');
            $menu->setCategory('daily');
            $menu->setActive(true);
            $menu->setRestaurant($restaurant);
            $manager->persist($menu);

            // Menu items
            $items = [
                ['name' => 'Entrée du jour', 'description' => 'Selon le marché', 'price' => '12.00'],
                ['name' => 'Plat principal', 'description' => 'Spécialité du chef', 'price' => '25.00'],
                ['name' => 'Dessert maison', 'description' => 'Fait maison', 'price' => '8.00']
            ];

            foreach ($items as $itemData) {
                $menuItem = new MenuItem();
                $menuItem->setName($itemData['name']);
                $menuItem->setDescription($itemData['description']);
                $menuItem->setPrice($itemData['price']);
                $menuItem->setAvailable(true);
                $menuItem->setRestaurant($restaurant);
                $manager->persist($menuItem);
            }

            // Reviews
            $comments = [
                'Excellent repas ! Service impeccable.',
                'Une très belle découverte, je recommande.',
                'Cuisine authentique et cadre agréable.'
            ];

            for ($i = 0; $i < 3; $i++) {
                $review = new Review();
                $review->setRating(random_int(3, 5));
                $review->setComment($comments[$i]);
                $review->setRestaurant($restaurant);
                $review->setCreatedAt(new \DateTime());
                $manager->persist($review);
            }

            // Discount
            $discount = new Discount();
            $discount->setCode('WELCOME20_' . strtoupper(substr($restaurant->getName(), 0, 3)));
            $discount->setDescription('20% de réduction sur votre première commande');
            $discount->setDiscount('20.00');
            $discount->setStartDate(new \DateTime());
            $discount->setEndDate((new \DateTime())->modify('+30 days'));
            $discount->setRestaurant($restaurant);
            $manager->persist($discount);

            // Employee
            $employee = new Employee();
            $employee->setFirstName('John');
            $employee->setLastName('Doe');
            $employee->setEmail(sprintf('employee@%s.fr', strtolower($restaurant->getName())));
            $employee->setPhone('0612345678');
            $employee->setPosition('Manager');
            $employee->setRestaurant($restaurant);
            $manager->persist($employee);
        }

        $manager->flush();
    }
}
