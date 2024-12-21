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
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
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

        // Create categories
        $categories = [];
        $categoryNames = ['Italien', 'Japonais', 'Français', 'Indien', 'Mexicain'];
        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription($faker->paragraph());
            $manager->persist($category);
            $categories[] = $category;
        }

        // Create restaurants
        $restaurants = [];
        for ($i = 0; $i < 20; $i++) {
            $restaurant = new Restaurant();
            $restaurant->setName($faker->company);
            $restaurant->setDescription($faker->paragraphs(3, true));
            $restaurant->setAddress($faker->address);
            $restaurant->setPhone($faker->phoneNumber);
            $restaurant->setEmail($faker->companyEmail);
            $restaurant->addCategory($faker->randomElement($categories));
            $manager->persist($restaurant);
            $restaurants[] = $restaurant;

            // Create tables for each restaurant
            for ($t = 1; $t <= 10; $t++) {
                $table = new Table();
                $table->setNumber($t);
                $table->setCapacity($faker->numberBetween(2, 8));
                $table->setRestaurant($restaurant);
                $table->setActive(true);  // Add this line
                $manager->persist($table);
            }
        }

        // Create reviews
        foreach ($restaurants as $restaurant) {
            $numReviews = $faker->numberBetween(0, 10);
            for ($i = 0; $i < $numReviews; $i++) {
                $review = new Review();
                $review->setRating($faker->numberBetween(1, 5));
                $review->setComment($faker->paragraph());
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
