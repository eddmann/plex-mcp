# Project Guidelines

## Structure

- Laravel 12 app in `app/`.
- Local Docker config in `docker/`.
- Project-specific targets in the root `Makefile`.

**NOTE:** Prefer the root `Makefile` over ad-hoc commands.
Run `make help` to see available commands before using ad-hoc commands.
If an ad-hoc command is required, invoke it with: `make shell/"<command>"`.

## Conventions

- Avoid unnecessary abbreviations.
- ✅ `$response` ❌ `$resp`
- ✅ `$connection` ❌ `$conn`
- Prefer descriptive, full-word names using meaningful domain terms.

## Coding Standards

- Use PHP v8.4 features.
- Enforce coding style with `pint.json`.
- Lint: `make lint`.
- Fix style issues: `make fmt`.

### Testing Guidelines (Critical)

- Pest PHP is required.
- Test behaviour, not implementation.
- Use only the public API (treat internals as invisible).
- No 1:1 mapping between test files and implementation files.
- Tests document expected **business behaviour**.
- Name tests by **what** they verify, not **how**.
- Follow AAA (Arrange, Act, Assert).
- Each model must have a factory.
- Do not remove tests without approval.

```php
it('creates a result', function () {
    // Arrange
    $user = \App\Models\User::factory()->create();
    $round = \App\Models\Round::factory()->create();

    // Act
    $response = $this
        ->actingAs($user)
        ->post(route('results.store'), [
            'round_id' => $round->id,
        ]);

    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas('results', ['round_id' => $round->id]);
});
```
