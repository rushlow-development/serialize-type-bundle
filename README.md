# Serialize Bundle

This library is in early development, I wouldn't use it if I were you...

The goal of this library is to be able to take simple value objects and
persist them in a Doctrine Entity in Symfony. Since Doctrine DBAL is leaning
towards `json` for array-like structures, we need to be able to:

- Create a doctrine type that uses `json` as the column definition
- Serialize simple objects and `Collection`'s of objects.
- Deserialize objects and `Collection`'s of objects.

The last part is where it gets tricky. When serializing a simple value object in
an entity like:

```php
#[ORM\Entity]
class MyEntity
{
    public function __construct(
        #[ORM\Column(type: 'json')]
        private MySimpleObject $object
    ) {
    }
    
    // getters / setters / etc...
}
```

```php
class MySimpleObject
{
    public function __construct(
        public string $name,
        public string $description,
    ) {
    }
}
```

Doctrine is able to serialize `MySimpleObject` ultimately using `json_encode()` and
persist in `MyEntity`. Resulting in a column like `{"name": "some value",
"description": "A nice paragraph"}`. But, when we retrieve `MyEntity` from
persistence, doctrine attempts to set the `array` from `json_decode()` on
`MyEntity::object`. This triggers a type error and the world ends...

This bundle aims to be a "wrapper" more of less around then existing `JsonType`
provided by `doctrine/dbal`. During serialization, we end up with a column value
like `{"class": "App\\MySimpleObject", "data": {"name": "some value", "description": "A nice paragraph"}}`.
Then during deserialization, we can instantiate a `MySimpleObject` and pass
the array of `data` to either the constructor method or set the properties with
reflection.

I'm not sure if this approach is the most viable solution to the problem at hand.
I'm not even sure if the problem really _is_ a problem, or I'm just missing something
obvious. Hence, don't use this library unless you don't care about your data.
Wait for a `1.x` release, before then, nothing is certain...
