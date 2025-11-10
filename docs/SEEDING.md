# Database Seeding Guide

**Version:** 2.4.0
**Status:** ✅ Production Ready
**Last Updated:** 2025-11-10

A complete database seeding system for PHPWeave that provides a structured way to populate databases with test and demo data, separate from migrations.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Quick Start](#quick-start)
3. [Installation](#installation)
4. [Creating Seeders](#creating-seeders)
5. [Creating Factories](#creating-factories)
6. [Running Seeders](#running-seeders)
7. [Seeder Methods](#seeder-methods)
8. [Factory Methods](#factory-methods)
9. [Best Practices](#best-practices)
10. [Complete Examples](#complete-examples)
11. [Faker Integration](#faker-integration)
12. [FAQ](#faq)

---

## Introduction

Database seeding allows you to populate your database with test or demo data in a structured, repeatable way. Unlike migrations which handle schema changes, seeders focus on data population.

### Why Use Seeders?

- **Repeatable** - Run the same seeds multiple times
- **Environment-specific** - Different data for development, staging, production
- **Testing** - Populate test databases quickly
- **Demo data** - Show off your application with realistic data
- **Team consistency** - Everyone gets the same test data

### Seeders vs Migrations

| Feature | Migrations | Seeders |
|---------|-----------|---------|
| Purpose | Schema changes | Data population |
| When to run | Once per environment | Repeatedly as needed |
| Reversible | Yes (rollback) | No (truncate & re-seed) |
| Production use | Always | Sometimes |

---

## Quick Start

### 1. Create a Seeder

```php
<?php
// seeders/UserSeeder.php
class UserSeeder extends Seeder {
    public function run() {
        $this->insert('users', [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'created_at' => $this->now()
            ]
        ]);
    }
}
```

### 2. Run the Seeder

```bash
php seed.php run UserSeeder
```

That's it! Your database now has test data.

---

## Installation

Database seeding is included in PHPWeave 2.4.0+. No installation needed.

### Requirements

- PHPWeave 2.0.0+
- PHP 7.4+
- Database connection configured

### Optional: Install Faker

For realistic fake data:

```bash
composer require fakerphp/faker
```

**Note:** Faker is optional. PHPWeave includes a built-in faker with common methods.

---

## Creating Seeders

### Basic Seeder

```php
<?php
// seeders/ProductSeeder.php
class ProductSeeder extends Seeder {
    public function run() {
        $this->output("Seeding products...");

        $this->insert('products', [
            ['name' => 'Laptop', 'price' => 999.99, 'stock' => 50],
            ['name' => 'Mouse', 'price' => 29.99, 'stock' => 200],
            ['name' => 'Keyboard', 'price' => 79.99, 'stock' => 150]
        ]);

        $this->output("Products seeded!");
    }
}
```

### Seeder with Truncate

```php
class UserSeeder extends Seeder {
    public function run() {
        // Clear existing data first
        $this->truncate('users');

        // Then insert fresh data
        $this->insert('users', $data);
    }
}
```

### Calling Other Seeders

```php
class DatabaseSeeder extends Seeder {
    public function run() {
        $this->call(UserSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(OrderSeeder::class);
    }
}
```

### Environment-Specific Seeding

```php
class UserSeeder extends Seeder {
    public function run() {
        if ($this->environment('development')) {
            // Seed 100 test users for development
            UserFactory::new()->create(100);
        } elseif ($this->environment('production')) {
            // Only seed admin user for production
            $this->insert('users', [
                [
                    'name' => 'Admin',
                    'email' => 'admin@example.com',
                    'role' => 'admin'
                ]
            ]);
        }
    }
}
```

Set environment in `.env`:
```ini
APP_ENV=development
```

---

## Creating Factories

### Basic Factory

```php
<?php
// factories/UserFactory.php
class UserFactory extends Factory {
    protected $table = 'users';

    public function definition() {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'created_at' => $this->now()
        ];
    }
}
```

### Using the Factory

```php
// Create one user
UserFactory::new()->create();

// Create multiple users
UserFactory::new()->create(10);

// Create with specific attributes
UserFactory::new()->create(['email' => 'test@example.com']);
```

### Factory States

```php
class UserFactory extends Factory {
    protected $table = 'users';

    public function definition() {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'role' => 'user',
            'status' => 'active'
        ];
    }

    public function admin() {
        return $this->state(['role' => 'admin']);
    }

    public function inactive() {
        return $this->state(['status' => 'inactive']);
    }

    public function verified() {
        return $this->state([
            'email_verified_at' => $this->now()
        ]);
    }
}
```

**Usage:**
```php
// Create admin user
UserFactory::new()->admin()->create();

// Create inactive user
UserFactory::new()->inactive()->create();

// Create verified admin user
UserFactory::new()->admin()->verified()->create();

// Create 5 admin users
UserFactory::new()->admin()->create(5);
```

### Factory Sequences

```php
class UserFactory extends Factory {
    public function definition() {
        return [
            'name' => 'User ' . $this->sequence(),
            'email' => 'user' . $this->sequence() . '@example.com'
        ];
    }
}

// Creates: user1@example.com, user2@example.com, user3@example.com
UserFactory::new()->create(3);
```

### After Create Callbacks

```php
UserFactory::new()
    ->afterCreating(function($user) {
        // Create related profile
        ProfileFactory::new()->create(['user_id' => $user['id']]);
    })
    ->create(10);
```

---

## Running Seeders

### Run All Seeders

```bash
php seed.php run
```

### Run Specific Seeder

```bash
php seed.php run UserSeeder
```

Or:
```bash
php seed.php run --class=UserSeeder
```

### Fresh Migration + Seed

Rollback all migrations, re-run them, then seed:

```bash
php seed.php fresh
```

**⚠️ Warning:** This destroys all data!

### List Available Seeders

```bash
php seed.php list
```

---

## Seeder Methods

### Data Insertion

#### insert()
```php
$this->insert('users', [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com']
]);
```

#### truncate()
```php
$this->truncate('users'); // Clear table and reset auto-increment
```

#### delete()
```php
$this->delete('users'); // Delete all records (keeps auto-increment)
```

#### execute()
```php
$this->execute("UPDATE users SET status = :status", ['status' => 'active']);
```

### Seeder Organization

#### call()
```php
$this->call(UserSeeder::class);
$this->call(ProductSeeder::class);
```

### Transactions

```php
$this->beginTransaction();

try {
    $this->insert('users', $userData);
    $this->insert('profiles', $profileData);
    $this->commit();
} catch (Exception $e) {
    $this->rollback();
    throw $e;
}
```

### Utilities

#### now()
```php
$timestamp = $this->now(); // Y-m-d H:i:s
$date = $this->now('Y-m-d'); // Custom format
```

#### randomString()
```php
$code = $this->randomString(10); // Random 10-char string
```

#### randomEmail()
```php
$email = $this->randomEmail(); // random@example.com
$email = $this->randomEmail('test.com'); // random@test.com
```

#### randomNumber()
```php
$num = $this->randomNumber(1, 100); // Random number 1-100
```

#### environment()
```php
if ($this->environment('development')) {
    // Development-only seeding
}
```

### Access Database

#### getConnection()
```php
$db = $this->getConnection();
$stmt = $db->executePreparedSQL($sql, $params);
```

#### query()
```php
// If Query Builder available
$users = $this->query()->table('users')->where('active', 1)->get();
```

### Factory Access

#### factory()
```php
$this->factory(UserFactory::class)->create(10);
```

---

## Factory Methods

### Creation

#### create()
```php
// Create one
$user = UserFactory::new()->create();

// Create many
$users = UserFactory::new()->create(10);

// Create with attributes
$user = UserFactory::new()->create(['email' => 'test@example.com']);
```

#### make()
```php
// Make without saving
$userData = UserFactory::new()->make();

// Make many
$usersData = UserFactory::new()->make(10);
```

### States

#### state()
```php
UserFactory::new()
    ->state(['role' => 'admin'])
    ->create();
```

### Callbacks

#### afterCreating()
```php
UserFactory::new()
    ->afterCreating(function($user) {
        // Do something after creation
    })
    ->create();
```

### Utilities

#### sequence()
```php
protected function definition() {
    return [
        'name' => 'User ' . $this->sequence()
    ];
}
```

#### now()
```php
'created_at' => $this->now()
```

#### faker
```php
'name' => $this->faker->name()
'email' => $this->faker->email()
'text' => $this->faker->paragraph()
```

---

## Best Practices

### 1. Use DatabaseSeeder as Entry Point

```php
// seeders/DatabaseSeeder.php
class DatabaseSeeder extends Seeder {
    public function run() {
        $this->call(UserSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(OrderSeeder::class);
    }
}
```

Then run:
```bash
php seed.php run DatabaseSeeder
```

### 2. Truncate Before Inserting

```php
public function run() {
    $this->truncate('users'); // Clear old data
    $this->insert('users', $newData); // Insert fresh data
}
```

### 3. Use Factories for Large Datasets

```php
// ❌ Slow - one insert per user
for ($i = 0; $i < 100; $i++) {
    $this->insert('users', [['name' => 'User ' . $i]]);
}

// ✅ Fast - uses factory
UserFactory::new()->create(100);
```

### 4. Seed Related Data Together

```php
public function run() {
    $userId = $this->insert('users', [...])[0];

    $this->insert('profiles', [
        ['user_id' => $userId, 'bio' => '...']
    ]);

    $this->insert('settings', [
        ['user_id' => $userId, 'theme' => 'dark']
    ]);
}
```

### 5. Use Transactions for Consistency

```php
public function run() {
    $this->beginTransaction();

    try {
        $this->call(UserSeeder::class);
        $this->call(ProductSeeder::class);
        $this->commit();
    } catch (Exception $e) {
        $this->rollback();
        throw $e;
    }
}
```

### 6. Environment-Aware Seeding

```php
public function run() {
    if ($this->environment('production')) {
        // Minimal data for production
        $this->insert('users', [/* admin only */]);
    } else {
        // Lots of test data for development
        UserFactory::new()->create(100);
    }
}
```

### 7. Document Your Seeders

```php
/**
 * User Seeder
 *
 * Seeds the users table with:
 * - 1 admin user (admin@example.com)
 * - 10 regular users (using factory)
 *
 * Run: php seed.php run UserSeeder
 */
class UserSeeder extends Seeder {
    // ...
}
```

---

## Complete Examples

### E-commerce Database Seeding

```php
<?php
// seeders/DatabaseSeeder.php
class DatabaseSeeder extends Seeder {
    public function run() {
        $this->output("Seeding e-commerce database...");

        // Truncate all tables
        $this->truncate('orders');
        $this->truncate('products');
        $this->truncate('users');

        // Seed in dependency order
        $this->call(UserSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(OrderSeeder::class);

        $this->output("E-commerce database seeded successfully!");
    }
}
```

```php
<?php
// seeders/UserSeeder.php
class UserSeeder extends Seeder {
    public function run() {
        // Create admin
        $this->insert('users', [[
            'name' => 'Admin',
            'email' => 'admin@shop.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => $this->now()
        ]]);

        // Create test customers
        UserFactory::new()->create(50);
    }
}
```

```php
<?php
// seeders/ProductSeeder.php
class ProductSeeder extends Seeder {
    public function run() {
        $categories = ['Electronics', 'Clothing', 'Books', 'Home'];

        foreach ($categories as $category) {
            ProductFactory::new()
                ->state(['category' => $category])
                ->create(10);
        }
    }
}
```

```php
<?php
// seeders/OrderSeeder.php
class OrderSeeder extends Seeder {
    public function run() {
        $users = $this->query()->table('users')
            ->where('role', 'user')
            ->pluck('id');

        foreach ($users as $userId) {
            // Create 1-5 orders per user
            OrderFactory::new()
                ->state(['user_id' => $userId])
                ->create(rand(1, 5));
        }
    }
}
```

### Blog Platform Seeding

```php
<?php
// seeders/BlogSeeder.php
class BlogSeeder extends Seeder {
    public function run() {
        $this->beginTransaction();

        try {
            // Create authors
            $authorIds = [];
            for ($i = 0; $i < 5; $i++) {
                $author = UserFactory::new()
                    ->state(['role' => 'author'])
                    ->create();
                $authorIds[] = $author['id'];
            }

            // Create posts for each author
            foreach ($authorIds as $authorId) {
                PostFactory::new()
                    ->state(['author_id' => $authorId])
                    ->afterCreating(function($post) {
                        // Add 3-10 comments to each post
                        CommentFactory::new()
                            ->state(['post_id' => $post['id']])
                            ->create(rand(3, 10));
                    })
                    ->create(rand(5, 15));
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
```

---

## Faker Integration

### With Faker Library

Install Faker:
```bash
composer require fakerphp/faker
```

```php
class UserFactory extends Factory {
    public function definition() {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'country' => $this->faker->country,
            'bio' => $this->faker->paragraph,
            'website' => $this->faker->url,
            'birthday' => $this->faker->dateTimeBetween('-50 years', '-18 years'),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }
}
```

### Without Faker (Built-in)

PHPWeave includes a built-in faker with common methods:

```php
class UserFactory extends Factory {
    public function definition() {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'text' => $this->faker->paragraph(),
            'number' => $this->faker->numberBetween(1, 100),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'boolean' => $this->faker->boolean(),
            'uuid' => $this->faker->uuid()
        ];
    }
}
```

### Built-in Faker Methods

- `name()` - Random name
- `email()` - Random email
- `userName()` - Random username
- `text($length)` - Random text
- `sentence()` - Random sentence
- `paragraph()` - Random paragraph
- `word()` - Random word
- `words($count)` - Array of random words
- `randomNumber($min, $max)` - Random number
- `boolean()` - Random boolean
- `date()` - Random date
- `dateTime()` - Random datetime
- `dateTimeBetween($start, $end)` - Random date in range
- `url()` - Random URL
- `uuid()` - Random UUID
- `randomElement($array)` - Random array element
- `randomString($length)` - Random string

---

## FAQ

**Q: When should I use seeders vs migrations?**
A: Migrations for schema changes, seeders for data population. Migrations run once per environment, seeders can run repeatedly.

**Q: Do I need Faker?**
A: No. PHPWeave includes a built-in faker. Install Faker for more realistic data.

**Q: Can I seed production databases?**
A: Yes, but be careful. Use environment checks to avoid seeding test data in production.

**Q: How do I reset my database?**
A: Run `php seed.php fresh` to rollback migrations, re-migrate, and seed.

**Q: Can seeders use Query Builder?**
A: Yes! If Query Builder is loaded, seeders can access it via `$this->query()`.

**Q: How do I seed relationships?**
A: Use factory callbacks or seed related data in the same seeder:

```php
UserFactory::new()
    ->afterCreating(function($user) {
        ProfileFactory::new()->create(['user_id' => $user['id']]);
    })
    ->create(10);
```

**Q: Can I use seeders in tests?**
A: Absolutely! Seeders are perfect for populating test databases.

**Q: How do I prevent duplicate seeding?**
A: Use `truncate()` before inserting, or check if data exists first.

**Q: Can factories use other factories?**
A: Yes! Call factories from within other factories' `afterCreating()` callback.

---

## Support

**Documentation:**
- Main Guide: `docs/SEEDING.md` (this file)
- Example Seeders: `seeders/`
- Example Factories: `factories/`

**Issues:**
- GitHub Issues: https://github.com/clintcan/PHPWeave/issues
- Discussions: https://github.com/clintcan/PHPWeave/discussions

---

**PHPWeave Database Seeding - Structured Data, Every Time** 🌱

*Last Updated: 2025-11-10*
*Version: 2.4.0*
