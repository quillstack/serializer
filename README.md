# Quillstack Serializer

Turns objects into what goes over the wire, saying what may go rather than what may not. Full
documentation: https://quillstack.org/serializer

Every response written by hand is a place a field can be forgotten, and every response written
by exclusion is a place a field can escape. This is the other way round: a field is on the wire
because somebody said so, and nothing else ever is.

### Requirements

- PHP 8.1 or newer

### Installation

```shell
composer require quillstack/serializer
```

### Usage

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

### Technical documentation

| Class | What it is |
| --- | --- |
| `Serializer` | `toArray()`, `toArrays()`, `toJson()` |
| `Attributes\Exposed` | `name` for a different name on the wire, `groups` for an audience |
| `Fields` | which properties of a class may go, worked out once and kept |
| `Exceptions\NothingExposedException` | nothing may go, or something points back at itself |

`Serializer::DEPTH` is how far it will follow one object into another — 32.

Reflection is done once per class and remembered, because a list of a thousand rows should not
read the same class a thousand times.

### Unit tests

```shell
composer test
composer test:coverage
composer stan
```

### License

MIT. See [LICENSE](LICENSE).
