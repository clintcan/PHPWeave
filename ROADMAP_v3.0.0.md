# PHPWeave v3.0.0 Roadmap

**Target Release Date:** Q2 2026
**Current Version:** v2.5.0 (Released November 2025)

**Philosophy:** Stay true to PHPWeave's roots - **simplicity first, power when needed**

---

## 🎯 Core Philosophy

PHPWeave was born from a commitment to **simplicity**:

> "A lightweight, homegrown PHP MVC framework born from simplicity and evolved with modern routing"

As we plan v3.0.0, we're recommitting to this philosophy:

### **Guiding Principles**

1. **Simplicity Over Features** - We say "no" to features that add unnecessary complexity
2. **Opt-In Everything** - Core stays minimal, features are additive
3. **Zero Dependencies** - Pure PHP, Composer optional
4. **Easy Learning Curve** - Learn PHPWeave in hours, not days
5. **Lightweight Always** - Minimal footprint, maximum performance
6. **Backward Compatible** - Never break existing code

### **What PHPWeave Is NOT**

- ❌ Not trying to be Laravel (we're simpler)
- ❌ Not trying to be Symfony (we're lighter)
- ❌ Not trying to be full-stack (we're focused)
- ❌ Not adding features for the sake of features

### **PHPWeave's Sweet Spot**

```
CodeIgniter 3     PHPWeave 2.5          Laravel 11
(Too basic) ──────► (Just right) ◄──────── (Too complex)
```

**Perfect for:**
- ✅ Small to medium projects (1-10 developers)
- ✅ Developers who value simplicity
- ✅ Teams that don't need enterprise features
- ✅ APIs and microservices
- ✅ Rapid prototyping

---

## 📊 Completed Features (v2.3.0 - v2.5.0)

### ✅ v2.3.0 (Released Nov 3, 2025)
- **Middleware-Style Hooks** - Route-specific hooks (still simple!)
- **Hot-Path Optimizations** - 7-12ms per request saved

### ✅ v2.4.0 (Released Nov 10, 2025)
- **Query Builder** - Fluent queries (opt-in trait, still allows raw SQL)
- **Database Seeding** - Test data generation (dev tool only)

### ✅ v2.5.0 (Released Nov 12, 2025)
- **Advanced Caching Layer** - Multi-tier caching (simple API: `Cache::get()`)
- **Cache Dashboard** - Real-time monitoring (opt-in)

**All features remain opt-in and don't add complexity to the core!** ✅

---

## 🚀 Planned Features for v3.0.0

After careful consideration, we're **drastically reducing** the scope of v3.0.0 to focus on **high-impact, simple features only**.

### **REMOVED from Roadmap** ❌

These features were deemed too complex or low-impact for PHPWeave's philosophy:

1. ❌ **Model Events & Observers** - Adds unnecessary abstraction
2. ❌ **API Resources & Collections** - Can use arrays/custom helpers instead
3. ❌ **Config Enhancement** - Current .env system is simple and works great
4. ❌ **Full Request/Response Objects** - Too much abstraction

### **KEPT - High Impact, Simple Features** ✅

---

## Feature #1: Simple Request Validation (High Priority)

**Status:** Planned
**Effort:** 1 week
**Priority:** High
**Complexity:** 🟢 Low

Instead of full Request/Response objects, we're adding **just the validation layer** - the most valuable part without the complexity.

**What We're Adding:**
- Simple validation helpers
- Common validation rules
- Clear error messages
- No new abstractions (still use $_POST, $_GET)

**Example Usage:**

```php
class UserController extends Controller {
    public function store() {
        // Simple validation - no new objects!
        $errors = Validator::validate($_POST, [
            'email' => 'required|email|unique:users',
            'name' => 'required|min:3',
            'password' => 'required|min:8'
        ]);

        if ($errors) {
            // Handle errors
            $this->show('register', ['errors' => $errors]);
            return;
        }

        // Validation passed - data is safe
        $user = $this->table('users')->insert([
            'email' => $_POST['email'],
            'name' => $_POST['name'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
        ]);

        header('Location: /dashboard');
    }
}
```

**Available Rules:**
- `required`, `optional`, `nullable`
- `email`, `url`, `ip`
- `min:n`, `max:n`, `between:min,max`
- `numeric`, `integer`, `string`, `boolean`
- `unique:table,column`, `exists:table,column`
- `confirmed` (password confirmation)
- `in:foo,bar`, `regex:/pattern/`

**Why This is Simple:**
- ✅ No new Request/Response objects
- ✅ Still use $_POST/$_GET (familiar!)
- ✅ Just adds validation convenience
- ✅ Opt-in (don't have to use it)
- ✅ Easy to understand and test

**Files to Create:**
- `coreapp/validator.php` - Simple validator class (~300 lines)
- `docs/VALIDATION.md` - Validation guide (~200 lines)
- `tests/test_validator.php` - Test suite

**Benefits:**
- ✅ Cleaner, safer user input handling
- ✅ Consistent validation across app
- ✅ No abstraction complexity
- ✅ 1-hour learning curve

---

## Feature #2: CLI Generators (High Priority)

**Status:** Planned
**Effort:** 2 weeks
**Priority:** High
**Complexity:** 🟢 Low (generators only, no complex console framework)

**What We're Adding:**
- Simple code generators via CLI
- **NOT** a full console framework (that's too complex)
- Just file creation helpers

**Example Usage:**

```bash
# Generate controller
php phpweave make:controller UserController
# Creates: controller/UserController.php with basic template

# Generate model
php phpweave make:model User
# Creates: models/user_model.php with QueryBuilder trait

# Generate migration
php phpweave make:migration create_users_table
# Creates: migrations/2025_11_12_create_users_table.php

# Generate seeder
php phpweave make:seeder UserSeeder
# Creates: seeders/UserSeeder.php

# Generate hook
php phpweave make:hook AuthHook
# Creates: hooks/classes/AuthHook.php

# Generate job
php phpweave make:job SendEmailJob
# Creates: jobs/SendEmailJob.php

# That's it! No complex command framework, just generators.
```

**What We're NOT Adding:**
- ❌ Custom command creation (too complex)
- ❌ Interactive prompts (adds complexity)
- ❌ Progress bars, colors (nice-to-have, not essential)
- ❌ Task scheduling (use cron directly)
- ❌ Command kernel/dispatcher (over-engineering)

**Why This is Simple:**
- ✅ Just file templates + simple CLI
- ✅ No new abstractions
- ✅ Saves time without complexity
- ✅ Easy to maintain

**Implementation:**
- Single `phpweave` file (~500 lines)
- Simple argument parsing (no complex library)
- File templates in `templates/` directory
- That's it!

**Files to Create:**
- `phpweave` - Simple CLI script (~500 lines)
- `templates/` - File templates for generators
- `docs/CLI_GENERATORS.md` - Generator guide (~150 lines)

**Benefits:**
- ✅ Huge time savings (no more copy-paste)
- ✅ Consistent code structure
- ✅ Still simple (just file generation)
- ✅ 15-minute learning curve

---

## Feature #3: Simple Testing Helpers (Medium Priority)

**Status:** Planned
**Effort:** 1 week
**Priority:** Medium
**Complexity:** 🟢 Low

**What We're Adding:**
- Database testing helpers (transactions)
- Simple assertion helpers
- **NOT** a full testing framework

**Example Usage:**

```php
// tests/UserTest.php
class UserTest extends PHPUnit\Framework\TestCase {
    use DatabaseTransactions; // Rollback after each test

    public function testUserCreation() {
        $user = UserFactory::create([
            'email' => 'test@example.com'
        ]);

        // Simple helper
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }

    public function testUserValidation() {
        $errors = Validator::validate([
            'email' => 'invalid-email'
        ], [
            'email' => 'required|email'
        ]);

        $this->assertNotEmpty($errors);
    }
}
```

**What We're Adding:**
- `DatabaseTransactions` trait (auto-rollback)
- `assertDatabaseHas()`, `assertDatabaseMissing()` helpers
- That's it! Use PHPUnit for everything else.

**What We're NOT Adding:**
- ❌ HTTP testing framework (too complex)
- ❌ Mock helpers (PHPUnit has this)
- ❌ Custom test runner (use PHPUnit)
- ❌ Code coverage tools (use PHPUnit)

**Why This is Simple:**
- ✅ Uses PHPUnit (already familiar)
- ✅ Just adds database helpers
- ✅ No new concepts to learn

**Files to Create:**
- `coreapp/testing/DatabaseTransactions.php` - Trait (~100 lines)
- `coreapp/testing/Assertions.php` - Helper methods (~100 lines)
- `docs/TESTING.md` - Testing guide (~200 lines)

**Benefits:**
- ✅ Easy to test database code
- ✅ No test pollution (transactions)
- ✅ Still uses standard PHPUnit

---

## 📊 Feature Summary

| Feature | Priority | Effort | Complexity | Impact |
|---------|----------|--------|------------|--------|
| Simple Request Validation | High | 1 week | 🟢 Low | High |
| CLI Generators | High | 2 weeks | 🟢 Low | Very High |
| Simple Testing Helpers | Medium | 1 week | 🟢 Low | Medium |

**Total Effort:** 4 weeks (vs 16 weeks in original roadmap!)
**Complexity:** All features are 🟢 Low
**Philosophy:** All features maintain simplicity ✅

---

## 🗓️ Development Timeline

### Phase 1: Validation (Week 1)
- Simple Validator class
- Common validation rules
- Documentation and tests

### Phase 2: CLI Generators (Weeks 2-3)
- Simple CLI script
- File templates
- Generator documentation

### Phase 3: Testing Helpers (Week 4)
- Database transaction trait
- Assertion helpers
- Testing documentation

### Phase 4: Polish & Release (Weeks 5-6)
- Beta testing
- Documentation finalization
- Migration guide
- Performance verification

**Total Timeline:** 6 weeks (vs 16 weeks!)
**Target Release:** Q2 2026

---

## 🔧 Breaking Changes

**ZERO Breaking Changes** - v3.0.0 will be 100% backward compatible.

All features are opt-in:
- ✅ Don't use validation? Keep using your current method
- ✅ Don't use generators? Keep creating files manually
- ✅ Don't use testing helpers? Keep using plain PHPUnit

**This is a feature release, not a breaking release!**

---

## 📚 Documentation Philosophy

All documentation will emphasize simplicity:
- 📖 Each feature guide: 150-250 lines (not 500+)
- 🎯 Focus on examples, not theory
- ⏱️ "Learn in 15 minutes" sections
- 🚀 Copy-paste ready code

---

## 🎯 Success Metrics

v3.0.0 will be considered successful if:

### Simplicity Metrics
- ✅ Learning curve: <2 hours for all new features combined
- ✅ Documentation: Each feature guide <250 lines
- ✅ Code: Each new feature <500 lines (excluding tests)
- ✅ Zero new abstractions (no Request/Response objects)

### Compatibility Metrics
- ✅ 100% backward compatibility maintained
- ✅ Zero breaking changes
- ✅ All v2.5.0 code works unchanged

### Performance Metrics
- ✅ <1ms overhead for new features
- ✅ Zero performance regression
- ✅ Optional features have zero cost when unused

### Adoption Metrics
- ✅ 90%+ users say "still feels simple"
- ✅ No complaints about complexity
- ✅ Positive community feedback

---

## 💡 Future Considerations (v4.0+)

Features we're **explicitly NOT doing** now, but might consider later:

### Maybe for v4.0 (if there's strong demand):
- 🤔 Simple JSON response helpers (`Response::json()`)
- 🤔 Simple file upload helpers
- 🤔 Basic rate limiting (simple implementation)

### Probably Never:
- ❌ Full Request/Response objects (too complex)
- ❌ Model Events/Observers (unnecessary abstraction)
- ❌ API Resources (arrays work fine)
- ❌ Service Container (over-engineering)
- ❌ Dependency Injection (not needed)
- ❌ ORM relationships (Query Builder is enough)
- ❌ Task scheduling framework (use cron)

**Philosophy:** If a feature makes you think "wait, how does this work?", it's too complex for PHPWeave.

---

## 🎨 Design Principles

Every new feature must pass this test:

### The "Simplicity Checklist"

Before adding any feature, ask:

1. ✅ **Can it be explained in 3 sentences?**
2. ✅ **Can developers learn it in <30 minutes?**
3. ✅ **Does it solve a real pain point?**
4. ✅ **Is it opt-in (zero cost if unused)?**
5. ✅ **Does it avoid new abstractions?**
6. ✅ **Is it <500 lines of code?**
7. ✅ **Does it feel "obvious" to use?**

If any answer is "no," the feature is **rejected**.

---

## 📈 Comparison: v3.0.0 vs Original Plan

### Original Roadmap (REJECTED)
- ❌ 6 major features
- ❌ 16 weeks development
- ❌ Multiple new abstractions (Request, Response, Observer, Resource)
- ❌ Moving toward Laravel complexity

### New Roadmap (APPROVED) ✅
- ✅ 3 focused features
- ✅ 6 weeks development (63% faster!)
- ✅ Zero new abstractions
- ✅ Staying true to simplicity

---

## 🤝 Community Input

We're committed to simplicity, and we want your feedback!

**Questions to Ask Yourself:**
- Is PHPWeave still simple enough for you?
- Would these features add value without complexity?
- What features would you REMOVE (if any)?

**Feedback Channels:**
- GitHub Issues: https://github.com/clintcan/PHPWeave/issues
- Discussions: https://github.com/clintcan/PHPWeave/discussions

**Our Commitment:**
We will **reject** any feature that goes against our simplicity philosophy, even if it's popular in other frameworks.

---

## 🎯 The Bottom Line

**PHPWeave v3.0.0 is about doing LESS, not MORE.**

We're adding:
- ✅ 3 simple, high-impact features
- ✅ ~1,000 lines of new code (vs 10,000+ in original plan)
- ✅ 6 weeks development (vs 16 weeks)

We're maintaining:
- ✅ Zero dependencies
- ✅ Lightweight footprint
- ✅ Simple learning curve
- ✅ Backward compatibility
- ✅ Core philosophy: **Simplicity First**

---

**Last Updated:** November 12, 2025
**Version:** 2.0 (Revised for Simplicity)
**Status:** Active planning - Focused on simplicity ✅
**Previous Roadmap:** v1.0 (Rejected - too complex)
**Author:** PHPWeave Development Team

**Motto:** *"Stay simple, stay fast, stay focused."*
