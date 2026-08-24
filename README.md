# Quillstack Serializer

[![Tests](https://github.com/quillstack/serializer/actions/workflows/tests.yml/badge.svg)](https://github.com/quillstack/serializer/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/quillstack/serializer.svg)](https://packagist.org/packages/quillstack/serializer)
[![Downloads](https://img.shields.io/packagist/dt/quillstack/serializer.svg)](https://packagist.org/packages/quillstack/serializer)
[![PHP Version](https://img.shields.io/packagist/php-v/quillstack/serializer)](https://packagist.org/packages/quillstack/serializer)
[![StyleCI](https://github.styleci.io/repos/1343714621/shield?branch=main)](https://github.styleci.io/repos/1343714621?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/quillstack/serializer/badge)](https://www.codefactor.io/repository/github/quillstack/serializer)
[![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=quillstack_serializer&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=quillstack_serializer)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=quillstack_serializer&metric=coverage)](https://sonarcloud.io/summary/new_code?id=quillstack_serializer)
[![Maintainability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_serializer&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_serializer)
[![Reliability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_serializer&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_serializer)
[![Security](https://sonarcloud.io/api/project_badges/measure?project=quillstack_serializer&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_serializer)
[![License](https://img.shields.io/packagist/l/quillstack/serializer)](https://github.com/quillstack/serializer/blob/main/LICENSE)

Turns objects into what goes over the wire, saying what may go rather than what may not. Full
documentation: https://quillstack.org/serializer

Every response written by hand is a place a field can be forgotten, and every response written
by exclusion is a place a field can escape. This is the other way round: a field is on the wire
because somebody said so, and nothing else ever is.

## Why this exists

There are two ways to decide what an object shows on the wire, and only one of them is safe.

A serializer that sends everything except a list of exclusions sends every new column on the day
it is added. Somebody adds `password_reset_token` to a table, the API starts returning it, and
nothing anywhere says so — not a test, not a type, not a review, because nothing changed in the
serializer.

This one goes the other way round: **a field is on the wire because somebody wrote `#[Exposed]`
above it**, and nothing else ever is. Adding a column is not a change to the API. Renaming a
property is caught by the type system rather than quietly dropping a field.

It does one direction only. There is no deserialization here — no turning JSON back into an
object — because the shape coming in is a different problem, and one that wants validation
rather than mapping.

## Requirements

- PHP 8.1 or newer

## Installation

```shell
composer require quillstack/serializer
```

## Usage

```php
use Quillstack\Serializer\Attributes\Exposed;

#[Table('users')]
final class User
{
    public function __construct(
        #[Id, Exposed] public ?int $id = null,
        #[Column, Exposed] public string $email = '',
        #[Column] public string $password = '',
        #[Column('created_at'), Exposed(name: 'created_at')] public ?DateTimeImmutable $createdAt = null,
    ) {
    }
}
```

```php
(new Serializer())->toArray($user);
// ['id' => 1, 'email' => 'ada@example.com', 'created_at' => '2026-08-23T10:00:00+00:00']
```

`password` is not there, and will not be there tomorrow either. **A serializer which sends
everything but a list of exclusions sends a new column on the day it is added, and says
nothing about it.**

### Audiences

One class can serve two readers without a second class written to hide a column:

```php
#[Exposed(groups: ['admin'])] public ?string $note = null;
```

```php
(new Serializer())->toArray($user);            // no note
(new Serializer(['admin']))->toArray($user);   // with it
```

A field with no group is for everybody, so adding an audience does not empty what was already
going out.

### What is inside

Nested objects are serialised the same way, which means nesting is not a way out either:

```php
(new Serializer())->toArray($user);
// ['id' => 1, 'posts' => [['id' => 7, 'title' => 'Hello']]]
```

| Value | Goes as |
| --- | --- |
| `int`, `float`, `string`, `bool`, `null` | itself |
| a backed enum | its value |
| a `DateTimeInterface` | ISO 8601 |
| an array or anything walked | a list, each serialised |
| an object with exposed fields | those fields |
| a `JsonSerializable` | whatever it says |

Anything an ORM walks — a relation holding many rows — is a list, because it is walked.

### When it refuses

An object with nothing exposed says so rather than answering `{}`, which would look like an
object that happens to be empty for as long as nobody noticed the attribute was missing.

Something pointing back at itself is refused at 32 deep, because the alternative is a stack
overflow with no explanation.

### In a response

```php
final class UserResponse extends Response
{
    public function __construct(private readonly Serializer $serializer)
    {
        parent::__construct();
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function send(): array
    {
        return $this->serializer->toArray($this->user);
    }
}
```

The response says which object answers; the object says which of its fields may be seen.
Neither has a list of what to leave out.

## Technical documentation

| Class | What it is |
| --- | --- |
| `Serializer` | `toArray()`, `toArrays()`, `toJson()` |
| `Attributes\Exposed` | `name` for a different name on the wire, `groups` for an audience |
| `Fields` | which properties of a class may go, worked out once and kept |
| `Exceptions\NothingExposedException` | nothing may go, or something points back at itself |

`Serializer::DEPTH` is how far it will follow one object into another — 32.

Reflection is done once per class and remembered, because a list of a thousand rows should not
read the same class a thousand times.

## Benchmark

Measured with [quillstack/benchmark](https://github.com/quillstack/benchmark) on ten thousand
objects — four exposed fields, one renamed, one never exposed — turned into arrays. All three
produce exactly the same output, including leaving the password out. Runs are interleaved and
unconcurrent, each figure is the median of five, and PHP is 8.5.7.

| | Version |
| --- | --- |
| quillstack/serializer | v0.6.0 |
| jms/serializer | 3.32.7 |
| symfony/serializer | v7.4.17 |

| | Per object | Relative |
| --- | --- | --- |
| **quillstack/serializer** | **0.59 µs** | — |
| jms/serializer | 3.55 µs | 6.0× |
| symfony/serializer | 8.59 µs | 14.5× |

**Both of the others do a great deal more**, and the list is worth reading before taking the
first row as a recommendation. They deserialize — JSON back into objects, which this cannot do
at all. They encode XML, CSV and YAML. They handle circular references, custom normalizers,
callbacks, maximum depth, and in JMS's case versioning with `@Since` and `@Until`. Fourteen
times the speed is what you get for a serializer that walks typed properties and reads one
attribute.

One thing found while setting this up is worth passing on: **JMS exposes every property by
default**, and the `#[Expose]` attributes do nothing until the class is also marked
`#[ExclusionPolicy('all')]`. Without that line the benchmark serialized the password. It is
documented behaviour and the fix is one attribute — but it is the failure mode this package was
built to remove, met on the first try.

## Tests

```shell
composer test
composer test:coverage
composer stan
```

## The rest of Quillstack

This is one component of [Quillstack](https://github.com/quillstack), a PHP framework which is
as simple to use as it is strict about what it does.

- [quillstack/response](https://github.com/quillstack/response) — what carries the result
- [quillstack/orm](https://github.com/quillstack/orm) — where the entities come from
- [quillstack/framework](https://github.com/quillstack/framework) — where a response serializes itself

## License

MIT. See [LICENSE](LICENSE).
