# PHPWeave v2.3.0 Roadmap

**Target Release Date:** Q1 2026

This document outlines planned features and improvements for PHPWeave v2.3.0, the next major release following the successful v2.2.0 launch.

---

## 🎯 Release Goals

PHPWeave v2.3.0 will focus on:
1. **Developer Experience** - Tools to make development faster and easier
2. **Advanced Database Features** - Query builder, seeding, factories
3. **Performance Enhancements** - Cache improvements, optimization tools
4. **Testing Tools** - Built-in testing framework integration

---

## 🚀 Planned Features

### 1. Query Builder (High Priority)

**Status:** Planned
**Effort:** 3-4 weeks
**Priority:** High

A fluent, database-agnostic query builder for cleaner, safer database queries.

**Features:**
- Fluent interface for building queries
- Database-agnostic (works with MySQL, PostgreSQL, SQLite, SQL Server)
- Automatic parameter binding (SQL injection protection)
- Support for joins, subqueries, unions
- Aggregation functions (COUNT, SUM, AVG, etc.)
- Transaction support
- Raw query support when needed

**Example Usage:**
```php
// In models
class user_model extends DBConnection {
    use QueryBuilder;

    function getActiveUsers() {
        return $this->table('users')
            ->where('is_active', 1)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
    }

    function getUserWithPosts($userId) {
        return $this->table('users')
            ->select('users.*', 'posts.title', 'posts.created_at as post_date')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->where('users.id', $userId)
            ->get();
    }

    function getUserStats() {
        return $this->table('users')
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }
}
```

**Technical Details:**
- Trait-based implementation for easy integration
- Builds parameterized queries internally
- Translates to appropriate SQL for each database driver
- Chainable methods for fluent syntax
- Returns arrays or objects based on configuration

**Files to Create:**
- `coreapp/querybuilder.php` - Main query builder trait
- `docs/QUERY_BUILDER.md` - Complete documentation
- `tests/test_query_builder.php` - Comprehensive tests

---

### 2. Database Seeding System (High Priority)

**Status:** Planned
**Effort:** 2-3 weeks
**Priority:** High

A structured way to populate databases with test/demo data, separate from migrations.

**Features:**
- Seed files for repeatable data insertion
- Factory pattern for generating test data
- Truncate tables before seeding (optional)
- Relationship-aware seeding
- CLI tool for running seeders
- Environment-specific seeds (dev, staging, production)

**Example Usage:**

**Seeder File:**
```php
// seeders/UserSeeder.php
class UserSeeder extends Seeder {
    public function run() {
        // Truncate table
        $this->truncate('users');

        // Insert specific records
        $this->insert('users', [
            ['email' => 'admin@example.com', 'name' => 'Admin', 'role' => 'admin'],
            ['email' => 'user@example.com', 'name' => 'User', 'role' => 'user']
        ]);

        // Use factory for bulk data
        UserFactory::create(50);
    }
}
```

**Factory File:**
```php
// factories/UserFactory.php
class UserFactory extends Factory {
    protected $table = 'users';

    public function definition() {
        return [
            'email' => $this->faker->email(),
            'name' => $this->faker->name(),
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }

    public function admin() {
        return $this->state(['role' => 'admin']);
    }
}

// Usage
UserFactory::create(10); // 10 random users
UserFactory::admin()->create(2); // 2 admin users
```

**CLI Commands:**
```bash
php seed.php run                    # Run all seeders
php seed.php run UserSeeder         # Run specific seeder
php seed.php run --class=UserSeeder # Alternative syntax
php seed.php fresh                  # Migrate fresh + seed
```

**Files to Create:**
- `coreapp/seeder.php` - Base seeder class
- `coreapp/factory.php` - Factory base class with Faker integration
- `seed.php` - CLI tool for seeding
- `seeders/` - Directory for seeder files
- `factories/` - Directory for factory files
- `docs/SEEDING.md` - Complete documentation

**Dependencies:**
- `fzaninotto/faker` or `fakerphp/faker` (optional, for fake data generation)

---

### 3. ~~Middleware System~~ → **Middleware-Style Hooks (✅ COMPLETED in v2.3.0)**

**Status:** ✅ **COMPLETED** - Released in v2.3.0 (2025-11-03)
**Effort:** 2 weeks (actual)
**Priority:** High (upgraded from Medium)

PHPWeave v2.3.0 implemented **middleware-style hooks** instead of traditional middleware, providing similar functionality while maintaining the framework's philosophy of simplicity.

**Implemented Features:**
- ✅ Class-based hooks (reusable, testable)
- ✅ Route-specific hooks via `->hook()` method
- ✅ Route groups with shared hooks via `Route::group()`
- ✅ Nested groups with cumulative hooks
- ✅ Hook parameter passing
- ✅ 100% backward compatible with callback hooks

**Implementation:**
```php
// Register class-based hooks
Hook::registerClass('auth', AuthHook::class);
Hook::registerClass('admin', AdminHook::class);

// Route-specific hooks
Route::get('/profile', 'User@profile')->hook('auth');
Route::get('/admin', 'Admin@index')->hook(['auth', 'admin']);

// Route groups
Route::group(['hooks' => ['auth']], function() {
    Route::get('/dashboard', 'Dashboard@index');
    Route::get('/profile', 'User@profile');
});

// With prefix
Route::group(['prefix' => '/admin', 'hooks' => ['auth', 'admin']], function() {
    Route::get('/users', 'Admin@users'); // /admin/users
});
```

**Built-in Hook Classes (v2.3.0):**
- ✅ `AuthHook` - Authentication check with redirect
- ✅ `AdminHook` - Admin authorization with 403
- ✅ `LogHook` - Request logging
- ✅ `RateLimitHook` - Rate limiting (APCu/session)
- ✅ `CorsHook` - CORS headers

**Files Created:**
- ✅ `coreapp/hooks.php` - Enhanced with class-based hooks
- ✅ `coreapp/router.php` - Added Route::group() and ->hook()
- ✅ `hooks/classes/` - Five production-ready hook classes
- ✅ `hooks/example_class_based_hooks.php` - Registration examples
- ✅ `docs/HOOKS.md` - Enhanced with 300+ lines of middleware docs
- ✅ `docs/MIGRATION_TO_V2.3.0.md` - Complete migration guide
- ✅ `tests/test_enhanced_hooks.php` - 14 comprehensive tests

**Why Hooks Instead of Middleware?**

PHPWeave chose to enhance its existing hooks system rather than create a separate middleware layer:

1. **Simpler architecture** - No need for Request/Response objects
2. **Lighter weight** - Fewer abstractions and dependencies
3. **More flexible** - Works with existing hook ecosystem
4. **Backward compatible** - All existing hooks still work
5. **Same benefits** - Route-specific, reusable, testable code

This approach gives developers middleware-like functionality while staying true to PHPWeave's "zero dependencies, maximum performance" philosophy.

---

### 4. Request & Response Objects (Medium Priority)

**Status:** Planned
**Effort:** 1-2 weeks
**Priority:** Medium

Modern request/response handling with OOP interface.

**Features:**
- Type-safe request data access
- Input validation
- File upload handling
- JSON response helpers
- Cookie management
- Header manipulation

**Example Usage:**
```php
class User extends Controller {
    public function store(Request $request) {
        // Validate input
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|min:3',
            'password' => 'required|min:8'
        ]);

        // Access input
        $email = $request->input('email');
        $name = $request->get('name', 'Guest'); // with default

        // Check if field exists
        if ($request->has('phone')) {
            // ...
        }

        // File uploads
        $file = $request->file('avatar');
        if ($file->isValid()) {
            $file->move('uploads/', $file->getName());
        }

        // Return JSON response
        return Response::json([
            'success' => true,
            'user' => $user
        ], 201);

        // Or redirect
        return Response::redirect('/dashboard')
            ->with('success', 'User created!');
    }
}
```

**Files to Create:**
- `coreapp/request.php` - Request class
- `coreapp/response.php` - Response class
- `coreapp/validator.php` - Validation class
- `docs/REQUEST_RESPONSE.md` - Documentation

---

### 5. Model Events & Observers (Medium Priority)

**Status:** Planned
**Effort:** 1-2 weeks
**Priority:** Medium

Event lifecycle hooks for models (creating, created, updating, updated, deleting, deleted).

**Example Usage:**
```php
class user_model extends DBConnection {
    use HasEvents;

    protected static function boot() {
        // Before creating
        static::creating(function($data) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            return $data;
        });

        // After created
        static::created(function($user) {
            // Send welcome email
            WelcomeEmailJob::dispatch($user);
        });

        // Before deleting
        static::deleting(function($userId) {
            // Delete related records
            DB::table('posts')->where('user_id', $userId)->delete();
        });
    }
}

// Or use Observer pattern
class UserObserver {
    public function creating($data) {
        $data['uuid'] = generateUuid();
        return $data;
    }

    public function created($user) {
        Log::info("User created: " . $user['email']);
    }
}

// Register observer
user_model::observe(UserObserver::class);
```

---

### 6. Caching Layer (High Priority)

**Status:** Planned
**Effort:** 2-3 weeks
**Priority:** High

Unified caching interface with multiple drivers.

**Features:**
- Multiple cache drivers (APCu, File, Redis, Memcached)
- Cache tags for grouped invalidation
- Cache-aside pattern helpers
- Remember/forget API
- Time-to-live (TTL) support

**Example Usage:**
```php
// Simple cache
Cache::put('key', 'value', 3600); // 1 hour
$value = Cache::get('key');
Cache::forget('key');

// Remember pattern
$users = Cache::remember('users.all', 3600, function() {
    return DB::table('users')->get();
});

// Cache tags (for selective clearing)
Cache::tags(['users', 'posts'])->put('user.1.posts', $posts, 3600);
Cache::tags(['posts'])->flush(); // Clear all post-related cache

// In models
class user_model extends DBConnection {
    use Cacheable;

    public function getUser($id) {
        return $this->cache("user.{$id}", 3600, function() use ($id) {
            $sql = "SELECT * FROM users WHERE id = :id";
            $stmt = $this->executePreparedSQL($sql, ['id' => $id]);
            return $this->fetch($stmt);
        });
    }
}
```

**Configuration:**
```ini
# .env
CACHE_DRIVER=apcu        # apcu, file, redis, memcached
CACHE_PREFIX=phpweave_
CACHE_TTL=3600

# For Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
```

**Files to Create:**
- `coreapp/cache.php` - Cache manager
- `coreapp/cache/drivers/` - Cache drivers
- `docs/CACHING.md` - Documentation

---

### 7. CLI Console & Artisan-like Commands (Medium Priority)

**Status:** Planned
**Effort:** 2 weeks
**Priority:** Medium

Unified CLI framework for creating custom commands.

**Features:**
- Command registration
- Argument/option parsing
- Interactive prompts
- Progress bars
- Color output
- Scheduled commands (cron integration)

**Example Usage:**

**Custom Command:**
```php
// commands/SendNewsletterCommand.php
class SendNewsletterCommand extends Command {
    protected $signature = 'newsletter:send {--dry-run}';
    protected $description = 'Send newsletter to all subscribers';

    public function handle() {
        $dryRun = $this->option('dry-run');

        $this->info('Starting newsletter send...');

        $subscribers = DB::table('subscribers')->where('active', 1)->get();
        $bar = $this->progressBar(count($subscribers));

        foreach ($subscribers as $subscriber) {
            if (!$dryRun) {
                // Send email
                $this->sendEmail($subscriber);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->success('Newsletter sent to ' . count($subscribers) . ' subscribers!');
    }
}
```

**Running Commands:**
```bash
php phpweave make:controller UserController
php phpweave make:model User
php phpweave make:migration create_users_table
php phpweave make:seeder UserSeeder
php phpweave newsletter:send
php phpweave newsletter:send --dry-run
php phpweave schedule:run  # For cron jobs
```

**Files to Create:**
- `coreapp/console/command.php` - Base command class
- `coreapp/console/kernel.php` - Command kernel
- `phpweave` - Main CLI entry point
- `commands/` - Directory for custom commands
- `docs/CONSOLE.md` - Documentation

---

### 8. API Resources & Collections (Medium Priority)

**Status:** Planned
**Effort:** 1-2 weeks
**Priority:** Medium

Transform models/data into JSON API responses with consistent formatting.

**Example Usage:**
```php
// resources/UserResource.php
class UserResource extends Resource {
    public function toArray() {
        return [
            'id' => $this->data['id'],
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'created_at' => $this->data['created_at'],
            // Don't expose password
            'posts_count' => $this->getPostsCount(),
        ];
    }

    private function getPostsCount() {
        // Additional computed field
        return DB::table('posts')->where('user_id', $this->data['id'])->count();
    }
}

// In controller
class UserController extends Controller {
    public function show($id) {
        $user = DB::table('users')->where('id', $id)->first();
        return UserResource::make($user)->toJson();
    }

    public function index() {
        $users = DB::table('users')->get();
        return UserResource::collection($users)->toJson();
    }
}
```

---

### 9. Environment Configuration Enhancement (Low Priority)

**Status:** Planned
**Effort:** 1 week
**Priority:** Low

Better environment management and configuration.

**Features:**
- Multiple environment files (.env.local, .env.production)
- Configuration caching
- Type-safe config access
- Environment detection

**Example Usage:**
```php
// config/app.php (new config files)
return [
    'name' => env('APP_NAME', 'PHPWeave'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
];

// Access config
Config::get('app.name');
Config::set('app.debug', true);

// Cache config for production
php phpweave config:cache
```

---

### 10. Testing Framework Integration (Medium Priority)

**Status:** Planned
**Effort:** 2-3 weeks
**Priority:** Medium

Built-in testing helpers and PHPUnit integration.

**Features:**
- Database transactions for tests
- HTTP testing helpers
- Factory integration for tests
- Mock helpers
- Code coverage reports

**Example Usage:**
```php
// tests/UserTest.php
class UserTest extends TestCase {
    use RefreshDatabase;

    public function testUserCreation() {
        $user = UserFactory::create([
            'email' => 'test@example.com'
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }

    public function testLoginEndpoint() {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
```

**Running Tests:**
```bash
php phpweave test
php phpweave test --coverage
php phpweave test tests/UserTest.php
```

---

## 📊 Feature Priority Matrix

| Feature | Priority | Effort | Impact | Status |
|---------|----------|--------|--------|--------|
| Query Builder | High | 3-4 weeks | High | Planned |
| Database Seeding | High | 2-3 weeks | High | Planned |
| Caching Layer | High | 2-3 weeks | High | Planned |
| Middleware System | Medium | 2 weeks | Medium | Planned |
| Request/Response | Medium | 1-2 weeks | Medium | Planned |
| Model Events | Medium | 1-2 weeks | Medium | Planned |
| CLI Console | Medium | 2 weeks | Medium | Planned |
| API Resources | Medium | 1-2 weeks | Low | Planned |
| Testing Framework | Medium | 2-3 weeks | Medium | Planned |
| Config Enhancement | Low | 1 week | Low | Planned |

---

## 🗓️ Development Timeline

### Phase 1: Core Database Features (Weeks 1-7)
- Query Builder (Weeks 1-4)
- Database Seeding & Factories (Weeks 5-7)

### Phase 2: Request Handling (Weeks 8-11)
- Middleware System (Weeks 8-9)
- Request/Response Objects (Weeks 10-11)

### Phase 3: Performance & Caching (Weeks 12-14)
- Caching Layer (Weeks 12-14)

### Phase 4: Developer Tools (Weeks 15-18)
- CLI Console Framework (Weeks 15-16)
- Testing Framework (Weeks 17-18)

### Phase 5: Polish & Release (Weeks 19-20)
- Model Events & Observers
- API Resources
- Config Enhancements
- Documentation finalization
- Beta testing

**Total Timeline:** ~20 weeks (5 months)

---

## 🔧 Breaking Changes

**None Planned** - v2.3.0 will maintain 100% backward compatibility with v2.2.0.

All new features will be opt-in:
- Query Builder is a trait (opt-in)
- Middleware is optional
- Seeding is separate from migrations
- Request/Response objects can coexist with traditional PHP superglobals

---

## 📚 Documentation Plan

Each feature will include:
- ✅ Comprehensive guide (500+ lines)
- ✅ Quick start examples
- ✅ API reference
- ✅ Best practices
- ✅ Migration guide (if applicable)
- ✅ Video tutorial (optional)

---

## 🧪 Testing Requirements

All features must have:
- ✅ Unit tests (>80% coverage)
- ✅ Integration tests
- ✅ Real-world usage examples
- ✅ Performance benchmarks

---

## 🚢 Release Criteria

Before v2.3.0 release:
- ✅ All planned features implemented
- ✅ Documentation complete
- ✅ Tests passing (100%)
- ✅ Beta testing period (2 weeks)
- ✅ Migration guide from v2.2.0
- ✅ Performance benchmarks vs v2.2.0

---

## 💡 Community Input

We welcome feedback on this roadmap! Please:
- Open GitHub issues for feature requests
- Comment on existing roadmap items
- Vote on features you'd like to see prioritized

**Feedback Channels:**
- GitHub Issues: https://github.com/clintcan/PHPWeave/issues
- Discussions: https://github.com/clintcan/PHPWeave/discussions

---

## 📈 Success Metrics

v2.3.0 will be considered successful if:
- ✅ 90%+ backward compatibility maintained
- ✅ <5% performance regression (preferably improvement)
- ✅ All tests passing
- ✅ Documentation coverage >95%
- ✅ Community adoption >70% within 3 months

---

**Last Updated:** October 2025
**Version:** Draft 1.0
**Author:** PHPWeave Development Team
